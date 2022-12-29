<?php

namespace App\Exports\Templates\Sheets;

use App\Abstracts\Export;
use App\Models\Cliente;
use App\Models\ListadoResultado;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class PageclienteinfoNoviembre extends Export implements WithColumnFormatting,WithColumnWidths
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
                ,DB::raw(" (select a.s_2022_11 from listado_resultados a where a.id=clientes.id ) as situacion ")
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
        //return parent::title();//Por defecto se toma del nombre de la clase de php, en este caso seria "Pagina One" de titulo
        return 'Detalle Noviembre';
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
}
