{{-- Extiende la plantilla principal del sistema --}}
@extends('layouts.app')

{{-- Sección principal del contenido --}}
@section('content')

<!-- Contenedor principal -->
<div class="container-fluid">

    <!-- Card principal -->
    <div class="card shadow mb-4">

        {{-- ========================================================= --}}
        {{-- ENCABEZADO DE LA EVALUACIÓN --}}
        {{-- ========================================================= --}}

        <div class="card-header py-3"
             style="background-color: #630d31; color: white;">

            <!-- Nombre del empleado evaluado -->
            <h5 class="m-0 font-weight-bold">
                Evaluación de Desempeño:
                {{ $empleado->nombre }}
            </h5>

            <!-- Información adicional -->
            <small>
                Departamento:
                {{ $empleado->departamento }}

                |

                Formulario:
                {{ $formulario->nombre }}
            </small>
        </div>


        {{-- ========================================================= --}}
        {{-- CUERPO PRINCIPAL --}}
        {{-- ========================================================= --}}

        <div class="card-body">

            <!-- Formulario de envío -->
            <form action="{{ route('evaluacion.guardar') }}"
                  method="POST">

                <!-- Token CSRF -->
                @csrf

                <!-- ID del formulario -->
                <input type="hidden"
                       name="formulario_id"
                       value="{{ $formulario->id }}">

                <!-- ID del empleado -->
                <input type="hidden"
                       name="empleado_id"
                       value="{{ $empleado->id }}">


                {{-- ========================================================= --}}
                {{-- TABLA DE EVALUACIÓN --}}
                {{-- ========================================================= --}}

                <div class="table-responsive">

                    <table class="table table-bordered align-middle text-center">

                        {{-- ENCABEZADO TABLA --}}
                        <thead>

                            <tr style="background-color: #f8e9f0;
                                       color: #630d31;">

                                <!-- Columna preguntas -->
                                <th class="text-left"
                                    style="width: 40%;">

                                    Criterios / Competencias
                                </th>

                                <!-- Escala de evaluación -->
                                <th style="width: 10%;">
                                    {{ $formulario->label_5 ?? '5' }}
                                </th>

                                <th style="width: 10%;">
                                    {{ $formulario->label_4 ?? '4' }}
                                </th>

                                <th style="width: 10%;">
                                    {{ $formulario->label_3 ?? '3' }}
                                </th>

                                <th style="width: 10%;">
                                    {{ $formulario->label_2 ?? '2' }}
                                </th>

                                <th style="width: 10%;">
                                    {{ $formulario->label_1 ?? '1' }}
                                </th>
                            </tr>
                        </thead>


                        {{-- CUERPO TABLA --}}
                        <tbody>

                            <!-- Recorremos preguntas -->
                            @foreach($formulario->preguntas as $p)

                            <tr>

                                <!-- Pregunta -->
                                <td class="text-left">

                                    <!-- Texto principal -->
                                    <strong>
                                        {{ $p->pregunta }}
                                    </strong>

                                    <!-- Categoría -->
                                    @if($p->categoria)

                                        <br>

                                        <small class="badge badge-light text-muted">
                                            {{ $p->categoria }}
                                        </small>

                                    @endif
                                </td>


                                {{-- ========================================================= --}}
                                {{-- OPCIONES DE RESPUESTA --}}
                                {{-- ========================================================= --}}

                                <!-- Valor 5 -->
                                <td>

                                    <input type="radio"
                                           name="respuestas[{{ $p->id }}]"
                                           value="5"
                                           required>
                                </td>

                                <!-- Valor 4 -->
                                <td>

                                    <input type="radio"
                                           name="respuestas[{{ $p->id }}]"
                                           value="4"
                                           required>
                                </td>

                                <!-- Valor 3 -->
                                <td>

                                    <input type="radio"
                                           name="respuestas[{{ $p->id }}]"
                                           value="3"
                                           required>
                                </td>

                                <!-- Valor 2 -->
                                <td>

                                    <input type="radio"
                                           name="respuestas[{{ $p->id }}]"
                                           value="2"
                                           required>
                                </td>

                                <!-- Valor 1 -->
                                <td>

                                    <input type="radio"
                                           name="respuestas[{{ $p->id }}]"
                                           value="1"
                                           required>
                                </td>
                            </tr>

                            @endforeach
                        </tbody>
                    </table>
                </div>


                {{-- ========================================================= --}}
                {{-- COMENTARIOS ADICIONALES --}}
                {{-- ========================================================= --}}

                <div class="mt-3">

                    <label>
                        <strong>
                            Comentarios Adicionales:
                        </strong>
                    </label>

                    <!-- Campo observaciones -->
                    <textarea name="observaciones"
                              class="form-control"
                              rows="3"
                              placeholder="Opcional..."></textarea>
                </div>


                {{-- ========================================================= --}}
                {{-- BOTÓN FINAL --}}
                {{-- ========================================================= --}}

                <div class="text-right mt-4">

                    <button type="submit"
                            class="btn btn-success btn-lg">

                        <i class="fas fa-save"></i>

                        Finalizar y Enviar Evaluación
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

{{-- Fin sección --}}
@endsection