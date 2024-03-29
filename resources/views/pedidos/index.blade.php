@extends('adminlte::page')

@section('title', 'Pedidos - Bandeja de pedidos')

@section('content_header')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;400;600;700&family=Work+Sans:wght@300;400&display=swap');

        body {
            font-family: 'Work Sans', sans-serif;
        }

        h1, h2, h3, h4, h5 {
            font-family: 'Poppins', sans-serif;
            font-weight: bold;
        }
    </style>

    <h1>Lista de pedidos
        @can('pedidos.create')
            <a href="{{ route('pedidos.create') }}" class="btn btn-info"><i class="fas fa-plus-circle"></i> Agregar</a>
            {{-- <a href="" data-target="#modal-add-ruc" data-toggle="modal">(Agregar +)</a> --}}
        @endcan
        {{-- @can('pedidos.exportar')
        <div class="float-right btn-group dropleft">
          <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Exportar
          </button>
          <div class="dropdown-menu">
            <a href="{{ route('pedidosExcel') }}" class="dropdown-item"><img src="{{ asset('imagenes/icon-excel.png') }}"> EXCEL</a>
          </div>
        </div>
        @endcan --}}
        <div class="float-right btn-group dropleft">
            <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="false">
                Exportar
            </button>
            <div class="dropdown-menu">
                <a href="" data-target="#modal-exportar" data-toggle="modal" class="dropdown-item" target="blank_"><img
                        src="{{ asset('imagenes/icon-excel.png') }}"> Excel</a>
            </div>
        </div>
        @include('pedidos.modal.exportar', ['title' => 'Exportar Lista de pedidos', 'key' => '3'])
    </h1>
    {{--@if($superasesor > 0)--}}
    {{--
    <br>
    <div class="bg-4">
      <h1 class="t-stroke t-shadow-halftone2" style="text-align: center">
        asesores con privilegios superiores: {{ $superasesor }}
      </h1>
    </div>--}}
    {{--@endif--}}
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <!--
            <table cellspacing="5" cellpadding="5" class="table-responsive">
                <tbody>
                <tr>
                    <td>Fecha Minima:</td>
                    <td><input type="text" value={{ $dateMin }} id="min" name="min" class="form-control"></td>
                    <td></td>
                    <td>Fecha Máxima:</td>
                    <td><input type="text" value={{ $dateMax }} id="max" name="max" class="form-control"></td>
                </tr>
                </tbody>
            </table>
            <br>-->
            <table id="tablaPrincipal" class="table table-striped">{{-- display nowrap  --}}
                <thead>
                <tr>
                    <th scope="col">Item</th>
                    <th scope="col">Código</th>
                    <th scope="col">Cliente</th>
                    <th scope="col">Razón social</th>
                    <th scope="col">Cantidad</th>
                    <th scope="col">Asesor</th>
                    <th scope="col">RUC</th>
                    <th scope="col">F. Registro</th>
                    <th scope="col">F. Actualizacion</th>
                    <th scope="col">Total (S/)</th>
                    <!--<th scope="col">Est. pedido</th> -->

                    <th scope="col">Est. pago</th>
                    <th scope="col">Con. pago</th>
                    <!--   <th scope="col">Est. sobre</th> -->
                    <th scope="col">Est. Sobre</th>
                    <!--  <th scope="col">Cond. Pago</th> -->
                    <!-- <th scope="col">Estado</th>-->
                    <th scope="col">Diferencia</th>
                    {{--<th scope="col">Resp. Pedido</th>--}}
                    <th scope="col">Acciones</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
            @include('pedidos.modalid')
            @include('pedidos.modal.restaurarid')

        </div>
    </div>
@stop

