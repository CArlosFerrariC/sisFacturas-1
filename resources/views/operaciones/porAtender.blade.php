@extends('adminlte::page')

@section('title', 'Operaciones | Pedidos por atender')

@section('content_header')
  <h1>Lista de pedidos por atender - OPERACIONES
    {{-- @can('pedidos.exportar')
    <div class="float-right btn-group dropleft">
      <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        Exportar
      </button>
      <div class="dropdown-menu">
        <a href="{{ route('pedidosporatenderExcel') }}" class="dropdown-item"><img src="{{ asset('imagenes/icon-excel.png') }}"> EXCEL</a>
      </div>
    </div>
    @endcan --}}
    <div class="float-right btn-group dropleft">
      <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        Exportar
      </button>
      <div class="dropdown-menu">
        <a href="" data-target="#modal-exportar" data-toggle="modal" class="dropdown-item" target="blank_"><img src="{{ asset('imagenes/icon-excel.png') }}"> Excel</a>
      </div>
    </div>
    @include('pedidos.modal.exportar', ['title' => 'Exportar pedidos por atender', 'key' => '7'])
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
      <br>
      <table id="tablaPrincipal" class="table table-striped">
        <thead>
          <tr>
            <th scope="col">Item</th>
            <th scope="col">Código</th>
            <th scope="col">Razón social</th>
            <th scope="col">Asesor</th>
            <th scope="col">Fecha de registro</th>{{--fecha hora--}}
            <th scope="col">Tipo de Banca</th>
            <th scope="col">Adjuntos</th>
            <th scope="col">Estado</th>
            <th scope="col">Acciones</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
      @include('pedidos.modalid')
      @include('operaciones.modal.atenderid')
      @include('operaciones.modal.veradjuntoid')
      @include('pedidos.modal.DeleteAdjuntoid')
    </div>
  </div>

@stop

@section('css')
  {{-- <link rel="stylesheet" href="../css/admin_custom.css"> --}}
  <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

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

  </style>
@stop

