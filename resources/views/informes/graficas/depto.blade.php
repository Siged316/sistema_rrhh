@extends('layouts.app')

@section('content')
<style>
    .grafico-container { position: relative; height: 500px; width: 100%; background-color: #141820 !important; border-radius: 15px; padding: 30px; border: 1px solid #d1d3e2; }
    .custom-dropdown { position: relative; width: 100%; }
    .dropdown-trigger { width: 100%; text-align: left; background: white; border: 1px solid #ced4da; padding: 0.375rem 0.75rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
    .dropdown-content { display: none; position: absolute; background: white; border: 1px solid #ced4da; width: 100%; z-index: 1000; max-height: 200px; overflow-y: auto; padding: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .dropdown-content.show { display: block; }
</style>

<div class="container-fluid">

    <div class="card shadow mb-4">
          {{-- =========================================================
             ENCABEZADO DE LA TARJETA
        ========================================================== --}}
        <div class="card-header py-3 bg-white text-center">
          {{-- Título principal --}}
          <h2 class="m-0 font-weight-bold text-primary">
             Análisis Comparativo Por Departamento
          </h2>
        </div>
        <div class="card-body">
            <div class="row align-items-end mb-4">
                {{-- Departamentos --}}
                <div class="col-md-4">
                    <label class="font-weight-bold">Departamentos:</label>
                    <div style="max-height: 150px; overflow-y: auto; border: 1px solid #d1d3e2; padding: 10px; border-radius: 5px;">
                        @foreach($departamentos as $depto)
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input depto-check" id="d{{$depto->id}}" value="{{$depto->id}}">
                                <label class="custom-control-label" for="d{{$depto->id}}">{{$depto->nombre}}</label>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Años (con menú desplegable igual al individual) --}}
                <div class="col-md-3">
                    <label class="font-weight-bold">Años:</label>
                    <div class="custom-dropdown">
                        <div class="dropdown-trigger form-control" id="btnAnios" onclick="toggleMenu()">Seleccionar años... <i class="fas fa-caret-down"></i></div>
                        <div class="dropdown-content" id="menuAnios">
                            @foreach($anios as $a)
                                <div class="custom-control custom-checkbox px-3 py-1">
                                    <input type="checkbox" class="custom-control-input anio-check" id="anio_{{ $a }}" value="{{ $a }}">
                                    <label class="custom-control-label" for="anio_{{ $a }}">{{ $a }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Mes --}}
                <div class="col-md-2">
                    <label class="font-weight-bold">Mes:</label>
                    <select id="mes_valor" class="form-control">
                        <option value="all">Todo el año</option>
                        @for($i=1; $i<=12; $i++) <option value="{{$i}}">{{date('F', mktime(0,0,0,$i,1))}}</option> @endfor
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="button" class="btn btn-primary btn-block" onclick="generarGrafica()">Generar Gráfica</button>
                </div>
            </div>
            <div class="grafico-container"><canvas id="canvasDepto"></canvas></div>
        </div>
    </div>
</div>

<script>
let miGrafica = null;

// Lógica de menú desplegable
function toggleMenu() { document.getElementById('menuAnios').classList.toggle('show'); }

document.addEventListener('click', function(e) {
    const menu = document.getElementById('menuAnios');
    const btn = document.getElementById('btnAnios');
    if (!btn.contains(e.target) && !menu.contains(e.target)) menu.classList.remove('show');
});

// Actualizar texto del botón de años
document.querySelectorAll('.anio-check').forEach(chk => {
    chk.addEventListener('change', () => {
        const checked = document.querySelectorAll('.anio-check:checked');
        const btn = document.getElementById('btnAnios');
        btn.innerHTML = checked.length === 0 ? 'Seleccionar años... <i class="fas fa-caret-down"></i>' : (checked.length === 1 ? checked[0].value : checked.length + ' años') + ' <i class="fas fa-caret-down"></i>';
    });
});

function generarGrafica() {
    const deptos = Array.from(document.querySelectorAll('.depto-check:checked')).map(cb => cb.value);
    const anios = Array.from(document.querySelectorAll('.anio-check:checked')).map(c => c.value);
    const mes = document.getElementById('mes_valor').value;

    // 1. Validaciones básicas de selección mínima
    if (deptos.length === 0) {
        Swal.fire('Atención', 'Seleccione al menos un departamento.', 'warning');
        return;
    }
    if (anios.length === 0) {
        Swal.fire('Atención', 'Seleccione al menos un año.', 'warning');
        return;
    }

    // 2. Validación de lógica de comparación
    // Caso A: 1 departamento -> Debe tener más de 1 año para comparar
    if (deptos.length === 1 && anios.length < 2) {
        Swal.fire('Atención', 'Al seleccionar un solo departamento, debe elegir al menos 2 años para poder comparar.', 'warning');
        return;
    }

    // Caso B: Más de 1 departamento -> Debe tener exactamente 1 año para comparar
    if (deptos.length > 1 && anios.length > 1) {
        Swal.fire('Atención', 'Al comparar varios departamentos, por favor seleccione un solo año para mantener la comparativa clara.', 'warning');
        return;
    }

    Swal.fire({ title: 'Generando...', didOpen: () => Swal.showLoading() });

    // Construcción de la URL (sin cambios, ya es correcta)
    let params = new URLSearchParams();
    deptos.forEach(id => params.append('departamento_ids[]', id));
    anios.forEach(a => params.append('anios[]', a));
    params.append('mes', mes);

    fetch("{{ route('graficas.data.depto') }}?" + params.toString())
        .then(r => r.json())
        .then(data => {
            Swal.close();
            if (miGrafica) miGrafica.destroy();
            const ctx = document.getElementById('canvasDepto').getContext('2d');
            miGrafica = new Chart(ctx, {
                type: 'bar',
                data: { labels: data.labels, datasets: data.datasets },
                options: { responsive: true, maintainAspectRatio: false }
            });
        })
        .catch(err => {
            Swal.close();
            Swal.fire('Error', 'No se pudo cargar la gráfica', 'error');
        });
}


</script>
@endsection