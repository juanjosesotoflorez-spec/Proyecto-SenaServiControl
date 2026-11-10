document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-numeric-only]').forEach(function (input) {
    input.addEventListener('input', function () {
      const cursor = input.selectionStart;
      const valueBeforeSanitizing = input.value;
      const digitsBeforeCursor = valueBeforeSanitizing.slice(0, cursor).replace(/\D/g, '').length;
      const digitsOnly = valueBeforeSanitizing.replace(/\D/g, '');

      if (input.value !== digitsOnly) {
        input.value = digitsOnly;
        input.setSelectionRange(digitsBeforeCursor, digitsBeforeCursor);
      }
    });
  });

  let fieldSequence = 0;

  function addSubmitConfirmation(form) {
    if (form.method.toLowerCase() !== 'post' || form.dataset.skipConfirmation === 'true') {
      return;
    }

    form.addEventListener('submit', function (event) {
      if (event.defaultPrevented) {
        return;
      }

      if (!window.confirm('¿Confirmas que completaste el formulario y deseas enviar los datos?')) {
        event.preventDefault();
      }
    });
  }

  document.querySelectorAll('form').forEach(function (form) {
    const fields = Array.from(form.elements).filter(function (field) {
      return field.willValidate;
    });

    if (fields.length === 0) {
      addSubmitConfirmation(form);
      return;
    }

    form.noValidate = true;
    let submitted = false;
    const updateMessages = new Map();

    fields.forEach(function (field) {
      const message = document.createElement('small');
      message.className = 'form-validation-message';
      message.id = `form-validation-${fieldSequence++}`;
      message.hidden = true;
      message.setAttribute('aria-live', 'polite');
      field.insertAdjacentElement('afterend', message);

      const describedBy = field.getAttribute('aria-describedby');
      field.setAttribute('aria-describedby', [describedBy, message.id].filter(Boolean).join(' '));

      function getLabel() {
        const label = field.labels?.[0] || field.closest('.mb-3, [class*="col-"]')?.querySelector('.form-label');
        return label?.textContent.trim().replace(/[:*]\s*$/, '').toLocaleLowerCase() || 'este campo';
      }

      function getError() {
        const matchingField = field.dataset.match
          ? form.querySelector(field.dataset.match)
          : null;

        if (matchingField && field.value !== matchingField.value) {
          return field.dataset.validationMessage || 'Los valores no coinciden.';
        }

        const validity = field.validity;
        if (validity.valueMissing) {
          return `Completa ${getLabel()}.`;
        }
        if (validity.patternMismatch) {
          return field.title || `Revisa el formato de ${getLabel()}.`;
        }
        if (validity.typeMismatch && field.type === 'email') {
          return 'Escribe un correo válido, por ejemplo nombre@dominio.com.';
        }
        if (validity.tooShort) {
          return `Escribe al menos ${field.minLength} caracteres.`;
        }
        if (validity.tooLong) {
          return `Usa como máximo ${field.maxLength} caracteres.`;
        }
        if (validity.rangeUnderflow) {
          return `El valor mínimo permitido es ${field.min}.`;
        }
        if (validity.rangeOverflow) {
          return `El valor máximo permitido es ${field.max}.`;
        }
        if (validity.stepMismatch || validity.badInput) {
          return `Ingresa un valor válido para ${getLabel()}.`;
        }
        return '';
      }

      function updateMessage(show) {
        const error = show ? getError() : '';
        message.textContent = error;
        message.hidden = error === '';
        field.classList.toggle('is-invalid', error !== '');
        field.setAttribute('aria-invalid', String(error !== ''));
      }

      updateMessages.set(field, updateMessage);

      field.addEventListener('blur', function () {
        updateMessage(true);
      });
      field.addEventListener('input', function () {
        updateMessage(submitted || field.dataset.touched === 'true');
      });
      field.addEventListener('change', function () {
        updateMessage(submitted || field.dataset.touched === 'true');
      });
      field.addEventListener('focus', function () {
        field.dataset.touched = 'true';
      });

      const matchingField = field.dataset.match
        ? form.querySelector(field.dataset.match)
        : null;
      if (matchingField) {
        matchingField.addEventListener('input', function () {
          updateMessage(submitted || field.dataset.touched === 'true');
        });
      }
    });

    form.addEventListener('submit', function (event) {
      submitted = true;
      const invalidFields = fields.filter(function (field) {
        const matchingField = field.dataset.match
          ? form.querySelector(field.dataset.match)
          : null;
        return !field.validity.valid || (matchingField && field.value !== matchingField.value);
      });

      fields.forEach(function (field) {
        updateMessages.get(field)(true);
      });

      if (invalidFields.length > 0) {
        event.preventDefault();
        invalidFields[0].focus();
      }
    });

    addSubmitConfirmation(form);
  });
});