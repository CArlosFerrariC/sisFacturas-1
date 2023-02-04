@extends('adminlte::page')

@section('title', 'Base fría')

@section('style')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.20/css/dataTables.bootstrap4.min.css">
@endsection

@section('content_header')
  <h1>Base fría
    @can('base_fria.create')
      <a href="{{ route('basefria.create') }}" class="btn btn-info"><i class="fas fa-plus-circle"></i> Agregar</a>
    @endcan
    @can('base_fria.exportar')
    <div class="float-right btn-group dropleft">


            <a href="{{route('excel/basefria/exportar')}}" target="_blank" class="btn btn-dark mr-4">
                <i class="fa fa-download"></i>
                <i class="fa fa-file-excel"></i>
                Base fria Asesor
            </a>


        @if(Auth::user()->rol == 'Administrador')
            <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Exportar
            </button>
            <div class="dropdown-menu">
                <a href="" data-target="#modal-exportar2" data-toggle="modal" class="dropdown-item" target="blank_"><img src="{{ asset('imagenes/icon-excel.png') }}"> Base fría por asesor</a>
            </div>
        @endif

    </div>
    {{-- @include('base_fria.modal.exportar') --}}
    @include('base_fria.modal.exportar2')
    @endcan
  </h1>
  @if($superasesor > 0)
  <br>
  <div class="bg-4">
    <h1 class="t-stroke t-shadow-halftone2" style="text-align: center">
      asesores con privilegios superiores: {{ $superasesor }}
    </h1>
  </div>
  @endif
@stop

@section('content')


            <div class="card">
                <div class="card-body">
                    <div class="card-title">

                    </div>

                    <table class="table table-striped data-table" id="tablaserverside" style="width:100%">
                        <thead>
                            <tr>
                                <th>COD.</th>
                                <th>Nombre de cliente</th>
                                <th>Celular</th>
                                <th>Asesor</th>
                                <th width="100px">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                    @include('base_fria.modal.convertirid')
                    @include('base_fria.modal.modalid')
                </div>
            </div>

@stop


@section('css')
	<link rel="stylesheet" href="/css/admin_custom.css">
<style>
    .bg-4{
      background: linear-gradient(to right, rgb(240, 152, 25), rgb(237, 222, 93));
    }

    .t-stroke {
        color: transparent;
        -moz-text-stroke-width: 2px;
        -webkit-text-stroke-width: 2px;
        -moz-text-stroke-color: #000000;
        -webkit-text-stroke-color: #ffffff;
    }

    .t-shadow-halftone2 {
        position: relative;
    }

    .t-shadow-halftone2::after {
        content: "AWESOME TEXT";
        font-size: 10rem;
        letter-spacing: 0px;
        background-size: 100%;
        -webkit-text-fill-color: transparent;
        -moz-text-fill-color: transparent;
        -webkit-background-clip: text;
        -moz-background-clip: text;
        -moz-text-stroke-width: 0;
        -webkit-text-stroke-width: 0;
        position: absolute;
        text-align: center;
        left: 0px;
        right: 0;
        top: 0px;
        z-index: -1;
        background-color: #ff4c00;
        transition: all 0.5s ease;
        text-shadow: 10px 2px #6ac7c2;
    }
    .textred {
            color: red !important;
        }
  </style>
@stop


@section('js')

<!--<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.js"></script>  -->
<script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js"></script>
<script>
  function resetearcamposdelete() {
            $('#motivo').val("");
            //$('#responsable').val("");
        }

