@extends('adminlte::page')

@section('title', 'Envios | Sobres sin enviar')

@section('content_header')
    <h1>Lista de sobres sin enviar - ENVIOS

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
        @include('pedidos.modal.exportar', ['title' => 'Exportar Estado Sobres', 'key' => '11'])
        {{-- @endcan --}}
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

    <div class="row">
        @foreach ($_pedidos as $pedido)
            <div class="col-2">
                <div class="card card-warning">
                    <div class="card-header p-8">
                        <h5 class="mb-0 font-16 text-center">ASESOR {{ $pedido->identificador }}</h5>
                    </div>
                    <div class="card-body pt-8 pb-8">
                        <h4 class="text-center mb-0">
                            <b>{{ $pedido->total }}</b>
                        </h4>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <style>
        .activo{
            background-color: #e74c3c !important;
            color: white !important;
            border: 0 !important;
        }
    </style>

    <div class="card">
        <div class="card-body">

            <table class="table-responsive" style="border-collapse: collapse;">
                <tbody class="table-responsive">
                <tr class="table-responsive">
                    <td class="table-responsive col-4 mx-auto">
                        <input type="text" value="" id="buscador_global" name="buscador_global" class="form-control" autocomplete="off">
                    </td>
                </tr>
                </tbody>
            </table>

            <ul class="nav nav-pills nav-justified nav-tabs mb-24 mt-24" id="myTab" role="tablist">
                <li class="nav-item text-center">
                    <a class="condicion-tabla nav-link activo active font-weight-bold"
                       id="received-tab"
                       data-toggle="tab"
                       data-url="11"
                       data-tipo="recepcion"
                       data-consulta="tablaPrincipal"
                       href="#received"
                       role="tab"
                       aria-controls="received"
                       aria-selected="true">
                        <i class="fa fa-inbox" aria-hidden="true"></i> RECEPCIÓN
                        <sup><span class="badge badge-light count_estadosobres_received">{{$count_recepcionados}}</span></sup>
                    </a>
                </li>
                <li class="nav-item text-center">
                    <a class="condicion-tabla nav-link font-weight-bold"
                       id="delivered-tab"
                       data-toggle="tab"
                       data-url="10,13,14"
                       data-tipo="entregados"
                       data-consulta="tablaEntregados"
                       href="#delivered"
                       role="tab"
                       aria-controls="delivered"
                       aria-selected="false">
                        <i class="fa fa-motorcycle" aria-hidden="true"></i> ENTREGADOS
                        <sup><span class="badge badge-light count_estadosobres_delivered">{{$count_entregados}}</span></sup>
                    </a>
                </li>

                <li class="nav-item text-center">
                    <a class="condicion-tabla nav-link font-weight-bold"
                       id="annulled-tab"
                       data-toggle="tab"
                       data-url="AN"
                       data-tipo="anulados"
                       data-consulta="tablaAnulados"
                       href="#annulled"
                       role="tab"
                       aria-controls="annulled"
                       aria-selected="false">
                        <i class="fa fa-motorcycle" aria-hidden="true"></i> ANULADOS
                        <sup><span class="badge badge-light count_estadosobres_annulled">{{$count_anulados}}</span></sup>
                    </a>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="received" role="tabpanel" aria-labelledby="received-tab">
                    <table id="tablaRecepcionados" class="table table-striped w-100" >
                        <thead>
                        <tr>
                            <th scope="col">Item</th>
                            <th scope="col">Código</th>
                            <th scope="col">Asesor</th>
                            <th scope="col">Razón social</th>
                            <th scope="col">Dias</th>
                            <th scope="col">Fecha de registro</th>
                            <th scope="col">Fecha de envio</th>
                            <th scope="col">Fecha de entrega</th>
                            <th scope="col">Destino</th>
                            <th scope="col">Dirección de envío</th>
                            <th scope="col">Estado de envio</th>
                            <th scope="col">Estado de sobre</th>
                            <th scope="col">Observacion Devolucion</th>
                            <th scope="col">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="delivered" role="tabpanel" aria-labelledby="delivered-tab">
                    <table id="tablaEntregados" class="table table-striped w-100" >
                        <thead>
                        <tr>
                            <th scope="col">Item</th>
                            <th scope="col">Asesor</th>
                            <th scope="col">Cliente</th>
                            <th scope="col">Código</th>
                            <th scope="col">Razón social</th>
                            <th scope="col">Fecha de entrega</th>
                            <th scope="col">Foto del sobre</th>
                            <th scope="col">Foto del domicilio</th>
                            <th scope="col">Foto de quien recibe</th>
                            <th scope="col">Estado de envio</th>
                            <th scope="col">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="annulled" role="tabpanel" aria-labelledby="annulled-tab">
                    <table id="tablaAnulados" class="table table-striped w-100">
                        <thead>
                        <tr>
                            <th scope="col">Item</th>
                            <th scope="col">Código</th>
                            <th scope="col">Asesor</th>
                            <th scope="col">Razón social</th>
                            <th scope="col">Dias</th>
                            <th scope="col">Fecha de registro</th>
                            <th scope="col">Fecha de envio</th>
                            <th scope="col">Fecha de entrega</th>
                            <th scope="col">Destino</th>
                            <th scope="col">Dirección de envío</th>
                            <th scope="col">Estado de envio</th>
                            <th scope="col">Estado de sobre</th>
                            <th scope="col">Observacion Devolucion</th>
                            <th scope="col">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>



            {{--@include('sobres.modal.direccionid')--}}

            @include('sobres.modal.historialLima')
            @include('sobres.modal.historialProvincia')

        </div>
    </div>

