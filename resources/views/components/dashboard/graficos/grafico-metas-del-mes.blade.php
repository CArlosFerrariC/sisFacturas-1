<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-center">
                    <ul class="list-group">
                        <li class="list-group-item">
                            <h4 class="text-center"><b>Meta del mes</b></h4>
                        </li>
                        <li class="list-group-item">
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-group">
                                        <li class="list-group-item" style=" min-width: 300px; ">
                                            <h5>Cobranzas {{$now_submonth->monthName}}</h5>
                                        </li>
                                        <li class="list-group-item" style=" background-color: #b7b7b7; ">
                                            <b>General </b>
                                            <div class="progress">
                                                <div class="progress-bar
                @if($data_noviembre->progress<40)
                 bg-danger
                 @elseif($data_noviembre->progress<70)
                 bg-warning
                  @else
                  bg-success
                  @endif
                 "
                                                     role="progressbar"
                                                     style="width: {{$data_noviembre->progress}}%"
                                                     aria-valuenow="{{$data_noviembre->progress}}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100">
                                                    <span> <b>  {{$data_noviembre->progress}}%</b> - {{$data_noviembre->current}}/{{$data_noviembre->total}}</span>
                                                </div>
                                            </div>
                                        </li>
                                        @foreach($novResult as $data)
                                            <li class="list-group-item">
                                                <b>{{$data['code']}}</b> <br> {{$data['name']}}
                                                <div class="progress">
                                                    <div class="progress-bar
                @if($data['progress']<40)
                 bg-danger
                 @elseif($data['progress']<70)
                 bg-warning
                  @else
                  bg-success
                  @endif
                 "
                                                         role="progressbar"
                                                         style="width: {{$data['progress']}}%"
                                                         aria-valuenow="{{$data['progress']}}"
                                                         aria-valuemin="0"
                                                         aria-valuemax="100">
                                                        <span> <b>{{$data['progress']}}%</b> - {{$data['current']}}/{{$data['total']}}</span>
                                                    </div>
                                                </div>
                                                <span>
                                    % - Asignados / Meta
                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-group">
                                        <li class="list-group-item" style=" min-width: 300px; ">
                                            <h4>Pedidos {{$now->monthName}}</h4>
                                        </li>
                                        <li class="list-group-item" style=" background-color: #b7b7b7; ">
                                            <b>General </b>
                                            <div class="progress">
                                                <div class="progress-bar
                @if($data_diciembre->progress<40)
                 bg-danger
                 @elseif($data_diciembre->progress<70)
                 bg-warning
                  @else
                  bg-success
                  @endif
                 "
                                                     role="progressbar"
                                                     style="width: {{$data_diciembre->progress}}%"
                                                     aria-valuenow="{{$data_diciembre->progress}}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100">
                                                    <span> <b>{{$data_diciembre->progress}}%</b> - {{$data_diciembre->total}}/{{$data_diciembre->meta}}</span>
                                                </div>
                                            </div>
                                        </li>
                                        @foreach($dicResult as $data)
                                            <li class="list-group-item">
                                                <b>{{$data['code']}}</b> <br> {{$data['name']}}
                                                <div class="progress">
                                                    <div class="progress-bar
                @if($data['progress']<40)
                 bg-danger
                 @elseif($data['progress']<70)
                 bg-warning
                  @else
                  bg-success
                  @endif
                 "
                                                         role="progressbar"
                                                         style="width: {{$data['progress']}}%"
                                                         aria-valuenow="{{$data['progress']}}"
                                                         aria-valuemin="0"
                                                         aria-valuemax="100">
                                                        <span><b>{{$data['progress']}}%</b> - {{$data['total']}}/{{$data['meta']}}</span>
                                                    </div>
                                                </div>
                                                <span>
                                    % - Asignados / Meta
                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('css')
    <style>
        .list-group .list-group-item{
            background: #a5770f1a;
        }
    </style>
@endpush
