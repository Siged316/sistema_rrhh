<!-- Offcanvas para registrar un nuevo usuario -->
<div class="offcanvas offcanvas-end border-0 shadow" tabindex="-1" id="offcanvasNuevoUsuario">

    <div class="offcanvas-header bg-primary text-white">
        <h5 class="offcanvas-title fw-bold">
            <i class="fa-solid fa-user-plus me-2"></i> Registrar Usuario
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body">
        <form method="POST" action="{{ route('usuarios.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-bold text-secondary small">EMPLEADO</label>
                <select name="empleado_id" class="form-select border-2 shadow-sm" required>
                    <option value="">Seleccione un empleado...</option>
                    @foreach($empleados as $emp)
                        <option value="{{ $emp->id }}">
                            {{ strtoupper($emp->nombre) }} {{ strtoupper($emp->apellido ?? '') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold text-secondary small">NOMBRE DE USUARIO</label>
                <input type="text" name="usuario" class="form-control border-2" placeholder="Ej: jlopez" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold text-secondary small">CORREO INSTITUCIONAL</label>
                <div class="input-group shadow-sm">
                    <span class="input-group-text border-2 bg-light">
                        <i class="fa-solid fa-envelope text-primary"></i>
                    </span>
                    <input type="email" name="email" class="form-control border-2" placeholder="usuario@empresa.com" required>
                    <input type="hidden" name="debe_cambiar_password" value="1">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold text-secondary small">ROL DEL SISTEMA</label>
                <select name="role_id" class="form-select border-2" required>
                    @foreach($roles as $rol)
                        <option value="{{ $rol->id }}">{{ $rol->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg shadow fw-bold">
                    <i class="fa-solid fa-save me-2"></i> Crear Usuario
                </button>
                <button type="button" class="btn btn-secondary btn-lg fw-bold" data-bs-dismiss="offcanvas">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Script para mostrar/ocultar la contraseña temporal -->
<script>
    function togglePass(id) {
        const input = document.getElementById(id); // Input de contraseña
        const icon = document.getElementById('icon_new'); // Icono del ojo
        if (input.type === "password") {
            input.type = "text"; // Mostrar contraseña
            icon.classList.replace('fa-eye', 'fa-eye-slash'); // Cambiar icono
        } else {
            input.type = "password"; // Ocultar contraseña
            icon.classList.replace('fa-eye-slash', 'fa-eye'); // Cambiar icono
        }
    }
</script>



