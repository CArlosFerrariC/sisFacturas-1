<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GrupoPedido extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = ['id'];

    public function pedidos()
    {
        return $this->belongsToMany(Pedido::class, 'grupo_pedido_items')->withPivot([
            'razon_social',
            'codigo',
        ])->orderByPivot('razon_social', 'asc');
    }

    public static function createGroupByPedido(Pedido $pedido, $createAnother = false, $attach = false)
    {
        $grupo = self::createGroupByArray([
            "zona" => $pedido->env_zona,
            "provincia" => $pedido->env_destino,
            'distrito' => $pedido->env_distrito,
            //'direccion' => $pedido->env_direccion,
            //'referencia' => $pedido->env_referencia,
            'direccion' => (($pedido->destino == 'PROVINCIA') ? 'OLVA' : $pedido->direccion),
            'referencia' => (($pedido->destino == 'PROVINCIA') ? $pedido->tracking : $pedido->referencia),
            'cliente_recibe' => $pedido->env_nombre_cliente_recibe,
            'telefono' => $pedido->env_celular_cliente_recibe,
        ], $createAnother);
        if ($attach) {
            $detalle = $pedido->detallePedidos()->orderBy('detalle_pedidos.created_at')->first();
            \DB::table('grupo_pedido_items')->where('pedido_id',$pedido->id)->delete();
            $grupo->pedidos()->syncWithoutDetaching([
                $pedido->id => [
                    "codigo" => $pedido->codigo,
                    "razon_social" => $detalle->nombre_empresa,
                ]
            ]);
            if ($pedido->condicion_envio_code != Pedido::RECEPCION_COURIER_INT) {
                $pedido->update([
                    'condicion_envio_code' => Pedido::RECEPCION_COURIER_INT,
                    'condicion_envio' => Pedido::RECEPCION_COURIER,
                    'condicion_envio_at'=>now(),
                    //'estado_sobre' => 1,
                ]);
            }
        }
        return $grupo;
    }

    public static function createGroupByArray($array, $createAnother = false)
    {
        $zona = \Str::lower(data_get($array, 'zona') ?? '');
        if ($zona == 'olva') {
            $data = [
                "zona" => data_get($array, 'zona'),
            ];
            $data2 = [
                "provincia" => 'OLVA',//LIMA
                'distrito' => 'OLVA',//LOS OLIVOS
                'direccion' => 'OLVA',//olva
                'cliente_recibe' => 'OLVA',//olva
                'referencia' => '--',//olva
                'telefono' => '--',//n/a
            ];
            /*if ($createAnother) {
                return GrupoPedido::create(array_merge($data, $data2));
            }*/
        } else {
            $distrito = Distrito::query()
                ->where('distrito', '=', data_get($array, 'distrito'))
                ->whereIn('provincia', ['LIMA', 'CALLAO'])
                ->first();
            $data = [
                "zona" => data_get($array, 'zona') ?? 'n/a',
                "provincia" => optional($distrito)->provincia ?? data_get($array, 'provincia') ?? 'n/a',//LIMA
                'distrito' => optional($distrito)->distrito ?? data_get($array, 'distrito') ?? 'n/a',//LOS OLIVOS
                'direccion' => data_get($array, 'direccion') ?: 'n/a',//olva
                'cliente_recibe' => data_get($array, 'cliente_recibe') ?? 'n/a',//olva
            ];
            $data2 = [
                'referencia' => data_get($array, 'referencia') ?: 'n/a',//olva
                'telefono' => data_get($array, 'telefono') ?? 'n/a',//n/a
            ];
            if ($createAnother) {
                return GrupoPedido::create(array_merge($data, $data2));
            }
        }
        return GrupoPedido::updateOrCreate($data, $data2);
    }

    public static function desvincularPedido(Pedido $pedido, $asignarOtro = false, $attach = false)
    {
        $grupopedido = GrupoPedido::query()
            ->select('grupo_pedidos.*')
            ->join('grupo_pedido_items', 'grupo_pedido_items.grupo_pedido_id', 'grupo_pedidos.id')
            ->where('grupo_pedido_items.pedido_id', $pedido->id)
            ->get();
        foreach ($grupopedido as $grupop) {
            if ($grupop->pedidos()->count() > 1) {
                $grupop->pedidos()->detach($pedido->id);
            } else {
                $grupop->delete();
            }
        }
        if ($asignarOtro) {
            return self::createGroupByPedido($pedido, true, $attach);
        }
        return true;
    }

    public function motorizadoHistories()
    {
        return $this->hasMany(PedidoMotorizadoHistory::class, 'pedido_grupo_id')->orderByDesc('pedido_motorizado_histories.created_at');
    }
}
