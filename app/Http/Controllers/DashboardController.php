<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\DetallePedido;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Ruc;
use App\Models\User;
use App\View\Components\dashboard\graficos\borras\PedidosPorDia;
use App\View\Components\dashboard\graficos\PedidosMesCountProgressBar;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use \Yajra\Datatables\Datatables;

class DashboardController extends Controller
{
  public function index()
  {
    if (auth()->user()->rol == 'MOTORIZADO') {
      return redirect()->route('envios.motorizados.index'); //->with('info', 'registrado');
    }
    $mytime = Carbon::now('America/Lima');
    $afecha = $mytime->year;
    $mfecha = $mytime->month;
    $dfecha = $mytime->day;

    $_pedidos = Pedido::activo()->join('clientes as c', 'pedidos.cliente_id', 'c.id')
      ->join('users as u', 'pedidos.user_id', 'u.id')
      ->select(
        DB::raw("COUNT(u.identificador) AS total, u.identificador ")
      )
      ->where('pedidos.codigo', 'not like', "%-C%")
      ->where('u.identificador', '<>', 'B')
      ->whereDate('pedidos.created_at', '=', now())
      ->groupBy('u.identificador');
    add_query_filtros_por_roles_pedidos($_pedidos, 'u.identificador');
    $_pedidos = $_pedidos->get();
    $data_pedidos = [];
    foreach ($_pedidos as $pedido) {
      $data_pedidos[$pedido->identificador] = $pedido->total;
    }
    $_pedidos = [];
    $asesores = User::activo()->rolAsesor()->orderBy('identificador');
    add_query_filtros_por_roles_pedidos($asesores, 'identificador');
    $asesores = $asesores->pluck('identificador');
    foreach ($asesores as $identificador) {
      $_pedidos[$identificador] = $data_pedidos[$identificador] ?? 0;
    }

    $_pedidos_totalpedidosdia = Pedido::activo()->join('clientes as c', 'pedidos.cliente_id', 'c.id')
      ->join('users as u', 'pedidos.user_id', 'u.id')
      ->where('pedidos.codigo', 'not like', "%-C%")
      ->where('u.identificador', '<>', 'B')
      ->whereDate('pedidos.created_at', '=', now());

    //add_query_filtros_por_roles_pedidos($_pedidos_totalpedidosdia, 'u.identificador');
    //$_pedidos_totalpedidosdia = $_pedidos_totalpedidosdia->count();


    /**
     * $_pedidos_mes_op = null;
     *
     *
     * if (Auth::user()->rol == "Jefe de operaciones") {
     *
     *
     * $_pedidos_mes_operario = Pedido::join('users as u', 'pedidos.user_id', 'u.id')
     * ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
     * ->select([
     * 'u.name',
     * DB::raw("COUNT(u.identificador) AS total"),
     * DB::raw(" (SELECT count(dp.tipo_banca) FROM detalle_pedidos dp
     * JOIN pedidos p ON (dp.pedido_id=p.id)
     * JOIN users us ON (p.user_id=us.id)
     * WHERE us.identificador=u.identificador AND dp.estado=1  AND dp.tipo_banca LIKE  'electronica%'
     * ) as electronico"),
     *
     * DB::raw(" (SELECT count(dp.tipo_banca) FROM detalle_pedidos dp
     * JOIN pedidos p ON (dp.pedido_id=p.id)
     * JOIN users us ON (p.user_id=us.id)
     * WHERE us.identificador=u.identificador AND dp.estado=1  AND dp.tipo_banca LIKE  'fisico%'
     * ) as fisico")
     * ])
     * ->where('pedidos.estado', '1');
     *
     *
     * $operarios = User::where('users.rol', 'Operario')
     * ->where('users.estado', '1')
     * ->where('users.jefe', Auth::user()->id)
     * ->select(
     * DB::raw("users.id as id")
     * )
     * ->pluck('users.id');
     *
     * $asesores = User::whereIN('users.rol', ['Asesor'])
     * ->where('users.estado', '1')
     * ->WhereIn('users.operario', $operarios)
     * ->select(
     * DB::raw("users.identificador as identificador")
     * )
     * ->pluck('users.identificador');
     *
     *
     * $_pedidos_mes_operario->WhereIn('u.identificador', $asesores)->groupBy('u.identificador', 'u.name');
     *
     * $_pedidos_mes_op = $_pedidos_mes_operario->get();
     * }
     */


    //DASHBOARD ADMINISTRADOR
    $pedidoxmes_total = User::select(DB::raw('sum(users.meta_pedido) as total')) //META PEDIDOS
    ->where('users.rol', "ENCARGADO")
      ->where('users.estado', '1')
      /* ->whereMonth('pedidos.created_at', $mfecha) */
      ->get();


    $pagoxmes_total = Pedido::join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id') //CANTIDAD DE PEDIDOS DEL MES
    ->activo()
      ->join('users as u', 'pedidos.user_id', 'u.id')
      ->select(DB::raw('count(dp.id) as pedidos'))
      ->wherein('u.rol', ['ASESOR'])
      ->whereBetween('dp.created_at', [now()->startOfMonth(), now()->endOfMonth()])
      ->get();


    $pagoxmes_total_solo_asesor_b = Pedido::join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id') //CANTIDAD DE PEDIDOS DEL MES
    ->activo()
      ->join('users as u', 'pedidos.user_id', 'u.id')
      ->select(DB::raw('count(dp.id) as pedidos'))
      ->where('u.id', 51)
      ->whereBetween('dp.created_at', [now()->startOfMonth(), now()->endOfMonth()])
      ->get();


    //$montopedidoxmes_total = User::select(DB::raw('sum(users.meta_cobro) as total'))
    //META DE COBRANZAS DEL MES
    $montopedidoxmes_total = Pedido::join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
      ->activo()
      ->join('users', 'pedidos.user_id', 'users.id')
      ->select(DB::raw('(sum(dp.total))/(count(dp.pedido_id)) as total'))
      //->where('users.rol', "ASESOR")
      ->where('users.estado', '1')
      ->whereBetween('pedidos.created_at', [now()->startOfMonth(), now()->endOfMonth()])
      //->whereYear('pedidos.created_at', $afecha)
      ->get();
    //return $montopedidoxmes_total;
    if (Auth::user()->id == "33") {
      $montopagoxmes_total = Pago::join('detalle_pagos as dpa', 'pagos.id', 'dpa.pago_id') //CANTIDAD DE PAGOS DEL MES
      ->select(DB::raw('sum(dpa.monto) as total'))
        ->where('pagos.estado', '1')
        ->where('dpa.estado', '1')
        ->whereBetween('dpa.created_at', [now()->startOfMonth(), now()->endOfMonth()])
        ->get();
    } else {
      $montopagoxmes_total = Pago::join('detalle_pagos as dpa', 'pagos.id', 'dpa.pago_id') //CANTIDAD DE PAGOS DEL MES
      ->join('users as u', 'pagos.user_id', 'u.id')
        ->select(DB::raw('sum(dpa.monto) as total'))
        ->where('u.rol', 'ASESOR')
        ->where('pagos.estado', '1')
        ->where('dpa.estado', '1')
        ->whereBetween('dpa.created_at', [now()->startOfMonth(), now()->endOfMonth()])
        ->get();
    }
    //GRAFICO DE BARRAS IMPORTE/PEDIDOS
    $cobranzaxmes = Pedido::join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
      ->activo()
      ->join('users as u', 'pedidos.user_id', 'u.id')
      ->select(
        'u.identificador as usuarios',
        DB::raw('((sum(dp.total)/count(dp.id))) as total')
      )
      //->whereIn('u.rol', ['ENCARGADO', 'Super asesor','ASESOR'])
      ->whereBetween('dp.created_at', [now()->startOfMonth(), now()->endOfMonth()])
      ->groupBy('u.identificador')
      //->orderBy((DB::raw('count(dp.id)')), 'DESC')
      ->get();
    //return $cobranzaxmes;
    //PEDIDOS POR ASESOR EN EL MES
    $pedidosxasesor = Pedido::join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
      ->activo()
      ->join('users as u', 'pedidos.user_id', 'u.id')
      ->select('u.identificador as users', DB::raw('count(dp.id) as pedidos'))
      ->whereIn('u.rol', ['ASESOR', 'Super asesor'])
      ->whereBetween('dp.created_at', [now()->startOfMonth(), now()->endOfMonth()])
      ->groupBy('u.identificador')
      ->orderBy((DB::raw('count(dp.id)')), 'DESC')
      ->get();

    //PEDIDOS X MES

    $pedidos_mes_ = Pedido::select(DB::raw('count(*) as total')) //META PEDIDOS
    ->activo()
      ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
      ->get();


    //MONTO DE PAGO X CLIENTE EN EL MES TOP 30
    $pagosxmes = Pago::join('clientes as c', 'pagos.cliente_id', 'c.id')
      ->join('users as u', 'pagos.user_id', 'u.id')
      ->select(['c.nombre as cliente', DB::raw('sum(pagos.total_cobro) as pagos')])
      ->whereIn('u.rol', ['ASESOR', 'Super asesor'])
      ->where('pagos.estado', '1')
      ->whereBetween('pagos.created_at', [now()->startOfMonth(), now()->endOfMonth()])
      ->groupBy('c.nombre')
      ->orderBy(DB::raw('sum(pagos.total_cobro)'), 'DESC')
      ->offset(0)
      ->limit(30)
      ->get();

    //PEDIDOS POR ASESOR EN EL DIA
    $pedidosxasesorxdia = Pedido::join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
      ->activo()
      ->join('users as u', 'pedidos.user_id', 'u.id')
      ->select('u.name as users', DB::raw('count(dp.id) as pedidos'))
      ->whereIn('u.rol', ['ASESOR', 'Super asesor'])
      ->whereDate('dp.created_at', now())
      ->groupBy('u.name')
      ->orderBy((DB::raw('count(dp.id)')), 'DESC')
      ->get();
    //DASHBOARD ENCARGADO
    $meta_pedidoencargado = Pedido::join('users as u', 'pedidos.user_id', 'u.id')
      ->activo()
      ->where('u.supervisor', Auth::user()->id)
      ->whereBetween('pedidos.created_at', [now()->startOfMonth(), now()->endOfMonth()])
      ->count();
    $meta_pagoencargado = Pago::join('users as u', 'pagos.user_id', 'u.id')
      ->join('detalle_pagos as dpa', 'pagos.id', 'dpa.pago_id')
      ->select(DB::raw('sum(dpa.monto) as pagos'))
      ->where('u.supervisor', Auth::user()->id)
      ->where('pagos.estado', '1')
      ->whereBetween('pagos.created_at', [now()->startOfMonth(), now()->endOfMonth()])
      ->first();


    //PEDIDOS DE MIS ASESORES EN EL MES


    $pedidosxasesor_encargado = Pedido::join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
      ->activo()
      ->join('users as u', 'pedidos.user_id', 'u.id')
      ->select('u.name as users', DB::raw('count(dp.id) as pedidos'))
      ->where('u.supervisor', Auth::user()->id)
      ->whereBetween('dp.created_at', [now()->startOfMonth(), now()->endOfMonth()])
      ->groupBy('u.name')
      ->orderBy((DB::raw('count(dp.id)')), 'DESC')
      ->get();


    //HISTORIAL DE PEDIDOS DE MIS ASESORES EN LOS ULTIMOS 3 MES
    $pedidosxasesor_3meses_encargado = Pedido::join('users as u', 'pedidos.user_id', 'u.id')
      ->activo()
      ->select('u.name as users', DB::raw('count(pedidos.id) as pedidos'), DB::raw('DATE(pedidos.created_at) as fecha'))
      ->where('u.supervisor', Auth::user()->id)
      ->whereDay('pedidos.created_at', $dfecha)
      ->WhereIn(DB::raw('DATE_FORMAT(pedidos.created_at, "%m")'), [$mfecha, $mfecha - 1, $mfecha - 2])
      ->whereYear('pedidos.created_at', $afecha)
      ->groupBy('u.name', 'u.id', DB::raw('DATE(pedidos.created_at)'))
      ->orderBy('u.id', 'ASC')
      ->get();
    //MONTO DE PAGO X CLIENTE DE MIS ASESORES EN EL MES TOP 30
    $pagosxmes_encargado = Pago::join('clientes as c', 'pagos.cliente_id', 'c.id')
      ->join('users as u', 'pagos.user_id', 'u.id')
      ->select('c.nombre as cliente', DB::raw('sum(pagos.total_cobro) as pagos'))
      ->where('u.supervisor', Auth::user()->id)
      ->where('pagos.estado', '1')
      ->whereBetween('pagos.created_at', [now()->startOfMonth(), now()->endOfMonth()])
      ->groupBy('c.nombre')
      ->offset(0)
      ->limit(30)
      ->get();
    //PEDIDOS DE MIS ASESORES EN EL DIA
    $pedidosxasesorxdia_encargado = Pedido::join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
      ->activo()
      ->join('users as u', 'pedidos.user_id', 'u.id')
      ->select('u.name as users', DB::raw('count(dp.id) as pedidos'))
      ->where('u.supervisor', Auth::user()->id)
      ->whereDate('dp.created_at', now())
      ->groupBy('u.name')
      ->orderBy((DB::raw('count(dp.id)')), 'DESC')
      ->get();
    //DASHBOARD ASESOR
    $meta_pedidoasesor = Pedido::join('users as u', 'pedidos.user_id', 'u.id')
      ->activo()
      ->where('u.id', Auth::user()->id)
      ->whereBetween('pedidos.created_at', [now()->startOfMonth(), now()->endOfMonth()])
      ->count();
    $meta_pagoasesor = (object)["pagos" => Pago::join('users as u', 'pagos.user_id', 'u.id')
      ->join('detalle_pagos as dpa', 'pagos.id', 'dpa.pago_id')
      ->where('u.id', Auth::user()->id)
      ->where('pagos.estado', '1')
      ->where('dpa.estado', '1')
      ->whereBetween('pagos.created_at', [now()->startOfMonth(), now()->endOfMonth()])
      ->sum('dpa.monto')];

    $pagosobservados_cantidad = Pago::where('user_id', Auth::user()->id) //PAGOS OBSERVADOS
    ->where('estado', '1')
      ->where('condicion', Pago::OBSERVADO)
      ->count();
    //HISTORIAL DE MIS PEDIDOS EN EL MES
    $pedidosxasesorxdia_asesor = Pedido::join('users as u', 'pedidos.user_id', 'u.id')
      ->activo()
      ->select('u.name as users', DB::raw('count(pedidos.id) as pedidos'), DB::raw('DATE(pedidos.created_at) as fecha'))
      ->where('u.id', Auth::user()->id)
      ->whereBetween('pedidos.created_at', [now()->startOfMonth(), now()->endOfMonth()])
      ->groupBy('u.name', DB::raw('DATE(pedidos.created_at)'))
      ->orderBy(DB::raw('DATE(pedidos.created_at)'), 'ASC')
      ->get();
    //ALERTA DE PEDIDOS SIN PAGOS
    $pedidossinpagos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')
      ->activo()
      ->join('users as u', 'pedidos.user_id', 'u.id')
      ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
      ->select(
        'pedidos.id',
        'c.nombre as nombres',
        'c.celular as celulares',
        'u.name as users',
        'dp.codigo as codigos',
        'dp.nombre_empresa as empresas',
        DB::raw('sum(dp.total) as total'),
        'pedidos.condicion as condiciones',
        'pedidos.created_at as fecha'
      )
      ->where('dp.estado', '1')
      ->where('u.id', Auth::user()->id)
      ->where('pedidos.pago', '0')
      ->groupBy(
        'pedidos.id',
        'c.nombre',
        'c.celular',
        'u.name',
        'dp.codigo',
        'dp.nombre_empresa',
        'pedidos.condicion',
        'pedidos.created_at'
      )
      ->orderBy('pedidos.created_at', 'DESC')
      ->get();
    //DASHBOARD OPERACION
    $pedidoxatender = Pedido::where('condicion', 'REGISTRADO')
      ->activo()
      ->count();
    $pedidoenatencion = Pedido::where('condicion', 2)
      ->activo()
      ->count();
    //DASHBOARD ADMINISTRACION
    $pagosxrevisar_administracion = Pago::where('estado', '1')
      ->where('condicion', Pago::PAGO)
      ->count();
    $pagosobservados_administracion = Pago::where('estado', '1')
      ->where('condicion', Pago::OBSERVADO)
      ->count();
    //DASHBOARD Logística
    //sobres por enviar
    //sobres por recibir


    $conteo = count(auth()->user()->unreadNotifications);


    return view(
      'dashboard.dashboard',
      compact(
        'pedidoxmes_total',
        'pedidos_mes_',
        'pagoxmes_total',
        'pagoxmes_total_solo_asesor_b',
        'montopedidoxmes_total',
        'montopagoxmes_total',
        'pedidossinpagos',
        'pedidosxasesor',
        'pagosxmes',
        'pedidosxasesorxdia',
        'meta_pedidoencargado',
        'meta_pagoencargado',
        'pedidosxasesor_encargado',
        'pedidosxasesor_3meses_encargado',
        'pagosxmes_encargado',
        'pedidosxasesorxdia_encargado',
        'meta_pedidoasesor',
        'meta_pagoasesor',
        'pagosobservados_cantidad',
        'pedidosxasesorxdia_asesor',
        'pedidoxatender',
        'pedidoenatencion',
        'pagosxrevisar_administracion',
        'pagosobservados_administracion',
        'conteo',
        'cobranzaxmes',
        '_pedidos',
        '_pedidos_totalpedidosdia'
      )
    );
  }

