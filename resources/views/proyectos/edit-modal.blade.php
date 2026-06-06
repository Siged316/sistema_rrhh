<div class="modal fade" id="modalEditarProyecto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="offcanvas-header modal-header text-white mb-2 py-3">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Proyecto / Meta + Equipo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="formEditarProyecto" method="POST">
                @csrf
                @method('PUT')
                
                <div id="hidden-designados-edit"></div>

                <div class="modal-body">
                    <div id="msj-modal-edit"></div>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-bold">Nombre del Proyecto</label>
                            <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-bold">Inicio</label>
                            <input type="date" name="fecha_inicio" id="edit_fecha_inicio" class="form-control" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-bold">Fin</label>
                            <input type="date" name="fecha_fin" id="edit_fecha_fin" class="form-control" required>
                        </div>
                    </div>

                    <hr>
                    <h6 class="fw-bold text-primary mb-3">1) Gestionar Equipo</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Filtrar por Departamento</label>
                            <select id="edit-select-departamento" class="form-select" size="6" onchange="cargarEmpleadosDepto(this.value)">
                                <option value="" disabled selected>Elija un área...</option>
                                @foreach($departamentos as $depto)
                                    <option value="{{ $depto->id }}">{{ strtoupper($depto->nombre) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Colaboradores Disponibles</label>
                            <div id="edit-contenedor-empleados" class="border rounded p-2 px-4 bg-light" style="height: 155px; overflow-y:auto;">
                                <p class="text-center text-muted mt-3 italic">Seleccione un departamento para ver colaboradores</p>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h6 class="fw-bold text-success mb-2">2) Tareas Asignadas</h6>
                    <button type="button" class="btn btn-sm btn-outline-success mb-2" onclick="agregarFilaManual()">
                        <i class="fas fa-plus"></i> Agregar tarea
                    </button>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle" id="tablaTareasEdit">
                            <thead class="table-dark text-center">
                                <tr style="font-size: 0.85rem;">
                                    <th style="width: 30%;">Título</th>
                                    <th style="width: 25%;">Asignado a</th>
                                    <th>Inicio</th>
                                    <th>Entrega</th>
                                    <th style="width: 8%;">Peso %</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn text-white rounded-pill px-4" style="background-color: #054084;"> 
                        <i class="fa-solid fa-rotate me-2"></i>Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 2000;">
    <div id="liveToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-check-circle me-2"></i> <span id="toast-mensaje"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
var listaColaboradoresModal = [];
var listaEmpleadosDelDepartamento = []; // Nueva lista maestra
document.getElementById('formEditarProyecto').addEventListener('submit', function(e) {
    e.preventDefault(); 

    const form = this;
    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';

    fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const modalEl = document.getElementById('modalEditarProyecto');
            const modalBS = bootstrap.Modal.getInstance(modalEl);
            if(modalBS) modalBS.hide();

            Swal.fire({
                title: '¡Actualizado!',
                text: data.message,
                icon: 'success',
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'Aceptar'
            }).then((result) => {
                if (result.isConfirmed) {
                    location.reload();
                }
            });
        } else {
            Swal.fire({
                title: 'Error',
                text: data.message || 'No se pudo actualizar',
                icon: 'error'
            });
        }
    })
    .catch(error => {
        Swal.fire('Error', 'Ocurrió un fallo en el servidor', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
});

function mostrarMensajeModal(mensaje, tipo = 'danger') {
    const contenedor = document.getElementById('msj-modal-edit');
    if (!contenedor) return;

    contenedor.innerHTML = `
        <div class="alert alert-${tipo} alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas ${tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'} me-2"></i>
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
    
    document.querySelector('#modalEditarProyecto .modal-body').scrollTop = 0;
}

function editarProyecto(id, boton) {
    boton.disabled = true;
    
    fetch(`/proyectos/${id}/edit`)
        .then(response => {
            if (!response.ok) throw new Error('Error en el servidor: ' + response.status);
            return response.json();
        })
        .then(data => {
            console.log("Datos recibidos:", data);
            // ... dentro de la función editarProyecto, en el .then(data => { ...
console.log("DEBUG: Contenido de data.equipo:", data.equipo); 

if (data.equipo && Array.isArray(data.equipo)) {
    listaColaboradoresModal = data.equipo;
} else {
    console.warn("DEBUG: data.equipo no es un array, se inicializa como []");
    listaColaboradoresModal = [];
}
sincronizarHiddenInputs();
            // 1. Llenar campos básicos del proyecto
            document.getElementById('edit_nombre').value = data.proyecto.nombre;
            document.getElementById('edit_fecha_inicio').value = data.proyecto.fecha_inicio;
            document.getElementById('edit_fecha_fin').value = data.proyecto.fecha_fin;

            // 2. Limpiar y llenar tabla de tareas
    const tbody = document.querySelector('#tablaTareasEdit tbody');
    tbody.innerHTML = ''; // Limpiar filas anteriores
    
    // Suponiendo que data.tareas es un array
    data.tareas.forEach((t, index) => {
        tbody.insertAdjacentHTML('beforeend', generarFilaTareaEdit(index, t));
    });

    //3. (Opcional) Si necesitas cargar el equipo seleccionado
     listaColaboradoresModal = data.equipo; 
     sincronizarHiddenInputs();

    // 4. Mostrar el modal
    const modalEl = document.getElementById('modalEditarProyecto');
    const modalBS = new bootstrap.Modal(modalEl);
    modalBS.show();
        })
        .catch(err => {
            console.error(err);
            alert("Error: " + err.message);
        })
        .finally(() => boton.disabled = false);
}

function generarFilaTareaEdit(index, t = {}) { 
    const colaboradores = Array.isArray(listaColaboradoresModal) ? listaColaboradoresModal : [];
    
    // Formateo de fechas: YYYY-MM-DD
    const fInicio = t.fecha_inicio ? String(t.fecha_inicio).split(' ')[0] : '';
    const fechaEntrega = t.fecha_entrega ? String(t.fecha_entrega).split(' ')[0] : '';

    return `<tr>
        <input type="hidden" name="tareas[${index}][id]" value="${t.id || ''}">
        <input type="hidden" name="tareas[${index}][estado]" value="${t.estado || 'Pendiente'}">
        
        <td><input name="tareas[${index}][titulo]" class="form-control" value="${t.titulo || ''}"></td>
        
        <td>
            <select name="tareas[${index}][asignado_user_id]" class="form-select select-asignado">
                <option value="">Seleccione...</option>
                ${colaboradores.map(u => 
                    `<option value="${u.id}" ${String(u.id) === String(t.asignado_user_id || '') ? 'selected' : ''}>
                        ${u.nombre}
                    </option>`
                ).join('')}
            </select>
        </td>
        
        <td><input type="date" name="tareas[${index}][fecha_inicio]" class="form-control" value="${fInicio}"></td>
        
        <td>
            <input type="date" name="tareas[${index}][fecha_entrega]" class="form-control bg-light" 
                   value="${fechaEntrega}" readonly>
        </td>
        
        <td><input type="number" name="tareas[${index}][peso]" class="form-control" value="${t.peso || ''}"></td>
        
        <td>
            <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>`;
}
function sincronizarHiddenInputs() {
    const cont = document.getElementById('hidden-designados-edit');
    if (!cont) return;
    
    // Validar existencia y tipo antes del map
    const lista = Array.isArray(window.listaColaboradoresModal) ? window.listaColaboradoresModal : [];
    
    cont.innerHTML = lista.map(c => `<input type="hidden" name="designados[]" value="${c.id}">`).join('');
}

function actualizarSelectsTareas() {
    // Si la lista maestra está vacía, intentamos al menos usar los ya seleccionados
    const colaboradores = listaEmpleadosDelDepartamento.length > 0 
                          ? listaEmpleadosDelDepartamento 
                          : listaColaboradoresModal;
    
    document.querySelectorAll('.select-asignado').forEach(s => {
        let val = s.value;
        s.innerHTML = '<option value="">Seleccione...</option>' + 
                      colaboradores.map(u => {
                          // Ajuste: si u viene de empleados, usamos user_id, si viene de equipo, usamos id
                          const id = u.user_id || u.id;
                          const nombre = u.nombre_completo || (u.nombre + ' ' + (u.apellido || ''));
                          
                          return `<option value="${id}" ${String(id) === String(val) ? 'selected' : ''}>
                                      ${nombre}
                                  </option>`;
                      }).join('');
    });
}

function cargarEmpleadosDepto(deptoId) {
    const contenedor = document.getElementById('edit-contenedor-empleados');
    contenedor.innerHTML = 'Cargando...';

    fetch(`/departamentos/${deptoId}/empleados`)
        .then(response => response.json())
        .then(data => {
            // 1. Guardamos todos los empleados recibidos en la lista maestra
            listaEmpleadosDelDepartamento = data; 
            
            contenedor.innerHTML = '';
            data.forEach(emp => {
                const idVincular = String(emp.user_id); 
                const estaEnEquipo = listaColaboradoresModal.some(c => String(c.id) === idVincular);
                
                contenedor.innerHTML += `
                    <div class="form-check form-switch mb-2 p-4 border-bottom">
                        <input class="form-check-input" type="checkbox" role="switch" 
                               id="edit-emp-${idVincular}" value="${idVincular}" ${estaEnEquipo ? 'checked' : ''}
                               onclick="gestionarSeleccionColaborador('${idVincular}', '${emp.nombre} ${emp.apellido}')">
                        <label class="form-check-label fw-bold small" for="edit-emp-${idVincular}">
                            ${emp.nombre} ${emp.apellido}
                        </label>
                    </div>`;
            });
            
            // 2. Actualizamos los selects usando la lista maestra
            actualizarSelectsTareas();
        });
}

function gestionarSeleccionColaborador(id, nombre) {
    id = String(id);
    const index = listaColaboradoresModal.findIndex(c => String(c.id) === id);
    if (index > -1) {
        listaColaboradoresModal.splice(index, 1);
    } else {
        listaColaboradoresModal.push({ id: id, nombre: nombre });
    }
    sincronizarHiddenInputs();
    actualizarSelectsTareas();
}


function agregarFilaManual() {
    const tbody = document.querySelector('#tablaTareasEdit tbody');
    const index = tbody.querySelectorAll('tr').length;
    tbody.insertAdjacentHTML('beforeend', generarFilaTareaEdit(index));
}
</script>