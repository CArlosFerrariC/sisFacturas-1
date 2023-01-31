<?php

namespace App\Exports\Templates\Sheets\Envios;

use App\Abstracts\Export;
use App\Exports\Templates\Sheets\AfterSheet;
use App\Exports\Templates\Sheets\Fill;
use App\Models\Cliente;
use App\Models\DetallePedido;
use App\Models\ListadoResultado;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Sheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Illuminate\Http\Request;

Sheet::macro('styleCells', function (Sheet $sheet, string $cellRange, array $style) {
    $sheet->getDelegate()->getStyle($cellRange)->applyFromArray($style);
});

class PageclienteCuatromesesNodeben extends Export implements WithColumnFormatting,WithColumnWidths
{
    public function collection()
    {
        $ultimos_pedidos=Cliente::activo()
            ->select([
                'clientes.id',
                'clientes.tipo',
                DB::raw("(select dp1.pago from pedidos dp1 where dp1.estado=1 and dp1.cliente_id=clientes.id order by dp1.created_at desc limit 1) as pagoultimopedido"),
                DB::raw("(select dp1.pagado from pedidos dp1 where dp1.estado=1 and dp1.cliente_id=clientes.id order by dp1.created_at desc limit 1) as pagadoultimopedido"),
                DB::raw("(select dp1.codigo from pedidos dp1 where dp1.estado=1 and dp1.cliente_id=clientes.id order by dp1.created_at desc limit 1) as codigoultimopedido"),
                DB::raw("(select DATE_FORMAT(dp1.created_at,'%Y-%m-%d') from pedidos dp1 where dp1.estado=1 and dp1.cliente_id=clientes.id order by dp1.created_at desc limit 1) as fechaultimopedido"),
                DB::raw("(select DATE_FORMAT(dp1.created_at,'%m') from pedidos dp1 where dp1.estado=1 and dp1.cliente_id=clientes.id order by dp1.created_at desc limit 1) as fechaultimopedido_mes"),
                DB::raw("(select DATE_FORMAT(dp1.created_at,'%Y') from pedidos dp1 where dp1.estado=1 and dp1.cliente_id=clientes.id order by dp1.created_at desc limit 1) as fechaultimopedido_anio"),
                DB::raw("(select dp1.pago from pedidos dp1 where dp1.estado=1 and dp1.cliente_id=clientes.id order by dp1.created_at desc limit 1) as fechaultimopedido_pago"),
                DB::raw("(select dp1.pagado from pedidos dp1 where dp1.estado=1 and dp1.cliente_id=clientes.id order by dp1.created_at desc limit 1) as fechaultimopedido_pagado"),
            ])->get();

        //$ultimos_pedidos
        foreach ($ultimos_pedidos as $procesada)
        {

        }

        $dosmeses_ini=[];
        for($i=4;$i>0;$i--)
        {
           /* 4 setiembnre 09
            3 octube 10
            2 noviembre 11
            1  diciembre 12*/
            $dosmeses_ini[]=  now()->startOfMonth()->subMonths($i)->format('Y-m');
        }
        /*
         * 2022-09,2022-10,2022-11,2022-12*/

        $lista=[];
        foreach ($ultimos_pedidos as $procesada){
            if($procesada->fechaultimopedido!=null)
            {
                //2022-09-02
                $fecha_analizar=Carbon::parse($procesada->fechaultimopedido)->format('Y-m');//2022-09
                if(in_array($fecha_analizar,$dosmeses_ini))
                {
                    if( in_array($procesada->pagadoultimopedido,["2"]) )
                    {
                        {
                            $lista[]=$procesada->id;
                        }
                    }
                }
            }
        }

        $data=Cliente::
        join('users as u','u.id','clientes.user_id')
            ->whereIn("clientes.id",$lista)
            ->select([
                'clientes.id as item',
                DB::raw("concat(u.identificador,' ',ifnull(u.letra,'') ) as asesor_identificador"),
                DB::raw("concat(clientes.celular,'-',clientes.icelular)  as celular"),
                DB::raw("(select group_concat(r.num_ruc) from rucs r where r.cliente_id=clientes.id) as rucs"),
                DB::raw("(select case when dp1.pagado=0 then 'DEUDA'
                                        when dp1.pagado=1 then 'DEUDA'
                                        else 'NO DEUDA' end from pedidos dp1
                                        where dp1.estado=1 and dp1.cliente_id=clientes.id order by dp1.created_at desc limit 1) as deuda"),
                DB::raw("(select dp2.saldo from pedidos a inner join detalle_pedidos dp2 on a.id=dp2.pedido_id
                                        where dp2.estado=1 and a.cliente_id=clientes.id order by dp2.created_at desc limit 1) as importeultimopedido"),
                DB::raw("(select DATE_FORMAT(dp3.created_at,'%m') from pedidos a inner join detalle_pedidos dp3 on a.id=dp3.pedido_id
                                        where dp3.estado=1 and a.cliente_id=clientes.id order by dp3.created_at desc limit 1) as mesultimopedido"),
            ]);
        $data=$data->where("deuda","NO DEUDA");

        if (Auth::user()->rol == User::ROL_LLAMADAS) {

            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.llamada', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');
            $data = $data->WhereIn("u.identificador", $usersasesores);

        }elseif (Auth::user()->rol == User::ROL_ASESOR) {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.identificador', Auth::user()->identificador)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');
            $data = $data->WhereIn("u.identificador", $usersasesores);
        }else if (Auth::user()->rol == User::ROL_ENCARGADO) {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.supervisor', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $data = $data->WhereIn("u.identificador", $usersasesores);
        }elseif (Auth::user()->rol == User::ROL_ASESOR_ADMINISTRATIVO) {
            $data = $data->Where("u.identificador", '=', 'B');
        }elseif (Auth::user()->rol == "Operario") {
        $asesores = User::whereIN('users.rol', ['Asesor', 'Administrador', 'ASESOR ADMINISTRATIVO'])
            ->where('users.estado', '1')
            ->Where('users.operario', Auth::user()->id)
            ->select(
                DB::raw("users.identificador as identificador")
            )
            ->pluck('users.identificador');
        $pedidos = $data->WhereIn('u.identificador', $asesores);

        }

        return $data->get();
    }
    public function fields(): array
    {
        return [
            "item"=>"Item"
            ,"asesor_identificador"=>"Asesor"
            ,"celular"=>"Celular"
            ,"rucs"=>"Rucs"
            ,"deuda"=>"Deuda"
            ,"importeultimopedido"=>"Importe ultimo pedido"
            ,"mesultimopedido"=>"Mes ultimo pedido",
        ];
    }
    public function columnWidths(): array
    {
        return [
            'A' => 8//item
            ,'B' => 8//identificador
            ,'C' => 8//celular
            ,'D' => 8//rucs
            ,'E' => 8//deuda
            ,'F' => 8//importe
            ,'G' => 8//mes
        ];
    }
    public function columnFormats(): array
    {
        return [
            //Formato de las columnas segun la letra
            /*
             'D' => NumberFormat::FORMAT_DATE_YYYYMMDD,
             'E' => NumberFormat::FORMAT_DATE_YYYYMMDD,
            */
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'G' => NumberFormat::FORMAT_TEXT,

        ];
    }
    public function title(): string
    {
        return 'Cuatro meses sin pedir- No deben';
    }
    public function map($model): array
    {
        //$model->Periodo=strval(str_pad($model->Periodo,2,"0"));
        return parent::map($model);
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => [self::class, 'afterSheet']
        ];
    }

    public static function afterSheet(AfterSheet $event){

        $event->sheet->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

        $event->sheet->styleCells(
            'B1:G1',
            [
                'borders' => [
                    'outline' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                        'color' => ['argb' => 'FFFF0000'],
                    ],
                ]
            ]
        );


    }
}
