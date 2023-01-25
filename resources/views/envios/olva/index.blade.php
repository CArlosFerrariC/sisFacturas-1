@extends('adminlte::page')

@section('title', 'Lista de pedidos por enviar')

@section('content_header')
    <h1>Lista de Envios en TIENDA/AGENTE para el encargado</h1>
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
            {{-- <table cellspacing="5" cellpadding="5">
              <tbody>
                <tr>
                  <td>Destino:</td>
                  <td>
                    <select name="destino" id="destino" class="form-control">
                      <option value="LIMA">LIMA</option>
                      <option value="PROVINCIA">PROVINCIA</option>
                    </select>
                  </td>
                </tr>
              </tbody>
            </table><br> --}}
            <table id="tablaPrincipal" style="width:100%;" class="table table-striped">
                <thead>
                <tr>
                    <th scope="col">Item</th>
                    <th scope="col">Código</th>
                    <th scope="col">Asesor</th>
                    <th scope="col">Cliente</th>
                    <th scope="col">Fecha de Envio</th>
                    <th scope="col">Razón social</th>
                    <th scope="col">Destino</th>
                    <th scope="col">Tracking</th>
                    <th scope="col">Numero de registro</th>
                    <th scope="col">Estado de envio</th><!--ENTREGADO - RECIBIDO-->
                    <th scope="col">Acciones</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

@stop

@push('css')
    <link rel="stylesheet" href="{{asset('/css/admin_custom.css')}}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">
@endpush

@section('js')
    {{--<script src="{{ asset('js/datatables.js') }}"></script>--}}
    <script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js"></script>

    <script src="https://momentjs.com/downloads/moment.js"></script>
    <script src="https://cdn.datatables.net/plug-ins/1.11.4/dataRender/datetime.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.7/jquery.inputmask.min.js"></script>

    <script>
        $(document).ready(function () {

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $('#tablaPrincipal').DataTable({
                processing: true,
                stateSave: true,
                serverSide: true,
                searching: true,
                /*  "order": [[0, "desc"]],*/
                ajax: "{{ route('envios.olva.table') }}",
                createdRow: function (row, data, dataIndex) {
                    //console.log(row);
                },
                rowCallback: function (row, data, index) {
                    $('[data-jqconfirm="notificado"]', row).click(function () {
                        const form_action = $(this).data('action')
                        $.dialog({
                            columnClass: 'large',
                            type: 'green',
                            title: 'Notificado',
                            content: `
<div class="p-3">
<div class="row">
<div class="col-md-12">
        <div class="alert alert-warning"> Informa a tu cliente que el sobre ya se encunetra en olva, <b>adjunta una captura de pantalla que se muestre numero de telefono, fecha y hora y mensaje haciendole recordar que tiene que ir a recoger su sobre</b></div>

        <strong>Adjuntar estado de olva</strong>
        <div id="attachmentfiles" class="border border-dark rounded d-flex justify-content-center align-items-center mb-4 position-relative" style="height: 400px">
            <i class="fa fa-upload"></i>
            <div class="result_picture position-absolute" style="display: block;top: 0;left: 0;bottom: 0;right: 0;text-align: center;">
                <img src="" class="h-100">
            </div>
        </div>
 <div class="alert alert-warning">Puede copiar y pegar la imagen o hacer click en el recuadro para seleccionar un archivo</div>
</div>
</div>
</div>
<div class="jconfirm-buttons">
<button type="button" class="btn-ok btn btn-success">Aceptar</button>
<button type="button" class="btn-cancel btn btn-default">cancelar</button>
</div>
`,
                            onContentReady: function () {
                                var self = this;
                                const dataForm = {
                                    direccion_grupo_id: data.id
                                }
                                this.$content.find('.result_picture').hide()
                                this.$content.find('#attachmentfiles').click(function () {
                                    var file = document.createElement('input');
                                    file.type = 'file';
                                    file.click()
                                    file.addEventListener('change', function (e) {
                                        if (file.files.length > 0) {
                                            self.$content.find('.result_picture').css('display', 'block')
                                            console.log(URL.createObjectURL(file.files[0]))
                                            dataForm.file = file.files[0]
                                            self.$content.find('.result_picture>img').attr('src', URL.createObjectURL(file.files[0]))
                                        }
                                    })
                                });
                                window.document.onpaste = function (event) {
                                    var items = (event.clipboardData || event.originalEvent.clipboardData).items;
                                    console.log(items);
                                    console.log((event.clipboardData || event.originalEvent.clipboardData));
                                    var files = []
                                    for (index in items) {
                                        var item = items[index];
                                        if (item.kind === 'file') {
                                            // adds the file to your dropzone instance
                                            var file = item.getAsFile()
                                            files.push(file)
                                        }
                                    }
                                    if (files.length > 0) {
                                        self.$content.find('.result_picture').css('display', 'block')
                                        console.log(URL.createObjectURL(files[0]))
                                        self.$content.find('.result_picture>img').attr('src', URL.createObjectURL(files[0]))
                                        dataForm.file = files[0]
                                    }
                                }

                                this.$content.find('button.btn-cancel').click(function () {
                                    self.close()
                                })
                                this.$content.find('button.btn-ok').click(function () {
                                    const fd = new FormData()
                                    fd.append('file', dataForm.file, dataForm.file.name)
                                    self.showLoading(true)
                                    $.ajax({
                                        url: form_action,
                                        data: fd,
                                        processData: false,
                                        contentType: false,
                                        type: 'POST',
                                    }).done(function (r) {
                                        self.close()
                                        if (!r.success) {
                                            $.alert('Ya se a ingresado una imagen para el dia de hoy')
                                        }

                                    }).always(function () {
                                        self.hideLoading(true)
                                        $('#tablaPrincipal').DataTable().draw(false)
                                    })
                                })
                            },
                            onDestroy: function () {
                                window.document.onpaste = null
                            },
                        })
                    })
                },
                columns: [
                    {
                        data: 'id',
                        name: 'id',
                        render: function (data, type, row, meta) {
                            if (row.id < 10) {
                                return 'ENV000' + row.id;
                            } else if (row.id < 100) {
                                return 'ENV00' + row.id;
                            } else if (row.id < 1000) {
                                return 'ENV0' + row.id;
                            } else {
                                return 'ENV' + row.id;
                            }
                        }
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
                        },
                    },
                    {data: 'identificador', name: 'identificador',},
                    {
                        data: 'celular',
                        name: 'celular',
                        render: function (data, type, row, meta) {
                            return row.cliente_celular + ' - ' + row.cliente_nombre
                        },
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
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
                        data: 'destino',
                        name: 'destino',
                        "visible": false,
                    },

                    {
                        data: 'direccion_format',
                        name: 'direccion',
                        render: function (data, type, row, meta) {
                            if (data != null) {
                                return data;
                            } else {
                                return '<span class="badge badge-info">REGISTRE DIRECCION</span>';
                            }
                        },
                    },
                    {
                        data: 'referencia_format',
                        name: 'referencia',
                        sWidth: '10%'
                    },
                    {
                        data: 'condicion_envio_format',
                        name: 'condicion_envio',
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        sWidth: '10%',
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
@stop