<form id="form-agregarcontacto-cnu" name="form-agregarcontacto-cnu" class="agregarcontacto">
    <input type="hidden" id="agregarcontacto_cnu" name="agregarcontacto_cnu">
    <input type="hidden" name="opcion" value="3">
    <ul class="list-group">
        <li class="list-group-item text-wrap">
          <h6 class="alert alert-warning text-center font-weight-bold">
            <b>Elegir cliente <span class="text-danger">(Obligatorio):</span></b>
          </h6>

          <select name="cliente_agregarcontacto_cnu" class="border form-control selectpicker border-secondary bg-dark" id="cliente_agregarcontacto_cnu"
                  data-show-subtext="true" data-live-search="true"
                  data-live-search-placeholder="Seleccione cliente" title="Ningun cliente seleccionado">
          </select>
        </li>

        <li class="list-group-item text-wrap">
            <h6 class="alert alert-warning text-center font-weight-bold">
                Sustento por Pedido Completo
                <span class="text-danger">(Obligatorio)</span>
            </h6>
            <textarea name="sustento-g" class="form-control w-100"
                      rows="3" style=" color: red; font-weight: bold; background: white; "  placeholder="Colocar sustento"></textarea>
        </li>

        <li class="list-group-item text-wrap">
            <h6 class="alert alert-warning text-center font-weight-bold">
                Adjuntos de las guias <span class="text-danger">(Obligatorio)</span>
            </h6>
            <input type="file" name="correcion_g_adjuntos[]" id="correcion_g_adjuntos"  class="form-control" placeholder="" multiple>
        </li>

        <li class="list-group-item text-wrap">
            <h6 class="alert alert-warning text-center font-weight-bold">
                <b>Detalle o cambio que se va a realizar<span class="text-danger">(Obligatorio)</span></b>
            </h6>
            <textarea name="detalle-g" class="form-control w-100"
                      rows="3" style=" color: red; font-weight: bold; background: white; "  placeholder="Colocar detalle"></textarea>
        </li>


    </ul>
    <hr class="mt-2 mb-3"/>
    <div class="form-group col-lg-12">
        <button type="submit" class="float-right btn btn-success">Enviar</button>
    </div>

</form>
