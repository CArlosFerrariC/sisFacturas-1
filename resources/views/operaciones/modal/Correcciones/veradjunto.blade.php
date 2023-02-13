  <!-- Modal -->
  <div class="modal fade" id="modalcorreccion-veradjunto" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 500px!important;">
      <div class="modal-content">
        <div class="modal-header bg-primary">
          <h5 class="modal-title" id="exampleModalLabel">Ver adjuntos de la correccion</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p class="d-none">Adjuntos del pedido: <strong class="textcode">PED00</strong></p>
          <p>Cantidad de adjuntos: <strong class="textcountadj">0</strong></p>

            <div style="margin: 10px">
                <div class="card">
                    <div class="border rounded card-body border-secondary">
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="row">
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            {!! Form::label('envio_doc', 'Documento(s) adjuntado(s) del pedido') !!}

                                            <div id="imagenes_adjunto"></div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
