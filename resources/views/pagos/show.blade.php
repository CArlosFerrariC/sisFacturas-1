@extends('adminlte::page')

@section('title', 'Detalle de pagos')

@section('content_header')
  <h1>DETALLE DEL <b>PAGO</b>: PAG000{{ $pagos->id }}</h1>
@stop

@section('content')
@include('pagos.modals.revisarhistorial')

  <div class="card">
    <div class="card-body">
      <div class="border rounded card-body border-secondary">
        <div class="form-row">
          <table class="table table-active">
            <thead>
              <tr>
                <td>
                  <th scope="col" class="col-lg-2" style="text-align: right;">ASESOR:</th>
                  <th scope="col">{{ $pagos->users }}</th>
                </td>
                <td>
                  <th scope="col" class="col-lg-2" style="text-align: right;">CLIENTE:</th>
                  <th scope="col">{{ $pagos->celular }} - {{ $pagos->nombre }}</th>
                </td>
                <td>
                  <th scope="col" colspan="" class="col-lg-2" style="text-align: right;">ESTADO:</th>
                <th scope="col">{{ $pagos->condicion }}</th>
                </td>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
    <div class="card-body">
      <div class="border rounded card-body border-secondary">
        <div class="form-row">
          <div class="form-group col-lg-12">
            <h3>PEDIDOS</h3>
            <table class="table table-striped">
              <thead>
                <tr>
                  <th scope="col">ITEM</th>
                  <th scope="col">PEDIDO</th>
                  <th scope="col">CODIGO</th>
                  <th scope="col">ESTADO DE PAGO</th>
                  <th scope="col">ESTADO</th>
                  <th scope="col">MONTO TOTAL</th>
                  <th scope="col">ABONADO</th>
                  <th scope="col">HISTORIAL</th>
                </tr>
              </thead>
              <tbody>
                @php
                  $contPe = 0;
                  $sumPe = 0;
                @endphp
                @foreach ($pagoPedidos as $pagoPedido)
                  <tr>
                    <td>{{ $contPe + 1 }}</td>
                    <td>PED000{{ $pagoPedido->pedidos }}</td>
                    <td>{{ $pagoPedido->codigo }}</td>

                    @if($pagos->condicion=='ABONADO')
                        @if($pagoPedido->pagado == 1)
                        <td>ADELANTO ABONADO</td>
                        @else
                        <td>PAGADO ABONADO</td>
                        @endif
                    @elseif($pagos->condicion=='OBSERVADO')
                        @if($pagoPedido->pagado == 1)
                        <td>ADELANTO OBSERVADO</td>
                        @else
                        <td>PAGADO OBSERVADO</td>
                        @endif
                    @elseif($pagos->condicion=='PAGO')
                        @if($pagoPedido->pagado == 1)
                        <td>ADELANTO PAGO</td>
                        @else
                        <td>PAGADO PAGO</td>
                        @endif
                    @endif
                      
                    <td>{{ $pagoPedido->condicion }}</td>
                    <td>{{ $pagoPedido->total }}</td>
                    <td style='font-weight: bolder;'>{{ $pagoPedido->abono }}</td>
                    <td><a href="" data-target="#modal-historial-pagos-pedido" data-toggle="modal" data-pedido="{{ $pagoPedido->codigo }}" data-pago="{{$pago->id}}"><button class="btn btn-danger btn-sm">Historial</button></a>   
                    </td>
                  </tr>
                  @php
                    $sumPe = $sumPe + $pagoPedido->abono;
                    $contPe++;
                  @endphp
                @endforeach
              </tbody>
              <tfoot>
                <tr>
                  <td>TOTAL ABONADO</td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td><?php echo number_format($sumPe, 2, '.', ' ')?></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="card-body">
      <div class="border rounded card-body border-secondary">
        <div class="form-row">
          <div class="form-group col-lg-12">
            <h3>PAGOS @if($pagos->saldo>0) - SALDO A FAVOR DEL CLIENTE: {{ $pagos->saldo }}@endif</h3>
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th scope="col">ITEM</th>
                    <th scope="col">PAGO</th>
                    <th scope="col">BANCO</th>                
                    <th scope="col">MONTO</th>
                    <th scope="col">FECHA</th>
                    <th scope="col">CUENTA DESTINO</th>
                    <th scope="col">TITULAR</th>
                    <th scope="col">FECHA DEPOSITO</th>
                    <th scope="col">OBSERVACION</th>
                    <th scope="col">IMAGEN</th>
                  </tr>
                </thead>
                <tbody>
                  @php
                    $contPa = 0;
                    $sumPa = 0;
                  @endphp
                  @foreach ($detallePagos as $detallePago)
                    <tr>
                      <td>{{ $contPa + 1 }}</td>
                      <td>PAG000{{ $detallePago->id }}</td>
                      <td>{{ $detallePago->banco }}</td>                  
                      <td>@php echo number_format($detallePago->monto,2) @endphp</td>
                      <td>{{ $detallePago->fecha }}</td>
                      <td>{{ $detallePago->cuenta }}</td>                  
                      <td>{{ $detallePago->titular }}</td>
                      <td>{{ $detallePago->fecha_deposito }}</td>
                      <td>{{ $detallePago->observacion }}</td>
                      <td><a href="" data-target="#modal-imagen-{{ $detallePago->id }}" data-toggle="modal">
                          <img src="{{ asset('storage/pagos/' . $detallePago->imagen) }}" alt="{{ $detallePago->imagen }}" height="200px" width="200px" class="img-thumbnail"></a>
                        <p><br><a href="{{ route('pagos.descargarimagen', $detallePago->imagen) }}"><button type="button" class="btn btn-secondary"> Descargar</button></a></p>
                       </td>
                    </tr>
                    @php
                      $sumPa = $sumPa + $detallePago->monto;
                      $contPa++;
                    @endphp
                    @include('pagos.modals.modalimagen')
                  @endforeach
                </tbody>
                <tfoot>
                  <th style="text-align: center">TOTAL</th>
                  <th></th>
                  <th></th>
                  <th><h4><?php echo number_format($sumPa, 2, '.', ' ')?></h4></th>
                  <th></th>  
                </tfoot>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="card-footer">
      @if (Auth::user()->rol == "Asesor")
        <a href="{{ url()->previous() }}" class="btn btn-danger"><i class="fas fas fa-arrow-left"></i>ATRAS</a>
      @else
        <a href="{{ url()->previous() }}" class="btn btn-danger"><i class="fas fas fa-arrow-left"></i>ATRAS</a>
      @endif
    </div>
  </div>  
