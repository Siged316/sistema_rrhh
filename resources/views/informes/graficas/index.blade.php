@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="font-weight-bold text-dark"><i class="fas fa-chart-line text-indigo mr-2"></i> Gráficas Estadísticas</h2>
            <hr>
        </div>
    </div>

    <div class="row">
        {{-- 1. Desempeño Depto --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="{{ route('graficas.depto') }}" class="text-decoration-none">
                <div class="card border-left-primary shadow h-100 py-2 card-hover">
                    <div class="card-body text-center">
                        <i class="fas fa-building fa-3x text-primary mb-3"></i>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Desempeño Depto.</div>
                        <small class="text-muted">Mensual vs Anual</small>
                    </div>
                </div>
            </a>
        </div>

        {{-- 2. Individual --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="{{ route('graficas.individual') }}" class="text-decoration-none">
                <div class="card border-left-info shadow h-100 py-2 card-hover">
                    <div class="card-body text-center">
                        <i class="fa-solid fa-chart-line fa-3x text-info mb-3"></i>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Rendimiento Individual</div>
                        <small class="text-muted">Análisis por Colaborador</small>
                    </div>
                </div>
            </a>
        </div>

        {{-- 3. Permisos y Vacaciones --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="{{ route('graficas.asistencias') }}" class="text-decoration-none">
                <div class="card border-left-success shadow h-100 py-2 card-hover">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-check fa-3x text-success mb-3"></i>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Ausencias y Permisos</div>
                        <small class="text-muted">Distribución de Faltas</small>
                    </div>
                </div>
            </a>
        </div>

        {{-- 4. Tiempo Compensatorio --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="{{ route('graficas.compensatorio') }}" class="text-decoration-none">
                <div class="card border-left-warning shadow h-100 py-2 card-hover">
                    <div class="card-body text-center">
                        <i class="fas fa-balance-scale fa-3x text-warning mb-3"></i>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Compensatorio</div>
                        <small class="text-muted">Ganado vs Usado</small>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<style>
    .card-hover { transition: all 0.3s; }
    .card-hover:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
    .text-indigo { color: #6610f2; }
</style>
@endsection