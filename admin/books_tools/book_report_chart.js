const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
new Chart(monthlyCtx, {
    data: {
        labels: monthlyLabels,
        datasets: [
            {
                type: 'bar',
                label: 'Books Added',
                data: monthlyAdded,
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderRadius: 6
            },
            {
                type: 'line',
                label: 'Total Books',
                data: monthlyTotals,
                borderColor: 'orange',
                borderWidth: 2,
                tension: 0.3,
                fill: false
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true }
        }
    }
});




// 🥧 Category Pie Chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
const categoryChart = new Chart(categoryCtx, {
    type: 'pie',
    data: {
        labels: categoryLabels,
        datasets: [{
            data: categoryTotals,
            backgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56',
                '#4BC0C0', '#9966FF', '#FF9F40',
                '#FF8C69', '#C9CBCF', '#8FBC8F',
                '#DDA0DD', '#F0E68C', '#87CEEB'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } }
    }
});

// Custom legend
const legendContainer = document.getElementById('categoryLegend');
categoryLabels.forEach((label, index) => {
    const li = document.createElement('li');
    li.style.display = "flex";
    li.style.alignItems = "center";
    li.style.gap = "6px";

    const colorBox = document.createElement('div');
    colorBox.style.width = '14px';
    colorBox.style.height = '14px';
    colorBox.style.borderRadius = '3px';
    colorBox.style.backgroundColor = categoryChart.data.datasets[0].backgroundColor[index];

    li.appendChild(colorBox);
    li.appendChild(document.createTextNode(` ${label}: ${categoryTotals[index]} books`));
    legendContainer.appendChild(li);
});
