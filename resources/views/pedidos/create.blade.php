@extends('adminlte::page')
@section('title', 'Agregar pedidos')
@section('content_header')

    <h1>Agregar pedidos</h1>

    {{-- @error('num_ruc')
<small class="text-danger" style="font-size: 16px">{{ $message }}</small>
@enderror --}}
@stop

@section('content')
    {{--  {!! Form::open(['route' => 'pedidos.store','enctype'=>'multipart/form-data', 'id'=>'formulario','files'=>true]) !!}
    <form id="formulario" name="formulario" enctype="multipart/form-data"> --}}
    {{Form::open(['files'=>true,'name'=>'formulario','id'=>'formulario'])}}
    @include('pedidos.partials.form')
    <div class="card-footer" id="guardar">
        <button type="submit" class="btn btn-success" id="btnImprimir" target="_blank">
            <i class="fas fa-save"></i>
            Guardar
        </button>
        <button type="button" onClick="history.back()" class="btn btn-danger btn-lg">
            <i class="fas fa-arrow-left"></i>
            ATRAS
        </button>
    </div>
    {!! Form::close() !!}
    @include('pedidos.modal.AddRuc')
    @include('pedidos.modal.copiarinfo')
    @include('pedidos.modal.historial')
    @include('pedidos.modal.historial2')



    <x-simple-modal id="previsualizar_modal_pedido" title="Previsualizar Pedido" size="lg">
        <x-slot name="body">
            <div class="">
                <button type="button" onclick="copyElement('#pedido_visualizar_content')" class="btn btn-outline-dark">
                    <i class="fa fa-copy"></i> Copiar
                </button>
                <textarea class="form-control w-100" cols="20" rows="15" id="pedido_visualizar_content"></textarea>
            </div>
        </x-slot>

        <x-slot name="footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        </x-slot>
    </x-simple-modal>
@endsection

