<?php
namespace App\Exports\Templates\Sheets;

use App\Abstracts\Export;
use App\Models\Cliente;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class PageclienteinfoAgosto extends Export implements WithColumnFormatting,WithColumnWidths
{
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
                ,'clientes.celular'
                //,'clientes.situacion'
                ,DB::raw(" (select a.s_2022_08 from listado_resultados a where a.id=clientes.id ) as situacion ")
                ,DB::raw("(select DATE_FORMAT(dp1.created_at,'%Y-%m-%d %h:%i:%s') from pedidos dp1 where dp1.cliente_id=clientes.id order by dp1.created_at desc limit 1) as fecha"),
            )
            ->where('clientes.estado', '1')
            ->where('clientes.tipo', '1')
            ->get();
    }
    public function fields(): array
    {
        return [
            "id"=>"Id"
            ,"id_asesor"=>"Asesor"
            ,"nombre"=>"Nombre"
            ,"dni"=>"Dni"
            ,"icelular"=>"Identificador celular"
            ,"celular"=>"Celular"
            ,"situacion"=>"Situacion"
            ,"fecha"=>"Fecha Ultimo Pedido"
        ];
    }
    public function title(): string
    {
        return 'Detalle Setiembre';
    }
    public function map($model): array
    {
        $model->Periodo=strval(str_pad($model->Periodo,2,"0"));//->setDataType(DataType::TYPE_STRING);
        return parent::map($model);
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
        ];
    }

    public function columnFormats(): array
    {
        return [
            'H' => NumberFormat::FORMAT_DATE_YYYYMMDD

        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => [self::class, 'afterSheet']
        ];
    }

    public static function afterSheet(AfterSheet $event)
    {
        $color_recurente='a9def9';

        $style_recurrente = array(
            'fill' => array(
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => $color_recurente)
            )
        );

        $row_cell_=14;
        $letter_cell='N';
        //$event->sheet->getStyle($letter_cell.$row->getRowIndex())->applyFromArray($style_recurrente);

        /*foreach ($event->sheet->getRowIterator() as $row)
    }
}
