@extends('adminlte::page')

@section('title', 'Envios | Sobres sin enviar')

@section('content_header')
    <h1>DISTRIBUCION DE SOBRES</h1>
@stop

@section('content')
    @php
        $color_zones=[];
        $color_zones['NORTE']='warning';
        $color_zones['CENTRO']='info';
        $color_zones['SUR']='dark';
    @endphp
    <div class="row">
        @foreach($motorizados as $motorizado)
            <div class="col-4 container-{{Str::slug($motorizado->zona)}}">
                <div class="table-responsive">
                    <div class="card card-{{$color_zones[Str::upper($motorizado->zona)]??'info'}}">
                        <div class="card-header">
                            <div class="d-flex justify-content-between">
                                <h5> {{Str::upper($motorizado->zona)}}</h5>
                                <div>
                                    <button type="button" class="btn btn-light buttom-agrupar"
                                            data-zona="{{Str::upper($motorizado->zona)}}"
                                            data-table-save="#tablaPrincipal{{Str::upper($motorizado->zona)}}"
                                            data-ajax-action="{{route('envios.distribuirsobres.agrupar',['visualizar'=>1,'motorizado_id'=>$motorizado->id,'zona'=>Str::upper($motorizado->zona)])}}">
                                        <span class="spinner-border spinner-border-sm"
                                              role="status" aria-hidden="true" style="display: none"></span>
                                        <span class="sr-only" style="display: none"></span>
                                        <i class="fa fa-envelope-o" aria-hidden="true"></i>
                                        <b>Crear Paquetes</b>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body py-1">
                            <div>
                                <table id="tablaPrincipal{{Str::upper($motorizado->zona)}}" class="table table-striped">
                                    <thead>
                                    <tr>
                                        <th scope="col">Cliente</th>
                                        <th scope="col">Zona</th>
                                        <th scope="col">Distrito</th>
                                        <th scope="col">Acciones</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach


    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tablaPrincipal" class="table table-striped">
                    <thead>
                    <tr>
                        <th scope="col">Sobres</th>
                        <th scope="col">Razón social</th>
                        <th scope="col">CLIENTE</th>
                        <th scope="col">TELEFONO</th>
                        <th scope="col">PROV</th>
                        <th scope="col">DISTRITO</th>
                        <th scope="col">DIRECCION</th>
                        <th scope="col">REFERENCIA</th>
                        <th scope="col">Estado de envio</th>
                        <th scope="col">ZONA</th>
                        <th scope="col">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@stop

