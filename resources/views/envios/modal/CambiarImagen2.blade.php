  <!-- Modal -->
  <div class="modal fade" id="modal-cambiar-imagen2" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header bg-primary">
          <h5 class="modal-title" id="exampleModalLabel">Cambiar Imagen 2</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        
        <div class="modal-body">

        <input type="text" value="" name="DPConciliar" id="DPConciliar">
        <input type="text" value="" name="DPitem" id="DPitem">

          <div class="form-row">
            
            <div class="form-group col-lg-6">
              <div class="image-wrapper">
                <img id="picture" src="{{asset('imagenes/logo_facturas.png')}}" alt="Imagen del pago" width="250px">
              </div>
            </div>

            <div class="form-group col-lg-6">
              {!! Form::label('pimagen', 'Imagen') !!}  
              {!! Form::file('pimagen', ['class' => 'form-control-file', 'accept' => 'image/*']) !!}
            </div>
            
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" data-dismiss="modal">Cerrar</button>
          <button type="button" class="btn btn-info" id="change_imagen2" >Agregar</button>
        </div>
        
      </div>
    </div>
  </div>
