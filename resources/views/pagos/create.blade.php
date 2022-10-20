@extends('adminlte::page')

@section('title', 'Agregar Pago')

@section('content_header')
  <h1>Agregar pago</h1>
@stop

@section('content')
  <div class="card">
    {!! Form::open(['route' => 'pagos.store','enctype'=>'multipart/form-data', 'id'=>'formulario','files'=>true]) !!}
    <div class="border rounded card-body border-secondary">
      <div class="form-row">
        <div class="form-group col-lg-6">
          {!! Form::label('user_id', 'Asesor') !!}
          <input type="hidden" name="user_id" requerid value="{{ Auth::user()->id }}" class="form-control">
          <input type="text" name="user_name" value="{{ Auth::user()->name }}" class="form-control" disabled>
        </div>
        <div class="form-group col-lg-6">
          {!! Form::label('cliente_id', 'Cliente*') !!}{!! Form::hidden('cliente_id', '',['id' => 'cliente_id']) !!}
            <select name="pcliente_id" class="border form-control selectpicker border-secondary" id="pcliente_id" data-live-search="true">
              <option value="">---- SELECCIONE CLIENTE ----</option>
                @foreach($clientes as $cliente)
                  <option value="{{ $cliente->id }}_{{ $cliente->saldo }}">{{$cliente->nombre}} - {{$cliente->celular}}</option>
              @endforeach
            </select>
        </div>
      </div>
    </div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group col-lg-6">
          <div class="form-row">
            <div class="form-group col-lg-6">
              <h2>PEDIDOS A PAGAR</h2>
            </div>
            <div class="form-group col-lg-6">
              <a data-target="#modal-add-pedidos" id="addpedido" data-toggle="modal"><button class="btn btn-info"><i class="fas fa-plus-circle"></i></button></a>  
            </div>
          </div>
          <div class="table-responsive">
            <table id="tabla_pedidos" class="table table-striped">
              <thead class="bg-info">
                <tr>
                  <th scope="col">ITEM</th>
                  <th scope="col">PEDIDO</th>
                  <th scope="col">CODIGO</th>
                  <th scope="col">MONTO</th>
                  <th scope="col">SALDO</th>
                  <th scope="col">ACCIÓN</th>
                </tr>
              </thead>
              <tfoot>
                <th style="text-align: center">TOTAL</th>
                <th></th>
                <th></th>
                <th></th>
                <th><h4 id="total_pedido">S/. 0.00</h4></th>
                <th><input type="hidden" name="total_pedido_pagar" requerid value="" id="total_pedido_pagar" class="form-control"></th>              
              </tfoot>
              <tbody>
              </tbody>
            </table>
          </div>
        </div>
        <div class="form-group col-lg-6">
          <div class="form-row" style="margin:-2px">
            <div class="form-group col-lg-6">
              <h2>PAGOS - <b style="font-size:20px"> {!! Form::label('saldo', 'Saldo a favor') !!}</b></h2>
            </div>
            <div class="form-group col-lg-4">
              <input type="text" name="saldo" id="saldo" class="form-control number" placeholder="Saldo a favor...">
            </div>
            <div class="form-group col-lg-2">
              <a data-target="#modal-add-pagos" id="addpago" data-toggle="modal"><button class="btn btn-primary"><i class="fas fa-plus-circle"></i></button></a>
            </div>
          </div>          
            @error('imagen')
              <small class="text-danger">{{$message}}</small>
            @enderror
          <div class="table-responsive">
            <table id="tabla_pagos" class="table table-striped">
              <thead class="bg-primary">
                <tr>
                  <th scope="col">ITEM</th>                
                  <th scope="col">BANCO</th>
                  <th scope="col">FECHA</th>
                  <th scope="col">IMAGEN</th>
                  <th scope="col">MONTO</th>
                  <th scope="col">ACCIÓN</th>
                </tr>
              </thead>
              <tfoot>
                <th style="text-align: center">TOTAL</th>
                <th></th>
                <th></th>
                <th></th>
                <th><h4 id="total_pago">S/. 0.00</h4></th>
                <th><input type="hidden" name="total_pago_pagar" requerid value="" id="total_pago_pagar" class="form-control"></th>  
              </tfoot>
              <tbody>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      {{-- MODALS --}}
      @include('pagos.modals.AddPedidos')
      @include('pagos.modals.AddPagos')
    </div>
    <div class="card-footer">
      <div class="form-row">
        <div id="guardar" class="form-group col-lg-1">
          <button id="registrar_pagos" type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar</button>
        </div>
        <div class="form-group col-lg-1">
          @if (Auth::user()->rol == "Asesor")
            <a class="btn btn-danger" href="{{ route('pagos.mispagos') }}"><i class="fas fa-times-circle"></i> Cancelar</a>
          @else
            <a class="btn btn-danger" href="{{ route('pagos.index') }}"><i class="fas fa-times-circle"></i> Cancelar</a>
          @endif
        </div>
        <div class="form-group col-lg-3"></div>
        <div class="form-group col-lg-4" style="text-align: center;">
          <div class="input-group">            
            <input type="text" name="" value="DIFERENCIA FALTANTE S/:" disabled class="form-control" style="color: red; font-weight:bold; font-weight: 900; font-size:21px">
            <input type="text" name="diferencia" value="" disabled id="diferencia" class="form-control" style="color: red; font-weight:bold; font-weight: 900; font-size:21px">   
          </div>
        </div>
      </div>
    </div>
    {!! Form::close() !!}
  </div>
