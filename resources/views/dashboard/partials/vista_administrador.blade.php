<div class="text-center mb-4" style="font-family:'Times New Roman', Times, serif">
    <h2>
        <p>
            Bienvenido <b>{{ Auth::user()->name }}</b> al software empresarial de Ojo Celeste, eres el
            <b>{{ Auth::user()->rol }} del sistema</b>
        </p>
    </h2>
</div>

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                @foreach ($pedidoxmes_total as $mpxm)
                    <h3>{{ $mpxm->total  }}</h3>
                @endforeach
                <p>META DE PEDIDOS DEL MES</p>
            </div>
            <div class="icon">
                <i class="ion ion-bag"></i>
            </div>
            <a href="{{ route('pedidos.index') }}" class="small-box-footer">Más info <i
                    class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                @foreach ($montopedidoxmes_total as $mcxm)
                    <h3>{{number_format( ($mcxm->total)/10 ,2)}} %</h3>
                @endforeach
                <p>META DE COBRANZAS DEL MES</p>
            </div>
            <div class="icon">
                <i class="ion ion-stats-bars"></i>
            </div>
            <a href="{{ route('pedidos.index') }}" class="small-box-footer">Más info <i
                    class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>


    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                @foreach ($pagoxmes_total as $pxm)
                    <h3>{{ $pxm->pedidos }}</h3>
                @endforeach
                <p>PEDIDOS DEL MES</p>
            </div>
            <div class="icon">
                <i class="ion ion-person-add"></i>
            </div>
            <a href="{{ route('pagos.index') }}" class="small-box-footer">Más info <i
                    class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>


    <div class="col-lg-3 col-6">
        <div class="small-box bg-default">
            <div class="inner">
                @foreach ($pagoxmes_total_solo_asesor_b as $pxm2)
                    <h3>{{ $pxm2->pedidos }}</h3>
                @endforeach
                <p>PEDIDOS DEL MES ASESOR B</p>
            </div>
            <div class="icon">
                <i class="ion ion-person-add"></i>
            </div>
            <a href="{{ route('pagos.index') }}" class="small-box-footer">Más info <i
                    class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>


    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                @foreach ($montopagoxmes_total as $cxm)
                    <h3>S/@php echo number_format( ($cxm->total)/1000 ,2) @endphp </h3>
                @endforeach
                <p>COBRANZAS DEL MES</p>
            </div>
            <div class="icon">
                <i class="ion ion-pie-graph"></i>
            </div>
            <a href="{{ route('pagos.index') }}" class="small-box-footer">Más info <i
                    class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-lg-9 col-12">

        <div class="small-box bg-secondary">
            <div class="inner">
                <h5> PEDIDOS CREADOS {{  \Carbon\Carbon::now()->format('d-m-Y') }}  <span class="badge badge-light">{{$_pedidos_totalpedidosdia}}</span></h5>
            </div>
            <div class="row">
                @foreach ($_pedidos as $pedido)
                    <div class="col-md-2 col-6">
                        <div class="p-4 border-top border-bottom">
                            <h5 class="text-center">ASESOR {{ $pedido->identificador }}</h5>
                            <h5 class="text-center"><b>{{ $pedido->total }}</b></h5>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>


    </div>
</div>


