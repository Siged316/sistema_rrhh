
<table
    id="tablaHistorialProyecto"
    class="table table-bordered table-striped">

    <thead>
        <tr>
            <th>Fecha</th>
            <th>Formulario</th>
            <th>Colaborador</th>
            <th>Estado</th>
            <th>Puntuación</th>
            <th>Acciones</th>
        </tr>
    </thead>

    <tbody>

    @foreach($historial as $item)

        <tr>
            <td>
                {{ \Carbon\Carbon::parse($item->created_at)
                    ->format('d/m/Y') }}
            </td>

            <td>{{ $item->formulario }}</td>

            <td>{{ $item->colaborador }}</td>

            <td>{{ $item->estado }}</td>

            <td>{{ $item->puntuacion_total }}</td>

            <td class="text-center">
              <button class="btn btn-sm btn-primary"
                 onclick="verDetalleHistorial({{ $item->id }})">
                  👁
               </button>
          </td>

        </tr>

    @endforeach

    </tbody>

</table>