@stop

@section('css')
  <style>
    .modal.left .modal-dialog,
	.modal.right .modal-dialog {
		position: fixed;
		margin: auto;
		width: 320px;
		height: 100%;
		-webkit-transform: translate3d(0%, 0, 0);
		    -ms-transform: translate3d(0%, 0, 0);
		     -o-transform: translate3d(0%, 0, 0);
		        transform: translate3d(0%, 0, 0);
	}

	.modal.left .modal-content,
	.modal.right .modal-content {
		height: 100%;
		overflow-y: auto;
	}
	
	.modal.left .modal-body,
	.modal.right .modal-body {
		padding: 15px 15px 80px;
	}

/*Left*/
	
	
	.modal.left.fade.in .modal-dialog{
		left: 0;
	}
        
/*Right*/
	
	.modal.right.fade.in .modal-dialog {
		right: 0;
	}
  .modal.right .modal-dialog {
		right: 0;
	}

/* ----- MODAL STYLE ----- */
	.modal-content {
		border-radius: 0;
		border: none;
	}

	.modal-header {
		border-bottom-color: #EEEEEE;
		background-color: #FAFAFA;
	}

  .modal-dialog{
    right:0;
    padding-right: 0 !important;
    margin-right: 0 !important;
  }
  </style>
@stop

