import OwnerDashboardAPI from "/resources/js/api/ownerDashboardApi.js";
import Toast from "/resources/js/utils/toast.js";

const MONTH_LABELS = [
  "January",
  "February",
  "March",
  "April",
  "May",
  "June",
  "July",
  "August",
  "September",
  "October",
  "November",
  "December",
];

const PERIOD_LABELS = {
  daily: "Daily",
  weekly: "Weekly",
  monthly: "Monthly",
  yearly: "Yearly",
};

const PERIOD_VALUES = Object.keys(PERIOD_LABELS);

const REVENUE_BAR_COLORS = [
  "#DCBAE6",
  "#9659A7",
  "#DCBAE6",
  "#9659A7",
  "#DCBAE6",
  "#9659A7",
  "#DCBAE6",
  "#9659A7",
  "#DCBAE6",
  "#9659A7",
  "#DCBAE6",
  "#9659A7",
];

const PIE_COLORS = ["#9659A7", "#DCBAE6", "#FFF2D9", "#F4D6D2", "#CDBAA7", "#8CC6D7"];

const elements = {
  periodSelector: document.getElementById("periodSelector"),
  salesPeriodSelector: document.getElementById("salesPeriodSelector"),
  yearSelector: document.getElementById("yearSelector"),
  monthSelector: document.getElementById("monthSelector"),
  reportSectionTitle: document.getElementById("reportSectionTitle"),
  salesSectionTitle: document.getElementById("salesSectionTitle"),
  revenueChartCanvas: document.getElementById("revenueChart"),
  salesChartCanvas: document.getElementById("salesChart"),
  revenueNoDataHint: document.getElementById("revenueNoDataHint"),
  salesNoDataHint: document.getElementById("salesNoDataHint"),
  weeklyTotalSales: document.getElementById("weeklyTotalSales"),
  weeklyItemsSold: document.getElementById("weeklyItemsSold"),
  lowStockItemName: document.getElementById("lowStockItemName"),
  weeklyTotalOrders: document.getElementById("weeklyTotalOrders"),
  weeklyReceivedPayment: document.getElementById("weeklyReceivedPayment"),
  weeklyPendingPayment: document.getElementById("weeklyPendingPayment"),
  weeklyCanceledOrders: document.getElementById("weeklyCanceledOrders"),
};

let dashboardState = null;
let revenueBarChart = null;
let salesPieChart = null;
let isFetching = false;

document.addEventListener("DOMContentLoaded", () => {
  if (!hasRequiredElements()) {
    return;
  }

  dashboardState = normalizeDashboardData(readBootstrapData());
  syncSelectorValues();
  renderSummaryCards();
  renderSectionTitles();
  renderNoDataHints();
  persistPeriodsInQuery(dashboardState.selected_report_period, dashboardState.selected_sales_period);

  if (typeof window.Chart !== "function") {
    Toast.error("Chart library failed to load. Please refresh the page.");
    return;
  }

  initCharts();
  bindEvents();
});

function hasRequiredElements() {
  return (
    elements.periodSelector &&
    elements.salesPeriodSelector &&
    elements.yearSelector &&
    elements.monthSelector &&
    elements.revenueChartCanvas &&
    elements.salesChartCanvas
  );
}

function bindEvents() {
  elements.periodSelector.addEventListener("change", handleReportPeriodChange);
  elements.salesPeriodSelector.addEventListener("change", handleSalesPeriodChange);
  elements.yearSelector.addEventListener("change", handleYearChange);
  elements.monthSelector.addEventListener("change", handleMonthChange);
}

async function handleYearChange() {
  if (isFetching) {
    return;
  }

  const selectedYear = Number(elements.yearSelector.value);

  if (!Number.isInteger(selectedYear)) {
    return;
  }

  await refreshDashboardMetrics();
}

async function handleReportPeriodChange() {
  if (isFetching) {
    return;
  }

  const selectedReportPeriod = normalizePeriodValue(elements.periodSelector.value);

  if (!selectedReportPeriod) {
    syncSelectorValues();
    return;
  }

  await refreshDashboardMetrics({ report_period: selectedReportPeriod });
}

async function handleSalesPeriodChange() {
  if (isFetching) {
    return;
  }

  const selectedSalesPeriod = normalizePeriodValue(elements.salesPeriodSelector.value);

  if (!selectedSalesPeriod) {
    syncSelectorValues();
    return;
  }

  await refreshDashboardMetrics({ sales_period: selectedSalesPeriod });
}

