{{-- Extiende la plantilla principal de la aplicación --}}
@extends('layouts.app')

{{-- Sección de contenido principal --}}
@section('content')

{{-- Contenedor principal del login --}}
<div class="login-main-wrapper">

    {{-- Caja del login con sombra --}}
    <div class="login-container shadow-lg">

        {{-- Fila sin espacios entre columnas --}}
        <div class="row g-0">

            {{-- Columna izquierda (solo visible en pantallas medianas o grandes) --}}
            <div class="col-md-6 d-none d-md-flex flex-column align-items-center justify-content-center bg-light-custom p-5">

                {{-- Contenedor del logo --}}
                <div class="logo-circle-bg mb-4 d-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm"
                    style="width: 150px; height: 150px; margin: 0 auto; overflow: hidden;">
                    {{-- Logo de la institución --}}
                    <img src="{{ asset('images/ihci_logo.jpg') }}" 
                         alt="Logo IHCI" 
                         class="img-fluid"
                         style="max-width: 80%; height: auto;">
                </div>

                {{-- Título de bienvenida --}}
                <h2 class="fw-bold text-dark">¡Bienvenido!</h2>

                {{-- Descripción del sistema --}}
                <p class="text-muted text-center">
                    Sistema de Recursos Humanos <br> IHCI
                </p>
            </div>

            {{-- Columna derecha: formulario de login --}}
            <div class="col-md-6 bg-white p-5 d-flex flex-column justify-content-center">

                {{-- Encabezado del formulario --}}
                <div class="form-header mb-4">
                    <h3 class="fw-bold">Iniciar Sesión</h3>
                </div>

                {{-- BLOQUE PARA MOSTRAR ERRORES --}}
                @if ($errors->any())
                   <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                      <ul class="mb-0 ps-3">
                          @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                          @endforeach
                       </ul>
                       <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>
                @endif

                {{-- Formulario de autenticación --}}
                <form method="POST" action="{{ route('login.post') }}">

                    {{-- Token CSRF para seguridad --}}
                    @csrf
                    
                    {{-- Campo usuario --}}
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">USUARIO</label>

                        {{-- Input group con ícono --}}
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0">
                                <i class="fa-solid fa-user text-muted"></i>
                            </span>

                            {{-- Campo de texto para usuario --}}
                            <input type="text" 
                                   name="usuario" 
                                   class="form-control bg-light border-0 py-2" 
                                   placeholder="Ingresa tu usuario" 
                                   required 
                                   autofocus>
                        </div>
                    </div>

                    {{-- Campo contraseña --}}
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">CONTRASEÑA</label>

                        {{-- Input group con ícono --}}
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0">
                                <i class="fa-solid fa-lock text-muted"></i>
                            </span>

                            {{-- Campo password --}}
                            <input type="password" 
                                   name="password" 
                                   id="password"
                                   class="form-control bg-light border-0 py-2" 
                                   placeholder="" 
                                   required>
                                   {{-- Botón manual para mostrar/ocultar --}}
                           <button class="btn btn-light border-0" type="button" id="togglePassword">
                             <i class="fa-solid fa-eye text-muted" id="eyeIcon"></i>
                             </button>
                        </div>
                    </div>

                    {{-- Botón de envío --}}
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary-ihci btn-lg shadow-sm">
                            ENTRAR 
                            <i class="fa-solid fa-arrow-right ms-2"></i>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

{{-- scrip para el btn de ver contraseña --}}
<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');
    const eyeIcon = document.querySelector('#eyeIcon');

    togglePassword.addEventListener('click', function (e) {
        // Cambiar el tipo de atributo
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        
        // Cambiar el ícono (opcional)
        eyeIcon.classList.toggle('fa-eye');
        eyeIcon.classList.toggle('fa-eye-slash');
    });
</script>

<script>
    // Esperar a que el documento cargue
    document.addEventListener("DOMContentLoaded", function() {
        // Buscar las alertas con clase 'alert'
        const alert = document.querySelector('.alert');
        
        if (alert) {
            // Esperar 4 segundos (4000 milisegundos)
            setTimeout(function() {
                // Hacer un efecto de desvanecimiento suave (fade out)
                alert.style.transition = "opacity 0.5s ease";
                alert.style.opacity = "0";
                
                // Eliminar el elemento del DOM después de la transición
                setTimeout(function() {
                    alert.remove();
                }, 500);
            }, 4000); 
        }
    });
</script>
{{-- Fin de la sección de contenido --}}
@endsection
