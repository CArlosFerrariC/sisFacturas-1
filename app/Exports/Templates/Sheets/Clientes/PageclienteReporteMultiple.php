<?php

namespace App\Exports\Templates\Sheets\Clientes;

use App\Abstracts\Export;
use App\Exports\Templates\Sheets\AfterSheet;
use App\Exports\Templates\Sheets\Fill;
use App\Models\Cliente;
use App\Models\ListadoResultado;
use App\Models\Pedido;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Illuminate\Http\Request;

class PageclienteReporteMultiple extends Export implements WithColumnFormatting,WithColumnWidths
{
    public function __construct($situacion,$anio)
    {
        parent::__construct();
        self::$situacion=$situacion;
        self::$anio=$anio;
    }
    public function collection()
    {
        $clientes=Cliente::activo()
            ->join('users as u', 'clientes.user_id', 'u.id')
            ->select([
                'clientes.id',
                'clientes.tipo',
                'u.identificador as asesor',
                'clientes.nombre',
                'clientes.dni',
                'clientes.icelular',
                'clientes.celular',
                'clientes.provincia',
                'clientes.distrito',
                'clientes.direccion',
                'clientes.referencia',
                'clientes.estado',
                'clientes.deuda',
                DB::raw(" (CASE WHEN clientes.deuda=1 then 'DEBE' else 'CANCELADO' end) as deposito "),
                'clientes.pidio',
                DB::raw("(select DATE_FORMAT(dp1.created_at,'%d-%m-%Y %h:%i:%s') from pedidos dp1 where dp1.cliente_id=clientes.id order by dp1.created_at desc limit 1) as fecha"),
                DB::raw("(select DATE_FORMAT(dp2.created_at,'%m') from pedidos dp2 where dp2.cliente_id=clientes.id and dp2.estado=1 order by dp2.created_at desc limit 1) as fechaultimopedido_mes"),
                DB::raw("(select DATE_FORMAT(dp3.created_at,'%Y') from pedidos dp3 where dp3.cliente_id=clientes.id and dp3.estado=1 order by dp3.created_at desc limit 1) as fechaultimopedido_anio"),
                DB::raw(" (select (dp.codigo) from pedidos dp where dp.cliente_id=clientes.id and dp.estado=1 order by dp.created_at desc limit 1) as codigo "),
                'clientes.situacion',
                DB::raw("(select dp1.pago from pedidos dp1 where dp1.estado=1 and dp1.cliente_id=clientes.id order by dp1.created_at desc limit 1) as fechaultimopedido_pago"),
                DB::raw("(select dp1.pagado from pedidos dp1 where dp1.estado=1 and dp1.cliente_id=clientes.id order by dp1.created_at desc limit 1) as fechaultimopedido_pagado"),
                DB::raw("(select (r.porcentaje) from porcentajes r where r.cliente_id=clientes.id and r.nombre='FISICO - sin banca' limit 1) as porcentajefsb"),
                DB::raw("(select (r.porcentaje) from porcentajes r where r.cliente_id=clientes.id and r.nombre='FISICO - banca' limit 1) as porcentajefb"),
                DB::raw("(select (r.porcentaje) from porcentajes r where r.cliente_id=clientes.id and r.nombre='ELECTRONICA - sin banca' limit 1) as porcentajeesb"),
                DB::raw("(select (r.porcentaje) from porcentajes r where r.cliente_id=clientes.id and r.nombre='ELECTRONICA - banca' limit 1) as porcentajeeb"),
            ])
            ->where('clientes.estado','1')
            ->where('clientes.tipo','1')
            ->whereNotNull('clientes.situacion');
        if(self::$situacion)
        {
            switch(self::$situacion)
            {
                case 'ABANDONO':
                    $clientes=$clientes->whereIn('clientes.situacion',['ABANDONO','ABANDONO RECIENTE']);
                    break;
                case 'RECURENTE':
                    $clientes=$clientes->whereIn('clientes.situacion',['RECURRENTE']);
                    break;
                case 'NUEVO':
                    $clientes=$clientes->whereIn('clientes.situacion',['NUEVO']);
                    break;
                case 'RECUPERADO':
                    $clientes=$clientes->whereIn('clientes.situacion',['RECUPERADO']);
                    break;
                case 'RECUPERADO ABANDONO':
                    $clientes=$clientes->whereIn('clientes.situacion',['RECUPERADO ABANDONO']);
                    break;
                case 'RECUPERADO RECIENTE':
                    $clientes=$clientes->whereIn('clientes.situacion',['RECUPERADO RECIENTE']);
                    break;
                case 'ABANDONO RECIENTE':
                    $clientes=$clientes->whereIn('clientes.situacion',['ABANDONO RECIENTE']);
                    break;
                default:break;
            }
        }

        if (Auth::user()->rol == "Llamadas")
        {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.llamada', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');
            $clientes = $clientes->WhereIn("u.identificador", $usersasesores);
        }
        elseif (Auth::user()->rol == "Asesor")
        {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.identificador', Auth::user()->identificador)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');
            $clientes = $clientes->WhereIn("u.identificador", $usersasesores);
        }
        else if (Auth::user()->rol == "Encargado")
        {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.supervisor', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');
            $clientes = $clientes->WhereIn("u.identificador", $usersasesores);
        }
        return $clientes->get();
    }
    public function fields(): array
    {
        return [
            "item"=>"Item"
            ,"id"=>"Asesor"
            ,"asesor"=>"Celular"
            ,"nombre"=>"Rucs"
            ,"dni"=>"Deuda"
            ,"celular"=>"Deuda"
            ,"icelular"=>"Deuda"
            ,"provincia"=>"Deuda"
            ,"distrito"=>"Deuda"
            ,"direccion"=>"Deuda"
            ,"referencia"=>"Deuda"
            ,"porcentajefsb"=>"Deuda"
            ,"porcentajefb"=>"Deuda"
            ,"porcentajeesb"=>"Deuda"
            ,"porcentajeeb"=>"Deuda"
            ,"porcentajeeb"=>"Deuda"
            ,"deuda"=>"Importe ultimo pedido"
            ,"deposito"=>"Mes ultimo pedido"
            ,"fecha"=>"Mes ultimo pedido"
            ,"fecha_dia"=>"Mes ultimo pedido"
            ,"fecha_mes"=>"Mes ultimo pedido"
            ,"fecha_anio"=>"Mes ultimo pedido"
            ,"codigo"=>"Mes ultimo pedido"
            ,"situacion"=>"Mes ultimo pedido"
            ,"estadopedido"=>"Mes ultimo pedido"
            ,"pidio"=>"Mes ultimo pedido"
            ,"estado"=>"Mes ultimo pedido"
            ,"eneroa"=>"Mes ultimo pedido","enerob"=>"Mes ultimo pedido"
            ,"febreroa"=>"Mes ultimo pedido","febrerob"=>"Mes ultimo pedido"
            ,"marzoa"=>"Mes ultimo pedido","marzob"=>"Mes ultimo pedido"
            ,"abrila"=>"Mes ultimo pedido","abrilb"=>"Mes ultimo pedido"
            ,"mayoa"=>"Mes ultimo pedido","mayob"=>"Mes ultimo pedido"
            ,"junioa"=>"Mes ultimo pedido","juniob"=>"Mes ultimo pedido"
            ,"julioa"=>"Mes ultimo pedido","juliob"=>"Mes ultimo pedido"
            ,"agostoa"=>"Mes ultimo pedido","agostob"=>"Mes ultimo pedido"
            ,"setiembrea"=>"Mes ultimo pedido","setiembreb"=>"Mes ultimo pedido"
            ,"octubrea"=>"Mes ultimo pedido","octubreb"=>"Mes ultimo pedido"
            ,"noviembrea"=>"Mes ultimo pedido","noviembreb"=>"Mes ultimo pedido"
            ,"diciembrea"=>"Mes ultimo pedido","diciembreb"=>"Mes ultimo pedido"
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
            ,'H' => 8//mes
            ,'I' => 8//mes
            ,'J' => 8//mes
            ,'K' => 8//mes
            ,'L' => 8//mes
            ,'M' => 8//mes
            ,'N' => 8//mes
            ,'O' => 8//mes
            ,'P' => 8//mes
            ,'Q' => 8//mes
            ,'R' => 8//mes
            ,'S' => 8//mes
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

        ];
    }
    public function title(): string
    {
        return 'CLIENTES SITUACION '.(self::$anio).' '.(self::$anio+1). ' :: '.(self::$situacion);
    }
    public function map($model): array
    {
        $model->anioa=self::$anio;
        $model->aniob=( intval(self::$anio) +1);
        $model->eneroa=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->anioa)
            ->where(DB::raw('MONTH(created_at)'), '1')
            ->count();
        $model->enerob=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->aniob)
            ->where(DB::raw('MONTH(created_at)'), '1')
            ->count();
        $model->febreroa=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->anioa)
            ->where(DB::raw('MONTH(created_at)'), '2')
            ->count();
        $model->febrerob=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->aniob)
            ->where(DB::raw('MONTH(created_at)'), '2')
            ->count();
        $model->marzoa=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->anioa)
            ->where(DB::raw('MONTH(created_at)'), '3')
            ->count();
        $model->marzob=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->aniob)
            ->where(DB::raw('MONTH(created_at)'), '3')
            ->count();
        $model->abrila=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->anioa)
            ->where(DB::raw('MONTH(created_at)'), '4')
            ->count();
        $model->abrilb=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->aniob)
            ->where(DB::raw('MONTH(created_at)'), '4')
            ->count();
        $model->mayoa=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->anioa)
            ->where(DB::raw('MONTH(created_at)'), '5')
            ->count();
        $model->mayob=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->aniob)
            ->where(DB::raw('MONTH(created_at)'), '5')
            ->count();
        $model->junioa=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->anioa)
            ->where(DB::raw('MONTH(created_at)'), '6')
            ->count();
        $model->juniob=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->aniob)
            ->where(DB::raw('MONTH(created_at)'), '6')
            ->count();
        $model->julioa=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->anioa)
            ->where(DB::raw('MONTH(created_at)'), '7')
            ->count();
        $model->juliob=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->aniob)
            ->where(DB::raw('MONTH(created_at)'), '7')
            ->count();
        $model->agostoa=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->anioa)
            ->where(DB::raw('MONTH(created_at)'), '8')
            ->count();
        $model->agostob=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->aniob)
            ->where(DB::raw('MONTH(created_at)'), '8')
            ->count();
        $model->setiembrea=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->anioa)
            ->where(DB::raw('MONTH(created_at)'), '9')
            ->count();
        $model->setiembreb=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->aniob)
            ->where(DB::raw('MONTH(created_at)'), '9')
            ->count();
        $model->octubrea=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->anioa)
            ->where(DB::raw('MONTH(created_at)'), '10')
            ->count();
        $model->octubreb=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->aniob)
            ->where(DB::raw('MONTH(created_at)'), '10')
            ->count();
        $model->noviembrea=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->anioa)
            ->where(DB::raw('MONTH(created_at)'), '11')
            ->count();
        $model->noviembreb=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->aniob)
            ->where(DB::raw('MONTH(created_at)'), '11')
            ->count();
        $model->diciembrea=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->anioa)
            ->where(DB::raw('MONTH(created_at)'), '12')
            ->count();
        $model->diciembreb=Pedido::where('estado', '1')->where('cliente_id', $model->id)
            ->whereYear(DB::raw('Date(created_at)'), $model->aniob)
            ->where(DB::raw('MONTH(created_at)'), '12')
            ->count();

        return parent::map($model);
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => [self::class, 'afterSheet']
        ];
    }

    public static function afterSheet(AfterSheet $event){

        $color_cabeceras='a9def9';


        /*$style_recurrente = array(
            'fill' => array(
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => $color_cabeceras)
            )
        );

        $row_cell_=14;
        $letter_cell='N';
        foreach ($event->sheet->getRowIterator() as $row)
        {
            if($row->getRowIndex()==1)continue;
            if($event->sheet->getCellByColumnAndRow($row_cell_,$row->getRowIndex())->getValue()=='RECURRENTE')
            {
                $event->sheet->getStyle($letter_cell.$row->getRowIndex())->applyFromArray($style_recurrente);
            }


        }*/

        /*echo 'ROW: ', $cell->getRow(), PHP_EOL;
                   echo 'COLUMN: ', $cell->getColumn(), PHP_EOL;
                   echo 'COORDINATE: ', $cell->getCoordinate(), PHP_EOL;
                   echo 'RAW VALUE: ', $cell->getValue(), PHP_EOL;*/

        //Range Columns
                /*
                $event->sheet->styleCells(
                    'Q',
                    [
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'color' => ['rgb' => '336655']
                        ]
                    ]
                ); */
    }
}
