import './bootstrap';
import * as bootstrap from 'bootstrap';

document.querySelectorAll('.service-progress-modal').forEach((modal) => {
    document.body.append(modal);
});

document.querySelectorAll('[data-open-service-modal="true"]').forEach((modal) => {
    bootstrap.Modal.getOrCreateInstance(modal).show();
});

document.querySelectorAll('[data-image-preview-input]').forEach((input) => {
    const preview = input.form?.querySelector('[data-image-preview]');
    let previewUrl = null;

    if (!preview) return;

    input.addEventListener('change', () => {
        const file = input.files?.[0];
        if (!file) return;

        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(file);
        preview.src = previewUrl;
    });
});

if (document.querySelector('[data-dashboard-chart]')) {
    import('chart.js/auto').then(({ default: Chart }) => {
        document.querySelectorAll('[data-dashboard-chart]').forEach((canvas) => {
            const labels = JSON.parse(canvas.dataset.labels);
            const values = JSON.parse(canvas.dataset.values);

            if (canvas.dataset.dashboardChart === 'states') {
                new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels,
                        datasets: [{
                            data: values,
                            backgroundColor: ['#39A900', '#e3b341', '#3c91a5', '#39A900'],
                            borderWidth: 0,
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } },
                    },
                });

                return;
            }

            const services = JSON.parse(canvas.dataset.services);
            new Chart(canvas, {
                data: {
                    labels,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Ingresos',
                            data: values,
                            backgroundColor: '#39A900',
                            borderRadius: 4,
                            yAxisID: 'revenue',
                        },
                        {
                            type: 'line',
                            label: 'Servicios',
                            data: services,
                            borderColor: '#e3b341',
                            backgroundColor: '#e3b341',
                            tension: 0.3,
                            yAxisID: 'services',
                        },
                    ],
                },
                options: {
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        revenue: {
                            beginAtZero: true,
                            title: { display: true, text: 'Ingresos ($)' },
                            ticks: {
                                callback: (value) => `$${Number(value).toLocaleString('es-CO')}`,
                            },
                        },
                        services: {
                            beginAtZero: true,
                            position: 'right',
                            title: { display: true, text: 'Servicios' },
                            ticks: { precision: 0 },
                            grid: { drawOnChartArea: false },
                        },
                    },
                },
            });
        });
    });
}
