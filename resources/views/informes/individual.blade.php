@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <a href="{{ route('informes.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Centro de Informes
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white py-3" style="background-color: #003366 !important;">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-user me-2"></i> Informe de Desempeño Individual</h5>
                </div>
                <div class="card-body p-5">
                    <form id="formReporte">
                        @csrf
                        <div class="row g-4">
                            {{-- 1. SELECCIONAR EMPLEADO --}}
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">1. Colaborador</label>
                                <select class="form-select form-select-lg border-2 shadow-sm" name="empleado_id" id="empleado_id" required>
                                    <option value="" selected disabled>Seleccione...</option>
                                    @foreach($empleados as $e)
                                        <option value="{{ $e->id }}">{{ $e->nombre }} {{ $e->apellido }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- 2. PERÍODO --}}
                            <div class="col-md-3">
                                <label class="form-label fw-bold text-dark">2. Período</label>
                                <select class="form-select form-select-lg border-2 shadow-sm" name="periodo" id="periodo" onchange="actualizarInterfaz()">
                                    <option value="" selected disabled>Elija...</option>
                                    <option value="mensual">Mensual</option>
                                    <option value="anual">Anual</option>
                                </select>
                            </div>

                            {{-- 3. AÑO --}}
                            <div class="col-md-2 d-none" id="div_anio">
                                <label class="form-label fw-bold text-dark">3. Año</label>
                                <select class="form-select form-select-lg border-2 shadow-sm" name="anio" id="anio_valor">
                                    @foreach($anios as $a)
                                        <option value="{{ $a }}">{{ $a }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- 4. MES --}}
                            <div class="col-md-3 d-none" id="div_mes">
                                <label class="form-label fw-bold text-dark">4. Mes</label>
                                <select class="form-select form-select-lg border-2 shadow-sm" name="mes" id="mes_valor">
                                    <option value="01">Enero</option><option value="02">Febrero</option>
                                    <option value="03">Marzo</option><option value="04">Abril</option>
                                    <option value="05">Mayo</option><option value="06">Junio</option>
                                    <option value="07">Julio</option><option value="08">Agosto</option>
                                    <option value="09">Septiembre</option><option value="10">Octubre</option>
                                    <option value="11">Noviembre</option><option value="12">Diciembre</option>
                                </select>
                            </div>
                        </div>

                        <div class="row text-center mt-5">
                            <div class="col-md-6 mb-3">
                                <button type="button" onclick="descargar('pdf')" class="btn btn-outline-danger w-100 p-4 border-2">
                                    <i class="fas fa-file-pdf fa-3x mb-2"></i><br>
                                    <b>Descargar PDF</b>
                                </button>
                            </div>
                            <div class="col-md-6 mb-3">
                                <button type="button" onclick="descargar('excel')" class="btn btn-outline-success w-100 p-4 border-2">
                                    <i class="fas fa-file-excel fa-3x mb-2"></i><br>
                                    <b>Descargar Excel</b>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function actualizarInterfaz() {
        const periodo = document.getElementById('periodo').value;
        document.getElementById('div_anio').classList.remove('d-none');
        if (periodo === 'mensual') {
            document.getElementById('div_mes').classList.remove('d-none');
        } else {
            document.getElementById('div_mes').classList.add('d-none');
        }
    }

   function descargar(tipo) {
    const empleado = document.getElementById('empleado_id').value;
    const periodo = document.getElementById('periodo').value;
    const anio = document.getElementById('anio_valor').value;
    const mes = document.getElementById('mes_valor').value;

    if (!empleado || !periodo || !anio) {
        Swal.fire('Atención', 'Complete los campos obligatorios.', 'warning');
        return;
    }

    Swal.fire({ title: 'Verificando...', didOpen: () => { Swal.showLoading(); } });

    // ENVIAMOS empleado_id EN LUGAR DE departamento_id
    fetch(`{{ route('informes.validar') }}?empleado_id=${empleado}&anio=${anio}&periodo=${periodo}&mes=${mes}`)
        .then(response => response.json())
        .then(data => {
            Swal.close();
            if (data.count > 0) {
                let ruta = (tipo === 'pdf') 
                    ? "{{ route('informes.individual.pdf') }}" 
                    : "{{ route('informes.individual.excel') }}";
                
                window.location.href = `${ruta}?empleado_id=${empleado}&anio=${anio}&periodo=${periodo}&mes=${mes}`;
            } else {
                Swal.fire('Sin registros', 'No se encontraron evaluaciones para este empleado.', 'info');
            }
        })
        .catch(error => {
            Swal.close();
            Swal.fire('Error', 'No se pudo validar la información.', 'error');
        });
}
</script>
@endsection