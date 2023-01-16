<?php

namespace App\Http\Controllers;

use App\Events\PedidoAtendidoEvent;
use App\Events\PedidoEntregadoEvent;
use App\Events\PedidoEvent;
use App\Models\Cliente;
use App\Models\Departamento;
use App\Models\DetallePago;
use App\Models\DetallePedido;
use App\Models\DireccionEnvio;
use App\Models\DireccionGrupo;
use App\Models\DireccionPedido;
use App\Models\Distrito;
use App\Models\GastoEnvio;
use App\Models\GastoPedido;
use App\Models\GrupoPedido;
use App\Models\ImagenAtencion;
use App\Models\ImagenPedido;
use App\Models\PedidoMotorizadoHistory;
use App\Models\User;
use App\Models\Pedido;
use App\Models\Porcentaje;
use App\Models\Provincia;
use App\Models\Ruc;
use App\Models\PedidoMovimientoEstado;
use App\Notifications\PedidoNotification;
use Carbon\Carbon;
use Exception;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

use Facade\FlareClient\Http\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDF;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Validator;
use DataTables;

class EnvioController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function Envioscondireccion()//SOBRES EN REPARTO
    {

        $distribuir = [
            "NORTE" => 'NORTE',
            "CENTRO" => 'CENTRO',
            "SUR" => 'SUR',
        ];

        $condiciones = [
            "PENDIENTE DE ENVIO" => 'PENDIENTE DE ENVIO',
            "EN REPARTO" => 'EN REPARTO',
            "ENTREGADO" => 'ENTREGADO'
        ];

        $destinos = [
            "LIMA" => 'LIMA',
            "PROVINCIA" => 'PROVINCIA'
        ];

        $distritos = Distrito::whereIn('provincia', ['LIMA', 'CALLAO'])
            ->where('estado', '1')
            ->pluck('distrito', 'distrito');

        $departamento = Departamento::where('estado', "1")
            ->pluck('departamento', 'departamento');

        $direcciones = DireccionEnvio::join('direccion_pedidos as dp', 'direccion_envios.id', 'dp.direccion_id')
            ->select('direccion_envios.id',
                'direccion_envios.distrito',
                'direccion_envios.direccion',
                'direccion_envios.referencia',
                'direccion_envios.nombre',
                'direccion_envios.celular',
                'dp.pedido_id as pedido_id',
            )
            ->where('direccion_envios.estado', '1')
            ->where('dp.estado', '1')
            ->get();

        $superasesor = User::where('rol', 'Super asesor')->count();

        $ver_botones_accion = 1;

        if (Auth::user()->rol == "Asesor") {
            $ver_botones_accion = 0;
        } else if (Auth::user()->rol == "Super asesor") {
            $ver_botones_accion = 0;
        } else if (Auth::user()->rol == "Encargado") {
            $ver_botones_accion = 1;
        } else {
            $ver_botones_accion = 1;
        }

        return view('envios.condireccion', compact('condiciones', 'distritos', 'direcciones', 'destinos', 'superasesor', 'ver_botones_accion', 'departamento', 'distribuir'));
    }

    public function Envioscondirecciontabla(Request $request)
    {
        $pedidos = null;

        $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
            ->join('users as u', 'pedidos.user_id', 'u.id')
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->select(
                [
                    'pedidos.*',
                    'c.nombre as nombres',
                    'c.icelular as icelulares',
                    'c.celular as celulares',
                    'u.identificador',
                    'dp.nombre_empresa as empresas',
                ]
            )->where('condicion_envio_code', Pedido::RECEPCION_COURIER_INT)
            ->where('estado_sobre', '1');

        return Datatables::of($pedidos)
            ->addIndexColumn()
            ->editColumn('condicion_envio', function ($pedido) {
                $badge_estado = '';
                $color = Pedido::getColorByCondicionEnvio($pedido->condicion_envio);
                if ($pedido->estado_sobre == '1') {
                    $badge_estado .= '<span class="badge badge-dark p-8" style="color: #fff; background-color: #347cc4; font-weight: 600; margin-bottom: -2px;border-radius: 4px 4px 0px 0px; font-size:8px;  padding: 4px 4px !important;">Direccion agregada</span>';
                }

                $badge_estado .= '<span class="badge badge-success" style="background-color: ' . $color . '!important;">' . $pedido->condicion_envio . '</span>';
                return $badge_estado;
            })
            ->addColumn('action', function ($pedido) {
                $btn = '';

                if (auth()->user()->can('envios.enviar')):

                    $btn .= '<ul class="list-unstyled pl-0">';
                    $btn .= '<li>
                                        <a href="" class="btn-sm text-secondary" data-target="#modal-confirmacion" data-toggle="modal" data-ide="' . $pedido->id . '" data-entregar-confirm="' . $pedido->id . '" data-destino="' . $pedido->destino . '" data-fechaenvio="' . $pedido->fecha . '" data-codigos="' . $pedido->codigos . '">
                                            <i class="fas fa-envelope text-success"></i> A motorizado</a></li>
                                        </a>
                                    </li>';
                    $btn .= '</ul>';
                endif;

                return $btn;
            })
            ->rawColumns(['action', 'condicion_envio'])
            ->make(true);
    }

    public function Enviossindireccion()//SOBRES EN REPARTO
    {

        $distribuir = [
            "NORTE" => 'NORTE',
            "CENTRO" => 'CENTRO',
            "SUR" => 'SUR',
        ];

        $condiciones = [
            "PENDIENTE DE ENVIO" => 'PENDIENTE DE ENVIO',
            "EN REPARTO" => 'EN REPARTO',
            "ENTREGADO" => 'ENTREGADO'
        ];

        $destinos = [
            "LIMA" => 'LIMA',
            "PROVINCIA" => 'PROVINCIA'
        ];

        $distritos = Distrito::whereIn('provincia', ['LIMA', 'CALLAO'])
            ->where('estado', '1')
            ->pluck('distrito', 'distrito');

        $departamento = Departamento::where('estado', "1")
            ->pluck('departamento', 'departamento');

        $direcciones = DireccionEnvio::join('direccion_pedidos as dp', 'direccion_envios.id', 'dp.direccion_id')
            ->select('direccion_envios.id',
                'direccion_envios.distrito',
                'direccion_envios.direccion',
                'direccion_envios.referencia',
                'direccion_envios.nombre',
                'direccion_envios.celular',
                'dp.pedido_id as pedido_id',
            )
            ->where('direccion_envios.estado', '1')
            ->where('dp.estado', '1')
            ->get();

        $superasesor = User::where('rol', 'Super asesor')->count();

        $ver_botones_accion = 1;

        if (Auth::user()->rol == "Asesor") {
            $ver_botones_accion = 0;
        } else if (Auth::user()->rol == "Super asesor") {
            $ver_botones_accion = 0;
        } else if (Auth::user()->rol == "Encargado") {
            $ver_botones_accion = 1;
        } else {
            $ver_botones_accion = 1;
        }

        return view('envios.sindireccion', compact('condiciones', 'distritos', 'direcciones', 'destinos', 'superasesor', 'ver_botones_accion', 'departamento', 'distribuir'));
    }

    public function Enviossindirecciontabla(Request $request)
    {
        $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
            ->join('users as u', 'pedidos.user_id', 'u.id')
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->select(
                [
                    'pedidos.*',
                    'c.nombre as nombres',
                    'c.icelular',
                    'c.celular',
                    'u.identificador',
                    'dp.nombre_empresa as empresas',
                ]
            )->where('condicion_envio_code', Pedido::RECEPCION_COURIER_INT)
            ->activo(1)
            ->where('estado_sobre', '0');

        return Datatables::of($pedidos)
            ->addIndexColumn()
            ->editColumn('condicion_envio', function ($pedido) {
                $color = Pedido::getColorByCondicionEnvio($pedido->condicion_envio);
                return '<span class="badge badge-success" style="background-color: ' . $color . '!important;">' . $pedido->condicion_envio . '</span>';
            })
            ->editColumn('fecha_recepcion_courier', function ($pedido) {
                if ($pedido->fecha_recepcion_courier) {
                    return Carbon::parse($pedido->fecha_recepcion_courier)->format('d-m-Y h:i A');
                }
                return '--';
            })
            ->addColumn('dias', function ($pedido) {
                if ($pedido->fecha_recepcion_courier) {
                    return Carbon::parse($pedido->fecha_recepcion_courier)->diffInDays() . ' dias';
                }
                return '--';
            })
            ->addColumn('action', function ($pedido) {
                $btn = '';

                if (auth()->user()->can('envios.enviar')):

                    $btn .= '<ul class="list-unstyled pl-0">';
                    $btn .= '<li>
                                        <button class="btn btn-sm text-secondary" data-target="' . route('operaciones.confirmarentregasinenvio', ['hiddenCodigo' => $pedido->codigo]) . '" data-toggle="jqconfirm">
                                            <i class="fas fa-envelope text-danger"></i> Entregado sin envio
                                        </button>
                                    </li>';
                    $btn .= '</ul>';
                endif;

                return $btn;
            })
            ->rawColumns(['action', 'condicion_envio'])
            ->make(true);
    }

    public function Enviosparareparto()//SOBRES EN REPARTO
    {

        $distribuir = [
            "NORTE" => 'NORTE',
            "CENTRO" => 'CENTRO',
            "SUR" => 'SUR',
        ];

        $condiciones = [
            "PENDIENTE DE ENVIO" => 'PENDIENTE DE ENVIO',
            "EN REPARTO" => 'EN REPARTO',
            "ENTREGADO" => 'ENTREGADO'
        ];

        $destinos = [
            "LIMA" => 'LIMA',
            "PROVINCIA" => 'PROVINCIA'
        ];

        $distritos = Distrito::whereIn('provincia', ['LIMA', 'CALLAO'])
            ->where('estado', '1')
            ->pluck('distrito', 'distrito');

        $departamento = Departamento::where('estado', "1")
            ->pluck('departamento', 'departamento');

        $direcciones = DireccionEnvio::join('direccion_pedidos as dp', 'direccion_envios.id', 'dp.direccion_id')
            ->select('direccion_envios.id',
                'direccion_envios.distrito',
                'direccion_envios.direccion',
                'direccion_envios.referencia',
                'direccion_envios.nombre',
                'direccion_envios.celular',
                'dp.pedido_id as pedido_id',
            )
            ->where('direccion_envios.estado', '1')
            ->where('dp.estado', '1')
            ->get();

        $superasesor = User::where('rol', 'Super asesor')->count();

        $ver_botones_accion = 1;

        if (Auth::user()->rol == "Asesor") {
            $ver_botones_accion = 0;
        } else if (Auth::user()->rol == "Super asesor") {
            $ver_botones_accion = 0;
        } else if (Auth::user()->rol == "Encargado") {
            $ver_botones_accion = 1;
        } else {
            $ver_botones_accion = 1;
        }

        return view('envios.paraReparto', compact('condiciones', 'distritos', 'direcciones', 'destinos', 'superasesor', 'ver_botones_accion', 'departamento', 'distribuir'));
    }

    public function Enviosenreparto()//SOBRES EN REPARTO
    {

        $distribuir = [
            "NORTE" => 'NORTE',
            "CENTRO" => 'CENTRO',
            "SUR" => 'SUR',
        ];

        $condiciones = [
            "PENDIENTE DE ENVIO" => 'PENDIENTE DE ENVIO',
            "EN REPARTO" => 'EN REPARTO',
            "ENTREGADO" => 'ENTREGADO'
        ];

        $destinos = [
            "LIMA" => 'LIMA',
            "PROVINCIA" => 'PROVINCIA'
        ];

        $distritos = Distrito::whereIn('provincia', ['LIMA', 'CALLAO'])
            ->where('estado', '1')
            ->pluck('distrito', 'distrito');

        $departamento = Departamento::where('estado', "1")
            ->pluck('departamento', 'departamento');

        $direcciones = DireccionEnvio::join('direccion_pedidos as dp', 'direccion_envios.id', 'dp.direccion_id')
            ->select('direccion_envios.id',
                'direccion_envios.distrito',
                'direccion_envios.direccion',
                'direccion_envios.referencia',
                'direccion_envios.nombre',
                'direccion_envios.celular',
                'dp.pedido_id as pedido_id',
            )
            ->where('direccion_envios.estado', '1')
            ->where('dp.estado', '1')
            ->get();

        $superasesor = User::where('rol', 'Super asesor')->count();

        $ver_botones_accion = 1;

        if (Auth::user()->rol == "Asesor") {
            $ver_botones_accion = 0;
        } else if (Auth::user()->rol == "Super asesor") {
            $ver_botones_accion = 0;
        } else if (Auth::user()->rol == "Encargado") {
            $ver_botones_accion = 1;
        } else {
            $ver_botones_accion = 1;
        }

        return view('envios.porEnviar', compact('condiciones', 'distritos', 'direcciones', 'destinos', 'superasesor', 'ver_botones_accion', 'departamento', 'distribuir'));
    }

    public function Enviospararepartotabla(Request $request)
    {
        $zona_aux = $request->zona;
        $lazona = '';
        switch ($zona_aux) {
            case 'NORTE':
                $lazona = array('NORTE', 'OLVA');
                break;
            case 'CENTRO':
                $lazona = array('CENTRO', 'CENTRO SUR', 'CENTRO OESTE', 'CENTRO NORTE', 'CENTRO ESTE', 'ESTE', 'OESTE');
                break;
            case 'SUR':
                $lazona = array('SUR');
                break;
        }

        $pedidos_lima = DireccionGrupo::select([
            'direccion_grupos.*',
            'u.identificador as user_identificador',
            'u.identificador as nombre_motorizado',
            //DB::raw(" (select 'LIMA') as destino "),
            DB::raw('(select DATE_FORMAT( direccion_grupos.created_at, "%Y-%m-%d")   from direccion_grupos dpa where dpa.id=direccion_grupos.id) as fecha_formato'),
        ])
            //join('direccion_envios as de', 'direccion_grupos.id', 'de.direcciongrupo')
            ->join('clientes as c', 'c.id', 'direccion_grupos.cliente_id')
            //->join('users as u', 'u.id', 'c.user_id')
            ->LeftJoin('users as u', 'u.id', 'direccion_grupos.motorizado_id')
            ->where('direccion_grupos.condicion_envio_code', Pedido::REPARTO_COURIER_INT)
            ->whereIn('direccion_grupos.distribucion', $lazona)
            ->activo();

        if (Auth::user()->rol == "Asesor") {
            $pedidos_lima = $pedidos_lima->Where('u.identificador', Auth::user()->identificador);


        } else if (Auth::user()->rol == "Encargado") {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.supervisor', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos_lima = $pedidos_lima->WhereIn('u.identificador', $usersasesores);
        } else if (Auth::user()->rol == "Jefe de llamadas") {
            $pedidos_lima = $pedidos_lima->where('u.identificador', '<>', 'B');
        } else if (Auth::user()->rol == "Llamadas") {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.llamada', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos_lima = $pedidos_lima->WhereIn('u.identificador', $usersasesores);

        }

        return Datatables::of(DB::table($pedidos_lima))
            ->addIndexColumn()
            ->editColumn('condicion_envio', function ($pedido) {
                $color = Pedido::getColorByCondicionEnvio($pedido->condicion_envio);

                $badge_estado = '';

                $badge_estado .= '<span class="badge badge-dark p-8" style="color: #fff; background-color: #347cc4; font-weight: 600; margin-bottom: -2px;border-radius: 4px 4px 0px 0px; font-size:8px;  padding: 4px 4px !important; font-weight: 500;">Direccion agregada</span>';

                $badge_estado .= '<span class="badge badge-success" style="background-color: #00bc8c !important;
                    padding: 4px 8px !important;
                    font-size: 8px;
                    margin-bottom: -4px;
                    color: black !important;">Con ruta</span>';

                $badge_estado .= '<span class="badge badge-success" style="background-color: ' . $color . '!important;">' . $pedido->condicion_envio . '</span>';
                return $badge_estado;


                return ' <span class="badge badge-success" style="background-color: #00bc8c !important;
    padding: 4px 8px !important;
    font-size: 8px;
    margin-bottom: -4px;
    color: black !important;">Con ruta</span>
                    <span class="badge badge-success" style="background-color: ' . $color . '!important;">' . $pedido->condicion_envio . '</span>';
            })
            ->editColumn('direccion', function ($pedido) {
                if ($pedido->distribucion == 'OLVA') {
                    return collect(explode(',', $pedido->direccion))->trim()->unique()->join(', ');
                }
                return $pedido->direccion;
            })
            ->editColumn('referencia', function ($pedido) {
                if ($pedido->distribucion == 'OLVA') {
                    $html = collect(explode(',', $pedido->referencia))->trim()->unique()->join(', ');
                    if ($pedido->observacion) {
                        $html .= collect(explode(',', $pedido->observacion))
                            ->trim()
                            ->unique()
                            ->map(fn($observacion) => '<a class="btn btn-icon p-0" target="_blank" href="' . \Storage::disk('pstorage')->url($observacion) . '">
<i class="fa fa-file-pdf"></i>
Ver Rotulo</a>')
                            ->join('');
                    }
                    return $html;
                }
                return $pedido->referencia;
            })
            ->addColumn('action', function ($grupo) {
                $btn = '';
                $btn .= '<ul class="list-unstyled pl-0">';
                //if (auth()->user()->can('envios.enviar')):

                $btn .= '<li>
                                        <a href="" class="btn-sm text-secondary" data-target="#modal-confirmacion" data-toggle="jqconfirm" data-ide="' . $grupo->id . '" data-entregar-confirm="' . $grupo->id . '" data-destino="' . $grupo->destino . '" data-fechaenvio="' . $grupo->fecha . '" data-codigos="' . $grupo->codigos . '"
                                            data-distribucion="' . $grupo->distribucion . '" >
                                            <i class="fa fa-motorcycle text-success" aria-hidden="true"></i> Enviar a Motorizado</a></li>
                                        </a>
                                    </li>';

                //endif;
                $btn .= '<li>
                            <a href="" class="btn-sm text-secondary" data-target="#modal-desvincular" data-toggle="modal" data-desvincular="' . $grupo->id . '">

                                            <i class="fa fa-undo text-danger" aria-hidden="true"></i> Retornar a sobres con dirección
                                </a>
                            </li>';
                $btn .= '</ul>';

                return $btn;
            })
            ->rawColumns(['action', 'condicion_envio', 'referencia'])
            ->make(true);

    }

    public function Enviosenrepartotabla(Request $request)
    {
        $pedidos = null;

        $pedidos_lima = DireccionGrupo::join('direccion_envios as de', 'direccion_grupos.id', 'de.direcciongrupo')
            ->join('clientes as c', 'c.id', 'de.cliente_id')
            ->join('users as u', 'u.id', 'c.user_id')
            ->where('direccion_grupos.condicion_envio_code', Pedido::REPARTO_COURIER_INT)
            ->where('direccion_grupos.estado', '1')
            ->select(
                'direccion_grupos.id',
                'u.identificador as identificador',
                DB::raw(" (select 'LIMA') as destino "),
                'de.celular',
                'de.nombre',
                'de.cantidad',
                'direccion_grupos.codigos',
                'direccion_grupos.producto',
                'de.direccion',
                'de.referencia',
                'de.observacion',
                'de.distrito',
                DB::raw('(select DATE_FORMAT( direccion_grupos.created_at, "%Y-%m-%d")   from direccion_grupos dpa where dpa.id=direccion_grupos.id) as fecha'),
                'direccion_grupos.destino as destino2',
                'direccion_grupos.distribucion',
                'direccion_grupos.condicion_envio',
                'direccion_grupos.subcondicion_envio',
                'direccion_grupos.condicion_sobre',
                'direccion_grupos.correlativo as correlativo'
            );

        if (Auth::user()->rol == "Asesor") {
            $pedidos_lima = $pedidos_lima->Where('u.identificador', Auth::user()->identificador);

        } else if (Auth::user()->rol == "Encargado") {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.supervisor', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos_lima = $pedidos_lima->WhereIn('u.identificador', $usersasesores);
        } else if (Auth::user()->rol == "Jefe de llamadas") {
            $pedidos_lima = $pedidos_lima->where('u.identificador', '<>', 'B');
        } else if (Auth::user()->rol == "Llamadas") {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.llamada', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos_lima = $pedidos_lima->WhereIn('u.identificador', $usersasesores);

        }

        $pedidos = $pedidos_lima->get();

        return Datatables::of($pedidos)
            ->addIndexColumn()
            ->addColumn('condicion_envio_color', function ($pedido) {
                return Pedido::getColorByCondicionEnvio($pedido->condicion_envio);
            })
            ->addColumn('action', function ($pedido) {
                $btn = '';

                //if (auth()->user()->can('envios.enviar')):

                $btn .= '<ul class="list-unstyled pl-0">';
                $btn .= '<li>
                                        <a href="" class="btn-sm text-secondary" data-target="#modal-confirmacion" data-toggle="modal" data-ide="' . $pedido->id . '" data-entregar-confirm="' . $pedido->id . '" data-destino="' . $pedido->destino . '" data-fechaenvio="' . $pedido->fecha . '" data-codigos="' . $pedido->codigos . '">
                                            <i class="fas fa-envelope text-success"></i> A motorizado</a></li>
                                        </a>
                                    </li>';
                $btn .= '</ul>';
                //endif;

                return $btn;
            })
            ->rawColumns(['action'])
            ->make(true);

    }


    public function DistribuirEnvioid(Request $request)
    {

        $envio = DireccionGrupo::where("id", $request->hiddenDistribuir)->first();

        $envio->update([
            'distribucion' => $request->distribuir,
            //'modificador' => 'USER'.Auth::user()->id
        ]);


        return response()->json(['html' => $request->hiddenEnviar]);

    }

    public function Entregados()//ENTREGADOS
    {

        $distribuir = [
            "NORTE" => 'NORTE',
            "CENTRO" => 'CENTRO',
            "SUR" => 'SUR',
        ];

        $dateMin = Carbon::now()->subDays(4)->format('d/m/Y');
        $dateMax = Carbon::now()->format('d/m/Y');

        $condiciones = [
            "PENDIENTE DE ENVIO" => 'PENDIENTE DE ENVIO',
            "EN REPARTO" => 'EN REPARTO',
            "ENTREGADO" => 'ENTREGADO'
        ];

        PedidoMovimientoEstado::where('condicion_envio_code', Pedido::ENTREGADO_SIN_SOBRE_OPE_INT)->update([
            'notificado' => 1,
        ]);

        $superasesor = User::where('rol', 'Super asesor')->count();

        return view('envios.entregados', compact('dateMin', 'dateMax', 'condiciones', 'superasesor'));
    }

    public function Entregadostabla()//ENTREGADOS
    {
        $mirol = Auth::user()->rol;


        $pedidos = null;

        $pedidos_lima = DireccionGrupo::/*join('direccion_envios as de', 'direccion_grupos.id', 'de.direcciongrupo')*/
        join('clientes as c', 'c.id', 'direccion_grupos.cliente_id')
            ->join('users as u', 'u.id', 'c.user_id')
            ->where('direccion_grupos.estado', '1')
            ->whereIn('direccion_grupos.condicion_envio_code', [Pedido::ENTREGADO_CLIENTE_INT, Pedido::ENTREGADO_SIN_SOBRE_OPE_INT, Pedido::ENTREGADO_SIN_SOBRE_CLIENTE_INT])
            ->select(
                'direccion_grupos.id',
                'u.identificador as identificador',
                DB::raw(" (select 'LIMA') as destino "),
                'direccion_grupos.celular',
                'direccion_grupos.nombre',
                'direccion_grupos.cantidad',
                'direccion_grupos.codigos',
                'direccion_grupos.producto',
                'direccion_grupos.direccion',
                'direccion_grupos.referencia',
                'direccion_grupos.observacion',
                'direccion_grupos.distrito',
                'direccion_grupos.created_at as fecha',
                DB::raw("DATE_FORMAT(direccion_grupos.fecha_recepcion, '%Y-%m-%d') as fechaentrega"),
                'direccion_grupos.destino as destino2',
                'direccion_grupos.distribucion',
                'direccion_grupos.condicion_envio',
                'direccion_grupos.condicion_envio_code',
                'direccion_grupos.condicion_sobre',
                'direccion_grupos.subcondicion_envio',
                'direccion_grupos.foto1',
                'direccion_grupos.foto2',
                'direccion_grupos.foto3',
                'direccion_grupos.correlativo'
            );
        $pedidos = $pedidos_lima;

        if (Auth::user()->rol == "Operario") {

            $asesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->Where('users.operario', Auth::user()->id)
                ->select(
                    DB::raw("users.id as id")
                )
                ->pluck('users.id');
            $pedidos = $pedidos->Where('u.identificador', Auth::user()->identificador);

        } else if (Auth::user()->rol == "Jefe de operaciones") {

            $operarios = User::where('users.rol', 'Operario')
                ->where('users.estado', '1')
                ->where('users.jefe', Auth::user()->id)
                ->select(
                    DB::raw("users.id as id")
                )
                ->pluck('users.id');

            $asesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->WhereIn('users.operario', $operarios)
                ->select(
                    DB::raw("users.id as id")
                )
                ->pluck('users.id');
            $pedidos = $pedidos->Where('u.identificador', Auth::user()->identificador);


        } else if (Auth::user()->rol == "Asesor") {

            $pedidos = $pedidos->Where('u.identificador', Auth::user()->identificador);

        } else if (Auth::user()->rol == "Encargado") {

        } else {

        }

        return datatables()->query(\DB::table($pedidos))
            ->addIndexColumn()
            ->addColumn('condicion_envio_color', function ($pedido) {

                return Pedido::getColorByCondicionEnvio($pedido->condicion_envio);
            })
            ->editColumn('condicion_envio', function ($grupo) {
                $color = Pedido::getColorByCondicionEnvio($grupo->condicion_envio);

                $badge_estado = '';
                $badge_estado .= '<span class="badge badge-dark p-8" style="color: #fff; background-color: #347cc4; font-weight: 600; margin-bottom: -2px;border-radius: 4px 4px 0px 0px; font-size:8px;  padding: 4px 4px !important; font-weight: 500;">Direccion agregada</span>';

                $badge_estado .= '<span class="badge badge-success" style="background-color: #00bc8c !important;
                    padding: 4px 8px !important;
                    font-size: 8px;
                    margin-bottom: -4px;
                    color: black !important;">Con ruta</span>';
                $badge_estado .= '<span class="badge badge-success" style="background-color: ' . $color . '!important;">' . $grupo->condicion_envio . '</span>';
                return $badge_estado;
            })
            ->editColumn('foto1', function ($pedido) {
                if ($pedido->foto1 != null) {
                    $urlimagen1 = \Storage::disk('pstorage')->url($pedido->foto1);

                    $data = '<a href="" data-target="#modal-imagen" data-toggle="modal" data-imagen="' . $pedido->foto1 . '">
                    <img src="' . $urlimagen1 . '" alt="' . $pedido->foto1 . '" height="100px" width="100px" id="imagen_' . $pedido->id . '-1" class="img-thumbnail cover">
                    </a>
                    <a download href="' . $urlimagen1 . '" class="text-center"><button type="button" class="btn btn-secondary btn-md"> Descargar</button> </a>
                    <a href="" data-target="#modal-cambiar-imagen" data-toggle="modal" data-item="1" data-imagen="' . $pedido->foto1 . '" data-pedido="' . $pedido->id . '">
<button class="btn btn-danger btn-md">Cambiar</button></a>';

                    if (Auth::user()->rol == "Asesor") {
                        $data .= '<a href="" data-target="#modal-delete-foto1" data-toggle="modal" data-deletefoto1="' . $pedido->id . '">
                        <button class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i></button>
                        </a>';
                    }
                    return $data;
                } else if ($pedido->condicion_envio_code == Pedido::ENTREGADO_SIN_SOBRE_OPE_INT) {
                    return '<span class="badge badge-dark">Sin envio</span>';
                } else {
                    return '';
                }
            })
            ->editColumn('foto2', function ($pedido) {
                if ($pedido->foto2 != null) {
                    $urlimagen1 = \Storage::disk('pstorage')->url($pedido->foto2);

                    $data = '<a href="" data-target="#modal-imagen" data-toggle="modal" data-imagen="' . $pedido->foto2 . '">
                    <img src="' . $urlimagen1 . '" alt="' . $pedido->foto2 . '" height="100px" width="100px" id="imagen_' . $pedido->id . '-2" class="img-thumbnail cover">
                    </a>
                    <a download href="' . $urlimagen1 . '" class="text-center"><button type="button" class="btn btn-secondary btn-md"> Descargar</button> </a>
                    <a href="" data-target="#modal-cambiar-imagen" data-toggle="modal" data-item="2" data-imagen="' . $pedido->foto2 . '" data-pedido="' . $pedido->id . '">
<button class="btn btn-danger btn-md">Cambiar</button></a>';

                    if (Auth::user()->rol == "Asesor") {
                        $data .= '<a href="" data-target="#modal-delete-foto2" data-toggle="modal" data-deletefoto2="' . $pedido->id . '">
                        <button class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i></button>
                        </a>';
                    }
                    return $data;
                } else if ($pedido->condicion_envio_code == Pedido::ENTREGADO_SIN_SOBRE_OPE_INT) {
                    return '<span class="badge badge-dark">Sin envio</span>';
                } else {
                    return '';
                }
            })
            ->editColumn('foto3', function ($pedido) {
                if ($pedido->foto3 != null) {
                    $urlimagen1 = \Storage::disk('pstorage')->url($pedido->foto3);

                    $data = '<a href="" data-target="#modal-imagen" data-toggle="modal" data-imagen="' . $pedido->foto3 . '">
                    <img src="' . $urlimagen1 . '" alt="' . $pedido->foto3 . '" height="100px" width="100px" id="imagen_' . $pedido->id . '-3" class="img-thumbnail cover">
                    </a>
                    <a download href="' . $urlimagen1 . '" class="text-center"><button type="button" class="btn btn-secondary btn-md"> Descargar</button> </a>
                    <a href="" data-target="#modal-cambiar-imagen" data-toggle="modal" data-item="3" data-imagen="' . $pedido->foto3 . '" data-pedido="' . $pedido->id . '">
<button class="btn btn-danger btn-md">Cambiar</button></a>';

                    if (Auth::user()->rol == "Asesor") {
                        $data .= '<a href="" data-target="#modal-delete-foto3" data-toggle="modal" data-deletefoto3="' . $pedido->id . '">
                        <button class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i></button>
                        </a>';
                    }
                    return $data;
                } else if ($pedido->condicion_envio_code == Pedido::ENTREGADO_SIN_SOBRE_OPE_INT) {
                    return '<span class="badge badge-dark">Sin envio</span>';
                } else {
                    return '';
                }
            })
            ->addColumn('action', function ($pedido) {
                $btn = '';
                if ($pedido->condicion_envio_code == 13) {
                    $btn .= '<button class="btn btn-sm text-white bg-primary"
                                    data-jqconfirm="' . $pedido->id . '">
                                        <i class="fa fa-motorcycle text-white" aria-hidden="true"></i> A revertir
                                    </button>';

                }

                return $btn;
            })
            ->rawColumns(['action', 'foto1', 'foto2', 'foto3', 'condicion_envio'])
            ->make(true);

    }

    public function Enviosrutaenvio(Request $request)
    {

        $motorizados = User::select([
            'id',
            'zona',
            DB::raw(" (select count(*) from pedidos inner join direccion_grupos b on pedidos.direccion_grupo=b.id where b.motorizado_status in (" . Pedido::ESTADO_MOTORIZADO_OBSERVADO . "," . Pedido::ESTADO_MOTORIZADO_NO_CONTESTO . ") and b.motorizado_id=users.id and b.estado=1) as devueltos")
        ])->where('rol', '=', User::ROL_MOTORIZADO)
            ->whereNotNull('zona')
            ->activo()
            ->get();


        if ($request->fechaconsulta != null) {
            try {
                $fecha_consulta = Carbon::createFromFormat('d/m/Y', $request->fechaconsulta);
            } catch (\Exception $ex) {
                $fecha_consulta = now();
            }
        } else {
            $fecha_consulta = now();
        }


        $rol = Auth::user()->rol;
        $distribuir = [
            "NORTE" => 'NORTE',
            "CENTRO" => 'CENTRO',
            "SUR" => 'SUR',
        ];

        $condiciones = [
            "1" => 1,
            "2" => 2,
            "3" => 3
        ];

        $destinos = [
            "LIMA" => 'LIMA',
            "PROVINCIA" => 'PROVINCIA'
        ];

        $departamento = Departamento::where('estado', "1")
            ->pluck('departamento', 'departamento');

        $distritos = Distrito::whereIn('provincia', ['LIMA', 'CALLAO'])
            ->where('estado', '1')
            ->pluck('distrito', 'distrito');

        $direcciones = DireccionEnvio::join('direccion_pedidos as dp', 'direccion_envios.id', 'dp.direccion_id')
            ->select('direccion_envios.id',
                'direccion_envios.distrito',
                'direccion_envios.direccion',
                'direccion_envios.referencia',
                'direccion_envios.nombre',
                'direccion_envios.celular',
                'dp.pedido_id as pedido_id',
            )
            ->where('direccion_envios.estado', '1')
            ->where('dp.estado', '1')
            ->get();

        $superasesor = User::where('rol', 'Super asesor')->count();

        $ver_botones_accion = 1;

        if (Auth::user()->rol == "Asesor") {
            $ver_botones_accion = 0;
        } else if (Auth::user()->rol == "Super asesor") {
            $ver_botones_accion = 0;
        } else if (Auth::user()->rol == "Encargado") {
            $ver_botones_accion = 1;
        } else {
            $ver_botones_accion = 1;
        }

        $dateMin = Carbon::now()->format('Y-m-d');

        return view('envios.rutaenvio', compact('condiciones', 'distritos', 'direcciones', 'destinos', 'superasesor', 'ver_botones_accion', 'departamento', 'dateMin', 'distribuir', 'rol', 'fecha_consulta', 'motorizados'));
    }


    public function Enviosrutaenviotabla(Request $request)
    {
        // SI EXISTE UNA VISTA
        if ($request->vista != null) {
            try {
                $vista_consulta = Carbon::createFromFormat('d/m/Y', $request->vista);
            } catch (\Exception $ex) {
                $vista_consulta = now();
            }
        } else {
            $vista_consulta = now();
        }

        // SI EXISTE UNA FECHA
        if ($request->fechaconsulta != null) {
            try {
                $fecha_consulta = Carbon::createFromFormat('d/m/Y', $request->fechaconsulta);
            } catch (\Exception $ex) {
                $fecha_consulta = now();
            }
        } else {
            $fecha_consulta = now();
        }

        // SI SE ESPERA RESULTADOS PARA UNA TABLA
        if ($request->has('datatable')) {
            $query = DireccionGrupo::
            join('clientes as c', 'c.id', 'direccion_grupos.cliente_id')
                ->join('users as u', 'u.id', 'c.user_id')
                ->when($fecha_consulta != null, function ($query) use ($fecha_consulta) {
                    $query->whereDate('direccion_grupos.fecha_salida', $fecha_consulta);
                })
                ->where('direccion_grupos.motorizado_id', '=', $request->motorizado_id)
                ->select([
                    'direccion_grupos.*',
                ]);

            $tab = ($request->tab ?: '');
            switch ($tab) {
                case 'entregado':
                    $query
                        ->where('direccion_grupos.estado', '1')
                        ->where('direccion_grupos.condicion_envio_code', Pedido::CONFIRM_MOTORIZADO_INT);
                    break;
                case 'no_contesto':
                    $query
                        ->where('direccion_grupos.estado', '1')
                        ->where('direccion_grupos.condicion_envio_code', Pedido::MOTORIZADO_INT)
                        ->where('direccion_grupos.motorizado_status', Pedido::ESTADO_MOTORIZADO_NO_CONTESTO);
                    break;
                case 'observado':
                    $query->where('direccion_grupos.condicion_envio_code', Pedido::MOTORIZADO_INT);
                    $query->where('direccion_grupos.motorizado_status', Pedido::ESTADO_MOTORIZADO_OBSERVADO);
                    break;
                default:
                    $query
                        ->where('direccion_grupos.estado', '1')
                        ->where('direccion_grupos.condicion_envio_code', Pedido::MOTORIZADO_INT)
                        ->whereNotIn('direccion_grupos.motorizado_status', [Pedido::ESTADO_MOTORIZADO_OBSERVADO, Pedido::ESTADO_MOTORIZADO_NO_CONTESTO]);
            }

            return datatables()->query(DB::table($query))
                ->addIndexColumn()
                ->rawColumns(['action', 'condicion_envio'])
                ->toJson();
        }

    }

    public function Enviosporconfirmar()
    {
        $distritos = Distrito::whereIn('provincia', ['LIMA', 'CALLAO'])
            ->where('estado', '1')
            ->pluck('distrito', 'distrito');

        $direcciones = DireccionEnvio::join('direccion_pedidos as dp', 'direccion_envios.id', 'dp.direccion_id')
            ->select('direccion_envios.id',
                'direccion_envios.distrito',
                'direccion_envios.direccion',
                'direccion_envios.referencia',
                'direccion_envios.nombre',
                'direccion_envios.celular',
                'dp.pedido_id as pedido_id',
            )
            ->where('direccion_envios.estado', '1')
            ->where('dp.estado', '1')
            ->get();
        $departamento = Departamento::where('estado', "1")
            ->pluck('departamento', 'departamento');
        $superasesor = User::where('rol', 'Super asesor')->count();

        $ver_botones_accion = 1;

        if (Auth::user()->rol == "Asesor") {
            $ver_botones_accion = 0;
        } else if (Auth::user()->rol == "Super asesor") {
            $ver_botones_accion = 0;
        } else if (Auth::user()->rol == "Encargado") {
            $ver_botones_accion = 1;
        } else {
            $ver_botones_accion = 1;
        }

        return view('envios.porConfirmar', compact('distritos', 'direcciones', 'superasesor', 'ver_botones_accion', 'departamento'));
    }


    public function Enviosporconfirmartabla(Request $request)
    {
        $pedidos = null;
        $filtros_code = [12];

        $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
            ->join('users as u', 'pedidos.user_id', 'u.id')
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->select([
                'pedidos.id',
                'pedidos.correlativo as id2',
                'c.nombre as nombres',
                'c.celular as celulares',
                'u.identificador as users',
                'dp.codigo as codigos',
                'dp.nombre_empresa as empresas',
                'dp.total as total',
                'pedidos.condicion',
                'pedidos.created_at as fecha',
                'pedidos.condicion_envio',
                'pedidos.envio',
                'pedidos.destino',
                'pedidos.direccion',
                'pedidos.estado_sobre',
                'dp.envio_doc',
                'dp.fecha_envio_doc',
                'dp.cant_compro',
                'dp.fecha_envio_doc_fis',
                'dp.foto1',
                'dp.foto2',
                'dp.fecha_recepcion',
                'pedidos.fecha_envio_op_courier'
            ])
            ->WhereIn('pedidos.condicion_envio_code', $filtros_code)
            //->where('pedidos.envio', '2')
            ->where('pedidos.estado', '1');

        if (Auth::user()->rol == "Operario") {
            $asesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->Where('users.operario', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos = $pedidos->WhereIn('u.identificador', $asesores);

        } else if (Auth::user()->rol == "Jefe de operaciones") {
            /*$operarios = User::where('users.rol', 'Operario')
                ->where('users.estado', '1')
                ->where('users.jefe', Auth::user()->id)
                ->select(
                    DB::raw("users.id as id")
                )
                ->pluck('users.id');

            $asesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->WhereIn('users.operario', $operarios)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos = $pedidos->WhereIn('u.identificador', $asesores);*/

        } else if (Auth::user()->rol == "Asesor") {
            $pedidos = $pedidos->Where('u.identificador', Auth::user()->identificador);

        } else if (Auth::user()->rol == "Super asesor") {
            $pedidos = $pedidos->Where('u.identificador', Auth::user()->identificador);

        } else if (Auth::user()->rol == "Encargado") {
            $pedidos = $pedidos->Where('u.supervisor', Auth::user()->identificador);
        } else if (Auth::user()->rol == "Llamadas") {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.llamada', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos = $pedidos->WhereIn('u.identificador', $usersasesores);
        } else if (Auth::user()->rol == "Jefe de llamadas") {
            $pedidos = $pedidos->where('u.identificador', '<>', 'B');
        }

        return Datatables::of(DB::table($pedidos))
            ->addIndexColumn()
            ->addColumn('condicion_envio_color', function ($pedido) {
                return Pedido::getColorByCondicionEnvio($pedido->condicion_envio);
            })
            ->addColumn('action', function ($pedido) {
                $btn = [];
                $btn[] = '<button type="button" class="btn btn-warning btn-sm" data-target="#modal-envio" data-toggle="modal" data-recibir="' . $pedido->id . '" data-codigos="' . $pedido->codigos . '"><i class="fas fa-check-circle"></i> Recibido</a>';
                return join('', $btn);
            })
            ->rawColumns(['action'])
            ->make(true);

    }


    public function Enviosporrecibir()
    {
        $condiciones = [
            "1" => 1,
            "2" => 2,
            "3" => 3
        ];

        $destinos = [
            "LIMA" => 'LIMA',
            "PROVINCIA" => 'PROVINCIA'
        ];

        $distritos = Distrito::whereIn('provincia', ['LIMA', 'CALLAO'])
            ->where('estado', '1')
            ->pluck('distrito', 'distrito');

        $direcciones = DireccionEnvio::join('direccion_pedidos as dp', 'direccion_envios.id', 'dp.direccion_id')
            ->select('direccion_envios.id',
                'direccion_envios.distrito',
                'direccion_envios.direccion',
                'direccion_envios.referencia',
                'direccion_envios.nombre',
                'direccion_envios.celular',
                'dp.pedido_id as pedido_id',
            )
            ->where('direccion_envios.estado', '1')
            ->where('dp.estado', '1')
            ->get();
        $departamento = Departamento::where('estado', "1")
            ->pluck('departamento', 'departamento');
        $superasesor = User::where('rol', 'Super asesor')->count();

        $ver_botones_accion = 1;

        if (Auth::user()->rol == "Asesor") {
            $ver_botones_accion = 0;
        } else if (Auth::user()->rol == "Super asesor") {
            $ver_botones_accion = 0;
        } else if (Auth::user()->rol == "Encargado") {
            $ver_botones_accion = 1;
        } else {
            $ver_botones_accion = 1;
        }

        return view('envios.porRecibir', compact('condiciones', 'distritos', 'direcciones', 'destinos', 'superasesor', 'ver_botones_accion', 'departamento'));
    }

    public function Enviosporrecibirtabla(Request $request)
    {
        $pedidos = null;

        $filtros_code = [Pedido::REPARTO_COURIER_INT, Pedido::ENVIADO_OPE_INT, Pedido::ENVIO_COURIER_JEFE_OPE_INT];

        $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
            ->join('users as u', 'pedidos.user_id', 'u.id')
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->select(
                'pedidos.id',
                'pedidos.correlativo as id2',
                'c.nombre as nombres',
                'c.celular as celulares',
                'u.identificador as users',
                'dp.codigo as codigos',
                'dp.nombre_empresa as empresas',
                'dp.total as total',
                'pedidos.condicion',
                'pedidos.created_at as fecha',
                'pedidos.condicion_envio',
                'pedidos.envio',
                'pedidos.destino',
                'pedidos.direccion',
                'pedidos.estado_sobre',
                'dp.envio_doc',
                'dp.fecha_envio_doc',
                'dp.cant_compro',
                'dp.fecha_envio_doc_fis',
                'dp.foto1',
                'dp.foto2',
                'dp.fecha_recepcion'
            )
            ->WhereIn('pedidos.condicion_envio_code', $filtros_code)
            ->where('pedidos.envio', '2')
            ->where('pedidos.estado', '1');
        if (Auth::user()->rol == "Operario") {
            $asesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->Where('users.operario', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos = $pedidos->WhereIn('u.identificador', $asesores);

        } else if (Auth::user()->rol == "Jefe de operaciones") {
            $operarios = User::where('users.rol', 'Operario')
                ->where('users.estado', '1')
                ->where('users.jefe', Auth::user()->id)
                ->select(
                    DB::raw("users.id as id")
                )
                ->pluck('users.id');

            $asesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->WhereIn('users.operario', $operarios)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos = $pedidos->WhereIn('u.identificador', $asesores);

        } else if (Auth::user()->rol == "Asesor") {
            $pedidos = $pedidos->Where('u.identificador', Auth::user()->identificador);

        } else if (Auth::user()->rol == "Super asesor") {
            $pedidos = $pedidos->Where('u.identificador', Auth::user()->identificador);

        } else if (Auth::user()->rol == "Encargado") {
            $pedidos = $pedidos->Where('u.supervisor', Auth::user()->identificador);
        } else if (Auth::user()->rol == "Llamadas") {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.llamada', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos = $pedidos->WhereIn('u.identificador', $usersasesores);
        } else if (Auth::user()->rol == "Jefe de llamadas") {
            $pedidos = $pedidos->where('u.identificador', '<>', 'B');
        }

        return Datatables::of(DB::table($pedidos))
            ->addIndexColumn()
            ->addColumn('action', function ($pedido) {
                $btn = '';
                return $btn;
            })
            ->rawColumns(['action'])
            ->make(true);

    }

    public function EnviarPedidoid(Request $request)
    {

        $envio = DireccionGrupo::where("id", $request->hiddenEnviar)->first();
        $envio->update([
            'subcondicion_envio' => $request->condicion,
            'modificador' => 'USER' . Auth::user()->id
        ]);


        if ($request->condicion == 'ENTREGADO') {
            $envio->update([
                'condicion_envio' => Pedido::ENTREGADO_CLIENTE,
                'condicion_envio_code' => Pedido::ENTREGADO_CLIENTE_INT,
                'condicion_envio_at' => now(),

            ]);

        }


        $files = $request->file('foto1');
        $files2 = $request->file('foto2');

        $destinationPath = base_path('public/storage/entregas/');

        if ($request->hasFile('foto1') && $request->hasFile('foto2')) {
            $file_name = Carbon::now()->second . $files->getClientOriginalName();
            $file_name2 = Carbon::now()->second . $files2->getClientOriginalName();

            $files->move($destinationPath, $file_name);
            $files2->move($destinationPath, $file_name2);

            $envio->update([
                'foto1' => $file_name,
                'foto2' => $file_name2,
                'fecha_recepcion' => $request->fecha_recepcion,
                'atendido_por' => Auth::user()->name,
                'atendido_por_id' => Auth::user()->id,
            ]);
        } else if ($request->hasFile('foto1') && $request->foto2 == null) {
            $file_name = Carbon::now()->second . $files->getClientOriginalName();
            $files->move($destinationPath, $file_name);

            $envio->update([
                'foto1' => $file_name,
                'fecha_recepcion' => $request->fecha_recepcion,
                'atendido_por' => Auth::user()->name,
                'atendido_por_id' => Auth::user()->id,
            ]);
        } else if ($request->foto1 == null && $request->hasFile('foto2')) {
            $file_name2 = Carbon::now()->second . $files2->getClientOriginalName();
            $files2->move($destinationPath, $file_name2);

            $envio->update([
                'foto2' => $file_name2,
                'fecha_recepcion' => $request->fecha_recepcion,
                'atendido_por' => Auth::user()->name,
                'atendido_por_id' => Auth::user()->id,
            ]);
        } else {
            $envio->update([
                'fecha_recepcion' => $request->fecha_recepcion,
                'atendido_por' => Auth::user()->name,
                'atendido_por_id' => Auth::user()->id,
            ]);
        }

        return response()->json(['html' => $request->hiddenEnviar]);

    }

    public function changeImg(Request $request)
    {
        $item = $request->item;
        $pedido = $request->pedido;
        $file = $request->file('adjunto');


        if (isset($file)) {
            $file_name = $file->store('entregas', 'pstorage');
            DireccionGrupo::where('id', $pedido)
                ->update([
                    'foto' . $item => $file_name
                ]);
            return response()->json([
                'success' => true,
                'pedido' => $pedido,
                'item' => $item,
                'path' => \Storage::disk('pstorage')->url($file_name)
            ]);
        }
        return response()->json([
            'success' => false,
        ]);
    }


    public function Seguimientoprovincia()
    {

        $distribuir = [
            "NORTE" => 'NORTE',
            "CENTRO" => 'CENTRO',
            "SUR" => 'SUR',
        ];

        $condiciones = [
            "1" => 1,
            "2" => 2,
            "3" => 3
        ];

        $destinos = [
            "LIMA" => 'LIMA',
            "PROVINCIA" => 'PROVINCIA'
        ];

        $distritos = Distrito::whereIn('provincia', ['LIMA', 'CALLAO'])
            ->where('estado', '1')
            ->pluck('distrito', 'distrito');

        $departamento = Departamento::where('estado', "1")
            ->pluck('departamento', 'departamento');

        $direcciones = DireccionEnvio::join('direccion_pedidos as dp', 'direccion_envios.id', 'dp.direccion_id')
            ->select('direccion_envios.id',
                'direccion_envios.distrito',
                'direccion_envios.direccion',
                'direccion_envios.referencia',
                'direccion_envios.nombre',
                'direccion_envios.celular',
                'dp.pedido_id as pedido_id',
            )
            ->where('direccion_envios.estado', '1')
            ->where('dp.estado', '1')
            ->get();

        $superasesor = User::where('rol', 'Super asesor')->count();

        $ver_botones_accion = 1;

        if (Auth::user()->rol == "Asesor") {
            $ver_botones_accion = 0;
        } else if (Auth::user()->rol == "Super asesor") {
            $ver_botones_accion = 0;
        } else if (Auth::user()->rol == "Encargado") {
            $ver_botones_accion = 1;
        } else {
            $ver_botones_accion = 1;
        }

        return view('envios.seguimientoProvincia', compact('condiciones', 'distritos', 'direcciones', 'destinos', 'superasesor', 'ver_botones_accion', 'departamento', 'distribuir'));
    }

    public function Seguimientoprovinciatabla(Request $request)
    {
        $pedidos = null;


        $pedidos_provincia = DireccionGrupo::join('gasto_envios as de', 'direccion_grupos.id', 'de.direcciongrupo')
            ->join('clientes as c', 'c.id', 'de.cliente_id')
            ->join('users as u', 'u.id', 'c.user_id')
            ->where('direccion_grupos.estado', '1')
            ->where('direccion_grupos.condicion_envio_code', Pedido::SEGUIMIENTO_PROVINCIA_COURIER_INT)
            ->select(
                'direccion_grupos.id',
                'u.identificador as identificador',
                DB::raw(" (select 'PROVINCIA') as destino "),
                DB::raw(" (select '') as celular "),
                DB::raw(" (select '') as nombre "),
                'de.cantidad',
                'direccion_grupos.codigos',
                'direccion_grupos.producto',
                'de.tracking as direccion',
                'de.foto as referencia',
                DB::raw(" (select '') as observacion "),
                DB::raw(" (select '') as distrito "),
                DB::raw('(select DATE_FORMAT( direccion_grupos.created_at, "%Y-%m-%d")   from direccion_grupos dpa where dpa.id=direccion_grupos.id) as fecha'),
                'direccion_grupos.destino as destino2',
                'direccion_grupos.distribucion',
                'direccion_grupos.condicion_envio',
                'direccion_grupos.subcondicion_envio',
                'direccion_grupos.condicion_sobre',
            );

        $pedidos = $pedidos_provincia;
        $pedidos = $pedidos->get();

        return Datatables::of($pedidos)
            ->addIndexColumn()
            ->addColumn('condicion_envio_color', function ($pedido) {
                return Pedido::getColorByCondicionEnvio($pedido->condicion_envio);
            })
            ->addColumn('action', function ($pedido) {
                $btn = '';
                return $btn;
            })
            ->rawColumns(['action'])
            ->make(true);

    }

    public function recibiridLog(Request $request)
    {
        $pedido = Pedido::with(['detallePedido'])->where("id", $request->hiddenEnvio)->first();

        $pedido->update([
            'fecha_recepcion_courier' => Carbon::now(),
            'modificador' => 'USER' . Auth::user()->id,
            'condicion_envio' => Pedido::RECEPCION_COURIER,
            'condicion_envio_code' => Pedido::RECEPCION_COURIER_INT,
            'condicion_envio_at' => now(),
        ]);

        if ($pedido->estado_sobre) {
            $detalle = $pedido->detallePedido;
            $grupoPedido = GrupoPedido::createGroupByPedido($pedido);

            if (!$grupoPedido->pedidos()->where('pedidos.id', '=', $pedido->id)->exists()) {
                $grupoPedido->pedidos()->syncWithoutDetaching([
                    $pedido->id => [
                        'razon_social' => $detalle->nombre_empresa,
                        'codigo' => $pedido->codigo,
                    ]
                ]);
            }
        }

        PedidoMovimientoEstado::create([
            'pedido' => $request->hiddenEnvio,
            'condicion_envio_code' => Pedido::RECEPCION_COURIER_INT,
            'notificado' => 0
        ]);

        return response()->json(['html' => $pedido->id]);
    }

    public function RecibirMotorizado(Request $request)
    {
        $accion = $request->hiddenAccion;
        $grupo = DireccionGrupo::query()->findOrFail($request->hiddenEnvio);

        $pedidosIds = $grupo->pedidos()->pluck('id');
        if ($accion == "recibir") {
            if ($request->has('pedidos')) {
                $pedidos = Pedido::query()->whereIn('id', $request->pedidos)->get();
                if ($pedidos->count() > 0) {
                    $diff = $pedidosIds->diff($request->pedidos);
                    if ($diff->count() > 0) {
                        $grupo = DireccionGrupo::desvincularPedidos($grupo, $pedidos, null, 0);
                    }
                    $grupo->update([
                        'fecha_recepcion_motorizado' => Carbon::now(),
                        'condicion_envio' => Pedido::RECEPCION_MOTORIZADO,
                        'condicion_envio_code' => Pedido::RECEPCION_MOTORIZADO_INT,
                        'cambio_direccion_at' => null,
                        'condicion_envio_at' => now(),
                    ]);
                }

            } else {
                $grupo->update([
                    'fecha_recepcion_motorizado' => Carbon::now(),
                    'condicion_envio' => Pedido::RECEPCION_MOTORIZADO,
                    'condicion_envio_code' => Pedido::RECEPCION_MOTORIZADO_INT,
                    'cambio_direccion_at' => null,
                    'condicion_envio_at' => now(),
                ]);
            }
            PedidoMovimientoEstado::create([
                'pedido' => $request->hiddenEnvio,
                'condicion_envio_code' => Pedido::RECEPCION_MOTORIZADO_INT,
                'notificado' => 0,
            ]);

            return response()->json(['html' => "Grupo recibido"]);

        } else if ($accion == "rechazar") {
            if ($request->has('pedidos')) {
                $pedidos = Pedido::query()->whereIn('id', $request->pedidos)->get();
                if ($pedidos->count() > 0) {
                    $diff = $pedidosIds->diff($request->pedidos);
                    if ($diff->count() > 0) {
                        $grupo = DireccionGrupo::desvincularPedidos($grupo, $pedidos, 'No recibido', Pedido::ESTADO_MOTORIZADO_NO_RECIBIDO);
                    } else {
                        $grupo->update([
                            'motorizado_status' => Pedido::ESTADO_MOTORIZADO_NO_RECIBIDO,
                            'motorizado_sustento_text' => 'No recibido',
                            'cambio_direccion_at' => null
                        ]);
                    }
                    DireccionGrupo::addNoRecibidoAuthorization($grupo);
                }
            } else {
                $grupo->update([
                    'motorizado_sustento_text' => 'No recibido',
                    'motorizado_status' => Pedido::ESTADO_MOTORIZADO_NO_RECIBIDO,
                    'cambio_direccion_at' => null
                ]);
                DireccionGrupo::addNoRecibidoAuthorization($grupo);
            }

            PedidoMovimientoEstado::create([
                'pedido' => $request->hiddenEnvio,
                'condicion_envio_code' => Pedido::REPARTO_COURIER_INT,
                'notificado' => 0,
            ]);

            return response()->json(['html' => "Grupo rechazado"]);
        }
    }

    public function Recibirid(Request $request)

    {

        $pedido = Pedido::where("id", $request->hiddenRecibir)->first();
        $direccion_grupos = DireccionGrupo::where("codigos", $pedido->codigo)->first();
        $localizacion = $pedido->condicion_envio_code;


        if ($localizacion == 7) {

            $pedido->update([
                'envio' => '2',
                'estado_sobre' => '1',
                'condicion_envio' => Pedido::REPARTO_COURIER,
                'condicion_envio_code' => Pedido::REPARTO_COURIER_INT,
                'condicion_envio_at' => now(),
                'modificador' => 'USER' . Auth::user()->id,
            ]);

            $direccion_grupos->update([
                'condicion_envio' => Pedido::REPARTO_COURIER,
                'condicion_envio_code' => Pedido::REPARTO_COURIER_INT,
                'condicion_envio_at' => now(),
                'modificador' => 'USER' . Auth::user()->id,
                'pedido_id' => $request->hiddenRecibir,
            ]);


        }

        /* si es provincia */

        if ($localizacion == 6) {

            $pedido->update([

                'envio' => '2',
                'estado_sobre' => '1',
                'condicion_envio' => Pedido::SEGUIMIENTO_PROVINCIA_COURIER,
                'condicion_envio_code' => Pedido::SEGUIMIENTO_PROVINCIA_COURIER_INT,
                'condicion_envio_at' => now(),
                'modificador' => 'USER' . Auth::user()->id
            ]);


            $direccion_grupos->update([

                'condicion_envio' => Pedido::SEGUIMIENTO_PROVINCIA_COURIER,
                'condicion_envio_code' => Pedido::SEGUIMIENTO_PROVINCIA_COURIER_INT,
                'condicion_envio_at' => now(),
                'modificador' => 'USER' . Auth::user()->id,
                'pedido_id' => $request->hiddenRecibir
            ]);

        }

        return response()->json(['html' => $request->hiddenRecibir]);

    }

    public function getDireccionEnvio(Request $request)
    {
        $pedido_id = (int)$request->get('pedido_id');
        if ($pedido_id > 0) {
            $pedido = Pedido::query()->with('direcciongrupo')->find($pedido_id);
            $dirgrupo = $pedido->direcciongrupo;
            if ($dirgrupo != null) {
                if ($dirgrupo->condicion_envio_code == Pedido::ENTREGADO_CLIENTE_INT) {
                    return response()->json([
                        'html' => '<div class="alert alert-warning">Este pedido ya fue entregado</div>',
                        'success' => false,
                        'pedido' => $pedido,
                        'dirgrupo' => $dirgrupo,
                    ]);
                }
            } else {
                if ($pedido->estado_sobre == 0) {
                    return response()->json([
                        'html' => '<div class="alert alert-warning">Este no tiene direccion agregada</div>',
                        'success' => false,
                        'pedido' => $pedido,
                        'dirgrupo' => $dirgrupo,
                    ]);
                }
            }
            $distritos = Distrito::whereIn('provincia', ['LIMA', 'CALLAO'])
                ->where('estado', '1')
                ->WhereNotIn('distrito', ['CHACLACAYO', 'CIENEGUILLA', 'LURIN', 'PACHACAMAC', 'PUCUSANA', 'PUNTA HERMOSA', 'PUNTA NEGRA', 'SAN BARTOLO', 'SANTA MARIA DEL MAR'])
                ->select([
                    'distrito',
                    DB::raw("concat(distrito,' - ',zona) as distritonam"),
                    'zona'
                ])->get()->map(function ($d) {
                    $d->zona = trim($d->zona);
                    return $d;
                });
            $departamento = Departamento::where('estado', "1")
                ->pluck('departamento', 'departamento');
            return response()->json([
                'html' => view('sobres.modal.modal_editar_envio_ajax', compact('pedido', 'dirgrupo', 'distritos', 'departamento'))->render(),
                'success' => true,
                'pedido' => $pedido,
                'dirgrupo' => $dirgrupo,
            ]);
        }
        return response()->json([
            'html' => '<h5>Pedido no encontrado</h5>',
            'success' => false,
        ]);
    }

    public function updateDireccionGrupo(Request $request)
    {
        $pedido_id = (int)$request->get('pedido_id');
        if ($pedido_id > 0) {
            $pedido = Pedido::query()->with('direcciongrupo')->findOrFail($pedido_id);
            $dirgrupo = $pedido->direcciongrupo;
            if (Str::upper($pedido->destino ?: '') != 'PROVINCIA') {//para lima
                $data = [
                    'zona' => $pedido->env_zona,
                    'destino' => $pedido->destino,
                    'nombre_cliente_recibe' => $request->nombre,
                    'celular_cliente_recibe' => $request->celular,
                    'direccion' => $request->direccion,
                    'referencia' => $request->referencia,
                    'distrito' => $request->distrito,
                    'observacion' => $request->observacion,
                    'cambio_direccion_sustento' => $request->cambio_direccion_sustento,
                ];
            } else {//para provincia
                $routulo = '';
                if ($request->hasFile('rotulo')) {
                    $routulo = $request->file('rotulo')->store('pedidos/rotulos', 'pstorage');
                }
                $data = [
                    'zona' => $pedido->env_zona,
                    'destino' => $pedido->destino,
                    'cambio_direccion_sustento' => $request->cambio_direccion_sustento,
                    'env_numregistro' => $request->numregistro,
                    'env_tracking' => $request->tracking,
                    'env_importe' => $request->importe,
                    'env_rotulo' => $routulo,
                ];
            }

            if ($dirgrupo != null) {
                DireccionGrupo::cambiarDireccion($dirgrupo, $pedido, $data);
            } else {
                if (Str::upper($pedido->destino ?: '') != 'PROVINCIA') {//para lima
                    $pedido->update([
                        'env_nombre_cliente_recibe' => $data['nombre_cliente_recibe'],
                        'env_celular_cliente_recibe' => $data['celular_cliente_recibe'],
                        'env_direccion' => $data['direccion'],
                        'env_referencia' => $data['referencia'],
                        'env_distrito' => $data['distrito'],
                        'env_observacion' => $data['observacion'],
                        'cambio_direccion_sustento' => null,
                        'cambio_direccion_at' => null,
                    ]);
                } else {
                    $pedido->update([
                        'env_numregistro' => $data['env_numregistro'],
                        'env_tracking' => $data['env_tracking'],
                        'env_importe' => $data['env_importe'],
                        'env_rotulo' => $data['env_rotulo'],
                        'cambio_direccion_at' => null,
                        'cambio_direccion_sustento' => null,
                    ]);
                }
                if ($pedido->condicion_envio_code == Pedido::RECEPCION_COURIER_INT) {
                    GrupoPedido::desvincularPedido($pedido, true, true);
                }
            }
            return response()->json([
                'suucess' => true
            ]);
        }
        return response()->json([
            'suucess' => false
        ]);
    }

    public function DireccionEnvio(Request $request)
    {


        $attach_pedidos_data = [];
        $pedidos = $request->pedidos;
        if (!$request->pedidos) {
            return '0';
        } else {


            $_destino = $request->destino;
            $_pedido = Pedido::find($request->cod_pedido);

            $lista_productos = '';
            $lista_codigos = '';
            $pedidos = $request->pedidos;
            $array_pedidos = collect(explode(",", $pedidos))->filter()->map(function ($id) {
                return intval($id);
            })->all();

            $data = DetallePedido::activo()->whereIn("pedido_id", $array_pedidos)->get();
            foreach ($data as $dat) {
                $lista_productos .= $dat->nombre_empresa . ", ";
                $lista_codigos .= $dat->codigo . ", ";
            }
            $lista_codigos = rtrim($lista_codigos, ", ");
            $lista_productos = rtrim($lista_productos, ", ");

            $cliente = Cliente::where("id", $request->cliente_id)->first();
            $count_pedidos = count((array)$array_pedidos);

            $usuario = Cliente::find($request->cliente_id);
            $usuario_id = $usuario->user_id;

            $identi = User::find($usuario_id);
            $identi_id = $identi->identificador;

            DB::beginTransaction();
            if ($request->destino == "LIMA") {
                $zona_distrito = Distrito::where('distrito', $request->distrito)->first();
                $cantidad = $count_pedidos;

                $modelData = [
                    'cliente_id' => $request->cliente_id,
                    'distrito' => $request->distrito,
                    'direccion' => $request->direccion,
                    'referencia' => $request->referencia,
                    'nombre' => $request->nombre,
                    'celular' => $request->contacto,
                    'observacion' => $request->observacion,
                    //'direcciongrupo' => $direccion_grupo_id,
                    'cantidad' => $cantidad,
                    'destino' => $request->destino,
                    'estado' => '1',
                    "salvado" => "0"
                ];
                if (intval($request->model_id) > 0) {
                    $direccionLima = DireccionEnvio::query()->find($request->model_id);
                    if ($direccionLima != null) {
                        unset($modelData['salvado']);
                        $direccionLima->update($modelData);
                    } else {
                        $direccionLima = DireccionEnvio::create($modelData);
                    }
                } else {
                    $direccionLima = DireccionEnvio::create($modelData);
                }


                $pedido_id = $request->pedido_id;
                $contPe = 0;

                $destino_temp = $request->destino;
                if ($destino_temp == 'PROVINCIA') {
                    $destino_temp = 'LIMA';
                }

                foreach ($array_pedidos as $pedido_id) {
                    $pedido = Pedido::find($pedido_id);
                    $pedido->update([
                        'estado_sobre' => '1',
                        'destino' => $request->destino,
                        'direccion' => $request->direccion,
                        'env_destino' => $destino_temp,
                        'env_distrito' => $request->distrito,
                        'env_zona' => $zona_distrito->zona,
                        'env_nombre_cliente_recibe' => $request->nombre,
                        'env_celular_cliente_recibe' => $request->contacto,
                        'env_cantidad' => $count_pedidos,
                        'env_direccion' => $request->direccion,
                        'env_tracking' => '',
                        'env_referencia' => $request->referencia,
                        'env_numregistro' => '',
                        'env_rotulo' => '',
                        'env_observacion' => $request->observacion,
                        'env_importe' => '',
                    ]);
                    $dp_empresa = DetallePedido::activo()->where("pedido_id", $pedido_id)->first();
                    if ($pedido->condicion_envio_code == Pedido::RECEPCION_COURIER_INT) {
                        $attach_pedidos_data[$pedido->id] = [
                            'razon_social' => $dp_empresa->nombre_empresa,
                            'codigo' => $dp_empresa->codigo,
                        ];
                    }
                    $direccionPedido = DireccionPedido::create([
                        'direccion_id' => $direccionLima->id,
                        'pedido_id' => $pedido_id,
                        'codigo_pedido' => $dp_empresa->codigo,
                        //'direcciongrupo' => $direccion_grupo_id,
                        'empresa' => $dp_empresa->nombre_empresa,
                        'estado' => '1'
                    ]);
                }

                if ($request->saveHistoricoLima == "1") {
                    $direccionLima->update([
                        "salvado" => "1"
                    ]);
                }
            }
            $file_name_temp = '';
            if ($request->destino == "PROVINCIA") {

                $cliente = Cliente::where("id", $request->cliente_id)->first();
                $count_pedidos = count((array)$array_pedidos);

                $cantidad = $count_pedidos;

                $usuario = Cliente::find($request->cliente_id);
                $usuario_id = $usuario->user_id;

                $identi = User::find($usuario_id);
                $identi_id = $identi->identificador;

                $file_name = $request->file('rotulo')->store('entregas', 'pstorage');


                $modelData = [
                    'cliente_id' => $request->cliente_id,
                    'user_id' => Auth::user()->id,
                    'tracking' => $request->tracking,
                    'registro' => $request->numregistro,
                    'foto' => $file_name,
                    'importe' => $request->importe,
                    'cantidad' => $cantidad,
                    //'direcciongrupo' => $direccion_grupo_id,
                    'destino' => $request->destino,
                    'estado' => '1',
                    "salvado" => "0"
                ];
                if (intval($request->model_id) > 0) {
                    $gastoProvincia = GastoEnvio::find($request->model_id);
                    if ($gastoProvincia != null) {
                        unset($modelData['salvado']);
                        $gastoProvincia->update($modelData);
                    } else {
                        $gastoProvincia = GastoEnvio::create($modelData);
                    }
                } else {
                    $gastoProvincia = GastoEnvio::create($modelData);
                }

                foreach ($array_pedidos as $pedido_id) {
                    $pedido = Pedido::find($pedido_id);

                    $pedido->update([
                        'estado_sobre' => '1',
                        'destino' => $request->destino,
                        //'condicion_envio' => 2,//AL REGISTRAR DIRECCION PASA A ESTADO  EN REPARTO
                        'direccion' => 'PROVINCIA',
                        //'condicion_envio' => Pedido::SEGUIMIENTO_PROVINCIA_COURIER,
                        //'condicion_envio_code' => Pedido::SEGUIMIENTO_PROVINCIA_COURIER_INT,
                        'env_destino' => 'LIMA',
                        'env_distrito' => 'LOS OLIVOS',
                        'env_zona' => 'OLVA',
                        'env_nombre_cliente_recibe' => 'OLVA',
                        'env_celular_cliente_recibe' => 'OLVA',
                        'env_cantidad' => $count_pedidos,
                        'env_direccion' => 'OLVA',
                        'env_tracking' => $request->tracking,
                        'env_referencia' => '',
                        'env_numregistro' => $request->numregistro,
                        'env_rotulo' => $file_name,
                        'env_observacion' => '',
                        'env_importe' => $request->importe,
                    ]);

                    $dp_empresa = DetallePedido::activo()->where("pedido_id", $pedido_id)->first();
                    if ($pedido->condicion_envio_code == Pedido::RECEPCION_COURIER_INT) {
                        $attach_pedidos_data[$pedido->id] = [
                            'razon_social' => $dp_empresa->nombre_empresa,
                            'codigo' => $dp_empresa->codigo,
                        ];
                    }
                }

                if ($request->saveHistoricoProvincia == "1") {
                    //temporal lima
                    $gastoProvincia->update([
                        "salvado" => "1"
                    ]);
                }
            }

            if (count($attach_pedidos_data) > 0) {
                if ($request->destino == "LIMA") {
                    $grupoPedido = GrupoPedido::createGroupByArray([
                        "zona" => $zona_distrito->zona,
                        "provincia" => $zona_distrito->provincia,
                        'distrito' => $zona_distrito->distrito,
                        'direccion' => (($request->destino == 'PROVINCIA') ? 'OLVA' : $request->direccion),
                        'referencia' => (($request->destino == 'PROVINCIA') ? $request->tracking : $request->referencia),
                        'cliente_recibe' => ($request->nombre),
                        'telefono' => ($request->contacto),
                    ]);
                } else {
                    $grupoPedido = GrupoPedido::createGroupByArray([
                        "zona" => 'OLVA',
                        "provincia" => 'OLVA',
                        'distrito' => '--',
                        'direccion' => 'OLVA',
                        'referencia' => $request->tracking,
                        'cliente_recibe' => $request->nombre,
                        'telefono' => $request->contacto,
                    ]);
                }
                $grupoPedido->pedidos()->syncWithoutDetaching($attach_pedidos_data);
            }
            DB::commit();
            return response()->json(['html' => $pedidos]);
        }

        return redirect()->route('envios.index')->with('info', 'actualizado');
    }

    public function UpdateDireccionEnvio(Request $request, DireccionEnvio $direccion)
    {
        $direccion->update([
            'distrito' => $request->distrito,
            'direccion' => $request->direccion,
            'referencia' => $request->referencia,
            'nombre' => $request->nombre,
            'celular' => $request->celular,
            'estado' => '1'
        ]);

        return redirect()->route('envios.index')->with('info', 'actualizado');
    }

    public function createDireccion(Pedido $pedido)
    {
        $destinos = [
            "LIMA" => 'LIMA',
            "PROVINCIA" => 'PROVINCIA'
        ];

        $distritos = Distrito::whereIn('provincia', ['LIMA', 'CALLAO'])
            ->where('estado', '1')
            ->pluck('distrito', 'distrito');

        $clientes = Cliente::where('estado', '1')
            ->where('id', $pedido->user_id)
            ->first();
        $pedidos = Pedido::join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->select('pedidos.id',
                'dp.codigo')
            ->where('pedidos.cliente_id', $pedido->cliente_id)
            ->where('pedidos.destino', null)
            ->where('pedidos.direccion', '0')
            ->where('pedidos.envio', '>', '0')
            ->where('pedidos.estado', '1')
            ->get();

        return view('pedidos.createDireccion', compact('destinos', 'distritos', 'clientes', 'pedidos'));
    }


    public function SinEnviarid(Request $request)
    {
        //Pedido $pedido
        $pedido = Pedido::where("id", $request->hiddenSinenvio)->first();
        $data = DetallePedido::where("pedido_id", $request->hiddenSinenvio)->first();
        $detalle_pedidos = DetallePedido::where('pedido_id', $pedido->id)->first();
        $fecha = Carbon::now();


        PedidoMovimientoEstado::create([
            'pedido' => $request->hiddenSinenvio,
            'condicion_envio_code' => Pedido::ENTREGADO_SIN_SOBRE_OPE_INT,
            'notificado' => 0
        ]);


        $pedido->update([
            'envio' => '3',//SIN ENVIO
            //'condicion_envio' => DireccionGrupo::CE_ENTREGADO,
            //'condicion_envio_code' => DireccionGrupo::CE_ENTREGADO_CODE,
            'condicion_envio' => Pedido::ENTREGADO_SIN_SOBRE_OPE,
            'condicion_envio_code' => Pedido::ENTREGADO_SIN_SOBRE_OPE_INT,
            'condicion_envio_at' => now(),

            //  'condicion_envio' => 'ENTREGADO',
            //  'condicion_envio_code' => 10 ,
            'modificador' => 'USER' . Auth::user()->id
        ]);

        $detalle_pedidos->update([
            'fecha_envio_doc_fis' => $fecha,
            'fecha_recepcion' => $fecha,
            'atendido_por' => Auth::user()->name,
            'atendido_por_id' => Auth::user()->id,
            'pedido_id' => $request->hiddenSinenvio
        ]);

        /**/
        $cliente = Cliente::where("id", $pedido->cliente_id)->first();

        $data = DetallePedido::where("pedido_id", $request->hiddenSinenvio)->first();

        $direccion_grupo_id = DireccionGrupo::create([
            'estado' => '1',
            'destino' => 'LIMA',
            'distribucion' => '',

            'condicion_envio' => Pedido::ENTREGADO_SIN_SOBRE_OPE,
            'condicion_envio_code' => Pedido::ENTREGADO_SIN_SOBRE_OPE_INT,
            'condicion_envio_at' => now(),

            'condicion_sobre' => 'SIN ENVIO',
            'codigos' => $data->codigo,
            'producto' => $data->nombre_empresa,
        ])->id;

        $direccion_grupo = DireccionGrupo::find($direccion_grupo_id);
        $direccion_grupo->correlativo = 'ENV' . $direccion_grupo_id;
        $direccion_grupo->save();

        $direccionLima = DireccionEnvio::create([
            'cliente_id' => $pedido->cliente_id,
            'distrito' => 'LIMA',
            'direccion' => '',
            'referencia' => '',
            'nombre' => $cliente->nombre,
            'celular' => $cliente->celular,
            'observacion' => '',
            'direcciongrupo' => $direccion_grupo_id,
            'cantidad' => 1,
            'destino' => 'LIMA',
            'estado' => '1',
            "salvado" => "0"
        ]);


        $direccionPedido = DireccionPedido::create([
            'direccion_id' => $direccionLima->id,
            'pedido_id' => $pedido->id,
            'codigo_pedido' => $detalle_pedidos->codigo,
            'direcciongrupo' => $direccion_grupo_id,
            'empresa' => $detalle_pedidos->nombre_empresa,
            'estado' => '1'
        ]);

        return response()->json(['html' => $pedido->id]);

    }


    /* Esta funciòn actualiza al estado envio 4*/


    public function Enviarid(Request $request)
    {
        $pedido = Pedido::where("id", $request->hiddenEnvio)->first();
        $detalle_pedidos = DetallePedido::where('pedido_id', $pedido->id)->first();
        $fecha = Carbon::now();

        $pedido->update([
            //'envio' => '1',
            'estado_sinconsobre' => '1',
            'fecha_envio_atendido_op' => Carbon::now(),
            'condicion_envio' => Pedido::ENVIADO_OPE,
            'condicion_envio_code' => Pedido::ENVIADO_OPE_INT,
            'modificador' => 'USER' . Auth::user()->id,
            'condicion_envio_at' => now(),
        ]);

        PedidoMovimientoEstado::create([
            'pedido' => $request->hiddenEnvio,
            'condicion_envio_code' => Pedido::ENVIADO_OPE_INT,
            'notificado' => 0
        ]);


        $detalle_pedidos->update([
            'fecha_envio_doc_fis' => $fecha,
        ]);

        return response()->json(['html' => $pedido->id]);


    }

    public function confirmarRecepcionID(Request $request)
    {
        $pedido = Pedido::where("id", $request->hiddenEnvio)->first();

        $pedido->update([
            'modificador' => 'USER' . Auth::user()->id,
            'condicion_envio' => Pedido::ENTREGADO_SIN_SOBRE_CLIENTE,
            'condicion_envio_code' => Pedido::ENTREGADO_SIN_SOBRE_CLIENTE_INT,
            'condicion_envio_at' => now(),
            'fecha_recepcion_courier' => now()
        ]);
        PedidoMovimientoEstado::create([
            'pedido' => $request->hiddenEnvio,
            'condicion_envio_code' => Pedido::ENTREGADO_SIN_SOBRE_CLIENTE_INT,
            'notificado' => 0
        ]);

        return response()->json(['html' => $pedido->id]);
    }

    public function RecibirPedidoOP(Request $request)
    {
        $pedido = Pedido::where("id", $request->hiddenEnvio)->first();

        $pedido->update([
            'envio' => '2',
            'modificador' => 'USER' . Auth::user()->id,
            'fecha_envio_op_courier' => Carbon::now(),
            'condicion_envio' => Pedido::RECIBIDO_JEFE_OPE,
            'condicion_envio_code' => Pedido::RECIBIDO_JEFE_OPE_INT,
            'condicion_envio_at' => now(),

        ]);

        PedidoMovimientoEstado::create([
            'pedido' => $request->hiddenEnvio,
            'condicion_envio_code' => Pedido::ENVIO_COURIER_JEFE_OPE_INT,
            'notificado' => 0
        ]);

        return response()->json(['html' => $pedido->id]);
    }

    public function AtenderPedidoOP(Request $request)
    {
        $pedido = Pedido::where("id", $request->hiddenEnvio)->first();

        $pedido->update([
            'envio' => '2',
            'modificador' => 'USER' . Auth::user()->id,
            'condicion_envio' => Pedido::ENVIO_COURIER_JEFE_OPE,
            'condicion_envio_code' => Pedido::ENVIO_COURIER_JEFE_OPE_INT,
            'condicion_envio_at' => now(),

        ]);

        PedidoMovimientoEstado::create([
            'pedido' => $request->hiddenEnvio,
            'condicion_envio_code' => Pedido::RECEPCION_COURIER_INT,
            'notificado' => 0
        ]);

        return response()->json(['html' => $pedido->id]);
    }

    public function Estadosobres()
    {
        $ver_botones_accion = 1;

        if (Auth::user()->rol == "Asesor") {
            $ver_botones_accion = 0;
        } else if (Auth::user()->rol == "Super asesor") {
            $ver_botones_accion = 0;
        } else if (Auth::user()->rol == "Encargado") {
            $ver_botones_accion = 1;
        } else {
            $ver_botones_accion = 1;
        }


        $_pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
            ->join('users as u', 'pedidos.user_id', 'u.id')
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->select(
                DB::raw("COUNT(u.identificador) AS total, u.identificador ")
            )
            ->where('pedidos.estado', '1')
            ->whereIn('pedidos.condicion_envio_code', [Pedido::RECEPCION_COURIER_INT])
            ->where('dp.estado', '1')
            ->groupBy('u.identificador');


        $distritos = Distrito::whereIn('provincia', ['LIMA', 'CALLAO'])
            ->where('estado', '1')
            ->WhereNotIn('distrito', ['CHACLACAYO', 'CIENEGUILLA', 'LURIN', 'PACHACAMAC', 'PUCUSANA', 'PUNTA HERMOSA', 'PUNTA NEGRA', 'SAN BARTOLO', 'SANTA MARIA DEL MAR'])
            ->pluck('distrito', 'distrito');

        $departamento = Departamento::where('estado', "1")
            ->pluck('departamento', 'departamento');

        $superasesor = User::where('rol', 'Super asesor')->count();

        $_pedidos = $_pedidos->get();

        return view('envios.estadosobres', compact('superasesor', 'ver_botones_accion', 'distritos', 'departamento', '_pedidos'));
    }

    public function Estadosobrestabla(Request $request)
    {
        $opcion = $request->opcion;
        if ($opcion == 'recepcionado' || $opcion == 'anulado' || $opcion == 'anulado_courier') {
            $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
                ->join('users as u', 'pedidos.user_id', 'u.id')
                ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
                ->select([
                    'pedidos.*',
                    'u.identificador as users',
                    'dp.codigo as codigos',
                    'dp.nombre_empresa as empresas',
                    'dp.total as total',
                    'pedidos.created_at as fecha',
                    'dp.envio_doc',
                    'dp.fecha_envio_doc',
                    'dp.cant_compro',
                    DB::raw(" (CASE WHEN pedidos.condicion='ANULADO' THEN pedidos.fecha_anulacion_confirm
                                    else pedidos.fecha_recepcion_courier end) as fecha_recepcion_courier_anulado "),
                    'dp.foto1',
                    'dp.foto2',
                    'dp.fecha_recepcion',
                    DB::raw(" (CASE WHEN pedidos.condicion='ANULADO' THEN DATEDIFF(DATE(NOW()), DATE(pedidos.fecha_anulacion_confirm))
                                    else DATEDIFF(DATE(NOW()), DATE(pedidos.fecha_recepcion_courier)) end) as dias "),
                ]);
            if ($opcion == 'recepcionado') {
                $pedidos = $pedidos->where('pedidos.estado', '1')->whereIn('pedidos.condicion_envio_code', [Pedido::RECEPCION_COURIER_INT]);
            } else if ($opcion == 'anulado') {
                $pedidos = $pedidos->where('pedidos.estado', '0')->whereNull('pedidos.direccion_grupo');
            } else if ($opcion == 'anulado_courier') {
                $pedidos = $pedidos->where('pedidos.estado', '0')->whereNotNull('pedidos.direccion_grupo');
            }
        } else if ($opcion == 'entregado') {
            $pedidos = DireccionGrupo::/*join('direccion_envios as de', 'direccion_grupos.id', 'de.direcciongrupo')*/
            join('clientes as c', 'c.id', 'direccion_grupos.cliente_id')
                ->join('users as u', 'u.id', 'c.user_id')
                ->where('direccion_grupos.estado', '1')
                ->whereIn('direccion_grupos.condicion_envio_code', [
                    Pedido::ENTREGADO_CLIENTE_INT,
                    Pedido::ENTREGADO_SIN_SOBRE_OPE_INT,
                    Pedido::ENTREGADO_SIN_SOBRE_CLIENTE_INT
                ])
                ->select(
                    'direccion_grupos.*',
                    DB::raw("DATE_FORMAT(direccion_grupos.fecha_recepcion, '%Y-%m-%d %H:%i:%s') as fechaentrega"),
                );
        }

        if ($opcion == 'recepcionado' || $opcion == 'anulado' || $opcion == 'anulado_courier') {
            return Datatables::of(DB::table($pedidos))
                ->addColumn('condicion_envio_color', function ($pedido) {
                    return Pedido::getColorByCondicionEnvio($pedido->condicion_envio);
                })
                ->editColumn('condicion_envio', function ($pedido) {
                    $badge_estado = '';
                    if ($pedido->pendiente_anulacion == '1') {
                        $badge_estado .= '<span class="badge badge-success">' . Pedido::PENDIENTE_ANULACION . '</span>';
                        return $badge_estado;
                    }
                    if ($pedido->condicion_code == '4' || $pedido->estado == '0') {
                        return '<span class="badge badge-danger">ANULADO</span>';
                    }
                    if ($pedido->estado_sobre == '1') {
                        $badge_estado .= '<span class="badge badge-dark p-8" style="color: #fff; background-color: #347cc4; font-weight: 600; margin-bottom: -2px;border-radius: 4px 4px 0px 0px; font-size:8px;  padding: 4px 4px !important; font-weight: 500;">Direccion agregada</span><br>';
                    }
                    if ($pedido->estado_ruta == '1') {
                        $badge_estado .= '<span class="badge badge-success" style="background-color: #00bc8c !important;
                            padding: 4px 8px !important;
                            font-size: 8px;
                            margin-bottom: -4px;
                            color: black !important;">Con ruta</span>';
                    }
                    $color = Pedido::getColorByCondicionEnvio($pedido->condicion_envio);
                    $badge_estado .= '<span class="badge badge-success" style="background-color: ' . $color . '!important;">' . $pedido->condicion_envio . '</span>';
                    return $badge_estado;
                })
                ->addColumn('action', function ($pedido) {
                    $btn = [];
                    return join('', $btn);
                })
                ->rawColumns(['action', 'condicion_envio_color', 'condicion_envio'])
                ->make(true);
        } else if ($opcion == 'entregado') {
            return Datatables::of(DB::table($pedidos))
                ->editColumn('condicion_envio', function ($pedido) {
                    $color = Pedido::getColorByCondicionEnvio($pedido->condicion_envio);

                    $badge_estado = '';

                    $badge_estado .= '<span class="badge badge-dark p-8" style="color: #fff; background-color: #347cc4; font-weight: 600; margin-bottom: -2px;border-radius: 4px 4px 0px 0px; font-size:8px;  padding: 4px 4px !important; font-weight: 500;">Direccion agregada</span>';

                    $badge_estado .= '<span class="badge badge-success" style="background-color: #00bc8c !important;
                    padding: 4px 8px !important;
                    font-size: 8px;
                    margin-bottom: -4px;
                    color: black !important;">Con ruta</span>';

                    $badge_estado .= '<span class="badge badge-success" style="background-color: ' . $color . '!important;">' . $pedido->condicion_envio . '</span>';
                    return $badge_estado;
                })
                ->editColumn('foto1', function ($pedido) {
                    if ($pedido->foto1 != null) {
                        $urlimagen1 = \Storage::disk('pstorage')->url($pedido->foto1);

                        $data = '<div class="card bg-transparent text-center border-none border-left-0 shadow-none " style="width: 8rem;border: none;">
                          <a href="" data-target="#modal-imagen" data-toggle="modal" data-imagen="' . $pedido->foto1 . '">
                            <img src="' . $urlimagen1 . '" alt="' . $pedido->foto1 . '" height="50px" width="50px" id="imagen_' . $pedido->id . '-1" class=" text-center">
                            </a>
                      <div class="card-body bg-transparent p-0">
                        <h5 class="card-title"></h5>';


                        //$data='<a download href="' . $urlimagen1 . '" class="text-center btn btn-block btn-outline-secondary">Descargar </a>';
                        //$data='<a href="" data-target="#modal-cambiar-imagen" data-toggle="modal" data-item="1" data-imagen="' . $pedido->foto1 . '" data-pedido="' . $pedido->id . '" class="btn btn-block btn-outline-danger">Cambiar</a>';

                        if (Auth::user()->rol == "Asesor") {
                            $data .= '<a href="" data-target="#modal-delete-foto1" data-toggle="modal" data-deletefoto1="' . $pedido->id . '">
                        <button class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i></button>
                        </a>';
                        }

                        $data .= '</div>
                    </div>';

                        return $data;
                    } else if ($pedido->condicion_envio_code == Pedido::ENTREGADO_SIN_SOBRE_OPE_INT) {
                        return '<span class="badge badge-dark">Sin envio</span>';
                    } else {
                        return '';
                    }
                })
                ->editColumn('foto2', function ($pedido) {
                    if ($pedido->foto2 != null) {
                        $urlimagen1 = \Storage::disk('pstorage')->url($pedido->foto2);

                        $data = '<div class="card bg-transparent text-center border-none border-left-0 shadow-none " style="width: 8rem;border: none;">
                          <a href="" data-target="#modal-imagen" data-toggle="modal" data-imagen="' . $pedido->foto2 . '">
                            <img src="' . $urlimagen1 . '" alt="' . $pedido->foto2 . '" height="50px" width="50px" id="imagen_' . $pedido->id . '-1" class=" text-center">
                            </a>
                      <div class="card-body bg-transparent p-0">
                        <h5 class="card-title"></h5>';

                        //$data='<a download href="' . $urlimagen1 . '" class="text-center btn btn-block btn-outline-secondary">Descargar </a>';
                        //$data='<a href="" data-target="#modal-cambiar-imagen" data-toggle="modal" data-item="2" data-imagen="' . $pedido->foto2 . '" data-pedido="' . $pedido->id . '" class="btn btn-block btn-outline-danger">Cambiar</a>';

                        if (Auth::user()->rol == "Asesor") {
                            $data .= '<a href="" data-target="#modal-delete-foto2" data-toggle="modal" data-deletefoto1="' . $pedido->id . '">
                        <button class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i></button>
                        </a>';
                        }

                        $data .= '</div>
                    </div>';
                        return $data;
                    } else if ($pedido->condicion_envio_code == Pedido::ENTREGADO_SIN_SOBRE_OPE_INT) {
                        return '<span class="badge badge-dark">Sin envio</span>';
                    } else {
                        return '';
                    }
                })
                ->editColumn('foto3', function ($pedido) {
                    if ($pedido->foto3 != null) {
                        $urlimagen1 = \Storage::disk('pstorage')->url($pedido->foto3);

                        $data = '<div class="card bg-transparent text-center border-none border-left-0 shadow-none " style="width: 8rem;border: none;">
                          <a href="" data-target="#modal-imagen" data-toggle="modal" data-imagen="' . $pedido->foto3 . '">
                            <img src="' . $urlimagen1 . '" alt="' . $pedido->foto3 . '" height="50px" width="50px" id="imagen_' . $pedido->id . '-1" class=" text-center">
                            </a>
                      <div class="card-body bg-transparent p-0">
                        <h5 class="card-title"></h5>';

                        //$data='<a download href="' . $urlimagen1 . '" class="text-center btn btn-block btn-outline-secondary"> Descargar </a>';
                        //$data='<a href="" data-target="#modal-cambiar-imagen" data-toggle="modal" data-item="3" data-imagen="' . $pedido->foto3 . '" data-pedido="' . $pedido->id . '" class="btn btn-block btn-outline-danger">Cambiar</a>';

                        if (Auth::user()->rol == "Asesor") {
                            $data .= '<a href="" data-target="#modal-delete-foto3" data-toggle="modal" data-deletefoto1="' . $pedido->id . '">
                        <button class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i></button>
                        </a>';
                        }

                        $data .= '</div>
                    </div>';
                        return $data;
                    } else if ($pedido->condicion_envio_code == Pedido::ENTREGADO_SIN_SOBRE_OPE_INT) {
                        return '<span class="badge badge-dark">Sin envio</span>';
                    } else {
                        return '';
                    }
                })
                ->addColumn('action', function ($pedido) {
                    $btn = [];
                    return join('', $btn);
                })
                ->rawColumns(['foto1', 'foto2', 'foto3', 'action', 'condicion_envio'])
                ->make(true);
        }

    }

    public function ValidarOPBarra(Request $request)
    {


        //VARIABLES GLOBALES
        $responsable = $request->responsable;
        $accion = $request->accion;
        $codigo = $request->codigo;

        //BUSCAMOS EL PEDIDO

        $pedido = Pedido::where("codigo", $codigo)->first();
        $condicion_code_actual = $pedido->condicion_envio_code;

        if ($pedido == null) {
            return response()->json([
                'success' => true
            ]);
        }

        /************
         * SETEAMOS VALORES POR DEFECTO
         */
        $nuevo_estado = $condicion_code_actual;


        // SI SON SOBRES DEVUELTOS
        if ($accion == "sobres_devuelto") {
            $condicion_code_actual = 100;
        }

        /**************
         * SETEAMOS OPCION ADICIONAL
         */
        // SI EXISTE UNA OPCION ADICIONAL LA INICIALIZAMOS AQUI
        if (isset($request->extra)) {
            $opcion_adicional = $request->extra;
        } else {
            $opcion_adicional = "";
        }
        /*************
         * SETEAMOS EL NUEVO ESTADO Y EL MENSAJE DE CONFIRMACION
         */

        switch ($responsable) {

            // FERNANDEZ RECEPCIONA LOS SOBRES
            case "fernandez_recepcion":

                switch ($condicion_code_actual) {
                    case Pedido::ENVIO_COURIER_JEFE_OPE_INT: // 8
                        $nuevo_estado = Pedido::RECEPCION_COURIER_INT; // 19
                        $respuesta = "El jefe Courier recepciono correctamente el pedido";
                        break;
                }
                break;

            // ENVIA SOBRES A MOTORIZADO
            case "fernandez_reparto":

                switch ($condicion_code_actual) {
                    case Pedido::REPARTO_COURIER_INT: // 8
                        $nuevo_estado = Pedido::ENVIO_MOTORIZADO_COURIER_INT; // 19
                        $respuesta = "El sobre se envió a motorizado correctamente.";
                        break;
                }
                break;
            // CONFIRMA SOBRES DEVUELTOS
            case "fernandez_devuelto":
                switch ($condicion_code_actual) {
                    case 100: // CODIGO EN DURO
                        $nuevo_estado = Pedido::RECEPCION_COURIER_INT; // 11
                        $respuesta = "El sobre fue devuelto exitosamente.";
                        break;
                }
                break;
            //ENVIO A COURIER JEFE OPE
            case "maria_courier":
                switch ($condicion_code_actual) {
                    case Pedido::RECIBIDO_JEFE_OPE_INT:
                        $nuevo_estado = Pedido::ENVIO_COURIER_JEFE_OPE_INT;
                        $respuesta = "El pedido se envió a Logistica correctamente.";
                        break;
                }
                break;
            // RECEPCION DE SOBRE POR MARIA
            case "maria_recepcion":
                switch ($condicion_code_actual) {
                    case Pedido::ENVIADO_OPE_INT:
                        $nuevo_estado = Pedido::RECIBIDO_JEFE_OPE_INT;
                        $respuesta = "El sobre se recibio correctamente.";
                        break;
                }
                break;
            // ENTREGA MARIA SIN SOBRE
            case "maria_entregado_sin_sobre":
                switch ($condicion_code_actual) {
                    case Pedido::ENTREGADO_SIN_SOBRE_OPE_INT: // 13
                        $nuevo_estado = Pedido::ENTREGADO_SIN_SOBRE_CLIENTE_INT; // 14
                        $respuesta = "El pedido sin sobre se confirmo correctamente.";
                        break;
                }
                break;
        }
        /***************
         * COMPROBAMOS SI YA ESTA ATENDIDO EL PEDIDO
         */
        return response()->json([
            'success' => $pedido->condicion_envio_code == $nuevo_estado
        ]);

    }

    public function ConfirmarOPBarra(Request $request)
    {
        /*************
         * RECUPERAMOS VARIABLES
         */

        //VARIABLES GLOBALES
        $responsable = $request->responsable;
        $accion = $request->accion;
        $codigo = $request->hiddenCodigo;
        $tipo = $request->tipo; // PEDIDO O PAQUETE

        /*************
         * IDENTIFICAMOS LOS DATOS GLOBALES
         */
        $pedido = Pedido::where("codigo", $codigo)->firstOrFail();
        $condicion_code_actual = $pedido->condicion_envio_code;

        /************
         * SETEAMOS VALORES POR DEFECTO
         */
        $nuevo_estado = $condicion_code_actual;
        $respuesta = "";

        // SI SON SOBRES DEVUELTOS
        if ($accion == "sobres_devuelto") {
            $condicion_code_actual = 100;
        }

        /**************
         * SETEAMOS OPCION ADICIONAL
         */
        // SI EXISTE UNA OPCION ADICIONAL LA INICIALIZAMOS AQUI
        if (isset($request->extra)) {
            $opcion_adicional = $request->extra;
        } else {
            $opcion_adicional = "";
        }
        /*************
         * SETEAMOS EL NUEVO ESTADO Y EL MENSAJE DE CONFIRMACION
         */

        switch ($responsable) {

            // FERNANDEZ RECEPCIONA LOS SOBRES
            case "fernandez_recepcion":

                switch ($condicion_code_actual) {
                    case Pedido::ENVIO_COURIER_JEFE_OPE_INT: // 8
                        $nuevo_estado = Pedido::RECEPCION_COURIER_INT; // 19
                        $respuesta = "El jefe Courier recepciono correctamente el pedido";
                        break;
                }
                break;

            // ENVIA SOBRES A MOTORIZADO
            case "fernandez_reparto":

                switch ($condicion_code_actual) {
                    case Pedido::REPARTO_COURIER_INT: // 8
                        $nuevo_estado = Pedido::ENVIO_MOTORIZADO_COURIER_INT; // 19
                        $respuesta = "El sobre se envió a motorizado correctamente.";
                        break;
                }
                break;
            // CONFIRMA SOBRES DEVUELTOS
            case "fernandez_devuelto":
                switch ($condicion_code_actual) {
                    case 100: // CODIGO EN DURO
                        $nuevo_estado = Pedido::RECEPCION_COURIER_INT; // 11
                        $respuesta = "El sobre fue devuelto exitosamente.";
                        break;
                }
                break;
            //ENVIO A COURIER JEFE OPE
            case "maria_courier":
                switch ($condicion_code_actual) {
                    case Pedido::RECIBIDO_JEFE_OPE_INT:
                        $nuevo_estado = Pedido::ENVIO_COURIER_JEFE_OPE_INT;
                        $respuesta = "El pedido se envió a Logistica correctamente.";
                        break;
                }
                break;
            // RECEPCION DE SOBRE POR MARIA
            case "maria_recepcion":
                switch ($condicion_code_actual) {
                    case Pedido::ENVIADO_OPE_INT:
                        $nuevo_estado = Pedido::RECIBIDO_JEFE_OPE_INT;
                        $respuesta = "El sobre se recibio correctamente.";
                        break;
                }
                break;
            // ENTREGA MARIA SIN SOBRE
            case "maria_entregado_sin_sobre":
                switch ($condicion_code_actual) {
                    case Pedido::ENTREGADO_SIN_SOBRE_OPE_INT: // 13
                        $nuevo_estado = Pedido::ENTREGADO_SIN_SOBRE_CLIENTE_INT; // 14
                        $respuesta = "El pedido sin sobre se confirmo correctamente.";
                        break;
                }
                break;
        }
        /***************
         * COMPROBAMOS SI YA ESTA ATENDIDO EL PEDIDO
         */
        if ($pedido->condicion_envio_code == $nuevo_estado) {
            return response()->json(['html' => "Este pedido ya ah sido procesado anteriormente", 'class' => "text-danger", 'codigo' => 0]);
        } else {
            switch ($accion) {

                case "recepcionar_sobres":

                    $pedido->update([
                        'fecha_recepcion_courier' => Carbon::now(),
                        'modificador' => 'USER' . Auth::user()->id,
                        'condicion_envio' => Pedido::RECEPCION_COURIER,
                        'condicion_envio_code' => Pedido::RECEPCION_COURIER_INT,
                        'condicion_envio_at' => now(),
                    ]);

                    if ($pedido->estado_sobre) {
                        $detalle = $pedido->detallePedido;
                        $grupoPedido = GrupoPedido::createGroupByPedido($pedido);

                        if (!$grupoPedido->pedidos()->where('pedidos.id', '=', $pedido->id)->exists()) {
                            $grupoPedido->pedidos()->syncWithoutDetaching([
                                $pedido->id => [
                                    'razon_social' => $detalle->nombre_empresa,
                                    'codigo' => $pedido->codigo,
                                ]
                            ]);
                        }
                    }

                    PedidoMovimientoEstado::create([
                        'pedido' => $request->hiddenEnvio,
                        'condicion_envio_code' => Pedido::RECEPCION_COURIER_INT,
                        'notificado' => 0
                    ]);

                    break;

                case "confirmacion_operaciones":

                    $pedido->update([
                        'envio' => '2',
                        'modificador' => 'USER' . Auth::user()->id,
                        'fecha_envio_op_courier' => Carbon::now(),
                        'condicion_envio' => Pedido::RECIBIDO_JEFE_OPE,
                        'condicion_envio_code' => Pedido::RECIBIDO_JEFE_OPE_INT,
                        'condicion_envio_at' => now(),

                    ]);

                    PedidoMovimientoEstado::create([
                        'pedido' => $request->hiddenEnvio,
                        'condicion_envio_code' => Pedido::RECIBIDO_JEFE_OPE_INT,
                        'notificado' => 0
                    ]);

                    break;

                case "envio_courier_operaciones":

                    $pedido->update([
                        'modificador' => 'USER' . Auth::user()->id,
                        'condicion_envio' => Pedido::ENVIO_COURIER_JEFE_OPE,
                        'condicion_envio_code' => Pedido::ENVIO_COURIER_JEFE_OPE_INT,
                        'condicion_envio_at' => now(),

                    ]);

                    PedidoMovimientoEstado::create([
                        'pedido' => $request->hiddenEnvio,
                        'condicion_envio_code' => Pedido::ENVIO_COURIER_JEFE_OPE_INT,
                        'notificado' => 0
                    ]);
                    break;

                case "sobres_en_reparto":
                    $envio = $pedido->direcciongrupo;
                    $envio->update([
                        'condicion_envio' => Pedido::ENVIO_MOTORIZADO_COURIER,
                        'condicion_envio_code' => Pedido::ENVIO_MOTORIZADO_COURIER_INT,
                        'condicion_envio_at' => now(),
                        'fecha_salida' => $request->fecha_salida,
                        'cambio_direccion_at' => null,
                    ]);

                    $envio->pedidos()->activo()->update([
                        'condicion_envio_code' => Pedido::ENVIO_MOTORIZADO_COURIER_INT,
                        'condicion_envio_at' => now(),
                        'condicion_envio' => Pedido::ENVIO_MOTORIZADO_COURIER,
                        'fecha_salida' => $request->fecha_salida,
                        'cambio_direccion_at' => null
                    ]);

                    PedidoMovimientoEstado::create([
                        'pedido' => $request->hiddenCodigo,
                        'condicion_envio_code' => Pedido::ENVIO_MOTORIZADO_COURIER_INT,
                        'notificado' => 0
                    ]);
                    break;

                case "sobres_devuelto":

                    /*********
                     * IDENTIFICAMOS AL GRUPO
                     */
                    $grupo = $pedido->direcciongrupo;

                    /**************
                     * CREAMOS EL GRUPO TEMPORAL
                     */
                    $pgroup = GrupoPedido::createGroupByPedido($pedido, false, true);

                    if ($grupo != null) {
                        if ($grupo->pedidos()->activo()->count() <= 1) {
                            $grupo->update([
                                'estado' => 0,
                            ]);
                            if ($pedido->estado = 0) {
                                $grupo->update([
                                    'motorizado_status' => Pedido::ESTADO_MOTORIZADO_RE_RECIBIDO,
                                ]);
                            } else {
                                $grupo->update([
                                    'motorizado_status' => 0,
                                ]);
                            }
                        } else {
                            $pedido->update([
                                'direccion_grupo' => null
                            ]);
                            DireccionGrupo::restructurarCodigos($grupo);
                        }
                    } else {
                        $pedido->update([
                            'direccion_grupo' => null
                        ]);
                    }
                    break;
            }
            return response()->json(['html' => $respuesta, 'class' => "text-success", 'codigo' => $request->hiddenCodigo]);
        }
    }

    public
    function confirmarEstado(Request $request)
    {
        $envio = DireccionGrupo::query()->findOrFail($request->hiddenCodigo);
        $envio->update([
            'condicion_envio' => Pedido::ENVIO_MOTORIZADO_COURIER,
            'condicion_envio_code' => Pedido::ENVIO_MOTORIZADO_COURIER_INT,
            'condicion_envio_at' => now(),
            'fecha_salida' => $request->fecha_salida,
            'cambio_direccion_at' => null,
        ]);

        /*$codigos_paquete = collect(explode(",", $envio->codigos))->map(function ($cod) {
            return trim($cod);
        })->all();*/
        $envio->pedidos()->activo()->update([
            'condicion_envio_code' => Pedido::ENVIO_MOTORIZADO_COURIER_INT,
            'condicion_envio_at' => now(),
            'condicion_envio' => Pedido::ENVIO_MOTORIZADO_COURIER,
            'fecha_salida' => $request->fecha_salida,
            'cambio_direccion_at' => null
        ]);


        /*Pedido::whereIn('codigo', $codigos_paquete)
            ->update([
                'condicion_envio_code' => Pedido::ENVIO_MOTORIZADO_COURIER_INT,
                'condicion_envio' => Pedido::ENVIO_MOTORIZADO_COURIER,
                'fecha_salida'=>$request->fecha_salida
            ]);*/

        PedidoMovimientoEstado::create([
            'pedido' => $request->hiddenCodigo,
            'condicion_envio_code' => Pedido::ENVIO_MOTORIZADO_COURIER_INT,
            'notificado' => 0
        ]);

        return response()->json(['html' => $envio->id]);
    }

    public function confirmarEntregaSinEnvio(Request $request)
    {
        $pedido = Pedido::query()->findOrFail($request->get('pedido_id', $request->get('hiddenCodigo')));

        $pedido->update([
            'condicion_envio' => Pedido::ENTREGADO_SIN_SOBRE_CLIENTE,
            'condicion_envio_code' => Pedido::ENTREGADO_SIN_SOBRE_CLIENTE_INT,
            'condicion_envio_at' => now(),
            //'fecha_salida' => $request->fecha_salida
        ]);

        $grupo = DireccionGrupo::createByPedido($pedido);
        if ($request->hasFile('adjunto1')) {
            $grupo->update([
                'foto1' => $request->file('adjunto1')->store('entregados_sin_envio', 'pstorage'),
            ]);
        }
        if ($request->hasFile('adjunto2')) {
            $grupo->update([
                'foto2' => $request->file('adjunto2')->store('entregados_sin_envio', 'pstorage'),
            ]);
        }
        if ($request->hasFile('adjunto3')) {
            $grupo->update([
                'foto3' => $request->file('adjunto3')->store('entregados_sin_envio', 'pstorage'),
            ]);
        }

        PedidoMovimientoEstado::create([
            'pedido' => $pedido->id,
            'condicion_envio_code' => Pedido::ENTREGADO_SIN_SOBRE_CLIENTE_INT,
            'notificado' => 0
        ]);

        return response()->json(['html' => $pedido->id]);
    }


    public
    function confirmarEstadoRecepcionMotorizado(Request $request)
    {
        $envio = DireccionGrupo::where("id", $request->hiddenCodigo)->first();
        $envio->update([
            'condicion_envio' => Pedido::MOTORIZADO,
            'condicion_envio_code' => Pedido::MOTORIZADO_INT,
            'condicion_envio_at' => now(),
            //'condicion_envio' => Pedido::MOTORIZADO,
            //'condicion_envio_code' => Pedido::MOTORIZADO_INT,
        ]);

        $codigos_paquete = collect(explode(",", $envio->codigos))->map(function ($cod) {
            return trim($cod);
        })->all();

        Pedido::whereIn('codigo', $codigos_paquete)
            ->update([
                'condicion_envio_code' => Pedido::MOTORIZADO_INT,
                'condicion_envio_at' => now(),
                'condicion_envio' => Pedido::MOTORIZADO
            ]);

        PedidoMovimientoEstado::create([
            'pedido' => $request->hiddenCodigo,
            'condicion_envio_code' => Pedido::MOTORIZADO_INT,
            'notificado' => 0
        ]);

        return response()->json(['html' => $envio->id]);
    }

    public
    function confirmarEstadoRevert(Request $request)
    {
        $envio = DireccionGrupo::where("id", $request->envio_id)->first();
        $envio->update([
            'foto1' => '',
            'foto2' => '',
            //'condicion_envio' => Pedido::REPARTO_COURIER,
            //'condicion_envio_code' => Pedido::REPARTO_COURIER_INT,
            'condicion_envio' => Pedido::RECEPCION_MOTORIZADO,
            'condicion_envio_code' => Pedido::RECEPCION_MOTORIZADO_INT,
            'condicion_envio_at' => now(),
        ]);

        $codigos_paquete = collect(explode(",", $envio->codigos))->map(function ($cod) {
            return trim($cod);
        })->all();

        Pedido::whereIn('codigo', $codigos_paquete)
            ->update([
                'condicion_envio_code' => Pedido::RECEPCION_MOTORIZADO_INT,
                'condicion_envio_at' => now(),
                'condicion_envio' => Pedido::RECEPCION_MOTORIZADO
            ]);


        PedidoMovimientoEstado::create([
            'pedido' => $request->envio_id,
            //'condicion_envio_code' => Pedido::REPARTO_COURIER_INT,
            'condicion_envio_code' => Pedido::RECEPCION_MOTORIZADO_INT,
            'notificado' => 0
        ]);

        return response()->json(['html' => $envio->id]);
    }

    public
    function confirmarEstadoConfirm(Request $request)
    {
        $action = $request->action;
        if ($action == 'update_status_observado') {
            $this->validate($request, [
                'grupo_id' => 'required',
                'sustento_text' => 'required',
            ]);

            $grupo = DireccionGrupo::query()->with('pedidos')->findOrFail($request->grupo_id);
            $grupo->update([
                'motorizado_status' => '1',
                'motorizado_sustento_text' => $request->sustento_text,
                'fecha' => now(),
            ]);
            //foreach ($grupo->pedidos as $pedido) {

            PedidoMotorizadoHistory::query()->create([
                'pedido_id' => '0',
                'direccion_grupo_id' => $grupo->id,
                //'pedido_grupo_id' => null,
                'status' => '1',
                'sustento_text' => $request->sustento_text,
                //'sustento_foto' => null,
            ]);

        } elseif ($action == 'update_status_no_contesto') {
            $this->validate($request, [
                'grupo_id' => 'required',
                //'sustento_text' => 'required',
                'sustento_foto' => 'required|file',
            ]);
            $grupo = DireccionGrupo::query()->with('pedidos')->findOrFail($request->grupo_id);
            $path = $request->file('sustento_foto')->store('sobres/no_contesto', 'pstorage');
            $grupo->update([
                'motorizado_status' => '2',
                'motorizado_sustento_text' => $request->sustento_text,
                'motorizado_sustento_foto' => $path,
                'fecha' => now(),
            ]);
            //foreach ($grupo->pedidos as $pedido) {

            PedidoMotorizadoHistory::query()->create([
                'pedido_id' => '0',
                'direccion_grupo_id' => $grupo->id,
                //'pedido_grupo_id' => null,
                'status' => '2',
                'sustento_text' => $request->sustento_text,
                'sustento_foto' => $path,
            ]);
        } else {
            $this->validate($request, [
                'adjunto1' => 'required|file',
                'adjunto2' => 'required|file',
                'adjunto3' => 'required|file',
                'envio_id' => 'required',
                //'fecha_recepcion' => 'required|date',
            ]);
            $file1 = $request->file('adjunto1')->store('entregas', 'pstorage');
            $file2 = $request->file('adjunto2')->store('entregas', 'pstorage');
            $file3 = $request->file('adjunto3')->store('entregas', 'pstorage');
            $envio = DireccionGrupo::where("id", $request->envio_id)->first();

            $envio->update([
                'foto1' => $file1,
                'foto2' => $file2,
                'foto3' => $file3,
                'atendido_por' => Auth::user()->name,
                'atendido_por_id' => Auth::user()->id,
                'fecha_recepcion' => now(),
                'condicion_envio' => Pedido::CONFIRM_MOTORIZADO,
                'condicion_envio_code' => Pedido::CONFIRM_MOTORIZADO_INT,
                'condicion_envio_at' => now(),
            ]);

            $paquete_sobres = $envio;

            $codigos_paquete = collect(explode(",", $paquete_sobres->codigos))
                ->map(fn($cod) => trim($cod))
                ->filter()->values();

            Pedido::whereIn('codigo', $codigos_paquete)
                ->update([
                    'condicion_envio' => Pedido::CONFIRM_MOTORIZADO,
                    'condicion_envio_code' => Pedido::CONFIRM_MOTORIZADO_INT,
                    'condicion_envio_at' => now(),
                ]);

            PedidoMovimientoEstado::create([
                'pedido' => $request->pedido_id,
                'condicion_envio_code' => Pedido::CONFIRM_MOTORIZADO_INT,
                'condicion_envio_at' => now(),
                'notificado' => 0
            ]);

            return response()->json(['html' => $envio->id]);
        }
    }

    public
    function confirmarEstadoConfirmRevert(Request $request)
    {
        $envio = DireccionGrupo::where("id", $request->envio_id)->first();
        $envio->update([
            'foto1' => '',
            'foto2' => '',
            'condicion_envio' => Pedido::MOTORIZADO,
            'condicion_envio_code' => Pedido::MOTORIZADO_INT,
            'condicion_envio_at' => now(),
        ]);

        PedidoMovimientoEstado::create([
            'pedido' => $request->hiddenCodigo,
            'condicion_envio_code' => Pedido::MOTORIZADO_INT,
            'notificado' => 0
        ]);

        return response()->json(['html' => $envio->id]);
    }

    public
    function confirmarEstadoConfirmConfirm(Request $request)
    {
        $envio = DireccionGrupo::where("id", $request->hiddenMotorizadoEntregarConfirm)->first();
        $envio->update([
            'condicion_envio' => Pedido::ENTREGADO_CLIENTE,
            'condicion_envio_code' => Pedido::ENTREGADO_CLIENTE_INT,
            'condicion_envio_at' => now(),
        ]);

        $pedidos = Pedido::where('direccion_grupo', $request->hiddenMotorizadoEntregarConfirm)->where('estado', '1');
        $pedidos->update([
            'condicion_envio' => Pedido::ENTREGADO_CLIENTE,
            'condicion_envio_code' => Pedido::ENTREGADO_CLIENTE_INT,
            'condicion_envio_at' => now(),
        ]);

        PedidoMovimientoEstado::create([
            'pedido' => $request->hiddenMotorizadoEntregarConfirm,
            'condicion_envio_code' => Pedido::ENTREGADO_CLIENTE_INT,
            'notificado' => 0
        ]);

        return response()->json(['html' => $envio->id]);
    }

    public
    function confirmarEstadoConfirmDismiss(Request $request)
    {
        //$hiddenAtender = $request->hiddenMotorizadoEntregarConfirm;
        /*$pedido = Pedido::where("id", $hiddenAtender)->first();
        $imagenesatencion_ = ImagenAtencion::where("pedido_id", $hiddenAtender)->where("confirm", '0');
        $imagenesatencion_->update([
            'estado' => '0'
        ]);*/
    }

    public
    function confirmarEstadoConfirmConfirmDismiss(Request $request)
    {
        //$hiddenAtender = $request->hiddenMotorizadoEntregarConfirm;
        /*$pedido = Pedido::where("id", $hiddenAtender)->first();
        $imagenesatencion_ = ImagenAtencion::where("pedido_id", $hiddenAtender)->where("confirm", '0');
        $imagenesatencion_->update([
            'estado' => '0'
        ]);*/
    }

    public
    function confirmarEstadoConfirmValidada(Request $request)
    {
        $envio = DireccionGrupo::where("id", $request->hiddenCodigo)->first();
        $envio->update([
            'condicion_envio' => Pedido::CONFIRM_VALIDADA_CLIENTE,
            'condicion_envio_code' => Pedido::CONFIRM_VALIDADA_CLIENTE_INT,
            'condicion_envio_at' => now(),
        ]);

        PedidoMovimientoEstado::create([
            'pedido' => $request->hiddenCodigo,
            'condicion_envio_code' => Pedido::CONFIRM_VALIDADA_CLIENTE_INT,
            'notificado' => 0
        ]);

        return response()->json(['html' => $envio->id]);
    }


    public
    function VerificarZona(Request $request)
    {
        $search = str_replace('+', ' ', $request->distrito);
        $zona_distrito = Distrito::where('distrito', $search)
            ->whereIn('provincia', ['LIMA', 'CALLAO'])
            ->first();

        if ($zona_distrito->zona == "OLVA") {
            return response()->json(['html' => 0]);
        } else {
            return response()->json(['html' => 1]);
        }

    }

    public
    function EscaneoQR(Request $request)
    {
        $pedido = Pedido::where("codigo", $request->id)->firstOrFail();

        /*$pedido->update([
            'envio' => '2',
            'modificador' => 'USER' . Auth::user()->id,
            'condicion_envio' => Pedido::ENVIO_COURIER_JEFE_OPE,
            'condicion_envio_code' => Pedido::ENVIO_COURIER_JEFE_OPE_INT,

        ]);

        PedidoMovimientoEstado::create([
            'pedido' => $request->hiddenEnvio,
            'condicion_envio_code' => Pedido::RECEPCION_COURIER_INT,
            'notificado' => 0
        ]);*/

        return response()->json(['html' => $pedido->codigo, 'distrito' => $pedido->distrito, 'direccion' => $pedido->direccion]);
    }

    public
    function RecibirPedidoMotorizado(Request $request)
    {
        /**********
         * BUSCAMOS EL PEDIDO
         */
        $pedido = Pedido::with('direcciongrupo')->where("codigo", $request->id)
            ->activo()
            ->firstOrFail();

        if ($pedido->condicion_envio_code == Pedido::RECEPCION_MOTORIZADO_INT) {
            return response()->json(['html' => 0]);
        } else {
            /*************
             * BUSCAMOS EL PAQUETE
             */
            $paquete_sobres = $pedido->direccionGrupo;
            $codigos_paquete = collect(explode(",", $paquete_sobres->codigos))
                ->map(fn($cod) => trim($cod))
                ->filter()->values();


            $codigos_confirmados = collect(explode(",", $paquete_sobres->codigos_confirmados ?? ''))
                ->push($pedido->codigo)
                ->map(fn($cod) => trim($cod))
                ->filter()
                ->values();

            DB::beginTransaction();
            /************
             * ACTUALIZAMOS EL PEDIDO
             */
            $pedido->update([
                'modificador' => 'USER' . Auth::user()->id,
                'condicion_envio' => Pedido::RECEPCION_MOTORIZADO,
                'condicion_envio_code' => Pedido::RECEPCION_MOTORIZADO_INT,
                'condicion_envio_at' => now(),
            ]);


            /*************
             * SACAMOS LA CANTIDAD DE SOBRES YA RECIBIDOS DE ESTE PAQUETE
             */
            $sobres_ya_recibidos = Pedido::where('condicion_envio_code', Pedido::RECEPCION_MOTORIZADO_INT)
                ->whereIn('codigo', $codigos_paquete)
                ->count();
            /*************
             * SI la cantidad de paquetes recibidos es igual a la cantidad total del paquete, actualizamos el paquete
             */
            $sobres_restantes = $codigos_paquete->count() - $sobres_ya_recibidos;

            if ($sobres_restantes == 0) {
                $paquete_sobres->update([
                    'modificador' => 'USER' . Auth::user()->id,
                    'condicion_envio' => Pedido::RECEPCION_MOTORIZADO,
                    'condicion_envio_code' => Pedido::RECEPCION_MOTORIZADO_INT,
                    'condicion_envio_at' => now(),
                ]);
            }
            $paquete_sobres->update([
                'codigos_confirmados' => $codigos_confirmados->unique()->join(',')
            ]);
            DB::commit();
            return response()->json(['html' => $pedido->id, 'grupo' => $paquete_sobres, 'pedido' => $pedido, 'distrito' => $pedido->distrito, 'direccion' => $pedido->direccion, 'sobres_recibidos' => $sobres_ya_recibidos, 'sobres_restantes' => $sobres_restantes]);
        }
    }

    public
    function IniciarRutaMasiva(Request $request)
    {
        $rol = Auth::user()->rol;
        $zona_ = null;
        $motorizadoid = null;

        if ($rol == 'MOTORIZADO') {
            $usuario = User::where('id', Auth::user()->id)->first();
            $zona = $usuario->zona;
            $motorizadoid = $usuario->id;
            $direcciones = DireccionGrupo::where('motorizado_id', $motorizadoid)->where('distribucion', $zona)->where('condicion_envio_code', Pedido::RECEPCION_MOTORIZADO_INT);
            $direcciones->update([
                'condicion_envio_code' => Pedido::MOTORIZADO_INT,
                'condicion_envio_at' => now(),
                'condicion_envio' => Pedido::MOTORIZADO,
            ]);
        } else if ($rol == User::ROL_ADMIN) {
            $direcciones = DireccionGrupo::where('condicion_envio_code', Pedido::RECEPCION_MOTORIZADO_INT);
            $direcciones->update([
                'condicion_envio_code' => Pedido::MOTORIZADO_INT,
                'condicion_envio_at' => now(),
                'condicion_envio' => Pedido::MOTORIZADO,
            ]);
        } else {
            return response()->json(['html' => '0']);
        }

        return response()->json(['html' => '1']);

    }

    public
    function valida_direccionenvio(Request $request)
    {
        $element = $request->element;
        $value_ = $request->value;
        $from_ = $request->from;

        switch ($element) {
            case 'tracking':
                $count_tracking = Pedido::where('env_tracking', '=', $value_)->count();
                if ($count_tracking > 0) {
                    $arr = array('response' => 1, 'element' => 'tracking');
                    return response()->json($arr);
                } else {
                    return response()->json(['response' => '0']);
                }
                break;
            case 'numregistro':
                $count_nregistro = Pedido::where('env_numregistro', '=', $value_)->count();
                if ($count_nregistro > 0) {
                    $arr = array('response' => 1, 'element' => 'num.registro');
                    return response()->json($arr);
                } else {
                    return response()->json(['response' => '0']);
                }
                break;
        }

    }

}
