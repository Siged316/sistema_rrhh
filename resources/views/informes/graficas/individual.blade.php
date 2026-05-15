@extends('layouts.app')
@section('content')
    <div class="container-fluid">
        <h1>Gráfica: Evolución Anual</h1>
        <p>Próximamente: Comparativa de año actual vs anterior.</p>
        <a href="{{ route('informes.graficas.index') }}" class="btn btn-secondary">Volver</a>
    </div>
@endsection