import './bootstrap';
import * as bootstrap from 'bootstrap';

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const inputId = button.getAttribute('aria-controls');
    const input = inputId ? document.getElementById(inputId) : null;
    const icon = button.querySelector('i');

    if (!(input instanceof HTMLInputElement) || !icon) return;

    button.addEventListener('click', () => {
        const isVisible = input.type === 'text';
        input.type = isVisible ? 'password' : 'text';
        button.setAttribute('aria-label', isVisible ? 'Mostrar contraseña' : 'Ocultar contraseña');
        button.setAttribute('aria-pressed', String(!isVisible));
        icon.classList.toggle('fa-eye', isVisible);
        icon.classList.toggle('fa-eye-slash', !isVisible);
    });
});

document.querySelectorAll('.service-progress-modal, .vehicle-delivery-modal').forEach((modal) => {
    document.body.append(modal);
});

document.querySelectorAll('[data-open-service-modal="true"]').forEach((modal) => {
    bootstrap.Modal.getOrCreateInstance(modal).show();
});

const deliveryFallback = (modal) => {
    if (!modal.classList.contains('show')) {
        document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
        modal.classList.add('show');
        modal.style.display = 'block';
        modal.removeAttribute('aria-hidden');
        modal.setAttribute('aria-modal', 'true');
        document.body.classList.add('modal-open');
        document.body.style.overflow = 'hidden';

        if (!document.querySelector('.modal-backdrop')) {
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.append(backdrop);
        }
    }
};

document.querySelectorAll('[data-bs-target*="vehicle-delivery-modal"], [data-bs-target*="service-progress-modal"]').forEach((button) => {
    button.addEventListener('click', () => {
        const target = button.dataset.bsTarget;
        const modal = target ? document.querySelector(target) : null;
        if (!(modal instanceof HTMLElement)) return;

        const instance = bootstrap.Modal.getOrCreateInstance(modal, { backdrop: true, keyboard: true });
        instance.show();
        window.setTimeout(() => deliveryFallback(modal), 80);
    });
});

document.querySelectorAll('.vehicle-delivery-modal [data-bs-dismiss="modal"], .service-progress-modal [data-bs-dismiss="modal"]').forEach((button) => {
    button.addEventListener('click', () => {
        const modal = button.closest('.modal');
        if (!(modal instanceof HTMLElement)) return;

        bootstrap.Modal.getInstance(modal)?.hide();
        modal.classList.remove('show');
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        modal.removeAttribute('aria-modal');
        document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
    });
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
