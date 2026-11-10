document.addEventListener('DOMContentLoaded', function () {
  const contactStatus = new URLSearchParams(window.location.search).get('contacto');
  const contactFeedback = document.getElementById('contactoMensaje');
  const contactForm = document.getElementById('contactoForm');

  if (contactForm && contactFeedback) {
    contactForm.addEventListener('invalid', function () {
      contactFeedback.classList.remove('d-none', 'alert-success', 'alert-danger');
      contactFeedback.classList.add('alert-warning');
      contactFeedback.textContent = 'Completa los campos obligatorios y corrige los datos marcados antes de enviar.';
    }, true);
  }

  if (contactStatus && contactFeedback) {
    const feedbackMessages = {
      enviado: ['alert-success', 'Tu mensaje fue enviado a la Serviteca. Gracias por contactarnos.'],
      error: ['alert-danger', 'El servidor de correo no está configurado o no está disponible. En XAMPP debes configurar un servicio SMTP autorizado y reiniciar Apache para poder enviar el mensaje.'],
      datos: ['alert-warning', 'Revisa los datos ingresados e inténtalo de nuevo.']
    };
    const feedback = feedbackMessages[contactStatus];

    if (feedback) {
      contactFeedback.classList.remove('d-none');
      contactFeedback.classList.add(feedback[0]);
      contactFeedback.textContent = feedback[1];
    }
  }

  const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
  const sidebar = document.getElementById('dashboardSidebar');

  if (sidebarToggle && sidebar) {
    const mobileSidebar = window.matchMedia('(max-width: 991.98px)');
    const updateExpanded = function () {
      const isOpen = mobileSidebar.matches
        ? document.body.classList.contains('dashboard-sidebar-open')
        : !document.body.classList.contains('dashboard-sidebar-collapsed');
      sidebarToggle.setAttribute('aria-expanded', String(isOpen));
    };

    sidebarToggle.addEventListener('click', function () {
      if (mobileSidebar.matches) {
        const opening = !document.body.classList.contains('dashboard-sidebar-open');
        document.body.classList.toggle('dashboard-sidebar-open', opening);
        if (opening) {
          const firstLink = sidebar.querySelector('a');
          if (firstLink) {
            firstLink.focus();
          }
        }
      } else {
        document.body.classList.toggle('dashboard-sidebar-collapsed');
      }
      updateExpanded();
    });

    document.querySelectorAll('[data-sidebar-close]').forEach(function (button) {
      button.addEventListener('click', function () {
        document.body.classList.remove('dashboard-sidebar-open');
        updateExpanded();
        sidebarToggle.focus();
      });
    });

    sidebar.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        if (mobileSidebar.matches) {
          document.body.classList.remove('dashboard-sidebar-open');
          updateExpanded();
        }
      });
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && document.body.classList.contains('dashboard-sidebar-open')) {
        document.body.classList.remove('dashboard-sidebar-open');
        updateExpanded();
        sidebarToggle.focus();
      }
    });

    mobileSidebar.addEventListener('change', function () {
      document.body.classList.remove('dashboard-sidebar-open');
      document.body.classList.remove('dashboard-sidebar-collapsed');
      updateExpanded();
    });
    updateExpanded();
  }

  const carousel = document.querySelector('#carouselInicio');
  if (carousel) {
    const items = carousel.querySelectorAll('.carousel-item');
    if (items.length > 0) {
      bootstrap.Carousel.getOrCreateInstance(carousel, {
        interval: 5000,
        ride: 'carousel'
      });
    }
  }

  document.querySelectorAll('[data-service-title]').forEach(function (button) {
    button.addEventListener('click', function () {
      const title = document.getElementById('modalServicioLabel');
      const description = document.getElementById('serviceDescription');
      const benefits = document.getElementById('serviceBenefits');

      if (title) {
        title.textContent = button.getAttribute('data-service-title');
      }
      if (description) {
        description.textContent = button.getAttribute('data-service-description');
      }
      if (benefits) {
        const list = button.getAttribute('data-service-benefits') || '';
        benefits.innerHTML = '';
        list.split('.').filter(Boolean).forEach(function (item) {
          const li = document.createElement('li');
          li.className = 'mb-2';
          li.textContent = item.trim();
          benefits.appendChild(li);
        });
      }
    });
  });
});
