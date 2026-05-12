@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="font-weight-bold text-dark">
                <i class="fas fa-chart-line text-primary mr-2"></i> Centro de Informes y Estadísticas
            </h2>
            <p class="text-muted">Seleccione el tipo de reporte que desea generar o visualizar.</p>
            <hr>
        </div>
    </div>

    <div class="row">
        {{-- 1. DESEMPEÑO POR DEPTO --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="{{ route('informes.departamento') }}" class="text-decoration-none">
                <div class="card border-left-primary shadow h-100 py-2 card-hover">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-building fa-3x text-primary"></i>
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Desempeño por Depto.</div>
                        <small class="text-muted text-uppercase">Mensual y Anual</small>
                    </div>
                </div>
            </a>
        </div>

        {{-- 2. INFORME INDIVIDUAL (EVALUACIÓN + METAS) --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="{{ route('informes.individual') }}" class="text-decoration-none">
                <div class="card border-left-info shadow h-100 py-2 card-hover">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-user-check fa-3x text-info"></i>
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Informe Individual</div>
                        <small class="text-muted text-uppercase">Evaluación + Metas</small>
                    </div>
                </div>
            </a>
        </div>

        {{-- 3. REGISTRO DE PERMISOS Y VACACIONES --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="{{ route('informes.permisos') }}" class="text-decoration-none">
                <div class="card border-left-success shadow h-100 py-2 card-hover">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-calendar-alt fa-3x text-success"></i>
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Permisos y Vacaciones</div>
                        <small class="text-muted text-uppercase">Control de Ausencias</small>
                    </div>
                </div>
            </a>
        </div>

        {{-- 4. CONTROL DE TIEMPO COMPENSATORIO --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="{{ route('informes.compensatorio') }}" class="text-decoration-none">
                <div class="card border-left-warning shadow h-100 py-2 card-hover">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-clock fa-3x text-warning"></i>
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Tiempo Compensatorio</div>
                        <small class="text-muted text-uppercase">Ganado vs Usado</small>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<style>
    /* Efecto para que las cajas se muevan un poco al pasar el mouse */
    .card-hover {
        transition: transform 0.3s ease, shadow 0.3s ease;
    }
    .card-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important;
        background-color: #f8f9fc;
    }
    .border-left-primary { border-left: .25rem solid #4e73df !important; }
    .border-left-success { border-left: .25rem solid #1cc88a !important; }
    .border-left-info { border-left: .25rem solid #36b9cc !important; }
    .border-left-warning { border-left: .25rem solid #f6c23e !important; }
</style>
@endsection