  public function widgets(Request $request)
  {
    $widget1 = new PedidosMesCountProgressBar();
    $widget2 = new PedidosPorDia(\auth()->user()->rol, 'Cantidad de pedidos de los asesores por dia', 'Asesores', 'Cant. Pedidos', '370', true);
    $widget3 = new PedidosPorDia(\auth()->user()->rol, 'Cantidad de pedidos de los asesores por mes', 'Asesores', 'Cant. Pedidos');
    $widget2->renderData();
    $widget3->renderData();
    return response()->json([
      "widgets" =>
        [
          [
            "data" => [],
            "html" => \Blade::renderComponent($widget1)
          ],
          [
            "data" => $widget2->getData(),
            "html" => \Blade::renderComponent($widget2)
          ],
          [
            "chart" => true,
            "data" => $widget3->getData(),
            "html" => \Blade::renderComponent($widget3)
          ],
        ]
    ]);
  }

  public function searchCliente(Request $request)
  {
    $q = $request->get("q");
    $clientes = Cliente::query()
      ->with(['user', 'rucs', 'porcentajes'])
      ->where('celular', 'like', '%' . $q . '%')
      ->orwhere(DB::raw("concat(clientes.celular,'-',clientes.icelular)"), 'like', '%' . $q . '%')
      ->orWhere('nombre', 'like', '%' . join("%", explode(" ", trim($q))) . '%')
      ->orWhere('dni', 'like', '%' . $q . '%')
      ->limit(10)
      ->get()
      ->map(function (Cliente $cliente) {
        $cliente->deuda_total = DetallePedido::query()->whereIn('pedido_id', $cliente->pedidos()->where('estado', '1')->pluck("id"))->sum("saldo");
        return $cliente;
      });

    return view('dashboard.searchs.search_cliente', compact('clientes'));
  }

