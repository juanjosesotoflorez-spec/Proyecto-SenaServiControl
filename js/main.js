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
      const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      const carouselInstance = bootstrap.Carousel.getOrCreateInstance(carousel, {
        interval: 6500,
        pause: false,
        ride: false
      });
      const toggle = carousel.querySelector('[data-carousel-toggle]');
      const toggleLabel = toggle?.querySelector('[data-carousel-toggle-label]');
      const toggleIcon = toggle?.querySelector('.hero-carousel-toggle-icon');
      const counter = carousel.querySelector('[data-carousel-count]');
      let userPaused = reducedMotion;
      let pointerPaused = false;

      const setPaused = function (paused) {
        if (!toggle) return;

        toggle.setAttribute('aria-pressed', String(paused));
        toggle.setAttribute('aria-label', paused ? 'Reanudar carrusel' : 'Pausar carrusel');
        if (toggleLabel) toggleLabel.textContent = paused ? 'Reanudar' : 'Pausar';
        if (toggleIcon) toggleIcon.textContent = paused ? '▶' : 'Ⅱ';
      };

      const updatePlayback = function () {
        if (userPaused || pointerPaused) {
          carouselInstance.pause();
        } else {
          carouselInstance.cycle();
        }
        setPaused(userPaused);
      };

      toggle?.addEventListener('click', function () {
        userPaused = !userPaused;
        updatePlayback();
      });

      carousel.addEventListener('mouseenter', function () {
        pointerPaused = true;
        updatePlayback();
      });

      carousel.addEventListener('mouseleave', function () {
        pointerPaused = false;
        updatePlayback();
      });

      updatePlayback();

      carousel.addEventListener('slid.bs.carousel', function (event) {
        if (counter && typeof event.to === 'number') {
          counter.textContent = String(event.to + 1).padStart(2, '0');
        }
        updatePlayback();
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