async function refreshDashboardMetrics(periodOverrides = {}) {
  const selectedYear = Number(elements.yearSelector.value);
  const selectedMonth = Number(elements.monthSelector.value);

  if (!Number.isInteger(selectedYear)) {
    return;
  }

  const selectedReportPeriod =
    normalizePeriodValue(periodOverrides.report_period) ||
    normalizePeriodValue(elements.periodSelector.value) ||
    dashboardState.selected_report_period;

  const selectedSalesPeriod =
    normalizePeriodValue(periodOverrides.sales_period) ||
    normalizePeriodValue(elements.salesPeriodSelector.value) ||
    dashboardState.selected_sales_period;

  isFetching = true;

  try {
    const response = await OwnerDashboardAPI.getMetrics({
      year: selectedYear,
      month: Number.isInteger(selectedMonth) ? selectedMonth : dashboardState.selected_month,
      report_period: selectedReportPeriod,
      sales_period: selectedSalesPeriod,
    });

    if (!response.success) {
      Toast.error(response.message || "Unable to refresh dashboard metrics.");
      syncSelectorValues();
      return;
    }

    dashboardState = normalizeDashboardData(response.data);
    syncSelectorValues();
    renderSummaryCards();
    renderSectionTitles();
    renderNoDataHints();
    updateCharts();
    persistPeriodsInQuery(dashboardState.selected_report_period, dashboardState.selected_sales_period);
  } finally {
    isFetching = false;
  }
}

function handleMonthChange() {
  const selectedMonth = Number(elements.monthSelector.value);

  if (!Number.isInteger(selectedMonth) || selectedMonth < 0 || selectedMonth > 11) {
    syncSelectorValues();
    return;
  }

  dashboardState.selected_month = selectedMonth;
  renderNoDataHints();
  updateSalesChart();
}

function readBootstrapData() {
  const bootstrapElement = document.getElementById("ownerDashboardBootstrap");

  if (!bootstrapElement) {
    return {};
  }

  try {
    return JSON.parse(bootstrapElement.textContent || "{}");
  } catch (error) {
    console.error("Failed to parse dashboard bootstrap payload", error);
    return {};
  }
}