  public function searchRuc(Request $request)
  {
    $q = $request->get("q");
    $rucs = Ruc::query()
      ->with(['cliente', 'user'])
      ->where('num_ruc', 'like', '%' . $q . '%')
      ->limit(10)
      ->get()
      ->map(function (Ruc $ruc) {
        $ruc->cliente->deuda_total = DetallePedido::query()->activo()->whereIn('pedido_id', $ruc->cliente->pedidos()->activo()->pluck("id"))->sum("saldo");
        $ruc->cliente->deuda_total_ruc = DetallePedido::query()->activo()->where('ruc', $ruc->num_ruc)->sum("saldo");
        return $ruc;
      });
    return view('dashboard.searchs.search_rucs', compact('rucs'));
  }


  /**
   * @throws \Exception
   */
  public function graficoMetaTable(Request $request)
  {
    $metas = [];
    if (auth()->user()->rol == User::ROL_LLAMADAS) {
      $asesores = User::query()->activo()->rolAsesor()->get();
    } else if (auth()->user()->rol == User::ROL_FORMACION) {
      $asesores = User::query()->activo()->rolAsesor()->get();
    } else {
      $encargado = null;
      if (auth()->user()->rol == User::ROL_ENCARGADO) {
        $encargado = auth()->user()->id;
      }
      $asesores = User::query()->activo()->rolAsesor()->when($encargado != null, function ($query) use ($encargado) {
        return $query->where('supervisor', '=', $encargado);
      })->get();
    }
    foreach ($asesores as $asesor) {
      if (in_array(auth()->user()->rol, [User::ROL_FORMACION, User::ROL_ADMIN])) {
      } else {
        if (auth()->user()->rol != User::ROL_ADMIN /*|| auth()->user()->rol!=User::ROL_FORMACION*/) {
          if (auth()->user()->rol != User::ROL_ENCARGADO) {
            if (auth()->user()->id != $asesor->id) {
              continue;
            }
          } else {
            if (auth()->user()->id != $asesor->supervisor) {
              continue;
            }
          }
        }
      }

      $date_pagos = Carbon::parse(now())->subMonth();
      $asesor_pedido_dia = Pedido::query()->join('users as u', 'u.id', 'pedidos.user_id')->where('u.identificador', $asesor->identificador)
        ->where('pedidos.codigo', 'not like', "%-C%")->activo()->whereDate('pedidos.created_at', now())->count();
      $metatotal = (float)$asesor->meta_pedido;
      $metatotal_2 = (float)$asesor->meta_pedido_2;
      $metatotal_cobro = (float)$asesor->meta_cobro;
      $total_pedido = $this->applyFilterCustom(Pedido::query()->where('user_id', $asesor->id)
        ->where('codigo', 'not like', "%-C%")->activo(), now(), 'created_at')
        ->count();
      $total_pedido_mespasado = $this->applyFilterCustom(Pedido::query()->where('user_id', $asesor->id)
        ->where('codigo', 'not like', "%-C%")->activo(), $date_pagos, 'created_at')
        ->count();
      $total_pagado = $this->applyFilterCustom(Pedido::query()->where('user_id', $asesor->id)
        ->where('codigo', 'not like', "%-C%")->activo()->pagados(), $date_pagos, 'created_at')
        ->count();

      //TOTALES//
      //PEDIDOS INDIVIDUALES
      $pedidos_totales = Pedido::query()->join('users as u', 'u.id', 'pedidos.user_id')
        ->where('pedidos.codigo', 'not like', "%-C%")->whereDate('pedidos.created_at', now())->count();
      //COBRANZAS TOTALES
      $total_pagado_cobranza = $this->applyFilterCustom(Pedido::query()
        ->where('codigo', 'not like', "%-C%")->activo()->pagados(), $date_pagos, 'created_at')
        ->count();
      $total_pedido_mespasado_cobranza = $this->applyFilterCustom(Pedido::query()
        ->where('codigo', 'not like', "%-C%")->activo(), $date_pagos, 'created_at')
        ->count();
      //PEDIDOS TOTALES
      $total_pedido_general = $this->applyFilterCustom(Pedido::query()
        ->where('codigo', 'not like', "%-C%")->activo(), now(), 'created_at')
        ->count();


      $item = [
        "identificador" => $asesor->identificador,
        "code" => "Asesor {$asesor->identificador}",
        "pedidos_dia" => $asesor_pedido_dia,
        "name" => $asesor->name,
        "total_pedido" => $total_pedido,
        "total_pedido_mespasado" => $total_pedido_mespasado,
        "total_pagado" => $total_pagado,
        "meta" => $metatotal,
        "meta_2" => $metatotal_2,
        "meta_cobro" => $metatotal_cobro,
        "pedidos_totales" => $pedidos_totales,
      ];

      if ($asesor->excluir_meta) {
        if ($metatotal_cobro > 0) {
          $p_pagos = round(($total_pedido_mespasado / $total_pagado) * 100, 2);
        } else {
          $p_pagos = 0;
        }
        if ($metatotal > 0) {
          $p_pedidos = round(($total_pedido / $metatotal) * 100, 2);
        } else {
          $p_pedidos = 0;
        }
        $item['progress_pagos'] = $p_pagos;
        $item['progress_pedidos'] = $p_pedidos;
      } else {
        $progressData[] = $item;
      }
    }


    $newData = [];
    $union = collect($progressData)->groupBy('identificador');
    foreach ($union as $identificador => $items) {
      foreach ($items as $item) {
        if (!isset($newData[$identificador])) {
          $newData[$identificador] = $item;
        } else {
          $newData[$identificador]['total_pedido'] += data_get($item, 'total_pedido');
          $newData[$identificador]['total_pedido_pasado'] += data_get($item, 'total_pedido_mespasado');
          $newData[$identificador]['total_pagado'] += data_get($item, 'total_pagado');
          $newData[$identificador]['meta'] += data_get($item, 'meta');
          $newData[$identificador]['meta_2'] += data_get($item, 'meta_2');
          $newData[$identificador]['meta_cobro'] += data_get($item, 'meta_cobro');
          $newData[$identificador]['pedidos_dia'] += data_get($item, 'pedidos_dia');

          $newData[$identificador]['pedidos_totales'] += data_get($item, 'pedidos_totales');
        }
      }
      $newData[$identificador]['name'] = collect($items)->map(function ($item) {
        return explode(" ", data_get($item, 'name'))[0];
      })->first();
    }
    $progressData = collect($newData)->values()->map(function ($item) {
      $all = data_get($item, 'total_pedido');
      $all_mespasado = data_get($item, 'total_pedido_mespasado');
      $pay = data_get($item, 'total_pagado');
      $allmeta = data_get($item, 'meta');
      $allmeta_2 = data_get($item, 'meta_2');
      $allmeta_cobro = data_get($item, 'meta_cobro');
      $pedidos_dia = data_get($item, 'pedidos_dia');

      $pedidos_totales = data_get($item, 'pedidos_totales');


      if ($pay > 0) {
        $p_pagos = round(($pay / $all_mespasado) * 100, 2);
      } else {
        $p_pagos = 0;
      }

      if ($allmeta > 0) {
        $p_pedidos = round(($all / $allmeta) * 100, 2);
      } else {
        $p_pedidos = 0;
      }

      if ($allmeta_2 > 0) {
        $p_pedidos_new = round(($all / $allmeta_2) * 100, 2);
      } else {
        $p_pedidos_new = 0;
      }

      if ($p_pedidos >= 100) {
        $item['progress_pedidos'] = $p_pedidos_new;
        $item['meta_new'] = 0;

      } else {
        $item['progress_pedidos'] = $p_pedidos;
        $item['meta_new'] = 1;
      }

      $item['progress_pagos'] = $p_pagos;
      $item['total_pedido'] = $all;
      $item['total_pedido_pasado'] = $all_mespasado;
      $item['pedidos_dia'] = $pedidos_dia;


      $item['pedidos_totales'] = $pedidos_totales;
      return $item;

    })->sortBy('progress_pedidos', SORT_NUMERIC, true)->all();;

    return Datatables::of($progressData)
      //ASESOR
      ->editColumn('identificador', function ($row) {
        return 'TODOS';
      })
      //IDENTIFICADOR
      ->editColumn('code', function ($row) {
        return '<span class="align-center font-weight-bold justify-content-center" style="font-weight:bold !important;"> ' . $row["code"] . '</span>';
      })
      //PEDIDOS DEL DIA
      ->editColumn('pedidos_dia', function ($row) {
        if ($row["pedidos_dia"] > 0) {
          return '<span class="px-4 pt-1 pb-1 bg-white text-center justify-content-center w-100 rounded font-weight-bold"> ' . $row["pedidos_dia"] . ' </span>';
        } else {
          return '<span class="px-4 pt-1 pb-1 bg-red text-center justify-content-center w-100 rounded font-weight-bold"> ' . $row["pedidos_dia"] . ' </span>';
        }
      })



      //SECCION DEL TOTAL
      //ASESORES
      ->editColumn('asesores', function ($row) {
        return '';
      })
      //IDENTIFICADORES
      ->editColumn('identificador', function ($row) {
        return '';
      })
      //PEDIDOS TOTALES
      ->editColumn('pedidos_totales', function ($row) {
        return '<span class="px-4 pt-1 pb-1 bg-white text-center justify-content-center w-100 rounded font-weight-bold"> ' . $row["pedidos_totales"] . ' </span>';
      })
      //COBRANZA TOTAL MES
      ->editColumn('progress_pagos', function ($row) {
        return '               <div class="w-100 bg-white rounded">
                                  <div class="position-relative rounded">
                                    <div class="progress bg-white rounded" style="height: 30px">
                                        <div class="rounded" role="progressbar" style="background: #008ffb !important; width: ' . $row["progress_pagos"] . '%" ></div>
                                        </div>
                                      <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                          <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  ' . $row["progress_pagos"] . '% </b> - ' . $row["total_pagado"] . ' / ' . $row["meta"] . ' aaaaa</span>
                                      </div>
                                  </div>
                                  <sub class="d-none">% -  Pagados/ Asignados</sub>
                                </div>';
      })
      //PEDIDO TOTAL MES
      ->editColumn('progress_pedidos', function ($row) {
        return '               <div class="w-100 bg-white rounded">
                                  <div class="position-relative rounded">
                                    <div class="progress bg-white rounded" style="height: 30px">
                                        <div class="rounded" role="progressbar" style="background: #008ffb !important; width: ' . $row["progress_pedidos"] . '%" ></div>
                                        </div>
                                      <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                          <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  ' . $row["progress_pedidos"] . '% </b> - ' . $row["total_pedido"] . ' / ' . $row["meta"] . ' bbbbb</span>
                                      </div>
                                  </div>
                                  <sub class="d-none">% -  Pagados/ Asignados</sub>
                                </div>';
      })



      //PEDIDOS DEL MES
      ->editColumn('progress_pedidos', function ($row) {
        if ($row["meta_new"] == 0) {
          if ($row["progress_pedidos"] == 100) {
            return '<div class="w-100 bg-white rounded">
                                  <div class="position-relative rounded">
                                    <div class="progress bg-white rounded" style="height: 30px">
                                        <div class="rounded" role="progressbar" style="background: #008ffb !important; width: ' . $row["progress_pedidos"] . '%" ></div>
                                        </div>
                                      <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                          <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  ' . $row["progress_pedidos"] . '% </b> - ' . $row["total_pedido"] . ' / ' . $row["meta_2"] . '</span>
                                      </div>
                                  </div>
                                  <sub class="d-none">% -  Pagados/ Asignados</sub>
                                </div>';
          } else {
            return '    <div class="w-100 bg-white rounded">
                                  <div class="position-relative rounded">
                                    <div class="progress bg-white rounded" style="height: 30px">
                                        <div class="rounded" role="progressbar" style="background: #03af03 !important; font-size: 18px; width: ' . $row["progress_pedidos"] . '%" ></div>
                                        </div>
                                      <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                          <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px"">  ' . $row["progress_pedidos"] . '% </b> - ' . $row["total_pedido"] . ' / ' . $row["meta_2"] . '</span>
                                      </div>
                                  </div>
                                  <sub class="d-none">% -  Pagados/ Asignados</sub>
                                </div>';
          }
        }
        if ($row["meta_new"] == 1) {
          if ($row["progress_pedidos"] >= 95) {
            return '   <div class="w-100 bg-white rounded">
                                  <div class="position-relative rounded">
                                    <div class="progress bg-white rounded" style="height: 30px">
                                        <div class="rounded" role="progressbar" style="background: linear-gradient(90deg, rgba(3,175,3,1) 0%, rgba(24,150,24,1) 60%, rgba(0,143,251,1) 100%) !important; width: ' . $row["progress_pedidos"] . '%" ></div>
                                        </div>
                                      <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                          <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  ' . $row["progress_pedidos"] . '% </b> - ' . $row["total_pedido"] . ' / ' . $row["meta"] . '</span>
                                      </div>
                                  </div>
                                  <sub class="d-none">% -  Pagados/ Asignados</sub>
                                </div>';
          } elseif ($row["progress_pedidos"] >= 70) {
            return '    <div class="w-100 bg-white rounded">
                                  <div class="position-relative rounded">
                                    <div class="progress bg-white rounded" style="height: 30px">
                                        <div class="rounded" role="progressbar" style="background: linear-gradient(90deg, rgba(255,193,7,1) 0%, rgba(255,193,7,1) 89%, rgba(113,193,27,1) 100%) !important; width: ' . $row["progress_pedidos"] . '%" ></div>
                                        </div>
                                      <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                          <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  ' . $row["progress_pedidos"] . '% </b> - ' . $row["total_pedido"] . ' / ' . $row["meta"] . '</span>
                                      </div>
                                  </div>
                                  <sub class="d-none">% -  Pagados/ Asignados</sub>
                                </div>';
          } elseif ($row["progress_pedidos"] >= 60) {
            return '    <div class="w-100 bg-white rounded">
                                  <div class="position-relative rounded">
                                    <div class="progress bg-white rounded" style="height: 30px">
                                        <div class="rounded" role="progressbar" style="background: #ffc107 !important; width: ' . $row["progress_pedidos"] . '%" ></div>
                                        </div>
                                      <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                          <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  ' . $row["progress_pedidos"] . '% </b> - ' . $row["total_pedido"] . ' / ' . $row["meta"] . '</span>
                                      </div>
                                  </div>
                                  <sub class="d-none">% -  Pagados/ Asignados</sub>
                                </div>';
          } elseif ($row["progress_pedidos"] >= 50) {
            return '    <div class="w-100 bg-white rounded">
                                  <div class="position-relative rounded">
                                    <div class="progress bg-white rounded" style="height: 30px">
                                        <div class="rounded" role="progressbar" style="background: linear-gradient(90deg, rgba(220,53,69,1) 0%, rgba(194,70,82,1) 89%, rgba(255,193,7,1) 100%) !important; width: ' . $row["progress_pedidos"] . '%" ></div>
                                        </div>
                                      <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                          <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  ' . $row["progress_pedidos"] . '% </b> - ' . $row["total_pedido"] . ' / ' . $row["meta"] . '</span>
                                      </div>
                                  </div>
                                  <sub class="d-none">% -  Pagados/ Asignados</sub>
                                </div>
                        ';
          } else {
            return '<div class="w-100 bg-white rounded">
                            <div class="position-relative rounded">
                                <div class="progress bg-white rounded" style="height: 30px">
                                    <div class="rounded" role="progressbar" style="background: #dc3545;width: ' . $row["progress_pedidos"] . '%" ></div>
                                    </div>
                                <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                    <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  ' . $row["progress_pedidos"] . '% </b> - ' . $row["total_pedido"] . ' / ' . $row["meta"] . '</span>
                                </div>
                            </div>
                            <sub class="d-none">% -  Pagados/ Asignados</sub>
                          </div>';
          }
        }
      })
      //COBRANZA DEL MES
      ->editColumn('progress_pagos', function ($row) {
        if ($row["progress_pagos"] == 100) {
          return '<div class="w-100 bg-white rounded">
                                  <div class="position-relative rounded">
                                    <div class="progress bg-white rounded" style="height: 30px">
                                        <div class="rounded" role="progressbar" style="background: #008ffb !important; width: ' . $row["progress_pagos"] . '%" aria-valuenow="34.25" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                      <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                          <span style="font-weight: lighter"> <b style="font-weight: bold !important;font-size: 18px">  ' . $row["progress_pagos"] . '% </b> - ' . $row["total_pagado"] . ' / ' . $row["total_pedido_mespasado"] . '</span>
                                      </div>
                                  </div>
                                  <sub class="d-none">% -  Pagados/ Asignados</sub>
                                </div>';
        } elseif ($row["progress_pagos"] >= 80) {
          return '    <div class="w-100 bg-white rounded">
                                  <div class="position-relative rounded">
                                    <div class="progress bg-white rounded" style="height: 30px">
                                        <div class="rounded" role="progressbar" style="background: #8ec117 !important; width: ' . $row["progress_pagos"] . '%" aria-valuenow="34.25" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                      <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                          <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  ' . $row["progress_pagos"] . '% </b> - ' . $row["total_pagado"] . ' / ' . $row["total_pedido_mespasado"] . '</span>
                                      </div>
                                  </div>
                                  <sub class="d-none">% -  Pagados/ Asignados</sub>
                                </div>';
        } elseif ($row["progress_pagos"] > 70) {
          return '<div class="w-100 bg-white rounded">
                                  <div class="position-relative rounded">
                                    <div class="progress bg-white rounded" style="height: 30px">
                                        <div class="rounded" role="progressbar" style="background: linear-gradient(90deg, rgba(255,193,7,1) 0%, rgba(255,193,7,1) 89%, rgba(113,193,27,1) 100%) !important; width: ' . $row["progress_pagos"] . '%" aria-valuenow="34.25" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                      <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                          <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  ' . $row["progress_pagos"] . '% </b> - ' . $row["total_pagado"] . ' / ' . $row["total_pedido_mespasado"] . '</span>
                                      </div>
                                  </div>
                                  <sub class="d-none">% -  Pagados/ Asignados</sub>
                                </div>';
        } elseif ($row["progress_pagos"] > 60) {
          return '<div class="w-100 bg-white rounded">
                                  <div class="position-relative rounded">
                                    <div class="progress bg-white rounded" style="height: 30px">
                                        <div class="rounded" role="progressbar" style="background: #ffc107 !important; width: ' . $row["progress_pagos"] . '%" aria-valuenow="34.25" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                      <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                          <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  ' . $row["progress_pagos"] . '% </b> - ' . $row["total_pagado"] . ' / ' . $row["total_pedido_mespasado"] . '</span>
                                      </div>
                                  </div>
                                  <sub class="d-none">% -  Pagados/ Asignados</sub>
                                </div>';
        } elseif ($row["progress_pagos"] > 50) {
          return '<div class="w-100 bg-white rounded">
                                  <div class="position-relative rounded">
                                    <div class="progress bg-white rounded" style="height: 30px">
                                        <div class="rounded" role="progressbar" style="background: linear-gradient(90deg, rgba(220,53,69,1) 0%, rgba(194,70,82,1) 89%, rgba(255,193,7,1) 100%) !important; width: ' . $row["progress_pagos"] . '%" aria-valuenow="34.25" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                      <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                          <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  ' . $row["progress_pagos"] . '% </b> - ' . $row["total_pagado"] . ' / ' . $row["total_pedido_mespasado"] . '</span>
                                      </div>
                                  </div>
                                  <sub class="d-none">% -  Pagados/ Asignados</sub>
                                </div>';
        } else {
          return '    <div class="w-100 bg-white rounded">
                            <div class="position-relative rounded">
                                <div class="progress bg-white rounded" style="height: 30px">
                                    <div class="rounded" role="progressbar" style="background: #dc3545 !important; width: ' . $row["progress_pagos"] . '%" aria-valuenow="34.25" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                    <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  ' . $row["progress_pagos"] . '% </b> - ' . $row["total_pagado"] . ' / ' . $row["total_pedido_mespasado"] . '</span>
                                </div>
                            </div>
                            <sub class="d-none">% -  Pagados/ Asignados</sub>
                          </div>';
        }


      }
      )->rawColumns(['identificador', 'code', 'pedidos_dia', 'progress_pagos', 'progress_pedidos'])
      ->make(true);
  }


