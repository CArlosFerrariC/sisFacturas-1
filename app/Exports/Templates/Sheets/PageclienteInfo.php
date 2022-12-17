<?php

namespace App\Exports\Templates\Sheets;

use App\Abstracts\ExportYear;
use App\Models\Cliente;
use App\Models\Porcentaje;
use App\Models\Pedido;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\WithBackgroundColor;

use \Maatwebsite\Excel\Sheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

Sheet::macro('styleCells', function (Sheet $sheet, string $cellRange, array $style) {
    $sheet->getDelegate()->getStyle($cellRange)->applyFromArray($style);
});

class PageclienteInfo extends ExportYear implements WithColumnFormatting, FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    //public $anio;
    /*public function __construct($anio)
    {

        parent::__construct();
        //$this->$anio=2021;
    }*/

    public function collection()
    {
        return Cliente::with('user')
            ->join('users as u', 'clientes.user_id', 'u.id')
            ->select(
                'clientes.id'
                ,'u.identificador as id_asesor'
                ,'clientes.nombre'
                ,'clientes.dni'
                ,'clientes.icelular'
                //'p.codigo as codigo'
                ,'clientes.celular'
                ,'u.name as nombre_asesor'
                ,'clientes.provincia'
                ,'clientes.distrito'
                ,'clientes.direccion'
                ,'clientes.referencia'
                //,'clientes.estado'
                //,'clientes.deuda'
                //,'clientes.pidio'
                ,'clientes.situacion'
                ,DB::raw("(select DATE_FORMAT(dp1.created_at,'%Y-%m-%d %h:%i:%s') from pedidos dp1 where dp1.cliente_id=clientes.id order by dp1.created_at desc limit 1) as fecha")
                ,DB::raw(" (select (dp.codigo) from pedidos dp where dp.cliente_id=clientes.id order by dp.created_at desc limit 1) as codigo ")
            )
        //->whereIn('clientes.id',[1,2,3,4,5,6,7,8,9,10])
        ->where('clientes.estado', '1')
        ->where('clientes.tipo', '1')
        ->get();
    }

    public function title(): string
    {
        //return parent::title();//Por defecto se toma del nombre de la clase de php, en este caso seria "Pagina One" de titulo
        return 'Info de Cliente';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8
            ,'B' => 8
            ,'C' => 8
            ,'D' => 8
            ,'E' => 8
            ,'F' => 8
            ,'G' => 8
            ,'H' => 8
            ,'I' => 8
            ,'J' => 8
            ,'K' => 8
            ,'L' => 8
            ,'M' => 8
            ,'N' => 8
            ,'O' => 8
            ,'P' => 8
            ,'Q' => 8
            ,'R' => 8
            ,'S' => 8
            ,'T' => 8
            ,'U' => 8
            ,'V' => 8
            ,'W' => 8
            ,'X' => 8
            ,'Y' => 8
            ,'Z' => 8
            ,'AA' => 8
            ,'AB' => 8
            ,'AC' => 8
            ,'AD' => 8
            ,'AE' => 8
            ,'AF' => 8
            ,'AG' => 8
            ,'AH' => 8
            ,'AI' => 8
            ,'AJ' => 8
            ,'AK' => 8
            ,'AL' => 8
            ,'AM' => 8
            ,'AN' => 8
            ,'AO' => 8
            ,'AP' => 8
            ,'AQ' => 8
        ];
    }

    public function map($model): array
    {
        //mapear datos del model que no esten la tabla
     /*
        $model->nuevo_campo = //nuevo campo
     */
        //$model->fehca_formato=$model->created_at->format('');
        try {
            $model->porcentajefsb= Porcentaje::select('porcentaje')
            ->where('cliente_id',$model->id)
            ->where('nombre','FISICO - sin banca')->first()->porcentaje;
          } catch (\Exception $e) {
            $model->porcentajefsb=0;
          }

          try {
            $model->porcentajefb= Porcentaje::select('porcentaje')
            ->where('cliente_id',$model->id)
            ->where('nombre','FISICO - banca')->first()->porcentaje;
          } catch (\Exception $e) {
            $model->porcentajefb=0;
          }

          try {
            $model->porcentajeesb= Porcentaje::select('porcentaje')
            ->where('cliente_id',$model->id)
            ->where('nombre','ELECTRONICA - sin banca')->first()->porcentaje;
          } catch (\Exception $e) {
            $model->porcentajeesb=0;
          }

          try {
            $model->porcentajeeb= Porcentaje::select('porcentaje')
            ->where('cliente_id',$model->id)
            ->where('nombre','ELECTRONICA - banca')->first()->porcentaje;
          } catch (\Exception $e) {
            $model->porcentajeeb=0;
          }


        $model->eneroa = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '1')->count();
        $model->eneroa=($model->eneroa<0)? 0:$model->eneroa;
        $model->enerop = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio+1)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '1')->count();

        $model->febreroa = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '2')->count();
        $model->febrerop = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio+1)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '2')->count();

        $model->marzoa = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '3')->count();
        $model->marzop = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio+1)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '3')->count();

        $model->abrila = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '4')->count();
        $model->abrilp = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio+1)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '4')->count();

        $model->mayoa = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '5')->count();
        $model->mayop = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio+1)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '5')->count();

        $model->junioa = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '6')->count();
        $model->juniop = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio+1)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '6')->count();

        $model->julioa = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '7')->count();
        $model->juliop = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio+1)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '7')->count();

        $model->agostoa = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '8')->count();
        $model->agostop = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio+1)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '8')->count();

        $model->setiembrea = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '9')->count();
        $model->setiembrep = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio+1)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '9')->count();

        $model->octubrea = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '10')->count();
        $model->octubrep = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio+1)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '10')->count();

        $model->noviembrea = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '11')->count();
        $model->noviembrep = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio+1)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '11')->count();

        $model->diciembrea = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '12')->count();
        $model->diciembrep = Pedido::where('estado', '1')->whereYear(DB::raw('Date(created_at)'), $this->anio+1)->where('cliente_id', $model->id)
            ->where(DB::raw('MONTH(created_at)'), '12')->count();



        if ($model->deuda == '1') {
            $model->deposito = 'DEBE';
        } else {
            $model->deposito = 'CANCELADO';
        }

        //$dateM = Carbon::now()->format('m');
        //$dateY = Carbon::now()->format('Y');

        //$model->created_at->format('');
        return parent::map($model);
    }

    public function fields(): array
    {
        return [
            "id"=>"Identificador"
            ,"nombre"=>"Nombre"
            ,"dni"=>"DNI"
            ,"icelular"=>"Ientificador celular"
            ,"celular"=>"Celular"
            ,"id_asesor"=>"Identificador Asesor"
            ,"nombre_asesor"=>"Nombre Asesor"
            ,"provincia"=>"Provincia"
            ,"distrito"=>"Distrito"
            ,"direccion"=>"Direccion"
            ,"referencia"=>"Referencia"
            //,"estado"=>"Estado"
            //,"deuda"=>"Deuda"
            //,"pidio"=>"Pidio"
            ,"fecha"=>"Fecha"
            ,"codigo"=>"codigo"
            ,"situacion"=>"Situacion"
            ,'deposito'=>'Deposito'
            ,"porcentajefsb"=>"Porcentaje Fisico sin banca"
            ,"porcentajefb"=>"Porcentaje Fisico Bancarizado"
            ,"porcentajeesb"=>"Porcentaje Electronico sin banca"
            ,"porcentajeeb"=>"Porcentaje Electronico Bancarizado"
            ,"eneroa"=>"Enero ".$this->anio
            ,"febreroa"=>"Febrero ".$this->anio
            ,"marzoa"=>"Marzo ".$this->anio
            ,"abrila"=>"Abril ".$this->anio
            ,"mayoa"=>"Mayo ".$this->anio
            ,"junioa"=>"Junio ".$this->anio
            ,"julioa"=>"Julio ".$this->anio
            ,"agostoa"=>"Agosto ".$this->anio
            ,"setiembrea"=>"Setiembre ".$this->anio
            ,"octubrea"=>"Octubre ".$this->anio
            ,"noviembrea"=>"Noviembre ".$this->anio
            ,"diciembrea"=>"Diciembre ".$this->anio
            ,"enerop"=>"Enero ".($this->anio+1)
            ,"febrerop"=>"Febrero ".($this->anio+1)
            ,"marzop"=>"Marzo ".($this->anio+1)
            ,"abrilp"=>"Abril ".($this->anio+1)
            ,"mayop"=>"Mayo ".($this->anio+1)
            ,"juniop"=>"Junio ".($this->anio+1)
            ,"juliop"=>"Julio ".($this->anio+1)
            ,"agostop"=>"Agosto ".($this->anio+1)
            ,"setiembrep"=>"Setiembre ".($this->anio+1)
            ,"octubrep"=>"Octubre ".($this->anio+1)
            ,"noviembrep"=>"Noviembre ".($this->anio+1)
            ,"diciembrep"=>"Diciembre ".($this->anio+1)
            //,"created_at"=>"Fecha",
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
            'L' => NumberFormat::FORMAT_DATE_YYYYMMDD
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => [self::class, 'afterSheet']
        ];
    }

    public static function afterSheet(AfterSheet $event){

        /*d62828  ABANDONO
        fca311  ABANDONO RECIENTE
        blanco  base fria
        b5e48c	nuevo
        00b4d8		RECUPERADO RECIENTE
        3a86ff		RECUPERADO ABANDONO
        a9def9		RECURRENTE*/

        /*$stylerecuperadoreciente = array(
            'alignment' => array(
                'horizontal' => Alignment::HORIZONTAL_JUSTIFY,
            ),
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'bfd200',
                ]
            ],
        );*/
        $color_recurente='a9def9';
        $color_recuperadoabandono='3a86ff';
        $color_recuperadoreciente='00b4d8';
        $color_nuevo='b5e48c';
        $color_basefria='ffffff';
        $color_abandono='d62828';
        $color_abandonoreciente='fca311';
        $color_default='eff7f6';

        $style_recurrente = array(
                'fill' => array(
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => array('argb' => $color_recurente)
                )
        );
        $style_recuperadoabandono = array(
                'fill' => array(
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => array('argb' => $color_recuperadoabandono)
                )
        );
        $style_recuperadoreciente = array(
            'fill' => array(
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => $color_recuperadoreciente)
            )
        );
        $style_nuevo = array(
            'fill' => array(
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => $color_nuevo)
            )
        );
        $style_basefria = array(
            'fill' => array(
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => $color_basefria)
            )
        );
        $style_abandono = array(
            'fill' => array(
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => $color_abandono)
            )
        );
        $style_abandonoreciente = array(
            'fill' => array(
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => $color_abandonoreciente)
            )
        );
        $styledefault = array(
            'fill' => array(
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => $color_default)
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
            else if($event->sheet->getCellByColumnAndRow($row_cell_,$row->getRowIndex())->getValue()=='RECUPERADO ABANDONO')
            {
                $event->sheet->getStyle($letter_cell.$row->getRowIndex())->applyFromArray($style_recuperadoabandono);
            }
            else if($event->sheet->getCellByColumnAndRow($row_cell_,$row->getRowIndex())->getValue()=='RECUPERADO RECIENTE')
            {
                $event->sheet->getStyle($letter_cell.$row->getRowIndex())->applyFromArray($style_recuperadoreciente);
            }
            else if($event->sheet->getCellByColumnAndRow($row_cell_,$row->getRowIndex())->getValue()=='NUEVO')
            {
                $event->sheet->getStyle($letter_cell.$row->getRowIndex())->applyFromArray($style_nuevo);
            }
            else if($event->sheet->getCellByColumnAndRow($row_cell_,$row->getRowIndex())->getValue()=='BASE FRIA')
            {
                $event->sheet->getStyle($letter_cell.$row->getRowIndex())->applyFromArray($style_basefria);
            }
            else if($event->sheet->getCellByColumnAndRow($row_cell_,$row->getRowIndex())->getValue()=='ABANDONO')
            {
                $event->sheet->getStyle($letter_cell.$row->getRowIndex())->applyFromArray($style_abandono);
            }
            else if($event->sheet->getCellByColumnAndRow($row_cell_,$row->getRowIndex())->getValue()=='ABANDONO RECIENTE')
            {
                $event->sheet->getStyle($letter_cell.$row->getRowIndex())->applyFromArray($style_abandonoreciente);
            }else{
                $event->sheet->getStyle($letter_cell.$row->getRowIndex())->applyFromArray($styledefault);
            }

        }

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