@stop

@section('js')
  <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>  

    $("#guardar").hide();
    $("#addpedido").hide();
    $("#addpago").hide();
    $("#pcliente_id").change(mostrarBotones);

    function mostrarBotones() {
      $("#addpedido").show();
      $("#addpago").show();
    }    

    // CARGAR PEDIDOS DE CLIENTE SELECCIONADO
    $("#pcliente_id").change(function() {
      datosCliente = document.getElementById('pcliente_id').value.split('_');

      cliente_id = datosCliente[0];
      saldo = datosCliente[1];

      $("#cliente_id").val(cliente_id);
      $("#saldo").val(saldo);      

      $.ajax({
        url: "{{ route('cargar.pedidoscliente') }}?cliente_id=" + $(this).val(),
        method: 'GET',
        success: function(data) {
          $('#ppedido_id').html(data.html);
        }
      });
    });
    
    //VALIDAR CAMPO FECHAS MAX DIA ACTUAL
    var today = new Date().toISOString().split('T')[0];
    document.getElementsByName("pfecha")[0].setAttribute('max', today);

    // AGREGANDO PEDIDOS
    $('#add_pedido').click(function() {
      agregarPedido();
    });

    function Remove_options(Pedido_delete)
    {
      $("#ppedido_id option[value='" + Pedido_delete +"']").remove();
    }

    diferencia = 0;
    total_pedido = 0;
    subtotal_pedido = [];
    var contPe = 1;

    function agregarPedido() {
      datosPedido = document.getElementById('ppedido_id').value.split('_');
      Pedido_delete = document.getElementById('ppedido_id').value

      pedido_id = datosPedido[0];
      codigo = datosPedido[1];
      monto = datosPedido[2];
      saldo = datosPedido[3];

      if (pedido_id != "") {
        subtotal_pedido[contPe] = saldo*1;
        total_pedido = total_pedido + subtotal_pedido[contPe];

        var filasPe = '<tr class="selected" id="filasPe' + contPe + '">' +
          '<td>' + contPe + '</td>' +
          '<td style="display:none;" ><input type="hidden" name="pedido_id[]" value="' + pedido_id + '">' + pedido_id + '</td>' +
          '<td><input type="hidden" name="" value="">PED000' + pedido_id + '</td>' +
          '<td><input type="hidden" name="" value="">' + codigo + '</td>' +
          '<td><input type="hidden" name="" id= "numbermonto" value="">S/' + monto + '</td>' +
          '<td><input type="hidden" name="" id= "numbersaldo" value="">S/' + saldo + '</td>' +
          '<td><button type="button" class="btn btn-danger btn-sm" onclick="eliminarPe(' + contPe + ')"><i class="fas fa-trash-alt"></i></button></td>' +
          '</tr>';
        contPe++;
        limpiarPe();
        $("#total_pedido").html("S/. " + total_pedido.toLocaleString("en-US"));
        $("#total_pedido_pagar").val(total_pedido.toLocaleString("en-US"));
        evaluarPe();
        diferenciaFaltante();
        $('#tabla_pedidos').append(filasPe);
        Remove_options(Pedido_delete);
      } else {
        Swal.fire(
          'Error!',
          'Error al agregar el pedido',
          'warning')
      }
    }    
    
    function limpiarPe() {
      $("#ppedido_id").val("");
      $("#total_pedido").val("");
    }

    function evaluarPe() {
      if (total_pedido > 0 && total_pago > 0) {
        $("#guardar").show();
      } else {
        $("#guardar").hide();
      }
    }

    function diferenciaFaltante() {
      diferencia = total_pedido - total_pago;
      $("#diferencia").val(diferencia.toLocaleString("en-US"));
    }

    function eliminarPe(index) {
      total_pedido = total_pedido - subtotal_pedido[index];
      $("#total_pedido").html("S/. " + total_pedido.toLocaleString("en-US"));
      $("#total_pedido_pagar").val(total_pedido);
      $("#filasPe" + index).remove();
      evaluarPe();
    }

    //VALIDAR CAMPOS NUMERICO DE MONTO EN PAGOS
    
    $('input.number').keyup(function(event) {

    if(event.which >= 37 && event.which <= 40){
      event.preventDefault();
    }

    $(this).val(function(index, value) {
      return value
        .replace(/\D/g, "")
        .replace(/([0-9])([0-9]{2})$/, '$1.$2')  
        .replace(/\B(?=(\d{3})+(?!\d)\.?)/g, ",")
      ;
    });
    });

    //VALIDANDO CAMPOS DE PAGOS
    $(document).ready(function() {
      $('#add_pago').click(function() {
        if ($('#pmonto').val() == '') {
          Swal.fire(
            'Error',
            'Ingrese monto',
            'warning'
          )
        }
        else if ($('#pbanco').val() == ''){
          Swal.fire(
            'Error',
            'Seleccione banco ',
            'warning'
          )
        }
        else if ($('#pfecha').val() == ''){
          Swal.fire(
            'Error',
            'Seleccione la fecha',
            'warning'
          )
        }
        else {
          deuda = !isNaN($('#pcantidad').val()) ? parseInt($('#pcantidad').val(), 10) : 0;
          pagado = !isNaN($('#pstock').val()) ? parseInt($('#pstock').val(), 10) : 0;

          agregarPago();
        }
      })
    });
    // CAMBIAR IMAGEN
    /* document.getElementById("imagen").addEventListener('change', cambiarImagen());

    function cambiarImagen(event){
        var file = event.target.files[0];

        var reader = new FileReader();
        reader.onload = (event) => {
            document.getElementById("picture").setAttribute('src', event.target.result);
        };

        reader.readAsDataURL(file);
    } */

    total_pago = 0;
    subtotal_pago = [];
    var contPa = 0;

    // AGREGANDO PAGOS
    function agregarPago() {
      var strEx = $("#pmonto").val();//1,000.00
      //primer paso: fuera coma
      strEx = strEx.replace(",","");//1000.00
      var numFinal = parseFloat(strEx);
      monto = numFinal;
      banco = $('#pbanco option:selected').val();
      fecha = $("#pfecha").val();
      /* imagen = $("#pimagen").val(); */


      if (monto != ""  && banco != "" && fecha != ""/*  && imagen != "" */) {
        subtotal_pago[contPa] = monto*1;
        total_pago = total_pago + subtotal_pago[contPa];

        var filasPa = '<tr class="selected" id="filasPa' + contPa + '">' +
          '<td>' + (contPa + 1) + '</td>' +          
          '<td><input type="hidden" name="banco[]" value="' + banco + '">' + banco + '</td>' +
          '<td><input type="hidden" name="fecha[]" value="' + fecha + '">' + fecha + '</td>' +
          '<td>@csrf<input type="file" id="imagen" name="imagen[]" accept= "image/*" /></td>' + 
            /* <img id="picture" src="{{asset('imagenes/logo_facturas.png')}}" alt="Imagen del pago" height="100px" width="100px"> */        
          '<td><input type="hidden" name="monto[]" value="' + monto + '">' + monto + '</td>' +
          '<td><button type="button" class="btn btn-danger btn-sm" onclick="eliminarPa(' + contPa + ')"><i class="fas fa-trash-alt"></i></button></td>' +
          '</tr>';
          

        contPa++;
        limpiarPa();
        $("#total_pago").html("S/. " + total_pago.toLocaleString("en-US"));
        $("#total_pago_pagar").val(total_pago.toLocaleString("en-US"));
        evaluarPa();
        diferenciaFaltante();
        $('#tabla_pagos').append(filasPa);
      } else {
        Swal.fire(
          'Error!',
          'Información faltante del pago',
          'warning')
      }
    }

    function limpiarPa() {
      $("#pmonto").val("");
      $("#pbanco").val('').change();
      $("#pfecha").val("");
      $("#pimagen").val("");
    }

    function evaluarPa() {
      if (total_pedido > 0 && total_pago > 0) {
        $("#guardar").show();
      } else {
        $("#guardar").hide();
      }
    }

    function eliminarPa(index) {
      total_pago = total_pago - subtotal_pago[index];
      $("#total_pago").html("S/. " + total_pago.toLocaleString("en-US"));
      $("#total_pago_pagar").val(total_pago);
      $("#filasPa" + index).remove();
      evaluarPa();
    }

  //VALIDAR ANTES DE ENVIAR
  document.addEventListener("DOMContentLoaded", function() {
  document.getElementById("formulario").addEventListener('submit', validarFormulario); 
    });

    function validarFormulario(evento) {
      evento.preventDefault();      
      var total_pedido_pagar = document.getElementById('total_pedido_pagar').value;
      var total_pedido = document.getElementById('total_pedido').value;
      var total_pago_pagar = document.getElementById('total_pago_pagar').value;
      var total_pago = document.getElementById('total_pago').value;
      var falta = total_pedido_pagar - total_pago_pagar;
      falta = falta.toFixed(2);
      /* var imagen = document.getElementById('imagen').value; */

      //Obtengo todos los campos con el nombre cantidad[]
      imagen = document.getElementsByName("imagen[]");

      //Creo el arreglo donde almaceno sus valores
      var img = [];

      //Recorro todos los nodos que encontre que coinciden con ese nombre
      for(var i=0;i<imagen.length;i++){
      //Añado el valor que contienen los campos
          img.push(imagen[i].value);
      }

      /* console.info(img.includes('')); */

      //valido si hay una imagen vacia
      if(img.includes('') == true)
      {
        Swal.fire(
            'Error',
            'Seleccione una imagen para cada pago agregado',
            'warning'
          )
      }
      else if (total_pedido_pagar*1 < total_pago_pagar*1 ) {
          Swal.fire(
            'Error',
            'No se puede ingresar un pago mayor a la deuda que tiene el cliente',
            'warning'
          )
      }
      else if(total_pedido_pagar - total_pago_pagar > 1) {
          Swal.fire({
            icon: 'warning',
            title: 'Pago incompleto ¿Estás seguro?',
            text: "Falta S/" + falta + " para cancelar",            
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, guardar!'
          }).then((result) => {
            if (result.isConfirmed) {
              this.submit();
            }
          })
      }
      else {
          this.submit();
      }      
    }
  </script>

@stop
