<form id="form-correccionpedido-g" name="form-correccionpedido-g" class="correccion">
    <input type="hidden" id="correccion_g" name="correccion_g">
    <input type="hidden" name="opcion" value="3">
    <ul class="list-group">
        <li class="list-group-item text-wrap">
            <h6 class="alert alert-warning text-center font-weight-bold">
                Sustento por Pedido Completo
                <span class="text-danger">(Obligatorio)</span>
            </h6>
            <textarea name="sustento-g" class="form-control w-100"
                      rows="3" style=" color: red; font-weight: bold; background: white; " required>response.sustento</textarea>
        </li>

        <li class="list-group-item text-wrap">
            <h6 class="alert alert-warning text-center font-weight-bold">
                Adjuntos de las guias <span class="text-danger">(Obligatorio)</span>
            </h6>
            <input type="file" name="correcion_g_adjuntos" id="correcion_f_adjuntos"  class="form-control" placeholder="">
        </li>

        <li class="list-group-item text-wrap">
            <h6 class="alert alert-warning text-center font-weight-bold">Detalle o cambio que se va a realizar</h6>
            <textarea name="detalle-g" class="form-control w-100"
                      rows="3" style=" color: red; font-weight: bold; background: white; " required>response.detalle</textarea>
        </li>


    </ul>
    <hr class="mt-2 mb-3"/>
    <div class="form-group col-lg-12">
        <button type="submit" class="float-right btn btn-success">Enviar</button>
    </div>

</form>
