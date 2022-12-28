@extends('adminlte::page')

@section('title', 'Operaciones | Pedidos atendidos')

@section('content_header')
    <h1>Lista de pedidos ATENDIDOS - OPERACIONES
        {{-- @can('pedidos.exportar')
        <div class="float-right btn-group dropleft">
          <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Exportar
          </button>
          <div class="dropdown-menu">
            <a href="{{ route('pedidosatendidosExcel') }}" class="dropdown-item"><img src="{{ asset('imagenes/icon-excel.png') }}"> EXCEL</a>
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
        @include('pedidos.modal.exportar', ['title' => 'Exportar pedidos atendidos', 'key' => '9'])
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
            <table cellspacing="5" cellpadding="5" class="d-none">
                <tbody>
                <tr>
                    <td>Minimum date:</td>
                    <td><input type="text" value={{ $dateMin }} id="min" name="min" class="form-control"></td>
                    <td></td>
                    <td>Maximum date:</td>
                    <td><input type="text" value={{ $dateMax }} id="max" name="max" class="form-control"></td>
                </tr>
                </tbody>
            </table>
            <br>
            <table id="tablaPrincipal" class="table table-striped" style="width:100%">
                <thead>
                <tr>
                    <th scope="col">Item</th>
                    <th scope="col">Item</th>
                    <th scope="col">Código</th>
                    <th scope="col">Razón social</th>
                    <th scope="col">Asesor</th>
                    <th scope="col">Fecha de registro</th>
                    <th scope="col">Fecha de atención</th>
                    <th scope="col">Destino</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Atendido por</th>
                    <th scope="col">Jefe</th>
                    <th scope="col">Estado de sobre</th>
                    <th scope="col">Acciones</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
            @include('pedidos.modal.sinenvioid')
            @include('pedidos.modal.envioid')
            @include('pedidos.modal.EditarAtencion')
            @include('pedidos.modal.DeleteAdjuntoid')
        </div>
    </div>

@stop

@section('css')
    {{-- <link rel="stylesheet" href="../css/admin_custom.css"> --}}
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

    <style>
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

            $('#modal-editar-atencion').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget)
                var idunico = button.data('atencion')
                var confirmo_descarga = button.data('adj')

                $(".textcode").html("PED" + idunico);
                $("#hiddenAtender").val(idunico);
                $('#conf_descarga').val(confirmo_descarga);

                if(confirmo_descarga == 1){
                    $('#sustento_adjunto').css({'display':'block'});
                }else{
                    $('#sustento_adjunto').css({'display':'none'});
                }

                //RecuperarAdjuntos(idunico);

                //recupera info de atencion
                $.ajax({
                    type: 'POST',
                    url: "{{ route('operaciones.datossubidaadj',':id') }}".replace(':id', idunico),
                    data: idunico,
                    success: function (data) {
                        console.log(data);
                        console.log(data.pedidos[0]['cant_compro']);

                        $('#cant_compro').val(data.pedidos[0]['cant_compro']);
                        $('#fecha_envio_doc').val(data.pedidos[0]['fecha_envio_doc']);

                    }
                }).done(function (data) {
                });

                //recupera imagenes adjuntas
                $.ajax({
                    url: "{{ route('operaciones.editatencion',':id') }}".replace(':id', idunico),
                    data: idunico,
                    method: 'POST',
                    success: function (data) {
                        console.log(data)
                        console.log("obtuve las imagenes atencion del pedido " + idunico)
                        $('#listado_adjuntos').html(data);
                        console.log(data);
                    }
                });

            });

            $('#modal-envio').on('show.bs.modal', function (event) {
                //cuando abre el form de anular pedido
                var button = $(event.relatedTarget)
                var idunico = button.data('envio')
                var idcodigo = button.data('codigo')
                $(".textcode").html(idcodigo);
                $("#hiddenEnvio").val(idunico);
            });

            $('#modal-delete-adjunto').on('show.bs.modal', function (event) {
                //cuando abre el form de anular pedido
                var button = $(event.relatedTarget)
                var img_pedidoid = button.data('imgid')
                var imgadjunto = button.data('imgadjunto')
                $(".textcode").html("PED" + img_pedidoid);
                $("#eliminar_pedido_id").val(img_pedidoid);
                $("#eliminar_pedido_id_imagen").val(imgadjunto);
            });

            $(document).on("submit", "#formulario_adjuntos_confirmar", function (evento) {
                evento.preventDefault();
                let idunicoconfirmar = $("#hiddenAtender").val();
                let fecha_envio_doc_confirmar = $("#fecha_envio_doc").val();
                let cant_compro_confirmar = $("#cant_compro").val();
                let cnf_adjunto = $("#conf_descarga").val();
                console.log(cnf_adjunto);




                var data = new FormData();
                data.append('hiddenAtender', idunicoconfirmar);
                data.append('fecha_envio_doc', fecha_envio_doc_confirmar);
                data.append('cant_compro', cant_compro_confirmar);

                if(cnf_adjunto == 1){

                    console.log("tiene adjuntos");

                    var sustento = $('#sustento_data').val();

                    if(sustento == ""){
                        Swal.fire(
                            'Error',
                            'Ingrese un sustento para continuar',
                            'warning'
                        )
                        return false;
                    }else{
                        data.append('sustento', sustento);
                    }
                }else{
                    data.append('sustento', "");
                }

                $('#confirmar_atender').attr('disabled', true);
                $('#confirmar_atender').text('Subiendo archivos...');
                console.log("returnado")
                //var data = new FormData(document.getElementById("formulario_adjuntos"));

                $.ajax({
                    type: 'POST',
                    url: "{{ route('operaciones.updateatenderid',':id') }}".replace(':id', idunicoconfirmar),
                    data: data,
                    processData: false,
                    contentType: false,
                    success: function (data) {
                        $('#confirmar_atender').prop("disabled", false);
                        $('#confirmar_atender').text('Confirmar');
                        $('#modal-editar-atencion').modal('hide');
                        $('#tablaPrincipal').DataTable().ajax.reload();

                        //RecuperarAdjuntos(idunicoconfirmar);
                    }
                }).done(function (data) {
                    //$('#modal-delete-adjunto').modal('hide');
                    //$('#listado_adjuntos').html(data);
                });

            });


            $(document).on("submit", "#formulario_adjuntos", function (evento) {
                evento.preventDefault();

                let idunico = $("#hiddenAtender").val();
                console.log(idunico);
                $('#cargar_adjunto').attr("disabled", true);
                //$(this).attr('disabled',true);
                //$(this).text('Subiendo archivos...');
                $('#cargar_adjunto').html('Subiendo archivos...');
                //e.preventDefault();
                var data = new FormData(document.getElementById("formulario_adjuntos"));


                $.ajax({
                    type: 'POST',
                    url: "{{ route('operaciones.updateatender',':id') }}".replace(':id', idunico),
                    data: data,
                    processData: false,
                    contentType: false,
                    success: function (data) {
                        $('#cargar_adjunto').prop("disabled", false);
                        $('#cargar_adjunto').text('Confirmar');

                        ///RecuperarAdjuntos(idunico);
                        $.ajax({
                            url: "{{ route('operaciones.editatencion',':id') }}".replace(':id', idunico),
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
                //return false;
                let pedidoidimagenes = $("#eliminar_pedido_id").val();
                var fddeleteadjunto = new FormData();
                fddeleteadjunto.append('eliminar_pedido_id', pedidoidimagenes);
                fddeleteadjunto.append('eliminar_pedido_id_imagen', $("#eliminar_pedido_id_imagen").val());
                console.log(fddeleteadjunto);

                //return false;
                $.ajax({
                    url: "{{ route('operaciones.eliminaradjunto') }}",
                    type: 'POST',
                    data: fddeleteadjunto,
                    processData: false,
                    contentType: false,
                    success: function (data) {
                        //$('#listado_adjuntos').html(data);
                        //console.log(data.html);
                        console.log("rest ajax")
                        $('.adjuntos[data-adjunto="' + data.html + '"]').remove();
                        $('#modal-delete-adjunto').modal('toggle');

                        $.ajax({
                            url: "{{ route('operaciones.editatencion',':id') }}".replace(':id', pedidoidimagenes),
                            data: pedidoidimagenes,
                            method: 'POST',
                            success: function (data) {
                                console.log(data)
                                console.log("obtuve las imagenes atencion del pedido " + pedidoidimagenes)
                                $('#listado_adjuntos').html(data);
                            }
                        });
                    }
                }).done(function (data) {
                    //$('#modal-delete-adjunto').modal('hide');
                    //$('#listado_adjuntos').html(data);
                });

            });

            $('#modal-sinenvio').on('show.bs.modal', function (event) {
                //cuando abre el form de anular pedido
                var button = $(event.relatedTarget)
                var idunico = button.data('sinenvio')
                var idcodigo = button.data('codigo')
                $(".textcode").html(idcodigo);
                $("#hiddenSinenvio").val(idunico);
            });

            $(document).on("submit", "#formularioenvio", function (evento) {
                evento.preventDefault();
                var fd = new FormData();
                fd.append('hiddenEnvio', $("#hiddenEnvio").val());

                $.ajax({
                    data: fd,
                    processData: false,
                    contentType: false,
                    type: 'POST',
                    url: "{{ route('operaciones.envioid') }}",
                    success: function (data) {
                        console.log(data);
                        $("#modal-envio .textcode").text('');
                        $("#modal-envio").modal("hide");
                        $('#tablaPrincipal').DataTable().ajax.reload();
                    }
                });
            });

            $(document).on("submit", "#formulariosinenvio", function (evento) {
                evento.preventDefault();
                var fd = new FormData();
                fd.append('hiddenSinenvio', $("#hiddenSinenvio").val());
                fd.append('condicion', $("#condicion").val());
                $.ajax({
                    data: fd,
                    processData: false,
                    contentType: false,
                    type: 'POST',
                    url: "{{ route('operaciones.sinenvioid') }}",
                    success: function (data) {
                        console.log(data);
                        $("#modal-sinenvio .textcode").text('');
                        $("#modal-sinenvio").modal("hide");
                        $('#tablaPrincipal').DataTable().ajax.reload();
                    }
                });
            });

            $('#modal-delete').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget)
                var idunico = button.data('delete')
                var idresponsable = button.data('responsable')
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
                $(".textcode").html(idunico);
                $("#motivo").val('');
                $("#responsable").val(idresponsable);
            });

            $('#tablaPrincipal').DataTable({
                processing: true,
                serverSide: true,
                searching: true,
                "order": [[0, "desc"]],
                ajax: {
                    url: "{{ route('operaciones.atendidostabla') }}",
                    data: function (d) {
                        //d.asesores = $("#asesores_pago").val();
                        d.min = $("#min").val();
                        d.max = $("#max").val();

                    },
                },
                createdRow: function (row, data, dataIndex) {
                    //console.log(row);
                },
                rowCallback: function (row, data, index) {
                },
                initComplete: function (settings, json) {

                },
                /*"drawCallback": function( settings ) {
                  AtenderAtencion();
                },*/
                columns: [
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
                        }
                    },
                    {
                        data: 'id2'
                        , name: 'id2', "visible": false
                    },
                    {data: 'codigos', name: 'codigos',},
                    {data: 'empresas', name: 'empresas',},
                    {data: 'users', name: 'users',},
                    //{data: 'fecha', name: 'fecha', },
                    {
                        data: 'fecha',
                        name: 'fecha',
                        render: $.fn.dataTable.render.moment('YYYY-MM-DD HH:mm:ss', 'DD/MM/YYYY HH:mm:ss'),
                        "visible": false,
                    },
                    {
                        data: 'fecha_envio_doc',
                        name: 'fecha_envio_doc',
                        render: $.fn.dataTable.render.moment('YYYY-MM-DD HH:mm:ss', 'DD/MM/YYYY HH:mm:ss')
                    },
                    {data: 'destino', name: 'destino', "visible": false},
                    {
                        data: 'condicion_code',
                        name: 'condicion_code',
                        render: function (data, type, row, meta) {
                            if (row.condicion_code == 1) {
                                return '<span class="badge badge-success">' + '{{\App\Models\Pedido::POR_ATENDER_OPE }}' + '</span>';
                            } else if (row.condicion_code == 2) {
                                return '<span class="badge badge-success">' + '{{\App\Models\Pedido::EN_ATENCION_OPE }}' + '</span>';
                            } else if (row.condicion_code == 3) {
                                return '<span class="badge badge-success">' + '{{\App\Models\Pedido::ATENDIDO_OPE }}' + '</span>';
                            } else if (row.condicion_code == 4) {
                                return '<span class="badge badge-success">' + '{{\App\Models\Pedido::ANULADO }}' + '</span>';
                            }
                        }
                    },
                    {data: 'atendido_por', name: 'atendido_por',},
                    {data: 'jefe', name: 'jefe',},
                    {
                        data: 'envio',
                        name: 'envio',
                        render: function (data, type, row, meta) {
                            if (row.envio == 1) {
                                return '<span class="badge badge-success">Enviado</span>' +
                                    '<span class="badge badge-warning">Por confirmar recepcion</span>';
                            } else if (row.envio == 2) {
                                return '<span class="badge badge-success">Enviado</span>' +
                                    '<span class="badge badge-info">Recibido</span>';
                            } else if (row.envio == 3) {
                                return '<span class="badge badge-dark">Sin envio</span>';
                            } else {
                                return '<span class="badge badge-danger">por enviar</span>';
                            }
                        }, "visible": false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        sWidth: '20%',
                        render: function (data, type, row, meta) {

                            var urlver = '{{ route("operaciones.showatender", ":id") }}';
                            urlver = urlver.replace(':id', row.id);

                            data = '<div><ul class="" aria-labelledby="dropdownMenuButton">';

                            data = data + '<a href="' + urlver + '" class="btn-sm dropdown-item" ><i class="fas fa-eye text-success"></i> Ver</a>';

                            var urledit = '{{ route("operaciones.editatender", ":id") }}';
                            urledit = urledit.replace(':id', row.id);


                            @can('operacion.editatender')
                                data = data + '<a href="" class="btn-sm dropdown-item" data-target="#modal-editar-atencion" data-adj=' + row.da_confirmar_descarga + ' data-atencion=' + row.id + ' data-toggle="modal" ><i class="fa fa-paperclip text-primary" aria-hidden="true"></i> Editar Adjuntos</a>';

                            @endcan

                            var urlpdf = '{{ route("pedidosPDF", ":id") }}';
                            urlpdf = urlpdf.replace(':id', row.id);
                            @can('operacion.PDF')
                                data = data + '<a href="' + urlpdf + '" class="btn-sm dropdown-item" target="_blank"><i class="fa fa-file-pdf text-warning"></i> PDF</a>';
                            @endcan

                                @can('operacion.enviar')
                            if (row.envio == '0') {
                                @if (Auth::user()->rol == "Jefe de operaciones" || Auth::user()->rol == "Administrador" || Auth::user()->rol == "Operario")
                                    data = data + '<a href="" class="btn-sm dropdown-item" data-target="#modal-envio" data-envio=' + row.id + ' data-codigo='+row.codigos+' data-toggle="modal" ><i class="fa fa-envelope text-success" aria-hidden="true"></i> Con sobre</a>';
                                    data = data + '<a href="" class="btn-sm dropdown-item" data-target="#modal-sinenvio" data-sinenvio=' + row.id + ' data-codigo='+row.codigos+' data-toggle="modal" ><i class="fa fa-times text-danger" aria-hidden="true"></i> Sin sobre</a>';
                                @endif

                            }
                            @endcan

                                data = data + '</ul></div>';

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

            $('#tablaPrincipal_filter label input').on('paste', function (e) {
                var pasteData = e.originalEvent.clipboardData.getData('text')
                localStorage.setItem("search_tabla", pasteData);
            });
            $(document).on("keypress", '#tablaPrincipal_filter label input', function () {
                localStorage.setItem("search_tabla", $(this).val());
                console.log("search_tabla es " + localStorage.getItem("search_tabla"));
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
