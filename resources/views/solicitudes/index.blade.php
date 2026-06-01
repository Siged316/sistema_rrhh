@extends('layouts.app')

@section('content')

<div class="container mt-4 mb-5">

    {{-- ENCABEZADO --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">
                <i class="fas fa-file-invoice me-2 text-primary"></i>Historial de Solicitudes
            </h2>
            <p class="text-muted small mb-0">Gestión de permisos y formato oficial FT-GTH-001</p>
        </div>
    </div>

    {{-- FORMULARIO DE FILTRADO --}}
    <form action="{{ route('solicitudes.index') }}" method="GET" id="form-filtros-unico">
        <div class="card mb-4 border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    {{-- Buscar empleado --}}
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">BUSCAR EMPLEADO</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" placeholder="Nombre..." value="{{ request('search') }}">
                        </div>
                    </div>

                    {{-- Filtrar por Mes --}}
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">MES</label>
                        <select name="mes" class="form-select" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            @foreach(['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] as $index => $mes)
                                <option value="{{ $index + 1 }}" {{ request('mes') == ($index + 1) ? 'selected' : '' }}>
                                    {{ $mes }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Botones --}}
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 fw-bold">BUSCAR</button>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('solicitudes.index') }}" class="btn btn-outline-secondary w-100 fw-bold">LIMPIAR</a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- TABLA DE SOLICITUDES --}}
    <div class="card shadow-sm border-0 overflow-hidden" style="border-radius:15px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle shadow-sm mb-0">
                    <thead>
                        <tr class="text-center">
                            <th class="bg-primary text-white">Empleado / Cargo</th>
                            <th class="bg-primary text-white">Tipo</th>
                            <th class="bg-primary text-white">Periodo</th>
                            <th class="bg-primary text-white">Estado</th>
                            <th class="bg-primary text-white">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                       
                        @forelse(isset($solicitudes) ? $solicitudes : [] as $solicitud)
                            @php
                                $aprobaciones = $solicitud->aprobaciones ?? collect();
                                $estadoDB = strtolower($solicitud->estado);

                                $tieneFirmaJefe = $aprobaciones->where('paso_orden', 1)->isNotEmpty();
                                $tieneFirmaGTH = $aprobaciones->where('paso_orden', 2)->isNotEmpty();

                                if ($estadoDB == 'rechazado') {
                                    $color = 'danger'; 
                                    $texto = 'RECHAZADO'; 
                                    $icono = 'fa-times-circle';
                                } elseif ($tieneFirmaGTH) {
                                    $color = 'success'; 
                                    $texto = 'COMPLETADO'; 
                                    $icono = 'fa-check-double';
                                } elseif ($tieneFirmaJefe || $estadoDB == 'en proceso') {
                                    $color = 'info'; 
                                    $texto = 'GTH'; 
                                    $icono = 'fa-spinner fa-spin';
                                } else {
                                    $color = 'warning'; 
                                    $texto = 'JEFE'; 
                                    $icono = 'fa-clock';
                                }

                                // Determinar si el usuario en sesión es el jefe asignado a esta solicitud
                                $user = auth()->user();
                                $empleadoId = $user->empleado->id ?? null;
                                $rolNombre = $user->rol->nombre ?? null;
                                $miDepto = $user->empleado->departamento->nombre ?? '';

                                $esJefeDeEstaSol = false;
                              $departamentoSolicitud = \App\Models\Departamento::where(
                                  'nombre',
                                   $solicitud->departamento
                                )->first();

                                if (
                                  $departamentoSolicitud &&
                                  $departamentoSolicitud->jefe_empleado_id == $empleadoId
                                ) {
                                 $esJefeDeEstaSol = true;
                                }

                                // Lógica de turnos para habilitar botones
                                $esMiTurnoParaFirmar = false;
                                if ($esJefeDeEstaSol && !$tieneFirmaJefe && $estadoDB !== 'rechazado') {
                                    $esMiTurnoParaFirmar = true;
                                } elseif ($rolNombre === 'GTH' && $tieneFirmaJefe && !$tieneFirmaGTH && $estadoDB !== 'rechazado') {
                                    $esMiTurnoParaFirmar = true;
                                }
                            @endphp

                            <tr id="fila-{{ $solicitud->id }}">
                                {{-- Columna: Empleado --}}
                                <td class="ps-4">
                                    <div class="fw-bold text-primary">{{ $solicitud->empleado ? strtoupper($solicitud->empleado->nombre . ' ' . $solicitud->empleado->apellido) : strtoupper($solicitud->nombre) }}</div>
                                    <div class="small text-muted"><b>{{ $solicitud->empleado ? strtoupper($solicitud->empleado->cargo) : 'N/A' }}</b></div>
                                </td>

                                {{-- Columna: Tipo --}}
                                <td class="text-center">
                                    <span class="badge bg-white text-dark border shadow-sm">{{ strtoupper(str_replace('_',' ',$solicitud->tipo)) }}</span>
                                </td>

                                {{-- Columna: Periodo --}}
                                <td class="text-center">
                                    <div class="small text-success"><b>INICIO:</b> {{ \Carbon\Carbon::parse($solicitud->fecha_inicio)->format('d/m/Y') }}</div>
                                    <div class="small text-danger"><b>FIN:</b> {{ \Carbon\Carbon::parse($solicitud->fecha_fin)->format('d/m/Y') }}</div>
                                </td>

                                {{-- Columna: Estado visual --}}
                                <td class="text-center">
                                    <div class="d-flex flex-column align-items-center" style="min-width: 150px;">
                                        <span class="badge bg-{{ $color }} mb-2 shadow-sm px-3 py-2" style="font-size: 0.75rem; min-width: 140px;">
                                            <i class="fas {{ $icono }} me-1"></i> {{ $texto }}
                                        </span>

                                        {{-- Línea de flujo visual corregida --}}
                                        <div class="d-flex align-items-center justify-content-center gap-2 mt-2">
                                            <div class="text-center">
                                                <div class="rounded-circle p-2 {{ $tieneFirmaJefe ? 'bg-success text-white' : 'bg-light text-muted' }}" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                                    <i class="fas fa-user-tie small"></i>
                                                </div>
                                                <small style="font-size: 0.7rem; display: block; mt-1;">JEFE</small>
                                            </div>

                                            <div style="width:40px; height:2px; background:#ccc; margin-bottom: 15px;"></div>

                                            <div class="text-center">
                                                <div class="rounded-circle p-2 {{ $tieneFirmaGTH ? 'bg-success text-white' : 'bg-light text-muted' }}" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                                    <i class="fas fa-users small"></i>
                                                </div>
                                                <small style="font-size: 0.7rem; display: block; mt-1;">GTH</small>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Columna: Acciones --}}
                                <td class="text-center">
                                    @if($esMiTurnoParaFirmar)
                                        <button type="button" class="btn btn-success btn-sm shadow-sm" onclick="verDetalles({{ $solicitud->id }})">
                                            <i class="fas fa-file-signature me-1"></i> <b>GESTIONAR</b>
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="verDetalles({{ $solicitud->id }})">
                                            <i class="fas fa-eye me-1"></i> Ver
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">No se encontraron registros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if(isset($solicitudes) && method_exists($solicitudes, 'links'))
    <div class="d-flex justify-content-center mt-4 custom-pagination">
        {{ $solicitudes->appends(request()->query())->links('pagination::bootstrap-5') }}
    </div>