function normalizeDashboardData(payload = {}) {
  const now = new Date();
  const fallbackYear = now.getFullYear();
  const fallbackMonth = now.getMonth();

  const availableYearsRaw = Array.isArray(payload.available_years)
    ? payload.available_years
    : [fallbackYear];

  const availableYears = availableYearsRaw
    .map((year) => Number(year))
    .filter((year) => Number.isInteger(year));

  const selectedYearCandidate = Number(payload.selected_year);
  const selectedMonthCandidate = Number(payload.selected_month);
  const selectedReportPeriodCandidate = normalizePeriodValue(payload.selected_report_period);
  const selectedSalesPeriodCandidate = normalizePeriodValue(payload.selected_sales_period);
  const selectedPeriodCandidate = normalizePeriodValue(payload.selected_period);

  const selectedYear = availableYears.includes(selectedYearCandidate)
    ? selectedYearCandidate
    : availableYears.includes(fallbackYear)
      ? fallbackYear
      : availableYears[availableYears.length - 1] || fallbackYear;

  const selectedMonth =
    Number.isInteger(selectedMonthCandidate) && selectedMonthCandidate >= 0 && selectedMonthCandidate <= 11
      ? selectedMonthCandidate
      : fallbackMonth;

  const selectedReportPeriod = selectedReportPeriodCandidate || selectedPeriodCandidate || "weekly";
  const selectedSalesPeriod = selectedSalesPeriodCandidate || selectedPeriodCandidate || "weekly";

  const monthlyRevenueRaw = Array.isArray(payload.charts?.monthly_revenue)
    ? payload.charts.monthly_revenue
    : [];

  const monthlyRevenue = MONTH_LABELS.map((_, monthIndex) => {
    const candidate = Number(monthlyRevenueRaw[monthIndex] ?? 0);
    return Number.isFinite(candidate) ? candidate : 0;
  });

  const monthlySalesLabels = {};
  const monthlySalesValues = {};
  const monthlySalesHasData = {};

  for (let monthIndex = 0; monthIndex < 12; monthIndex++) {
    const key = String(monthIndex);

    const labelsCandidate = payload.charts?.monthly_sales?.labels_by_month?.[key];
    const valuesCandidate = payload.charts?.monthly_sales?.values_by_month?.[key];

    const labels = Array.isArray(labelsCandidate)
      ? labelsCandidate.map((label) => String(label))
      : [];

    const values = Array.isArray(valuesCandidate)
      ? valuesCandidate.map((value) => {
        const castValue = Number(value);
        return Number.isFinite(castValue) ? castValue : 0;
      })
      : [];

    const hasDataCandidate = payload.charts?.monthly_sales?.has_data_by_month?.[key];

    monthlySalesHasData[key] =
      typeof hasDataCandidate === "boolean"
        ? hasDataCandidate
        : values.some((value) => value > 0);

    if (labels.length > 0 && values.length > 0 && labels.length === values.length) {
      monthlySalesLabels[key] = labels;
      monthlySalesValues[key] = values;
      continue;
    }

    monthlySalesLabels[key] = ["No data"];
    monthlySalesValues[key] = [0];
    monthlySalesHasData[key] = false;
  }

  const weeklyReport = {
    total_sales: Number(payload.weekly_report?.total_sales ?? 0),
    items_sold: Number(payload.weekly_report?.items_sold ?? 0),
    low_stock_item_name: payload.weekly_report?.low_stock_item_name || null,
  };

  const weeklySales = {
    total_orders: Number(payload.weekly_sales?.total_orders ?? 0),
    received_payment: Number(payload.weekly_sales?.received_payment ?? 0),
    pending_payment: Number(payload.weekly_sales?.pending_payment ?? 0),
    canceled_orders: Number(payload.weekly_sales?.canceled_orders ?? 0),
  };

  return {
    available_years: availableYears.length > 0 ? availableYears : [fallbackYear],
    selected_year: selectedYear,
    selected_month: selectedMonth,
    selected_period: selectedReportPeriod,
    selected_report_period: selectedReportPeriod,
    selected_sales_period: selectedSalesPeriod,
    weekly_report: weeklyReport,
    weekly_sales: weeklySales,
    charts: {
      monthly_revenue: monthlyRevenue,
      has_revenue_data:
        typeof payload.charts?.has_revenue_data === "boolean"
          ? payload.charts.has_revenue_data
          : monthlyRevenue.some((value) => value > 0),
      monthly_sales: {
        labels_by_month: monthlySalesLabels,
        values_by_month: monthlySalesValues,
        has_data_by_month: monthlySalesHasData,
      },
    },
  };
}

function syncSelectorValues() {
  elements.yearSelector.value = String(dashboardState.selected_year);
  elements.monthSelector.value = String(dashboardState.selected_month);
  elements.periodSelector.value = dashboardState.selected_report_period;
  elements.salesPeriodSelector.value = dashboardState.selected_sales_period;
}

function renderSummaryCards() {
  if (elements.weeklyTotalSales) {
    elements.weeklyTotalSales.textContent = `Php ${formatMoney(dashboardState.weekly_report.total_sales)}`;
  }

  if (elements.weeklyItemsSold) {
    elements.weeklyItemsSold.textContent = String(Math.max(0, Math.trunc(dashboardState.weekly_report.items_sold || 0)));
  }

  if (elements.lowStockItemName) {
    elements.lowStockItemName.textContent =
      dashboardState.weekly_report.low_stock_item_name || "No low stock items";
  }

  if (elements.weeklyTotalOrders) {
    elements.weeklyTotalOrders.textContent = String(Math.max(0, Math.trunc(dashboardState.weekly_sales.total_orders || 0)));
  }

  if (elements.weeklyReceivedPayment) {
    elements.weeklyReceivedPayment.textContent = String(
      Math.max(0, Math.trunc(dashboardState.weekly_sales.received_payment || 0)),
    );
  }

  if (elements.weeklyPendingPayment) {
    elements.weeklyPendingPayment.textContent = String(
      Math.max(0, Math.trunc(dashboardState.weekly_sales.pending_payment || 0)),
    );
  }

  if (elements.weeklyCanceledOrders) {
    elements.weeklyCanceledOrders.textContent = String(
      Math.max(0, Math.trunc(dashboardState.weekly_sales.canceled_orders || 0)),
    );
  }
}