$(document).ready(function () {

    $('#modal-delete').on('hidden.bs.modal', function (event) {
        $("#motivo").val('')
        $("#anulacion_password").val('')
        $("#attachments").val(null)
    })

    $('#modal-delete').on('show.bs.modal', function (event) {
      //cuando abre el form de anular pedido
      var button = $(event.relatedTarget)
      var idunico = button.data('cliente')//id  basefria
      var idresponsable = button.data('responsable')//id  basefria
      var idcodigo = button.data('asesor')
      //console.log(idunico);
      $("#hiddenIDdelete").val(idunico);
      if (idcodigo < 10) {
          idcodigo = 'BF' + idcodigo+'000'+idunico;
      } else if (idcodigo < 100) {
          idcodigo = 'BF' + idcodigo+'00'+idunico;
      } else if (idunico < 1000) {
          idcodigo = 'BF' + idcodigo+'0'+idunico;
      } else {
          idcodigo = 'BF' + idcodigo+''+idunico;
      }
      //solo completo datos
      //hiddenId
      //

      console.log(idcodigo)
      $(".textcode").html(idcodigo);
      $("#motivo").val('');
      //$("#responsable").val(idresponsable);

  });

    $(document).on("submit", "#formdelete", function (evento) {
        evento.preventDefault();
        console.log("action delete action")

        var formData = new FormData();
        formData.append("hiddenID", $("#hiddenIDdelete").val())

        formData.append("motivo", $("#motivo").val())
        formData.append("responsable", $("#responsable").val())
        formData.append("anulacion_password", $("#anulacion_password").val())
        if ($("#attachments")[0].files.length > 0) {
            var attachments = Array.from($("#attachments")[0].files)
            attachments.forEach(function (file) {
                formData.append("attachments[]", file, file.name)
            })
        }
        console.log(formData);
        $.ajax({
            type: 'POST',
            url: "{{ route('basefriadeleteRequest.post') }}",
            data: formData,
            processData: false,
            contentType: false,
        }).done(function (data) {
            $("#modal-delete").modal("hide");
            resetearcamposdelete();
            $('#tablaPrincipal').DataTable().ajax.reload();
        }).fail(function (err, error, errMsg) {
            console.log(arguments, err, errMsg)
            if (err.status == 401) {
                Swal.fire(
                    'Error',
                    'No autorizado para poder bloquear el cliente, ingrese una contraseña correcta',
                    'error'
                )
            } else {
                Swal.fire(
                    'Error',
                    'Ocurrio un error: ' + errMsg,
                    'error'
                )
            }
        });
    });

    $('#tablaserverside').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('basefriatabla') }}",
        initComplete:function(settings,json){

        },
        "createdRow": function (row, data, dataIndex) {
          if(data["situacion"]=='BLOQUEADO')
          {
              $(row).addClass('textred');
          }
        },
        columns: [
        {
            data: 'id',
            name: 'id',
            render: function ( data, type, row, meta ) {
                if(row.id<10){
                    if(row.identificador==null){
                        return 'BF'+'000'+row.id;
                    }else{
                        return 'BF'+row.identificador+'000'+row.id;
                    }
                }else if(row.id<100){
                    if(row.identificador==null){
                        return 'BF'+'00'+row.id;
                    }else{
                        return 'BF'+row.identificador+'00'+row.id;
                    }
                }else if(row.id<1000){
                    if(row.identificador==null){
                        return 'BF'+'0'+row.id;
                    }else{
                        return 'BF'+row.identificador+'0'+row.id;
                    }
                }else{
                    return 'BF'+row.identificador+'000'+row.id;
                }
                //return row.id+' aa';
                //return ''+data+'';
            }},
        {
            data: 'nombre',
            name: 'nombre',
        },
        {
          data: 'celular',
          name: 'celular',
          render: function ( data, type, row, meta ) {
            if(row.icelular!=null)
            {
              return row.celular+'-'+row.icelular;
            }else{
              return row.celular;
            }
          }
        },
        {
            data: 'identificador',
            name: 'Asesor',
        },
        {
          data: 'action',
          name: 'action',
          orderable: false,
          searchable: false,
          sWidth:'20%',
        },
        ],
        language: {
        "decimal": "",
        "emptyTable": "No hay información",
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
    $('#tablaPrincipal_filter label input').on('paste', function(e) {
      var pasteData = e.originalEvent.clipboardData.getData('text')
      localStorage.setItem("search_tabla",pasteData);
    });
    $(document).on("keypress",'#tablaPrincipal_filter label input',function(){
      localStorage.setItem("search_tabla",$(this).val());
      console.log( "search_tabla es "+localStorage.getItem("search_tabla") );
    });


    $('#modal-convertir').on('show.bs.modal', function (event) {

      var button = $(event.relatedTarget)
      var idunico = button.data('opcion')//id  basefria
      console.log(idunico);
      //id return

      $.ajax({
        url: "{{ route('basefria.cargarid') }}?basefria_id=" + idunico,
        method: 'GET',
        dataType:'JSON',
        success: function(data) {
          let result=JSON.parse(JSON.stringify(data.html[0]));
          let codigo='';
          if(result['id']<10)
          {
            codigo='BF000'+result['id']+result['celular'];
          }else if(result['id']<100)
          {
            codigo='BF00'+result['id']+result['celular'];
          }else if(result['id']<100)
          {
            codigo='BF0'+result['id']+result['celular'];
          }else if(result['id']<1000)
          {
            codigo='BF'+result['id']+result['celular'];
          }
          /*console.log(codigo);
          console.log(result['nombre']);
          console.log(result['celular']);
          console.log(result['id']);*/
          $(".textcode").html(codigo);
          $("#nombre").val(result['nombre']);
          $("#celular").val(result['celular']);
          $("#hiddenId").val(result['id']);


          //$('#pruc').html(data.html);
        }
      });

      /*var button = $(event.relatedTarget)
      var recipient = button.data('whatever')

      var modal = $(this)
      modal.find('.modal-title').text('New message to ' + recipient)
      modal.find('.modal-body input').val(recipient)*/
      //console.log("asd");

    });
    //


    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });


    /*$(document).on("click","#submit",function(e){


        event.preventDefault();
    });*/


});
</script>

