<!-- Modal -->
<div class="modal fade" id="modal-direccion_crearpedido" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title" id="exampleModalLabel"><b>Direccion destino para Pedido</b></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 card_form ">
                        <div class="row">
                            <div class="col-md-12">
                                <form id="formrecojo" name="formrecojo" role="form">
                                    <input type="hidden" id="recojo_cliente" name="recojo_cliente">
                                    <input type="hidden" id="recojo_pedido" name="recojo_pedido">

                                    <div class="form-row">

                                        <div class="form-group col-md-6">
                                            <label for="recojo_cliente_name">Cliente</label>
                                            <input type="text" class="form-control" id="recojo_cliente_name" placeholder="Cliente" readonly>
                                        </div>
                                    </div>



                                    <div class="form-row ">
                                        <div class="form-group col-md-6">
                                            <label for="recojo_destino">Destino</label>
                                            <select class="form-control" id="recojo_destino">
                                                <option value="LIMA">LIMA</option>
                                                <option value="OLVA">OLVA</option>
                                            </select>
                                        </div>

                                        <div class="form-group col-md-6">
                                            {!! Form::label('distrito', 'Distrito') !!}<br>
                                            <select name="distrito_recoger" id="distrito_recoger" class="distrito_recoger form-control"
                                                    data-show-subtext="true" data-live-search="true"
                                                    data-live-search-placeholder="Seleccione distrito" title="Seleccione distrito">
                                                @foreach($distritos_recojo as $distrito)
                                                    <option data-subtext="{{$distrito->zona}}"
                                                            value="{{$distrito->distrito}}">{{($distrito->distrito) }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group col-md-6 mt-4">
                                            <button type="button" class="btn-charge-history btn btn-info">Cargar de Historial</button>
                                        </div>

                                    </div>

                                    <div class="form-row datos_direccion">
                                        <div class="form-group col-md-6">
                                            <label for="recojo_pedido_quienrecibe_nombre">Nombre Recibe</label>
                                            <input required type="text" class="form-control" id="env_pedido_quienrecibe_nombre" placeholder="Quien recibe" autocomplete="off" >
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="recojo_pedido_quienrecibe_celular">Celular recibe</label>
                                            <input required type="text" class="form-control" id="env_pedido_quienrecibe_celular" maxlength="9" placeholder="Celular de quien recibe" autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="form-row datos_direccion">

                                        <div class="form-group col-md-12">
                                            <label for="recojo_pedido_direccion">Direccion</label>
                                            <textarea required class="form-control" id="env_pedido_direccion" name="env_pedido_direccion" rows="3"></textarea>
                                        </div>

                                        <div class="form-group col-md-6">
                                            <label for="recojo_pedido_referencia">Referencia</label>
                                            <input type="text" class="form-control" id="env_pedido_referencia" name="env_pedido_referencia" placeholder="Referencia" autocomplete="off">
                                        </div>

                                        <div class="form-group col-md-6">
                                            <label for="recojo_pedido_observacion">Observacion</label>
                                            <input type="text" class="form-control" id="env_pedido_observacion" name="env_pedido_observacion" placeholder="Observacion" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">Registrar direccion</button>
                                    </div>
                                </form>

                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="modal-footer">
                {{--<a href="{{ route('pedidos.sinpagos') }}" class="btn btn-danger btn-sm">Ver deudores</a>--}}
                <button type="button" class="btn btn-dark btn-sm" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