@section('js')
  {{--<script src="{{ asset('js/datatables.js') }}"></script>--}}

  <script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js"></script>

  <script src="https://momentjs.com/downloads/moment.js"></script>
  <script src="https://cdn.datatables.net/plug-ins/1.11.4/dataRender/datetime.js"></script>

  <script>
    $(document).ready(function () {

      $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
      });

      function validarFormulario(evento) {
      var adjunto = document.getElementById('adjunto').files;
      var cant_compro = document.getElementById('cant_compro').value;
     
      if (adjunto.length == 0) {
          Swal.fire(
            'Error',
            'Debe registrar almenos un documento adjunto',
            'warning'
          )
          return false;
        }






        else if (cant_compro == '0'){
          Swal.fire(
            'Error',
            'Cantidad de comprobantes enviados debe ser diferente de 0 (cero)',
            'warning'
          )

          return false;
        }else if (adjunto.length != cant_compro) {
          Swal.fire({
              icon: 'warning',
              title: 'Aviso',
              text: 'La cantidad de adjuntos es diferente a la cantidad ingresada',
              confirmButtonText:'Confirmar',
          }).then((result) => {
              /* Read more about isConfirmed, isDenied below */
              if (result.isConfirmed) {

              } else if (result.isDenied) {
              }
          })

      }

        return true;
    }

        $(document).on("submit", "#formulario_adjuntos", function (evento) {
        evento.preventDefault();

        let idunico=$("#hiddenAtender").val();
        console.log(idunico);
        $('#cargar_adjunto').attr("disabled",true);
        //$(this).attr('disabled',true);
        //$(this).text('Subiendo archivos...');
        $('#cargar_adjunto').html('Subiendo archivos...');
        //e.preventDefault();
        var data = new FormData(document.getElementById("formulario_adjuntos"));

        $.ajax({
                  type:'POST',
                  url: "{{ route('operaciones.updateatender',':id') }}".replace(':id',idunico),
                  data: data,
                  processData: false,
                  contentType: false,
                  success:function(data){
                    $('#cargar_adjunto').prop("disabled",false);
                    $('#cargar_adjunto').text('Confirmar');

                    ///RecuperarAdjuntos(idunico);
                    $.ajax({
                        url: "{{ route('operaciones.editatencion',':id') }}".replace(':id',idunico),
                        data: idunico,
                        method: 'POST',
                        success: function(data) {
                          console.log(data)
                          console.log("obtuve las imagenes atencion del pedido "+idunico)
                        $('#listado_adjuntos').html(data);
                        }
                    });

                  }
              }).done(function (data) {

              });

              return false;
      });

      $(document).on("click", "#formularioatender", function (evento) {


      });


    $(document).on("submit", "#formularioatender", function (evento) {
      evento.preventDefault();
      var status= validarFormulario(evento);
      var cant_compro = document.getElementById('cant_compro').value;
      var cantidad_archivos = $("");
       if(!status){
        return;
       }

        let files=$('input[name="adjunto[]');
        //console.log(files)

        var imagen = $('input[type=file][name="adjunto[]"]')[0].files[0];
      // console.log(imagen)
      //return false;

      var data = new FormData(document.getElementById("formularioatender"));

        var fd = new FormData();

        console.log(files.length+" cantidad adjuntos sin confirmar")

        if(files.length == 0)
        {
          Swal.fire(
              'Error',
              'Debe ingresar el detalle del pedido',
              'warning'
            )
            return false;
        }

        if(cant_compro == "")
        {
            Swal.fire(
                'Error',
                'Debe colocar la cantidad de archivos',
                'warning'
            )
            return false;
        }

        for (let i = 0; i < files.length; i++) {
          fd.append('adjunto', $('input[type=file][name="adjunto[]"]')[0].files[0]);
        }

        //console.log(files);
        //return false;
        //fd.append( 'cant_compro', $("#cant_compro").val() );
        fd.append( 'cant_compro', files.length );
        fd.append( 'condicion', $("#condicion").val() );
        fd.append( 'hiddenAtender', $("#hiddenAtender").val() );

        $.ajax({
           data: data,
           processData: false,
           contentType: false,
           type: 'POST',
           url:"{{ route('operaciones.atenderid') }}",
           success:function(data)
           {
            console.log(data);
            $("#modal-atender .textcode").text('');
            $("#modal-atender").modal("hide");
            $('#tablaPrincipal').DataTable().ajax.reload();

           }

        });
        console.log(fd);
      });

      $('#modal-atender').on('show.bs.modal', function (event) {
        //cuando abre el form de anular pedido
        var button = $(event.relatedTarget)
        var idunico = button.data('atender')
        var idcodigo = button.data('codigo')
        $(".textcode").html(idcodigo);
        $("#hiddenAtender").val(idunico);

        $("#adjunto").val("");

        $.ajax({
            url: "{{ route('operaciones.editatencionsinconfirmar',':id') }}".replace(':id', idunico),
            data: idunico,
            method: 'POST',
            success: function (data) {
                console.log(data)
                console.log("obtuve las imagenes atencion del pedido " + idunico)
                $('#listado_adjuntos').html("");
                //$('#listado_adjuntos_antes').html(data);
                console.log(data);
            }
        });

        $.ajax({
            url: "{{ route('operaciones.editatencion',':id') }}".replace(':id', idunico),
            data: idunico,
            method: 'POST',
            success: function (data) {
                console.log(data)
                console.log("obtuve las imagenes atencion del pedido " + idunico)
                //$('#listado_adjuntos').html("");
                $('#listado_adjuntos_antes').html(data);
                console.log(data);
            }
        });

      });

      $(document).on("click", "#cargar_adjunto", function (evento) {
        let idunico = $("#hiddenAtender").val();
        console.log(idunico);
        $('#cargar_adjunto').attr("disabled", true);
        $('#cargar_adjunto').html('Subiendo archivos...');
        //e.preventDefault();
        let cant_compro=$("#cant_compro").val();
        if(cant_compro=='')$("#cant_compro").val(0);
        var data = new FormData(document.getElementById("formularioatender"));

        $.ajax({
          type: 'POST',
          url: "{{ route('operaciones.updateatendersinconfirmar',':id') }}".replace(':id', idunico),
          data: data,
          processData: false,
          contentType: false,
          success: function (data) {
              $('#cargar_adjunto').prop("disabled", false);
              $('#cargar_adjunto').text('Subir Informacion');

              $.ajax({
                  url: "{{ route('operaciones.editatencionsinconfirmar',':id') }}".replace(':id', idunico),
                  data: idunico,
                  method: 'POST',
                  success: function (data) {
                      console.log(data)
                      console.log("obtuve las imagenes atencion del pedido " + idunico)
                      $('#listado_adjuntos').html(data);
                  }
              });
          }
        }).done(function (data) {
        });
        return false;


      });

      $(document).on("submit", "#formdeleteadjunto", function (evento) {
          evento.preventDefault();
          console.log("ejecutando eliminando adjunto")
          let pedidoidimagenes = $("#eliminar_pedido_id").val();
          let pedidoconfirmado = $("#eliminar_pedido_id_confirmado").val();/*0 o 1*/
          console.log(pedidoidimagenes);
          var fddeleteadjunto = new FormData();
          fddeleteadjunto.append('eliminar_pedido_id', pedidoidimagenes);
          fddeleteadjunto.append('eliminar_pedido_id_imagen', $("#eliminar_pedido_id_imagen").val());
          fddeleteadjunto.append('eliminar_pedido_id_confirmado', pedidoconfirmado);
          console.log(fddeleteadjunto);

          $.ajax({
              url: "{{ route('operaciones.eliminaradjunto') }}",
              type: 'POST',
              data: fddeleteadjunto,
              processData: false,
              contentType: false,
              success: function (data) {
                  console.log("rest ajax")
                  $('.adjuntos[data-adjunto="' + data.html + '"]').remove();
                  $('#modal-delete-adjunto').modal('toggle');
                  if(pedidoconfirmado==1)
                  {
                    $.ajax({
                      url: "{{ route('operaciones.editatencion',':id') }}".replace(':id', pedidoidimagenes),
                      data: pedidoidimagenes,
                      method: 'POST',
                      success: function (data) {
                          console.log(data)
                          console.log("obtuve las imagenes atencion del pedido " + pedidoidimagenes)
                          $('#listado_adjuntos_antes').html(data);
                      }
                  });
                  }else if(pedidoconfirmado==0)
                  {
                    $.ajax({
                      url: "{{ route('operaciones.editatencionsinconfirmar',':id') }}".replace(':id', pedidoidimagenes),
                      data: pedidoidimagenes,
                      method: 'POST',
                      success: function (data) {
                          console.log(data)
                          console.log("obtuve las imagenes atencion del pedido " + pedidoidimagenes)
                          $('#listado_adjuntos').html(data);
                      }
                  });
                  }
                  
              }
          }).done(function (data) {
          });
      });

      $('#modal-delete-adjunto').on('show.bs.modal', function (event) {
            //cuando abre el form de anular pedido
            var button = $(event.relatedTarget)
            var img_pedidoid = button.data('imgid')
            var imgadjunto = button.data('imgadjunto')
            var imgadjuntoconfirm = button.data('imgadjuntoconfirm')
            $(".textcode").html("PED" + img_pedidoid);
            $("#eliminar_pedido_id").val(img_pedidoid);
            $("#eliminar_pedido_id_imagen").val(imgadjunto);
            $("#eliminar_pedido_id_confirmado").val(imgadjuntoconfirm);
        });

      $('#modal-veradjunto').on('show.bs.modal', function (event) {
        //cuando abre el form de anular pedido
        var button = $(event.relatedTarget)
        var idunico = button.data('adjunto')
        var idcodigo = button.data('codigo')
        $(".textcode").html(idcodigo);

        //consulta de imagenes
          $.ajax({
            type:'POST',
            url:"{{ route('pedidoobteneradjuntoRequest') }}",
            data:{"pedido":idunico},
          }).done(function (data) {
            //console.log(data.html);
            console.log(data.cantidad);
            if(data.cantidad>0)
            {
              ////recorrer y poner imagenes en div con router
              var adjuntos = data.html.split('|');
              //console.log(adjuntos);
              var urladjunto="";
              var datal="";
              $.each(adjuntos, function( index, value ) {
                urladjunto = '{{ route("pedidos.descargaradjunto", ":id") }}';
                urladjunto = urladjunto.replace(':id', value);
                datal = datal+'<p><a href="'+urladjunto+'"><i class="fa fa-file mr-2"></i>'+value+'</a><p>';
                //console.log(datal);
                //console.log( index + ": " + value );
              });
              $("#imagenes_adjunto").html(datal)
              return datal;
              //console.log(data.html)
            }else{
              console.log("sin imagenes");
            }
          });

      });

      $('#modal-delete').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget)
        var idunico = button.data('delete')
        var idresponsable = button.data('responsable')
        $("#hiddenIDdelete").val(idunico);
        if(idunico<10){
          idunico='PED000'+idunico;
        }else if(idunico<100){
          idunico= 'PED00'+idunico;
        }else if(idunico<1000){
          idunico='PED0'+idunico;
        }else{
          idunico='PED'+idunico;
        }

        $(".textcode").html(idunico);
        $("#motivo").val('');
        $("#responsable").val( idresponsable );
      });

      $('#tablaPrincipal').DataTable({
        processing: true,
        //stateSave:true,
		    serverSide: true,
        //searching: true,
        order: [[ 4, "asc" ]],
        ajax: "{{ route('operaciones.poratendertabla') }}",
        createdRow: function( row, data, dataIndex){
        },
        rowCallback: function (row, data, index) {
            if(data.pendiente_anulacion==1){
                $('td',row).css('background', 'red').css('font-weight','bold');
            }
        },
        columns: [
          {
              data: 'id',
              name: 'id',
              render: function ( data, type, row, meta ) {
                if(row.id<10){
                  return 'PED000'+row.id;
                }else if(row.id<100){
                  return 'PED00'+row.id;
                }else if(row.id<1000){
                  return 'PED0'+row.id;
                }else{
                  return 'PED'+row.id;
                }
              }
          },
          {data: 'codigos', name: 'codigos', },
          {data: 'empresas', name: 'empresas', },
          {data: 'users', name: 'users', },
          {
            data: 'fecha',
            name: 'fecha',
            render:$.fn.dataTable.render.moment('YYYY-MM-DD HH:mm:ss', 'DD/MM/YYYY HH:mm:ss' ),
            orderable: true,
          },
          {data: 'tipo_banca', name: 'tipo_banca', },
          {
            data: 'imagenes',
            name: 'imagenes',
            orderable: false,
            searchable: false,
            sWidth:'20%',
            render: function ( data, type, row, meta ) {
              if(data==null)
              {
                return '';
              }else{
                if(data>0)
                {
                  data = '<a href="" data-target="#modal-veradjunto" data-adjunto='+row.id+' data-codigo='+row.codigos+' data-toggle="modal" ><button class="btn btn-outline-dark btn-sm"><i class="fas fa-eye"></i> Ver</button></a>';
                  return data;
                }else{
                  return '';
                }
              }

            }
          },
            {data: 'condicion_envio',
                name: 'condicion_envio',
                render: function ( data, type, row, meta ) {
                    if(row.pendiente_anulacion==1){
                        return '<span class="badge badge-success">'+'{{\App\Models\Pedido::PENDIENTE_ANULACION }}'+'</span>';
                    }
                    return '<span class="badge badge-success" style="background-color: '+row.condicion_envio_color+'!important;">'+row.condicion_envio+'</span>';
                }
            },
          {
            data: 'action2',
            name: 'action2',
            orderable: false,
            searchable: false,
            sWidth:'20%',
            render: function ( data, type, row, meta ) {
                console.log(arguments)
              var urlpdf = '{{ route("pedidosPDF", ":id") }}';
              urlpdf = urlpdf.replace(':id', row.id);
              @can('operacion.atender')
                  if(!row.pendiente_anulacion) {
                      data = data + '<a href="" data-target="#modal-atender" data-atender=' + row.id + ' data-codigo='+row.codigos+' data-toggle="modal" ><button class="btn btn-success btn-sm">Atender</button></a>';
                  }
              @endcan
              @can('operacion.PDF')
                data = data+'<a href="'+urlpdf+'" class="btn btn-primary btn-sm" target="_blank"><i class="fa fa-file-pdf"></i> PDF</a>';
              @endcan
              data+=row.action;
              return data;
            }
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

    });
  </script>

  <script>
    $("#penvio_doc").change(mostrarValores1);

    function mostrarValores1() {
      $("#envio_doc").val($("#penvio_doc option:selected").text());
    }

    $("#pcondicion").change(mostrarValores2);

    function mostrarValores2() {
      $("#condicion").val($("#pcondicion option:selected").text());
    }
  </script>

  @if (session('info') == 'registrado' || session('info') == 'actualizado' || session('info') == 'eliminado')
    <script>
      Swal.fire(
        'Pedido {{ session('info') }} correctamente',
        '',
        'success'
      )
    </script>
  @endif


<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>

<script>
  /*window.onload = function () {
    $('#tablaPrincipal').DataTable().draw();
  }*/
</script>

  <script>
    /* Custom filtering function which will search data in column four between two values */
    $(document).ready(function () {

            $.fn.dataTable.ext.search.push(
                function (settings, data, dataIndex) {
                    var min = $('#min').datepicker("getDate");
                    var max = $('#max').datepicker("getDate");
                    // need to change str order before making  date obect since it uses a new Date("mm/dd/yyyy") format for short date.
                    var d = data[4].split("/");
                    var startDate = new Date(d[1]+ "/" +  d[0] +"/" + d[2]);

                    if (min == null && max == null) { return true; }
                    if (min == null && startDate <= max) { return true;}
                    if(max == null && startDate >= min) {return true;}
                    if (startDate <= max && startDate >= min) { return true; }
                    return false;
                }
            );


            $("#min").datepicker({ onSelect: function () { table.draw(); }, changeMonth: true, changeYear: true , dateFormat:"dd/mm/yy"});
            $("#max").datepicker({ onSelect: function () { table.draw(); }, changeMonth: true, changeYear: true, dateFormat:"dd/mm/yy" });
            var table = $('#tablaPrincipal').DataTable();

            // Event listener to the two range filtering inputs to redraw on input
            $('#min, #max').change(function () {
                table.draw();
            });
        });
  </script>

@stop