@section('css')
    {{-- <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css" rel="stylesheet" /> --}}

    <style>
        select option:disabled {
            color: #000;
            font-weight: bold;
        }

        .highlight {
            color: red !important;
            background: white !important;
        }
    </style>
    <script>
        window.copyElement=function (el) {
            $(el).select();
            window.document.execCommand("copy");
        }
    </script>
@stop

@section('js')
    {{-- <script src="{{ asset('js/datatables.js') }}"></script> --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js"></script>

    <script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js"></script>
    <script>
        var tabladeudores = null;
        var tablahistorial = null;
    </script>

    @if (session('info') == 'registrado')
        <script>
            Swal.fire(
                'RUC {{ session('info') }} correctamente',
                '',
                'success'
            )
        </script>
    @endif

    <script>
        ///fin

        //VALIDAR CAMPOS NUMERICO DE MONTO EN PAGOS

        $('input.number').keyup(function (event) {

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

        //VALIDAR CAMPO RUC
        function maxLengthCheck(object) {
            if (object.value.length > object.maxLength)
                object.value = object.value.slice(0, object.maxLength)
        }

        // CARGAR RUCS DE CLIENTE SELECCIONADO


        // CARGAR CLIENTES DE ASESOR


        // CARGAR TIPO DE COMPROBANTE Y BANCA/PORCENTAJES DE CLIENTE SELECCIONADO


        //VALIDACION DE CAMPOS


        var cont = 0;
        total = 0;
        subtotal = [];
        $("#guardar").hide();
        $("#ptipo_banca").change(mostrarValores);

        function mostrarValores() {
            datosTipoBanca = document.getElementById('ptipo_banca').value.split('_');
            $("#pporcentaje").val(datosTipoBanca[1]);
        }

        function ValidarDatosPedido() {
            pruc = $("#pruc option:selected").val();
            nombre_empresa = $("#pempresa").val();
            //ASESOR
            asesor_ide = $("#user_id option:selected").val();

            //CLIENTE
            cliente_ide = $("#cliente_id option:selected").val();

            //MES
            mes = $("#pmes").val();

            //AÑO
            anio = $("#panio").val();

            // CANTIDAD
            var cant_strEx = $("#pcantidad").val(); //1,000.00
            cant_strEx = cant_strEx.replace(",", ""); //1000.00
            var can_numFinal = parseFloat(cant_strEx);

            // BANCA
            var selectedTipoBanca = document.getElementById('ptipo_banca').value.split('_');
            var ptipo_banca = selectedTipoBanca[0];

            // PORCENTAJE
            //porcentaje = $("#pporcentaje").val();

            // COURIER
            /*var strEx = $("#pcourier").val(); //1,000.00
            strEx = strEx.replace(",", ""); //1000.00
            var numFinal = parseFloat(strEx);
            courier = numFinal * 1;

            var respuesta_validacion = "";*/


            $.ajax({
                data: {
                    ruc: pruc,
                    nombre_empresa: nombre_empresa,
                    asesor: asesor_ide,
                    cliente: cliente_ide,
                    mes: mes,
                    ano: anio,
                    ptipo_banca: ptipo_banca,
                    cantidad: cant_strEx
                    //banca : tipo_banca,
                    //porcentaje : porcentaje,
                    //courier : courier
                },
                type: 'POST',
                url: "{{ route('validarpedido') }}",
                success: function (data) {
                    console.log(data.html);

                    if (!data.is_repetido) {
                        agregar();
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Advertencia',
                            html: 'Este pedido ya se encuentra regitrado con el codigo <b>'+data.codigos+'</b>',
                            showDenyButton: true,
                            confirmButtonText: 'Estoy de acuerdo',
                            denyButtonText: 'Cancelar',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                //agregar();
                                if (result.isConfirmed) {
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Advertencia',
                                        text: 'Recuerda que tienes que revisar si es un pedido duplicado',
                                        showDenyButton: true,
                                        confirmButtonText: 'Estoy de acuerdo, Agregar',
                                        denyButtonText: 'Cancelar, No Agregar',
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            agregar();
                                        }
                                    })
                                    //agregar();
                                }
                            }
                        })
                    }
                }
            });
            return false;
        };

        function agregar() {
            datosTipoBanca = document.getElementById('ptipo_banca').value.split('_');
            datosCodigo = document.getElementById('pcodigo').value.split('-');

            var strEx = $("#pcantidad").val(); //1,000.00
            //primer paso: fuera coma
            strEx = strEx.replace(",", ""); //1000.00
            var numFinal = parseFloat(strEx);
            cantidad = numFinal * 1;

            var strEx = $("#pcourier").val(); //1,000.00
            //primer paso: fuera coma
            strEx = strEx.replace(",", ""); //1000.00
            var numFinal = parseFloat(strEx);
            courier = numFinal * 1;

            //codigo = $("#pcodigo").val();
            numped = datosCodigo[1];
            nombre_empresa = $("#pempresa").val();
            mes = $("#pmes").val();
            anio = $("#panio").val();
            ruc = $("#pruc").val();
            /* cantidad = $("#pcantidad").val(); */
            tipo_banca = datosTipoBanca[0];
            porcentaje = $("#pporcentaje").val();
            /* courier = $("#pcourier").val(); */
            descripcion = $("#pdescripcion").val();
            nota = $("#pnota").val();

            if (nombre_empresa != "" && mes != "") {
                subtotal[cont] = (cantidad * porcentaje) / 100;
                total = Number(courier) + subtotal[cont];

                var fila = '<tr class="selected" id="fila' + cont +
                    '"><td><button type="button" class="btn btn-warning" onclick="eliminar(' + cont +
                    ');">X</button></td>' +
                    //'<td><input type="hidden" name="codigo[]" value="' + codigo + '">' + codigo + '</td>' +
                    '<td><textarea class="d-none" name="nombre_empresa[]">' + nombre_empresa + '</textarea>' + nombre_empresa +
                    '</td>' +
                    '<td><input type="hidden" name="mes[]" value="' + mes + '">' + mes + '</td>' +
                    '<td><input type="hidden" name="anio[]" value="' + anio + '">' + anio + '</td>' +
                    '<td><input type="hidden" name="ruc[]" value="' + ruc + '">' + ruc + '</td>' +
                    '<td><input type="hidden" name="cantidad[]" value="' + cantidad + '">' + cantidad.toLocaleString(
                        "en-US") + '</td>' +
                    '<td><input type="hidden" name="tipo_banca[]" value="' + tipo_banca + '">' + tipo_banca + '</td>' +
                    '<td><input type="hidden" name="porcentaje[]" value="' + porcentaje + '">' + porcentaje + '</td>' +
                    '<td><input type="hidden" name="courier[]" value="' + courier + '">' + courier + '</td>' +
                    '<td><textarea class="d-none" name="descripcion[]">' + descripcion + '</textarea>'+ descripcion +'</td>' +
                    '<td><textarea class="d-none" name="nota[]" >' + nota + '</textarea>'+ nota +'</td>' +
                    '<td>@csrf<input type="file" id="adjunto" name="adjunto[]" multiple /></td>' +
                    '<td>' + subtotal[cont].toLocaleString("en-US") + '</td></tr>';
                cont++; //accept= ".zip, .rar"
                limpiar();
                $("#total").html("S/. " + total.toLocaleString("en-US"));
                evaluar();
                $('#detalles').append(fila);
            } else {
                alert("error al ingresar el detalle del pedido, revise los datos");
            }
        }

        function limpiar() {
            /* $("#pcodigo").val("{{ $fecha }}-"+( Number(numped)+1 )); */
            $("#pcodigo").val("");
            $("#pempresa").val("");
            $('#pmes').val('').change();
            $('#panio').val('').change();
            $("#pruc").val("");
            $("#pcantidad").val("");
            $('#ptipo_banca').val('').change();
            $("#pporcentaje").val("");
            $("#pcourier").val("");
            $("#pdescripcion").val("");
            $("#pnota").val("");
        }

        function evaluar() {
            if (total > 0) {
                $("#guardar").show();
            } else {
                $("#guardar").hide();
            }

            if (cont > 0) {
                $("#bt_add").hide();
            } else {
                $("#bt_add").show();
            }
        }

        function eliminar(index) {
            $("#total").html("S/. 0.00");
            $("#fila" + index).remove();
            cont--;
            evaluar();
            $("#pcodigo").val("{{ Auth::user()->identificador }}-{{ $fecha }}-{{ $numped }}");
        }
    </script>

    <script>
        //VALIDAR ANTES DE ENVIAR
        /*document.addEventListener("DOMContentLoaded", function() {
        document.getElementById("formulario").addEventListener('submit', validarFormulario);
        });*/

        /*function validarFormulario(evento) {
          evento.preventDefault();

            window.open('https://sistema.ojoceleste.com/pedidos.mispedidos', '_blank');//CAMBIAR A LINK DE PRODUCCION//************
            this.submit();
        }*/
    </script>

    <script>
        //VALIDAR ANTES DE ENVIAR 2
        /*document.addEventListener("DOMContentLoaded", function() {
        var form = document.getElementById("formulario2")
          if(form)
          {
            form.addEventListener('submit', validarFormulario2);
          }
        });*/

        /*function validarFormulario2(evento) {
          evento.preventDefault();
          var agregarruc = document.getElementById('agregarruc').value;

          if (agregarruc == '') {
              Swal.fire(
                'Error',
                'Debe ingresar el número de RUC',
                'warning'
              )
          }
          else if (agregarruc.length < 11){
            Swal.fire(
                'Error',
                'El número de RUC debe tener 11 dígitos',
                'warning'
              )
          }
          else {
            this.submit();
          }
        }*/
    </script>

    <script>
        $(document).ready(function () {

            //$('#pmes').selectpicker('refresh');
            //$('#panio').selectpicker('refresh');

            /*$(document).on("change","#pmes",function(){
                console.log("sss")
                let ptipo_banca=$("#ptipo_banca").val().split('-')[0];
                console.log(ptipo_banca);
            })*/

            $(document).on("change","#panio",function(){
                //obtengo banca
                let ptipo_banca=$.trim($("#ptipo_banca").val().split('-')[0]);
                //obtengo anio
                console.log("banca "+ptipo_banca)
                let anno_filter=parseInt($(this).val());
                console.log(anno_filter)

                if (ptipo_banca==''){
                }else{
                    if(ptipo_banca=='FISICO')
                    {
                        if(isNaN(anno_filter))
                        {
                            console.log("anno is nan")
                        }else{
                            if(anno_filter!='')
                            {
                                if(  anno_filter=={{$anno_selected}}   ||  anno_filter==({{$anno_selected}}-1) )
                                {
                                }else{
                                    Swal.fire(
                                        'Error',
                                        'No puede seleccionar este año para banca fisica, elija otra opcion por favor',
                                        'warning'
                                    );
                                    $("#panio").val('').selectpicker('refresh')
                                }
                            }
                        }



                    }else if(ptipo_banca=='ELECTRONICA')
                    {
                        if(isNaN(anno_filter))
                        {
                            console.log("anno is nan")
                        }else{
                            if(anno_filter!='')
                            {
                                if(anno_filter=={{$anno_selected}})
                                {

                                }else{
                                    //2023= 2023
                                    Swal.fire(
                                        'Error',
                                        'No puede seleccionar este año para banca eletronica',
                                        'warning'
                                    );
                                    //return false;
                                    $("#panio").val('').selectpicker('refresh')
                                }
                            }
                        }
                    }
                }

            })

            $(document).on("change","#ptipo_banca",function(){
                //obtengo banca
                let ptipo_banca=$.trim($(this).val().split('-')[0]);
                //obtengo anio
                console.log("banca "+ptipo_banca)
                let anno_filter=parseInt($("#panio").val());
                console.log(anno_filter)

                if (ptipo_banca==''){
                }else{
                    if(ptipo_banca=='FISICO')
                    {
                        if(isNaN(anno_filter))
                        {
                            console.log("anno is nan")
                        }else{
                            if(anno_filter!='')
                            {
                                if(  anno_filter=={{$anno_selected}}   ||  anno_filter==({{$anno_selected}}-1) )
                                {
                                }else{
                                    Swal.fire(
                                        'Error',
                                        'No puede seleccionar este año para banca fisica, elija otra opcion por favor',
                                        'warning'
                                    );
                                    //return false;
                                    $("#panio").val('').selectpicker('refresh')
                                }
                            }
                        }

                    }else if(ptipo_banca=='ELECTRONICA')
                    {
                        if(isNaN(anno_filter))
                        {
                            console.log("anno is nan")
                        }else{
                            if(anno_filter!='')
                            {
                                if(anno_filter=={{$anno_selected}})
                                {

                                }else{
                                    if(anno_filter)
                                        //2023= 2023
                                        Swal.fire(
                                            'Error',
                                            'No puede seleccionar este año para banca eletronica',
                                            'warning'
                                        );
                                    //return false;
                                    $("#panio").val('').selectpicker('refresh');
                                }
                            }
                        }

                    }
                }
            })

            $("#previsualizar_modal_pedido").on('show.bs.modal',function () {
                let pruc = $('#pruc').val();
                let pempresa = $('#pempresa').val();
                let pmes = $('#pmes').val();
                let panio = $('#panio').val();
                let pcantidad = $('#pcantidad').val();
                let ptipo_banca = $('#ptipo_banca').val();
                let pdescripcion = $('#pdescripcion').val();
                let pnota = $('#pnota').val();

                var insertData=`
PEDIDO
__________________________________
*CANTIDAD* ${pcantidad}
*RUC* ${pruc}
*RAZON SOCIAL* ${pempresa}
*MES* ${pmes}
*AÑO* ${panio}
*FISICO O ELECTRONICO* ${ptipo_banca}
*DESCRIPCIÓN*
    ${pdescripcion}
*NOTA*
    ${pnota}
__________________________________
`;
              $("#pedido_visualizar_content").val(insertData)
            })

            $(document).on("submit", "#formulario", function (event) {
                event.preventDefault();
                //console.log("abrir")

                var fd = new FormData();
                //var data = new FormData(document.getElementById("formulario"));

                $('[name="nombre_empresa[]"]').each(function () {
                    fd.append("nombre_empresa[]", this.value);
                });
                $('[name="mes[]"]').each(function () {
                    fd.append("mes[]", this.value);
                });
                $('[name="anio[]"]').each(function () {
                    fd.append("anio[]", this.value);
                });
                $('[name="ruc[]"]').each(function () {
                    fd.append("ruc[]", this.value);
                });
                $('[name="cantidad[]"]').each(function () {
                    fd.append("cantidad[]", this.value);
                });
                $('[name="tipo_banca[]"]').each(function () {
                    fd.append("tipo_banca[]", this.value);
                });
                $('[name="porcentaje[]"]').each(function () {
                    fd.append("porcentaje[]", this.value);
                });
                $('[name="courier[]"]').each(function () {
                    fd.append("courier[]", this.value);
                });
                $('[name="descripcion[]"]').each(function () {
                    fd.append("descripcion[]", this.value);
                });
                $('[name="nota[]"]').each(function () {
                    fd.append("nota[]", this.value);
                });
                let files = $('[name="adjunto[]');
                /*if(files.length == 0)
                {
                  Swal.fire(
                      'Error',
                      'Debe ingresar el  adjunto del pedido',
                      'warning'
                    )
                    return false;
                }*/
                /*else{

                         var totalfilescarga = $('input[name="adjunto[]"]').get(0).files.length;
                         console.log("totalfilescarga "+totalfilescarga);


                         if(files.length!=totalfilescarga)
                         {
                           Swal.fire(
                             'Error',
                             'Debe ingresar los adjuntos del pedido',
                             'warning'
                           )
                           return false;
                         }else{
                          */

                if (files[0].files.length > 0) {
                    for (let i in files[0].files) {
                        fd.append('adjunto[]',  files[0].files[i]);
                    }
                }
                /*for (let i = 0; i < files.length; i++) {
                  fd.append('adjunto['+i+']', files[i]);
                }*/
                /*}

                   }*/
                $("#btnImprimir").prop("disabled", true);
                fd.append('user_id', $("#user_id").val());
                fd.append('cliente_id', $("#cliente_id").val());

                $.ajax({
                    data: fd,
                    //data: data,
                    processData: false,
                    contentType: false,
                    type: 'POST',
                    url: "{{ route('pedidoss.store') }}",
                    success: function (data) {
                        console.log(data);
                        if (data.html == '|2') {
                            Swal.fire(
                                'Error',
                                'Cliente supero el limite de pedidos (3) en el mes.',
                                'warning'
                            )

                        } else if (data.html == '|4') {
                            Swal.fire(
                                'Error',
                                'Cliente supero el limite de pedidos (5) en el mes.',
                                'warning'
                            )

                        } else if (data.html == '|0') {
                            Swal.fire(
                                'Error',
                                'Cliente mantiene deudas meses atras.',
                                'warning'
                            )

                        } else if (data.html == '|tmp_time') {
                            Swal.fire(
                                'Error',
                                'El tiempo dado para registrar pedidos expiro para el cliente seleccionado.',
                                'warning'
                            )
                        } else if (data.html == '|tmp_count') {
                            Swal.fire(
                                'Error',
                                'El cliente supero la cantidad dada para crearle pedidos extras.',
                                'warning'
                            )
                        } else {
                            var urlpdf = '{{ route('pedidosPDF', ':id') }}';
                            urlpdf = urlpdf.replace(':id', data.html);
                            window.open(urlpdf, '_blank');

                            $("#modal-copiar .textcode").text(data.html);

                            $("#modal-copiar").modal("show");
                        }
                    }
                });
            });

            $(document).on("change", "#user_id", function () {
                //console.log("link asesor "+$(this).val())
                var uid = $(this).val();
                //if($(this).val()!='')
                $.ajax({
                    url: "{{ route('cargar.clientedeasesor') }}?user_id=" + uid,
                    method: 'GET',
                    success: function (data) {
                        //console.log(data.html);
                        $('#cliente_id').html(data.html);
                        $("#cliente_id").selectpicker(
                            "refresh"); //addClass("your-custom-class")

                        $('#cliente_id_ruc').html(data.html);
                        let c_cliente_id = $('#cliente_id').val();
                        //console.log(c_cliente_id);

                        $('#cliente_id_ruc').selectpicker('refresh');
                        $('#cliente_id_ruc').val(c_cliente_id);

                    }
                });

            });

            /*console.log(" {{ Auth::user()->id }} ")
                $('#user_id option').attr("disabled", true);
                $("#user_id").val( "{{ Auth::user()->id }}" ).trigger("change");
                $("#user_id").selectpicker("refresh");*/

            $(document).on("click", "#bt_add", function () {

                if ($('#pcliente_id').val() == '') {
                    Swal.fire(
                        'Error',
                        'Seleccione Cliente',
                        'warning'
                    )
                } else if ($('#pempresa').val() == '') {
                    Swal.fire(
                        'Error',
                        'Agregue nombre de empresa',
                        'warning'
                    )
                } else if ($('#pmes').val() == '') {
                    Swal.fire(
                        'Error',
                        'Seleccione mes',
                        'warning'
                    )
                } else if ($('#panio').val() == '') {
                    Swal.fire(
                        'Error',
                        'Agregue el año',
                        'warning'
                    )
                } else if ($('#pruc').val() == '') {
                    Swal.fire(
                        'Error',
                        'Agregue número de RUC',
                        'warning'
                    )
                } else if ($('#pruc').val() < 0) {
                    Swal.fire(
                        'Error',
                        'El número de RUC no puede ser negativo',
                        'warning'
                    )
                } else if ($('#pruc').val().length < 11) {
                    Swal.fire(
                        'Error',
                        'Número de RUC incompleto',
                        'warning'
                    )
                } else if ($('#pruc').val().length > 11) {
                    Swal.fire(
                        'Error',
                        'Número de RUC debe teber máximo 11 dígitos',
                        'warning'
                    )
                } else if ($('#pcantidad').val() == '') {
                    Swal.fire(
                        'Error',
                        'Agregue cantidad',
                        'warning'
                    )
                } else if ($('#pcantidad').val() < 0) {
                    Swal.fire(
                        'Error',
                        'Ingrese una cantidad válida',
                        'warning'
                    )
                } else if ($('#ptipo_banca').val() == '') {
                    Swal.fire(
                        'Error',
                        'Seleccione tipo de comprobante y banca',
                        'warning'
                    )
                } else if ($('#pporcentaje').val() == '') {
                    Swal.fire(
                        'Error',
                        'Agregue porcentaje(%)',
                        'warning'
                    )
                } else if ($('#pporcentaje').val() < 0) {
                    Swal.fire(
                        'Error',
                        'Ingrese un porcentaje(%) válido',
                        'warning'
                    )
                } else if ($('#pcourier').val() == '') {
                    Swal.fire(
                        'Error',
                        'Agregue costo de courier (S/)',
                        'warning'
                    )
                } else if ($('#pcourier').val() < 0) {
                    Swal.fire(
                        'Error',
                        'Ingrese un costo de courier (S/) válido',
                        'warning'
                    )
                } else if ($('#pdescripcion').val() == '') {
                    Swal.fire(
                        'Error',
                        'Agregue descripción del pedido',
                        'warning'
                    )
                } else if ($('#pdescripcion').val().length > 250) { //
                    Swal.fire(
                        'Error',
                        'Se acepta máximo 200 caracteres',
                        'warning'
                    )
                } else if ($('#pnota').val() == '') {
                    Swal.fire(
                        'Error',
                        'Agregue nota del pedido',
                        'warning'
                    )
                } else if ($('#pnota').val().length > 250) {
                    Swal.fire(
                        'Error',
                        'Se acepta máximo 200 caracteres',
                        'warning'
                    )
                } else {
                    cantidad = !isNaN($('#pcantidad').val()) ? parseInt($('#pcantidad').val(), 10) : 0;
                    ValidarDatosPedido();
                }
            });

            $(document).on("change", "#cliente_id", function () {
                $.ajax({
                    url: "{{ route('cargar.tipobanca') }}?cliente_id=" + $(this).val(),
                    method: 'GET',
                    success: function (data) {
                        $('#ptipo_banca').html(data.html);
                    }
                });
            });

            $(document).on("change", "#cliente_id", function () {

                $.ajax({
                    url: "{{ route('cargar.ruc') }}?cliente_id=" + $(this).val(),
                    method: 'GET',
                    success: function (data) {
                        $('#pruc').html(data.html);
                        $("#pruc").selectpicker("refresh");

                    }
                });
            });

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            //$("#user_id").val("").trigger("change");
            //$("#cliente_id").val("").trigger("change");
            //$("#pruc").val("");//.trigger("change");
            //$("#user_id").selectpicker("refresh");


            $(document).on("change", "#pruc", function () {
                //al cambiar el ruc que hacer
                $.ajax({
                    url: "{{ route('rucnombreempresa') }}?ruc=" + $(this).val(),
                    method: 'GET',
                    //before
                    success: function (data) {
                        $('#pempresa').val((data.html));
                    }
                });
            });

            $('#modal-add-ruc').on('show.bs.modal', function (event) {

                $("#agregarruc").val("");
                $("#pempresaruc").val("");
                $("#porcentajeruc").val("");


                //limpiar datos

                let c_cliente_id = $('#cliente_id').val(); //
                //console.log(c_cliente_id+"id carga cliente para ruc");


                $('#cliente_id_ruc').val(c_cliente_id);
                $('#cliente_id_ruc option').attr("disabled", true);
                $('#cliente_id_ruc option[value="' + c_cliente_id + '"]').attr("disabled", false);

                $('#cliente_id_ruc').selectpicker('refresh');
            });

            $('#modal-historial').on('show.bs.modal', function (event) {
                //let c_cliente_id=$('#cliente_id').val();//
                //let c_ruc=$('#pruc').val();

                $('#tablaPrincipal').DataTable().clear().destroy();

                $('#tablaPrincipal').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: true,
                    "order": [
                        [0, "desc"]
                    ],
                    ajax: {
                        url: "{{ route('deudoresoncreate') }}",
                        data: function (d) {
                            //d.buscarpedidocliente = c_cliente_id;
                            //d.buscarpedidoruc = c_ruc;

                        },
                    },
                    "createdRow": function (row, data, dataIndex) {
                    },
                    "autoWidth": false,
                    rowCallback: function (row, data, index) {
                    },
                    columns: [/*{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        sWidth: '10%',
                    },*/
                        {
                            data: 'celular',
                            name: 'celular',
                            sWidth: '70%',
                            render: function (data, type, row, meta) {
                                return row.celular + " - " + row.nombre;
                            }
                        },
                        {
                            data: 'estado',
                            name: 'estado',
                            sWidth: '20%',
                            render: function (data, type, row, meta) {
                                return '<span class="badge badge-danger">Deudor</span>';
                            }
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
                //recree mi tabla

            });

            $('#modal-copiar').on('show.bs.modal', function (event) {

                //cargar informacion para copiar del pedido
                //consulta traer datos
                let string_copiar = $("#modal-copiar .textcode").html();
                //console.log(string_copiar)
                var fd = new FormData();
                fd.append('infocopiar', string_copiar); //4721
                //fd.append("infocopiar", string_copiar);

                $.ajax({
                    //async:false,
                    type: 'POST',
                    url: "{{ route('pedidos.infopdf') }}",
                    data: fd,
                    processData: false,
                    contentType: false,
                }).done(function (data) {
                    console.log(data);
                    //console.log(data.codigo);
                    let copydata = "*S/." + data.cantidad + " * " + data.porcentaje + "% = S/." +
                        data.ft + "*\n" +
                        "*ENVIO = S/." + data.courier + "*\n" +
                        "*TOTAL = S/." + data.total + "*\n\n" +
                        "*ES IMPORTANTE PAGAR EL ENVIO* \n";

                    $("#pedido_copiar").val(copydata);
                    $("#pedido_copiar_2").val(copydata).removeClass("d-none");

                    const textarea = document.createElement('textarea');
                    document.body.appendChild(textarea);
                    textarea.value = $("#pedido_copiar").val();
                    textarea.select();
                    textarea.setSelectionRange(0, 99999);
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                    $("#pedido_copiar").after("Copiado");

                    $("#formulario").trigger("reset");

                    $("#total").html("S/. 0.00");

                    //var rowCount = $('#detalles tr').length;
                    $("#detalles > tbody").empty();
                    //$("#fila" + index).remove();
                    $("#user_id").val("").trigger("change");
                    $("#cliente_id").val("").trigger("change");
                    $("#pruc").val(""); //.trigger("change");
                    $("#user_id").selectpicker("refresh");
                    //$("#cliente_id").selectpicker("refresh");
                    $("#pruc").selectpicker("refresh");
                    $("#pedido_copiar_2").val(copydata);
                    cont--;
                    evaluar();

                    $('#cerrar-modal-copiar').on('click', function () {
                        console.log("Test");
                        location.reload();
                    });

                });
            });

            $('#modal-historial-2').on('show.bs.modal', function (event) {
                let c_cliente_id = $('#cliente_id').val(); //
                let c_ruc = $('#pruc').val();

                $('#tablaPrincipalHistorial').DataTable().clear().destroy();
                $('#tablaPrincipalHistorial').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: true,
                    "order": [
                        [0, "desc"]
                    ],
                    ajax: {
                        url: "{{ route('pedidostablahistorial') }}",
                        data: function (d) {
                            d.buscarpedidocliente = c_cliente_id;
                            d.buscarpedidoruc = c_ruc;

                        },
                    },
                    "createdRow": function (row, data, dataIndex) {
                    },
                    "autoWidth": false,
                    rowCallback: function (row, data, index) {
                    },
                    columns: [

                        {
                            data: 'id',
                            name: 'id',
                            "visible": false,
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
                            data: 'descripcion',
                            name: 'descripcion',
                            sWidth: '70%',
                        },
                        {
                            data: 'nota',
                            name: 'nota',
                        },
                        {
                            data: 'adjunto',
                            name: 'adjunto',
                            render: function (data, type, row, meta) {
                                var str = "storage/pagos/" + data;
                                var urlimage = '{{ asset(':id') }}';

                                urlimage = urlimage.replace(':id', str);
                                data = '<img src="' + urlimage + '" alt="' + urlimage +
                                    '" height="200px" width="200px" class="img-thumbnail">';
                                return data
                            }

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
            });



            $("#formulario2").submit(function (event) {
                event.preventDefault();
                //console.log("fdormulario 2")
                var agregarruc = $("#agregarruc").val();
                if (agregarruc == '') {
                    Swal.fire(
                        'Error',
                        'Debe ingresar el número de RUC',
                        'warning'
                    )
                    return;
                } else if (agregarruc.length < 11) {
                    Swal.fire(
                        'Error',
                        'El número de RUC debe tener 11 dígitos',
                        'warning'
                    )
                    return;
                }

                var formData = $("#formulario2").serialize();
                //validar primero
                $.ajax({
                    type: 'POST',
                    url: "{{ route('validarrelacionruc') }}",
                    data: formData + '&user_id=' + $("#user_id").val(),
                }).done(function (data) {
                    //console.log(data.html);
                    var sese = data.html.split("|");
                    if (sese[0] == '1') {
                        $.ajax({
                            type: 'POST',
                            url: "{{ route('pedidos.agregarruc') }}",
                            data: formData,
                        }).done(function (data) {
                            // console.log(data.html);
                            if (data.html == 'true') {
                                //ya paso
                                Swal.fire(
                                    'Ruc registrado correctamente',
                                    '',
                                    'success'
                                );
                                $("#cliente_id").trigger("change");
                                $("#modal-add-ruc").modal("hide");
                            } else if (data.html == 'false') {
                                Swal.fire(
                                    'Se actualizo razon social',
                                    '',
                                    'success'
                                );
                                $("#cliente_id").trigger("change");
                                $("#modal-add-ruc").modal("hide");
                                //no paso
                            }

                        });

                    } else if (sese[0] == '0') {
                        //
                        if (sese[1] == 'A') {
                            Swal.fire(
                                'El ruc ya se encuentra relacionado con el asesor ' + sese[2],
                                '',
                                'warning'
                            );
                        } else if (sese[1] == "C") {
                            Swal.fire(
                                'El ruc ya se encuentra relacionado con el cliente ' + sese[2],
                                '',
                                'warning'
                            );
                        }

                    }

                });


            });

            $("form").keypress(function (e) {
                if (e.which == 13) {
                    return false;
                }
            });

        });
    </script>

    <script>
        $(document).ready(function () {

            /************************************
             * CARGAMOS LOS ASESORES EN EL COMBO
             * *********************************/
            $.ajax({
                type: 'POST',
                url: "{{ route('asesorcombo') }}",
            }).done(function (data) {
                $("#user_id").html('');
                $("#user_id").html(data.html);
                $("#user_id").selectpicker("refresh").trigger("change");
            });
            /************************************
             * PREVISUALIZAR PEDIDO
             * *********************************/

            $(document).on("click", "#prev", function (e) {
                console.log("Test");
                e.preventDefault()

                let pruc = $('#pruc').val();
                let pempresa = $('#pempresa').val();
                let pmes = $('#pmes').val();
                let panio = $('#panio').val();
                let pcantidad = $('#pcantidad').val();
                let ptipo_banca = $('#ptipo_banca').val();
                let pdescripcion = $('#pdescripcion').val();
                let pnota = $('#pnota').val();

                window.open("/pedidosPDFpreview2?pruc=" + pruc + "&pempresa=" + pempresa + "&pmes=" + pmes +
                    "&panio=" + panio + "&pcantidad=" + pcantidad + "&ptipo_banca=" + ptipo_banca +
                    "&pdescripcion=" + pdescripcion + "&pnota=" + pnota + "&fff=1");
            })
        });
    </script>
@stop