@section('css')
    {{-- <link rel="stylesheet" href="../css/admin_custom.css"> --}}
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

    <style>

        .yellow {
            /*background-color: yellow !important;*/
            color: #fcd00e !important;
        }

        .textred {
            color: red !important;
        }

        .red {
            background-color: red !important;
        }

        .white {
            background-color: white !important;
        }

        .bg-4 {
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">
@stop

@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js"></script>


    {{--<script type="text/javascript" src="https://cdn.datatables.net/searchbuilder/1.0.1/js/dataTables.searchBuilder.min.js"></script>--}}
    {{--<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.8.4/moment.min.js"></script>--}}
    {{--<script type="text/javascript" src="//cdn.datatables.net/plug-ins/1.10.24/sorting/datetime-moment.js"></script>--}}

    <script src="https://momentjs.com/downloads/moment.js"></script>
    <script src="https://cdn.datatables.net/plug-ins/1.11.4/dataRender/datetime.js"></script>

    <!--  <script src="{{ asset('js/datatables.js') }}"></script>-->
    <script>
        //VALIDAR CAMPO CELULAR
        function maxLengthCheck(object)
        {
            if (object.value.length > object.maxLength)
                object.value = object.value.slice(0, object.maxLength)
        }
    </script>
    <script>

        $(document).ready(function () {
            //moment.updateLocale(moment.locale(), { invalidDate: "Invalid Date Example" });
            //$.fn.dataTable.moment('DD-MMM-Y HH:mm:ss');
            //$.fn.dataTable.moment('DD/MM/YYYY');

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            if (localStorage.getItem("search_tabla") === null) {
                //...

            } else {
                //si existe la variable  localstorage

            }

            $('#modal-delete').on('hidden.bs.modal', function (event) {
                $("#motivo").val('')
                $("#anulacion_password").val('')
                $("#attachments").val(null)
            })
            $('#modal-delete').on('show.bs.modal', function (event) {
                //cuando abre el form de anular pedido
                var button = $(event.relatedTarget)
                var idunico = button.data('delete')//id  basefria
                var idresponsable = button.data('responsable')//id  basefria
                var idcodigo = button.data('codigo')
                //console.log(idunico);
                $("#hiddenIDdelete").val(idunico);
                if (idunico < 10) {
                    idunico = 'PED000' + idunico;
                } else if (idunico < 100) {
                    idunico = 'PED00' + idunico;
                } else if (idunico < 1000) {
                    idunico = 'PED0' + idunico;
                } else {
                    idunico = 'PED' + idunico;
                }
                //solo completo datos
                //hiddenId
                //
                $

                $(".textcode").html(idcodigo);
                $("#motivo").val('');
                $("#responsable").val(idresponsable);

            });

            $('#modal-restaurar').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget)
                var idunico = button.data('restaurar')
                var idcodigo = button.data('codigo')
                console.log("unico " + idunico)
                $("#hiddenIDrestaurar").val(idunico);
                if (idunico < 10) {
                    idunico = 'PED000' + idunico;
                } else if (idunico < 100) {
                    idunico = 'PED00' + idunico;
                } else if (idunico < 1000) {
                    idunico = 'PED0' + idunico;
                } else {
                    idunico = 'PED' + idunico;
                }

                $(".textcode").html(idcodigo);

            });

            function openConfirmDownloadDocuments(action, idc, codigo) {
                $.confirm({
                    title: `
<h5>Detalle de atencion de pedido <b class="allow-copy">${codigo}</b></h5>
`,
                    columnClass: 'col-md-6 col-md-offset-4 col-sm-6 col-sm-offset-3 col-xs-10 col-xs-offset-1',
                    buttons: {
                        cancel: {
                            text: 'Cerrar',
                            btnClass: 'btn-outline-dark',
                            action: function () {
                                return true
                            }
                        },
                    },
                    draggable:false,
                    backgroundDismiss: function () {
                        return false; // modal wont close.
                    },
                    content: function () {
                        var self = this;
                        return $.ajax({
                            url: action,
                            dataType: 'json',
                            method: 'get'
                        }).done(function (response) {
                            var html = `<div class="list-group">`
                            // html += `<li class="list-group-item bg-dark">Codigo: ${codigo}</li>`
                            if (response.sustento) {
                                html += `<li class="list-group-item text-wrap">
<h6 class="alert alert-warning text-center font-weight-bold">Los archivos de este pedido fueron modificados</h6>
<b>Sustento del facturador:</b>
<textarea readonly class="form-control w-100" rows="6" style=" color: red; font-weight: bold; background: white; ">${response.sustento}</textarea>
</li>`
                            }
                            html += `<li class="list-group-item text-left"><h5 class="font-weight-bold">Adjuntos descargados</h5></li>`
                            html += response.data.map(function (item) {
                                return `<li class="list-group-item">
<a href="${item.link}" download class="d-flex justify-content-between"><span>Descargar </span> <span><i class="fa fa-file mx-2"></i>${item.adjunto} <i class="fa fa-download mx-2"></i></span></a>
</li>`
                            }).join('')

                            html += `</div>`
                            self.setContentAppend(html);
                            if(response.cliente){
                                self.setTitle(`
<div class="d-flex justify-content-between w-100 align-content-center">
<h5>Cliente: <b class="allow-copy">${response.cliente.nombre}</b></h5>
<h5 class="text-right">Telf: <b class="allow-copy">${response.cliente.celular}</b></h5>
</div>
<hr class="my-0">
<h5>Detalle de atencion de pedido <b class="allow-copy">${response.detalle_pedido.codigo}</b></h5>
`)
                            }
                        }).fail(function () {
                            self.setContent('Ocurrio un error.');
                        });
                    },
                    onContentReady: function () {
                        const self = this
                        self.$content.find('#copy_pedido_buttom').click(function () {
                            self.$content.find('#copy_pedido_text').select();
                            window.document.execCommand("copy");
                        })
                    },
                });
            }
            var tablaPrincipal = $('#tablaPrincipal').DataTable({
                processing: true,
                serverSide: true,
                searching: true,
                stateSave: true,
                order: [[0, "desc"]],
                ajax: "{{ route('pedidostabla') }}",
                createdRow: function (row, data, dataIndex) {
                    if (data["estado"] == "1") {
                        if (data.pendiente_anulacion == 1) {
                            $('td', row).css('background', 'red').css('font-weight', 'bold');
                        }
                    } else {
                        $(row).addClass('textred');
                    }
                },
                rowCallback: function (row, data, index) {
                    var pedidodiferencia = data.diferencia;

                    if (data.condicion_code == 4 || data.estado == 0) {
                        $('td:eq(11)', row).css('background', '#ff7400').css('color', '#ffffff').css('text-align', 'center').css('font-weight', 'bold');
                    } else {
                        if (pedidodiferencia == null) {
                            $('td:eq(11)', row).css('background', '#ca3a3a').css('color', '#ffffff').css('text-align', 'center').css('font-weight', 'bold');
                        } else {
                            if (pedidodiferencia > 3) {
                                $('td:eq(11)', row).css('background', '#ca3a3a').css('color', '#ffffff').css('text-align', 'center').css('font-weight', 'bold');
                            } else {
                                $('td:eq(11)', row).css('background', '#44c24b').css('text-align', 'center').css('font-weight', 'bold');
                            }
                        }
                    }

                    $('[data-jqconfirm]', row).click(function () {
                        $.confirm({
                            columnClass: 'large',
                            title: 'Editar direccion de envio',
                            content: function () {
                                var self = this;
                                return $.ajax({
                                    url: '{{route('pedidos.envios.get-direccion')}}?pedido_id=' + data.id,
                                    dataType: 'json',
                                    method: 'get'
                                })
                                    .done(function (response) {
                                    console.log(response);

                                    self.setContent(response.html);
                                    if (!response.success) {
                                        self.$$confirm.hide();
                                    }
                                    //self.setContent('Description: ' + response.description);
                                    //self.setContentAppend('<br>Version: ' + response.version);
                                    //self.setTitle(response.name);
                                })
                                    .fail(function (e) {
                                    console.error(e)
                                    self.setContent('Ocurrio un error');
                                });
                            },
                            buttons: {
                                confirm: {
                                    text: 'Actualizar',
                                    btnClass: 'btn-success',
                                    action: function () {
                                        var self = this;
                                        console.log(self.$content.find('form')[0])
                                        const form = self.$content.find('form')[0];
                                        const data = new FormData(form)

                                        /*if (form.rotulo.files.length > 0) {
                                            data.append('rotulo', form.rotulo.files[0])
                                        }*/
                                        if(data.get('celular').length!=9){
                                            $.alert({
                                                title: 'Alerta!',
                                                content: '¡El numero de celular debe tener 9 digitos!',
                                            });
                                            return false

                                        }

                                        self.showLoading(true)
                                        $.ajax({
                                            data: data,
                                            processData: false,
                                            contentType: false,
                                            type: 'POST',
                                            url: "{{route('pedidos.envios.update-direccion')}}",
                                        }).always(function () {
                                            self.close();
                                            $('#tablaPrincipal').DataTable().ajax.reload();
                                        });
                                        return false
                                    }
                                },
                                cancel: function () {

                                },
                            },
                            onContentReady:function (){

                                var self = this;
                                //console.log(self.$content.find('form')[0])
                                const form = self.$content.find('form')[0];
                                const data = new FormData(form)
                                console.log("aa")
                                console.log(form)
                                //this.buttons.ok.disable();
                                //$(select).selectpicker('refresh');
                                //form.distrito.addClass('selectpicker')
                                self.$content.find('select#distrito').selectpicker('refresh');
                                //console.log("a")

                                //$(self.$).selectpicker();
                                //console.log("aa");
                            }
                        });
                    })

                    $('[data-verforotos]', row).click(function () {
                        var data = $(this).data('verforotos')
                        $.dialog({
                            columnClass: 'xlarge',
                            title: 'Fotos confirmadas',
                            type: 'green',
                            content: function () {
                                return `<div class="row">
${data.foto1 ? `
<div class="col-md-4">
<div class="card">
<div class="card-header d-none"><h5>Foto de los sobres</h5></div>
<div class="card-body">
<img src="${data.foto1}" class="w-100">
</div>
</div>
</div>
` : ''}
${data.foto2 ? `
<div class="col-md-4">
<div class="card">
<div class="card-header d-none"><h5>Foto del domicilio</h5></div>
<div class="card-body">
<img src="${data.foto2}" class="w-100">
</div>
</div>
</div>
` : ''}
${data.foto3 ? `
<div class="col-md-4">
<div class="card">
<div class="card-header d-none"><h5>Foto de quien recibe</h5></div>
<div class="card-body">
<img src="${data.foto3}" class="w-100">
</div>
</div>
</div>
` : ''}
</div>`
                            }
                        })

                    })

                    $("[data-jqconfirmdetalle=jqConfirm]",row).on('click', function (e) {
                        openConfirmDownloadDocuments($(e.target).data('target'), $(e.target).data('idc'), $(e.target).data('codigo'))
                    })
                },
                initComplete: function (settings, json) {

                },
                columns: [
                    //ID
                    {
                        data: 'id',
                        name: 'id',
                        render: function (data, type, row, meta) {
                            if (row.id < 10) {
                                return 'PED000' + row.id;
                            } else if (row.id < 100) {
                                return 'PED00' + row.id;
                            } else if (row.id < 1000) {
                                return 'PED0' + row.id;
                            } else {
                                return 'PED' + row.id;
                            }
                        },
                        "visible": false,
                    },
                    // CODIGO
                    {data: 'codigos', name: 'codigos',},
                    {
                        data: 'celulares',
                        name: 'celulares',
                        render: function (data, type, row, meta) {
                            if (row.icelulares != null) {
                                return row.celulares + '-' + row.icelulares + ' - ' + row.nombres;
                            } else {
                                return row.celulares + ' - ' + row.nombres;
                            }

                        },
                        //searchable: true
                    },
                    //EMPRESAS
                    {data: 'empresas', name: 'empresas',},
                    {data: 'cantidad', name: 'cantidad', render: $.fn.dataTable.render.number(',', '.', 2, ''),},
                    //USUARIOS
                    {data: 'users', name: 'users',},
                    {data: 'ruc', name: 'ruc',},

                    //FECHA
                    {
                        data: 'fecha',
                        name: 'fecha',
                        //render: $.fn.dataTable.render.moment( 'DD-MMM-YYYY HH:mm:ss' )
                    },
                    {
                        data: 'fecha_up',
                        name: 'fecha_up',
                        "visible": false,
                        //render: $.fn.dataTable.render.moment( 'DD-MMM-YYYY HH:mm:ss' )
                    },
                    {
                        data: 'total',
                        name: 'total',
                        render: $.fn.dataTable.render.number(',', '.', 2, '')
                    },
                        {{--
                            {data: 'condicion_code',
                                name: 'condicion_code',
                                render: function ( data, type, row, meta ) {
                                    if(row.pendiente_anulacion){
                                        return '{{\App\Models\Pedido::PENDIENTE_ANULACION}}';
                        }
                        if(row.condicion_code==1){
                            return '{{\App\Models\Pedido::POR_ATENDER }}';
                        }else if(row.condicion_code==2){
                            return '{{\App\Models\Pedido::EN_PROCESO_ATENCION }}';
                        }else if(row.condicion_code==3){
                            return '{{\App\Models\Pedido::ATENDIDO }}';
                        }else if(row.condicion_code==4||row.estado==0){
                            return '{{\App\Models\Pedido::ANULADO }}';
                        }
                    }
                },
                        --}}
                    {
                        data: 'condicion_pa',
                        name: 'condicion_pa',
                        render: function (data, type, row, meta) {

                            if (row.condiciones == 'ANULADO' || row.condicion_code == 4 || row.estado == 0) {
                                return 'ANULADO';
                            } else {
                                if (row.condicion_pa == null) {
                                    return 'SIN PAGO REGISTRADO';
                                } else {
                                    if (row.condicion_pa == '0') {
                                        return '<p>SIN PAGO REGISTRADO</p>'
                                    }
                                    if (row.condicion_pa == '1') {
                                        return '<p>ADELANTO</p>'
                                    }
                                    if (row.condicion_pa == '2') {
                                        return '<p>PAGO</p>'
                                    }
                                    if (row.condicion_pa == '3') {
                                        return '<p>ABONADO</p>'
                                    }
                                    //return data;
                                }
                            }

                        }
                    },//estado de pago
                    {
                        data: 'condiciones_aprobado',
                        name: 'condiciones_aprobado',
                        render: function (data, type, row, meta) {
                            if (row.condicion_code == 4 || row.estado == 0) {
                                return 'ANULADO';
                            }
                            if (data != null) {
                                return data;
                            } else {
                                return 'SIN REVISAR';
                            }

                        }
                    },
                    /*
                    {
                      //estado del sobre
                      data: 'envio',
                      name: 'envio',
                      render: function ( data, type, row, meta ) {
                        if(row.envio==null){
                          return '';
                        }else{
                          {
                            if(row.envio=='1'){
                              return '<span class="badge badge-success">Enviado</span><br>'+
                                      '<span class="badge badge-warning">Por confirmar recepcion</span>';
                            }else if(row.envio=='2'){
                              return '<span class="badge badge-success">Enviado</span><br>'+
                                      '<span class="badge badge-info">Recibido</span>';
                            }else{
                              return '<span class="badge badge-danger">Pendiente</span>';
                            }
                          }


                        }
                      }
                    },  */
                    //{data: 'responsable', name: 'responsable', },//estado de envio

                    //{data: 'condicion_pa', name: 'condicion_pa', },//ss
                    {
                        data: 'condicion_envio',
                        name: 'condicion_envio',
                    },//

                    /*
                    {
                      data: 'estado',
                      name: 'estado',
                      render: function ( data, type, row, meta ) {
                          if(row.estado==1){
                            return '<span class="badge badge-success">Activo</span>';
                          }else{
                            return '<span class="badge badge-danger">Anulado</span>';
                          }
                        }
                    },

                    */
                    {
                        data: 'diferencia',
                        name: 'diferencia',
                        render: function (data, type, row, meta) {
                            if (row.condicion_code == 4 || row.estado == 0) {
                                return '0';
                            }
                            if (row.diferencia == null) {
                                return 'NO REGISTRA PAGO';
                            } else {
                                if (row.diferencia > 0) {
                                    return row.diferencia;
                                } else {
                                    return row.diferencia;
                                }
                            }
                        }
                    },
                    //{data: 'responsable', name: 'responsable', },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        sWidth: '20%',
                    },
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


            $('#tablaPrincipal_filter label input').on('paste', function (e) {
                var pasteData = e.originalEvent.clipboardData.getData('text')
                localStorage.setItem("search_tabla", pasteData);
            });
            $(document).on("keypress", '#tablaPrincipal_filter label input', function () {
                localStorage.setItem("search_tabla", $(this).val());
                console.log("search_tabla es " + localStorage.getItem("search_tabla"));
            });

            $(document).on("blur", '#tablaPrincipal_filter label input', function () {
                localStorage.setItem("search_tabla", $(this).val());
                console.log("search_tabla es " + localStorage.getItem("search_tabla"));

            });

            $('#tablaPrincipal_filter label input').on('paste', function (e) {
                var pasteData = e.originalEvent.clipboardData.getData('text')
                localStorage.setItem("search_tabla", pasteData);
            });


            //$('#myInput').val( ... ).change();


            /*$.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
              console.log("data" +data);
            });*/

            /* $(document).on("change","search.dt",function(){
               console.log("aaaaa")
             });
         */

            /*$("").on( 'search.dt', function () {
            $('#filterInfo').html( 'Currently applied global search: '+table.search() );
        } );*/

            $(document).on("submit", "#formdelete", function (evento) {
                evento.preventDefault();
                console.log("validar delete");
                var motivo = $("#motivo").val();
                var responsable = $("#responsable").val();
                var anulacion_password = $("#anulacion_password").val();

                if (motivo.length < 1) {
                    Swal.fire(
                        'Error',
                        'Ingrese el motivo para anular el pedido',
                        'warning'
                    )
                } else if (responsable == '') {
                    Swal.fire(
                        'Error',
                        'Ingrese el responsable de la anulación',
                        'warning'
                    )
                } else if (!anulacion_password) {
                    Swal.fire(
                        'Error',
                        'Ingrese la contraseña para autorizar la anulación',
                        'warning'
                    )
                } else {
                    //this.submit();
                    clickformdelete();
                }

                /*var oForm = $(this);
                var formId = oForm.attr("id");
                var firstValue = oForm.find("input").first().val();
                alert("Form '" + formId + " is being submitted, value of first input is: " + firstValue);
                // Do stuff
                return false;*/
            })

            $(document).on("submit", "#formrestaurar", function (evento) {
                evento.preventDefault();
                clickformrestaurar();
            });

        });
    </script>

    <script>
        function resetearcamposdelete() {
            $('#motivo').val("");
            $('#responsable').val("");
        }

        function clickformdelete() {
            console.log("action delete action")
            var formData = new FormData();//$("#formdelete").serialize();
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
                url: "{{ route('pedidodeleteRequest.post') }}",
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
                        'No autorizado para poder anular el pedido, ingrese una contraseña correcta',
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
        }

        function clickformrestaurar() {
            var formData = $("#formrestaurar").serialize();
            $.ajax({
                type: 'POST',
                url: "{{ route('pedidorestaurarRequest.post') }}",
                data: formData,
            }).done(function (data) {
                $("#modal-restaurar").modal("hide");
                //resetearcamposdelete();
                $('#tablaPrincipal').DataTable().ajax.reload();
            });
        }

        /*function clickformdelete(){
          $("#modal-delete").modal("show");
        }*/

    </script>

    @if (session('info') == 'registrado' || session('info') == 'actualizado' || session('info') == 'eliminado' || session('info') == 'restaurado')
        <script>
            Swal.fire(
                'Pedido {{ session('info') }} correctamente',
                '',
                'success'
            )
        </script>
    @endif

    <script>
        //VALIDAR ANTES DE ENVIAR
        /*document.addEventListener("DOMContentLoaded", function() {
          document.getElementById("formdelete").addEventListener('submit', validarFormularioDelete);
        });*/

    </script>

    <script>
        //VALIDAR CAMPO RUC
        function maxLengthCheck(object) {
            if (object.value.length > object.maxLength)
                object.value = object.value.slice(0, object.maxLength)
        }

        //VALIDAR ANTES DE ENVIAR 2
        document.addEventListener("DOMContentLoaded", function () {
            var form = document.getElementById("formulario2")
            if (form) {
                form.addEventListener('submit', validarFormulario2);
            }
        });

        function validarFormulario2(evento) {
            evento.preventDefault();
            var agregarruc = document.getElementById('agregarruc').value;

            if (agregarruc == '') {
                Swal.fire(
                    'Error',
                    'Debe ingresar el número de RUC',
                    'warning'
                )
            } else if (agregarruc.length < 11) {
                Swal.fire(
                    'Error',
                    'El número de RUC debe tener 11 dígitos',
                    'warning'
                )
            } else {
                this.submit();
            }
        }
    </script>

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
                    var d = data[5].split("/");
                    var startDate = new Date(d[1] + "/" + d[0] + "/" + d[2]);

                    if (min == null && max == null) {
                        return true;
                    }
                    if (min == null && startDate <= max) {
                        return true;
                    }
                    if (max == null && startDate >= min) {
                        return true;
                    }
                    if (startDate <= max && startDate >= min) {
                        return true;
                    }
                    return false;
                }
            );


            $("#min").datepicker({
                onSelect: function () {
                    table.draw();
                }, changeMonth: true, changeYear: true, dateFormat: "dd/mm/yy"
            });
            $("#max").datepicker({
                onSelect: function () {
                    table.draw();
                }, changeMonth: true, changeYear: true, dateFormat: "dd/mm/yy"
            });
            var table = $('#tablaPrincipal').DataTable();

            // Event listener to the two range filtering inputs to redraw on input
            $('#min, #max').change(function () {
                table.draw();
            });
        });
    </script>
@stop
