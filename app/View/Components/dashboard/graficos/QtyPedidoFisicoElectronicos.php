<?php

namespace App\View\Components\dashboard\graficos;

use App\Abstracts\Widgets;
use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QtyPedidoFisicoElectronicos extends Widgets
{
  /**
   * Create a new component instance.
   *
   * @return void
   */
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Get the view / contents that represent the component.
   *
   * @return \Illuminate\Contracts\View\View|\Closure|string
   */
  public function render()
  {
    $resultados = $this->jsConfig();
    return view('components.dashboard.graficos.qty-pedido-fisico-electronicos', compact('resultados'));
  }

  public function jsConfig()
  {
    if (auth()->user()->rol == User::ROL_JEFE_OPERARIO) {
      $jefesOpe = User::activo()
        ->where('rol', '=', User::ROL_JEFE_OPERARIO)
        ->where('id', '=', Auth::user()->id)->get();
      $dataFi = [];
      $dataEl = [];
      $dataAn = [];

    } else {

      $pedidosAtendidosFisicos = Pedido::query()
        ->activo()
        ->porAtenderEstatus()
        ->whereIn(
          'id',
          DetallePedido::query()->select('pedido_id')
            ->activo()
            ->whereRaw('detalle_pedidos.pedido_id=pedidos.id')
            ->where('detalle_pedidos.tipo_banca', 'like', '%FISICO%')
        )
        ->count();

      $pedidosAtendidosElectronica = Pedido::query()
        ->activo()
        ->porAtenderEstatus()
        ->whereIn(
          'id',
          DetallePedido::query()->select('pedido_id')
            ->activo()
            ->whereRaw('detalle_pedidos.pedido_id=pedidos.id')
            ->where('detalle_pedidos.tipo_banca', 'like', '%ELECTRONICA%')
        )
        ->count();

      $pedidosPendienteAnulacion = Pedido::query()
        ->pendienteAnulacion()
        ->join('users as u', 'pedidos.user_id', 'u.id')
        ->whereNotNull('pedidos.user_anulacion_id')
        ->whereBetween('pedidos.created_at', [now()->startOfMonth()->format('Y-m-d'), now()->endOfMonth()->format('Y-m-d')])
        ->count();

      $jefesOpe = User::activo()
        ->where('rol', '=', User::ROL_JEFE_OPERARIO)->get();
      $dataFi = [
        [
          "count" => $pedidosAtendidosFisicos,
          "title" => "Total",
          'bg' => '#00bcd4',
          'color' => 'white',
        ]
      ];
      $dataEl = [[
        "count" => $pedidosAtendidosElectronica,
        "title" => "Total",
        'bg' => '#e91e63',
        'color' => 'white',
      ]];
      $dataAn = [[
        "count" => $pedidosPendienteAnulacion,
        "title" => "Total",
        'bg' => '#8ec117',
        'color' => 'white',
      ]];
    }

    foreach ($jefesOpe as $user) {
      $operario = User::activo()
        ->where('rol', '=', User::ROL_OPERARIO)
        ->where('jefe', $user->id)
        ->pluck('id');
      $asesores = User::activo()
        ->whereIn('rol', [User::ROL_ASESOR, User::ROL_ASESOR_ADMINISTRATIVO])
        ->whereIn('operario', $operario)
        ->pluck('id');
      $fi = Pedido::query()
        ->activo()
        ->porAtenderEstatus()
        ->whereIn('user_id', $asesores)
        ->whereIn(
          'id',
          DetallePedido::query()->select('pedido_id')
            ->activo()
            ->whereRaw('detalle_pedidos.pedido_id=pedidos.id')
            ->where('detalle_pedidos.tipo_banca', 'like', '%FISICO%')
        )->count();

      $el = Pedido::query()
        ->activo()
        ->whereIn('user_id', $asesores)
        ->porAtenderEstatus()
        ->whereIn(
          'id',
          DetallePedido::query()->select('pedido_id')
            ->activo()
            ->whereRaw('detalle_pedidos.pedido_id=pedidos.id')
            ->where('detalle_pedidos.tipo_banca', 'like', '%ELECTRONICA%')
        )
        ->count();

      $an = Pedido::query()
        ->pendienteAnulacion()
        ->whereIn('user_id', $asesores)
        ->join('users as u', 'pedidos.user_id', 'u.id')
        ->whereNotNull('pedidos.pendiente_anulacion')
        /*->whereYear('pedidos.created_at', now()->format('Y'))*/
        /*->whereMonth('pedidos.created_at', now()->format('m'))*/
        ->whereBetween('pedidos.created_at', [now()->startOfMonth(), now()->endOfMonth()])
        ->count();

      $dataFi[] = [
        "title" => $user->name,
        "count" => $fi,
        "bg" => '#7af0ff',
        'color' => 'black',
      ];
      $dataEl[] = [
        "title" => $user->name,
        "count" => $el,
        'bg' => '#ff97ba',
        'color' => 'black',
      ];
      $dataAn[] = [
        "title" => $user->name,
        "count" => $an,
        'bg' => '#C1D424',
        'color' => 'black',
      ];
    }

    return [
      "fisico" => $dataFi,
      "electronic" => $dataEl,
      "anulado" => $dataAn,
    ];
  }
}