@section('js')

  <script src="{{ asset('js/datatables.js') }}"></script>
  <script>
    var tableconciliar=null;
    $(document).ready(function() {

      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      });

      tableconciliar=$('#tablaPrincipalConciliar').DataTable({
          "bPaginate": false,
          "bFilter": false,
          "bInfo": false,
          "length": 3,
          columns: 
          [
            {
              data: 'titular'
            },
            {
              data: 'banco'
            },
            {
              data: 'fecha'
            },
            {
              data: 'movimiento'
            },
            {
              data: 'monto'
            }
          ],
          language: {
            "decimal": "",
            "emptyTable": "No hay informaciÃ³n",
            "info": "Mostrando del _START_ al _END_ de _TOTAL_ Entradas",
            "infoEmpty": "Mostrando 0 to 0 of 0 Entradas",
            "infoFiltered": "(Filtrado de _MAX_ total entradas)",
            "infoPostFix": "",
            "thousands": ",",
            "lengthMenu": "Mostrar _MENU_ Entradas",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "Sin resultados encontrados",
            "paginate": {
            "first": "Primero",
            "last": "Ultimo",
            "next": "Siguiente",
            "previous": "Anterior"
            }
          }
        });

      $('#modal-historial-pagos-pedido').on('show.bs.modal', function (event) {
       
       console.log("aa")
       var button = $(event.relatedTarget) 
       var pedido = button.data('pedido')
       var pago = button.data('pago')

       tableconciliar.destroy();

       tableconciliar=$('#tablapagospedidoshistorial').DataTable({
         "bPaginate": true,
         "bFilter": true,
         "bInfo": true,
         "bAutoWidth": false,
          "pageLength":5,
         "order": [[ 0, "asc" ]],
         'ajax': {
           url:"{{ route('pagostablahistorial') }}",					
           'data': { "pedido":pedido,"pago":pago }, 
           "type": "get",
         },
         "search": {
            "search": pedido
          },
         columns: [
        {
            data: 'id', 
            name: 'id',
            render: function ( data, type, row, meta ) {   
              var cantidadvoucher=row.cantidad_voucher;
              var cantidadpedido=row.cantidad_pedido;
              var unido= ( (cantidadvoucher>1)? 'V':'I' )+''+( (cantidadpedido>1)? 'V':'I' );
              if(row.id<10){
                return 'PAG'+row.users+unido+'000'+row.id;
              }else if(row.id<100){
                return 'PAG00'+row.users+unido+''+row.id;
              }else if(row.id<1000){
                return 'PAG0'+row.users+unido+''+row.id;
              }else{
                return 'PAG'+row.users+unido+''+row.id;
              }

              /*if(row.id<10){
                return 'PAG000'+row.id;
              }else if(row.id<100){
                return 'PAG00'+row.id;
              }else if(row.id<1000){
                return 'PAG0'+row.id;
              }else{
                return 'PAG'+row.id;
              } */
            }
        },
        {
          data: 'codigos'
          , name: 'codigos' 
          , render: function ( data, type, row, meta ) {    
            if(data==null){
              return 'SIN PEDIDOS';
            }else{
              var returndata='';
              var jsonArray=data.split(",");
              $.each(jsonArray, function(i, item) {
                  returndata+=item+'<br>';
              });
              return returndata;
            }  
          }
        },
        {//asesor
          data: 'users', name: 'users' },
        {//cliente
          data: 'celular', 
            name: 'celular',
            render: function ( data, type, row, meta ) {
              return row.celular;
            },
        },
        {//observacion
          data: 'observacion', name: 'observacion'
        },
        /*{
          data: 'total_cobro', name: 'total_cobro'
        },*/
        {//totalpagado
          data: 'total_pago', name: 'total_pago'
        },
        {//fecha
          data: 'fecha', 
          name: 'fecha', 
          render: function ( data, type, row, meta ) {
              return data;
          }
        },//estado de pedido
        {
          data: 'condicion', 
          name: 'condicion', 
          render: function ( data, type, row, meta ) {            
            return data;             
          }
        },//estado de pago
        {data: 'action', name: 'action', orderable: false, searchable: false,sWidth:'20%'},
        ],
         language: {
           "decimal": "",
           "emptyTable": "No hay informaciÃ³n",
           "info": "Mostrando del _START_ al _END_ de _TOTAL_ Entradas",
           "infoEmpty": "Mostrando 0 to 0 of 0 Entradas",
           "infoFiltered": "(Filtrado de _MAX_ total entradas)",
           "infoPostFix": "",
           "thousands": ",",
           "lengthMenu": "Mostrar _MENU_ Entradas",
           "loadingRecords": "Cargando...",
           "processing": "Procesando...",
           "search": "Buscar:",
           "zeroRecords": "Sin resultados encontrados",
           "paginate": {
           "first": "Primero",
           "last": "Ultimo",
           "next": "Siguiente",
           "previous": "Anterior"
           }
         },
       });


     });

    });


      </script>

@stop
