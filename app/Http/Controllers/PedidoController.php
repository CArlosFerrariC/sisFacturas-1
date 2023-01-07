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
use App\Models\ImagenAtencion;
use App\Models\ImagenPedido;
use App\Models\Pago;
use App\Models\PagoPedido;
use App\Models\User;
use App\Models\Pedido;
use App\Models\Porcentaje;
use App\Models\Provincia;
use App\Models\Ruc;
use App\Notifications\PedidoNotification;
use Carbon\Carbon;
use Exception;
use Facade\FlareClient\Http\Client;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PDF;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Validator;
use DataTables;

class PedidoController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $dateMin = Carbon::now()->subDays(4)->format('d/m/Y');
        $dateMax = Carbon::now()->format('d/m/Y');

        $mirol = Auth::user()->rol;
        $miidentificador = Auth::user()->name;

        $superasesor = User::where('rol', 'Super asesor')->count();

        return view('pedidos.index', compact('dateMin', 'dateMax', 'superasesor', 'mirol', 'miidentificador'));
    }

    public function indexperdonarcurrier()
    {
        $dateMin = Carbon::now()->subDays(4)->format('d/m/Y');
        $dateMax = Carbon::now()->format('d/m/Y');

        $mirol = Auth::user()->rol;
        $miidentificador = Auth::user()->name;

        $superasesor = User::where('rol', 'Super asesor')->count();

        return view('pedidos.perdonarCurrier', compact('dateMin', 'dateMax', 'superasesor', 'mirol', 'miidentificador'));
    }

    public function indextablahistorial(Request $request)
    {
        //return $request->buscarpedidocliente;
        if (!$request->buscarpedidocliente && !$request->buscarpedidoruc) {
            $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
                ->join('users as u', 'pedidos.user_id', 'u.id')
                ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
                ->join('imagen_pedidos as ip', 'pedidos.id', 'ip.pedido_id')
                ->select(
                    'pedidos.id',
                    'dp.descripcion',
                    'dp.nota',
                    'ip.adjunto'
                )
                ->where('dp.estado', '3')
                ->where('pedidos.estado', '1')
                //->where('pedidos.cliente_id',$request->buscarpedidocliente)
                //->where('dp.ruc',$request->buscarpedidoruc)
                ->orderBy('pedidos.created_at', 'DESC');
            //->get();
            return Datatables::of(DB::table($pedidos))
                ->addIndexColumn()
                ->make(true);
        } else {
            $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
                ->join('users as u', 'pedidos.user_id', 'u.id')
                ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
                ->join('imagen_pedidos as ip', 'pedidos.id', 'ip.pedido_id')
                ->select(
                    'pedidos.id',
                    'dp.descripcion',
                    'dp.nota',
                    'ip.adjunto'
                )
                ->where('dp.estado', '1')
                ->where('pedidos.estado', '1')
                ->where('pedidos.cliente_id', $request->buscarpedidocliente)
                ->where('dp.ruc', $request->buscarpedidoruc)
                ->orderBy('pedidos.created_at', 'DESC');
            //->get();

            return Datatables::of(DB::table($pedidos))
                ->addIndexColumn()
                ->make(true);
        }
    }


    public function indextabla(Request $request)
    {
        $mirol = Auth::user()->rol;

        $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
            ->join('users as u', 'pedidos.user_id', 'u.id')
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            //->leftjoin('pago_pedidos as pp', 'pedidos.id','pp.pedido_id')
            ->select(
                [
                    'pedidos.*',
                    'pedidos.codigo as codigos',
                    'pedidos.condicion as condiciones',
                    'pedidos.pagado as condicion_pa',
                    'c.nombre as nombres',
                    'c.icelular as icelulares',
                    'c.celular as celulares',
                    'u.identificador as users',
                    'dp.nombre_empresa as empresas',
                    'dp.total as total',
                    'dp.cantidad as cantidad',
                    'dp.ruc as ruc',
                    DB::raw("
                    concat(
                        (case when pedidos.pago=1 and pedidos.pagado=1 then 'ADELANTO' when pedidos.pago=1 and pedidos.pagado=2 then 'PAGO' else '' end),
                        ' ',
                        (
                            select pago.condicion from pago_pedidos pagopedido inner join pedidos pedido on pedido.id=pagopedido.pedido_id and pedido.id=pedidos.id inner join pagos pago on pagopedido.pago_id=pago.id where pagopedido.estado=1 and pago.estado=1 order by pagopedido.created_at desc limit 1
                        )
                    )  as condiciones_aprobado"),
                    DB::raw('DATE_FORMAT(pedidos.created_at, "%d/%m/%Y") as fecha2'),
                    DB::raw('DATE_FORMAT(pedidos.created_at, "%Y-%m-%d %H:%i:%s") as fecha'),
                    DB::raw('DATE_FORMAT(pedidos.updated_at, "%d/%m/%Y") as fecha2_up'),
                    DB::raw('DATE_FORMAT(pedidos.updated_at, "%Y-%m-%d %H:%i:%s") as fecha_up'),
                    'dp.saldo as diferencia',
                ]
            );
        //->where('pendiente_anulacion', '<>', 1)
        //->whereIn('pedidos.condicion_code', [Pedido::POR_ATENDER_INT, Pedido::EN_PROCESO_ATENCION_INT, Pedido::ATENDIDO_INT, Pedido::ANULADO_INT]);

        if (Auth::user()->rol == "Llamadas") {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.llamada', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos = $pedidos->WhereIn('u.identificador', $usersasesores);
        } else if (Auth::user()->rol == "Jefe de llamadas") {
            /*$usersasesores = User::where('users.rol', 'Asesor')
                -> where('users.estado', '1')
                -> where('users.llamada', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos=$pedidos->WhereIn('u.identificador',$usersasesores); */
            $pedidos = $pedidos->where('u.identificador', '<>', 'B');
        } else if (Auth::user()->rol == "Asesor") {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.identificador', Auth::user()->identificador)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos = $pedidos->WhereIn('u.identificador', $usersasesores);

        } else if (Auth::user()->rol == "Super asesor") {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.identificador', Auth::user()->identificador)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos = $pedidos->WhereIn('u.identificador', $usersasesores);

        } else if (Auth::user()->rol == "ASESOR ADMINISTRATIVO") {
            $usersasesores = User::where('users.rol', 'ASESOR ADMINISTRATIVO')
                ->where('users.estado', '1')
                ->where('users.identificador', Auth::user()->identificador)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos = $pedidos->WhereIn('u.identificador', $usersasesores);

        } else if (Auth::user()->rol == "Encargado") {

            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.supervisor', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos = $pedidos->WhereIn('u.identificador', $usersasesores);
        }/* else {
            $pedidos = $pedidos;
        }*/
        //$pedidos=$pedidos->get();

        return Datatables::of(DB::table($pedidos))
            ->addIndexColumn()
            ->addColumn('condicion_envio_color', function ($pedido) {
                return Pedido::getColorByCondicionEnvio($pedido->condicion_envio);
            })
            ->editColumn('condicion_envio', function ($pedido) {
                $badge_estado='';
                if($pedido->pendiente_anulacion=='1')
                {
                    $badge_estado.='<span class="badge badge-success">' . Pedido::PENDIENTE_ANULACION.'</span>';
                    return $badge_estado;
                }
                if($pedido->condicion_code=='4' || $pedido->estado=='0')
                {
                    return '<span class="badge badge-danger">ANULADO</span>';
                }
                if($pedido->estado_sobre=='1')
                {
                    $badge_estado .= '<span class="badge badge-dark p-8" style="color: #fff; background-color: #347cc4; font-weight: 600; margin-bottom: -2px;border-radius: 4px 4px 0px 0px; font-size:8px;  padding: 4px 4px !important; font-weight: 500;">Direccion agregada</span>';

                }
                if($pedido->estado_ruta=='1')
                {
                    $badge_estado.='<span class="badge badge-success" style="background-color: #00bc8c !important;
                    padding: 4px 8px !important;
                    font-size: 8px;
                    margin-bottom: -4px;
                    color: black !important;">Con ruta</span>';
                }
                $color = Pedido::getColorByCondicionEnvio($pedido->condicion_envio);
                $badge_estado.= '<span class="badge badge-success w-100" style="background-color: ' . $color . '!important;">' . $pedido->condicion_envio . '</span>';
                return $badge_estado;
            })
            ->addColumn('action', function ($pedido) {
                $btn ='';
                if($pedido->estado_sobre==1) {
                    if(\auth()->user()->can('envios.direccionenvio.editar')) {
                        $btn = '<button class="btn btn-sm btn-info dropdown-item" data-jqconfirm="' . $pedido->id . '"><i class="fa fa-map-marker-alt text-info mr-8"></i>Editar direccion de envio</button>';
                    }
                }else{
                    $btn = '';
                }
                return $btn;
            })
            ->rawColumns(['action','condicion_envio'])
            ->make(true);
    }


    public function indexperdonarcurriertabla(Request $request)
    {
        $mirol = Auth::user()->rol;
        $pedidos = null;

        $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
            ->join('users as u', 'pedidos.user_id', 'u.id')
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->select(
                'pedidos.id',
                'c.nombre as nombres',
                'c.icelular as icelulares',
                'c.celular as celulares',
                'u.identificador as users',
                'pedidos.codigo as codigos',
                'dp.nombre_empresa as empresas',
                'dp.total as total',
                'pedidos.condicion_envio',
                'pedidos.condicion as condiciones',
                'pedidos.pagado as condicion_pa',
                DB::raw('(select pago.condicion from pago_pedidos pagopedido inner join pedidos pedido on pedido.id=pagopedido.pedido_id and pedido.id=pedidos.id inner join pagos pago on pagopedido.pago_id=pago.id where pagopedido.estado=1 and pago.estado=1 order by pagopedido.created_at desc limit 1) as condiciones_aprobado'),
                'pedidos.motivo',
                'pedidos.responsable',
                DB::raw('DATE_FORMAT(pedidos.created_at, "%d/%m/%Y") as fecha2'),
                DB::raw('DATE_FORMAT(pedidos.created_at, "%Y-%m-%d %H:%i:%s") as fecha'),
                'dp.saldo as diferencia',
                'pedidos.estado',
                'pedidos.pago',
                'pedidos.pagado',
                'pedidos.envio'
            )
            ->whereIn('pedidos.condicion_code', [Pedido::POR_ATENDER_INT, Pedido::EN_PROCESO_ATENCION_INT, Pedido::ATENDIDO_INT, Pedido::ANULADO_INT])
            ->whereIn('pedidos.pagado', ['1'])
            ->whereIn('pedidos.pago', ['1'])
            //->whereNotIn("pedidos.envio", ['3'])
            ->where('dp.saldo', '>=', 11)->where('dp.saldo', '<=', 13);

        $pedidos2 = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
            ->join('users as u', 'pedidos.user_id', 'u.id')
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->select(
                'pedidos.id',
                'c.nombre as nombres',
                'c.icelular as icelulares',
                'c.celular as celulares',
                'u.identificador as users',
                'pedidos.codigo as codigos',
                'dp.nombre_empresa as empresas',
                'dp.total as total',
                'pedidos.condicion_envio',
                'pedidos.condicion as condiciones',
                'pedidos.pagado as condicion_pa',
                DB::raw('(select pago.condicion from pago_pedidos pagopedido inner join pedidos pedido on pedido.id=pagopedido.pedido_id and pedido.id=pedidos.id inner join pagos pago on pagopedido.pago_id=pago.id where pagopedido.estado=1 and pago.estado=1 order by pagopedido.created_at desc limit 1) as condiciones_aprobado'),
                'pedidos.motivo',
                'pedidos.responsable',
                DB::raw('DATE_FORMAT(pedidos.created_at, "%d/%m/%Y") as fecha2'),
                DB::raw('DATE_FORMAT(pedidos.created_at, "%Y-%m-%d %H:%i:%s") as fecha'),
                'dp.saldo as diferencia',
                'pedidos.estado',
                'pedidos.pago',
                'pedidos.pagado',
                'pedidos.envio'
            )
            ->whereIn('pedidos.condicion_code', [Pedido::POR_ATENDER_INT, Pedido::EN_PROCESO_ATENCION_INT, Pedido::ATENDIDO_INT, Pedido::ANULADO_INT])
            ->whereIn('pedidos.pagado', ['1'])
            ->whereIn('pedidos.pago', ['1'])
            //->whereNotIn("pedidos.envio", ['3'])
            ->Where('dp.saldo', '>=', 17)->where('dp.saldo', '<=', 19);

        $pedidos = $pedidos->union($pedidos2);
        //->WhereBetween("dp.saldo", ['11', '13'])
        //->orWhereBetween("dp.saldo", ['17', '19']);


        if (Auth::user()->rol == "Llamadas") {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.llamada', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos = $pedidos->WhereIn('u.identificador', $usersasesores);
        } else if (Auth::user()->rol == "Jefe de llamadas") {
            /*
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.llamada', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');
            $pedidos = $pedidos->WhereIn('u.identificador', $usersasesores);
            */
        } else if (Auth::user()->rol == "Asesor") {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.identificador', Auth::user()->identificador)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos = $pedidos->WhereIn('u.identificador', $usersasesores);

        } else if (Auth::user()->rol == "Super asesor") {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.identificador', Auth::user()->identificador)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos = $pedidos->WhereIn('u.identificador', $usersasesores);

        } else if (Auth::user()->rol == "Encargado") {

            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.supervisor', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos = $pedidos->WhereIn('u.identificador', $usersasesores);
        } else {
            $pedidos = $pedidos;
        }
        //$pedidos=$pedidos->get();

        return Datatables::of(DB::table($pedidos))
            ->addIndexColumn()
            ->addColumn('action', function ($pedido) {
                $btn = '';

                return $btn;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function deudoresoncreate(Request $request)
    {
        $deudores = Cliente::where('estado', '1')
            //->where('user_id', Auth::user()->id)
            ->where('tipo', '1')
            ->where('deuda', '1');
        //->get();

        return Datatables::of(DB::table($deudores))
            ->addIndexColumn()
            ->make(true);

        //return response()->json($deudores);
    }

    public function clientesenpedidos(Request $request)
    {
        $clientes1 = Cliente::
        join('users as u', 'clientes.user_id', 'u.id')
            ->leftjoin('pedidos as p', 'clientes.id', 'p.cliente_id')
            ->where('clientes.estado', '1')
            ->where('clientes.tipo', '1')
            ->where('clientes.user_id', Auth::user()->id)
            ->groupBy(
                'clientes.id',
                'clientes.nombre',
                'clientes.celular',
                'clientes.estado',
                'u.name',
                'u.identificador',
                'clientes.provincia',
                'clientes.distrito',
                'clientes.direccion',
                'clientes.pidio',
                'clientes.deuda'
            )
            ->get(['clientes.id',
                'clientes.nombre',
                'clientes.celular',
                'clientes.estado',
                'u.name as user',
                'u.identificador',
                'clientes.provincia',
                'clientes.distrito',
                'clientes.direccion',
                'clientes.pidio',
                'clientes.deuda',
                DB::raw('count(p.created_at) as cantidad'),
                DB::raw('MAX(p.created_at) as fecha'),
                DB::raw('MAX(DATE_FORMAT(p.created_at, "%d")) as dia'),
                DB::raw('MAX(DATE_FORMAT(p.created_at, "%m")) as mes'),
                DB::raw('MAX(DATE_FORMAT(p.created_at, "%Y")) as anio')
            ]);

        return response()->json($clientes1);
    }

    public function clientesenruconcreate(Request $request)
    {
        $clientes_ruc = Cliente::
        where('clientes.estado', '1')
            ->where('clientes.tipo', '1')
            ->where('clientes.user_id', Auth::user()->id)
            ->groupBy(
                'clientes.id',
                'clientes.nombre',
                'clientes.celular',
                'clientes.estado'
            )
            ->get(['clientes.id',
                'clientes.nombre',
                'clientes.celular',
                'clientes.estado'
            ]);

        return response()->json($clientes_ruc);
    }

    public function asesortiempo(Request $request)//clientes
    {
        $mirol = Auth::user()->rol;
        $html = '<option value="">' . trans('---- SELECCIONE ASESOR ----') . '</option>';

        if ($mirol == 'Llamadas') {
            $asesores = Users::where('users.rol', "Asesor")
                ->where('users.estado', '1')
                ->where('users.llamada', Auth::user()->id)
                ->get();
        } else if ($mirol == 'Jefe de llamadas') {
            $asesores = User:: where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.llamada', Auth::user()->id)
                ->get();
        } else if ($mirol == 'Asesor') {
            $asesores = User:: where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.identificador', Auth::user()->identificador)
                ->get();
        } else {
            $asesores = User:: where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->get();
        }

        foreach ($asesores as $asesor) {
            $html .= '<option style="color:#000" value="' . $asesor->id . '">' . $asesor->identificador . '</option>';
        }

        return response()->json(['html' => $html]);
    }

    public function create()
    {
        $dateM = Carbon::now()->format('m');
        $dateY = Carbon::now()->format('Y');

        $mirol = Auth::user()->rol;
        $users = User::where('estado', '1')->where("rol", "Asesor");

        if ($mirol == 'Llamadas') {
            $users = $users->where('llamada', Auth::user()->id);
        } else if ($mirol == 'Jefe de llamadas') {
            $users = $users->where('llamada', Auth::user()->id);
        } else if ($mirol == 'Asesor') {
            $users = $users->where('id', Auth::user()->id);
        }
        //$users=$users->get(['identificador','id'])  ;//->pluck('identificador', 'id');
        $users = $users->pluck('identificador', 'id');

        $meses = [
            "ENERO" => 'ENERO',
            "FEBRERO" => 'FEBRERO',
            "MARZO" => 'MARZO',
            "ABRIL" => 'ABRIL',
            "MAYO" => 'MAYO',
            "JUNIO" => 'JUNIO',
            "JULIO" => 'JULIO',
            "AGOSTO" => 'AGOSTO',
            "SEPTIEMBRE" => 'SEPTIEMBRE',
            "OCTUBRE" => 'OCTUBRE',
            "NOVIEMBRE" => 'NOVIEMBRE',
            "DICIEMBRE" => 'DICIEMBRE',
        ];

        $anios = [
            "2020" => '2020',
            "2021" => '2021',
            "2022" => '2022',
            "2023" => '2023',
            "2024" => '2024',
            "2025" => '2025',
            "2026" => '2026',
            "2027" => '2027',
            "2028" => '2028',
            "2029" => '2029',
            "2030" => '2030',
            "2031" => '2031',
        ];

        /*$rucs = Ruc::where('user_id', Auth::user()->id)
                    ->where('estado', '1')
                    ->pluck('num_ruc', 'num_ruc');*/

        $fecha = Carbon::now()->format('dm');
        $dia = Carbon::now()->toDateString();

        $numped = Pedido::where(DB::raw('Date(created_at)'), $dia)
            ->where('user_id', Auth::user()->id)
            ->groupBy(DB::raw('Date(created_at)'))
            ->count();
        $numped = $numped + 1;

        $mirol = Auth::user()->rol;

        return view('pedidos.create', compact('users', 'dateM', 'dateY', 'meses', 'anios', 'fecha', 'numped', 'mirol'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */

    public function validarrelacionruc(Request $request)
    {
        $ruc_registrar = $request->agregarruc;
        $cliente_registrar = $request->cliente_id_ruc;
        $nombreruc_registrar = $request->pempresaruc;
        $asesor_registrar = $request->user_id;

        $ruc_repetido = Ruc::where('rucs.num_ruc', $ruc_registrar)->count();

        if ($ruc_repetido > 0) {
            //ya existe, actualizar y buscar relacion
            //busco relacion si es correcta
            $ruc = Ruc::where('num_ruc', $request->agregarruc)->first();//ruc ya exisste entoces busco al asesor//buscar si corresponde al cliente
            if ($cliente_registrar == $ruc->cliente_id_ruc) {
                //verificar el asesor
                $asesordelruc = User::where("users.id", $ruc->user_id)->first();
                if ($asesor_registrar == $asesordelruc->id) {
                    $html = "1";
                    return response()->json(['html' => $html]);
                } else {
                    $html = "0|A|" . $asesordelruc->name;
                    return response()->json(['html' => $html]);
                }
                //$html="1";
            } else {
                //$asesordelruc= User::where("users.id",$ruc->user_id)->first();
                $cliente = Cliente::where("clientes.id", $ruc->cliente_id)->first();
                //$html="0|C|RUC YA EXISTE PERO NO CORRESPONDE AL CLIENTE";
                $html = "0|C|" . $cliente->nombre;
                return response()->json(['html' => $html]);
            }
        } else {
            //no existe ,registrare
            $html = "1";
            return response()->json(['html' => $html]);
        }

    }

    public function pedidoobteneradjuntoRequest(Request $request)
    {
        $buscar_pedido = $request->pedido;

        $array_html = [];

        $imagenes = ImagenPedido::where('pedido_id', $buscar_pedido)
            ->where("estado", "1")
            ->whereNotIn("adjunto", ['logo_facturas.png'])
            ->orderBy('created_at', 'DESC')->get();
        foreach ($imagenes as $imagen) {
            $array_html[] = $imagen->adjunto;
        }
        /*
        $imagenesatencion = ImagenAtencion::where('pedido_id', $buscar_pedido)
            ->where("estado", "1")
            ->whereNotIn("adjunto", ['logo_facturas.png'])
            ->orderBy('created_at', 'DESC')->get();
        foreach ($imagenesatencion as $imagenatencion) {
            $array_html[] = $imagenatencion->adjunto;
        } */

        $html = implode("|", $array_html);
        return response()->json(['html' => $html, 'cantidad' => count($array_html)]);
    }

    public function pedidoobteneradjuntoOPRequest(Request $request)
    {
        $buscar_pedido = $request->pedido;

        $array_html = [];
        /*
        $imagenes = ImagenPedido::where('pedido_id', $buscar_pedido)
            ->where("estado", "1")
            ->whereNotIn("adjunto", ['logo_facturas.png'])
            ->orderBy('created_at', 'DESC')->get();
        foreach ($imagenes as $imagen) {
            $array_html[] = $imagen->adjunto;
        } */
        $imagenesatencion = ImagenAtencion::where('pedido_id', $buscar_pedido)
            ->where("estado", "1")
            ->whereNotIn("adjunto", ['logo_facturas.png'])
            ->orderBy('created_at', 'DESC')->get();
        foreach ($imagenesatencion as $imagenatencion) {
            $array_html[] = $imagenatencion->adjunto;
        }
        $html = implode("|", $array_html);
        return response()->json(['html' => $html, 'cantidad' => count($array_html)]);
    }

    public function ruc(Request $request)//rucs
    {
        if (!$request->cliente_id || $request->cliente_id == '') {
            $html = '<option value="">' . trans('---- SELECCIONE ----') . '</option>';
        } else {
            $html = '<option value="">' . trans('---- SELECCIONE ----') . '</option>';
            $rucs = Ruc::join('clientes as c', 'rucs.cliente_id', 'c.id')
                ->select('rucs.num_ruc as num_ruc', 'rucs.empresa')
                ->where('rucs.cliente_id', $request->cliente_id)
                ->get();
            foreach ($rucs as $ruc) {
                $html .= '<option value="' . $ruc->num_ruc . '">' . $ruc->num_ruc . "  " . $ruc->empresa . '</option>';
            }
        }
        return response()->json(['html' => $html]);
    }

    public function rucnombreempresa(Request $request)//rucs
    {
        if (!$request->ruc || $request->ruc == '') {
            $html = '<option value="">' . trans('---- SELECCIONE ----') . '</option>';
        } else {
            $html = '<option value="">' . trans('---- SELECCIONE ----') . '</option>';
            $rucs = Ruc::where('rucs.num_ruc', $request->ruc)
                ->first();
            $html = htmlentities($rucs->empresa);

        }
        return response()->json(['html' => $html]);
    }

    public function infopdf(Request $request)//rucs
    {
        if (!$request->infocopiar) {
            $html = '<option value="">' . trans('---- SELECCIONE ----') . '</option>';
            $pedido = "";
        } else {
            $html = '<option value="">' . trans('---- SELECCIONE ----') . '</option>';
            $pedido = Pedido::join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
                ->select(
                    'pedidos.id',
                    'dp.cantidad',
                    'dp.porcentaje',
                    'dp.ft',
                    'dp.courier',
                    'dp.total',
                )
                ->where('pedidos.id', $request->infocopiar)
                ->first();
            //$html=$pedido->id;

        }
        return response()->json($pedido);
    }

    /*
    public function ruc  vantigua(Request $request)//rucs
    {
        if (!$request->cliente_id) {
            $html = '<option value="">' . trans('---- SELECCIONE ----') . '</option>';
        } else {
            $html = '<option value="">' . trans('---- SELECCIONE ----') . '</option>';
            $rucs = Ruc::where('rucs.cliente_id', $request->cliente_id)
                ->get();
            foreach ($rucs as $ruc) {
                $html .= '<option value="' . $ruc->num_ruc . '">' . $ruc->num_ruc . '</option>';
            }
        }
        return response()->json(['html' => $html]);
    } */

    public function cliente()//clientes
    {
        $html = '<option value="">' . trans('---- SELECCIONE CLIENTE ----') . '</option>';
        $clientes = Cliente::where('clientes.user_id', Auth::user()->id)
            ->where('clientes.tipo', '1')
            ->get();
        foreach ($clientes as $cliente) {
            $html .= '<option value="' . $cliente->id . '">' . $cliente->celular . '-' . $cliente->nombre . '</option>';
        }
        return response()->json(['html' => $html]);
    }


    public function clientedeudaparaactivar(Request $request)//clientes
    {
        if (!$request->user_id || $request->user_id == '') {
            $html = '<option value="">' . trans('---- SELECCIONE CLIENTE ----') . '</option>';
        } else {
            $html = '<option value="">' . trans('---- SELECCIONE CLIENTE ----') . '</option>';
            $clientes = Cliente::join('users as u', 'clientes.user_id', 'u.id')->where('clientes.tipo', '1')//->where('clientes.celular','925549426')
            ->where('u.identificador', $request->user_id)
                ->where('clientes.estado', '1')
                ->get([
                    'clientes.id',
                    'clientes.celular',
                    'clientes.icelular',
                    'clientes.nombre',
                    'clientes.crea_temporal',
                    'clientes.activado_tiempo',
                    'clientes.activado_pedido',
                    'clientes.temporal_update',
                    DB::raw(" (select count(ped.id) from pedidos ped where ped.cliente_id=clientes.id and ped.pago in (0,1) and ped.pagado in (0,1) and ped.created_at >='".now()->startOfMonth()->format("Y-m-d H:i:s")."' and ped.estado=1) as pedidos_mes_deuda "),
                    DB::raw(" (select count(ped2.id) from pedidos ped2 where ped2.cliente_id=clientes.id and ped2.pago in (0,1) and ped2.pagado in (0,1) and ped2.created_at <='".now()->startOfMonth()->subMonth()->endOfMonth()->endOfDay()->format("Y-m-d H:i:s")."'  and ped2.estado=1) as pedidos_mes_deuda_antes ")
                ]);
            foreach ($clientes as $cliente) {
                if ($cliente->pedidos_mes_deuda > 0 || $cliente->pedidos_mes_deuda_antes > 0) {
                    $html .= '<option style="color:black" value="' . $cliente->celular . '">' . $cliente->celular . (($cliente->icelular != null) ? '-' . $cliente->icelular : '') . '  -  ' . $cliente->nombre . '</option>';
                }
            }
        }
        return response()->json(['html' => $html]);
    }

    public function clientedeasesordeuda(Request $request)//clientes
    {
        if (!$request->user_id || $request->user_id == '') {
            $html = '<option value="">' . trans('---- SELECCIONE CLIENTE ----') . '</option>';
        } else {
            $html = '<option value="">' . trans('---- SELECCIONE CLIENTE ----') . '</option>';
            $clientes = Cliente::where('clientes.user_id', $request->user_id)
                ->where('clientes.tipo', '1')
                ->where('clientes.deuda', '1')
                ->where('clientes.estado', '1')
                ->get();
            foreach ($clientes as $cliente) {
                $html .= '<option value="' . $cliente->id . '">' . $cliente->celular . '  -  ' . $cliente->nombre . '</option>';
            }

        }

        return response()->json(['html' => $html]);
    }

    public function tipobanca(Request $request)//pedidoscliente
    {
        if (!$request->cliente_id || $request->cliente_id == '') {
            $html = '<option value="">' . trans('---- SELECCIONE ----') . '</option>';
        } else {
            $html = '<option value="">' . trans('---- SELECCIONE ----') . '</option>';
            $porcentajes = Porcentaje::where('porcentajes.cliente_id', $request->cliente_id)->get();
            foreach ($porcentajes as $porcentaje) {
                $html .= '<option value="' . $porcentaje->nombre . '_' . $porcentaje->porcentaje . '">' . $porcentaje->nombre . '</option>';
            }
        }
        return response()->json(['html' => $html]);
    }

    public function AgregarRuc(Request $request)
    {
        $ruc = Ruc::where('num_ruc', $request->agregarruc)->first();

        if ($ruc !== null) {
            $user = User::where('id', $ruc->user_id)->first();

            $messages = [
                'required' => 'EL RUC INGRESADO ESTA ASIGNADO AL ASESOR ' . $user->identificador,
            ];

            $validator = Validator::make($request->all(), [
                'num_ruc' => 'required|unique:rucs',
            ], $messages);

            /*if ($validator->fails()) {
                return redirect('pedidos/create')
                            ->withErrors($validator)
                            ->withInput();
            }*/
            $ruc->update([
                'empresa' => $request->pempresaruc
            ]);

            $html = "false";
        } else {
            $ruc = Ruc::create([
                'num_ruc' => $request->agregarruc,
                'user_id' => Auth::user()->id,
                'cliente_id' => $request->cliente_id_ruc,
                'empresa' => $request->pempresaruc,
                'porcentaje' => ((!$request->porcentajeruc) ? 0.0 : $request->porcentajeruc),
                'estado' => '1'
            ]);
            $html = "true";
        }


        return response()->json(['html' => $html]);

    }

    public function pedidosstore(Request $request)
    {

        //return $request->all();
        $numped = "";
        $mirol = Auth::user()->rol;//
        $codigo = "";
        $identi_asesor = null;
        if ($mirol == 'Llamadas') {
            $identi_asesor = User::where("identificador", $request->user_id)->where("unificado", "NO")->first();
            $fecha = Carbon::now()->format('dm');
            $dia = Carbon::now();
            $numped = Pedido::join('clientes as c', 'c.id', 'pedidos.cliente_id')
                ->join('users as u', 'c.user_id', 'u.id')
                ->where("c.estado", "1")
                ->where("c.tipo", "1")
                ->whereDate('pedidos.created_at', $dia)
                ->where('u.identificador', $request->user_id)
                ->count();
            $numped = $numped + 1;

        } else if ($mirol == 'Jefe de llamadas') {
            $identi_asesor = User::where("identificador", $request->user_id)->where("unificado", "NO")->first();
            $fecha = Carbon::now()->format('dm');
            $dia = Carbon::now();
            $numped = Pedido::join('clientes as c', 'c.id', 'pedidos.cliente_id')
                ->join('users as u', 'c.user_id', 'u.id')
                ->where("c.estado", "1")
                ->where("c.tipo", "1")
                ->whereDate('pedidos.created_at', $dia)
                ->where('u.identificador', $request->user_id)
                ->count();
            $numped = $numped + 1;
        } else {
            $identi_asesor = User::where("identificador", $request->user_id)->where("unificado", "NO")->first();
            $fecha = Carbon::now()->format('dm');
            $dia = Carbon::now();
            $numped = Pedido::join('clientes as c', 'c.id', 'pedidos.cliente_id')
                ->join('users as u', 'c.user_id', 'u.id')
                ->where("c.estado", "1")
                ->where("c.tipo", "1")
                ->whereDate('pedidos.created_at', $dia)
                ->where('u.identificador', $request->user_id)
                ->count();
            $numped = $numped + 1;
        }
        $cliente_AB = Cliente::where("id", $request->cliente_id)->first();
        $codigo = (($identi_asesor->identificador == 'B') ? $identi_asesor->identificador : intval($identi_asesor->identificador)) . (($cliente_AB->icelular != null) ? $cliente_AB->icelular : '') . "-" . $fecha . "-" . $numped;
        //return $codigo;


        $request->validate([
            'cliente_id' => 'required',
        ]);
        //validar
        ///
        //$request->cliente_id

        //$cliente = Cliente::find($request->cliente_id);


        $arreglo = array("ASESOR ADMINISTRATIVO", "Administrador",);

        if (!(in_array($mirol, $arreglo))) {
            //calcular con activacion temporal

            //sino darle bloqueado por 3 maximo en el mes
            //sino  alerta deniega registrar

            $cliente_deuda = Cliente::where("id", $request->cliente_id)
                ->get([
                        'clientes.id',
                        'clientes.crea_temporal',
                        'clientes.activado_tiempo',
                        'clientes.activado_pedido',
                        'clientes.temporal_update',
                        DB::raw(" (select count(ped.id) from pedidos ped where ped.cliente_id=clientes.id and ped.pago in (0,1) and ped.pagado in (0,1) and ped.created_at >='" . now()->startOfMonth()->format("Y-m-d H:i:s") . "' and ped.estado=1) as pedidos_mes_deuda "),
                        DB::raw(" (select count(ped2.id) from pedidos ped2 where ped2.cliente_id=clientes.id and ped2.pago in (0,1) and ped2.pagado in (0,1) and ped2.created_at <='" . now()->startOfMonth()->subMonth()->endOfMonth()->endOfDay()->format("Y-m-d H:i:s") . "'  and ped2.estado=1) as pedidos_mes_deuda_antes ")
                    ]
                )->first();


            if ($cliente_deuda->crea_temporal == 1) {


            } else {


                if ($cliente_deuda->pedidos_mes_deuda > 0 && $cliente_deuda->pedidos_mes_deuda_antes == 0) {
                    if ($cliente_deuda->pedidos_mes_deuda > 4) {
                        $html = "|4";
                        return response()->json(['html' => $html]);
                    }
                } else if ($cliente_deuda->pedidos_mes_deuda > 0 && $cliente_deuda->pedidos_mes_deuda_antes > 0) {
                    $html = "|0";
                    return response()->json(['html' => $html]);
                } else if ($cliente_deuda->pedidos_mes_deuda == 0 && $cliente_deuda->pedidos_mes_deuda_antes > 0) {
                    $html = "|0";
                    return response()->json(['html' => $html]);
                }
            }


        }

        //return $cliente_deuda->pedidos_mes_deuda;


        try {

            DB::beginTransaction();

            $pedido = Pedido::create([
                'cliente_id' => $request->cliente_id,
                'user_id' => $identi_asesor->id, //usuario que registra
                'creador' => 'USER0' . Auth::user()->id,//aqui una observacion, en el migrate la columna en tabla pedido tenia nombre creador y resulto ser creador_id
                'condicion' => Pedido::POR_ATENDER,
                'condicion_code' => 1,
                'condicion_int' => '1',
                'pago' => '0',
                'envio' => '0',
                'condicion_envio' => Pedido::POR_ATENDER_OPE,
                'condicion_envio_code' => Pedido::POR_ATENDER_INT,
                'estado' => '1',
                'codigo' => $codigo,
                'notificacion' => 'Nuevo pedido creado',
                'modificador' => 'USER0' . Auth::user()->id,
                'pagado' => '0',
                'direccion' => '0'
            ]);

            $pedido->update([
                "correlativo" => $pedido->id_code
            ]);

            if ($cliente_AB->situacion == 'ABANDONO RECIENTE') {
                $cliente_AB->update([
                    'situacion' => 'RECUPERADO RECIENTE',
                ]);
            } else if ($cliente_AB->situacion == 'ABANDONO') {
                $cliente_AB->update([
                    'situacion' => 'RECUPERADO ABANDONO'
                ]);
            }else if ($cliente_AB->situacion == 'BASE FRIA') {
                $cliente_AB->update([
                    'situacion' => 'NUEVO'
                ]);
            }


            // ALMACENANDO DETALLES
            $codigo = $codigo;//$request->codigo; actualizado para codigo autogenerado
            $codigo_generado = $codigo;
            $nombre_empresa = $request->nombre_empresa;
            $mes = $request->mes;
            $anio = $request->anio;
            $ruc = $request->ruc;
            $cantidad = $request->cantidad;
            $tipo_banca = $request->tipo_banca;
            $porcentaje = $request->porcentaje;
            $courier = $request->courier;
            $descripcion = $request->descripcion;
            $nota = $request->nota;

            $files = $request->file('adjunto');
            //return $files;
            //$files = $request->adjunto;
            $destinationPath = base_path('public/storage/adjuntos/');

            $cont = 0;
            $fileList = [];


            if (isset($files)) {

                $cont = 0;
                foreach ($files as $file) {
                    $file_name = Carbon::now()->second . $file->getClientOriginalName();
                    $file->move($destinationPath, $file_name);

                    ImagenPedido::create([
                        'pedido_id' => $pedido->id,
                        'adjunto' => $file_name,
                        'estado' => '1'
                    ]);
                }
            } else {
                ImagenPedido::create([
                    'pedido_id' => $pedido->id,
                    'adjunto' => 'logo_facturas.png',
                    'estado' => '1'
                ]);
                $cont = 0;
                $fileList[$cont] = array(
                    'file_name' => 'logo_facturas.png',
                );
            }
            /*
                        if (isset($files)) {
                            $destinationPath = base_path('public/storage/adjuntos/');
                            $cont = 0;
                            $file_name = Carbon::now()->second . $files->getClientOriginalName();
                            $fileList[$cont] = array(
                                'file_name' => $file_name,
                            );
                            $files->move($destinationPath, $file_name);

                            ImagenPedido::create([
                                'pedido_id' => $pedido->id,
                                'adjunto' => $file_name,
                                'estado' => '1'
                            ]);

                            //$cont++;
                            //}
                        } else {
                            ImagenPedido::create([
                                'pedido_id' => $pedido->id,
                                'adjunto' => 'logo_facturas.png',
                                'estado' => '1'
                            ]);
                            $cont = 0;
                            $fileList[$cont] = array(
                                'file_name' => 'logo_facturas.png',
                            );

                        }*/

            $contP = 0;

            while ($contP < count((array)$codigo)) {

                $detallepedido = DetallePedido::create([
                    'pedido_id' => $pedido->id,
                    'codigo' => $codigo_generado,//$codigo[$contP],
                    'nombre_empresa' => $nombre_empresa[$contP],
                    'mes' => $mes[$contP],
                    'anio' => $anio[$contP],
                    'ruc' => $ruc[$contP],
                    'cantidad' => $cantidad[$contP],
                    'tipo_banca' => $tipo_banca[$contP],
                    'porcentaje' => $porcentaje[$contP],
                    'ft' => ($cantidad[$contP] * $porcentaje[$contP]) / 100,
                    'courier' => $courier[$contP],
                    'total' => (($cantidad[$contP] * $porcentaje[$contP]) / 100) + $courier[$contP],
                    'saldo' => (($cantidad[$contP] * $porcentaje[$contP]) / 100) + $courier[$contP],
                    'descripcion' => $descripcion[$contP],
                    'nota' => $nota[$contP],
                    'estado' => '1',//,
                    //'adjunto' => $fileList[$contP]['file_name']
                ]);

                $contP++;

                //ACTUALIZAR DEUDA
                $cliente = Cliente::find($request->cliente_id);

                $fecha = Carbon::now()->format('dm');
                $dia = Carbon::now()->toDateString();
                //
                $dateMinWhere = Carbon::now()->subDays(60)->format('d/m/Y');
                $dateMin = Carbon::now()->subDays(30)->format('d/m/Y');
                $dateMax = Carbon::now()->format('d/m/Y');

                $valido_deudas_mes = Pedido::where("pedidos.cliente_id", $request->cliente_id)
                    ->where("pedidos.estado", "1")
                    ->where("pedidos.pago", "0")
                    //->between("pedidos.estado","1")
                    ->whereBetween('pedidos.created_at', [$dateMinWhere, $dateMax])
                    ->where("pedidos.created_at", "<", $dateMin)->count();
                if ($valido_deudas_mes > 0) {
                    $cliente->update([
                        'deuda' => '1',
                        'pidio' => '1'
                    ]);

                } else {
                    $cliente->update([
                        'deuda' => '0',
                        'pidio' => '1'
                    ]);
                }
            }
            DB::commit();
            $html = $pedido->id;
        } catch (\Throwable $th) {
            throw $th;
            $html = "0";
            /* DB::rollback();
            dd($th); */
        }
        return response()->json(['html' => $html]);
        //return redirect()->route('pedidosPDF', $pedido)->with('info', 'registrado');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Pedido $pedido)
    {
        //ver pedido anulado y activo
        $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
            ->join('users as u', 'pedidos.user_id', 'u.id')
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->select(
                'pedidos.id',
                'pedidos.motivo',
                'c.nombre as nombres',
                'c.celular as celulares',
                'u.name as users',
                'dp.codigo as codigos',
                'dp.nombre_empresa as empresas',
                'dp.mes',
                'dp.anio',
                'dp.ruc',
                'dp.cantidad',
                'dp.tipo_banca',
                'dp.porcentaje',
                'dp.courier',
                'dp.ft',
                'dp.descripcion',
                'dp.nota',
                'dp.adjunto',
                'dp.total',
                'dp.envio_doc',
                'dp.fecha_envio_doc',
                'dp.cant_compro',
                'dp.fecha_envio_doc_fis',
                'dp.fecha_recepcion',
                'pedidos.condicion as condiciones',
                'pedidos.created_at as fecha',
                'pedidos.path_adjunto_anular',
                'pedidos.path_adjunto_anular_disk',
                'pedidos.pendiente_anulacion',
                'pedidos.user_anulacion_id',
                'pedidos.fecha_anulacion',
                'pedidos.fecha_anulacion_confirm',
                'pedidos.responsable',
                'pedidos.condicion_code',
            )
            //->where('pedidos.estado', '1')
            ->where('pedidos.id', $pedido->id)
            //->where('dp.estado', '1')
            /*->groupBy(
                'pedidos.id',
                'c.nombre',
                'c.celular',
                'u.name',
                'dp.codigo',
                'dp.nombre_empresa',
                'dp.mes',
                'dp.anio',
                'dp.ruc',
                'dp.cantidad',
                'dp.tipo_banca',
                'dp.porcentaje',
                'dp.courier',
                'dp.ft',
                'dp.descripcion',
                'dp.nota',
                'dp.adjunto',
                'dp.total',
                'dp.envio_doc',
                'dp.fecha_envio_doc',
                'dp.cant_compro',
                'dp.fecha_envio_doc_fis',
                'dp.fecha_recepcion',
                'pedidos.condicion',
                'pedidos.created_at',
                'pedidos.path_adjunto_anular',
                'pedidos.path_adjunto_anular_disk',
                'pedidos.pendiente_anulacion',
                'pedidos.user_anulacion_id',
                'pedidos.fecha_anulacion',
                'pedidos.fecha_anulacion_confirm',
                'pedidos.responsable',
            )
            */
            ->orderBy('pedidos.created_at', 'DESC')
            ->get();

        $cotizacion = Pedido::query()->with(['cliente'])
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->select(
                'pedidos.id',
                'dp.nombre_empresa',
                'dp.cantidad',
                'dp.porcentaje',
                'dp.ft',
                'dp.courier',
                'dp.total',
                'dp.saldo as diferencia',
            )
            ->where('pedidos.id', $pedido->id)
            ->first();

        $deudaTotal = DetallePedido::query()->whereIn('pedido_id', $pedido->cliente->pedidos()->where('estado', '1')->pluck("id"))->sum("saldo");
        $adelanto = PagoPedido::query()->where('pedido_id', $pedido->id)->whereEstado(1)->sum('abono');

        $imagenes = ImagenPedido::where('imagen_pedidos.pedido_id', $pedido->id)->where('estado', '1')->get();

        $imagenesatencion = ImagenAtencion::where('pedido_id', $pedido->id)->where('estado', '=', '1')->orderByDesc('estado')->get();

        return view('pedidos.show', compact('pedidos', 'imagenes', 'imagenesatencion', 'cotizacion', 'adelanto', 'deudaTotal'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Pedido $pedido)
    {
        $mirol = Auth::user()->rol;
        $meses = [
            "ENERO" => 'ENERO',
            "FEBRERO" => 'FEBRERO',
            "MARZO" => 'MARZO',
            "ABRIL" => 'ABRIL',
            "MAYO" => 'MAYO',
            "JUNIO" => 'JUNIO',
            "JULIO" => 'JULIO',
            "AGOSTO" => 'AGOSTO',
            "SEPTIEMBRE" => 'SEPTIEMBRE',
            "OCTUBRE" => 'OCTUBRE',
            "NOVIEMBRE" => 'NOVIEMBRE',
            "DICIEMBRE" => 'DICIEMBRE',
        ];

        $anios = [
            "2020" => '2020',
            "2021" => '2021',
            "2022" => '2022',
            "2023" => '2023',
            "2024" => '2024',
            "2025" => '2025',
            "2026" => '2026',
            "2027" => '2027',
            "2028" => '2028',
            "2029" => '2029',
            "2030" => '2030',
            "2031" => '2031',
        ];

        $porcentajes = Porcentaje::where('porcentajes.cliente_id', $pedido->cliente_id)
            ->get();

        $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
            ->join('users as u', 'pedidos.user_id', 'u.id')
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->select(
                'pedidos.id',
                'c.nombre as nombres',
                'c.celular as celulares',
                'u.name as users',
                'dp.codigo as codigos',
                'dp.nombre_empresa as empresas',
                'dp.mes',
                'dp.anio',
                'dp.ruc',
                'dp.cantidad',
                'dp.tipo_banca',
                'dp.porcentaje',
                'dp.courier',
                'dp.ft',
                'dp.descripcion',
                'dp.nota',
                'dp.adjunto',
                'dp.total',
                'pedidos.condicion as condiciones',
                'pedidos.created_at as fecha'
            )
            ->where('pedidos.estado', '1')
            ->where('pedidos.id', $pedido->id)
            ->where('dp.estado', '1')
            ->groupBy(
                'pedidos.id',
                'c.nombre',
                'c.celular',
                'u.name',
                'dp.codigo',
                'dp.nombre_empresa',
                'dp.mes',
                'dp.anio',
                'dp.ruc',
                'dp.cantidad',
                'dp.tipo_banca',
                'dp.porcentaje',
                'dp.courier',
                'dp.ft',
                'dp.descripcion',
                'dp.nota',
                'dp.adjunto',
                'dp.total',
                'pedidos.condicion',
                'pedidos.created_at'
            )
            ->orderBy('pedidos.created_at', 'DESC')
            ->get();

        $imagenes = ImagenPedido::where('imagen_pedidos.pedido_id', $pedido->id)->get();

        return view('pedidos.edit', compact('pedido', 'pedidos', 'meses', 'anios', 'porcentajes', 'imagenes', 'mirol'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Pedido $pedido)
    {/*return $request->all();*/
        $detallepedido = DetallePedido::where('pedido_id', $pedido->id)->first();
        try {
            DB::beginTransaction();

            // ALMACENANDO DETALLES
            $codigo = $request->codigo;
            $nombre_empresa = $request->nombre_empresa;
            $mes = $request->mes;
            $anio = $request->anio;
            $ruc = $request->ruc;
            $cantidad = $request->cantidad;
            $tipo_banca = $request->tipo_banca;
            $porcentaje = $request->porcentaje;
            $courier = $request->courier;
            $descripcion = $request->descripcion;
            $nota = $request->nota;
            $contP = 0;

            $files = $request->file('adjunto');
            $destinationPath = base_path('public/storage/adjuntos/');
            $cont = 0;


            if (isset($files)) {
                foreach ($files as $file) {
                    $file_name = Carbon::now()->second . $file->getClientOriginalName(); //Get file original name
                    $file->move($destinationPath, $file_name);

                    ImagenPedido::create([
                        'pedido_id' => $pedido->id,
                        'adjunto' => $file_name,
                        'estado' => '1'
                    ]);

                    $cont++;
                }
            }

            while ($contP < count((array)$codigo)) {
                $detallepedido->update([
                    'codigo' => $codigo[$contP],
                    'nombre_empresa' => $nombre_empresa[$contP],
                    'mes' => $mes[$contP],
                    'anio' => $anio[$contP],
                    'ruc' => $ruc[$contP],
                    'cantidad' => $cantidad[$contP],
                    'tipo_banca' => $tipo_banca[$contP],
                    'porcentaje' => $porcentaje[$contP],
                    'ft' => ($cantidad[$contP] * $porcentaje[$contP]) / 100,
                    'courier' => $courier[$contP],
                    'total' => (($cantidad[$contP] * $porcentaje[$contP]) / 100) + $courier[$contP],
                    'saldo' => (($cantidad[$contP] * $porcentaje[$contP]) / 100) + $courier[$contP],
                    'descripcion' => $descripcion[$contP],
                    'nota' => $nota[$contP]
                ]);

                $contP++;
            }

            //ACTUALIZAR PORCENTAJE EN CLIENTE
            $porcentaje = Porcentaje::where('cliente_id', $pedido->cliente_id)
                ->where('nombre', $detallepedido->tipo_banca);
            $porcentaje->update([
                'porcentaje' => $detallepedido->porcentaje
            ]);

            //ACTUALIZAR MODIFICACION AL PEDIDO
            $pedido->update([
                'modificador' => 'USER' . Auth::user()->id
            ]);

            DB::commit();
        } catch (\Throwable $th) {
            throw $th;
            /*DB::rollback();
            dd($th);*/
        }

        if (Auth::user()->rol == "Asesor") {
            return redirect()->route('pedidos.mispedidos')->with('info', 'actualizado');
        } else
            return redirect()->route('pedidos.index')->with('info', 'actualizado');


    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Pedido $pedido)
    {
        $detalle_pedidos = DetallePedido::find($pedido->id);
        $pedido->update([
            'motivo' => $request->motivo,
            'responsable' => $request->responsable,
            'condicion' => 'ANULADO',
            'modificador' => 'USER' . Auth::user()->id,
            'estado' => '0'
        ]);

        $detalle_pedidos->update([
            'estado' => '0'
        ]);

        //ACTUALIZAR QUE CLIENTE NO DEBE
        $cliente = Cliente::find($pedido->cliente_id);

        $pedido_deuda = Pedido::where('cliente_id', $pedido->cliente_id)//CONTAR LA CANTIDAD DE PEDIDOS QUE DEBE
        ->where('pagado', '0')
            ->count();
        if ($pedido_deuda == 0) {//SINO DEBE NINGUN PEDIDO EL ESTADO DEL CLIENTE PASA A NO DEUDA(CERO)
            $cliente->update([
                'deuda' => '0'
            ]);
        }

        return redirect()->route('pedidos.index')->with('info', 'eliminado');
    }

    public function destroyid(Request $request)
    {
        if (!$request->hiddenID) {
            $html = '';
        } else {
            $pedido = Pedido::findOrFail($request->hiddenID);
            $filePaths = [];
            $files = $request->attachments;
            if (is_array($files)) {
                foreach ($files as $file) {
                    if ($file instanceof UploadedFile) {
                        $filePaths[] = $file->store("pedidos_adjuntos", "pstorage");
                    }
                }
            }

            setting()->load();
            foreach ($filePaths as $index => $path) {
                $key = "pedido." . $pedido->id . ".adjuntos_file." . $index;
                $keyd = "pedido." . $pedido->id . ".adjuntos_disk." . $index;
                setting([
                    $key => $path,
                    $keyd => 'pstorage'
                ]);
            }
            setting()->save();

            /**
             * tipo_banca
             * ELECTRONICA - sin banca
             * ELECTRONICA - banca
             * FISICO - sin banca
             * FISICA - sin banca
             * ELECTRONICA - bancarizado
             */
            $is_fisico = $pedido->detallePedidos()->whereIn('detalle_pedidos.tipo_banca', [
                'FISICO - banca',
                'FISICO - sin banca',
                'FISICA - sin banca',
            ])->count();
            if ($is_fisico == 0 && $pedido->condicion_code == Pedido::ATENDIDO_INT) {
                //pendiente de anulacion
                $pedido->update([
                    'motivo' => $request->motivo,
                    'responsable' => $request->responsable,
                    'pendiente_anulacion' => 1,
                    'path_adjunto_anular' => null,
                    'path_adjunto_anular_disk' => 'pstorage',
                    'modificador' => 'USER' . Auth::user()->id,
                    'fecha_anulacion' => now(),
                ]);
                $html = '';
            } else {
                $pedido->update([
                    'motivo' => $request->motivo,
                    'responsable' => $request->responsable,
                    'condicion' => 'ANULADO',
                    'condicion_code' => Pedido::ANULADO_INT,
                    'modificador' => 'USER' . Auth::user()->id,
                    'user_anulacion_id' => Auth::user()->id,
                    'fecha_anulacion' => now(),
                    'fecha_anulacion_confirm' => now(),
                    'estado' => '0',
                    'path_adjunto_anular' => null,
                    'path_adjunto_anular_disk' => 'pstorage',
                ]);
                //$detalle_pedidos = DetallePedido::find($request->hiddenID);
                $detalle_pedidos = DetallePedido::where('pedido_id', $request->hiddenID)->first();

                $detalle_pedidos->update([
                    'estado' => '0'
                ]);
                $html = $detalle_pedidos;
            }


        }
        return response()->json(['html' => $html]);
    }

    public function destroyidpedidoadjuntooperaciones(Request $request)
    {
        if (!$request->hiddenID) {
            $html = '';
        } else {
            ImagenAtencion::where("pedido_id", $request->hiddenID)->update([
                'estado' => '0'
            ]);

            //$detalle_pedidos = DetallePedido::find($request->hiddenID);
            //$detalle_pedidos = DetallePedido::where('pedido_id',$request->hiddenID)->first() ;

            /*$detalle_pedidos->update([
                'estado' => '0'
            ]);*/

            $html = $request;
        }
        return response()->json(['html' => $html]);
    }

    public function Restaurarid(Request $request)
    {
        if (!$request->hiddenID) {
            $html = '';
        } else {
            Pedido::find($request->hiddenID)->update([
                'condicion' => Pedido::POR_ATENDER,
                'condicion_code' => Pedido::POR_ATENDER_INT,
                'modificador' => 'USER' . Auth::user()->id,
                'estado' => '1',
                'pendiente_anulacion' => '0'
            ]);
            $detalle_pedidos = DetallePedido::where('pedido_id', $request->hiddenID)->first();

            $detalle_pedidos->update([
                'estado' => '1',
            ]);
            $html = $detalle_pedidos;
        }

        return response()->json(['html' => $html]);
    }

    public function viewVentas()
    {
        return view('ventas.reportes.index');
    }

    public function MisPedidos()
    {
        $dateMin = Carbon::now()->subDays(4)->format('d/m/Y');
        $dateMax = Carbon::now()->format('d/m/Y');

        $mirol = Auth::user()->rol;

        $destinos = [
            "LIMA" => 'LIMA',
            "PROVINCIA" => 'PROVINCIA'
        ];

        $superasesor = User::where('rol', 'Super asesor')->count();

        return view('pedidos.misPedidos', compact('destinos', 'superasesor', 'dateMin', 'dateMax', 'mirol'));
    }

    public function mispedidostabla(Request $request)
    {
        $pedidos = null;

        $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
            ->join('users as u', 'pedidos.user_id', 'u.id')
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->select(
                'pedidos.id',
                'c.nombre as nombres',
                'c.icelular as icelulares',
                'c.celular as celulares',
                'u.identificador as users',
                'dp.codigo as codigos',
                'dp.nombre_empresa as empresas',
                'dp.total as total',
                'dp.cantidad as cantidad',
                'dp.ruc as ruc',
                'pedidos.condicion_envio as condicion_env',
                'pedidos.condicion_envio',
                'pedidos.condicion as condiciones',
                'pedidos.condicion_code',

                /*'pedidos.envio',*/
                'pedidos.direccion',
                'pedidos.destino',
                'pedidos.motivo',
                'pedidos.responsable',
                'pedidos.pendiente_anulacion',
                'dp.saldo as diferencia',
                'pedidos.pagado as condicion_pa',
                DB::raw('DATE_FORMAT(pedidos.created_at, "%d/%m/%Y") as fecha'),
                'pedidos.estado'
            )
            ->where('pedidos.estado', '1')
            ->where('dp.estado', '1')
            ->whereIn('pedidos.condicion_code', [Pedido::POR_ATENDER_INT, Pedido::EN_PROCESO_ATENCION_INT, Pedido::ATENDIDO_INT, Pedido::ANULADO_INT]);


        if (Auth::user()->rol == "Asesor") {

            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.identificador', Auth::user()->identificador)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos = $pedidos->WhereIn('u.identificador', $usersasesores);


        } else if (Auth::user()->rol == "Super asesor") {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.identificador', Auth::user()->identificador)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos = $pedidos->WhereIn('u.identificador', $usersasesores);

        }
        //$pedidos=$pedidos->get();

        return Datatables::of(DB::table($pedidos))
            ->addIndexColumn()
            ->addColumn('action', function ($pedido) {

                $btn = ' <div>

  <ul class="" aria-labelledby="dropdownMenuButton">';

                $btn = $btn . '
                <a href="' . route('pedidosPDF', data_get($pedido, 'id')) . '" class="btn-sm dropdown-item" target="_blank"><i class="fa fa-file-pdf text-primary"></i> Ver PDF</a>';
                $btn = $btn . '<a href="' . route('pedidos.show', data_get($pedido, 'id')) . '" class="btn-sm dropdown-item"><i class="fas fa-eye text-success"></i> Ver pedido</a>';

                if ($pedido->estado > 0) {

                    if (Auth::user()->rol == "Super asesor" || Auth::user()->rol == "Administrador") {
                        if (!$pedido->pendiente_anulacion) {
                            $btn = $btn . '<a href="' . route('pedidos.edit', $pedido->id) . '" class="btn-sm dropdown-item"><i class="fas fa-edit text-warning" aria-hidden="true"></i> Editar pedido</a>';
                        }
                    }

                    if (Auth::user()->rol == "Administrador") {
                        if (!$pedido->pendiente_anulacion) {
                            $btn = $btn . '<a href="" class="btn-sm dropdown-item" data-target="#modal-delete" data-toggle="modal" data-delete="' . $pedido->id . '"><i class="fas fa-trash-alt text-danger"></i> Anular pedido</a>';
                        }
                    }


                }

                $btn = $btn . '</ul></div>';

                return $btn;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function Pagados()//PEDIDOS PAGADOS
    {
        $dateMin = Carbon::now()->subDays(4)->format('d/m/Y');
        $dateMax = Carbon::now()->format('d/m/Y');

        $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
            ->join('users as u', 'pedidos.user_id', 'u.id')
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->join('pago_pedidos as pp', 'pedidos.id', 'pp.pedido_id')
            ->join('pagos as pa', 'pp.pago_id', 'pa.id')
            ->select(
                'pedidos.id',
                'c.nombre as nombres',
                'c.icelular as icelulares',
                'c.celular as celulares',
                'u.identificador as users',
                'dp.codigo as codigos',
                'dp.nombre_empresa as empresas',
                /* DB::raw('sum(dp.total) as total'), */
                'dp.total as total',
                'pedidos.condicion as condiciones',
                'pedidos.motivo',
                'pedidos.responsable',
                'pedidos.pagado as condicion_pa',//'pa.condicion as condicion_pa',
                DB::raw('(select pago.condicion from pago_pedidos pagopedido inner join pedidos pedido on pedido.id=pagopedido.pedido_id and pedido.id=pedidos.id inner join pagos pago on pagopedido.pago_id=pago.id where pagopedido.estado=1 and pago.estado=1 order by pagopedido.created_at desc limit 1) as condiciones_aprobado'),
                /* 'pedidos.created_at as fecha' */
                DB::raw('DATE_FORMAT(pedidos.created_at, "%d/%m/%Y") as fecha')
            )
            ->where('pedidos.estado', '1')
            ->where('dp.estado', '1')
            ->where('u.id', Auth::user()->id)
            ->where('pa.condicion', Pago::PAGO)
            ->groupBy(
                'pedidos.id',
                'c.nombre',
                'c.icelular',
                'c.celular',
                'u.identificador',
                'dp.codigo',
                'dp.nombre_empresa',
                'dp.total',
                'pedidos.condicion',
                'pedidos.motivo',
                'pedidos.responsable',
                'pedidos.pagado',
                'pa.condicion',
                'pedidos.created_at')
            ->orderBy('pedidos.created_at', 'DESC')
            ->get();

        $superasesor = User::where('rol', 'Super asesor')->count();

        $miidentificador = User::where("id", Auth::user()->id)->first()->identificador;

        return view('pedidos.pagados', compact('pedidos', 'superasesor', 'dateMin', 'dateMax', 'miidentificador'));
    }

    public function Pagadostabla()
    {
        $dateMin = Carbon::now()->subDays(4)->format('d/m/Y');
        $dateMax = Carbon::now()->format('d/m/Y');

        $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
            ->join('users as u', 'pedidos.user_id', 'u.id')
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->join('pago_pedidos as pp', 'pedidos.id', 'pp.pedido_id')
            ->join('pagos as pa', 'pp.pago_id', 'pa.id')
            ->select(
                'pedidos.id',
                'c.nombre as nombres',
                'c.icelular as icelulares',
                'c.celular as celulares',
                'u.identificador as users',
                'dp.codigo as codigos',
                'dp.nombre_empresa as empresas',
                'dp.total as total',
                'pedidos.condicion as condiciones',
                'pedidos.motivo',
                'pedidos.responsable',
                'pedidos.pagado as condicion_pa',
                DB::raw('(select pago.condicion from pago_pedidos pagopedido inner join pedidos pedido on pedido.id=pagopedido.pedido_id and pedido.id=pedidos.id inner join pagos pago on pagopedido.pago_id=pago.id where pagopedido.estado=1 and pago.estado=1 order by pagopedido.created_at desc limit 1) as condiciones_aprobado'),
                DB::raw('DATE_FORMAT(pedidos.created_at, "%d/%m/%Y") as fecha')
            )
            ->where('pedidos.estado', '1')
            ->where('dp.estado', '1')
            ->where('u.id', Auth::user()->id)
            ->where('pa.condicion', Pago::PAGO)
            ->groupBy(
                'pedidos.id',
                'c.nombre',
                'c.icelular',
                'c.celular',
                'u.identificador',
                'dp.codigo',
                'dp.nombre_empresa',
                'dp.total',
                'pedidos.condicion',
                'pedidos.motivo',
                'pedidos.responsable',
                'pedidos.pagado',
                'pa.condicion',
                'pedidos.created_at')
            ->orderBy('pedidos.created_at', 'DESC');
        //->get();


        return Datatables::of(DB::table($pedidos))
            ->addIndexColumn()
            ->addColumn('action', function ($pedido) {
                $btn = '';

                return $btn;
            })
            ->rawColumns(['action'])
            ->make(true);

    }

    public function SinPagos()//PEDIDOS POR COBRAR
    {

        $miidentificador = User::where("id", Auth::user()->id)->first()->identificador;

        $superasesor = User::where('rol', 'Super asesor')->count();

        return view('pedidos.sinPagos', compact('superasesor', 'miidentificador'));
    }

    public function SinPagostabla()
    {
        $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
            ->join('users as u', 'pedidos.user_id', 'u.id')
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->select(
                'pedidos.id',
                'c.id as cliente_id',
                'c.nombre as nombres',
                'c.icelular as icelulares',
                'c.celular as celulares',
                'u.identificador as users',
                'dp.codigo as codigos',
                'dp.nombre_empresa as empresas',
                'dp.total as total',
                'pedidos.estado_sobre',
                'pedidos.condicion as condiciones',
                'pedidos.condicion_code',
                'pedidos.condicion_envio',
                'pedidos.condicion_envio_code',
                'pedidos.motivo',
                'pedidos.pendiente_anulacion',
                'pedidos.responsable',
                'pedidos.pagado as condicion_pa',
                'pedidos.created_at as fecha',
                'dp.saldo as diferencia',
                DB::raw('(select pago.condicion_code from pago_pedidos pagopedido inner join pedidos pedido on pedido.id=pagopedido.pedido_id and pedido.id=pedidos.id inner join pagos pago on pagopedido.pago_id=pago.id where pagopedido.estado=1 and pago.estado=1 order by pagopedido.created_at desc limit 1) as condiciones_aprobado'),
                'pedidos.estado_ruta',
                'pedidos.estado'
            )
            ->where('pedidos.estado', '1')
            ->where('dp.estado', '1')
            ->where('pedidos.pagado', '<>', '2')
            ->where('pedidos.da_confirmar_descarga', '1')           
            ->orderBy('pedidos.created_at', 'DESC');
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
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.supervisor', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos = $pedidos->WhereIn('u.identificador', $usersasesores);

        }

        return Datatables::of(DB::table($pedidos))
            ->addIndexColumn()
            ->addColumn('condicion_envio_color', function ($pedido) {
                return Pedido::getColorByCondicionEnvio($pedido->condicion_envio);
            })
            ->editColumn('condicion_envio', function ($pedido) {
                $badge_estado='';
                if($pedido->pendiente_anulacion=='1')
                {
                    $badge_estado.='<span class="badge badge-success">' . Pedido::PENDIENTE_ANULACION.'</span>';
                    return $badge_estado;
                }
                if($pedido->condicion_code=='4' || $pedido->estado=='0')
                {
                    return '<span class="badge badge-danger">ANULADO</span>';
                }
                if($pedido->estado_sobre=='1')
                {
                    $badge_estado .= '<span class="badge badge-dark p-8" style="color: #fff; background-color: #347cc4; font-weight: 600; margin-bottom: -2px;border-radius: 4px 4px 0px 0px; font-size:8px;  padding: 4px 4px !important; font-weight: 500;">Direccion agregada</span>';

                }
                if($pedido->estado_ruta=='1')
                {
                    $badge_estado.='<span class="badge badge-success w-50" style="background-color: #00bc8c !important;
                    padding: 4px 8px !important;
                    font-size: 8px;
                    margin-bottom: -4px;
                    color: black !important;">Con ruta</span>';
                }
                $color = Pedido::getColorByCondicionEnvio($pedido->condicion_envio);
                $badge_estado.= '<span class="badge badge-success w-100" style="background-color: ' . $color . '!important;">' . $pedido->condicion_envio . '</span>';
                return $badge_estado;
            })
            ->addColumn('action', function ($pedido) {
                $btn = '';

                return $btn;
            })
            ->rawColumns(['action','condicion_envio'])
            ->make(true);

    }


    public function EnAtenciontabla(Request $request)
    {
        if (Auth::user()->rol == "Operario") {

            $asesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->Where('users.operario', Auth::user()->id)
                ->select(
                    DB::raw("users.id as id")
                )
                ->pluck('users.id');

            $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
                ->join('users as u', 'pedidos.user_id', 'u.id')
                ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
                ->select(
                    'pedidos.id',
                    'c.nombre as nombres',
                    'c.celular as celulares',
                    'u.identificador as users',
                    'dp.codigo as codigos',
                    'dp.nombre_empresa as empresas',
                    /* DB::raw('sum(dp.total) as total'), */
                    'dp.total as total',
                    'pedidos.condicion',
                    /* 'pedidos.created_at as fecha', */
                    DB::raw('DATE_FORMAT(pedidos.created_at, "%d/%m/%Y") as fecha'),
                    'dp.envio_doc',
                    'dp.fecha_envio_doc',
                    'dp.cant_compro',
                    'dp.fecha_envio_doc_fis',
                    'dp.fecha_recepcion'
                )
                ->where('pedidos.estado', '1')
                ->where('dp.estado', '1')
                ->WhereIn('pedidos.user_id', $asesores)
                //->where('u.operario', Auth::user()->id)
                ->where('pedidos.condicion', Pedido::EN_PROCESO_ATENCION)
                ->groupBy(
                    'pedidos.id',
                    'c.nombre',
                    'c.celular',
                    'u.identificador',
                    'dp.codigo',
                    'dp.nombre_empresa',
                    'dp.total',
                    'pedidos.condicion',
                    'pedidos.created_at',
                    'dp.envio_doc',
                    'dp.fecha_envio_doc',
                    'dp.cant_compro',
                    'dp.fecha_envio_doc_fis',
                    'dp.fecha_recepcion'
                )
                ->orderBy('pedidos.created_at', 'DESC')
                ->get();
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

            $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
                ->join('users as u', 'pedidos.user_id', 'u.id')
                ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
                ->select(
                    'pedidos.id',
                    'c.nombre as nombres',
                    'c.celular as celulares',
                    'u.identificador as users',
                    'dp.codigo as codigos',
                    'dp.nombre_empresa as empresas',
                    /* DB::raw('sum(dp.total) as total'), */
                    'dp.total as total',
                    'pedidos.condicion',
                    /* 'pedidos.created_at as fecha', */
                    DB::raw('DATE_FORMAT(pedidos.created_at, "%d/%m/%Y") as fecha'),
                    'dp.envio_doc',
                    'dp.fecha_envio_doc',
                    'dp.cant_compro',
                    'dp.fecha_envio_doc_fis',
                    'dp.fecha_recepcion'
                )
                ->where('pedidos.estado', '1')
                ->where('dp.estado', '1')
                ->WhereIn('pedidos.user_id', $asesores)
                //->where('u.jefe', Auth::user()->id)
                ->where('pedidos.condicion', Pedido: EN_PROCESO_ATENCION)
                ->groupBy(
                    'pedidos.id',
                    'c.nombre',
                    'c.celular',
                    'u.identificador',
                    'dp.codigo',
                    'dp.nombre_empresa',
                    'dp.total',
                    'pedidos.condicion',
                    'pedidos.created_at',
                    'dp.envio_doc',
                    'dp.fecha_envio_doc',
                    'dp.cant_compro',
                    'dp.fecha_envio_doc_fis',
                    'dp.fecha_recepcion'
                )
                ->orderBy('pedidos.created_at', 'DESC');
            //->get();
        } else {
            $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
                ->join('users as u', 'pedidos.user_id', 'u.id')
                ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
                ->select(
                    'pedidos.id',
                    'c.nombre as nombres',
                    'c.celular as celulares',
                    'u.identificador as users',
                    'dp.codigo as codigos',
                    'dp.nombre_empresa as empresas',
                    /* DB::raw('sum(dp.total) as total'), */
                    'dp.total as total',
                    'pedidos.condicion',
                    /* 'pedidos.created_at as fecha', */
                    DB::raw('DATE_FORMAT(pedidos.created_at, "%d/%m/%Y") as fecha'),
                    'dp.envio_doc',
                    'dp.fecha_envio_doc',
                    'dp.cant_compro',
                    'dp.fecha_envio_doc_fis',
                    'dp.fecha_recepcion'
                )
                ->where('pedidos.estado', '1')
                ->where('dp.estado', '1')
                ->where('pedidos.condicion', Pedido::EN_PROCESO_ATENCION)
                ->groupBy(
                    'pedidos.id',
                    'c.nombre',
                    'c.celular',
                    'u.identificador',
                    'dp.codigo',
                    'dp.nombre_empresa',
                    'dp.total',
                    'pedidos.condicion',
                    'pedidos.created_at',
                    'dp.envio_doc',
                    'dp.fecha_envio_doc',
                    'dp.cant_compro',
                    'dp.fecha_envio_doc_fis',
                    'dp.fecha_recepcion'
                )
                ->orderBy('pedidos.created_at', 'DESC');
            //->get();
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

    public function EnAtencion()
    {
        $dateMin = Carbon::now()->subDays(4)->format('d/m/Y');
        $dateMax = Carbon::now()->format('d/m/Y');

        $condiciones = [
            "POR ATENDER" => Pedido::POR_ATENDER,
            "EN PROCESO ATENCION" => Pedido::EN_PROCESO_ATENCION,
            "ATENDIDO" => Pedido::ATENDIDO
        ];

        if (Auth::user()->rol == "Operario") {
            $asesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->Where('users.operario', Auth::user()->id)
                ->select(
                    DB::raw("users.id as id")
                )
                ->pluck('users.id');

            $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
                ->join('users as u', 'pedidos.user_id', 'u.id')
                ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
                ->select(
                    'pedidos.id',
                    'c.nombre as nombres',
                    'c.celular as celulares',
                    'u.name as users',
                    'dp.codigo as codigos',
                    'dp.nombre_empresa as empresas',
                    /* DB::raw('sum(dp.total) as total'), */
                    'dp.total as total',
                    'pedidos.condicion',
                    /* 'pedidos.created_at as fecha', */
                    DB::raw('DATE_FORMAT(pedidos.created_at, "%d/%m/%Y") as fecha'),
                    'dp.envio_doc',
                    'dp.fecha_envio_doc',
                    'dp.cant_compro',
                    'dp.fecha_envio_doc_fis',
                    'dp.fecha_recepcion'
                )
                ->where('pedidos.estado', '1')
                ->where('dp.estado', '1')
                ->WhereIn('u.identificador', $asesores)
                //->where('u.operario', Auth::user()->id)
                ->where('pedidos.condicion', Pedido: EN_PROCESO_ATENCION)
                ->groupBy(
                    'pedidos.id',
                    'c.nombre',
                    'c.celular',
                    'u.name',
                    'dp.codigo',
                    'dp.nombre_empresa',
                    'dp.total',
                    'pedidos.condicion',
                    'pedidos.created_at',
                    'dp.envio_doc',
                    'dp.fecha_envio_doc',
                    'dp.cant_compro',
                    'dp.fecha_envio_doc_fis',
                    'dp.fecha_recepcion'
                )
                ->orderBy('pedidos.created_at', 'DESC')
                ->get();
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


            $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
                ->join('users as u', 'pedidos.user_id', 'u.id')
                ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
                ->select(
                    'pedidos.id',
                    'c.nombre as nombres',
                    'c.celular as celulares',
                    'u.name as users',
                    'dp.codigo as codigos',
                    'dp.nombre_empresa as empresas',
                    /* DB::raw('sum(dp.total) as total'), */
                    'dp.total as total',
                    'pedidos.condicion',
                    /* 'pedidos.created_at as fecha', */
                    DB::raw('DATE_FORMAT(pedidos.created_at, "%d/%m/%Y") as fecha'),
                    'dp.envio_doc',
                    'dp.fecha_envio_doc',
                    'dp.cant_compro',
                    'dp.fecha_envio_doc_fis',
                    'dp.fecha_recepcion'
                )
                ->where('pedidos.estado', '1')
                ->where('dp.estado', '1')
                ->WhereIn('u.identificador', $asesores)
                //->where('u.jefe', Auth::user()->id)
                ->where('pedidos.condicion', Pedido::EN_PROCESO_ATENCION)
                ->groupBy(
                    'pedidos.id',
                    'c.nombre',
                    'c.celular',
                    'u.name',
                    'dp.codigo',
                    'dp.nombre_empresa',
                    'dp.total',
                    'pedidos.condicion',
                    'pedidos.created_at',
                    'dp.envio_doc',
                    'dp.fecha_envio_doc',
                    'dp.cant_compro',
                    'dp.fecha_envio_doc_fis',
                    'dp.fecha_recepcion'
                )
                ->orderBy('pedidos.created_at', 'DESC')
                ->get();
        } else {
            $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
                ->join('users as u', 'pedidos.user_id', 'u.id')
                ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
                ->select(
                    'pedidos.id',
                    'c.nombre as nombres',
                    'c.celular as celulares',
                    'u.name as users',
                    'dp.codigo as codigos',
                    'dp.nombre_empresa as empresas',
                    /* DB::raw('sum(dp.total) as total'), */
                    'dp.total as total',
                    'pedidos.condicion',
                    /* 'pedidos.created_at as fecha', */
                    DB::raw('DATE_FORMAT(pedidos.created_at, "%d/%m/%Y") as fecha'),
                    'dp.envio_doc',
                    'dp.fecha_envio_doc',
                    'dp.cant_compro',
                    'dp.fecha_envio_doc_fis',
                    'dp.fecha_recepcion'
                )
                ->where('pedidos.estado', '1')
                ->where('dp.estado', '1')
                ->where('pedidos.condicion', Pedido::EN_PROCESO_ATENCION)
                ->groupBy(
                    'pedidos.id',
                    'c.nombre',
                    'c.celular',
                    'u.name',
                    'dp.codigo',
                    'dp.nombre_empresa',
                    'dp.total',
                    'pedidos.condicion',
                    'pedidos.created_at',
                    'dp.envio_doc',
                    'dp.fecha_envio_doc',
                    'dp.cant_compro',
                    'dp.fecha_envio_doc_fis',
                    'dp.fecha_recepcion'
                )
                ->orderBy('pedidos.created_at', 'DESC')
                ->get();
        }

        $imagenes = ImagenAtencion::get();
        $superasesor = User::where('rol', 'Super asesor')->count();

        return view('pedidos.enAtencion', compact('dateMin', 'dateMax', 'pedidos', 'condiciones', 'imagenes', 'superasesor'));
    }


    public function cargarAtendidos(Request $request)//pedidoscliente
    {
        if (Auth::user()->rol == "Operario") {
            $pedidos = Pedido::join('users as u', 'pedidos.user_id', 'u.id')
                ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
                ->select(
                    'pedidos.id as id',
                    'u.name as users',
                    'dp.codigo as codigos',
                    'dp.nombre_empresa as empresas',
                    'pedidos.condicion',
                    DB::raw('DATE_FORMAT(pedidos.created_at, "%d/%m/%Y") as fecha'),
                    'pedidos.envio',
                    'pedidos.condicion_envio',
                    'dp.envio_doc',
                    'dp.fecha_envio_doc',
                    'dp.cant_compro',
                    'dp.fecha_envio_doc_fis',
                    'dp.fecha_recepcion'
                )
                ->where('pedidos.estado', '1')
                ->where('dp.estado', '1')
                ->where('u.operario', Auth::user()->id)
                ->where('pedidos.condicion', Pedido::ATENDIDO)
                ->groupBy(
                    'pedidos.id',
                    'u.name',
                    'dp.codigo',
                    'dp.nombre_empresa',
                    'pedidos.condicion',
                    'pedidos.created_at',
                    'pedidos.envio',
                    'pedidos.condicion_envio',
                    'dp.envio_doc',
                    'dp.fecha_envio_doc',
                    'dp.cant_compro',
                    'dp.fecha_envio_doc_fis',
                    'dp.fecha_recepcion'
                )
                ->orderBy('pedidos.created_at', 'DESC')
                ->get();
        } else if (Auth::user()->rol == "Jefe de operaciones") {
            $pedidos = Pedido::join('users as u', 'pedidos.user_id', 'u.id')
                ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
                ->select(
                    'pedidos.id as id',
                    'u.name as users',
                    'dp.codigo as codigos',
                    'dp.nombre_empresa as empresas',
                    'pedidos.condicion',
                    DB::raw('DATE_FORMAT(pedidos.created_at, "%d/%m/%Y") as fecha'),
                    'pedidos.envio',
                    'pedidos.condicion_envio',
                    'dp.envio_doc',
                    'dp.fecha_envio_doc',
                    'dp.cant_compro',
                    'dp.fecha_envio_doc_fis',
                    'dp.fecha_recepcion'
                )
                ->where('pedidos.estado', '1')
                ->where('dp.estado', '1')
                ->where('u.jefe', Auth::user()->id)
                ->where('pedidos.condicion', Pedido::ATENDIDO)
                ->groupBy(
                    'pedidos.id',
                    'u.name',
                    'dp.codigo',
                    'dp.nombre_empresa',
                    'pedidos.condicion',
                    'pedidos.created_at',
                    'pedidos.envio',
                    'pedidos.condicion_envio',
                    'dp.envio_doc',
                    'dp.fecha_envio_doc',
                    'dp.cant_compro',
                    'dp.fecha_envio_doc_fis',
                    'dp.fecha_recepcion'
                )
                ->orderBy('pedidos.created_at', 'DESC')
                ->get();
        } else {
            $pedidos = Pedido::join('users as u', 'pedidos.user_id', 'u.id')
                ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
                ->select(
                    'pedidos.id as id',
                    'u.name as users',
                    'dp.codigo as codigos',
                    'dp.nombre_empresa as empresas',
                    'pedidos.condicion as estado',
                    DB::raw('DATE_FORMAT(pedidos.created_at, "%d/%m/%Y") as fecha'),
                    'pedidos.envio',
                    'pedidos.condicion_envio',
                    'dp.envio_doc',
                    'dp.fecha_envio_doc',
                    'dp.cant_compro',
                    'dp.fecha_envio_doc_fis',
                    'dp.fecha_recepcion'
                )
                ->where('pedidos.estado', '1')
                ->where('dp.estado', '1')
                ->where('pedidos.condicion', Pedido::ATENDIDO)
                ->groupBy(
                    'pedidos.id',
                    'u.name',
                    'dp.codigo',
                    'dp.nombre_empresa',
                    'pedidos.condicion',
                    'pedidos.created_at',
                    'pedidos.envio',
                    'pedidos.condicion_envio',
                    'dp.envio_doc',
                    'dp.fecha_envio_doc',
                    'dp.cant_compro',
                    'dp.fecha_envio_doc_fis',
                    'dp.fecha_recepcion'
                )
                ->orderBy('pedidos.created_at', 'DESC')
                ->get();
        }

        //return datatables($pedidos)->toJson();
    }


    public function Atender(Request $request, Pedido $pedido)
    {
        $detalle_pedidos = DetallePedido::where('pedido_id', $pedido->id)->first();
        $fecha = Carbon::now();

        $pedido->update([
            'condicion' => $request->condicion,
            'modificador' => 'USER' . Auth::user()->id
        ]);

        if ($request->condicion == "3") {
            $pedido->update([
                'notificacion' => 'Pedido atendido'
            ]);

            event(new PedidoAtendidoEvent($pedido));
        }

        /* $files = $request->file('envio_doc'); */
        /* $destinationPath = base_path('public/storage/adjuntos/'); */

        $files = $request->file('adjunto');
        $destinationPath = base_path('public/storage/adjuntos/');

        $cont = 0;

        if (isset($files)) {
            foreach ($files as $file) {
                $file_name = Carbon::now()->second . $file->getClientOriginalName();
                $file->move($destinationPath, $file_name);

                ImagenAtencion::create([
                    'pedido_id' => $pedido->id,
                    'adjunto' => $file_name,
                    'estado' => '1'
                ]);

                $cont++;
            }
        }

        $detalle_pedidos->update([
            'envio_doc' => '1',
            'fecha_envio_doc' => $fecha,
            'cant_compro' => $request->cant_compro,
            'atendido_por' => Auth::user()->name,
            'atendido_por_id' => Auth::user()->id,
        ]);

        /* if ($request->hasFile('envio_doc')){
            $file_name = Carbon::now()->second.$files->getClientOriginalName();
            $files->move($destinationPath , $file_name);

            $detalle_pedidos->update([
                'envio_doc' => $file_name,
                'fecha_envio_doc' => $fecha,
                'cant_compro' => $request->cant_compro,
            ]);
        }
        else{
            $detalle_pedidos->update([
                'cant_compro' => $request->cant_compro,
            ]);
        } */

        return redirect()->route('operaciones.poratender')->with('info', 'actualizado');
    }


    public function Enviar(Request $request, Pedido $pedido)
    {
        $detalle_pedidos = DetallePedido::where('pedido_id', $pedido->id)->first();
        $fecha = Carbon::now();

        $pedido->update([
            'envio' => '1',
            'modificador' => 'USER' . Auth::user()->id
        ]);

        $detalle_pedidos->update([
            'fecha_envio_doc_fis' => $fecha,
        ]);

        return redirect()->route('operaciones.atendidos')->with('info', 'actualizado');
    }


    public function Destino(Request $request, Pedido $pedido)
    {
        $pedido->update([
            'destino' => $request->destino,
            'modificador' => 'USER' . Auth::user()->id
        ]);

        return redirect()->route('envios.index')->with('info', 'actualizado');
    }

    public function SinEnviar(Pedido $pedido)
    {
        $detalle_pedidos = DetallePedido::where('pedido_id', $pedido->id)->first();
        $fecha = Carbon::now();

        $pedido->update([
            'envio' => '3',//SIN ENVIO
            'condicion_envio' => 3,
            'modificador' => 'USER' . Auth::user()->id
        ]);

        $detalle_pedidos->update([
            'fecha_envio_doc_fis' => $fecha,
            'fecha_recepcion' => $fecha,
            'atendido_por' => Auth::user()->name,
            'atendido_por_id' => Auth::user()->id,
        ]);

        return redirect()->route('operaciones.atendidos')->with('info', 'actualizado');
    }


    public function DescargarAdjunto($adjunto)
    {
        $destinationPath = base_path("public/storage/adjuntos/" . $adjunto);
        /* $destinationPath = storage_path("app/public/adjuntos/".$pedido->adjunto); */

        return response()->download($destinationPath);
    }

    public function DescargarGastos($adjunto)
    {
        $destinationPath = base_path("public/storage/gastos/" . $adjunto);

        return response()->download($destinationPath);
    }

    public function changeImg(Request $request)
    {
        $item = $request->item;
        $pedido = $request->pedido;
        $file = $request->file('adjunto');

        if (isset($file)) {
            $destinationPath = base_path('public/storage/entregas/');
            $cont = 0;
            $file_name = Carbon::now()->second . $file->getClientOriginalName();
            $fileList[$cont] = array(
                'file_name' => $file_name,
            );
            $file->move($destinationPath, $file_name);
            $html = $file_name;

            DetallePedido::where('pedido_id', $pedido)
                ->update([
                    'foto' . $item => $file_name
                ]);
        } else {
            $html = "";
        }

        return response()->json(['html' => $html]);
    }


    public function Recibir(Pedido $pedido)
    {
        $pedido->update([
            'envio' => '2',
            'modificador' => 'USER' . Auth::user()->id
        ]);

        return redirect()->route('envios.index')->with('info', 'actualizado');
    }


    public function EnviarPedido(Request $request, Pedido $pedido)//'notificacion' => 'Nuevo pedido creado'
    {
        $detalle_pedidos = DetallePedido::where('pedido_id', $pedido->id)->first();

        $pedido->update([
            'condicion_envio' => $request->condicion,
            'trecking' => $request->trecking,
            'modificador' => 'USER' . Auth::user()->id
        ]);

        if ($request->condicion == "3") {
            $pedido->update([
                'notificacion' => 'Pedido entregado'
            ]);

            event(new PedidoEntregadoEvent($pedido));
        }

        $files = $request->file('foto1');
        $files2 = $request->file('foto2');

        $destinationPath = base_path('public/storage/entregas/');

        if ($request->hasFile('foto1') && $request->hasFile('foto2')) {
            $file_name = Carbon::now()->second . $files->getClientOriginalName();
            $file_name2 = Carbon::now()->second . $files2->getClientOriginalName();

            $files->move($destinationPath, $file_name);
            $files2->move($destinationPath, $file_name2);

            $detalle_pedidos->update([
                'foto1' => $file_name,
                'foto2' => $file_name2,
                'fecha_recepcion' => $request->fecha_recepcion,
                'atendido_por' => Auth::user()->name,
                'atendido_por_id' => Auth::user()->id,
            ]);
        } else if ($request->hasFile('foto1') && $request->foto2 == null) {
            $file_name = Carbon::now()->second . $files->getClientOriginalName();
            $files->move($destinationPath, $file_name);

            $detalle_pedidos->update([
                'foto1' => $file_name,
                'fecha_recepcion' => $request->fecha_recepcion,
                'atendido_por' => Auth::user()->name,
                'atendido_por_id' => Auth::user()->id,
            ]);
        } else if ($request->foto1 == null && $request->hasFile('foto2')) {
            $file_name2 = Carbon::now()->second . $files2->getClientOriginalName();
            $files2->move($destinationPath, $file_name2);

            $detalle_pedidos->update([
                'foto2' => $file_name2,
                'fecha_recepcion' => $request->fecha_recepcion,
                'atendido_por' => Auth::user()->name,
                'atendido_por_id' => Auth::user()->id,
            ]);
        } else {
            $detalle_pedidos->update([
                'fecha_recepcion' => $request->fecha_recepcion,
                'atendido_por' => Auth::user()->name,
                'atendido_por_id' => Auth::user()->id,
            ]);
        }

        if ($request->vista == 'ENTREGADOS') {
            return redirect()->route('envios.enviados')->with('info', 'actualizado');
        }

        return redirect()->route('envios.index')->with('info', 'actualizado');
    }

    public function DescargarImagen($imagen)
    {
        $destinationPath = base_path("public/storage/entregas/" . $imagen);

        return response()->download($destinationPath);
    }

    public function eliminarFoto1(Pedido $pedido)
    {
        $detallepedido = DetallePedido::find($pedido->id);
        $detallepedido->update([
            'foto1' => null
        ]);
        return redirect()->route('envios.enviados')->with('info', 'actualizado');
    }

    public function eliminarFoto2(Pedido $pedido)
    {
        $detallepedido = DetallePedido::find($pedido->id);
        $detallepedido->update([
            'foto2' => null
        ]);
        return redirect()->route('envios.enviados')->with('info', 'actualizado');
    }

    public function validadContenidoPedido(Request $request)
    {
        $pedidos_repetidos = Pedido::join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->join('users as u', 'pedidos.user_id', 'u.id')
            ->select(
                'pedidos.id',
                'u.identificador',
                'pedidos.user_id',
                'pedidos.cliente_id',
                'pedidos.codigo',
                'pedidos.condicion_code',
                'dp.mes',
                'dp.anio',
                'dp.ruc',
                'dp.nombre_empresa',
                'dp.cantidad'
            //'dp.tipo_banca',
            //'dp.porcentaje',
            //'dp.courier',

            )
            ->where('u.identificador', $request->asesor)
            ->where('pedidos.cliente_id', $request->cliente)
            ->where('dp.mes', $request->mes)
            ->where('dp.anio', $request->ano)
            ->where('dp.cantidad', $request->cantidad)
            //->where('dp.tipo_banca', $request->banca)
            //->where('dp.porcentaje', $request->porcentaje)
            //->where('dp.courier', $request->courier)
            ->where('dp.ruc', $request->ruc)
            ->where('dp.nombre_empresa', $request->nombre_empresa)
            ->whereIn('pedidos.id',
                DetallePedido::query()
                    ->select('detalle_pedidos.pedido_id')
                    ->whereRaw('pedidos.id=detalle_pedidos.pedido_id')
                    ->where('detalle_pedidos.tipo_banca', '=', $request->ptipo_banca)
                    ->getQuery()
            )
            ->limit(5)
            ->get();

        return response()->json([
            'is_repetido' => $pedidos_repetidos->count() > 0,
            'coincidencia' => $pedidos_repetidos,
            'codigos' => $pedidos_repetidos->map(function (Pedido $p) {
                if ($p->condicion_code == 4) {
                    return "<span class='text-danger'>" . $p->codigo . "</span>";
                } else {
                    return "<span class='text-dark'>" . $p->codigo . "</span>";
                }
            })->join(', '),
        ]);
    }

    public function ConfirmarAnular(Request $request)
    {
        if ($request->get('action') == 'confirm_anulled_cancel') {
            $pedido = Pedido::findOrFail($request->pedido_id);
            if ($pedido->pendiente_anulacion != '1') {
                return response()->json([
                    "success" => 0,
                ]);
            }
            $pedido->update([
                'pendiente_anulacion' => '0',
                'user_anulacion_id' => \auth()->id(),
                'fecha_anulacion_denegada' => now(),
            ]);
            return response()->json([
                "success" => 1
            ]);
        }
        $this->validate($request, [
            'pedido_id' => 'required|integer',
            'attachments' => 'array',
            'attachments.*' => 'required|file',
        ]);
        $pedido = Pedido::findOrFail($request->pedido_id);
        if ($pedido->pendiente_anulacion != '1') {
            return response()->json([
                "success" => 0,
            ]);
        }
        $filePaths = [];
        $files = $request->attachments;
        if (is_array($files)) {
            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    $filePaths[] = $file->store("pedidos_notacredito", "pstorage");
                }
            }
        }
        $pedido->update([
            'condicion' => 'ANULADO',
            'condicion_code' => Pedido::ANULADO_INT,
            'user_anulacion_id' => Auth::user()->id,
            'fecha_anulacion_confirm' => now(),
            'estado' => '0',
            'pendiente_anulacion' => '0',
        ]);
        setting()->load();
        foreach ($filePaths as $index => $path) {
            $key = "pedido." . $pedido->id . ".nota_credito_file." . $index;
            $keyd = "pedido." . $pedido->id . ".nota_credito_disk." . $index;
            setting([
                $key => $path,
                $keyd => 'pstorage'
            ]);
        }
        setting()->save();
        $detalle_pedidos = DetallePedido::where('pedido_id', $request->pedido_id)->first();

        $detalle_pedidos->update([
            'estado' => '0'
        ]);
        return response()->json([
            "success" => 1
        ]);
    }
}