@if (session('info') == 'registrado' || session('info') == 'actualizado' || session('info') == 'eliminado')
    <script>
      Swal.fire(
        'Base fría {{ session('info') }} correctamente',
        '',
        'success'
      )
    </script>
  @endif

  <script>
      //VALIDAR CAMPO CELULAR
    function maxLengthCheck(object)
    {
      if (object.value.length > object.maxLength)
        object.value = object.value.slice(0, object.maxLength)
    }
  </script>

<script>
  //VALIDAR CAMPOS ANTES DE ENVIAR
    document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("formconvertir").addEventListener('submit', validarFormulario);
    });

    function validarFormulario(evento) {
      evento.preventDefault();
      var nombre = document.getElementById('nombre').value;
      /*var dni = document.getElementById('dni').value;*/
      var celular = document.getElementById('celular').value;
      /*var provincia = document.getElementById('provincia').value;
      var distrito = document.getElementById('distrito').value;
      //var direccion = document.getElementById('direccion').value;
      var referencia = document.getElementById('referencia').value;*/
      var porcentaje1 = document.getElementById('porcentaje1').value;
      var porcentaje2 = document.getElementById('porcentaje2').value;
      var porcentaje3 = document.getElementById('porcentaje3').value;
      var porcentaje4 = document.getElementById('porcentaje4').value;

        if (nombre == '') {
          Swal.fire(
            'Error',
            'Ingrese nombre de cliente',
            'warning'
          )
        }
        else if (celular == ''){
          Swal.fire(
            'Error',
            'Agregue número celular del cliente',
            'warning'
          )
        }
        else if (celular.length != 9){
          Swal.fire(
            'Error',
            'Número celular del cliente debe tener 9 dígitos',
            'warning'
          )
        }
        /*else if (provincia == ''){
          Swal.fire(
            'Error',
            'Registre la provincia del cliente',
            'warning'
          )
        }
        else if (distrito == ''){
          Swal.fire(
            'Error',
            'Registre el distrito del cliente',
            'warning'
          )
        }*/
        /*
        else if (direccion == ''){
          Swal.fire(
            'Error',
            'Registre la direccion del cliente',
            'warning'
          )
        }*/
        /*else if (referencia == ''){
          Swal.fire(
            'Error',
            'Registre la referencia del cliente',
            'warning'
          )
        }
        else if (dni.length == 0){//provincia.toUpperCase() != ('lima').toUpperCase() &&
          Swal.fire(
            'Error',
            'Clientes de provincia necesitan registrar el DNI',
            'warning'
          )
        }
        else if (dni.length != 8){//provincia.toUpperCase() != ('lima').toUpperCase() &&
          Swal.fire(
            'Error',
            'El DNI debe tener 8 dígitos',
            'warning'
          )
        }*/
        else if (porcentaje1 == '0' || porcentaje1 == ''){
          Swal.fire(
            'Error',
            'Registre el porcentaje: FISIO - sin banca',
            'warning'
          )
        }
        else if (porcentaje2 == '0' || porcentaje2 == ''){
          Swal.fire(
            'Error',
            'Registre el porcentaje: ELECTRONICA - sin banca',
            'warning'
          )
        }
        else if (porcentaje3 == '0' || porcentaje3 == ''){
          Swal.fire(
            'Error',
            'Registre el porcentaje: FISICO - banca',
            'warning'
          )
        }
        else if (porcentaje4 == '0' || porcentaje4 == ''){
          Swal.fire(
            'Error',
            'Registre el porcentaje: ELECTRONICA - banca',
            'warning'
          )
        }
        else {
          clickformconvertir();
          //this.trigger("click");

        }
    }

    function resetearcamposconvertir(){
      $('#nombre').val("");
      $('#dni').val("");
      $('#celular').val("");
      $('#provincia').val("");
      $('#distrito').val("");
      $('#direccion').val("");
      $('#referencia').val("");
      $('#porcentaje1').val("");
      $('#porcentaje2').val("");
      $('#porcentaje3').val("");
      $('#porcentaje4').val("");
    }

    function clickformconvertir()
    {
      var formData = $("#formconvertir").serialize();
        console.log(formData);
        $.ajax({
          type:'POST',
          url:"{{ route('basefriaRequest.post') }}",
          data:formData,
        }).done(function (data) {

          $("#modal-convertir").modal("hide");
          resetearcamposconvertir();

          $('#tablaserverside').DataTable().ajax.reload();
          //console.log("resultados");
          //console.log(data);

        });
    }
  </script>


@stop
