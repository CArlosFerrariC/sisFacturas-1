<div class="border rounded card-body border-secondary">
  <div class="card-body">
    <div class="form-row">{{-- '0' => 'Base fría',  --}}
      <div class="form-group col-lg-6">
        {!! Form::label('tipo', 'Tipo de cliente') !!}
          <input type="hidden" name="tipo" requerid value="1" class="form-control">
          <input type="text" name="cliente" value="Cliente" class="form-control" disabled>
        @error('tipo')
          <small class="text-danger">{{ $message }}</small>
        @enderror
      </div>
      <div class="form-group col-lg-6">
        {!! Form::label('user_id', 'Asesor*') !!}
        @if(Auth::user()->rol == "Asesor")
        {!! Form::text('muser_id', Auth::user()->identificador, ['class' => 'form-control', 'id' => 'muser_id', 'disabled']) !!}
        {!! Form::hidden('user_id', Auth::user()->id, ['class' => 'form-control', 'id' => 'user_id']) !!}
        @else
        {!! Form::select('user_id', $users, null, ['class' => 'form-control selectpicker border border-secondary', 'id' => 'user_id','data-live-search' => 'true', 'placeholder' => '---- SELECCIONE USUARIO ----']) !!}
        @endif
        @error('user_id')
          <small class="text-danger">{{ $message }}</small>
        @enderror
      </div>
      <div class="form-group col-lg-6">
        {!! Form::label('nombre', 'Nombre*') !!}
        {!! Form::text('nombre', null, ['class' => 'form-control', 'id' => 'nombre']) !!}
        @error('nombre')
          <small class="text-danger">{{ $message }}</small>
        @enderror
      </div>
      <div class="form-group col-lg-3">
        {!! Form::label('dni', 'DNI') !!}
        {!! Form::number('dni', null, ['class' => 'form-control', 'id' => 'dni', 'min' =>'0', 'max' => '99999999', 'maxlength' => '8', 'oninput' => 'maxLengthCheck(this)']) !!}
        @error('dni')
          <small class="text-danger">{{ $message }}</small>
        @enderror
      </div>
      <div class="form-group col-lg-3">
        {!! Form::label('celular', 'Celular*') !!}
        {!! Form::number('celular', null, ['class' => 'form-control', 'id' => 'celular', 'min' =>'0', 'max' => '999999999', 'maxlength' => '9', 'oninput' => 'maxLengthCheck(this)']) !!}
        @error('celular')
          <small class="text-danger">{{ $message }}</small>
        @enderror
      </div>
      <div class="form-group col-lg-6">
        {!! Form::label('provincia', 'Provincia*') !!}
        {!! Form::text('provincia', null, ['class' => 'form-control', 'id' => 'provincia']) !!}
        @error('provincia')
          <small class="text-danger">{{ $message }}</small>
        @enderror
        <br>
        {!! Form::label('distrito', 'Distrito*') !!}
        {!! Form::text('distrito', null, ['class' => 'form-control', 'id' => 'distrito']) !!}
        @error('distrito')
          <small class="text-danger">{{ $message }}</small>
        @enderror
        <br>
        {!! Form::label('direccion', 'Dirección*') !!}
        {!! Form::text('direccion', null, ['class' => 'form-control', 'id' => 'direccion']) !!}
        @error('direccion')
          <small class="text-danger">{{ $message }}</small>
        @enderror
        <br>
        {!! Form::label('referencia', 'Referencia*') !!}
        {!! Form::text('referencia', null, ['class' => 'form-control', 'id' => 'referencia']) !!}
        @error('referencia')
          <small class="text-danger">{{ $message }}</small>
        @enderror
      </div>

      <div class="form-group col-lg-6">
        {!! Form::label('porcentaje', 'Porcentaje') !!}
        <table id="tabla_pagos" class="table table-striped">
          <thead class="bg-primary">
            <tr>
              <th scope="col">ITEM</th>
              <th scope="col"></th>
              <th scope="col">TIPO</th>
              <th scope="col">%</th>
            </tr>
          </thead>
          <tfoot>
          </tfoot>
          <tbody>
            <tr class="selected" id="filas2">
              <td>1</td>
              <td><input type="hidden" name="nombreporcentaje[]" requerid value="FISICO - sin banca" class="form-control"></td>
              <td><input type="text" name="nporcentaje[]" value="FISICO - sin banca" disabled class="form-control"></td>
              <td><input type="number" step="0.1" name="porcentaje[]" id="porcentaje2" value="0" min="0" class="form-control"></td>
            </tr>
            <tr class="selected" id="filas1">
              <td>2</td>
              <td><input type="hidden" name="nombreporcentaje[]" requerid value="FISICO - banca" class="form-control"></td>
              <td><input type="text" name="nporcentaje[]" value="FISICO - banca" disabled class="form-control"></td>
              <td><input type="number" step="0.1" name="porcentaje[]" id="porcentaje1" value="0" min="0" class="form-control"></td>
            </tr>
            <tr class="selected" id="filas4">
              <td>3</td>
              <td><input type="hidden" name="nombreporcentaje[]" requerid value="ELECTRONICA - sin banca" class="form-control"></td>
              <td><input type="text" name="nporcentaje[]" value="ELECTRONICA - sin banca" disabled class="form-control"></td>
              <td><input type="number" step="0.1" name="porcentaje[]" id="porcentaje4" value="0" min="0" class="form-control"></td>
            </tr>
            <tr class="selected" id="filas3">
              <td>4</td>
              <td><input type="hidden" name="nombreporcentaje[]" requerid value="ELECTRONICA - banca" class="form-control"></td>
              <td><input type="text" name="nporcentaje[]" value="ELECTRONICA - banca" disabled class="form-control"></td>
              <td><input type="number" step="0.1" name="porcentaje[]" id="porcentaje3" value="0" min="0" class="form-control"></td>
            </tr>

          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