@stop

@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.0.943/pdf_viewer.css">

    <style>
        img:hover {
            transform: scale(1.2)
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
    <style>
        /*#canvas_container {
                width: 200px !important;
                height: 400px !important;
                overflow: auto;
            }*/
        /* #canvas_container {
             background: #333;
             text-align: center;
             border: solid 3px;
         }*/

        #pdf_renderer {
            position: relative;
        }

        #canvas_container {
            position: relative;
        }

        .modal-lg {
            max-width: 80%;
        }
    </style>
@stop

@section('js')

    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.5.0/js/dataTables.select.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.0.943/pdf.min.js"></script>

    <script src="https://gyrocode.github.io/jquery-datatables-checkboxes/1.2.12/js/dataTables.checkboxes.min.js"></script>



    <script>

        $('.condicion-tabla').on('click', function (){
            var tabla_load = $(this).data('consulta');
            $('.condicion-tabla').removeClass("activo");
            $(this).addClass("activo");
            //$('#' + tabla_load).DataTable.init();
            //var url = $(this).data("url");
            //$('#tablaPrincipal').DataTable().ajax.reload();

            if ( ! $.fn.DataTable.isDataTable( '#' + tabla_load ) ) {
                $('#' + tabla_load).dataTable();
            }
        });



        var myState = {
            pdf: null,
            currentPage: 1,
            zoom: 1
        }

        var currPage = 0;

        var tabla_pedidos = null;

    </script>
    <script>
        $(document).ready(function () {

            $(document).on("click", "#go_previous", function (e) {
                e.preventDefault();
                if (myState.pdf == null || myState.currentPage == 1) {
                    console.log("atras")
                    return false;
                }
                myState.currentPage -= 1;
                $("#current_page").val(myState.currentPage);
                render();
            });

            $(document).on("click", "#go_next", function (e) {
                e.preventDefault();
                console.log("numpages " + myState.pdf._pdfInfo.numPages);
                console.log("currentpage " + myState.currentPage)
                if (myState.pdf == null || myState.currentPage == myState.pdf._pdfInfo.numPages) {
                    console.log("next")
                    return false;
                }


                myState.currentPage += 1;
                $("#current_page").val(myState.currentPage);
                if (myState.currentPage == myState.pdf._pdfInfo.numPages) {
                    $("#go_next").addClass("d-none");
                }
                render();
            });

            $(document).on("keypress", "#current_page", function (e) {
                if (myState.pdf == null) return;

                // Get key code
                var code = (e.keyCode ? e.keyCode : e.which);

                // If key code matches that of the Enter key
                if (code == 13) {
                    var desiredPage = document.getElementById('current_page').valueAsNumber;

                    if (desiredPage >= 1 && desiredPage <= myState.pdf._pdfInfo.numPages) {
                        myState.currentPage = desiredPage;
                        document.getElementById("current_page").value = desiredPage;
                        render();
                    }
                }
            });

            $(document).on("click", "#zoom_in", function (e) {
                if (myState.pdf == null) return;
                myState.zoom += 0.5;
                render();
            });

            $(document).on("click", "#zoom_out", function (e) {
                if (myState.pdf == null) return;
                myState.zoom -= 0.5;
                render();
            });



        });
    </script>

    <script>

    </script>

    <script>
        let tablaRecepcionados=null;
        let tablaEntregados=null;
        let tablaAnulados=null;
        $(document).ready(function () {



            $(document).on("click", "#change_imagen", function () {
                var fd2 = new FormData();
                //agregados el id pago
                let files = $('input[name="pimagen')
                var cambiaitem = $("#cambiaitem").val();
                var cambiapedido = $("#cambiapedido").val();

                fd2.append("item", cambiaitem)
                fd2.append("pedido", cambiapedido)
                for (let i = 0; i < files.length; i++) {
                    fd2.append('adjunto', $('input[type=file][name="pimagen"]')[0].files[0]);
                }

                $.ajax({
                    data: fd2,
                    processData: false,
                    contentType: false,
                    type: 'POST',
                    url: "{{ route('envios.changeImg') }}",
                    success: function (data) {
                        console.log(data);
                        if (data.html == '0') {
                        } else {
                            $("#modal-cambiar-imagen").modal("hide");
                            var urlimg = "{{asset('imagenes/logo_facturas.png')}}";
                            urlimg = urlimg.replace('imagenes/', 'storage/entregas/');
                            urlimg = urlimg.replace('logo_facturas.png', data.html);
                            urlimg = urlimg.replace(' ', '%20');
                            console.log(urlimg);
                            $("#imagen_" + cambiapedido + '-' + cambiaitem).attr("src", urlimg);
                        }
                    }
                });

            });

            $(document).on("change", "#rotulo", function (event) {
                $(".drop-rotulo").removeClass("d-none");
                console.log("cambe rotulo")
                var file = event.target.files[0];
                console.log(file);
                var reader = new FileReader();
                reader.onload = (event) => {

                    pdfjsLib.getDocument(event.target.result).then((pdf) => {
                        $("#my_pdf_viewer").removeClass("d-none");
                        //cargar frame
                        myState.pdf = pdf;
                        render();
                        thePDF = pdf;
                        numPages = pdf.numPages;
                        myState.currentPage = 1;
                        $("#current_page").val(myState.currentPage)
                        pdf.getPage(1).then(handlePages);

                        if (myState.currentPage == myState.pdf._pdfInfo.numPages) {

                            $("#go_next").addClass("d-none");
                            $("#go_previous").addClass("d-none");
                            $("#current_page").addClass("d-none");
                        } else {
                            $("#go_next").removeClass("d-none");
                            $("#go_previous").removeClass("d-none");
                            $("#current_page").removeClass("d-none");
                        }


                    });

                };
                reader.readAsDataURL(file);

            });

            window.render = function () {
                myState.pdf.getPage(myState.currentPage).then((page) => {
                    var canvas = document.getElementById("pdf_renderer");

                    var ctx = canvas.getContext('2d');
                    var viewport = page.getViewport(1);
                    canvas.width = viewport.width;//viewport.width;
                    canvas.height = viewport.height;//viewport.height;

                    //canvas.width  = 100%;
                    //canvas.height = 400;
                    canvas.style.width = '100%';
                    canvas.style.height = '100%';

                    page.render({
                        canvasContext: ctx,
                        viewport: viewport
                    });

                });
            }

            window.handlePages = function (page) {
                var viewport = page.getViewport(1);
                //We'll create a canvas for each page to draw it on
                var canvas = document.createElement("canvas");
                canvas.style.display = "block";
                var context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                //Draw it on the canvas
                page.render({canvasContext: context, viewport: viewport});

                //Add it to the web page
                document.body.appendChild(canvas);

                //Move to next page
                currPage++;
                if (thePDF !== null && currPage <= numPages) {
                    thePDF.getPage(currPage).then(handlePages);
                }
            }

            $('#celular').on('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            $("#direccion", '#referencia', '#observacion').bind('keypress', function (event) {
                var regex = new RegExp("^[a-zA-Z0-9 ]+$");
                var key = String.fromCharCode(!event.charCode ? event.which : event.charCode);
                if (!regex.test(key)) {
                    event.preventDefault();
                    return false;
                }
            });

            /*$("#tracking").bind('keypress', function(event) {
              var regex = new RegExp("^[0-9]{2}+[0-1]{2}$");
              var key = String.fromCharCode(!event.charCode ? event.which : event.charCode);
              if (!regex.test(key)) {
                event.preventDefault();
                return false;
              }
            });*/

            $('input.number').keyup(function (event) {
                console.log("number")

                if (event.which >= 37 && event.which <= 40) {
                    event.preventDefault();
                }

                $(this).val(function (index, value) {
                    return value
                        .replace(/\D/g, "")
                        .replace(/([0-9])([0-9]{2})$/, '$1.$2')
                        .replace(/\B(?=(\d{3})+(?!\d)\.?)/g, ",");
                });
            });

            $(".provincia").addClass("d-none");
            $(".lima").addClass("d-none");

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $("#buscador_global").on('change keyup', function(){
                tablaRecepcionados.search( this.value ).draw();
                tablaEntregados.search( this.value ).draw();
                tablaAnulados.search( this.value ).draw();

                var info_tablaRecepcionados = tablaRecepcionados.page.info().recordsDisplay
                console.log("en recepcionados "+info_tablaRecepcionados)

                var info_tablaEntregados = tablaEntregados.page.info().recordsDisplay
                console.log("en entregados "+info_tablaEntregados)

                var info_tablaAnulados = tablaAnulados.page.info().recordsDisplay
                console.log("en anulados "+info_tablaAnulados)

                $('.count_estadosobres_received').html(info_tablaRecepcionados)
                $('.count_estadosobres_delivered').html(info_tablaEntregados)
                $('.count_estadosobres_anulled').html(info_tablaAnulados)
                if(info_tablaRecepcionados>0){

                }
            })

            //boton buscador general
            /*$(document).on('change','#buscador_global',function(e){

            });*/

            /*$('#myInput').on( 'keyup', function () {
                table.search( this.value ).draw();
            } );*/

            /********************
             * TABLA SOBRES RECIBIDOS
             */
            tablaRecepcionados=$('#tablaRecepcionados').DataTable({
                processing: true,
                stateSave: false,
                serverSide: true,
                searching: false,
                "bFilter": false,
                "order": [[0, "desc"]],
                ajax: {
                    url: "{{ route('envios.estadosobrestabla') }}",
                    data: function (d) {
                        d.opcion = 'recepcionado';
                    },
                },
                createdRow: function (row, data, dataIndex) {
                },
                rowCallback: function (row, data, index) {
                    console.log(data);
                    if (data.devuelto != null) {
                        $('td', row).css('color', '#cf0a0a');
                    }
                },
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
                        }, "visible": false
                    },
                    {data: 'codigo', name: 'codigo',},
                    {data: 'users', name: 'users',},

                    {data: 'empresas', name: 'empresas',},
                    {data: 'dias', name: 'dias',},

                    {data: 'fecha_envio_doc', name: 'fecha_envio_doc', "visible": false},
                    {data: 'fecha_envio_doc_fis', name: 'fecha_envio_doc_fis',},
                    {data: 'fecha_recepcion', name: 'fecha_recepcion', "visible": false},
                    {data: 'destino', name: 'destino', "visible": false},
                    {
                        data: 'direccion',
                        name: 'direccion',
                        "visible": false,
                        render: function (data, type, row, meta) {
                            datas = '';
                            if (data != null) {
                                return data;
                            } else {
                                return '<span class="badge badge-danger">REGISTRE DIRECCION</span>';
                            }
                            //return 'REGISTRE DIRECCION';
                        },
                    },
                    {
                        data: 'condicion_envio',
                        name: 'condicion_envio',
                        render: function (data, type, row, meta) {
                            var badge_estado = '';
                            if (row.estado_sobre == 1) {
                                badge_estado += '<span class="badge badge-dark p-8" style="color: #fff; background-color: #347cc4; font-weight: 600; margin-bottom: -2px;border-radius: 4px 4px 0px 0px; font-size:8px;  padding:6px;">Direccion agregada</span>';
                            }
                            badge_estado += '<span class="badge badge-success" style="background-color:' + row.condicion_envio_color + ' !important;" >' + data + '</span>';
                            return badge_estado;
                        }
                    },
                    {
                        data: 'envio',
                        name: 'envio',
                        render: function (data, type, row, meta) {
                            if (row.envio == '1') {
                                return '<span class="badge badge-danger">Por confirmar recepcion</span>';
                            } else {
                                return '<span class="badge badge-info">Recibido</span>';
                            }
                        },
                        "visible": false
                    },
                    {
                        data: 'observacion_devuelto',
                        name: 'observacion_devuelto',
                        render: function (data, type, row, meta) {
                            if (data != null) {
                                return data;
                            } else {
                                return ''
                            }
                        },
                        "visible": true
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        sWidth: '20%',
                        "visible": false,
                        render: function (data, type, row, meta) {
                            datass = '';


                            return datass;
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

            /********************
             * TABLA SOBRES ENTREGADOS
             */

            tablaEntregados=$('#tablaEntregados').DataTable({
                processing: true,
                autoload: false,
                stateSave: false,
                serverSide: true,
                searching: false,
                "bFilter": false,
                "order": [[5, "desc"]],
                ajax: {
                    url: "{{ route('envios.estadosobrestabla') }}",
                    data: function (d) {
                        d.opcion = 'entregado';
                    },
                },
                createdRow: function (row, data, dataIndex) {
                },
                rowCallback: function (row, data, index) {

                    if (data.destino2 == 'PROVINCIA') {
                        $('td', row).css('color', 'red')
                    } else if (data.destino2 == 'LIMA') {
                        if (data.distribucion != null) {
                            if (data.distribucion == 'NORTE') {
                                //$('td', row).css('color','blue')
                            } else if (data.distribucion == 'CENTRO') {
                                //$('td', row).css('color','yellow')
                            } else if (data.distribucion == 'SUR') {
                                //$('td', row).css('color','green')
                            }
                        } else {
                        }
                    }

                    $('[data-jqconfirm]', row).click(function () {
                        $.confirm({
                            type: 'red',
                            title: '¡Revertir Envio!',
                            content: 'Confirme si desea revertir el envio <b>'+data.codigos+'</b>',
                            buttons: {
                                ok:{
                                    text:'Si, confirmar',
                                    btnClass:'btn-red',
                                    action:function (){
                                        const self=this;
                                        self.showLoading(true)
                                        $.ajax({
                                            data: {
                                                envio_id:data.id,
                                                pedido:data.codigos
                                            },
                                            type: 'POST',
                                            url: "{{ route('operaciones.revertirhaciaatendido') }}",
                                        }).always(function (){
                                            self.close()
                                            self.hideLoading(true)
                                            $('#tablaPrincipal').DataTable().ajax.reload();
                                        });
                                    }
                                },
                                cancel:{
                                    text:'No'
                                }
                            }
                        })
                    });
                },
                columns: [
                    {
                        data: 'correlativo',
                        name: 'correlativo'

                    },
                    {
                        data: 'identificador',
                        name: 'identificador',
                    },
                    {
                        data: 'celular',
                        name: 'celular',
                        render: function (data, type, row, meta) {
                            return row.celular + ' - ' + row.nombre
                        },
                    },
                    {
                        data: 'codigos',
                        name: 'codigos',
                        render: function (data, type, row, meta) {
                            if (data == null) {
                                return 'SIN PEDIDOS';
                            } else {
                                var returndata = '';
                                var jsonArray = data.split(",");
                                $.each(jsonArray, function (i, item) {
                                    returndata += item + '<br>';
                                });
                                return returndata;
                            }
                        }
                    },
                    {
                        data: 'producto',
                        name: 'producto',
                        render: function (data, type, row, meta) {
                            if (data == null) {
                                return 'SIN RUCS';
                            } else {
                                var numm = 0;
                                var returndata = '';
                                var jsonArray = data.split(",");
                                $.each(jsonArray, function (i, item) {
                                    numm++;
                                    returndata += numm + ": " + item + '<br>';

                                });
                                return returndata;
                            }
                        }
                    },
                    {
                        data: 'fechaentrega',
                        name: 'fechaentrega',
                        //render: $.fn.dataTable.render.moment('DD/MM/YYYY')
                    },
                    {
                        data: 'foto1',
                        name: 'foto1',
                    },
                    {
                        data: 'foto2',
                        name: 'foto2',
                    },
                    {
                        data: 'foto3',
                        name: 'foto3',
                    },
                    {
                        data: 'condicion_envio',
                        name: 'condicion_envio',
                    },
                    {data: 'action', name: 'action', orderable: false, searchable: false,sWidth:'20%'},

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

            tablaAnulados=$('#tablaAnulados').DataTable({
                processing: true,
                autoload: false,
                stateSave: false,
                serverSide: true,
                searching: false,
                "bFilter": false,
                "order": [[5, "desc"]],
                ajax: {
                    url: "{{ route('envios.estadosobrestabla') }}",
                    data: function (d) {
                        d.opcion = 'anulado';
                    },
                },
                createdRow: function (row, data, dataIndex) {
                },
                rowCallback: function (row, data, index) {

                    if (data.destino2 == 'PROVINCIA') {
                        $('td', row).css('color', 'red')
                    } else if (data.destino2 == 'LIMA') {
                        if (data.distribucion != null) {
                            if (data.distribucion == 'NORTE') {
                                //$('td', row).css('color','blue')
                            } else if (data.distribucion == 'CENTRO') {
                                //$('td', row).css('color','yellow')
                            } else if (data.distribucion == 'SUR') {
                                //$('td', row).css('color','green')
                            }
                        } else {
                        }
                    }

                    $('[data-jqconfirm]', row).click(function () {
                        $.confirm({
                            type: 'red',
                            title: '¡Revertir Envio!',
                            content: 'Confirme si desea revertir el envio <b>'+data.codigos+'</b>',
                            buttons: {
                                ok:{
                                    text:'Si, confirmar',
                                    btnClass:'btn-red',
                                    action:function (){
                                        const self=this;
                                        self.showLoading(true)
                                        $.ajax({
                                            data: {
                                                envio_id:data.id,
                                                pedido:data.codigos
                                            },
                                            type: 'POST',
                                            url: "{{ route('operaciones.revertirhaciaatendido') }}",
                                        }).always(function (){
                                            self.close()
                                            self.hideLoading(true)
                                            $('#tablaPrincipal').DataTable().ajax.reload();
                                        });
                                    }
                                },
                                cancel:{
                                    text:'No'
                                }
                            }
                        })
                    });
                },
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
                        }, "visible": false
                    },
                    {data: 'codigo', name: 'codigo',},
                    {data: 'users', name: 'users',},

                    {data: 'empresas', name: 'empresas',},
                    {data: 'dias', name: 'dias',},

                    {data: 'fecha_envio_doc', name: 'fecha_envio_doc', "visible": false},
                    {data: 'fecha_envio_doc_fis', name: 'fecha_envio_doc_fis',},
                    {data: 'fecha_recepcion', name: 'fecha_recepcion', "visible": false},
                    {data: 'destino', name: 'destino', "visible": false},
                    {
                        data: 'direccion',
                        name: 'direccion',
                        "visible": false,
                        render: function (data, type, row, meta) {
                            datas = '';
                            if (data != null) {
                                return data;
                            } else {
                                return '<span class="badge badge-danger">REGISTRE DIRECCION</span>';
                            }
                            //return 'REGISTRE DIRECCION';
                        },
                    },
                    {
                        data: 'condicion_envio',
                        name: 'condicion_envio',
                        render: function (data, type, row, meta) {
                            var badge_estado = '';
                            if (row.estado_sobre == 1) {
                                badge_estado += '<span class="badge badge-dark p-8" style="color: #fff; background-color: #347cc4; font-weight: 600; margin-bottom: -2px;border-radius: 4px 4px 0px 0px; font-size:8px;  padding:6px;">Direccion agregada</span>';
                            }
                            badge_estado += '<span class="badge badge-success" style="background-color:' + row.condicion_envio_color + ' !important;" >' + data + '</span>';
                            return badge_estado;
                        }
                    },
                    {
                        data: 'envio',
                        name: 'envio',
                        render: function (data, type, row, meta) {
                            if (row.envio == '1') {
                                return '<span class="badge badge-danger">Por confirmar recepcion</span>';
                            } else {
                                return '<span class="badge badge-info">Recibido</span>';
                            }
                        },
                        "visible": false
                    },
                    {
                        data: 'observacion_devuelto',
                        name: 'observacion_devuelto',
                        render: function (data, type, row, meta) {
                            if (data != null) {
                                return data;
                            } else {
                                return ''
                            }
                        },
                        "visible": true
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        sWidth: '20%',
                        "visible": false,
                        render: function (data, type, row, meta) {
                            datass = '';


                            return datass;
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
        function maxLengthCheck(object) {
            if (object.value.length > object.maxLength)
                object.value = object.value.slice(0, object.maxLength)
        }

        /* Custom filtering function which will search data in column four between two values */
        $(document).ready(function () {


        });
    </script>

@stop
