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
  yearSelector: document.getElementById("yearSelector"),
  monthSelector: document.getElementById("monthSelector"),
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
  renderNoDataHints();

  if (typeof window.Chart !== "function") {
    Toast.error("Chart library failed to load. Please refresh the page.");
    return;
  }

  initCharts();
  bindEvents();
});

function hasRequiredElements() {
  return (
    elements.yearSelector &&
    elements.monthSelector &&
    elements.revenueChartCanvas &&
    elements.salesChartCanvas
  );
}

function bindEvents() {
  elements.yearSelector.addEventListener("change", handleYearChange);
  elements.monthSelector.addEventListener("change", handleMonthChange);
}

async function handleYearChange() {
  if (isFetching) {
    return;
  }

  const selectedYear = Number(elements.yearSelector.value);
  const selectedMonth = Number(elements.monthSelector.value);

  if (!Number.isInteger(selectedYear)) {
    return;
  }

  isFetching = true;

  try {
    const response = await OwnerDashboardAPI.getMetrics({
      year: selectedYear,
      month: Number.isInteger(selectedMonth) ? selectedMonth : dashboardState.selected_month,
    });

    if (!response.success) {
      Toast.error(response.message || "Unable to refresh dashboard metrics.");
      syncSelectorValues();
      return;
    }

    dashboardState = normalizeDashboardData(response.data);
    syncSelectorValues();
    renderSummaryCards();
    renderNoDataHints();
    updateCharts();
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

  const selectedYear = availableYears.includes(selectedYearCandidate)
    ? selectedYearCandidate
    : availableYears.includes(fallbackYear)
      ? fallbackYear
      : availableYears[availableYears.length - 1] || fallbackYear;

  const selectedMonth =
    Number.isInteger(selectedMonthCandidate) && selectedMonthCandidate >= 0 && selectedMonthCandidate <= 11
      ? selectedMonthCandidate
      : fallbackMonth;

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

function formatMoney(value) {
  return new Intl.NumberFormat("en-PH", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number(value || 0));
}
