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
use App\Models\User;
use App\Models\Pedido;
use App\Models\Porcentaje;
use App\Models\Provincia;
use App\Models\Ruc;
use App\Models\PedidoMovimientoEstado;

use App\Notifications\PedidoNotification;
use Carbon\Carbon;
use Exception;
use Facade\FlareClient\Http\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PDF;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Validator;
use DataTables;

class OperacionController extends Controller
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

    public function PorAtender()
    {
        $dateMin = Carbon::now()->subDays(4)->format('d/m/Y');
        $dateMax = Carbon::now()->format('d/m/Y');

        $condiciones = [
            "POR ATENDER" => Pedido::POR_ATENDER,
            "EN PROCESO ATENCION" => Pedido::EN_PROCESO_ATENCION,
            "ATENDIDO" => Pedido::ATENDIDO
        ];

        PedidoMovimientoEstado::where('condicion_envio_code', Pedido::POR_ATENDER_OPE_INT)->update([
            'notificado' => 1,
        ]);


        $imagenespedido = ImagenPedido::get();
        $imagenes = ImagenAtencion::get();
        $superasesor = User::where('rol', 'Super asesor')->count();

        return view('operaciones.porAtender', compact('dateMin', 'dateMax', 'condiciones', 'imagenespedido', 'imagenes', 'superasesor'));
    }

    public function PorAtendertabla(Request $request)
    {
        $mirol = Auth::user()->rol;

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
                DB::raw('(DATE_FORMAT(pedidos.created_at, "%Y-%m-%d %H:%i:%s")) as fecha'),
                'dp.envio_doc',
                'dp.fecha_envio_doc',
                'dp.cant_compro',
                'dp.fecha_envio_doc_fis',
                'dp.fecha_recepcion',
                'dp.tipo_banca',
                'pedidos.condicion_envio',
                'pedidos.condicion_envio_code',
                'pedidos.estado_sobre',
                DB::raw(" ( select count(ip.id) from imagen_pedidos ip inner join pedidos pedido on pedido.id=ip.pedido_id and pedido.id=pedidos.id where ip.estado=1 and ip.adjunto not in ('logo_facturas.png') ) as imagenes "),
                'pedidos.pendiente_anulacion',
                'pedidos.condicion_code',
                'pedidos.estado',
                'pedidos.estado_ruta'
            )
            ->where('pedidos.estado', '1')
            ->where('dp.estado', '1')
            ->whereIn('pedidos.condicion_envio_code', [Pedido::POR_ATENDER_OPE_INT, Pedido::EN_ATENCION_OPE_INT]);


        if (Auth::user()->rol == "Operario") {

            $asesores = User::whereIN('users.rol', ['Asesor', 'Administrador', 'ASESOR ADMINISTRATIVO'])
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

            $asesores = User::whereIN('users.rol', ['Asesor', 'Administrador', 'ASESOR ADMINISTRATIVO'])
                ->where('users.estado', '1')
                ->WhereIn('users.operario', $operarios)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos = $pedidos->WhereIn('u.identificador', $asesores);
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
                    $badge_estado.='<span class="badge badge-success" style="background-color: #00bc8c !important;
                    padding: 4px 8px !important;
                    font-size: 8px;
                    margin-bottom: -4px;
                    color: black !important;">Con ruta</span>';
                }
                $color = Pedido::getColorByCondicionEnvio($pedido->condicion_envio);
                $badge_estado.= '<span class="badge badge-success" style="background-color: ' . $color . '!important;">' . $pedido->condicion_envio . '</span>';
                return $badge_estado;
            })
            ->addColumn('action', function ($pedido) {
                $btn = '';
                return $btn;
            })
            ->addColumn('action2', function ($pedido) {
                $btn = '';
                return $btn;
            })
            ->rawColumns(['action', 'action2','condicion_envio'])
            ->make(true);

    }

    public function Atendidos()
    {
        $dateMin = Carbon::now()->subDays(4)->format('d/m/Y');
        $dateMax = Carbon::now()->format('d/m/Y');

        $condiciones = [
            "POR ATENDER" => Pedido::POR_ATENDER,
            "EN PROCESO ATENCION" => Pedido::EN_PROCESO_ATENCION,
            "ATENDIDO" => Pedido::ATENDIDO
        ];
        $imagenes = ImagenAtencion::where('estado', '1')->get();
        $superasesor = User::where('rol', 'Super asesor')->count();

        PedidoMovimientoEstado::where('condicion_envio_code', Pedido::ATENDIDO_INT)->update([
            'notificado' => 1,
        ]);


        return view('operaciones.atendidos', compact('dateMin', 'dateMax', 'condiciones', 'superasesor'));//, 'imagenes'
    }

    public function Atendidostabla(Request $request)
    {
        $pedidos = Pedido::join('users as u', 'pedidos.user_id', 'u.id')
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->select([
                'pedidos.id',
                'pedidos.correlativo as id2',
                'u.identificador as users',
                'dp.codigo as codigos',
                'dp.nombre_empresa as empresas',
                'pedidos.condicion',
                'pedidos.condicion_code',
                'pedidos.da_confirmar_descarga',
                DB::raw('(DATE_FORMAT(pedidos.created_at, "%Y-%m-%d %H:%i:%s")) as fecha'),
                'pedidos.envio',
                'pedidos.destino',
                'pedidos.condicion_envio',
                'dp.envio_doc',
                DB::raw('(DATE_FORMAT(dp.fecha_envio_doc, "%Y-%m-%d %H:%i:%s")) as fecha_envio_doc'),
                'dp.cant_compro',
                'dp.atendido_por',
                //'u.jefe',
                DB::raw(" (select u2.name from users u2 where u2.id=u.jefe) as jefe "),
                DB::raw('DATE_FORMAT(dp.fecha_envio_doc_fis, "%d/%m/%Y") as fecha_envio_doc_fis'),
                'dp.fecha_recepcion',
                DB::raw(" (select count(ii.id) from imagen_atencions ii where ii.pedido_id=pedidos.id and ii.estado=1) as adjuntos "),
                'pedidos.pendiente_anulacion',
                'pedidos.estado',
                'pedidos.estado_sobre',
                'pedidos.estado_ruta',
            ])->activo()
            ->where('pedidos.condicion_envio_code', Pedido::ATENDIDO_OPE_INT);

        if (Auth::user()->rol == "Operario") {

            $asesores = User::whereIN('users.rol', ['Asesor', 'Administrador', 'ASESOR ADMINISTRATIVO'])
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

            $asesores = User::whereIN('users.rol', ['Asesor', 'Administrador', 'ASESOR ADMINISTRATIVO'])
                ->where('users.estado', '1')
                ->WhereIn('users.operario', $operarios)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $pedidos = $pedidos->WhereIn('u.identificador', $asesores);


        }

        return Datatables::of(DB::table($pedidos))
            ->addIndexColumn()
            ->editColumn('condicion_envio', function ($pedido) {
                $badge_estado='';
                $color = Pedido::getColorByCondicionEnvio($pedido->condicion_envio);
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
                    $badge_estado .= '<span class="badge badge-dark p-8"
                        style="color: #fff; background-color: #347cc4; font-weight: 600;
                        margin-bottom: -2px;border-radius: 4px 4px 0px 0px; font-size:8px;
                        padding: 4px 4px !important; font-weight: 500;">Direccion agregada</span>';

                }
                if($pedido->estado_ruta=='1')
                {
                    $badge_estado.='<span class="badge badge-success" style="background-color: #00bc8c !important;
                    padding: 4px 8px !important;
                    font-size: 8px;
                    margin-bottom: -4px;
                    color: black !important;">Con ruta</span>';
                }

                $badge_estado.= '<span class="badge badge-success" style="background-color: ' . $color . '!important;">' . $pedido->condicion_envio . '</span>';

                return $badge_estado;
            })
            ->addColumn('action', function ($pedido) {
                $btn = [];
                $btn[] = '<div class="row">';

                $btn[] = '<div class="col-6 d-flex justify-content-start text-left m-0 p-0">';
                $btn[] = '<ul class="text-left list-inline text-left" aria-labelledby="dropdownMenuButton" >';
                //$btn[] = '<a href="' . route("operaciones.showatender", $pedido->id) . '" class="btn-sm dropdown-item" ><i class="fas fa-eye text-success"></i> Ver</a>';
                if (\auth()->user()->can('operacion.editatender')) {
                    $btn[] = '<a href="" class="btn-sm dropdown-item" data-target="#modal-editar-atencion" data-adj=' . $pedido->da_confirmar_descarga . ' data-atencion=' . $pedido->id . ' data-toggle="modal" >
                                <i class="fa fa-paperclip text-primary" aria-hidden="true"></i>
                                Editar Adjuntos
                               </a>';
                }
                $btn[] = '<a href="" class="btn-sm dropdown-item" data-target="#modal-veradjuntos-atencion" data-adj=' . $pedido->da_confirmar_descarga . ' data-veradjuntos=' . $pedido->id . ' data-toggle="modal" >
                                <i class="fa fa-paperclip text-primary" aria-hidden="true"></i>
                                Ver Adjuntos
                               </a>';
                if (\auth()->user()->can('operacion.PDF')) {
                    $btn[] = '<a href="' . route("pedidosPDF", $pedido->id) . '" class="btn-sm dropdown-item" target="_blank"><i class="fa fa-file-pdf text-warning"></i> PDF</a>';
                }
                $btn[] = '</ul>';
                $btn[] = '</div>';

                $btn[] = '<div class="col-6 d-flex justify-content-start text-left m-0 p-0">';
                $btn[] = '<ul class="list-group text-left">';
                if (\auth()->user()->can('operacion.enviar')) {
                    if (Auth::user()->rol == "Jefe de operaciones" || Auth::user()->rol == "Administrador" || Auth::user()->rol == "Operario") {
                        $btn[] = '<a href="" class="btn-sm dropdown-item" data-target="#modal-envio" data-pedido_sobre_text="CON SOBRE" data-envio=' . $pedido->id . ' data-codigo=' . $pedido->codigos . ' data-toggle="modal" ><i class="fa fa-envelope text-success" aria-hidden="true"></i>Envio con sobre</a>';
                        $btn[] = '<a href="" class="btn-sm dropdown-item" data-target="#modal-sinenvio" data-pedido_sobre_text="SIN SOBRE" data-sinenvio=' . $pedido->id . ' data-codigo=' . $pedido->codigos . ' data-toggle="modal" ><i class="fa fa-times text-danger" aria-hidden="true"></i>Envio sin sobre</a>';
                    }
                }
                if (\auth()->user()->can('operacion.atendidos.revertir')) {
                    if (\Str::contains(\Str::lower($pedido->condicion_envio), 'courier')) {
                        $btn[] = '<button data-toggle="tooltip" data-placement="top" title="El sobre ya ah sido recivido en courier, solo el courier tiene permiso de revertir" class="btn-sm dropdown-item" disabled><i class="fa fa-undo text-danger" aria-hidden="true"></i> Revertir a por atender</button>';
                    } else {
                        $btn[] = '<a href="" class="btn-sm dropdown-item" data-target="#modal-revertir-poratender" data-adjuntos="' . $pedido->adjuntos . '" data-revertir=' . $pedido->id . ' data-codigo=' . $pedido->codigos . ' data-toggle="modal" ><i class="fa fa-undo text-danger" aria-hidden="true"></i> Revertir a por atender</a>';
                    }
                }
                $btn[] = '</ul>';
                $btn[] = '</div>';
                $btn[] = '</div>';

                return join('', $btn);
            })
            ->addColumn('condicion_envio_color', function ($pedido) {
                return Pedido::getColorByCondicionEnvio($pedido->condicion_envio);
            })
            ->rawColumns(['action','condicion_envio'])
            ->make(true);
    }

    public function Entregados()
    {
        $dateMin = Carbon::now()->subDays(4)->format('d/m/Y');
        $dateMax = Carbon::now()->format('d/m/Y');

        $condiciones = [
            "POR ATENDER" => Pedido::POR_ATENDER,
            "EN PROCESO ATENCION" => Pedido::EN_PROCESO_ATENCION,
            "ATENDIDO" => Pedido::ATENDIDO
        ];

        $imagenes = ImagenAtencion::where('estado', '1')->get();
        $superasesor = User::where('rol', 'Super asesor')->count();

        return view('operaciones.entregados', compact('dateMin', 'dateMax', 'condiciones', 'superasesor'));//, 'imagenes'
    }

    public function Entregadostabla(Request $request)
    {
        $min = Carbon::createFromFormat('d/m/Y', $request->min)->format('Y-m-d');
        $max = Carbon::createFromFormat('d/m/Y', $request->max)->format('Y-m-d');
        $pedidos = null;

        $pedidos = Pedido::join('users as u', 'pedidos.user_id', 'u.id')
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->select(
                [
                    'pedidos.*',
                    'u.identificador as users',
                    'dp.codigo as codigos',
                    'dp.nombre_empresa as empresas',
                    DB::raw('(DATE_FORMAT(pedidos.created_at, "%Y-%m-%d %h:%i:%s")) as fecha'),
                    'dp.envio_doc',
                    'dp.fecha_envio_doc',
                    'dp.cant_compro',
                    'dp.atendido_por',
                    'dp.atendido_por_id',
                    DB::raw(" (select u2.name from users u2 where u2.id=u.jefe limit 1) as jefe "),
                    DB::raw(' (select DATE_FORMAT(dp1.fecha_envio_doc_fis, "%d/%m/%Y")  from detalle_pedidos dp1 where dp1.id=dp.id limit 1) as fecha_envio_doc_fis'),
                    'dp.fecha_recepcion',
                    DB::raw("  (select IFNULL(count(b1.pedido_id),0) from direccion_pedidos b1 where b1.pedido_id=pedidos.id limit 1) as envios_lima "),
                    DB::raw("  (select IFNULL(count(b2.pedido_id),0) from gasto_pedidos b2 where b2.pedido_id=pedidos.id limit 1) as envios_provincia "),
                    DB::raw("  (CASE  when ((select IFNULL(count(b1.pedido_id),0) from direccion_pedidos b1 where b1.pedido_id=pedidos.id limit 1)+(select IFNULL(count(b2.pedido_id),0) from gasto_pedidos b2 where b2.pedido_id=pedidos.id limit 1))>0 then '1' else '0' end  )  as revierte "),
                    DB::raw("  (CASE  when pedidos.destino='LIMA' then (select gg.created_at from direccion_pedidos gg where gg.pedido_id=pedidos.id limit 1) " .
                        "when pedidos.destino='PROVINCIA' then (select g.created_at from gasto_pedidos g where g.pedido_id=pedidos.id limit 1) " .
                        "else '' end) as fecha_envio_sobre "),
                ]
            )
            ->where('pedidos.estado', '1')
            ->where('dp.estado', '1')
            ->whereIn('pedidos.condicion_envio_code', [Pedido::ENVIADO_OPE_INT, Pedido::ENTREGADO_SIN_SOBRE_OPE_INT, Pedido::RECIBIDO_JEFE_OPE_INT]);

        if (Auth::user()->rol == "Operario") {
            $asesores = User::whereIN('users.rol', ['Asesor', 'Administrador', 'ASESOR ADMINISTRATIVO'])
                ->where('users.estado', '1')
                ->Where('users.operario', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');
            $pedidos->WhereIn('u.identificador', $asesores);
        } else if (Auth::user()->rol == User::ROL_JEFE_OPERARIO) {
            $operarios = User::where('users.rol', 'Operario')
                ->where('users.estado', '1')
                ->where('users.jefe', Auth::user()->id)
                ->select(
                    DB::raw("users.id as id")
                )
                ->pluck('users.id');
            $asesores = User::whereIN('users.rol', [User::ROL_ASESOR, User::ROL_ADMIN,User::ROL_ASESOR_ADMINISTRATIVO])
                ->WhereIn('users.operario', $operarios)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->activo();
            $pedidos->WhereIn('u.identificador', $asesores);
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
                    $badge_estado.='<span class="badge badge-danger">' . Pedido::PENDIENTE_ANULACION.'</span>';
                    return $badge_estado;
                }
                if($pedido->estado=='0')
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
                $badge_estado.= '<span class="badge badge-success" style="background-color: ' . $color . '!important;">' . $pedido->condicion_envio . '</span>';
                return $badge_estado;
            })
            ->addColumn('action', function ($pedido) {
                $btn = [];

                $btn[] = '<div class="row">';
                $btn[] = '<div class="col-12 d-flex justify-content-start text-left m-0 p-0">';
                $btn[] = '<ul class="text-left list-inline text-left" aria-labelledby="dropdownMenuButton" >';

                if (auth()->user()->can('operacion.PDF')):
                    $btn[] = '<a href="'.route("pedidosPDF", $pedido->id).'" class="btn-sm dropdown-item" target="_blank"><i class="fa fa-file-pdf text-primary"></i> PDF</a>';
                endif;

                /*if ( in_array( auth()->user()->rol,[User::ROL_JEFE_OPERARIO,User::ROL_ADMIN]) ):
                    $btn[]='<a href="" data-target="#modal-envio"  class="btn-sm dropdown-item" data-envio='.$pedido->id.' data-toggle="modal" >Enviar</a><br>';
                    $btn[]='<a href="" data-target="#modal-sinenvio"  class="btn-sm dropdown-item" data-sinenvio='.$pedido->id.' data-toggle="modal" ><i class="fa fa-times text-danger" aria-hidden="true"></i> Sin envío</a><br>';
                endif;*/

                if($pedido->condicion_envio_code==5):
                    $btn[]='<li><a href="" data-target="#modal-envio-op" data-group="1" class="btn-sm dropdown-item" data-envio='.$pedido->id.' data-code="'.$pedido->codigos.'" data-toggle="modal" ><i class="fa fa-envelope text-success" aria-hidden="true"></i> Recepcion</a></li>';
                    $btn[]='<li><p data-target="#" class="btn-sm pl-16 text-gray mb-0" data-envio='.$pedido->id.' data-code="'.$pedido->codigos.'" data-toggle="" disabled><i class="fa fa fa-motorcycle text-gray" aria-hidden="true"></i> ENVIO A COURIER</p></li>';
                    $btn[]='<li><a href="" data-target="#modal-revertir" class="btn-sm dropdown-item" data-revertir='.$pedido->id.'  data-codigo='.$pedido->codigo.' data-toggle="modal" ><i class="fa fa-times text-danger" aria-hidden="true"></i> Revertir a LISTO PARA ENVIO</a></li>';
                endif;

                if($pedido->condicion_envio_code==6):
                    $btn[]='<li><p data-target="text-gray" class="btn-sm pl-16 mb-0" data-envio='.$pedido->id.' data-code="'.$pedido->codigos.'" data-toggle="" disabled><i class="fa fa-envelope text-gray " aria-hidden="true"></i> Recepcion</p></li>';
                    $btn[]='<li><a href="" data-target="#modal-envio-op" data-group="2" class="btn-sm dropdown-item " data-envio='.$pedido->id.' data-code="'.$pedido->codigos.'" data-toggle="modal" ><i class="fa fa fa-motorcycle text-success" aria-hidden="true"></i> ENVIO A COURIER</a></li>';
                    $btn[]='<li><a href="" data-target="#modal-revertir" class="btn-sm dropdown-item" data-revertir='.$pedido->id.'  data-codigo='.$pedido->codigo.' data-toggle="modal" ><i class="fa fa-times text-danger" aria-hidden="true"></i> Revertir a LISTO PARA ENVIO</a></li>';
                endif;

                if($pedido->condicion_envio_code == 13):
                    $btn[]='<li><a href="" class="btn-sm dropdown-item" data-target="#modal-envio" data-code="'.$pedido->codigos.'" data-envio='.$pedido->id.' data-toggle="modal" ><i class="fa fa-check text-success" aria-hidden="true"></i> Recepcion</a></li>';
                    $btn[]='<li><a href="" data-target="#modal-revertir" class="btn-sm dropdown-item" data-revertir='.$pedido->id.' data-codigo='.$pedido->codigo.' data-toggle="modal" ><i class="fa fa-times text-danger" aria-hidden="true"></i> Revertir a LISTO PARA ENVIO</a></li>';
                endif;

                $btn[] = '<div class="row">';
                $btn[] = '<div class="col-12 d-flex justify-content-start text-left m-0 p-0">';
                $btn[] = '<ul class="text-left list-inline text-left" aria-labelledby="dropdownMenuButton" >';

                return "<ul class='d-flex'>" . join('', $btn) . "</ul>";
            })
            ->rawColumns(['action','condicion_envio'])
            ->make(true);
    }

    public function Terminados()
    {
        $dateMin = Carbon::now()->subDays(4)->format('d/m/Y');
        $dateMax = Carbon::now()->format('d/m/Y');

        $condiciones = [
            "POR ATENDER" => Pedido::POR_ATENDER,
            "EN PROCESO ATENCION" => Pedido::EN_PROCESO_ATENCION,
            "ATENDIDO" => Pedido::ATENDIDO
        ];

        $imagenes = ImagenAtencion::where('estado', '1')->get();
        $superasesor = User::where('rol', 'Super asesor')->count();

        return view('operaciones.terminados', compact('dateMin', 'dateMax', 'condiciones', 'superasesor'));//, 'imagenes'
    }


    public function Terminadostabla(Request $request)
    {
        $min = Carbon::createFromFormat('d/m/Y', $request->min)->format('Y-m-d');
        $max = Carbon::createFromFormat('d/m/Y', $request->max)->format('Y-m-d');
        $pedidos = null;

        $pedidos = Pedido::join('users as u', 'pedidos.user_id', 'u.id')
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->select(
                [
                    'pedidos.*',
                    'u.identificador as users',
                    'dp.codigo as codigos',
                    'dp.nombre_empresa as empresas',
                    DB::raw('(DATE_FORMAT(pedidos.created_at, "%Y-%m-%d %h:%i:%s")) as fecha'),
                    'dp.envio_doc',
                    'dp.fecha_envio_doc',
                    'dp.cant_compro',
                    'dp.atendido_por',
                    'dp.atendido_por_id',
                    DB::raw(" (select u2.name from users u2 where u2.id=u.jefe limit 1) as jefe "),
                    DB::raw(' (select DATE_FORMAT(dp1.fecha_envio_doc_fis, "%d/%m/%Y")  from detalle_pedidos dp1 where dp1.id=dp.id limit 1) as fecha_envio_doc_fis'),
                    'dp.fecha_recepcion',
                    DB::raw("  (select IFNULL(count(b1.pedido_id),0) from direccion_pedidos b1 where b1.pedido_id=pedidos.id limit 1) as envios_lima "),
                    DB::raw("  (select IFNULL(count(b2.pedido_id),0) from gasto_pedidos b2 where b2.pedido_id=pedidos.id limit 1) as envios_provincia "),
                    DB::raw("  (CASE  when ((select IFNULL(count(b1.pedido_id),0) from direccion_pedidos b1 where b1.pedido_id=pedidos.id limit 1)+(select IFNULL(count(b2.pedido_id),0) from gasto_pedidos b2 where b2.pedido_id=pedidos.id limit 1))>0 then '1' else '0' end  )  as revierte "),
                    DB::raw("  (CASE  when pedidos.destino='LIMA' then (select gg.created_at from direccion_pedidos gg where gg.pedido_id=pedidos.id limit 1) " .
                        "when pedidos.destino='PROVINCIA' then (select g.created_at from gasto_pedidos g where g.pedido_id=pedidos.id limit 1) " .
                        "else '' end) as fecha_envio_sobre "),
                    DB::raw(" (select count(ii.id) from imagen_atencions ii where ii.pedido_id=pedidos.id and ii.estado=1) as adjuntos "),
                ]
            )
            //->where('pedidos.estado', '1')
            //->where('dp.estado', '1')
            ->whereIn('pedidos.condicion_envio_code', [
                Pedido::RECIBIDO_JEFE_OPE_INT,
                Pedido::RECEPCION_COURIER_INT
                , Pedido::REPARTO_COURIER_INT
                , Pedido::RECEPCIONADO_OLVA_INT
                , Pedido::ENTREGADO_CLIENTE_INT
                , Pedido::RECEPCION_COURIER_INT
                , Pedido::ENVIO_COURIER_JEFE_OPE_INT
                , Pedido::ENTREGADO_SIN_SOBRE_OPE_INT
                , Pedido::ENTREGADO_SIN_SOBRE_CLIENTE_INT
                //, Pedido::ENTREGADO_SIN_ENVIO_CLIENTE_INT
                , Pedido::ATENDIDO_OPE_INT
                , Pedido::ENVIADO_OPE_INT
            ]);
        //->whereIn('pedidos.condicion_envio_code', [Pedido::JEFE_OP_CONF_INT],[Pedido::COURIER_INT], [Pedido::EN_REPARTO_INT],[Pedido::SOBRE_ENVIAR_INT])

        //->whereIn('pedidos.envio', ['0'])
        //->whereBetween( 'pedidos.created_at', [$min, $max]);

        if (Auth::user()->rol == "Operario") {

            $asesores = User::whereIN('users.rol', ['Asesor', 'Administrador', 'ASESOR ADMINISTRATIVO'])
                ->where('users.estado', '1')
                ->Where('users.operario', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )/*->union(
                    User::where("id","33")
                        ->select(
                            DB::raw("users.identificador as identificador")
                        ) )*/
                ->pluck('users.identificador');

            $pedidos->WhereIn('u.identificador', $asesores);


        } else if (Auth::user()->rol == "Jefe de operaciones") {
            $operarios = User::where('users.rol', 'Operario')
                ->where('users.estado', '1')
                ->where('users.jefe', Auth::user()->id)
                ->select(
                    DB::raw("users.id as id")
                )
                ->pluck('users.id');

            $asesores = User::whereIN('users.rol', ['Asesor', 'Administrador', 'ASESOR ADMINISTRATIVO'])
                ->where('users.estado', '1')
                ->WhereIn('users.operario', $operarios)
                ->select(
                    DB::raw("users.identificador as identificador")
                )/*->union(
                    User::where("id","33")
                        ->select(
                            DB::raw("users.identificador as identificador")
                        ) )*/
                ->pluck('users.identificador');

            $pedidos->WhereIn('u.identificador', $asesores);


        }
        return Datatables::of(DB::table($pedidos))
            ->addIndexColumn()
            ->addColumn('condicion_envio_color', function ($pedido) {
                return Pedido::getColorByCondicionEnvio($pedido->condicion_envio);
            })
            ->editColumn('condicion_envio', function ($pedido) {
                $badge_estado='';
                if ($pedido->codigo_regularizado == '1') {
                    $badge_estado .= '<span class="badge badge-dark p-8" style="color: #fff; background-color: #347cc4; font-weight: 600; margin-bottom: -2px;border-radius: 4px 4px 0px 0px; font-size:8px;  padding: 4px 4px !important;">REGULARIZACION</span>';

                }
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
                if($pedido->estado_consinsobre=='1')
                {
                    $badge_estado .= '<span class="badge badge-dark p-8" style="color: #fff; background-color: #347cc4; font-weight: 600; margin-bottom: -2px;border-radius: 4px 4px 0px 0px; font-size:8px;  padding: 4px 4px !important; font-weight: 500;">CON SOBRE</span>';

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
                $badge_estado.= '<span class="badge badge-success" style="background-color: ' . $color . '!important;">' . $pedido->condicion_envio . '</span>';
                return $badge_estado;
            })
            ->addColumn('action', function ($pedido) {
                $btn = [];
                //$btn[] = '<a href="' . route("operaciones.showatender", $pedido->id) . '" class="m-1 btn btn-primary btn-sm"><i class="fas fa-eye"></i> Ver</a><br>';
                if (\auth()->user()->can('operacion.PDF')) {
                    $btn[] = '<a href="' . route('pedidosPDF', $pedido->id) . '" class="m-1 btn btn-primary btn-sm" target="_blank"><i class="fa fa-file-pdf"></i> PDF</a><br>';
                }

                if(\auth()->user()->rol==User::ROL_ADMIN || \auth()->user()->rol==User::ROL_JEFE_OPERARIO)
                if($pedido->condicion_envio_code==Pedido::ENTREGADO_SIN_ENVIO_CLIENTE_INT){
                    $btn[] = '<a href="" class="btn-sm dropdown-item" data-target="#modal-revertir-asindireccion" data-adjuntos="' . $pedido->adjuntos . '" data-revertir=' . $pedido->id . ' data-codigo=' . $pedido->codigos . ' data-toggle="modal" ><i class="fa fa-undo text-danger" aria-hidden="true"></i> Revertir a Sobres sin Direccion</a>';
                }

                if(\auth()->user()->rol==User::ROL_ADMIN || \auth()->user()->rol==User::ROL_JEFE_OPERARIO)
                if($pedido->condicion_envio_code==Pedido::ENTREGADO_SIN_SOBRE_CLIENTE_INT){
                    $btn[] = '<a href="" class="btn-sm dropdown-item" data-target="#modal-revertir-ajefeop" data-adjuntos="' . $pedido->adjuntos . '" data-revertir=' . $pedido->id . ' data-codigo=' . $pedido->codigos . ' data-toggle="modal" ><i class="fa fa-undo text-danger" aria-hidden="true"></i> Revertir a Jefe de Operaciones</a>';
                }

                if($pedido->condicion_envio_code==Pedido::ENVIO_COURIER_JEFE_OPE_INT    ) {
                    $btn[] = '<a class="btn btn-success btn-sm" href="" data-target="#modal-revertir-ajefeop" data-revertir="' . $pedido->id . '" data-codigo="' . $pedido->codigo . '" data-toggle="modal" >Revertir</a>';
                    //$btn[] = '<button data-toggle="tooltip" data-placement="top" title="El sobre ya ah sido recivido en currier,  solo el currier tiene permiso de revertir" disabled class="btn btn-disabled btn-success btn-sm" data-target="#modal-revertir" data-revertir="' . $pedido->id . '" data-codigo="' . $pedido->codigo . '" data-toggle="modal" >Revertir</button>';
                }

                if(in_array($pedido->condicion_envio_code,[Pedido::RECIBIDO_JEFE_OPE_INT,Pedido::ENVIADO_OPE_INT]))
                {
                    //$btn[] = '<a href="#" data-backdrop="static" data-keyboard="false" class="btn-sm dropdown-item" data-target="#modal-correccion-op" data-adjuntos="' . $pedido->adjuntos . '" data-correccion=' . $pedido->id . ' data-codigo=' . $pedido->codigos . ' data-toggle="modal" ><i class="fa fa-deaf"></i> Correccion</a>';
                }

                /*if(\auth()->user()->can('operacion.enviar')){
                    if (Auth::user()->rol == "Jefe de operaciones" || Auth::user()->rol == "Administrador") {
                        $btn[] = '<a class="btn btn-success btn-sm" href="" data-target="#modal-envio" data-envio=' . $pedido->id . ' data-toggle="modal" >Enviar</a><br>';
                        $btn[] = '<a class="btn btn-dark btn-sm" href="" data-target="#modal-sinenvio" data-sinenvio="' . $pedido->id . '" data-toggle="modal" >Sin envío</a><br>';
                    }
                }*/
                //\Str::contains(\Str::lower($pedido->condicion_envio),'courier')

                return "<div class='d-flex'>" . join('', $btn) . "</div>";
            })
            ->rawColumns(['action','condicion_envio'])
            ->make(true);
    }


    public function Bancarizacion()
    {
        $dateMin = Carbon::now()->subDays(4)->format('d/m/Y');
        $dateMax = Carbon::now()->format('d/m/Y');

        $condiciones = [
            "POR ATENDER" => Pedido::POR_ATENDER,
            "EN PROCESO ATENCION" => Pedido::EN_PROCESO_ATENCION,
            "ATENDIDO" => Pedido::ATENDIDO
        ];

        $imagenes = ImagenAtencion::where('estado', '1')->get();
        $superasesor = User::where('rol', 'Super asesor')->count();

        return view('operaciones.bancarizacion', compact('dateMin', 'dateMax', 'condiciones', 'superasesor'));//, 'imagenes'
    }

    public function Bancarizaciontabla(Request $request)
    {
        $min = Carbon::createFromFormat('d/m/Y', $request->min)->format('Y-m-d');
        $max = Carbon::createFromFormat('d/m/Y', $request->max)->format('Y-m-d');
        $pedidos = null;

        $pedidos = Pedido::join('users as u', 'pedidos.user_id', 'u.id')
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->select(
                'pedidos.id',
                'u.identificador as users',
                'dp.codigo as codigos',
                'dp.nombre_empresa as empresas',
                'pedidos.condicion_envio as condicion',
                //DB::raw('DATE_FORMAT(pedidos.created_at, "%d/%m/%Y") as fecha'),
                DB::raw('(DATE_FORMAT(pedidos.created_at, "%Y-%m-%d %h:%i:%s")) as fecha'),
                'pedidos.envio',
                'pedidos.condicion_envio_code',
                'pedidos.destino',
                'pedidos.condicion_envio',
                'dp.envio_doc',
                'dp.fecha_envio_doc',
                'dp.cant_compro',
                'dp.atendido_por',
                'dp.atendido_por_id',
                DB::raw(" (select u2.name from users u2 where u2.id=u.jefe limit 1) as jefe "),
                DB::raw(' (select DATE_FORMAT(dp1.fecha_envio_doc_fis, "%d/%m/%Y")  from detalle_pedidos dp1 where dp1.id=dp.id limit 1) as fecha_envio_doc_fis'),
                'dp.fecha_recepcion',
                DB::raw("  (select IFNULL(count(b1.pedido_id),0) from direccion_pedidos b1 where b1.pedido_id=pedidos.id limit 1) as envios_lima "),
                DB::raw("  (select IFNULL(count(b2.pedido_id),0) from gasto_pedidos b2 where b2.pedido_id=pedidos.id limit 1) as envios_provincia "),
                DB::raw("  (CASE  when ((select IFNULL(count(b1.pedido_id),0) from direccion_pedidos b1 where b1.pedido_id=pedidos.id limit 1)+(select IFNULL(count(b2.pedido_id),0) from gasto_pedidos b2 where b2.pedido_id=pedidos.id limit 1))>0 then '1' else '0' end  )  as revierte "),
                DB::raw("  (CASE  when pedidos.destino='LIMA' then (select gg.created_at from direccion_pedidos gg where gg.pedido_id=pedidos.id limit 1) " .
                    "when pedidos.destino='PROVINCIA' then (select g.created_at from gasto_pedidos g where g.pedido_id=pedidos.id limit 1) " .
                    "else '' end) as fecha_envio_sobre "),

            )
            ->where('pedidos.estado', '1')
            ->where('dp.estado', '1')
            ->where('pedidos.condicion_code', Pedido::ATENDIDO_INT)
            ->whereIn('pedidos.condicion_envio_code', [Pedido::BANCARIZACION_INT]);
        //->whereIn('pedidos.envio', ['1'])
        //->whereIn('pedidos.envio', ['0'])
        //->whereBetween( DB::raw('DATE(pedidos.created_at)'), [$min, $max]);


        if (Auth::user()->rol == "Operario") {

            $asesores = User::whereIN('users.rol', ['Asesor', 'Administrador'])
                ->where('users.estado', '1')
                ->Where('users.operario', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )/*->union(
                    User::where("id","33")
                        ->select(
                            DB::raw("users.identificador as identificador")
                        ) )*/
                ->pluck('users.identificador');

            $pedidos->WhereIn('u.identificador', $asesores);


        } else if (Auth::user()->rol == "Jefe de operaciones") {
            $operarios = User::where('users.rol', 'Operario')
                ->where('users.estado', '1')
                ->where('users.jefe', Auth::user()->id)
                ->select(
                    DB::raw("users.id as id")
                )
                ->pluck('users.id');

            $asesores = User::whereIN('users.rol', ['Asesor', 'Administrador'])
                ->where('users.estado', '1')
                ->WhereIn('users.operario', $operarios)
                ->select(
                    DB::raw("users.identificador as identificador")
                )/*->union(
                    User::where("id","33")
                        ->select(
                            DB::raw("users.identificador as identificador")
                        ) )*/
                ->pluck('users.identificador');

            $pedidos->WhereIn('u.identificador', $asesores);


        } else {
            $pedidos = $pedidos;
        }
        //$pedidos=$pedidos->get();

        return Datatables::of(DB::table($pedidos))//Datatables::of($pedidos)
        ->addIndexColumn()
            ->addColumn('action', function ($pedido) {
                $btn = '';

                return $btn;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function Atenderiddismiss(Request $request)
    {
        $hiddenAtender = $request->hiddenAtender;
        $pedido = Pedido::where("id", $hiddenAtender)->first();
        $imagenesatencion_ = ImagenAtencion::where("pedido_id", $hiddenAtender)->where("confirm", '0');
        $imagenesatencion_->update([
            'estado' => '0'
        ]);
    }

    public function Corregircerrar(Request $request)
    {
        $hiddenAtender = $request->correccion;
        $pedido = Pedido::where("id", $hiddenAtender)->first();
        $imagenesatencion_ = ImagenAtencion::where("pedido_id", $hiddenAtender)->where("confirm", '0');
        $imagenesatencion_->update([
            'estado' => '0'
        ]);
    }

    public function Atenderid(Request $request)
    {
        $hiddenAtender = $request->hiddenAtender;

        $fecha = Carbon::now();

        $pedido = Pedido::where("id", $hiddenAtender)->first();
        if ($pedido->imagenAtencion()->activo()->count() < 1) {
            abort(402);
        }

        $pedido->update([
            'condicion' => Pedido::$estadosCondicionEnvioCode[$request->condicion],
            'condicion_code' => $request->condicion,
            'condicion_envio' => Pedido::$estadosCondicionEnvioCode[$request->condicion],
            'condicion_envio_code' => $request->condicion,
            'condicion_envio_at'=>now(),
            'sustento_adjunto' => $request->sustento,
            'modificador' => 'USER' . Auth::user()->id,
            'da_confirmar_descarga' => 0,
        ]);

        $pedido->detallePedidos()->activo()->update([
            "cant_compro" => $request->cant_compro
        ]);


        PedidoMovimientoEstado::create([
            'pedido' => $request->hiddenAtender,
            'condicion_envio_code' => $request->condicion,
            'notificado' => 0
        ]);


        if ($request->condicion == "3") {
            $pedido->update([
                'notificacion' => 'Pedido atendido'
            ]);

            event(new PedidoAtendidoEvent($pedido));
        }


        $destinationPath = base_path('public/storage/adjuntos/');

        $cont = 0;

        $pedido->imagenAtencion()
            ->where("confirm", '0')
            ->update([
                'confirm' => '1'
            ]);


        /*if ($request->hasFile('adjunto')) {

            foreach ($files as $file) {
                $file_name = Carbon::now()->second . $file->getClientOriginalName();
                $file->move($destinationPath, $file_name);

                ImagenAtencion::create([
                    'pedido_id' => $pedido->id,
                    'adjunto' => $file_name,
                    'estado' => '1',
                    'confirm' => '1'
                ]);

                $cont++;
            }

            $detalle_pedidos->update([
                'envio_doc' => '1',
                'fecha_envio_doc' => $fecha,
                'cant_compro' => $request->cant_compro,
                'atendido_por' => Auth::user()->name,
                'atendido_por_id' => Auth::user()->id,
            ]);

        } else {
            $detalle_pedidos->update([
                'cant_compro' => $request->cant_compro,
                'atendido_por' => Auth::user()->name,
                'atendido_por_id' => Auth::user()->id,
            ]);
        }*/


        /*if(isset($files)){
            foreach ($files as $file){
                $file_name = Carbon::now()->second.$file->getClientOriginalName();
                $file->move($destinationPath , $file_name);

                ImagenAtencion::create([
                    'pedido_id' => $pedido->id,
                    'adjunto' => $file_name,
                    'estado' => '1'
                ]);

                $cont++;
            }
        }*/


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

    public function CorreccionAccion(Request $request)
    {
        $hiddenAtender = $request->correccion;

        $fecha = Carbon::now();

        $pedido = Pedido::where("id", $hiddenAtender)->first();
        if ($pedido->imagenAtencion()->activo()->count() < 1) {
            abort(402);
        }

        $pedido->update([
            'condicion' => Pedido::$estadosCondicionEnvioCode[$request->condicion],
            'condicion_code' => $request->condicion,
            'condicion_envio' => Pedido::$estadosCondicionEnvioCode[$request->condicion],
            'condicion_envio_code' => $request->condicion,
            'condicion_envio_at'=>now(),
            'sustento_adjunto' => $request->sustento,
            'modificador' => 'USER' . Auth::user()->id,
            'da_confirmar_descarga' => 0,
        ]);

        $pedido->detallePedidos()->activo()->update([
            "cant_compro" => $request->cant_compro
        ]);

        PedidoMovimientoEstado::create([
            'pedido' => $request->correccion,
            'condicion_envio_code' => $request->condicion,
            'notificado' => 0
        ]);

        /*if ($request->condicion == "3") {
            $pedido->update([
                'notificacion' => 'Pedido atendido'
            ]);

            event(new PedidoAtendidoEvent($pedido));
        }*/


        $destinationPath = base_path('public/storage/adjuntos/');

        $cont = 0;

        $pedido->imagenAtencion()
            ->where("confirm", '0')
            ->update([
                'confirm' => '1'
            ]);

        return redirect()->route('operaciones.poratender')->with('info', 'actualizado');
    }

    public function editAtender(Pedido $pedido)
    {
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
                'pedidos.envio',
                'pedidos.condicion_envio',
                'dp.envio_doc',
                'dp.fecha_envio_doc',
                'dp.cant_compro',
                'dp.fecha_envio_doc_fis',
                'dp.fecha_recepcion',
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
                'pedidos.envio',
                'pedidos.condicion_envio',
                'dp.envio_doc',
                'dp.fecha_envio_doc',
                'dp.cant_compro',
                'dp.fecha_envio_doc_fis',
                'dp.fecha_recepcion',
                'pedidos.created_at'
            )
            ->orderBy('pedidos.created_at', 'DESC')
            ->get();

        $imagenespedido = ImagenPedido::where('imagen_pedidos.pedido_id', $pedido->id)->where('estado', '1')->get();
        $imagenes = ImagenAtencion::where('imagen_atencions.pedido_id', $pedido->id)->where('estado', '1')->get();

        return view('operaciones.editatender', compact('pedido', 'pedidos', 'imagenespedido', 'imagenes'));
    }


    public function editAtencion(Pedido $pedido)
    {

        //dd('editando pedido: ' . $pedido);
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
                'pedidos.envio',
                'pedidos.condicion_envio',
                'dp.envio_doc',
                'dp.fecha_envio_doc',
                'dp.cant_compro',
                'dp.fecha_envio_doc_fis',
                'dp.fecha_recepcion',
                'pedidos.created_at as fecha'
            )
            ->where('pedidos.estado', '1')
            ->where('pedidos.id', $pedido->id)
            ->where('dp.estado', '1')
            ->orderBy('pedidos.created_at', 'DESC')
            ->get();

        $imagenespedido = ImagenPedido::where('imagen_pedidos.pedido_id', $pedido->id)->where('estado', '1')->get();
        $imagenes = ImagenAtencion::where('imagen_atencions.pedido_id', $pedido->id)->where('estado', '1')->where('confirm', '1')->get();

        return view('pedidos.modal.ContenidoModal.ListadoAdjuntos', compact('imagenes', 'pedido'));
        //return response()->json(compact('pedido', 'pedidos', 'imagenespedido', 'imagenes'));
    }

    public function verAtencion(Pedido $pedido)
    {
        $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
            ->join('users as u', 'pedidos.user_id', 'u.id')
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->select([
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
                'pedidos.envio',
                'pedidos.condicion_envio',
                'dp.envio_doc',
                'dp.fecha_envio_doc',
                'dp.cant_compro',
                'dp.fecha_envio_doc_fis',
                'dp.fecha_recepcion',
                'pedidos.created_at as fecha'
            ])
            ->where('pedidos.estado', '1')
            ->where('pedidos.id', $pedido->id)
            ->where('dp.estado', '1')
            ->orderBy('pedidos.created_at', 'DESC')
            ->get();

        $imagenespedido = ImagenPedido::where('imagen_pedidos.pedido_id', $pedido->id)->where('estado', '1')->get();
        $imagenes = ImagenAtencion::where('imagen_atencions.pedido_id', $pedido->id)->where('estado', '1')->where('confirm', '1')->get();

        return view('operaciones.modal.ContenidoModal.ListadoAdjuntos', compact('imagenes', 'pedido'));
        //return response()->json(compact('pedido', 'pedidos', 'imagenespedido', 'imagenes'));
    }

    public function editatencionsinconfirmar(Pedido $pedido)
    {

        //dd('editando pedido: ' . $pedido);
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
                'pedidos.envio',
                'pedidos.condicion_envio',
                'dp.envio_doc',
                'dp.fecha_envio_doc',
                'dp.cant_compro',
                'dp.fecha_envio_doc_fis',
                'dp.fecha_recepcion',
                'pedidos.created_at as fecha'
            )
            ->where('pedidos.estado', '1')
            ->where('pedidos.id', $pedido->id)
            ->where('dp.estado', '1')
            ->orderBy('pedidos.created_at', 'DESC')
            ->get();

        $imagenespedido = ImagenPedido::where('imagen_pedidos.pedido_id', $pedido->id)->where('estado', '1')->get();
        $imagenes = ImagenAtencion::where('imagen_atencions.pedido_id', $pedido->id)->where('estado', '1')->where('confirm', '0')->get();

        return view('pedidos.modal.ContenidoModal.ListadoAdjuntosSinConfirmar', compact('imagenes', 'pedido'));
        //return response()->json(compact('pedido', 'pedidos', 'imagenespedido', 'imagenes'));
    }


    public function DatosSubidaAdjunto(Pedido $pedido)
    {
        $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
            ->join('users as u', 'pedidos.user_id', 'u.id')
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->select(
                'dp.fecha_envio_doc',
                'dp.cant_compro',
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
                'pedidos.envio',
                'pedidos.condicion_envio',
                'dp.envio_doc',
                'dp.fecha_envio_doc',
                'dp.cant_compro',
                'dp.fecha_envio_doc_fis',
                'dp.fecha_recepcion',
                'pedidos.created_at'
            )
            ->orderBy('pedidos.created_at', 'DESC')
            ->get();

        return response()->json(compact('pedidos'));
    }

    public function eliminarAdjuntoOperaciones(Request $request)
    {
        $id = $request->eliminar_pedido_id;
        $imagen = $request->eliminar_pedido_id_imagen;
        $imagenatencion = ImagenAtencion::where("pedido_id", $id)
            ->where("id", $imagen)->first();

        if ($imagenatencion != NULL) {
            $imagenatencion->update([
                'estado' => '0'
            ]);
            /*
                        $pedido = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
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
                            'pedidos.envio',
                            'pedidos.condicion_envio',
                            'dp.envio_doc',
                            'dp.fecha_envio_doc',
                            'dp.cant_compro',
                            'dp.fecha_envio_doc_fis',
                            'dp.fecha_recepcion',
                            'pedidos.created_at as fecha'
                        )
                        ->where('pedidos.estado', '1')
                        ->where('pedidos.id', $id)
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
                            'pedidos.envio',
                            'pedidos.condicion_envio',
                            'dp.envio_doc',
                            'dp.fecha_envio_doc',
                            'dp.cant_compro',
                            'dp.fecha_envio_doc_fis',
                            'dp.fecha_recepcion',
                            'pedidos.created_at'
                        )
                        ->orderBy('pedidos.created_at', 'DESC')
                        ->get();

                    $imagenes = ImagenAtencion::where('imagen_atencions.pedido_id', $id)->where('estado', '1')->get();*/


        }


        //return view('pedidos.modal.ContenidoModal.ListadoAdjuntos',compact('imagenes', 'pedido'));

        return response()->json(['html' => $imagen]);
    }

    public function updateAtender(Request $request, Pedido $pedido)
    {
        $detalle_pedidos = DetallePedido::where('pedido_id', $pedido->id)->first();
        $fecha = Carbon::now();

        /* $files = $request->file('envio_doc'); */
        $files = $request->file('adjunto');
        $destinationPath = base_path('public/storage/adjuntos/');

        $cont = 0;

        //ACTUALIZAR MODIFICACION AL PEDIDO
        $pedido->update([
            'modificador' => 'USER' . Auth::user()->id
        ]);

        //dd($files);

        if ($request->hasFile('adjunto')) {
            /* $file_name = Carbon::now()->second.$files->getClientOriginalName();
            $files->move($destinationPath , $file_name); */

            foreach ($files as $file) {
                $file_name = Carbon::now()->second . $file->getClientOriginalName();
                $file->move($destinationPath, $file_name);

                ImagenAtencion::create([
                    'pedido_id' => $pedido->id,
                    'adjunto' => $file_name,
                    'estado' => '1',
                    'confirm' => '1'
                ]);

                $cont++;
            }

            $detalle_pedidos->update([
                'envio_doc' => '1',
                'fecha_envio_doc' => $fecha,
                'cant_compro' => $request->cant_compro,
                'atendido_por' => Auth::user()->name,
                'atendido_por_id' => Auth::user()->id,
            ]);

        } else {
            $detalle_pedidos->update([
                'cant_compro' => $request->cant_compro,
                'atendido_por' => Auth::user()->name,
                'atendido_por_id' => Auth::user()->id,
            ]);
        }

        return redirect()->route('operaciones.atendidos')->with('info', 'actualizado');
    }

    public function updateatendersinconfirmar(Request $request, Pedido $pedido)
    {
        $detalle_pedidos = $pedido->detallePedidos()->activo()->first();
        $fecha = Carbon::now();

        /* $files = $request->file('envio_doc'); */
        $files = $request->file('adjunto');
        $destinationPath = base_path('public/storage/adjuntos/');

        $cont = 0;

        //ACTUALIZAR MODIFICACION AL PEDIDO
        $pedido->update([
            'modificador' => 'USER' . Auth::user()->id
        ]);

        //dd($files);

        if ($request->hasFile('adjunto')) {
            /* $file_name = Carbon::now()->second.$files->getClientOriginalName();
            $files->move($destinationPath , $file_name); */

            foreach ($files as $file) {
                $file_name = Carbon::now()->second . $file->getClientOriginalName();
                $file->move($destinationPath, $file_name);

                ImagenAtencion::create([
                    'pedido_id' => $pedido->id,
                    'adjunto' => $file_name,
                    'estado' => '1',
                    'confirm' => '0'
                ]);

                $cont++;
            }

            $detalle_pedidos->update([
                'envio_doc' => '1',
                'fecha_envio_doc' => $fecha,
                'cant_compro' => $request->cant_compro,
                'atendido_por' => Auth::user()->name,
                'atendido_por_id' => Auth::user()->id,
            ]);


        } else {
            $detalle_pedidos->update([
                'cant_compro' => $request->cant_compro,
                'atendido_por' => Auth::user()->name,
                'atendido_por_id' => Auth::user()->id,
            ]);
        }
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
                'pedidos.envio',
                'pedidos.condicion_envio',
                'dp.envio_doc',
                'dp.fecha_envio_doc',
                'dp.cant_compro',
                'dp.fecha_envio_doc_fis',
                'dp.fecha_recepcion',
                'pedidos.created_at as fecha'
            )
            ->where('pedidos.estado', '1')
            ->where('pedidos.id', $pedido->id)
            ->where('dp.estado', '1')
            ->orderBy('pedidos.created_at', 'DESC')
            ->get();

        $imagenespedido = ImagenPedido::where('imagen_pedidos.pedido_id', $pedido->id)->where('estado', '1')->get();
        $imagenes = ImagenAtencion::where('imagen_atencions.pedido_id', $pedido->id)->where('estado', '1')->where('confirm', '0')->get();

        return view('pedidos.modal.ContenidoModal.ListadoAdjuntosSinConfirmar', compact('imagenes', 'pedido'));
        //return redirect()->route('operaciones.atendidos')->with('info', 'actualizado');
    }

    public function updateAtenderId(Request $request)
    {
        $pedido = Pedido::where('id', $request->hiddenAtender)->first();
        $detalle_pedidos = DetallePedido::where('pedido_id', $request->hiddenAtender)->first();
        $fecha = Carbon::now();
        $cont = 0;

        //ACTUALIZAR MODIFICACION AL PEDIDO
        $pedido->update([
            'modificador' => 'USER' . Auth::user()->id,
            'sustento_adjunto' => $request->sustento,
            'da_confirmar_descarga' => 0
        ]);

        //dd($files);
        //quien es el operario relacionado al pedido (el asesor)
        $asesor_de_pedido = $pedido->user_id;
        $operario = User::where('id', $asesor_de_pedido)->first()->operario;
        //calcular el operario relacionado a este pedido

        $info_operario = User::where('id', $operario)->first();

        {
            $detalle_pedidos->update([
                'envio_doc' => '1',
                'fecha_envio_doc' => $fecha,
                'cant_compro' => $request->cant_compro,
                'atendido_por' => $info_operario->name,//Auth::user()->name,
                'atendido_por_id' => $info_operario->id,//Auth::user()->id,
            ]);
            //atendido por - atendido por id , no debe ser el quien registra, sino la operaria vinculada segun el pedido (osea al asesor)
        }

        return response()->json(['html' => $request->hiddenAtender]);

        //return redirect()->route('operaciones.atendidos')->with('info','actualizado');
    }

    public function showAtender(Pedido $pedido)
    {
        $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
            ->join('users as u', 'pedidos.user_id', 'u.id')
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->select([
                'pedidos.id',
                'pedidos.correlativo',
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
                'pedidos.da_confirmar_descarga',
                'pedidos.created_at as fecha'
            ])
            ->where('pedidos.estado', '1')
            ->where('pedidos.id', $pedido->id)
            ->where('dp.estado', '1')
            ->orderBy('pedidos.created_at', 'DESC')
            ->get();

        $imagenes = ImagenPedido::where('imagen_pedidos.pedido_id', $pedido->id)->get();
        $imagenesatencion = ImagenAtencion::where('imagen_atencions.pedido_id', $pedido->id)->get();

        return view('operaciones.showAtender', compact('pedido', 'pedidos', 'imagenes', 'imagenesatencion'));
    }

    public function Revertirenvio(Request $request)
    {
        //a listo para envio    ATENDIDO OPE  3
        $pedido = Pedido::where("id", $request->hiddenRevertirpedido)->first();
        //$detalle_pedidos = DetallePedido::where('pedido_id', $pedido->id)->first();
        //$fecha = Carbon::now();

        $pedido->update([
            'condicion_envio' => Pedido::ATENDIDO_OPE,
            'condicion_envio_code' => Pedido::ATENDIDO_OPE_INT,
            'condicion_envio_at'=>now(),
            'condicion' => Pedido::ATENDIDO_OPE,
            'condicion_code' => Pedido::ATENDIDO_OPE_INT,
            'modificador' => 'USER' . Auth::user()->id
        ]);

        /*$pedido->detallePedidos()->activo()->update([
            "cant_compro" => 0
        ]);*/
        //liberar adjuntos
        /*$imagenesatencion_ = ImagenAtencion::where("pedido_id", $request->hiddenRevertirpedidoporatender);//->where("confirm", '0');
        $imagenesatencion_->update([
            'estado' => '0'
        ]);*/

        PedidoMovimientoEstado::where('pedido', $request->hiddenRevertirpedido)->delete();

        PedidoMovimientoEstado::create([
            'pedido' => $request->hiddenRevertirpedido,
            'condicion_envio_code' => Pedido::POR_ATENDER_OPE_INT,
            'notificado' => 0
        ]);

        return response()->json(['html' => $pedido->id]);
    }

    public function Revertirenvioporatender(Request $request)
    {
        $pedido = Pedido::where("id", $request->hiddenRevertirpedidoporatender)->first();
        $detalle_pedidos = DetallePedido::where('pedido_id', $pedido->id)->first();
        $fecha = Carbon::now();

        $pedido->update([
            'envio' => '0',
            'condicion_envio' => Pedido::POR_ATENDER_OPE,
            'condicion_envio_code' => Pedido::POR_ATENDER_OPE_INT,
            'condicion_envio_at'=>now(),
            'condicion' => Pedido::POR_ATENDER_OPE,
            'condicion_code' => Pedido::POR_ATENDER_OPE_INT,
            'modificador' => 'USER' . Auth::user()->id
        ]);

        $pedido->detallePedidos()->activo()->update([
            "cant_compro" => 0
        ]);
        //liberar adjuntos
        $imagenesatencion_ = ImagenAtencion::where("pedido_id", $request->hiddenRevertirpedidoporatender);//->where("confirm", '0');
        $imagenesatencion_->update([
            'estado' => '0'
        ]);

        PedidoMovimientoEstado::where('pedido', $request->hiddenRevertirpedidoporatender)->delete();

        PedidoMovimientoEstado::create([
            'pedido' => $request->hiddenRevertirpedidoporatender,
            'condicion_envio_code' => Pedido::POR_ATENDER_OPE_INT,
            'notificado' => 0
        ]);

        return response()->json(['html' => $pedido->id]);

    }

    public function Revertirajefeop(Request $request)
    {
        $pedido = Pedido::where("id", $request->ajefeoperevertir)->first();
        $detalle_pedidos = DetallePedido::where('pedido_id', $pedido->id)->first();
        $fecha = Carbon::now();

        $pedido->update([
            //'envio' => '0',
            'condicion_envio' => Pedido::RECIBIDO_JEFE_OPE,
            'condicion_envio_code' => Pedido::RECIBIDO_JEFE_OPE_INT,
            'condicion_envio_at'=>now(),
            'condicion' => Pedido::RECIBIDO_JEFE_OPE,
            'condicion_code' => Pedido::RECIBIDO_JEFE_OPE_INT,
            'modificador' => 'USER' . Auth::user()->id
        ]);

        /*$pedido->detallePedidos()->activo()->update([
            "cant_compro" => 0
        ]);
        //liberar adjuntos
        $imagenesatencion_ = ImagenAtencion::where("pedido_id", $request->ajefeoperevertir);//->where("confirm", '0');
        $imagenesatencion_->update([
            'estado' => '0'
        ]);*/

        PedidoMovimientoEstado::where('pedido', $request->ajefeoperevertir)->delete();

        PedidoMovimientoEstado::create([
            'pedido' => $request->ajefeoperevertir,
            'condicion_envio_code' => Pedido::RECIBIDO_JEFE_OPE_INT,
            'notificado' => 0
        ]);

        return response()->json(['html' => $pedido->id]);

    }

    public function Revertirasindireccion(Request $request)
    {
        $pedido = Pedido::where("id", $request->asindireccionrevertir)->first();
        $detalle_pedidos = DetallePedido::where('pedido_id', $pedido->id)->first();
        $fecha = Carbon::now();

        $grupo=$pedido->direccion_grupo;

        $pedido->update([
            //'envio' => '0',
            'condicion_envio' => Pedido::RECIBIDO_JEFE_OPE,
            'condicion_envio_code' => Pedido::RECIBIDO_JEFE_OPE_INT,
            'condicion_envio_at'=>now(),
            'condicion' => Pedido::RECIBIDO_JEFE_OPE,
            'condicion_code' => Pedido::RECIBIDO_JEFE_OPE_INT,
            'direccion_grupo' => null,
            'modificador' => 'USER' . Auth::user()->id
        ]);
        //$detalle_pedidos->update([]);

        if(!$grupo):
            $gp=$pedido->direcciongrupo;
            if(!gp)
                $gp->update(['estado'=>"0","motorizado_status" =>0]);
        endif;

        return response()->json(['html' => $pedido->id]);

    }

    public function Revertirhaciaatendido(Request $request)
    {
        $envio = DireccionGrupo::where("id", $request->envio_id)->first();
        $pedidos = Pedido::where('codigo', $request->pedido)->first();
        $fecha = Carbon::now();

        $pedidos->update([
            "condicion_envio_code" => Pedido::ATENDIDO_OPE_INT,
            "condicion_envio" => Pedido::ATENDIDO_OPE
        ]);

        $envio->update([
            'estado' => '0'
        ]);


        //PedidoMovimientoEstado::where('pedido', $request->hiddenRevertirpedidoporatender)->delete();

        /*PedidoMovimientoEstado::create([
            'pedido' => $request->hiddenRevertirpedidoporatender,
            'condicion_envio_code' => Pedido::POR_ATENDER_OPE_INT,
            'notificado' => 0
        ]);*/

        return response()->json(['html' => $request->pedido]);

    }

}