@endif
            </div>
        </div>
    </div>
</div>

{{-- MODAL DE DETALLES --}} 
<div class="modal fade" id="verSolicitudModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border-radius:15px;">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">Formato FT-GTH-001</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBodyContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p>Cargando información...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function verDetalles(id) {
    const content = document.getElementById('modalBodyContent');
    const modalElement = document.getElementById('verSolicitudModal');
    const modalInstance = new bootstrap.Modal(modalElement);

    content.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p>Cargando información...</p>
        </div>
    `;
    modalInstance.show();

    fetch(`/solicitudes/${id}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => {
        if (!response.ok) throw new Error('Error al cargar datos');
        return response.text();
    })
    .then(html => {
        content.innerHTML = html;
        const signatureButtons = content.querySelectorAll('button[data-firma]');
        signatureButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                firmarModal(id, this.dataset.firma);
            });
        });
    })
    .catch(err => {
        content.innerHTML = `<div class="alert alert-danger">Error al cargar datos.</div>`;
    });
}

function firmarModal(id, tipo) {
    fetch(`/solicitudes/${id}/procesar`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ tipo: tipo })
    })
    .then(res => res.json())
    .then(data => { 
        if(data.success){
            Swal.fire('Éxito', data.message, 'success').then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(err => {
        Swal.fire('Error', 'Ocurrió un error al firmar.', 'error');
    });
}
</script>
@endsection