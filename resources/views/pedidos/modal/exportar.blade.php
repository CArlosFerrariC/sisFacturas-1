<!-- Modal -->
<div class="modal fade" id="modal-exportar" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success">
        {{-- <h5 class="modal-title" id="exampleModalLabel">Exportar pedidos ENTREGADOS</h5> --}}
        <h5 class="modal-title" id="exampleModalLabel">{{ $title }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      {{-- {!! Form::open(['route' => ['entregadosporfechasexcel'], 'method' => 'POST', 'target' => 'blanck_']) !!} --}}
      @if($key === '1')
        {!! Form::open(['route' => ['pedidosporenviarExcel'], 'method' => 'POST', 'target' => 'blanck_']) !!}
      @elseif($key === '2')
        {!! Form::open(['route' => ['entregadosporfechasexcel'], 'method' => 'POST', 'target' => 'blanck_']) !!}
      @elseif($key === '3')
        {!! Form::open(['route' => ['pedidosExcel'], 'method' => 'POST', 'target' => 'blanck_']) !!}
      @elseif($key === '4')
        {!! Form::open(['route' => ['mispedidosExcel'], 'method' => 'POST', 'target' => 'blanck_']) !!}
      @elseif($key === '5')
        {!! Form::open(['route' => ['pedidospagadosExcel'], 'method' => 'POST', 'target' => 'blanck_']) !!}
      @elseif($key === '6')
        {!! Form::open(['route' => ['pedidossinpagosExcel'], 'method' => 'POST', 'target' => 'blanck_']) !!}
      @elseif($key === '7')
        {!! Form::open(['route' => ['pedidosporatenderExcel'], 'method' => 'POST', 'target' => 'blanck_']) !!}
      @elseif($key === '8')
        {!! Form::open(['route' => ['pedidosenatencionExcel'], 'method' => 'POST', 'target' => 'blanck_']) !!}
      @elseif($key === '9')
        {!! Form::open(['route' => ['pedidosatendidosExcel'], 'method' => 'POST', 'target' => 'blanck_']) !!}
      @elseif($key === '10')
        {!! Form::open(['route' => ['pedidosentregadosExcel'], 'method' => 'POST', 'target' => 'blanck_']) !!}

        @elseif($key === '11')
        {!! Form::open(['route' => ['estadosobresexcel'], 'method' => 'POST', 'target' => 'blanck_']) !!} 
      @endif

            <div class="card-body">
              <div class="form-row">
                <div class="form-group col-lg-12" style="text-align: center; font-size:16px">                  
                  <div class="form-row">
                    <div class="col-lg-12">
                      {!! Form::label('anio', 'Elija el rango de fechas del reporte') !!} <br><br>
                      <div class="form-row">
                        <div class="col-lg-6">
                          <label>Fecha inicial&nbsp;</label>
                          {!! Form::date('desde', \Carbon\Carbon::now(), ['class' => 'form-control']); !!}
                        </div>
                        <div class="col-lg-6">
                          <label>Fecha final&nbsp;</label>
                          {!! Form::date('hasta', \Carbon\Carbon::now(), ['class' => 'form-control']); !!}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>    
            <div class="card-footer">
              <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Consultar</button>
            </div>
      {!! Form::close() !!}
    </div>
  </div>
</div>