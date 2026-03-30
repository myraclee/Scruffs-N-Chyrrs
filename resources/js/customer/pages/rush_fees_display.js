/**
 * Rush Fees Page - Dynamic Rendering
 * Fetches rush fees from API and renders table with nested timeframe rows
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
            renderRushFeesTable();
        }
    } catch (error) {
        console.error("Error initializing rush fees page:", error);
        showErrorState();
    }
}

// ================= LOAD DATA =================
async function loadRushFees() {
    try {
        rushFees = await rushFeeApi.getAllRushFees();
    } catch (error) {
        console.error("Error fetching rush fees:", error);
        throw error;
    }
}

// ================= RENDER TABLE =================
function renderRushFeesTable() {
    tableWrapper.innerHTML = "";

    const table = document.createElement("table");
    table.className = "rush_fees_table";

    // Table header
    const thead = document.createElement("thead");
    const headerRow = document.createElement("tr");

    const rangeTh = document.createElement("th");
    rangeTh.textContent = "Price Range";
    headerRow.appendChild(rangeTh);

    const timeframeTh = document.createElement("th");
    timeframeTh.textContent = "Turnaround Time";
    headerRow.appendChild(timeframeTh);

    const feeTh = document.createElement("th");
    feeTh.textContent = "Rush Fee";
    headerRow.appendChild(feeTh);

    thead.appendChild(headerRow);
    table.appendChild(thead);

    // Table body
    const tbody = document.createElement("tbody");

    rushFees.forEach((rushFee) => {
        // Main rush fee row (first timeframe)
        if (rushFee.timeframes && rushFee.timeframes.length > 0) {
            const firstTimeframe = rushFee.timeframes[0];

            // First row with price range
            const firstRow = document.createElement("tr");

            const rangeCell = document.createElement("td");
            rangeCell.className = "rush_fee_range";
            rangeCell.rowSpan = rushFee.timeframes.length;
            rangeCell.textContent = formatPriceRange(
                rushFee.min_price,
                rushFee.max_price,
            );
            firstRow.appendChild(rangeCell);

            const timeframeCell = document.createElement("td");
            timeframeCell.className = "timeframe_label";
            timeframeCell.textContent = firstTimeframe.label || "—";
            firstRow.appendChild(timeframeCell);

            const feeCell = document.createElement("td");
            feeCell.className = "timeframe_percentage";
            feeCell.textContent = firstTimeframe.percentage
                ? `+${formatPercentage(firstTimeframe.percentage)}%`
                : "—";
            firstRow.appendChild(feeCell);

            tbody.appendChild(firstRow);

            // Additional timeframe rows
            for (let i = 1; i < rushFee.timeframes.length; i++) {
                const timeframe = rushFee.timeframes[i];
                const row = document.createElement("tr");
                row.className = "rush_fee_timeframe_row";

                // Empty cell for price range (since we merged it above)
                const emptyCell = document.createElement("td");
                row.appendChild(emptyCell);

                const tfCell = document.createElement("td");
                tfCell.className = "timeframe_label";
                tfCell.textContent = timeframe.label || "—";
                row.appendChild(tfCell);

                const pctCell = document.createElement("td");
                pctCell.className = "timeframe_percentage";
                pctCell.textContent = timeframe.percentage
                    ? `+${formatPercentage(timeframe.percentage)}%`
                    : "—";
                row.appendChild(pctCell);

                tbody.appendChild(row);
            }
        }
    });

    table.appendChild(tbody);
    tableWrapper.appendChild(table);
}

// ================= FORMAT HELPERS =================
function formatPriceRange(minPrice, maxPrice) {
    if (!minPrice || !maxPrice) return "—";

    const min = parseFloat(minPrice).toFixed(0);
    const max = parseFloat(maxPrice).toFixed(0);

    return `₱${min} - ₱${max}`;
}

function formatPercentage(percentage) {
    const num = parseFloat(percentage);
    return isNaN(num) ? "0" : num.toString();
}

// ================= STATE RENDERERS =================
function showLoadingState() {
    tableWrapper.innerHTML =
        '<div class="rush_fees_loading">Loading rush fees...</div>';
}

function showEmptyState() {
    const emptyDiv = document.createElement("div");
    emptyDiv.className = "rush_fees_empty";
    emptyDiv.innerHTML = `
        <h2>No Rush Fees Available</h2>
        <p>Check back later for rush fee information.</p>
    `;
    tableWrapper.appendChild(emptyDiv);
}

function showErrorState() {
    const errorDiv = document.createElement("div");
    errorDiv.className = "rush_fees_empty";
    errorDiv.innerHTML = `
        <h2>Error Loading Rush Fees</h2>
        <p>We encountered an error loading rush fee information. Please try refreshing the page.</p>
    `;
    tableWrapper.appendChild(errorDiv);
}
