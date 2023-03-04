<form id="form-agregarcontacto-n" name="form-agregarcontacto-n" class="agregarcontacto">
    <input type="hidden" id="agregarcontacto_n" name="agregarcontacto_n">
    <input type="hidden" name="opcion" value="1">
    <ul class="list-group">

        <li class="list-group-item text-wrap">
          <h6 class="alert alert-warning text-center font-weight-bold">
            <b>Elegir cliente <span class="text-danger">(Obligatorio):</span></b>
          </h6>

          <select name="cliente_agregarcontacto_n" class="border form-control selectpicker border-secondary bg-dark" id="cliente_agregarcontacto_n"
                  data-show-subtext="true" data-live-search="true"
                  data-live-search-placeholder="Seleccione cliente" title="Ningun cliente seleccionado">
          </select>
        </li>

        <li class="list-group-item text-wrap">
            <h6 class="alert alert-warning text-center font-weight-bold">
                <b>Numero de contacto <span class="text-danger">(Obligatorio):</span></b>
            </h6>
            <input name="nro_contacto-agregarcontacto_n" id="nro_contacto-agregarcontacto_n" class="form-control w-100"
                     style=" color: red; font-weight: bold; background: white; "  placeholder="Colocar num contacto">
        </li>

    </ul>
    <hr class="mt-2 mb-3"/>
    <div class="form-group col-lg-12">
        <button type="submit" class="float-right btn btn-success">Enviar</button>
    </div>

</form>
