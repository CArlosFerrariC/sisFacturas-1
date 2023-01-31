<?php

namespace App\Models;

use App\Traits\CommonModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;
    use CommonModel;

    const RECUPERADO_RECIENTE = "RECUPERADO RECIENTE";
    const RECUPERADO_PERMANENTE = "RECUPERADO ABANDONO";
    const RECUPERADO_ABANDONO = "RECUPERADO ABANDONO";
    const ABANDONO_RECIENTE = "ABANDONO RECIENTE";
    const ABANDONO_PERMANENTE = "ABANDONO PERMANENTE";
    const ABANDONO = "ABANDONO";
    const RECURRENTE = "RECURRENTE";
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

    //public static function cambiarDireccion(,self $cliente, Pedido $pedido, array $data)
    public static function restructurarCodigos(self $cliente)
    {
        $cliente->situacion;

        /*2021*/
        //11
        $cont_2021_11=Pedidos::where("cliente_id",$cliente->id)->whereYear('created_at','2021')->whereMonth('created_at','11')->activo()->count();
        if($cont_2021_11==0)
        {

        }else{

        }

    }

}