<div class="row">
    <div class="col-lg-6">
        <div class="card" style="background-color: #a5770f1a;">
            <div class="card-header">Buscar Cliente/RUC</div>
            <div class="card-header">
                <div class="row">
                    <div class="col-md-9">
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <div class="input-group-text p-0">
                                    <select id="input_search_type" class="form-control">
                                        <option value="CLIENTE">CLIENTE</option>
                                        <option value="RUC">RUC</option>
                                    </select>
                                </div>
                            </div>
                            <input id="input_search_cliente" class="form-control" maxlength="11"
                                   placeholder="Buscar cliente">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group mb-3">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-dark" id="buttom_search_cliente">
                                    <i class="fa fa-search"></i>
                                    Buscar
                                </button>
                                <button type="button" class="btn btn-light"
                                        id="buttom_search_cliente_clear">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div id="search_content_result">
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-12">
        <x-grafico-pedidos-elect-fisico></x-grafico-pedidos-elect-fisico>
    </div>
    <div class="col-lg-12">
        <x-grafico-metas-mes></x-grafico-metas-mes>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="d-flex justify-content-end align-items-center">
                        <div class="card my-2 mx-2">
                            @php
                                try {
                                     $currentDate=\Carbon\Carbon::createFromFormat('m-Y',request('selected_date',now()->format('m-Y')));
         }catch (Exception $ex){
                                     $currentDate=\Carbon\Carbon::createFromFormat('m-Y',now()->format('m-Y'));
         }

                            @endphp
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"> Seleccionar Mes</span>
                                </div>
                                <div class="input-group-prepend">
                                    <a href="{{route('dashboard.index',['selected_date'=>$currentDate->clone()->startOfMonth()->subYear()->format('m-Y')])}}"
                                       class="btn m-0 p-0"
                                       data-toggle="tooltip" data-placement="top" title="Un año atras">
                            <span class="input-group-text">
                                <
                            </span>
                                    </a>
                                    <a href="{{route('dashboard.index',['selected_date'=>$currentDate->clone()->startOfMonth()->subMonth()->format('m-Y')])}}"
                                       class="btn m-0 p-0"
                                       data-toggle="tooltip" data-placement="top" title="Un mes atras">
                                        <span class="input-group-text"><</span>
                                    </a>
                                </div>
                                <select class="form-control" id="datepickerDashborad"
                                        aria-describedby="basic-addon3">

                                    @foreach([1,2,3,4,5,6,7,8,9,10,11,12] as $month)
                                        @php
                                            $currentMonth=$currentDate->clone()->startOfYear()->addMonths($month-1);
                                        @endphp
                                        <option
                                            {{$currentMonth->format('m-Y')==request('selected_date',now()->format('m-Y'))?'selected':''}}
                                            value="{{$currentMonth->format('m-Y')}}"
                                        >{{Str::ucfirst($currentMonth->monthName)}} {{$currentMonth->year}}</option>
                                    @endforeach
                                </select>

                                <div class="input-group-append">
                                    <a href="{{route('dashboard.index',['selected_date'=>$currentDate->clone()->addMonths()->format('m-Y')])}}"
                                       class="btn m-0 p-0"
                                       data-toggle="tooltip" data-placement="top" title="Un mes adelante">
                                        <span class="input-group-text">></span>
                                    </a>
                                </div>
                                <div class="input-group-append">
                                    <a href="{{route('dashboard.index',['selected_date'=>$currentDate->clone()->addYear()->format('m-Y')])}}"
                                       class="btn m-0 p-0"
                                       data-toggle="tooltip" data-placement="top" title="Un año adelante">
                                        <span class="input-group-text">></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="row" id="widget-container">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4 pb-4">
                                    <ul class="list-group">
                                        <li class="list-group-item">
                                            <div class="row">
                                                <div class="col-md-9">
                                                    {{-- <x-grafico-meta-pedidos-progress-bar></x-grafico-meta-pedidos-progress-bar>--}}
                                                    <x-grafico-cobranzas-meses-progressbar></x-grafico-cobranzas-meses-progressbar>
                                                </div>
                                                <div class="col-md-3">
                                                    <x-grafico-pedidos-mes-count-progress-bar></x-grafico-pedidos-mes-count-progress-bar>
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-6">
                                <x-grafico-pedidos-atendidos-anulados></x-grafico-pedidos-atendidos-anulados>
                            </div>

                            <div class="col-lg-12">
                                <x-grafico-pedido_cobranzas-del-dia></x-grafico-pedido_cobranzas-del-dia>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <x-grafico-pedidos-por-dia rol="Administrador"
                                                   title="Cantidad de pedidos de los asesores por dia"
                                                   label-x="Asesores" label-y="Cant. Pedidos"
                                                   only-day></x-grafico-pedidos-por-dia>

                        <x-grafico-pedidos-por-dia rol="Administrador"
                                                   title="Cantidad de pedidos de los asesores por mes"
                                                   label-x="Asesores"
                                                   label-y="Cant. Pedidos"></x-grafico-pedidos-por-dia>
                    </div>
                </div>
            </div>
            {{--
            <div class="col-lg-12 col-sm-12 col-md-12 col-xs-12">
                <div class="card">
                    <div class="card-body">
                        <div class="chart tab-pane active w-100" id="pedidosxasesor" style="height: 550px;"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12 col-sm-12 col-md-12 col-xs-12 d-none">
                <div class="card ">
                    <div class="card-body">
                        <div class="chart tab-pane active w-100" id="cobranzaxmes" style="height: 550px; "></div>
                    </div>
                </div>
            </div> --}}
        </div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 col-sm-12 col-md-12 col-xs-12">
                <x-grafico-top-clientes-pedidos top="10"></x-grafico-top-clientes-pedidos>
            </div>
            <div class="col-lg-12 col-sm-12 col-md-12 col-xs-12 d-none">
                <div class="card">
                    <div class="card-body">
                        <div id="pagosxmes" class="w-100" style="height: 550px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
{{-- @include('dashboard.modal.alerta') --}}
