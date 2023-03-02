<table>
  <thead>
    <tr>
      <th style="background-color: #4472C4; text-align: center; color: #ffff; vertical-align: middle">ITEM</th>
      <th style="background-color: #4472C4; text-align: center; color: #ffff; vertical-align: middle">JEFE DE OPERACIONES</th>
      <th style="background-color: #4472C4; text-align: center; color: #ffff; vertical-align: middle">IDENTIFICADOR DE ASESOR</th>
      <th style="background-color: #4472C4; text-align: center; color: #ffff; vertical-align: middle">CODIGO DE PEDIDO</th>
      <th style="background-color: #4472C4; text-align: center; color: #ffff; vertical-align: middle">RAZON SOCIAL</th>
      <th style="background-color: #4472C4; text-align: center; color: #ffff; vertical-align: middle">RUC</th>
      <th style="background-color: #4472C4; text-align: center; color: #ffff; vertical-align: middle">MES</th>
      <th style="background-color: #4472C4; text-align: center; color: #ffff; vertical-align: middle">TIPO</th>
      <th style="background-color: #4472C4; text-align: center; color: #ffff; vertical-align: middle">CANTIDAD DEL PEDIDO</th>
      <th style="background-color: #4472C4; text-align: center; color: #ffff; vertical-align: middle">OPERARIO</th>
      <th style="background-color: #4472C4; text-align: center; color: #ffff; vertical-align: middle">CANT. DE DOCUMENTOS</th>
      <th style="background-color: #4472C4; text-align: center; color: #ffff; vertical-align: middle">ESTADO DE PEDIDO</th>
      <th style="background-color: #FFFF00; text-align: center; color: #0000; vertical-align: middle">FECHA DE REGISTRO DE PEDIDO</th>
      <th style="background-color: #FFFF00; text-align: center; color: #0000; vertical-align: middle">FECHA ELABORACIÓN PEDIDOS</th>
      <th style="background-color: #FFFF00; text-align: center; color: #0000; vertical-align: middle">FECHA DE FINALIZACIÓN DE PEDIDO</th>
      <th style="background-color: #4472C4; text-align: center; color: #ffff; vertical-align: middle">DETALLE</th>
    </tr>
  </thead>
  <tbody>
    <?php $cont = 0; ?>
    @foreach ($pedidos as $pedido)
      <tr>
        <td>{{ $cont + 1 }}</td>{{-- ITEM --}}
        <td>USER0{{ $pedido->jefe }}</td>{{-- JEFE DE OPERACIONES --}}
        <td>{{ $pedido->id_asesor }}</td>{{-- IDENTIFICADOR ASESOR --}}
        <td>{{ $pedido->codigo_pedido }}</td>{{-- CODIGO PEDIDO --}}
        <td>{{ $pedido->empresa }}</td>{{-- RAZON SOCIAL --}}
        <td>{{ $pedido->ruc }}</td>{{-- RUC --}}
        <td>{{ $pedido->mes }}</td>{{-- MES --}}
        <td>{{ $pedido->tipo }}</td>{{-- TIPO --}}
        <td>{{ $pedido->cantidad }}</td>{{-- CANTIDAD --}}
        <td>USER0{{ $pedido->operario }}</td>{{-- OPERARIO --}}
        <td>{{ $pedido->cant_doc }}</td>{{-- CANT. DOCUMENTOS --}}
        <td>{{ $pedido->estado_pedido }}</td>{{-- ESTADO PEDIDO --}}
        <td>{{ $pedido->fecha_registro }}</td>{{-- FECHA REGISTRO PEDIDO --}}
        <td>{{ $pedido->fecha_elaboracion }}</td>{{-- FECHA ELABORACION PEDIDO --}}
        <td>{{ $pedido->fecha_finalizacion }}</td>{{-- FECHA FINALIZACIÓN PEDIDO --}}
        <td></td>{{-- DETALLE --}}
      </tr>
      <?php $cont++; ?>
    @endforeach
  </tbody>
</table>
