<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\ListadoResultado;
use DB;
use Illuminate\Console\Command;

class ResumidoNoBF extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resumido:noBF';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'info:info';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $informacion = DB::table('nobf')
            /*->where('s_2023_02', 'BASE FRIA')
            ->where('a_2023_02', 0)
            ->having(DB::raw('anulados+activos'), '>', 0)*/
            ->skip(6)->take(1)
            ->get();
        $row = $informacion;
        foreach ($row as $key => $value){
            if($value->anulados==0 && $value->activos==1
                && $value->fecha_ultimo_pedido_activo=='2022-12'
                && $value->fecha_ultimo_pedido_con_anulados=='2022-12'
                && $value->situacion=='BASE FRIA'
            )
            {
                $items=explode(',',$value->codigos);
                    foreach($items as $item)
                    {
                        $this->info($item);
                        ListadoResultado::where('id',$item)
                            ->update([
                                'a_2022_12'=>$value->activos,
                                's_2022_12'=>'NUEVO  ',
                                's_2023_01'=>'RECURRENTE',
                                's_2023_02'=>'ABANDONO RECIENTE'
                            ]);
                        Cliente::where('id',$item)->update(['situacion'=>'ABANDONO']);
                    }
            }

        }
        return 0;
    }
}