@push('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">
    <link rel="stylesheet" href="{{asset('vendor/fontawesome-free/css/v4-shims.min.css')}}">
    <link rel="stylesheet" href="{{asset('vendor/fontawesome-free/css/solid.min.css')}}">
    <style>
        .cod_dir{
            font-size:11px;
            min-width: 200px;
        }
    </style>
@endpush

@push('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js"></script>


    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        var insertIds = []

        function createZoneRowTable(data, zona) {
            console.log(data, zona)
            return {
                ...data,
                cliente_recibe: data.cliente_recibe,
                zona: data.zona,
                zona_asignada: zona,
                distrito: data.distrito,
                action: `<button type="button" data-jqdetalle="${data.id}" class="btn btn-light buttom-agrupar d-flex align-items-center justify-content-center">
                                        <i class="fa fa-layer-group mr-1"></i>
                                        Detalle
                                    </button>
                                    <button type="button" data-revertir="${data.id}" class="btn btn-light buttom-agrupar d-flex align-items-center justify-content-center">
                                        <i class="fa fa-undo-alt mr-1 text-danger"></i>
                                        Revertir
                                    </button>`,
            }
        }

        $(document).ready(function () {
            $('#tablaPrincipal').DataTable({
                processing: true,
                stateSave: true,
                serverSide: true,
                searching: true,
                order: [[0, "desc"]],
                search: {
                    regex: true
                },
                ajax: {
                    url: "{{ route('envios.distribuirsobrestabla') }}",
                    data: function (query) {
                        query.exclude_ids = insertIds
                    }
                },
                createdRow: function (row, data, dataIndex) {
                    //console.log(row);

                },
                rowCallback: function (row, data, index) {
                    const self = this
                    $("[data-elTable]", row).click(function () {
                        $("#tablaPrincipal [data-elTable]").attr('disabled', 'disabled')
                        $(this).find('.spinner-border').show()
                        $(this).find('.sr-only').show()

                        var tableId = $(this).data('eltable');
                        var zona = $(this).data('zona');
                        insertIds.push(data.id)

                        $(tableId).DataTable()
                            .row.add(createZoneRowTable(data, zona)).draw(false);
                        self.api().ajax.reload();
                    })
                },
                columns: [
                    {data: 'codigos', name: 'codigos', sWidth: '8%'},
                    {data: 'productos', name: 'productos',searchable: true, sClass:'cod_dir'},
                    {data: 'cliente_recibe', name: 'cliente_recibe',},
                    {data: 'telefono', name: 'telefono',},
                    {data: 'provincia', name: 'provincia',},
                    {data: 'distrito', name: 'distrito',},
                    {data: 'direccion', name: 'direccion',},
                    {data: 'referencia', name: 'referencia',},
                    {data: 'condicion_envio', name: 'condicion_envio',},
                    {data: 'zona', name: 'zona',},
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


            const configDataTableZonas = {
                /*processing: false,
                stateSave: true,
                serverSide: false,
                searching: true,
                bLengthMenu: false,
                bInfo: false,*/
                lengthChange: false,
                order: [[0, "desc"]],
                createdRow: function (row, data, dataIndex) {
                },
                columns: [
                    {data: 'cliente_recibe', name: 'cliente_recibe',},
                    {data: 'zona', name: 'zona',},
                    {data: 'distrito', name: 'distrito',},
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        sWidth: '20%'
                    },
                ],
                language: {
                    "decimal": "",
                    "emptyTable": "No hay información",
                    "info": "_START_ - _END_ / _TOTAL_",
                    "infoEmpty": "0 Entradas",
                    "infoFiltered": "(Filtrado de _MAX_ total entradas)",
                    "infoPostFix": "",
                    "thousands": ",",
                    "lengthMenu": "Mostrar _MENU_ Entradas",
                    "loadingRecords": "Cargando...",
                    "processing": ``,
                    "search": "Buscar:",
                    "zeroRecords": "Sin resultados encontrados",
                    "paginate": {
                        "first": "Primero",
                        "last": "Ultimo",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                },
            }
            @foreach($motorizados as $motorizado)
            $('#tablaPrincipal{{Str::upper($motorizado->zona)}}').DataTable({
                ...configDataTableZonas,
                rowCallback: function (row, data, index) {
                    var table = this;
                    $('[data-revertir]', row).unbind();
                    $('[data-jqdetalle]', row).unbind();

                    $('[data-revertir]', row).click(function () {
                        insertIds = insertIds.filter(function (id) {
                            return id != data.id;
                        })
                        $('#tablaPrincipal').DataTable().ajax.reload();
                        table.api().row(row).remove().draw(false)
                    })
                    $('[data-jqdetalle]', row).click(function () {
                        console.log(data)
                        $.confirm({
                            title: '¡Detalle del grupo!',
                            columnClass: 'xlarge',
                            content: getHtmlPrevisualizarDesagrupar(data),
                            type: 'orange',
                            typeAnimated: true,
                            buttons: {
                                cancelar: function () {
                                    $('#tablaPrincipal').DataTable().ajax.reload();
                                    return true
                                }
                            },
                            onContentReady: function () {
                                const self = this

                                function setEvents() {
                                    self.$content.find('[data-jqdesagrupar]').click(function (e) {
                                        $.ajax({
                                            url: '{{route('envios.distribuirsobres.desagrupar')}}',
                                            data: {
                                                grupo_id: e.target.dataset.jqdesagrupar,
                                                pedido_id: e.target.dataset.pedido_id,
                                            },
                                            method: 'delete'
                                        })
                                            .done(function (grupo) {
                                                $('#tablaPrincipal{{Str::upper($motorizado->zona)}}')
                                                    .DataTable()
                                                    .row(row)
                                                    .data(createZoneRowTable(grupo.data, '{{Str::upper($motorizado->zona)}}'))
                                                    .draw();
                                                if (grupo.data) {
                                                    self.setContent(getHtmlPrevisualizarDesagrupar(grupo.data))
                                                } else {
                                                    self.close()
                                                    $.alert('Desagrupado por completo')
                                                }
                                                setEvents()
                                            })
                                            .always(function () {
                                                $('#tablaPrincipal').DataTable().ajax.reload();
                                            })
                                    })
                                }

                                setEvents();
                            }
                        })
                    })
                }
            });
            @endforeach

            function getHtmlPrevisualizarDesagrupar(row, success) {
                return `
<div class="card">
    <div class="card-header">
        <h4>Cliente: <strong>${row.cliente_recibe}</strong> - <i>${row.telefono}</i></h4>
    </div>
    <div class="card-body">
        <div class="col-md-12">
            <ul class="list-group">
                <li class="list-group-item">
                    <div class="row">
                        <div class="col-4">
                            <b>Codigo</b>
                        </div>
                        <div class="col-4">
                            <b>Razon Social</b>
                        </div>
                        <div class="col-4 text-center">
                            <b>Acciones</b>
                        </div>
                    </div>
                </li>
            ${row.pedidos.map(function (pedido) {
                    return `
                <li class="list-group-item">
                    <div class="row">
                        <div class="col-4">
                            ${pedido.pivot.codigo}
                        </div>
                        <div class="col-4">
                            ${pedido.pivot.razon_social}
                        </div>
                        <div class="col-4 text-center">
                            ${row.pedidos.length > 1 ? `<button class="btn btn-danger" data-jqdesagrupar="${row.id}" data-pedido_id="${pedido.id}"><i class="fa fa-arrow-down"></i> Desagrupar</button>` : ''}
                        </div>
                    </div>
                </li>`
                }).join('')}
            </ul>
        </div>
    </div>
</div>`;
            }

            function getHtmlPrevisualizarAgruparData(rows, success) {
                var html = rows.map(function (row) {
                    const ps = row.producto.split(',')
                    const productos = [`<li class="list-group-item">
                                    <div class="row">
                                        <div class="col-4 border-right">
                                        <strong>${row.nombre || ''}</strong> - <i>${row.celular || ''}</i>
                                        </div>
                                        <div class="col-4 border-right">
                                        <b>${row.distribucion || ''}</b><hr class="my-2"> ${row.distrito || ''}, ${row.direccion || ''} <hr class="my-2"><i> ${row.referencia || ''}</i>
                                        </div>
                                        <div class="col-4">
                                    ${row.codigos.split(',').map(function (codigo, index) {
                        return `<b>${codigo}</b> - <i>${ps[index] || ''}</i>`
                    }).join(`<hr class="my-2">`)}
                                        </div>
                                    </div>
                                </li>`]
                    return `<div class="col-md-12">
<div class="card border card-dark">
<div class="card-header">
${success ? `Paquete: <strong>${row.correlativo || ''}</strong>` : `Cliente: <strong>${row.nombre || ''}</strong> - <i>${row.celular || ''}</i>`}
</div>
<div class="card-body">
<ul class="list-group">
    <li class="list-group-item">
        <div class="row">
            <div class="col-4 border-right text-center">
            <b>Cliente</b>
            </div>
            <div class="col-4 border-right text-center">
            <b>Dirección</b>
            </div>
            <div class="col-4 text-center">
            <b>Productos</b>
            </div>
        </div>
    </li>
    ${productos.join('<hr>')}
</ul>
</div>
</div>
</div>`
                })
                return `<div class="row">${html.join('')}</div>`;
            }

            function getHtmlPrevisualizarPaqueteData(rows, success) {
                var html = rows.map(function (row) {
                    const ps = row.producto.split(',')
                    const productos = [`<li class="list-group-item">
                                    <div class="row">
                                        <div class="col-4 border-right">
                                        <strong>${row.nombre || ''}</strong> - <i>${row.celular || ''}</i>
                                        </div>
                                        <div class="col-4 border-right">
                                        <b>${row.distribucion || ''}</b><hr class="my-2"> ${row.distrito || ''}, ${row.direccion || ''} <hr class="my-2"><i> ${row.referencia || ''}</i>
                                        </div>
                                        <div class="col-4">
                                    ${row.codigos.split(',').map(function (codigo, index) {
                        return `<b>${codigo}</b> - <i>${ps[index] || ''}</i>`
                    }).join(`<hr class="my-2">`)}
                                        </div>
                                    </div>
                                </li>`]
                    // ${success?`<div class="col-12 alert alert-success">Grupos creados correctamente</div>`:``}
                    return `<div class="col-md-12">
<div class="card border card-dark">
<div class="card-header">
${success ? `Paquete: <strong>${row.correlativo || ''}</strong>` : `Cliente: <strong>${row.nombre || ''}</strong> - <i>${row.celular || ''}</i>`}
</div>
<div class="card-body">
<ul class="list-group">
    <li class="list-group-item">
        <div class="row">
            <div class="col-4 border-right text-center">
            <b>Cliente</b>
            </div>
            <div class="col-4 border-right text-center">
            <b>Dirección</b>
            </div>
            <div class="col-4 text-center">
            <b>Productos</b>
            </div>
        </div>
    </li>
    ${productos.join('<hr>')}
</ul>
</div>
</div>
</div>`
                })
                return `<div class="row">${html.join('')}</div>`;
            }

            $(".buttom-agrupar[data-table-save]").click(function () {
                const buttom = $(this)
                const link = buttom.attr('data-ajax-action')
                const tableId = buttom.attr('data-table-save')
                const zona = buttom.attr('data-zona')
                const table = $(tableId).DataTable();
                const grupos = Array.from(table.data()).map(function (item) {
                    return item.id
                })
                if (grupos.length === 0) {
                    return;
                }
                $.confirm({
                    title: '¡Confirmar creación de paquetes!',
                    columnClass: 'xlarge',
                    content: function () {
                        const self = this
                        self.$$goSobres.hide();
                        //return '¿Estas seguro de crear el paquete con los sobres listados en la zona <b>' + zona + '</b>?'
                        return $.ajax({
                            url: link,
                            data: {
                                groups: Array.from(table.data()).map(function (item) {
                                    return item.id
                                })
                            },
                            dataType: 'json',
                            method: 'post'
                        })
                            .done(function (response) {
                                self.setContent(getHtmlPrevisualizarAgruparData(response))
                            })
                    },
                    type: 'orange',
                    typeAnimated: true,
                    buttons: {
                        ok: {
                            text: 'Aceptar y crear paquetes',
                            btnClass: 'btn-success',
                            action: function () {
                                buttom.find('.spinner-border').show()
                                buttom.find('.sr-only').show()
                                const self = this
                                console.log(self)
                                self.showLoading(true)
                                $.ajax({
                                    url: link.replace('visualizar=1', '').replace('visualizar', '_agrupar').replace('?&', '?'),
                                    data: {
                                        groups: Array.from(table.data()).map(function (item) {
                                            return item.id
                                        })
                                    },
                                    dataType: 'json',
                                    method: 'post'
                                })
                                    .done(function (response) {
                                        self.setTitle('<h3 class="text-success font-24">Paquetes creados exitosamente</h3>');
                                        self.setContent(getHtmlPrevisualizarPaqueteData(response, true))
                                        self.$$ok.hide();
                                        self.$$goSobres.show();
                                        self.$$cancelar.text("Cerrar");
                                    })
                                    .always(function () {
                                        self.hideLoading(true)
                                        //self.close()
                                        buttom.find('.spinner-border').hide()
                                        buttom.find('.sr-only').hide()

                                        $('#tablaPrincipal').DataTable().ajax.reload();
                                        table.clear()
                                            .draw();
                                    })
                                return false
                            }
                        },
                        goSobres: {
                            text: 'Visualizar en sobres para reparto',
                            btnClass: 'btn-success',
                            action: function () {
                                window.open('{{route('envios.parareparto')}}', '_blank')
                                return true
                            }
                        },
                        cancelar: function () {
                            return true
                        }
                    }
                });
            })
        });
    </script>

@endpush
