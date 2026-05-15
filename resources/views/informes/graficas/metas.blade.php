@extends('layouts.app')
@section('content')
    <div class="container-fluid">
        <h1>Gráfica: Individual vs Media</h1>
        <p>Próximamente: Desempeño del colaborador frente al promedio.</p>
        <a href="{{ route('informes.graficas.index') }}" class="btn btn-secondary">Volver</a>
    </div>
@endsection