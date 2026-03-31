@auth
<!-- MODAL GESTIÓN: ELECCIÓN DIRECTA -->
<div class="modal fade" id="modalGestionPerfil" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-sm modal-dialog-centered"> <!-- modal-sm para que sea pequeño -->
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h6 class="fw-bold text-white mb-0">Gestión de Cuenta</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Opción: Datos Personales -->
                <button type="button" class="btn btn-light w-100 mb-3 py-3 rounded-4 d-flex align-items-center shadow-sm border" data-bs-toggle="modal" data-bs-target="#modalDatos">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3">
                        <i class="fa-solid fa-user-gear text-primary"></i>
                    </div>
                    <div class="text-start">
                        <span class="d-block fw-bold text-dark">Mis Datos</span>
                        <small class="text-muted">Correo y teléfono</small>
                    </div>
                </button>

                <!-- Opción: Contraseña -->
                <button type="button" class="btn btn-light w-100 py-3 rounded-4 d-flex align-items-center shadow-sm border" data-bs-toggle="modal" data-bs-target="#modalPassword">
                    <div class="bg-warning bg-opacity-10 p-2 rounded-3 me-3">
                        <i class="fa-solid fa-lock text-warning"></i>
                    </div>
                    <div class="text-start">
                        <span class="d-block fw-bold text-dark">Contraseña</span>
                        <small class="text-muted">Cambiar clave</small>
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>
<!-- MODAL: FORMULARIO DATOS -->
<div class="modal fade" id="modalDatos" tabindex="-1" aria-hidden="true" style="z-index: 1070;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="{{ route('perfil.update.datos') }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header border-0 px-4 pt-4">
                    <h5 class="fw-bold">Actualizar Datos Personales</h5>
                </div>
                <div class="modal-body px-4">
                   
                   <!-- MENSAJE DE ÉXITO INTERNO -->
                  @if (session('success_datos'))
                      <div id="msj-exito" class="alert alert-success border-0 shadow-sm rounded-3 d-flex align-items-center p-2 mb-4" role="alert">
                          <i class="fa-solid fa-circle-check me-2"></i>
                          <div class="small fw-bold">{{ session('success_datos') }}</div>
                      </div>
                   @endif

                    <!-- MENSAJES DE ERROR INTERNOS -->
                    @if ($errors->any())
                        <div id= "msj-error" class="alert alert-danger border-0 shadow-sm rounded-3 p-2 mb-4" role="alert">
                            <ul class="mb-0 small">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <!-- Fila para Nombre y Apellido -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted">Nombre</label>
                            <input type="text" name="primer_nombre" class="form-control rounded-3" 
                                value="{{ auth()->user()->empleado->nombre ?? '' }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted">Apellido</label>
                            <input type="text" name="primer_apellido" class="form-control rounded-3" 
                                value="{{ auth()->user()->empleado->apellido ?? '' }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Correo Electrónico</label>
                        <input type="email" name="email" class="form-control rounded-3" 
                            value="{{ auth()->user()->email }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Teléfono</label>
                        <input type="text" name="telefono" class="form-control rounded-3" 
                            value="{{ auth()->user()->empleado->contacto ?? '' }}" placeholder="Ej: 9999-9999">
                    </div>
                </div>
               <div class="modal-footer border-0 px-4 pb-4">
                  <button type="button" class="btn btn-light rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalGestionPerfil">Volver</button>
                  <button type="submit" class="btn text-white rounded-pill px-4" style="background-color: #002855;">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: FORMULARIO CONTRASEÑA -->
<div class="modal fade" id="modalPassword" tabindex="-1" aria-hidden="true" style="z-index: 1070;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="{{ route('perfil.update.password') }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header border-0 px-4 pt-4 text-white" style="background-color: #002855;">
                    <h5 class="fw-bold mb-0">Cambiar mi Clave</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 pt-4">
                    
                    <!-- MENSAJE DE ÉXITO INTERNO -->
                   @if (session('success_password'))
                     <div id="msj-exito-pass" class="alert alert-success border-0 shadow-sm rounded-3 d-flex align-items-center p-2 mb-4" role="alert">
                         <i class="fa-solid fa-circle-check me-2"></i>
                         <div class="small fw-bold">{{ session('success_password') }}</div>
                      </div>
                    @endif

                    <!-- Errores de Validación -->
                    @if ($errors->has('current_password') || $errors->has('new_password'))
                        <div id="msj-error-pass" class="alert alert-danger border-0 py-2 small rounded-3 mb-3">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Contraseña Actual</label>
                        <div class="input-group">
                            <input type="password" name="current_password" id="current_password" class="form-control rounded-start-3" required>
                            <button class="btn btn-outline-secondary border-start-0 rounded-end-3" type="button" onclick="togglePassword('current_password', this)">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Nueva Contraseña</label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="new_password" class="form-control rounded-start-3" required>
                            <button class="btn btn-outline-secondary border-start-0 rounded-end-3" type="button" onclick="togglePassword('new_password', this)">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#modalGestionPerfil">Volver</button>
                    <button type="submit" class="btn text-white rounded-pill px-4" style="background-color: #002855;">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
   document.addEventListener("DOMContentLoaded", function() {
      // 1. Lógica de apertura de modales
      @if ($errors->has('current_password') || $errors->has('new_password') || session('success_password'))
          // Si hay errores de clave o éxito de clave, abrimos el de Password
          var modalPass = new bootstrap.Modal(document.getElementById('modalPassword'));
          modalPass.show();
       @elseif (session('success_datos') || $errors->any())
          // Si hay éxito de datos o cualquier otro error, abrimos Datos
          var modalDatos = new bootstrap.Modal(document.getElementById('modalDatos'));
          modalDatos.show();
        @endif

       // 2. Auto-cerrar alertas
      function autoCloseAlert(id) {
          var alert = document.getElementById(id);
          if (alert) {
             setTimeout(() => {
                  alert.style.transition = "opacity 0.5s ease";
                  alert.style.opacity = "0";
                  setTimeout(() => alert.remove(), 500);
                }, 3000);
            }
        }

       autoCloseAlert('msj-exito');
       autoCloseAlert('msj-error');
       autoCloseAlert('msj-exito-pass');
       autoCloseAlert('msj-error-pass');
    });

    // Función del ojito (esta está perfecta)
    function togglePassword(inputId, button) {
     var input = document.getElementById(inputId);
     var icon = button.querySelector('i');
      if (input.type === "password") {
         input.type = "text";
         icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
         input.type = "password";
         icon.classList.replace('fa-eye-slash', 'fa-eye');
       }
    }
</script>

@endauth