function renderSectionTitles() {
  const reportPeriodLabel = PERIOD_LABELS[dashboardState.selected_report_period] || PERIOD_LABELS.weekly;
  const salesPeriodLabel = PERIOD_LABELS[dashboardState.selected_sales_period] || PERIOD_LABELS.weekly;

  if (elements.reportSectionTitle) {
    elements.reportSectionTitle.textContent = `${reportPeriodLabel} Report`;
  }

  if (elements.salesSectionTitle) {
    elements.salesSectionTitle.textContent = `${salesPeriodLabel} Sales`;
  }
}

function renderNoDataHints() {
  if (elements.revenueNoDataHint) {
    elements.revenueNoDataHint.hidden = dashboardState.charts.has_revenue_data;
  }

  const selectedMonthKey = String(dashboardState.selected_month);
  const hasSalesDataForMonth = Boolean(
    dashboardState.charts.monthly_sales.has_data_by_month[selectedMonthKey],
  );

  if (elements.salesNoDataHint) {
    elements.salesNoDataHint.hidden = hasSalesDataForMonth;
  }
}

function initCharts() {
  const revenueContext = elements.revenueChartCanvas.getContext("2d");
  const salesContext = elements.salesChartCanvas.getContext("2d");

  revenueBarChart = new window.Chart(revenueContext, {
    type: "bar",
    data: {
      labels: MONTH_LABELS,
      datasets: [
        {
          label: "Revenue (Php)",
          data: dashboardState.charts.monthly_revenue,
          backgroundColor: REVENUE_BAR_COLORS,
          borderRadius: 10,
          barThickness: 40,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            display: false,
          },
          ticks: {
            font: {
              family: "Coolvetica",
            },
          },
        },
        x: {
          grid: {
            display: false,
          },
          ticks: {
            font: {
              family: "Coolvetica",
            },
          },
        },
      },
    },
  });

  const monthData = getSelectedMonthData();

  salesPieChart = new window.Chart(salesContext, {
    type: "pie",
    data: {
      labels: monthData.labels,
      datasets: [
        {
          data: monthData.values,
          backgroundColor: monthData.values.map((_, index) => PIE_COLORS[index % PIE_COLORS.length]),
          borderWidth: 0,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: "top",
          labels: {
            font: {
              family: "Coolvetica",
              size: 12,
            },
            usePointStyle: true,
            boxWidth: 8,
          },
        },
      },
    },
  });
}

function updateCharts() {
  updateRevenueChart();
  updateSalesChart();
}

function updateRevenueChart() {
  if (!revenueBarChart) {
    return;
  }

  revenueBarChart.data.datasets[0].data = dashboardState.charts.monthly_revenue;
  revenueBarChart.update();
}

function updateSalesChart() {
  if (!salesPieChart) {
    return;
  }

  const monthData = getSelectedMonthData();

  salesPieChart.data.labels = monthData.labels;
  salesPieChart.data.datasets[0].data = monthData.values;
  salesPieChart.data.datasets[0].backgroundColor = monthData.values.map(
    (_, index) => PIE_COLORS[index % PIE_COLORS.length],
  );

  salesPieChart.update();
}

function getSelectedMonthData() {
  const monthKey = String(dashboardState.selected_month);

  const labels = dashboardState.charts.monthly_sales.labels_by_month[monthKey] || ["No data"];
  const values = dashboardState.charts.monthly_sales.values_by_month[monthKey] || [0];

  return {
    labels,
    values,
  };
}

function normalizePeriodValue(value) {
  if (typeof value !== "string") {
    return null;
  }

  const normalized = value.trim().toLowerCase();

  return PERIOD_VALUES.includes(normalized) ? normalized : null;
}

function persistPeriodsInQuery(reportPeriod, salesPeriod) {
  const normalizedReportPeriod = normalizePeriodValue(reportPeriod);
  const normalizedSalesPeriod = normalizePeriodValue(salesPeriod);

  if (!normalizedReportPeriod || !normalizedSalesPeriod) {
    return;
  }

  const currentUrl = new URL(window.location.href);
  currentUrl.searchParams.set("report_period", normalizedReportPeriod);
  currentUrl.searchParams.set("sales_period", normalizedSalesPeriod);

  // Keep legacy period query in sync with report period for old links and fallback support.
  currentUrl.searchParams.set("period", normalizedReportPeriod);

  const queryString = currentUrl.searchParams.toString();
  const nextUrl = queryString
    ? `${currentUrl.pathname}?${queryString}`
    : currentUrl.pathname;

  window.history.replaceState({}, "", nextUrl);
}

function formatMoney(value) {
  return new Intl.NumberFormat("en-PH", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number(value || 0));
}