  public function viewMetaTable(Request $request)
  {
    $metas = [];
    $total_asesor= User::query()->activo()->rolAsesor()->count();

    if (auth()->user()->rol == User::ROL_LLAMADAS) {
      $asesores = User::query()->activo()->rolAsesor()
        ->get();
    } else if (auth()->user()->rol == User::ROL_FORMACION) {
      $asesores = User::query()->activo()->rolAsesor()
        ->get();
    } else {
      $encargado = null;
      if (auth()->user()->rol == User::ROL_ENCARGADO) {
        $encargado = auth()->user()->id;
      }
      $asesores = User::query()->activo()->rolAsesor()->when($encargado != null, function ($query) use ($encargado) {
        return $query->where('supervisor', '=', $encargado);
      })->get();
    }
    foreach ($asesores as $asesor) {
      if (in_array(auth()->user()->rol, [User::ROL_FORMACION, User::ROL_ADMIN])) {
      } else {
        if (auth()->user()->rol != User::ROL_ADMIN /*|| auth()->user()->rol!=User::ROL_FORMACION*/) {
          if (auth()->user()->rol != User::ROL_ENCARGADO) {
            if (auth()->user()->id != $asesor->id) {
              continue;
            }
          } else {
            if (auth()->user()->id != $asesor->supervisor) {
              continue;
            }
          }
        }
      }

      $date_pagos = Carbon::parse(now())->subMonth();
      $asesor_pedido_dia = Pedido::query()->join('users as u', 'u.id', 'pedidos.user_id')->where('u.identificador', $asesor->identificador)
        ->where('pedidos.codigo', 'not like', "%-C%")->activo()->whereDate('pedidos.created_at', now())->count();
      $metatotal = (float)$asesor->meta_pedido;
      $metatotal_2 = (float)$asesor->meta_pedido_2;
      $metatotal_cobro = (float)$asesor->meta_cobro;
      $total_pedido = $this->applyFilterCustom(Pedido::query()->where('user_id', $asesor->id)
        ->where('codigo', 'not like', "%-C%")->activo(), now(), 'created_at')
        ->count();
      $total_pedido_mespasado = $this->applyFilterCustom(Pedido::query()->where('user_id', $asesor->id)
        ->where('codigo', 'not like', "%-C%")->activo(), $date_pagos, 'created_at')
        ->count();
      $total_pagado = $this->applyFilterCustom(Pedido::query()->where('user_id', $asesor->id)
        ->where('codigo', 'not like', "%-C%")->activo()->pagados(), $date_pagos, 'created_at')
        ->count();

      //TOTALES//
      //PEDIDOS INDIVIDUALES
      $pedidos_totales = Pedido::query()->join('users as u', 'u.id', 'pedidos.user_id')
        ->where('pedidos.codigo', 'not like', "%-C%")->whereDate('pedidos.created_at', now())->count();
      //COBRANZAS TOTALES
      $total_pagado_cobranza = $this->applyFilterCustom(Pedido::query()
        ->where('codigo', 'not like', "%-C%")->activo()->pagados(), $date_pagos, 'created_at')
        ->count();
      $total_pedido_mespasado_cobranza = $this->applyFilterCustom(Pedido::query()
        ->where('codigo', 'not like', "%-C%")->activo(), $date_pagos, 'created_at')
        ->count();
      //PEDIDOS TOTALES
      $total_pedido_general = $this->applyFilterCustom(Pedido::query()
        ->where('codigo', 'not like', "%-C%")->activo(), now(), 'created_at')
        ->count();

      $item = [
        "identificador" => $asesor->identificador,
        "code" => "Asesor {$asesor->identificador}",
        "pedidos_dia" => $asesor_pedido_dia,
        "name" => $asesor->name,
        "total_pedido" => $total_pedido,
        "total_pedido_mespasado" => $total_pedido_mespasado,
        "total_pagado" => $total_pagado,
        "meta" => $metatotal,
        "meta_2" => $metatotal_2,
        "meta_cobro" => $metatotal_cobro,
        "pedidos_totales" => $pedidos_totales,
      ];

      if ($asesor->excluir_meta) {
        if ($metatotal_cobro > 0) {
          $p_pagos = round(($total_pedido_mespasado / $total_pagado) * 100, 2);
        } else {
          $p_pagos = 0;
        }
        if ($metatotal > 0) {
          $p_pedidos = round(($total_pedido / $metatotal) * 100, 2);
        } else {
          $p_pedidos = 0;
        }
        $item['progress_pagos'] = $p_pagos;
        $item['progress_pedidos'] = $p_pedidos;
      } else {
        $progressData[] = $item;
      }
    }

    $newData = [];
    $union = collect($progressData)->groupBy('identificador');
    foreach ($union as $identificador => $items) {
      foreach ($items as $item) {
        if (!isset($newData[$identificador])) {
          $newData[$identificador] = $item;
        } else {
          $newData[$identificador]['total_pedido'] += data_get($item, 'total_pedido');
          $newData[$identificador]['total_pedido_pasado'] += data_get($item, 'total_pedido_mespasado');
          $newData[$identificador]['total_pagado'] += data_get($item, 'total_pagado');
          $newData[$identificador]['meta'] += data_get($item, 'meta');
          $newData[$identificador]['meta_2'] += data_get($item, 'meta_2');
          $newData[$identificador]['meta_cobro'] += data_get($item, 'meta_cobro');
          $newData[$identificador]['pedidos_dia'] += data_get($item, 'pedidos_dia');

          $newData[$identificador]['pedidos_totales'] += data_get($item, 'pedidos_totales');
        }
      }
      $newData[$identificador]['name'] = collect($items)->map(function ($item) {
        return explode(" ", data_get($item, 'name'))[0];
      })->first();
    }
    $progressData = collect($newData)->values()->map(function ($item) {
      $all = data_get($item, 'total_pedido');
      $all_mespasado = data_get($item, 'total_pedido_mespasado');
      $pay = data_get($item, 'total_pagado');
      $allmeta = data_get($item, 'meta');
      $allmeta_2 = data_get($item, 'meta_2');
      $allmeta_cobro = data_get($item, 'meta_cobro');
      $pedidos_dia = data_get($item, 'pedidos_dia');

      $pedidos_totales = data_get($item, 'pedidos_totales');

      if ($pay > 0) {
        $p_pagos = round(($pay / $all_mespasado) * 100, 2);
      } else {
        $p_pagos = 0;
      }

      if ($allmeta > 0) {
        $p_pedidos = round(($all / $allmeta) * 100, 2);
      } else {
        $p_pedidos = 0;
      }

      if ($allmeta_2 > 0) {
        $p_pedidos_new = round(($all / $allmeta_2) * 100, 2);
      } else {
        $p_pedidos_new = 0;
      }

      if ($p_pedidos >= 100) {
        $item['progress_pedidos'] = $p_pedidos_new;
        $item['meta_new'] = 0;

      } else {
        $item['progress_pedidos'] = $p_pedidos;
        $item['meta_new'] = 1;
      }

      $item['progress_pagos'] = $p_pagos;
      $item['total_pedido'] = $all;
      $item['total_pedido_pasado'] = $all_mespasado;
      $item['pedidos_dia'] = $pedidos_dia;


      $item['pedidos_totales'] = $pedidos_totales;
      return $item;

    })->sortBy('progress_pedidos', SORT_NUMERIC, true);//->all();

    if($request->ii==1)
    {
      if($total_asesor%2==0)
      {
        $skip=0;
        $take=intval($total_asesor/2);
      }else{
        $skip=0;
        $take=intval($total_asesor/2)+1;
      }
      //return json_encode(array('skip'=>$skip,'take'=>$take)); 0  8
      $progressData->splice($skip,$take)->all();
      //$progressData=array_slice($progressData, 2);
      //return $progressData;
    }else if($request->ii==2)
    {
      if($total_asesor%2==0)
      {
        $skip=intval($total_asesor/2);
        $take=intval($total_asesor/2);
      }else{
        $skip=intval($total_asesor/2)+1;
        $take=intval($total_asesor/2);
      }
      //return json_encode(array('skip'=>$skip,'take'=>$take));  8   7
      $progressData->splice($skip,$take)->all();
      //$progressData=array_slice($progressData, 3);
      //return $progressData;
    }else if($request->ii==3){
      $progressData->all();
    }

    //aqui la division de  1  o 2

    $all = collect($progressData)->pluck('total_pedido')->sum();
    $all_mespasado = collect($progressData)->pluck('total_pedido_mespasado')->sum();
    $pay = collect($progressData)->pluck('total_pagado')->sum();
    $meta = collect($progressData)->pluck('meta')->sum();
    $meta_2 = collect($progressData)->pluck('meta_2')->sum();
    $meta_cobro = collect($progressData)->pluck('meta_cobro')->sum();
    $pedidos_dia = collect($progressData)->pluck('pedidos_dia')->sum();

    if ($meta > 0) {
      $p_pedidos = round(($all / $meta) * 100, 2);
    } else {
      $p_pedidos = 0;
    }

    if ($pay > 0) {
      $p_pagos = round(($pay / $all_mespasado) * 100, 2);
    } else {
      $p_pagos = 0;
    }

    $object_totales = [
      "progress_pedidos" => $p_pedidos,
      "progress_pagos" => $p_pagos,
      "total_pedido" => $all,
      "total_pedido_mespasado" => $all_mespasado,
      "total_pagado" => $pay,
      "meta" => $meta,
      "meta_2" => $meta_2,
      "meta_cobro" => $meta_cobro,
      "pedidos_dia" => $pedidos_dia
    ];
    $html='';
    if($request->ii==3)
    {
      $html .= '<table class="table tabla-metas_pagos_pedidos table-dark">';
      $html .= '<tbody>
              <tr>
                  <th></th>
                  <th></th>
                  <th>'.$object_totales['pedidos_dia'].'</th>
                  <th><x-bs-progressbar :progress="$data_noviembre->progress_pagos">
                                                    <span> <b>  '.$object_totales['progress_pagos'].'%</b> - '.$object_totales['total_pagado'].'/'.$object_totales['total_pedido_mespasado'].'</span>
                                                </x-bs-progressbar>'.' </th>
                  <th>';
      if($object_totales['progress_pedidos']<'100'){
        $html.='<x-bs-progressbar :progress="$data_noviembre->progress_pedidos">
                                                    <span> <b>  '.$object_totales['progress_pedidos'].'%</b> - '.$object_totales['total_pedido'].'/'.$object_totales['meta'].'</span>
                                                </x-bs-progressbar>';
      }else{
        $html.='<div class="progress">
                    <div class="progress-bar bg-info" role="progressbar"
                         style="width: '.$object_totales['progress_pedidos'].'%"
                         aria-valuenow="'.$object_totales['progress_pedidos'].'"
                         aria-valuemin="0"
                         aria-valuemax="100"></div>
                </div>
                <div class="position-absolute w-100 text-center" style="top: 0;font-size: 12px;">
                    <span> <b>  '.$object_totales['progress_pedidos'].'%</b> - '.$object_totales['total_pedido'].'/'.$object_totales['meta'].'</span>
                </div>';
      }

      $html.='</th>
              </tr>
              </tbody>';
      $html .= '</table>';
    }else if($request->ii==1 || $request->ii==2)
    {

      $html .= '<table class="table tabla-metas_pagos_pedidos table-dark">';
      $html .= '<thead>
                <tr>
                    <th width="10%">Asesor</th>
                    <th width="10%">Identificador</th>
                    <th width="10%"><span style="font-size:10px;">Pedidos del día</span></th>
                    <th width="35%">Cobranza </th>
                    <th width="35%">Pedidos </th>
                </tr>
                </thead>
                <tbody>';
      foreach ($progressData as $data) {
        $html.= '<tr>
             <td>' . $data["identificador"] . '</td>
             <td>' . $data["code"] . '</td>
             <td>';
                if ($data["pedidos_dia"] > 0) {
                  $html.=  '<span class="px-4 pt-1 pb-1 bg-white text-center justify-content-center w-100 rounded font-weight-bold"> ' . $data["pedidos_dia"] . '</span> ';
                } else {
                  $html.=  '<span class="px-4 pt-1 pb-1 bg-red text-center justify-content-center w-100 rounded font-weight-bold"> ' . $data["pedidos_dia"] . ' </span> ';
                }
        $html.=  '</td>';
        $html.= '<td>';
                  if ($data["progress_pagos"] == 100) {
                    $html.=  ' <div class="w-100 bg-white rounded">
                                    <div class="position-relative rounded">
                                      <div class="progress bg-white rounded" style="height: 40px">
                                          <div class="rounded" role="progressbar" style="background: #008ffb !important; width: ' . $data["progress_pagos"] . '%" aria-valuenow="34.25" aria-valuemin="0" aria-valuemax="100"></div>
                                          </div>
                                        <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                            <span style="font-weight: lighter"> <b style="font-weight: bold !important;font-size: 18px">  '. $data["progress_pagos"] . '% </b> - ' . $data["total_pagado"] . ' / ' . $data["total_pedido_mespasado"] . '</span>
                                        </div>
                                    </div>
                                    <sub class="d-none">% -  Pagados/ Asignados</sub>
                                  </div>';
          } elseif ($data["progress_pagos"] >= 80) {
                    $html.=  '<div class="w-100 bg-white rounded">
                                    <div class="position-relative rounded">
                                      <div class="progress bg-white rounded" style="height: 40px">
                                          <div class="rounded" role="progressbar" style="background: #8ec117 !important; width: ' . $data["progress_pagos"] . '%" aria-valuenow="34.25" aria-valuemin="0" aria-valuemax="100"></div>
                                          </div>
                                        <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                            <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  ' . $data["progress_pagos"] . '% </b> - '. $data["total_pagado"] . ' / ' . $data["total_pedido_mespasado"] . '</span>
                                        </div>
                                    </div>
                                    <sub class="d-none">% -  Pagados/ Asignados</sub>
                                  </div>';
          } elseif ($data["progress_pagos"] > 70) {
                    $html.=  '
                    <div class="w-100 bg-white rounded">
                                    <div class="position-relative rounded">
                                      <div class="progress bg-white rounded" style="height: 40px">
                                          <div class="rounded" role="progressbar" style="background: linear-gradient(90deg, rgba(255,193,7,1) 0%, rgba(255,193,7,1) 89%, rgba(113,193,27,1) 100%) !important; width: ' . $data["progress_pagos"] . '%" aria-valuenow="34.25" aria-valuemin="0" aria-valuemax="100"></div>
                                          </div>
                                        <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                            <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  ' . $data["progress_pagos"] . '% </b> - ' . $data["total_pagado"] . ' / ' . $data["total_pedido_mespasado"] . '</span>
                                        </div>
                                    </div>
                                    <sub class="d-none">% -  Pagados/ Asignados</sub>
                                  </div>';
          } elseif ($data["progress_pagos"] > 60) {
                    $html.=  ' <div class="w-100 bg-white rounded">
                                    <div class="position-relative rounded">
                                      <div class="progress bg-white rounded" style="height: 40px">
                                          <div class="rounded" role="progressbar" style="background: #ffc107 !important; width: ' . $data["progress_pagos"] . '%" aria-valuenow="34.25" aria-valuemin="0" aria-valuemax="100"></div>
                                          </div>
                                        <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                            <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  '. $data["progress_pagos"] . '% </b> - ' . $data["total_pagado"] . ' / '. $data["total_pedido_mespasado"] . '</span>
                                        </div>
                                    </div>
                                    <sub class="d-none">% -  Pagados/ Asignados</sub>
                                  </div>';
          } elseif ($data["progress_pagos"] > 50) {
                    $html.=  '<div class="w-100 bg-white rounded">
                                    <div class="position-relative rounded">
                                      <div class="progress bg-white rounded" style="height: 40px">
                                          <div class="rounded" role="progressbar" style="background: linear-gradient(90deg, rgba(220,53,69,1) 0%, rgba(194,70,82,1) 89%, rgba(255,193,7,1) 100%) !important; width: ' . $data["progress_pagos"] . '%" aria-valuenow="34.25" aria-valuemin="0" aria-valuemax="100"></div>
                                          </div>
                                        <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                            <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  ' . $data["progress_pagos"] . '% </b> - ' . $data["total_pagado"] . ' / ' . $data["total_pedido_mespasado"] . '</span>
                                        </div>
                                    </div>
                                    <sub class="d-none">% -  Pagados/ Asignados</sub>
                                  </div>';
          } else {
                    $html.=  '<div class="w-100 bg-white rounded">
                              <div class="position-relative rounded">
                                  <div class="progress bg-white rounded" style="height: 40px">
                                      <div class="rounded" role="progressbar" style="background: #dc3545 !important; width: ' . $data["progress_pagos"] . '%" aria-valuenow="34.25" aria-valuemin="0" aria-valuemax="100"></div>
                                      </div>
                                  <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                      <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  ' . $data["progress_pagos"] . '% </b> - ' . $data["total_pagado"] . ' / ' . $data["total_pedido_mespasado"] . '</span>
                                  </div>
                              </div>
                              <sub class="d-none">% -  Pagados/ Asignados</sub>
                            </div>';
          }
        $html.=  '</td>';
        $html.=  '   <td>';
                     if ($data["meta_new"] == 0) {
            if ($data["progress_pedidos"] == 100) {
              $html.=  '<div class="w-100 bg-white rounded">
                                    <div class="position-relative rounded">
                                      <div class="progress bg-white rounded" style="height: 40px">
                                          <div class="rounded" role="progressbar" style="background: #008ffb !important; width: ' . $data["progress_pedidos"] . '%" ></div>
                                          </div>
                                        <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                            <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  ' . $data["progress_pedidos"] . '% </b> - ' . $data["total_pedido"] . ' / '. $data["meta_2"] . '</span>
                                        </div>
                                    </div>
                                    <sub class="d-none">% -  Pagados/ Asignados</sub>
                                  </div>';
            } else {
              $html.=  '<div class="w-100 bg-white rounded">
                                    <div class="position-relative rounded">
                                      <div class="progress bg-white rounded" style="height: 40px">
                                          <div class="rounded" role="progressbar" style="background: #03af03 !important; font-size: 18px; width: ' . $data["progress_pedidos"] . '%" ></div>
                                          </div>
                                        <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                            <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px"">  ' . $data["progress_pedidos"] .'% </b> - ' . $data["total_pedido"] . ' / ' . $data["meta_2"] . '</span>
                                        </div>
                                    </div>
                                    <sub class="d-none">% -  Pagados/ Asignados</sub>
                                  </div>';
            }
          } if ($data["meta_new"] == 1){
            if ($data["progress_pedidos"] >= 95) {
              $html.=  '<div class="w-100 bg-white rounded">
                                    <div class="position-relative rounded">
                                      <div class="progress bg-white rounded" style="height: 40px">
                                          <div class="rounded" role="progressbar" style="background: linear-gradient(90deg, rgba(3,175,3,1) 0%, rgba(24,150,24,1) 60%, rgba(0,143,251,1) 100%) !important; width: '. $data["progress_pedidos"] . '%" ></div>
                                          </div>
                                        <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                            <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  ' . $data["progress_pedidos"] . '% </b> - ' . $data["total_pedido"] . ' / ' . $data["meta"] . '</span>
                                        </div>
                                    </div>
                                    <sub class="d-none">% -  Pagados/ Asignados</sub>
                                  </div>';
            } elseif ($data["progress_pedidos"] >= 70) {
              $html.=  '<div class="w-100 bg-white rounded">
                                    <div class="position-relative rounded">
                                      <div class="progress bg-white rounded" style="height: 40px">
                                          <div class="rounded" role="progressbar" style="background: linear-gradient(90deg, rgba(255,193,7,1) 0%, rgba(255,193,7,1) 89%, rgba(113,193,27,1) 100%) !important; width: ' . $data["progress_pedidos"] . '%" ></div>
                                          </div>
                                        <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                            <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  ' . $data["progress_pedidos"] . '% </b> - ' . $data["total_pedido"] . ' / ' . $data["meta"] . '</span>
                                        </div>
                                    </div>
                                    <sub class="d-none">% -  Pagados/ Asignados</sub>
                                  </div>';
            } elseif ($data["progress_pedidos"] >= 60) {
              $html.=  '<div class="w-100 bg-white rounded">
                                    <div class="position-relative rounded">
                                      <div class="progress bg-white rounded" style="height: 40px">
                                          <div class="rounded" role="progressbar" style="background: #ffc107 !important; width: ' . $data["progress_pedidos"] . '%" ></div>
                                          </div>
                                        <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                            <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  ' . $data["progress_pedidos"] . '% </b> - ' . $data["total_pedido"] . ' / '. $data["meta"] . '</span>
                                        </div>
                                    </div>
                                    <sub class="d-none">% -  Pagados/ Asignados</sub>
                                  </div>';
            } elseif ($data["progress_pedidos"] >= 50) {
              $html.=  '<div class="w-100 bg-white rounded">
                                    <div class="position-relative rounded">
                                      <div class="progress bg-white rounded" style="height: 40px">
                                          <div class="rounded" role="progressbar" style="background: linear-gradient(90deg, rgba(220,53,69,1) 0%, rgba(194,70,82,1) 89%, rgba(255,193,7,1) 100%) !important; width: '. $data["progress_pedidos"] . '%" ></div>
                                          </div>
                                        <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                            <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  ' . $data["progress_pedidos"] . '% </b> - ' . $data["total_pedido"] . ' / ' . $data["meta"] . '</span>
                                        </div>
                                    </div>
                                    <sub class="d-none">% -  Pagados/ Asignados</sub>
                                  </div>';

            } else {
              $html.=  '<div class="w-100 bg-white rounded">
                              <div class="position-relative rounded">
                                  <div class="progress bg-white rounded" style="height: 40px">
                                      <div class="rounded" role="progressbar" style="background: #dc3545;width: ' . $data["progress_pedidos"] . '%" ></div>
                                      </div>
                                  <div class="position-absolute rounded w-100 text-center" style="top: 0;font-size: 12px;">
                                      <span style="font-weight: lighter"> <b style="font-weight: bold !important; font-size: 18px">  ' . $data["progress_pedidos"] . '% </b> - ' . $data["total_pedido"] . ' /'. $data["meta"] . '</span>
                                  </div>
                              </div>
                              <sub class="d-none">% -  Pagados/ Asignados</sub>
                            </div>';
            }
          }
        $html.=  '  </td>
      </tr> ';
      }

      $html .= '</tbody>';

      $html .= '</table>';
    }

    return $html;
  }

  public static function applyFilterCustom($query, CarbonInterface $date = null, $column = 'created_at')

  {
    if ($date == null) {
      $date = now();
    }
    return $query->whereBetween($column, [
      $date->clone()->startOfMonth(),
      $date->clone()->endOfMonth()->endOfDay()
    ]);
  }

}
