/**
 * Rush Fees Page - Dynamic Rendering
 * Renders each price range as a styled card with a timeframe table inside.
 */

// ================= IMPORTS =================
import rushFeeApi from "../../api/rushFeeApi.js";

// ================= STATE =================
let rushFees = [];

// ================= DOM ELEMENTS =================
const tableWrapper = document.getElementById("rushFeesTableWrapper");

// ================= INITIALIZATION =================
document.addEventListener("DOMContentLoaded", () => {
    initRushFeesPage();
});

async function initRushFeesPage() {
    showLoadingState();
    try {
        await loadRushFees();
        if (rushFees.length === 0) {
            showEmptyState();
        } else {
            renderRushFeeCards();
        }
    } catch (error) {
        console.error("Error initializing rush fees page:", error);
        showErrorState();
    }
}

// ================= LOAD DATA =================
async function loadRushFees() {
    rushFees = await rushFeeApi.getAllRushFees();
}

// ================= RENDER CARDS =================
function renderRushFeeCards() {
    tableWrapper.innerHTML = "";

    rushFees.forEach((rushFee, index) => {
        const card = buildCard(rushFee, index);
        tableWrapper.appendChild(card);
    });
}

function buildCard(rushFee, index) {
    // ---- Outer card ----
    const card = document.createElement("div");
    card.className = "rush_fee_card";
    card.style.animationDelay = `${0.1 + index * 0.12}s`;

    // ---- Corner sparkles ----
    ["tl", "tr", "bl", "br"].forEach((pos) => {
        const star = document.createElement("span");
        star.className = `card_star card_star_${pos}`;
        star.textContent = "✦";
        card.appendChild(star);
    });

    // ---- Card header (price range) ----
    const header = document.createElement("div");
    header.className = "rush_fee_card_header";

    const eyebrow = document.createElement("p");
    eyebrow.className = "range_eyebrow";

    eyebrow.innerHTML = `
        <span class="eyebrow_star">✦</span>
        ${rushFee.label || "Price Range"}
        <span class="eyebrow_star">✦</span>
    `;
    header.appendChild(eyebrow);

    const rangeValue = document.createElement("p");
    rangeValue.className = "range_value";

    rangeValue.textContent = formatPriceRange(
        rushFee.min_price,
        rushFee.max_price,
    );
    header.appendChild(rangeValue);

    card.appendChild(header);

    // ---- Table ----
    if (rushFee.timeframes && rushFee.timeframes.length > 0) {
        const table = buildTable(rushFee.timeframes);
        card.appendChild(table);
    }

    return card;
}

function buildTable(timeframes) {
    const table = document.createElement("table");
    table.className = "rush_fee_table";

    // Header
    const thead = document.createElement("thead");
    const headerRow = document.createElement("tr");

    const th1 = document.createElement("th");
    th1.textContent = "Timeframe";
    headerRow.appendChild(th1);

    const th2 = document.createElement("th");
    th2.textContent = "Percentage Added to Total";
    headerRow.appendChild(th2);

    thead.appendChild(headerRow);
    table.appendChild(thead);

    // Body
    const tbody = document.createElement("tbody");

    timeframes.forEach((timeframe) => {
        const row = document.createElement("tr");

        const tdLabel = document.createElement("td");
        tdLabel.className = "td_timeframe";
        tdLabel.textContent = timeframe.label || "—";
        row.appendChild(tdLabel);

        const tdPct = document.createElement("td");
        tdPct.className = "td_percentage";
        tdPct.textContent = timeframe.percentage
            ? `${formatPercentage(timeframe.percentage)}%`
            : "—";
        row.appendChild(tdPct);

        tbody.appendChild(row);
    });

    table.appendChild(tbody);
    return table;
}

// ================= FORMAT HELPERS =================
function formatPriceRange(minPrice, maxPrice) {
    const hasMinPrice =
        minPrice !== null && minPrice !== undefined && minPrice !== "";
    const hasMaxPrice =
        maxPrice !== null && maxPrice !== undefined && maxPrice !== "";

    if (!hasMinPrice && !hasMaxPrice) return "—";
    if (!hasMinPrice) return "—";

    const minValue = Number.parseFloat(String(minPrice));
    if (Number.isNaN(minValue)) return "—";

    if (!hasMaxPrice) return `₱${minValue.toFixed(0)}+`;

    const maxValue = Number.parseFloat(String(maxPrice));
    if (Number.isNaN(maxValue)) return `₱${minValue.toFixed(0)}+`;

    const min = minValue.toFixed(0);
    const max = maxValue.toFixed(0);
    return `₱${min} – ₱${max}`;
}

function formatPercentage(percentage) {
    const num = parseFloat(percentage);
    return isNaN(num) ? "0" : num.toString();
}

// ================= STATE RENDERERS =================
function showLoadingState() {
    tableWrapper.innerHTML = "";
}

function showEmptyState() {
    tableWrapper.innerHTML = `
        <div class="rush_fees_empty">
            <span class="empty_star">✦</span>
            <h2>No Rush Fees Available</h2>
            <p>Check back later for rush fee information.</p>
        </div>
    `;
}

function showErrorState() {
    tableWrapper.innerHTML = `
        <div class="rush_fees_empty">
            <span class="empty_star">✦</span>
            <h2>Couldn't Load Rush Fees</h2>
            <p>Something went wrong. Please try refreshing the page.</p>
        </div>
    `;
}
