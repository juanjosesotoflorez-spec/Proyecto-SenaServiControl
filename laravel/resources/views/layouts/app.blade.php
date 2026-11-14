<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SenaServiControl')</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#main-content">Saltar al contenido principal</a>
    @auth
        @php($rol = auth()->user()->rol->value)
        @php($clientePerfil = $rol === 'cliente' ? auth()->user()->cliente : null)
        <div class="app-shell">
            <aside class="sidebar d-none d-lg-flex" aria-label="Navegación principal">
                <a class="brand" href="{{ route('dashboard') }}">
                    <span class="brand-mark" aria-hidden="true">SS</span>
                    <span><strong>SenaServi</strong><small>CONTROL</small></span>
                </a>
                <p class="sidebar-label">Espacio de trabajo</p>
                @include('layouts.partials.navigation')
                @if ($clientePerfil)
                    <a class="sidebar-profile" href="{{ route('perfil.edit') }}">
                        <img class="sidebar-profile-photo" src="{{ route('perfil.foto') }}" alt="">
                        <span class="sidebar-profile-copy">
                            <strong>Mi perfil</strong>
                            <small><span class="sidebar-footer-dot"></span>Operación activa</small>
                        </span>
                        <i class="fa-solid fa-chevron-right sidebar-profile-chevron" aria-hidden="true"></i>
                    </a>
                @else
                    <div class="sidebar-footer"><span class="sidebar-footer-dot"></span>Operación activa</div>
                @endif
            </aside>
            <div class="offcanvas offcanvas-start mobile-sidebar d-lg-none" tabindex="-1" id="mobile-navigation" aria-labelledby="mobile-navigation-title">
                <div class="offcanvas-header">
                    <div class="brand mobile-brand">
                        <span class="brand-mark" aria-hidden="true">SS</span>
                        <span><strong id="mobile-navigation-title">SenaServi</strong><small>CONTROL</small></span>
                    </div>
                    <button class="btn-close btn-close-white" type="button" data-bs-dismiss="offcanvas" aria-label="Cerrar menú"></button>
                </div>
                <div class="offcanvas-body">
                    <p class="sidebar-label">Espacio de trabajo</p>
                    @include('layouts.partials.navigation')
                </div>
            </div>
            <div class="app-main">
                <header class="topbar">
                    <div class="topbar-heading">
                        <button class="btn btn-success mobile-menu-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobile-navigation" aria-controls="mobile-navigation" aria-label="Abrir menú de navegación">
                            <i class="fa-solid fa-bars" aria-hidden="true"></i>
                        </button>
                        <div>
                        <p class="topbar-eyebrow">@yield('eyebrow', 'Panel de operaciones')</p>
                        <h1>@yield('page-title', 'Resumen')</h1>
                        </div>
                    </div>
                    <form class="quick-search" role="search" method="GET" action="{{ route('servicios.index') }}">
                        <label class="visually-hidden" for="quick-search-input">Buscar servicios, placas o clientes</label>
                        <input class="form-control" id="quick-search-input" name="q" type="search" value="{{ request('q') }}" placeholder="Buscar servicios, placas o clientes">
                        <button class="btn btn-success" type="submit" aria-label="Buscar"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
                    </form>
                    <div class="dropdown profile-menu">
                        <button class="btn profile-button" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Abrir menú de perfil">
                            @if ($clientePerfil)
                                <img class="user-avatar user-avatar-image" src="{{ route('perfil.foto') }}" alt="">
                            @else
                                <span class="user-avatar" aria-hidden="true"><i class="fa-regular fa-user"></i></span>
                            @endif
                            <span class="profile-name">{{ auth()->user()->usuario }}</span>
                            <i class="fa-solid fa-chevron-down profile-chevron" aria-hidden="true"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end profile-dropdown">
                            <li><span class="dropdown-item-text"><strong>{{ auth()->user()->usuario }}</strong><small>{{ ucfirst($rol) }}</small></span></li>
                            @if ($clientePerfil)
                                <li><a class="dropdown-item" href="{{ route('perfil.edit') }}"><i class="fa-regular fa-id-card me-2" aria-hidden="true"></i>Mi perfil</a></li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item" type="submit"><i class="fa-solid fa-arrow-right-from-bracket me-2" aria-hidden="true"></i>Cerrar sesión</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </header>
                <main class="content-area" id="main-content">
    @else
        <div class="guest-shell @yield('page-class')" style="--auth-background-image: url('{{ asset('fondo.png') }}')">
            <header class="guest-header">
                <a class="brand brand-guest" href="{{ route('login') }}">
                    <span class="brand-mark" aria-hidden="true">SS</span>
                    <span><strong>SenaServi</strong><small>CONTROL</small></span>
                </a>
            </header>
            <main class="guest-main" id="main-content">
    @endauth
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="status" aria-live="polite">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar aviso"></button>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert" aria-live="assertive" tabindex="-1">
                        <strong>Revisa la información del formulario</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar aviso"></button>
                    </div>
                @endif
                @yield('content')
            </main>
    @auth
        </div>
    @else
        </div>
    @endauth
        <script>
            (() => {
                const fields = (form) => Array.from(form.querySelectorAll('input, select, textarea'))
                    .filter((field) => !field.disabled && !['hidden', 'submit', 'button', 'reset', 'search'].includes(field.type));

                const feedbackFor = (field) => field.closest('.form-field')?.querySelector('[data-validation-feedback]');

                const validationMessage = (field) => {
                    const value = field.value;
                    const label = field.labels?.[0]?.textContent.trim().toLowerCase() || 'este campo';

                    if (field.required && value.trim() === '') return `El campo ${label} es obligatorio.`;
                    if (value === '') return '';

                    const minimumLength = field.getAttribute('minlength');
                    const maximumLength = field.getAttribute('maxlength');
                    if (minimumLength !== null && value.length < Number(minimumLength)) {
                        return Number(minimumLength) === Number(maximumLength)
                            ? `Debe tener exactamente ${minimumLength} caracteres.`
                            : `Debe tener al menos ${minimumLength} caracteres.`;
                    }
                    if (maximumLength !== null && value.length > Number(maximumLength)) return `No puede superar ${maximumLength} caracteres.`;
                    if (field.matches('[data-letters-only]') && !/^[\p{L} ]+$/u.test(value)) {
                        return 'Solo se permiten letras, tildes, ñ y espacios.';
                    }
                    if (field.matches('[data-digits-only]') && !/^[0-9]+$/.test(value)) {
                        return 'Solo se permiten números de 0 a 9.';
                    }
                    if (field.matches('[data-uppercase]') && !/^[A-Z0-9]{6}$/.test(value)) {
                        return 'La placa debe contener exactamente 6 letras mayúsculas o números.';
                    }
                    if (field.matches('[data-password-policy]')) {
                        const passwordRequirements = [
                            { rule: 'length', valid: value.length >= 8, label: 'al menos 8 caracteres' },
                            { rule: 'uppercase', valid: /\p{Lu}/u.test(value), label: 'una letra mayúscula' },
                            { rule: 'numbers', valid: (value.match(/\p{N}/gu) || []).length >= 2, label: 'al menos dos números' },
                            { rule: 'special', valid: /[^\p{L}\p{N}\s]/u.test(value), label: 'un carácter especial' },
                        ];
                        field.closest('.form-field')?.querySelectorAll('[data-password-rule]').forEach((item) => {
                            const requirement = passwordRequirements.find((rule) => rule.rule === item.dataset.passwordRule);
                            item.classList.toggle('is-met', Boolean(requirement?.valid));
                        });
                        const unmetRequirements = passwordRequirements.filter((requirement) => !requirement.valid);
                        if (value !== '' && unmetRequirements.length > 0) {
                            return `La contraseña debe incluir ${unmetRequirements.map((requirement) => requirement.label).join(', ')}.`;
                        }
                    }
                    if (field.type === 'email' && field.validity.typeMismatch) return 'Ingresa un correo electrónico válido.';
                    if (field.validity.patternMismatch) return field.title || 'El formato ingresado no es válido.';
                    if (field.type === 'date' && field.validity.rangeUnderflow) return 'La fecha no puede ser anterior a la fecha indicada.';
                    if (field.type === 'date' && field.validity.rangeOverflow) return 'La fecha supera el límite permitido.';

                    const numericValue = Number(value);
                    const minimum = field.getAttribute('min');
                    const maximum = field.getAttribute('max');
                    if (field.dataset.digitsOnly !== undefined && !/^[0-9]+$/.test(value)) return 'Ingresa solo números, sin puntos ni espacios.';
                    if (minimum !== null && Number.isFinite(numericValue) && numericValue < Number(minimum)) {
                        return `El valor mínimo permitido es ${minimum}.`;
                    }
                    if (maximum !== null && Number.isFinite(numericValue) && numericValue > Number(maximum)) {
                        return `El valor máximo permitido es ${maximum}.`;
                    }
                    if (field.validity.tooShort) return `Debe tener al menos ${field.minLength} caracteres.`;
                    if (field.name === 'contrasena_confirmation') {
                        const password = field.form.elements.namedItem('contrasena');
                        if (password && value !== password.value) return 'La confirmación de contraseña no coincide.';
                    }

                    return '';
                };

                const validateField = (field) => {
                    if (!field) return true;

                    const feedback = feedbackFor(field);
                    if (!feedback) return true;

                    if (field.dataset.serverInvalid === 'true' && field.value === field.dataset.initialValue) {
                        feedback.textContent = field.dataset.serverErrorMessage;
                        feedback.classList.add('d-block');
                        field.classList.add('is-invalid');
                        field.setAttribute('aria-invalid', 'true');
                        return false;
                    }

                    const message = validationMessage(field);
                    feedback.textContent = message;
                    feedback.classList.toggle('d-block', message !== '');
                    field.classList.toggle('is-invalid', message !== '');
                    field.setAttribute('aria-invalid', message !== '' ? 'true' : 'false');
                    return message === '';
                };

                document.querySelectorAll('form[data-validate]').forEach((form) => {
                    const formFields = fields(form);
                    formFields.forEach((field) => {
                        if (field.matches('[data-uppercase]')) field.value = field.value.toUpperCase();
                        const feedback = feedbackFor(field);
                        field.dataset.initialValue = field.value;
                        if (feedback?.textContent.trim()) {
                            field.dataset.serverInvalid = 'true';
                            field.dataset.serverErrorMessage = feedback.textContent.trim();
                            field.classList.add('is-invalid');
                            field.setAttribute('aria-invalid', 'true');
                        }

                        field.addEventListener('input', () => {
                            if (field.matches('[data-uppercase]')) {
                                field.value = field.value.toUpperCase();
                            }

                            if (field.name === 'fecha_ingreso') {
                                const deliveryDate = form.elements.namedItem('fecha_entrega_estimada');
                                if (deliveryDate) deliveryDate.min = field.value;
                            }

                            validateField(field);
                            if (field.name === 'contrasena') {
                                validateField(form.elements.namedItem('contrasena_confirmation'));
                            }
                        });
                        field.addEventListener('blur', () => {
                            validateField(field);
                        });
                        field.addEventListener('change', () => {
                            validateField(field);
                        });
                    });

                    form.addEventListener('submit', (event) => {
                        let valid = true;
                        formFields.forEach((field) => {
                            if (!validateField(field)) valid = false;
                        });
                        if (!valid) {
                            event.preventDefault();
                            form.querySelector('.is-invalid')?.focus();
                        }
                    });
                });
            })();
        </script>
</body>
</html>
