/**
 * Chart.js Integration Script
 * Custom muted color palette matching CSS design system HEX values.
 */

function renderYieldTrendChart(canvasId, labels, yieldData) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    if (typeof Chart === 'undefined') {
        showChartUnavailable(ctx);
        return;
    }

    if (window.yieldChartInstance instanceof Chart) {
        window.yieldChartInstance.destroy();
    }

    window.yieldChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Yield (kg/ha)',
                data: yieldData,
                borderColor: '#B85C38',
                backgroundColor: '#F3E5DC',
                borderWidth: 3,
                fill: true,
                tension: 0.3,
                pointBackgroundColor: '#FFFDF8',
                pointBorderColor: '#B85C38',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#162E24',
                    titleColor: '#FFFFFF',
                    bodyColor: '#FFFFFF',
                    padding: 10,
                    cornerRadius: 2,
                    displayColors: false,
                    callbacks: { label: context => `${Number(context.raw).toLocaleString()} kg/ha` }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#667066', font: { family: 'Inter', size: 11 } }
                },
                y: {
                    beginAtZero: false,
                    grid: { color: '#E3E1D8' },
                    ticks: { color: '#667066', font: { family: 'Inter', size: 11 }, callback: value => Number(value).toLocaleString() }
                }
            }
        }
    });
}

function renderClimateTrendChart(canvasId, labels, rainfallData, tempData) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    if (typeof Chart === 'undefined') {
        showChartUnavailable(ctx);
        return;
    }

    if (window.climateChartInstance instanceof Chart) {
        window.climateChartInstance.destroy();
    }

    window.climateChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Rainfall (mm)',
                    data: rainfallData,
                    borderColor: '#2E6650',
                    borderWidth: 2,
                    yAxisID: 'yRain',
                    tension: 0.25,
                    pointBackgroundColor: '#2E6650'
                },
                {
                    label: 'Avg Temp (°C)',
                    data: tempData,
                    borderColor: '#B85C38',
                    borderDash: [4, 4],
                    borderWidth: 2,
                    yAxisID: 'yTemp',
                    tension: 0.25,
                    pointBackgroundColor: '#B85C38'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', align: 'start', labels: { usePointStyle: true, boxWidth: 8, font: { family: 'Inter', size: 11 } } },
                tooltip: { backgroundColor: '#162E24', titleColor: '#FFFFFF', bodyColor: '#FFFFFF', cornerRadius: 2 }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#667066', font: { family: 'Inter', size: 11 } }
                },
                yRain: {
                    type: 'linear',
                    position: 'left',
                    grid: { color: '#E3E1D8' },
                    ticks: { color: '#667066', font: { size: 11 } }
                },
                yTemp: {
                    type: 'linear',
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    ticks: { color: '#B85C38', font: { size: 11 } }
                }
            }
        }
    });
}

function showChartUnavailable(canvas) {
    const frame = canvas.parentElement;
    canvas.hidden = true;
    const message = document.createElement('p');
    message.className = 'chart-unavailable';
    message.textContent = 'Chart library unavailable. The values remain available in the table below.';
    frame.appendChild(message);
}
