<?php

namespace App\Exports;

use App\Models\Pedido;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Support\Facades\DB;

class MisPedidosExport implements FromView
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function view(): View
    {
        $pedidos = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')//PEDIDOS CON PAGOS
            ->join('users as u', 'pedidos.user_id', 'u.id')
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->join('pago_pedidos as pp', 'pedidos.id','pp.pedido_id')
            ->join('pagos as pa', 'pp.pago_id', 'pa.id')
            ->join('detalle_pagos as dpa', 'pa.id', 'dpa.pago_id')
            ->select(
                'pedidos.id',
                'pedidos.creador as creador',
                DB::raw('DATE_FORMAT(pedidos.updated_at, "%d/%m/%Y") as fecha_mod'),
                'pedidos.modificador',
                'u.name as asesor_nombre',
                'u.identificador as asesor_identificador',
                DB::raw('DATE_FORMAT(pedidos.created_at, "%d/%m/%Y") as fecha'),
                'pedidos.codigo as codigos',
                'c.nombre as nombres',
                'c.celular as celulares',
                'dp.nombre_empresa as empresas',
                'dp.mes as mes',
                'dp.ruc as ruc',
                'dp.cantidad as cantidad',
                'dp.tipo_banca as tipo',
                'dp.porcentaje as porcentaje',
                DB::raw('sum(dp.cantidad*dp.porcentaje) as importe'),
                'dp.courier as courier',
                'dp.total as total',
                'dp.cant_compro as cant_compro',
                'u.operario as operario',
                'pedidos.condicion as estado_pedido',
                'pedidos.condicion_envio as estado_envio',
                'pa.id as pago_id',
                'pa.created_at as fecha_pago',
                DB::raw('MAX(DATE_FORMAT(dpa.created_at, "%d/%m/%Y")) as fecha_ult_pago'),
                'pa.condicion as estado_pago',
                'pa.diferencia', 
                DB::raw('DATE_FORMAT(pa.fecha_aprobacion, "%d/%m/%Y") as fecha_aprobacion'),
                'pedidos.responsable as responsable',
                'pedidos.motivo as motivo',
                'pedidos.estado'
                /* 'pedidos.created_at as fecha', */
                /* DB::raw('sum(dp.cantidad*dp.porcentaje) as total'),*/
                /* DB::raw('sum(dp.total) as total'), */
                /* 'pedidos.updated_at as fecha_mod', */
            )
            ->where('pedidos.estado', '1')
            ->where('dp.estado', '1')
            ->where('pedidos.pago', '1')
            ->where('pa.estado', '1')
            ->where('u.id', Auth::user()->id)
            ->groupBy(
                'pedidos.id',
                'pedidos.creador',
                'pedidos.updated_at',
                'pedidos.modificador',
                'u.name',
                'u.identificador',
                'pedidos.created_at',
                'pedidos.codigo',
                'c.nombre',
                'c.celular',
                'dp.nombre_empresa',
                'dp.mes',
                'dp.ruc',
                'dp.cantidad',
                'dp.tipo_banca',
                'dp.porcentaje',
                'dp.courier',
                'dp.total',
                'dp.cant_compro',
                'u.operario',
                'pedidos.condicion',
                'pedidos.condicion_envio',
                'pa.id',
                'pa.created_at',
                'pa.condicion',
                'pa.diferencia',  
                'pa.fecha_aprobacion',
                'pedidos.responsable',
                'pedidos.motivo',
                'pedidos.estado'
                )
            ->orderBy('pedidos.created_at', 'DESC')
            ->get();

            $pedidos2 = Pedido::join('clientes as c', 'pedidos.cliente_id', 'c.id')//PEDIDOS SIN PAGOS
            ->join('users as u', 'pedidos.user_id', 'u.id')
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->select(
                'pedidos.id',
                'pedidos.creador as creador',
                DB::raw('DATE_FORMAT(pedidos.updated_at, "%d/%m/%Y") as fecha_mod'),
                'pedidos.modificador',
                'u.name as asesor_nombre',
                'u.identificador as asesor_identificador',
                DB::raw('DATE_FORMAT(pedidos.created_at, "%d/%m/%Y") as fecha'),
                'pedidos.codigo as codigos',
                'c.nombre as nombres',
                'c.celular as celulares',
                'dp.nombre_empresa as empresas',
                'dp.mes as mes',
                'dp.ruc as ruc',
                'dp.cantidad as cantidad',
                'dp.tipo_banca as tipo',
                'dp.porcentaje as porcentaje',
                DB::raw('sum(dp.cantidad*dp.porcentaje) as importe'),
                'dp.courier as courier',
                'dp.total as total',
                'dp.cant_compro as cant_compro',
                'u.operario as operario',
                'pedidos.condicion as estado_pedido',
                'pedidos.condicion_envio as estado_envio',
                'pedidos.responsable as responsable',
                'pedidos.motivo as motivo',
                'pedidos.estado'
            )
            ->where('pedidos.estado', '1')
            ->where('dp.estado', '1')
            ->where('u.id', Auth::user()->id)
            ->whereIn('pedidos.condicion', ['POR ATENDER', 'EN PROCESO ATENCION', 'ATENDIDO'])
            ->where('pedidos.pago', '0')
            ->groupBy(
                'pedidos.id',
                'pedidos.creador',
                'pedidos.updated_at',
                'pedidos.modificador',
                'u.name',
                'u.identificador',
                'pedidos.created_at',
                'pedidos.codigo',
                'c.nombre',
                'c.celular',
                'dp.nombre_empresa',
                'dp.mes',
                'dp.ruc',
                'dp.cantidad',
                'dp.tipo_banca',
                'dp.porcentaje',
                'dp.courier',
                'dp.total',
                'dp.cant_compro',
                'u.operario',
                'pedidos.condicion',
                'pedidos.condicion_envio',                
                'pedidos.responsable',
                'pedidos.motivo',
                'pedidos.estado'
                )
            ->orderBy('pedidos.created_at', 'DESC')
            ->get();

        return view('pedidos.excel.mispedidos', compact('pedidos', 'pedidos2'));
    }
}