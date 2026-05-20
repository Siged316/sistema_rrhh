{{-- Extiende el layout principal de la aplicación --}}
@extends('layouts.app')

@section('content')

<div class="container-fluid mt-3 px-0">

    {{-- ===================== ENCABEZADO ===================== --}}
    <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white shadow-sm rounded border-bottom border-primary border-3">
        <h2 class="mb-0 text-primary fw-bold">
            <i class="fa-solid fa-users me-2"></i> Gestión de Empleados
        </h2>

        <div class="d-flex gap-2">
            <button class="btn btn-primary px-4 shadow-sm fw-bold"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#offcanvasNuevoEmpleado">
                <i class="fa-solid fa-plus me-1"></i> Nuevo Empleado
            </button>
        </div>
    </div>

    {{-- ===================== FILTROS / BUSCADOR ===================== --}}
    <div class="card mb-4 border-0 shadow-sm mx-2">
        <div class="card-body">
            <form action="{{ route('empleado.index') }}" method="GET" class="row g-3">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fa-solid fa-magnifying-glass text-muted"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0"
                               placeholder="Buscar por nombre o cargo" value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Buscar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== MENSAJE DE ÉXITO ===================== --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4 mx-2" role="alert" id="success-alert">
            <i class="fa-solid fa-circle-check me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ===================== TABLA DE EMPLEADOS ===================== --}}
    <div class="card shadow-sm border-0 mx-0 w-100 bg-transparent">
        <div class="card-body p-0">
            <div class="table-responsive px-2">
                <table class="table align-middle tabla-personalizada">
                    <thead>
                        <tr class="text-center">
                            <th style="width: 60px;">Código/DNI</th>
                            <th class="text-start">Empleado</th>
                            <th class="text-start">Contacto</th>
                            <th class="text-start">Cargo y Departamento</th>
                            <th>Contrato</th>
                            <th class="text-start">Fechas</th>
                            <th>Estado</th>
                            <th>Doc</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($empleados as $empleado)
                        <tr>
                            <td class="ps-3">
                              {{-- Código de Empleado Principal --}}
                              <div class="fw-bold text-primary" style="font-size: 0.85rem;">
                                 <i class="fa-solid fa-id-badge me-1"></i>{{ $empleado->codigo_empleado ?? 'S/C' }}
                               </div>
                              {{-- DNI en secundario para no amontonar --}}
                              <div class="text-muted" style="font-size: 0.75rem;">
                                  <i class="fa-solid fa-fingerprint me-1"></i>{{ $empleado->dni ?? '0000-0000-00000' }}
                               </div>
                           </td>

                            {{-- Celda: Empleado (CAMBIADO A NEGRO NORMAL) --}}
                            <td>
                                <div class="text-dark" style="font-size: 1rem; font-weight: 400;">
                                    {{ strtoupper($empleado->nombre) }} {{ strtoupper($empleado->apellido) }}
                                </div>
                            </td>

                            {{-- Celda: Contacto --}}
                            <td>
                                <div class="small text-muted"><i class="fa-solid fa-envelope me-1 text-primary"></i> {{ $empleado->email }}</div>
                                @if($empleado->contacto)
                                    <div class="small mt-1 text-dark"><i class="fa-solid fa-phone me-1 text-success"></i> {{ $empleado->contacto }}</div>
                                @endif
                            </td>

                            {{-- Celda: Cargo y Departamento --}}
                            <td>
                                <div class="fw-bold text-dark mb-1" style="font-size: 0.9rem;">
                                    {{ strtoupper($empleado->cargo) }}
                                </div>

                                <div class="mb-2">
                                    @if($empleado->departamento)
                                        <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle px-2">
                                            <i class="fa-solid fa-building me-1" style="font-size: 0.75rem;"></i>
                                            {{ strtoupper($empleado->departamento->nombre) }}
                                        </span>
                                    @else
                                        <span class="badge rounded-pill bg-light text-muted border px-2">SIN DEP.</span>
                                    @endif
                                </div>

                                <div class="text-muted" style="font-size: 0.8rem;">
                                    <i class="fa-solid fa-user-tie me-1"></i>
                                    <span class="fw-semibold text-dark">
                                        {{ $empleado->departamento?->jefeEmpleado 
                                            ? strtoupper($empleado->departamento->jefeEmpleado->nombre . ' ' . $empleado->departamento->jefeEmpleado->apellido) 
                                            : 'N/A' }}
                                    </span>
                                </div>

                                @if($empleado->departamentosComoJefe->count() > 0)
                                    <div class="mt-1">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.7rem;">
                                            <i class="fa-solid fa-star me-1"></i> LÍDER DE ÁREA
                                        </span>
                                    </div>
                                @endif
                            </td>

                            {{-- Celda: Contrato --}}
                            <td class="text-center">
                                <span class="badge bg-white text-dark border shadow-sm px-2 py-1 text-uppercase">
                                    {{ $empleado->tipo_contrato ?? 'N/A' }}
                                </span>
                            </td>

                            {{-- Celda: Fechas --}}
                            <td>
                                <div style="font-size: 0.8rem;"><b class="text-success">ING:</b> {{ $empleado->fecha_ingreso ? \Carbon\Carbon::parse($empleado->fecha_ingreso)->format('d/m/Y') : '---' }}</div>
                                @if($empleado->fecha_baja)
                                    <div style="font-size: 0.8rem;" class="mt-1"><b class="text-danger">BAJ:</b> {{ \Carbon\Carbon::parse($empleado->fecha_baja)->format('d/m/Y') }}</div>
                                @endif
                            </td>

                            {{-- Celda: Estado --}}
                            <td class="text-center">
                                <span class="fw-bold {{ $empleado->estado == 'activo' ? 'text-success' : 'text-danger' }} small text-uppercase">
                                    {{ $empleado->estado }}
                                </span>
                            </td>

                            {{-- Celda: Documento --}}

                            <td class="text-center">
                              @if($empleado->documentos && $empleado->documentos->count() > 0)
                                  @php
                                      $primerDoc = $empleado->documentos->first();
                                      $extension = strtolower(pathinfo($primerDoc->ruta_archivo, PATHINFO_EXTENSION));
            
                                      // 1. Limpiamos "public/" o "storage/" por si acaso
                                      $pathLimpio = str_replace(['public/', 'storage/'], '', $primerDoc->ruta_archivo);
            
                                      // 2. CORRECCIÓN PARA WINDOWS: Convertimos cualquier barra invertida \ en barra normal /
                                      $pathLimpio = str_replace('\\', '/', $pathLimpio);
            
                                     // 3. Construimos la URL final perfecta
                                     $rutaWeb = asset('storage/' . $pathLimpio);
                                   @endphp
                         
                                  <a href="{{ $rutaWeb }}" target="_blank" title="Ver/Descargar {{ $primerDoc->nombre_archivo }}">
                                      @if($extension == 'pdf')
                                         <i class="fa-solid fa-file-pdf text-danger fa-xl"></i>
                                        @elseif(in_array($extension, ['doc', 'docx']))
                                          <i class="fa-solid fa-file-word text-primary fa-xl"></i>
                                        @elseif(in_array($extension, ['xls', 'xlsx']))
                                         <i class="fa-solid fa-file-excel text-success fa-xl"></i>
                                       @elseif(in_array($extension, ['jpg', 'jpeg', 'png']))
                                          <i class="fa-solid fa-file-image text-info fa-xl"></i>
                                      @else
                                           <i class="fa-solid fa-file text-secondary fa-xl"></i>
                                      @endif
                                    </a>
                                    @else
                                      <i class="fa-solid fa-minus text-muted opacity-50"></i>
                                    @endif
                                        </td>

                            {{-- Celda: Acciones --}}
                            <td class="text-center">
                                <div class="btn-group shadow-sm bg-white rounded">
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditarEmpleado{{ $empleado->id }}">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <button type="button" class="btn btn-outline-secondary btn-sm" 
                                            onclick="confirmarEstado('{{ $empleado->id }}', '{{ $empleado->nombre }}', '{{ $empleado->estado }}')"
                                            title="Cambiar Estado">
                                        <i class="fa-solid fa-power-off"></i>
                                    </button>

                                    <form id="estado-form-{{ $empleado->id }}" action="{{ route('empleado.estado', $empleado->id) }}" method="POST" style="display: none;">
                                        @csrf
                                        @method('PATCH')
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @include('empleado.edit')
                        @empty
                        <tr><td colspan="9" class="text-center py-5 text-muted">No se encontraron empleados en la base de datos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Offcanvas de creación --}}

@include('empleado.create')

{{-- ===================== SCRIPTS ===================== --}}

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

document.addEventListener('DOMContentLoaded', function () {

    // Alertas

    const successAlert = document.getElementById('success-alert');

    if (successAlert) {

        setTimeout(() => {

            bootstrap.Alert.getOrCreateInstance(successAlert).close();

        }, 4000);

    } 

});

</script>

<script>
    // Confirmación cambio de estado
    function confirmarEstado(id, nombre, estado) {
        // Lógica de textos según el estado actual
        const accion = estado === 'activo' ? 'DESACTIVAR' : 'ACTIVAR';
        const color = estado === 'activo' ? '#dc3545' : '#198754';

        Swal.fire({
            title: `¿${accion} al empleado?`,
            text: `El estado de ${nombre} cambiará a ${estado === 'activo' ? 'inactivo' : 'activo'}.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: color,
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            customClass: { popup: 'rounded-4' }
        }).then((result) => {
            if (result.isConfirmed) {
                // Envía el formulario específico del empleado
                document.getElementById('estado-form-' + id).submit();
            }
        });
    }
</script>

@endsection