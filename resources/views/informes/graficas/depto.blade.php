@extends('layouts.app')
@section('content')
    <div class="container-fluid">
        <h1>Gráfica: Depto vs Depto</h1>
        <p>Próximamente: Comparativa de rendimiento entre áreas.</p>
        <a href="{{ route('informes.graficas.index') }}" class="btn btn-secondary">Volver</a>
    </div>
@endsection