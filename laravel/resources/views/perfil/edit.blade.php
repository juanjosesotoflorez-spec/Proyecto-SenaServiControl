@extends('layouts.app')

@section('title', 'Mi perfil')
@section('page-title', 'Mi perfil')
@section('eyebrow', 'Cuenta del cliente')

@section('content')
    <div class="page-heading">
        <div>
            <h2>Datos de contacto</h2>
            <p>Actualiza tu correo, teléfono y dirección. Tus datos de identidad permanecen protegidos.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <section class="surface profile-photo-card h-100" aria-labelledby="profile-photo-heading">
                <div class="profile-photo-heading">
                    <span class="profile-photo-icon"><i class="fa-regular fa-image" aria-hidden="true"></i></span>
                    <div>
                        <h2 id="profile-photo-heading">Foto de perfil</h2>
                        <p>Personaliza tu cuenta con una imagen.</p>
                    </div>
                </div>
                <img
                    class="profile-photo-preview"
                    src="{{ route('perfil.foto') }}"
                    alt="Foto de perfil actual"
                    data-image-preview>
                <form method="POST" action="{{ route('perfil.foto.update') }}" enctype="multipart/form-data" data-validate novalidate>
                    @csrf
                    <div class="form-field mb-3">
                        <label class="form-label" for="foto">Seleccionar imagen</label>
                        <input
                            class="form-control @error('foto') is-invalid @enderror"
                            id="foto"
                            name="foto"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            required
                            data-image-preview-input
                            aria-describedby="foto-help foto-error">
                        <div class="profile-photo-help" id="foto-help">JPG, PNG o WebP; máximo 2 MB.</div>
                        <div class="invalid-feedback @error('foto') d-block @enderror" id="foto-error" data-validation-feedback>@error('foto'){{ $message }}@enderror</div>
                    </div>
                    <button class="btn btn-success w-100" type="submit">
                        <i class="fa-solid fa-camera me-2" aria-hidden="true"></i>Guardar foto
                    </button>
                </form>
            </section>
        </div>

        <div class="col-12 col-xl-8">
            <section class="surface profile-data-card" aria-labelledby="profile-contact-heading">
                <div class="profile-photo-heading">
                    <span class="profile-photo-icon"><i class="fa-regular fa-address-card" aria-hidden="true"></i></span>
                    <div>
                        <h2 id="profile-contact-heading">Información de contacto</h2>
                        <p>El correo también se usa para iniciar sesión.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('perfil.update') }}" data-validate novalidate>
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-12 form-field">
                            <label class="form-label" for="correo">Correo electrónico</label>
                            <input
                                class="form-control @error('correo') is-invalid @enderror"
                                id="correo"
                                name="correo"
                                type="email"
                                maxlength="100"
                                value="{{ old('correo', $usuario->correo) }}"
                                autocomplete="email"
                                required
                                aria-describedby="correo-error">
                            <div class="invalid-feedback @error('correo') d-block @enderror" id="correo-error" data-validation-feedback>@error('correo'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6 form-field">
                            <label class="form-label" for="telefono">Teléfono</label>
                            <input
                                class="form-control @error('telefono') is-invalid @enderror"
                                id="telefono"
                                name="telefono"
                                type="text"
                                inputmode="numeric"
                                minlength="10"
                                maxlength="10"
                                pattern="[0-9]{10}"
                                value="{{ old('telefono', $cliente->telefono) }}"
                                data-digits-only
                                aria-describedby="telefono-error">
                            <div class="invalid-feedback @error('telefono') d-block @enderror" id="telefono-error" data-validation-feedback>@error('telefono'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6 form-field">
                            <label class="form-label" for="direccion">Dirección</label>
                            <input
                                class="form-control @error('direccion') is-invalid @enderror"
                                id="direccion"
                                name="direccion"
                                type="text"
                                minlength="5"
                                maxlength="100"
                                value="{{ old('direccion', $cliente->direccion) }}"
                                aria-describedby="direccion-error">
                            <div class="invalid-feedback @error('direccion') d-block @enderror" id="direccion-error" data-validation-feedback>@error('direccion'){{ $message }}@enderror</div>
                        </div>
                    </div>
                    <div class="profile-form-actions">
                        <span><i class="fa-solid fa-lock me-1" aria-hidden="true"></i>Usuario, documento y rol no se pueden modificar.</span>
                        <button class="btn btn-success" type="submit">
                            <i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i>Guardar cambios
                        </button>
                    </div>
                </form>

                <div class="profile-locked-data" aria-label="Datos protegidos de la cuenta">
                    <div>
                        <span>Nombre</span>
                        <strong>{{ $cliente->nombre }} {{ $cliente->apellido }}</strong>
                    </div>
                    <div>
                        <span>Documento</span>
                        <strong>{{ $cliente->documento }}</strong>
                    </div>
                    <div>
                        <span>Usuario</span>
                        <strong>{{ $usuario->usuario }}</strong>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
