<?php

namespace App\Models;

use App\Traits\CommonModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;
    use CommonModel;

    const RECUPERADO_RECIENTE = "RECUPERADO RECIENTE";
    const RECUPERADO_PERMANENTE = "RECUPERADO ABANDONO";
    const SITUACION_NULO = "NULO";
    const RECUPERADO_ABANDONO = "RECUPERADO ABANDONO";
    const ABANDONO_RECIENTE = "ABANDONO RECIENTE";
    const ABANDONO_PERMANENTE = "ABANDONO PERMANENTE";
    const ABANDONO = "ABANDONO";
    const RECURRENTE = "RECURRENTE";
    const ACTIVO = "ACTIVO";
    const NUEVO = "NUEVO";
    const RECUPERADO = "RECUPERADO";
    const CASI_ABANDONO = "CASI ABANDONO";

    const ANULADO='ANULADO';


    protected $guarded = ['id'];
    protected $dates=[
        'temporal_update'
    ];
    protected $casts=[
        'activado_pedido'=>'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rucs()
    {
        return $this->hasMany(Ruc::class, 'cliente_id');
    }

    public function porcentajes()
    {
        return $this->hasMany(Porcentaje::class, 'cliente_id');
    }

    public function pedidos()
    {
        //SELECT SUM(saldo) FROM detalle_pedidos WHERE pedido_id=4;
        return $this->hasMany(Pedido::class, 'cliente_id');
    }

    public function direccion_grupos(){
        return $this->hasMany(DireccionGrupo::class,'cliente_id');
    }

    public function adjuntosFiles()
    {
        $data = setting("pedido." . $this->id . ".adjuntos_file");
        if (is_array($data)) {
            return $data;
        }
        return [];
    }

    public static function restructurarCodigos($anio,$mes,self $cliente)
    {
        $analisis=SituacionClientes::where('id',$cliente->id)->orderBy('periodo')->get();
        if($analisis)
        {
            $anio='2021';
            for($i=11;$i<=12;$i++)
            {
                switch ($i)
                {
                    case '11':
                        break;
                    case '12':
                        break;
                }
            }
        }else{
            Clientes::where('id',$cliente->id)->update(['situacion'=>'BASE FRIA']);
        }
    }

  public static function createSituacionByCliente($cliente_id){
    $fp=Pedido::orderBy('created_at','asc')->limit(1)->first();



    $periodo_original=Carbon::parse($fp->created_at)->clone()->startOfMonth();
    $periodo_actual=Carbon::parse(now())->clone()->endOfMonth();
    $primer_periodo=Carbon::parse($fp->created_at);
    $diff = ($periodo_original->diffInMonths($periodo_actual))+1;
    $where_anio='';
    $where_mes='';
    $cont_mes=0;
    $clientes=Cliente::whereIn('tipo',['0','1'])->where('id',$cliente_id)->orderBy('id','asc')->get();

      foreach($clientes as $cliente)
      {

          $idcliente=$cliente->id;

          //if($cliente->id==1739)
          {
              //$this->warn($cliente->id);
              $delete=SituacionClientes::where('cliente_id',$cliente->id)->delete();

              $periodo_inicial=Carbon::parse($fp->created_at);
              $periodo_ejecucion=null;

              for($i=0;$i<$diff;$i++)
              {
                  $periodo_ejecucion=Carbon::parse($fp->created_at)->addMonths($i);
                  $where_anio=$periodo_ejecucion->format('Y');
                  $where_mes=$periodo_ejecucion->format('m');

                  //contadores
                  $cont_mes=Pedido::where('cliente_id',$cliente->id)->whereYear('created_at',$where_anio)
                      ->whereMonth('created_at',$where_mes)->where('codigo', 'not like', "%-C%")->count();
                  $cont_mes_activo=Pedido::where('cliente_id',$cliente->id)->whereYear('created_at',$where_anio)
                      ->whereMonth('created_at',$where_mes)->activo()->where('codigo', 'not like', "%-C%")->count();
                  $cont_mes_anulado=Pedido::where('cliente_id',$cliente->id)->whereYear('created_at',$where_anio)
                      ->whereMonth('created_at',$where_mes)->activo('0')->where('codigo', 'not like', "%-C%")->count();

                  //$this->warn('cont_mes '.$cont_mes.' where_anio '.$where_anio.' where_mes '.$where_mes);

                  $situacion_create=SituacionClientes::create([
                      'cliente_id'=>$cliente->id,
                      'situacion'=>'',
                      'cantidad_pedidos'=>$cont_mes,
                      'anulados'=>$cont_mes_anulado,
                      'activos'=>$cont_mes_activo,
                      'periodo'=>Carbon::createFromDate($where_anio, $where_mes)->startOfMonth()->format('Y-m'),
                      'flag_fp'=>'0'
                  ]);

                  $compara=Carbon::parse($fp->created_at);

                  $mes_antes = Carbon::createFromDate($where_anio, $where_mes)->startOfMonth()->subMonth();

                  if($cont_mes==0)
                  {
                      if( $where_anio==$compara->format('Y') && $where_mes==$compara->format('m') )
                      {
                          $situacion_create->update([
                              "situacion" => 'BASE FRIA',
                              "flag_fp" => '0'
                          ]);
                      }
                      else{
                          $situacion_antes=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_antes->format('Y-m'))->first();

                          switch($situacion_antes->situacion)
                          {
                              case 'BASE FRIA':
                                  $situacion_create->update([
                                      "situacion" => 'BASE FRIA',
                                      "flag_fp" => '0'
                                  ]);
                                  break;

                              case 'RECUPERADO RECIENTE':
                                  $situacion_create->update([
                                      "situacion" => 'RECURRENTE',
                                      "flag_fp" => '1'
                                  ]);
                                  break;

                              case 'RECUPERADO ABANDONO':
                                  $situacion_create->update([
                                      "situacion" => 'RECURRENTE',
                                      "flag_fp" => '1'
                                  ]);
                                  break;

                              case 'NUEVO':
                                  $mes_actual = Carbon::createFromDate($where_anio, $where_mes)->startOfMonth();
                                  $situacion_periodo=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_actual->format('Y-m'))->first();
                                  $situacion_antes=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_antes->format('Y-m'))->first();

                                  if($situacion_periodo->activos>0)
                                  {
                                      if($situacion_antes->activos>0)
                                      {
                                          $situacion_create->update([
                                              "situacion" => 'ACTIVO',"flag_fp" => '1'
                                          ]);
                                      }else{
                                          $situacion_create->update([
                                              "situacion" => 'NUEVO',"flag_fp" => '1'//
                                          ]);
                                      }
                                  }else{
                                      if($situacion_antes->activos>0)
                                      {
                                          $situacion_create->update([
                                              "situacion" => 'RECURRENTE',"flag_fp" => '1'
                                          ]);
                                      }else{
                                          $situacion_create->update([
                                              "situacion" => 'NULO',"flag_fp" => '1'
                                          ]);
                                      }

                                  }
                                  break;
                              case 'NULO':
                                  $mes_actual = Carbon::createFromDate($where_anio, $where_mes)->startOfMonth();
                                  $situacion_periodo=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_actual->format('Y-m'))->first();
                                  $situacion_antes=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_antes->format('Y-m'))->first();

                                  if($situacion_periodo->activos>0)
                                  {
                                      $situacion_create->update([
                                          "situacion" => 'NUEVO',"flag_fp" => '1'//
                                      ]);
                                  }else{
                                      $situacion_create->update([
                                          "situacion" => 'NULO',"flag_fp" => '1'
                                      ]);

                                  }
                                  break;
                              case 'ABANDONO RECIENTE':
                              case 'ABANDONO':
                                  $situacion_create->update([
                                      "situacion" => 'ABANDONO',
                                      "flag_fp" => '1'
                                  ]);
                                  break;
                              case 'RECURRENTE':
                                  if($situacion_antes->activos==0)
                                  {
                                      $situacion_create->update([
                                          "situacion" => 'ABANDONO RECIENTE',
                                          "flag_fp" => '1'
                                      ]);
                                  }else{
                                      $situacion_create->update([
                                          "situacion" => 'RECURRENTE',
                                          "flag_fp" => '1'
                                      ]);
                                  }
                                  break;
                              case 'ACTIVO':
                                  if($situacion_antes->activos==0)
                                  {
                                      $situacion_create->update([
                                          "situacion" => 'RECURRENTE',
                                          "flag_fp" => '1'
                                      ]);
                                  }else{
                                      $situacion_create->update([
                                          "situacion" => 'RECURRENTE',
                                          "flag_fp" => '1'
                                      ]);
                                  }
                                  break;
                              default:break;
                          }
                      }
                  }
                  else{
                      if( $where_anio==$compara->format('Y') && $where_mes==$compara->format('m') )
                      {
                          $situacion_create->update([
                              "situacion" => 'NUEVO',
                              "flag_fp" => '0'
                          ]);
                      }
                      else{
                          $situacion_antes=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_antes->format('Y-m'))->first();
                          switch($situacion_antes->situacion)
                          {
                              case 'BASE FRIA':
                                  $situacion_create->update([
                                      "situacion" => 'NUEVO',
                                      "flag_fp" => '1'
                                  ]);

                                  $mes_actual = Carbon::createFromDate($where_anio, $where_mes)->startOfMonth();
                                  $situacion_periodo=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_actual->format('Y-m'))->first();
                                  $situacion_antes=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_antes->format('Y-m'))->first();

                                  if($situacion_antes->flag_fp==0)
                                  {
                                      if($situacion_periodo->activos>0)
                                      {
                                          $situacion_create->update([
                                              "situacion" => 'NUEVO',
                                              "flag_fp" => '1'
                                          ]);
                                      }else{
                                          $situacion_create->update([
                                              "situacion" => 'NUEVO',
                                              "flag_fp" => '0'
                                          ]);
                                      }

                                  }
                                  else if($situacion_antes->flag_fp==1)
                                  {
                                      if($situacion_periodo->activos>0)
                                      {
                                          $situacion_create->update([
                                              "situacion" => 'NUEVO',
                                              "flag_fp" => '1'
                                          ]);
                                      }else{
                                          $situacion_create->update([
                                              "situacion" => 'NUEVO',
                                              "flag_fp" => '0'
                                          ]);
                                      }
                                  }
                                  break;
                              case 'RECUPERADO RECIENTE':
                                  $mes_actual = Carbon::createFromDate($where_anio, $where_mes)->startOfMonth();
                                  $situacion_periodo=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_actual->format('Y-m'))->first();
                                  $situacion_antes=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_antes->format('Y-m'))->first();

                                  if($situacion_periodo->activos>0)
                                  {
                                      $situacion_create->update([
                                          "situacion" => 'ACTIVO',
                                          "flag_fp" => '1'
                                      ]);
                                  }else{
                                      $situacion_create->update([
                                          "situacion" => 'ACTIVO',
                                          "flag_fp" => '1'
                                      ]);
                                  }
                                  break;
                              case 'RECUPERADO ABANDONO':
                                  $mes_actual = Carbon::createFromDate($where_anio, $where_mes)->startOfMonth();
                                  $situacion_periodo=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_actual->format('Y-m'))->first();

                                  $situacion_antes=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_antes->format('Y-m'))->first();

                                  if($situacion_periodo->activos>0)
                                  {
                                      $situacion_create->update([
                                          "situacion" => 'ACTIVO',
                                          "flag_fp" => '1'
                                      ]);
                                  }else{
                                      $situacion_create->update([
                                          "situacion" => 'RECURRENTE',
                                          "flag_fp" => '1'
                                      ]);
                                  }

                                  break;
                              case 'NUEVO':
                                  $mes_actual = Carbon::createFromDate($where_anio, $where_mes)->startOfMonth();
                                  $situacion_periodo=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_actual->format('Y-m'))->first();
                                  $situacion_antes=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_antes->format('Y-m'))->first();


                                  if($situacion_periodo->activos>0)
                                  {
                                      if($situacion_antes->activos>0)
                                      {
                                          $situacion_create->update([
                                              "situacion" => 'ACTIVO',"flag_fp" => '1'
                                          ]);
                                      }else{
                                          $situacion_create->update([
                                              "situacion" => 'NUEVO',"flag_fp" => '1'//
                                          ]);
                                      }
                                  }else{
                                      if($situacion_antes->activos>0)
                                      {
                                          $situacion_create->update([
                                              "situacion" => 'RECURRENTE',"flag_fp" => '1'
                                          ]);
                                      }else{
                                          $situacion_create->update([
                                              "situacion" => 'NULO',"flag_fp" => '1'
                                          ]);
                                      }

                                  }
                                  break;
                              case 'NULO':
                                  $mes_actual = Carbon::createFromDate($where_anio, $where_mes)->startOfMonth();
                                  $situacion_periodo=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_actual->format('Y-m'))->first();
                                  $situacion_antes=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_antes->format('Y-m'))->first();


                                  if($situacion_periodo->activos>0)
                                  {
                                      $situacion_create->update([
                                          "situacion" => 'NUEVO',"flag_fp" => '1'//
                                      ]);
                                  }else{
                                      $situacion_create->update([
                                          "situacion" => 'NULO',"flag_fp" => '1'
                                      ]);
                                  }
                                  break;
                              case 'ABANDONO':
                                  $mes_actual = Carbon::createFromDate($where_anio, $where_mes)->startOfMonth();
                                  $situacion_periodo=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_actual->format('Y-m'))->first();
                                  $situacion_antes=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_antes->format('Y-m'))->first();

                                  if($situacion_periodo->activos>0)
                                  {
                                      $situacion_create->update([
                                          "situacion" => 'RECUPERADO ABANDONO',
                                          "flag_fp" => '1'
                                      ]);
                                  }else{
                                      $situacion_create->update([
                                          "situacion" => 'ABANDONO',
                                          "flag_fp" => '1'
                                      ]);
                                  }

                                  break;
                              case 'ABANDONO RECIENTE':
                                  $mes_actual = Carbon::createFromDate($where_anio, $where_mes)->startOfMonth();
                                  $situacion_periodo=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_actual->format('Y-m'))->first();
                                  $situacion_antes=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_antes->format('Y-m'))->first();

                                  if($situacion_periodo->activos>0)
                                  {
                                      $situacion_create->update([
                                          "situacion" => 'RECUPERADO ABANDONO',
                                          "flag_fp" => '1'
                                      ]);
                                  }else{
                                      $situacion_create->update([
                                          "situacion" => 'ABANDONO',
                                          "flag_fp" => '1'
                                      ]);
                                  }

                                  break;
                              case 'RECURRENTE':
                                  $mes_actual = Carbon::createFromDate($where_anio, $where_mes)->startOfMonth();
                                  $situacion_periodo=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_actual->format('Y-m'))->first();
                                  $situacion_antes=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_antes->format('Y-m'))->first();

                                  if($situacion_periodo->activos>0)
                                  {
                                      if ($situacion_antes->activos > 0 )
                                      {
                                          $situacion_create->update([
                                              "situacion" => 'ACTIVO',
                                              "flag_fp" => '1'
                                          ]);
                                      }else{
                                          $situacion_create->update([
                                              "situacion" => 'RECUPERADO RECIENTE',
                                              "flag_fp" => '1'
                                          ]);
                                      }

                                  }else{
                                      if ($situacion_antes->activos > 0 )
                                      {
                                          $situacion_create->update([
                                              "situacion" => 'ACTIVO',
                                              "flag_fp" => '1'
                                          ]);
                                      }else{
                                          $situacion_create->update([
                                              "situacion" => 'ABANDONO RECIENTE',
                                              "flag_fp" => '1'
                                          ]);
                                      }

                                  }
                                  break;
                              case 'ACTIVO':
                                  $mes_actual = Carbon::createFromDate($where_anio, $where_mes)->startOfMonth();
                                  $situacion_periodo=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_actual->format('Y-m'))->first();
                                  $situacion_antes=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_antes->format('Y-m'))->first();

                                  if($situacion_periodo->activos>0)
                                  {
                                      if ($situacion_antes->activos > 0 )
                                      {
                                          $situacion_create->update([
                                              "situacion" => 'ACTIVO',
                                              "flag_fp" => '1'
                                          ]);
                                      }else{
                                          $situacion_create->update([
                                              "situacion" => 'RECUPERADO RECIENTE',
                                              "flag_fp" => '1'
                                          ]);
                                      }

                                  }else{
                                      if ($situacion_antes->activos > 0 )
                                      {
                                          $situacion_create->update([
                                              "situacion" => 'RECURRENTE',
                                              "flag_fp" => '1'
                                          ]);
                                      }else{
                                          $situacion_create->update([
                                              "situacion" => 'ABANDONO RECIENTE',
                                              "flag_fp" => '1'
                                          ]);
                                      }

                                  }
                                  break;
                              default:
                                  break;
                          }

                      }
                  }

                  if($i==($diff-1))
                  {
                      $mes_actual = Carbon::createFromDate($where_anio, $where_mes)->startOfMonth();
                      $situacion_final=SituacionClientes::where('cliente_id',$cliente->id)
                          ->where('periodo',$mes_actual->format('Y-m'))->first();
                      $cont_ped_activo=Pedido::where('cliente_id',$cliente->id)->activo()->count();
                      $cont_ped_nulo=Pedido::where('cliente_id',$cliente->id)->activo(0)->count();

                      /*if( ($situacion_final!='BASE FRIA') && ($cont_ped_activo==0) && ($cont_ped_nulo>0) )
                      {
                          $situacion_cambia=SituacionClientes::where('cliente_id',$cliente->id)
                              ->where('periodo',$mes_actual->format('Y-m'))
                              ->first();
                          $situacion_cambia->update([
                              'situacion'=>'NULO'
                          ]);
                      }*/

                      $situacion_actual=SituacionClientes::where('cliente_id',$cliente->id)->where('periodo',$mes_actual->format('Y-m'))->first();

                      Cliente::where('id',$cliente->id)->update([
                          'situacion'=>$situacion_actual->situacion
                      ]);

                  }

              }

          }
          //$progress->advance();
      }

    return null;
  }

}
