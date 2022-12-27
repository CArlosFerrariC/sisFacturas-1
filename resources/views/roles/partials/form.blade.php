<div class="card-body">
  <div class="form-row">
    <div class="form-group col-lg-12">
      {!! Form::label('name', 'Nombre') !!}
      {!! Form::text('name', null, ['class' => 'form-control', 'placeholder' => 'Ingrese nombre del rol']) !!}
    </div>
    @error('name')
      <small class="text-danger">{{ $message }}</small>
    @enderror
  </div>

  <h2 class="h3">Lista de Permisos</h2>
<br>
  <div class="form-row">

    {{-- MODULO PERSONAS --}}
    <div class="form-group col-lg-8">
      <div class="mb-3 card border-secondary">
        <div class="card-header">
          @foreach ($permissions as $permission)
            @if ($permission->modulo == 'moduloPersonas')
              <div>
                <label>
                  {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                  {{ $permission->description }}
                </label>
              </div>
            @endif
          @endforeach
        </div>

        <div class="card-body text-secondary">
          <div class="form-row">
            {{-- CLIENTES --}}
            <div class="form-group col-lg-6">
              <div class="mb-3 card border-secondary">
                <div class="card-header">
                  @foreach ($permissions as $permission)
                    @if ($permission->modulo == 'BandejaClientes')
                      <div>
                        <label>
                          {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                          {{ $permission->description }}
                        </label>
                      </div>
                    @endif
                  @endforeach
                </div>
                <div class="card-body text-secondary">
                  @foreach ($permissions as $permission)
                    @if ($permission->modulo == 'clientes')
                      <div>
                        <label>
                          {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                          {{ $permission->description }}
                        </label>
                      </div>
                    @endif
                  @endforeach
                </div>
              </div>
            </div>

            {{-- BASE FRIA --}}
            <div class="form-group col-lg-6">
              <div class="mb-3 card border-secondary">
                <div class="card-header">
                  @foreach ($permissions as $permission)
                    @if ($permission->modulo == 'BandejaBasefria')
                      <div>
                        <label>
                          {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                          {{ $permission->description }}
                        </label>
                      </div>
                    @endif
                  @endforeach
                </div>
                <div class="card-body text-secondary">
                  @foreach ($permissions as $permission)
                    @if ($permission->modulo == 'Basefria')
                      <div>
                        <label>
                          {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                          {{ $permission->description }}
                        </label>
                      </div>
                    @endif
                  @endforeach
                </div>
              </div>
            </div>

          </div>

        </div>
      </div>
    </div>

    {{-- MODULO PEDIDO --}}
    <div class="form-group col-lg-4">
      <div class="mb-3 card border-secondary">
        <div class="card-header">
          @foreach ($permissions as $permission)
            @if ($permission->modulo == 'moduloPedidos')
              <div>
                <label>
                  {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                  {{ $permission->description }}
                </label>
              </div>
            @endif
          @endforeach
        </div>
        <div class="card-body text-secondary">
          <div class="form-row">
            {{-- PEDIDOS --}}
            <div class="form-group col-lg-12">
              <div class="mb-3 card border-secondary">
                <div class="card-header">
                  @foreach ($permissions as $permission)
                    @if ($permission->modulo == 'BandejaPedidos')
                      <div>
                        <label>
                          {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                          {{ $permission->description }}
                        </label>
                      </div>
                    @endif
                  @endforeach
                </div>
                <div class="card-body text-secondary">
                  @foreach ($permissions as $permission)
                    @if ($permission->modulo == 'Pedidos')
                      <div>
                        <label>
                          {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                          {{ $permission->description }}
                        </label>
                      </div>
                    @endif
                  @endforeach
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- MODULO CONFIGURACION --}}
    <div class="form-group col-lg-8">
      <div class="mb-3 card border-secondary">
        <div class="card-header">
          @foreach ($permissions as $permission)
            @if ($permission->modulo == 'moduloConfiguracion')
              <div>
                <label>
                  {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                  {{ $permission->description }}
                </label>
              </div>
            @endif
          @endforeach
        </div>
        <div class="card-body text-secondary">
          <div class="form-row">
            {{-- ROLES --}}
            <div class="form-group col-lg-6">
              <div class="mb-3 card border-secondary">
                <div class="card-header">
                  @foreach ($permissions as $permission)
                    @if ($permission->modulo == 'BandejaRoles')
                      <div>
                        <label>
                          {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                          {{ $permission->description }}
                        </label>
                      </div>
                    @endif
                  @endforeach
                </div>
                <div class="card-body text-secondary">
                  @foreach ($permissions as $permission)
                    @if ($permission->modulo == 'Roles')
                      <div>
                        <label>
                          {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                          {{ $permission->description }}
                        </label>
                      </div>
                    @endif
                  @endforeach
                </div>
              </div>
            </div>
            {{-- USUARIOS --}}
            <div class="form-group col-lg-6">
              <div class="mb-3 card border-secondary">
                <div class="card-header">
                  @foreach ($permissions as $permission)
                    @if ($permission->modulo == 'BandejaUsuarios')
                      <div>
                        <label>
                          {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                          {{ $permission->description }}
                        </label>
                      </div>
                    @endif
                  @endforeach
                </div>
                <div class="card-body text-secondary">
                  @foreach ($permissions as $permission)
                    @if ($permission->modulo == 'Usuarios')
                      <div>
                        <label>
                          {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                          {{ $permission->description }}
                        </label>
                      </div>
                    @endif
                  @endforeach
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- MODULO PAGOS --}}
    <div class="form-group col-lg-4">
      <div class="mb-3 card border-secondary">
        <div class="card-header">
          @foreach ($permissions as $permission)
            @if ($permission->modulo == 'moduloPagos')
              <div>
                <label>
                  {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                  {{ $permission->description }}
                </label>
              </div>
            @endif
          @endforeach
        </div>
        <div class="card-body text-secondary">
          <div class="form-row">
            {{-- PAGOS --}}
            <div class="form-group col-lg-12">
              <div class="mb-3 card border-secondary">
                <div class="card-header">
                  @foreach ($permissions as $permission)
                    @if ($permission->modulo == 'BandejaPagos')
                      <div>
                        <label>
                          {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                          {{ $permission->description }}
                        </label>
                      </div>
                    @endif
                  @endforeach
                </div>
                <div class="card-body text-secondary">
                  @foreach ($permissions as $permission)
                    @if ($permission->modulo == 'Pagos')
                      <div>
                        <label>
                          {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                          {{ $permission->description }}
                        </label>
                      </div>
                    @endif
                  @endforeach
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- MODULO OPERACIONES --}}
    <div class="form-group col-lg-4">
      <div class="mb-3 card border-secondary">
        <div class="card-header">
          @foreach ($permissions as $permission)
            @if ($permission->modulo == 'moduloOperacion')
              <div>
                <label>
                  {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                  {{ $permission->description }}
                </label>
              </div>
            @endif
          @endforeach
        </div>
        <div class="card-body text-secondary">
          <div class="form-row">
            {{-- PEDIDOS --}}
            <div class="form-group col-lg-12">
              <div class="mb-3 card border-secondary">
                <div class="card-header">
                  PEDIDOS EN OPERACIONES
                </div>
                <div class="card-body text-secondary">
                  @foreach ($permissions as $permission)
                    @if ($permission->modulo == 'Operacion')
                      <div>
                        <label>
                          {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                          {{ $permission->description }}
                        </label>
                      </div>
                    @endif
                  @endforeach
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- MODULO ENVIOS --}}
    <div class="form-group col-lg-4">
      <div class="mb-3 card border-secondary">
        <div class="card-header">
          @foreach ($permissions as $permission)
            @if ($permission->modulo == 'moduloEnvio')
              <div>
                <label>
                  {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                  {{ $permission->description }}
                </label>
              </div>
            @endif
          @endforeach
        </div>
        <div class="card-body text-secondary">
          <div class="form-row">
            {{-- ENVIOS --}}
            <div class="form-group col-lg-12">
              <div class="mb-3 card border-secondary">
                <div class="card-header">
                  @foreach ($permissions as $permission)
                    @if ($permission->modulo == 'BandejaEnvio')
                      <div>
                        <label>
                          {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                          {{ $permission->description }}
                        </label>
                      </div>
                    @endif
                  @endforeach
                </div>
                <div class="card-body text-secondary">
                  @foreach ($permissions as $permission)
                    @if ($permission->modulo == 'Envio')
                      <div>
                        <label>
                          {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                          {{ $permission->description }}
                        </label>
                      </div>
                    @endif
                  @endforeach
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- MODULO ADMINISTRACION --}}
    <div class="form-group col-lg-4">
      <div class="mb-3 card border-secondary">
        <div class="card-header">
          @foreach ($permissions as $permission)
            @if ($permission->modulo == 'moduloAdministracion')
              <div>
                <label>
                  {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                  {{ $permission->description }}
                </label>
              </div>
            @endif
          @endforeach
        </div>
        <div class="card-body text-secondary">
          <div class="form-row">
            {{-- PEDIDOS --}}
            <div class="form-group col-lg-12">
              <div class="mb-3 card border-secondary">
                <div class="card-header">
                  PAGOS EN ADMINISTRACION
                </div>
                <div class="card-body text-secondary">
                  @foreach ($permissions as $permission)
                    @if ($permission->modulo == 'Administracion')
                      <div>
                        <label>
                          {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                          {{ $permission->description }}
                        </label>
                      </div>
                    @endif
                  @endforeach
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- MODULO REPORTES --}}
    <div class="form-group col-lg-4">
      <div class="mb-3 card border-secondary">
        <div class="card-header">
          @foreach ($permissions as $permission)
            @if ($permission->modulo == 'moduloReportes')
              <div>
                <label>
                  {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                  {{ $permission->description }}
                </label>
              </div>
            @endif
          @endforeach
        </div>
        <div class="card-body text-secondary">
          <div class="form-row">
            {{-- REPORTES --}}
            <div class="form-group col-lg-12">
              <div class="mb-3 card border-secondary">
                <div class="card-header">
                  @foreach ($permissions as $permission)
                    @if ($permission->modulo == 'BandejaReportes')
                      <div>
                        <label>
                          {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                          {{ $permission->description }}
                        </label>
                      </div>
                    @endif
                  @endforeach
                </div>
                <div class="card-body text-secondary">
                  @foreach ($permissions as $permission)
                    @if ($permission->modulo == 'Reportes')
                      <div>
                        <label>
                          {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                          {{ $permission->description }}
                        </label>
                      </div>
                    @endif
                  @endforeach
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- MODULO RRHH --}}
    <div class="form-group col-lg-4">
        <div class="mb-3 card border-secondary">
            <div class="card-header">
                @foreach ($permissions as $permission)
                    @if ($permission->name == 'rrhh.ver')
                        <div>
                            <label>
                                {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                                {{ $permission->description }}
                            </label>
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="card-body text-secondary">
                @foreach ($permissions as $permission)
                    @if ($permission->name != 'rrhh.ver' && $permission->modulo == 'RRHH')
                        <div>
                            <label>
                                {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => 'mr-1']) !!}
                                {{ $permission->description }}
                            </label>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

  </div>

</div>
