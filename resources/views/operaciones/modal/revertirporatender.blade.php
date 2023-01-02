<!-- Modal -->
<div class="modal fade" id="modal-revertir-poratender" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success">
        <h5 class="modal-title" id="exampleModalLabel">Revertir pedido</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="formulariorevertirporatender" name="formulariorevertirporatender" enctype="multipart/form-data">
      <input type="hidden" id="hiddenRevertirpedidoporatender" name="hiddenRevertirpedidoporatender">
      <div class="modal-body">
        <p>Confirme si desea revertir el pedido <strong class="textcode">PED000</strong></p>
        <p>Recuerde que tiene <strong class="textcantadjunto"></strong> archivos adjunto(s)</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        <button type="submit" class="btn btn-success">Confirmar</button>
      </div>
      {{ Form::Close() }}
    </div>
  </div>
</div>
