<?php

namespace App\Http\Controllers;

use App\Jobs\SyncOlvaJob;
use App\Models\Cliente;
use App\Models\DireccionGrupo;
use App\Models\Pedido;
use Carbon\Carbon;
use Goutte\Client;
use Illuminate\Http\Request;
use Illuminate\Queue\Jobs\SyncJob;

class ScraperController extends Controller
{
  private $results = array();

  public function getscraper()
  {

      $direccionGrupos = DireccionGrupo::whereIn('condicion_envio_code', [
          Pedido::RECEPCIONADO_OLVA_INT,
          Pedido::EN_CAMINO_OLVA_INT,
          Pedido::EN_TIENDA_AGENTE_OLVA_INT,
          Pedido::NO_ENTREGADO_OLVA_INT,
      ])->get();

      foreach ($direccionGrupos as $grupo) {
          $pedido=Pedido::where('direccion_grupo',$grupo->id)->first();
          $valtracking=[];
          if (isset($pedido->env_tracking)){
              if (strpos($pedido->env_tracking, '-') !== false) {
                  $trackings= collect(explode(',', $pedido->env_tracking))->trim()->filter()->values();
                  $numerotrack="";
                  $aniotrack="";
                  foreach ($trackings as $item =>  $tracking) {
                      $tracking = explode('-', $tracking);

                  }
                  if (count($tracking) == 2) {
                      $numerotrack=$tracking[0];
                      $aniotrack=$tracking[1];
                      if ($tracking[0]!="" && $tracking[1]!=""){
                          $datosolva=$this->getconsultaolva(trim($numerotrack),trim($aniotrack))["data"]["details"];
                          $estado = data_get($datosolva, 'data.general.nombre_estado_tracking');
                          $grupo->update([
                              'direccion' => trim($numerotrack) . '-' . trim($aniotrack),
                              'courier_sync_at' => now(),
                              'courier_estado' => $estado,
                              'courier_data' => $datosolva,
                              'courier_failed_sync_at' => null,
                              'add_screenshot_at' => null,
                          ]);

                          $pedido->update([
                              'env_tracking' => trim($numerotrack) . '-' . trim($aniotrack),
                              'courier_sync_at' => now(),
                              'courier_estado' => $estado,
                              'courier_data' => $datosolva,
                              'courier_failed_sync_at' => null,
                          ]);
                          echo "( ". trim($numerotrack)." & ".trim($aniotrack)." GRUPO=> ".$grupo->id." PEDIDO=> ".$pedido->id." Estado=>".$estado." )";
                      }
                  }



              }
          }
      }

    /*dd($datosolva,$result,$estado,$grupo);*/
    //return view('scraper.index', compact('data','datosolva')); // TODO: Change the autogenerated stub
  }

  public function getconsultaolva(string $tracking, string  $year)
  {
    $result = get_olva_tracking(trim($tracking), trim($year));
    return $result;
  }

  function convert_from_latin1_to_utf8_recursively($dat)
  {
    if (is_string($dat)) {
      return utf8_encode($dat);
    } elseif (is_array($dat)) {
      $ret = [];
      foreach ($dat as $i => $d) $ret[$i] = $this->convert_from_latin1_to_utf8_recursively($d);

      return $ret;
    } elseif (is_object($dat)) {
      foreach ($dat as $i => $d) $dat->$i = $this->convert_from_latin1_to_utf8_recursively($d);

      return $dat;
    } else {
      return $dat;
    }
  }

  public function procesarInformacion($json)
  {
    $information = (object)[
      "general" => (object)[
        "id_envio" => "",
        "emision" => "",
        "remito" => "",
        "remitente" => "",
        "doc_externo" => "",
        "consignado" => "",
        "contenido" => "",
        "peso" => "",
        "cantidad" => "",
        "nombre_estado_tracking" => "",
        "nombre_estado" => "",
        "flg_devolucion" => null
      ],
      "details" => [
      ],
      "realtime" => [
      ],
      "dates" => (object)[
        "registrado" => "",
        "entregado" => "",
        "origen" => "",
        "destino" => ""
      ]
    ];
    $information->general = (object)data_get($json, 'data.general');
    $information->details = data_get($json, 'data.details');
    $information->realtime = data_get($json, 'data.realtime');

    $information->dates->destino = $information->general->destino;

    $contenido = \Str::replace("SYSTEM", "CAJA", $information->general->contenido);
    $information->general->contenido = $contenido;
    $destino = $information->general->destino;
    $details = collect($information->details)->map(fn($det) => (object)$det);
    $filterDetails = $details->filter(function ($t) {
      if ("RECIBIDO DEL CLIENTE" == $t->estado_tracking
        || "CONFIRMACION PAGO OLVACOMPRAS" == $t->estado_tracking) {
        $t->obs = "En almac\xe9n " . $t->nombre_sede;
        $t->img = "/assets/img/almacen.png";
        return true;
      }
      return false;
    });
    if ($filterDetails->count() > 0) {
      $information->dates->registrado = $filterDetails->first()->fecha_creacion;
      $information->dates->origen = $filterDetails->first()->nombre_sede;
    }

    $filterDetails = $details->filter(function ($t, $key) {
      if ("RECEPCIONADO" == $t->estado_tracking) {
        $t->obs = "Tu env\xedo ha sido recepcionado por nuestro colaborador de recojo.";
        $t->img = "/assets/img/recepcionado.png";
        return true;
      }
      return false;
    });

    if ($filterDetails->count() > 0) {
      $information->dates->registrado = $filterDetails->first()->fecha_creacion;
      $information->dates->origen = $filterDetails->first()->nombre_sede;
    }

    $filterDetails = $details->filter(function ($t) {
      if ("REGISTRADO" == $t->estado_tracking || "TRACKING IMPRESO" == $t->estado_tracking) {
        $t->obs = "Tu env\xedo ha sido registrado en nuestro sistema.";
        $t->img = "/assets/img/registrado.png";
        return true;
      }
      return false;
    });

    if ($filterDetails->count() > 0) {
      $information->dates->registrado = $filterDetails->first()->fecha_creacion;
      $information->dates->origen = $filterDetails->first()->nombre_sede;
    } else {
      if ("REGISTRADO" == $information->general->nombre_estado_tracking) {
        $a = explode("-", $information->general->nombre_oficina);

        $information->dates->registrado = $information->general->fecha_envio;
        $information->dates->origen = $a[0];
        if ($information->details > 0) {
          $information->details = [(object)[
            'fecha_creacion' => $information->general->fecha_envio,
            'nombre_sede' => $a[0],
            'estado_tracking' => "REGISTRADO",
            'obs' => "Tu env\xedo se encuentra en nuestro almac\xe9n."
          ]];
        }
      }
    }
    $origen = $information->dates->origen;
    $filterDetails = $details->filter(function ($t) use ($origen, $destino) {
      if ("EN VALIJA" == $t->estado_tracking || "PRE VALIJA" == $t->estado_tracking || "PRE DESPACHO" == $t->estado_tracking) {
        $t->estado_tracking = ($t->nombre_sede == $destino || $t->nombre_sede == $origen) ? "EN ALMACEN" : "";
        $t->obs = "En almac\xe9n " . $t->nombre_sede . ".";
        $t->img = "/assets/img/almacen.png";
        return true;
      }
      return false;
    });
    if ($filterDetails->count() == 0) {
      $information->dates->registrado = "";
      $information->dates->origen = "";
    } else {
      $information->dates->registrado = $filterDetails->first()->fecha_creacion;
      $information->dates->origen = $filterDetails->first()->nombre_sede;
    }
    $filterDetails = $details->filter(function ($t) use ($origen, $destino) {
      if ("CONFIRMACION RECOJO" == $t->estado_tracking) {
        $t->estado_tracking = "RECEPCIONADO";
        $t->obs = "Tu env\xedo ha sido recepcionado por nuestro colaborador de recojo.";
        $t->img = "/assets/img/recepcionado.png";
        return true;
      } elseif ("RECEPCION TIENDA" == $t->estado_tracking) {
        $t->estado_tracking = "RECEPCIONADO";
        $t->obs = "Recepcionado en tienda.";
        $t->img = "/assets/img/recepcionado.png";
      }
      return false;
    });
    if ($filterDetails->count() > 0) {
      $information->dates->registrado = $filterDetails->first()->fecha_creacion;
      $information->dates->origen = $filterDetails->first()->nombre_sede;
    }
    $filterDetails = $details->filter(function ($t) use ($origen, $destino) {
      if ("REZAGADO" == $t->estado_tracking) {
        $n = explode("/", $t->obs);
        $t->obs = "Tu env\xedo ha sido retenido (" . $n[0] . "). Comun\xedcate con nuestro call center 01-7140909";
        return true;
      }
      return false;
    });

    $filterDetails = $details->filter(function ($t) use ($origen, $destino) {
      if ("EN PROCESO DE VERIFICACION POR MESA DE PARTES" == $t->estado_tracking) {
        $t->estado_tracking = "MESA DE PARTES";
        $t->obs = "En proceso de verificacion por la entidad";
        return true;
      }
      return false;
    });
    $filterDetails = $details->filter(function ($t) use ($origen, $destino) {
      if ("DESPACHADO" == $t->estado_tracking) {
        $t->estado_tracking = "EN CAMINO";
        $t->obs = "Tu env\xedo ha sido despachado a " . $destino . ".";
        $t->img = "/assets/img/camino.png";
        return true;
      }
      return false;
    });

    $filterDetails = $details->filter(function ($t) use ($origen, $destino) {
      if ("EN RUTA" == $t->estado_tracking || "ASIGNADO" == $t->estado_tracking) {
        $n = 0;
        if ("ASIGNADO" == $t->estado_tracking) {
          $s = explode(" - ", $t->obs);
          if ("Codigo operador: OCA" == $s[0]) {
            $t->estado_tracking = "NO MUESTRA";
            return $t->nombre_sede == $destino;
          }
          $i = explode(": ", $s[1]);
          $o = explode("/", $i[1]);
          if (\Date::create($o[2], $o[1] - 1, $o[0]) <= now()) {
            $t->fecha_creacion = $i[1];
          }
          "2648" === ($s[3] ? explode(": ", $s[3]) : [])[1] && ($n = 1);
        }
        $t->estado_tracking = $t->nombre_sede == $destino ? "EN CAMINO" : "";

        $t->obs = 0 == $n ? "En camino a tu direcci\xf3n." : "Tu env\xedo pronto llegar\xe1 a la tienda/agente seleccionada.";
        $t->img = "/assets/img/ruta.png";
        return true;
      }
      return false;
    });

    $details->filter(fn($t) => ("RECEPCION DESPACHO" == $t->estado_tracking || "TRANSITO" == $t->estado_tracking || "CONFIRMACION DE LLEGADA A SEDE" == $t->estado_tracking) && ($t->obs = "Tu env\xedo se encuentra en " . $t->nombre_sede . "." &&
        $t->obs .= ($t->nombre_sede == $destino ? "Te mandaremos un msj cuando este listo para su entrega." : " El viaje est\xe1 por completarse.") &&
          ($t->estado_tracking = $t->nombre_sede == $destino ? "EN PROVINCIA" : "EN ESCALA") &&
          $t->img = "/assets/img/escala.png"));

    $details6 = $details->filter(fn($t) => "CONFIRMACION EN TIENDA" == $t->estado_tracking && ($t->estado_tracking = "EN TIENDA/AGENTE" &&
        ($t->obs = "Tu env\xedo ya se encuentra en tienda/agente") && ($t->img = "/assets/img/recibido.png")));


    $details7 = $details->filter(function ($t, $e) {
      if ("MOTIVADO" == $t->estado_tracking || "AUSENTE" == $t->estado_tracking) {
        $n = explode(" - ", $t->obs);
        if (count($n) >= 4) {
          $t->obs = "Tu env\xedo no fue entregado por el " . ($n[3] ? \Str::lower($n[3]) : $n[3]);
        }
        $t->estado_tracking = "NO ENTREGADO";
        $t->img = "/assets/img/warning2.png";
        return true;
      }
      return false;
    });

    if ($details7->count())
      if ($details6->count()) {
        $p = explode("/", $details6->first()->fecha_creacion);
        $f = explode("/", $details7->first()->fecha_creacion);

        try {
          if (\Date::create($p[2], $p[1], $p[0]) > \Date::create($f[2], $f[1], $f[0])) {
            $information->dates->entregado = "-";
          }
        } catch (\Exception $ex) {
        }
      } else {
        $information->dates->entregado = "-";
      }
    $details->filter(fn($t, $e) => "DEVUELTO" == $t->estado_tracking && ($t->obs = "El env\xedo regresa al punto de partida"))->count() && ($information->dates->entregado = "-");

    $g = $details->filter(fn($t, $e) => "ANULACI\xd3N DE TRACKING" == $t->estado_tracking);
    if ("ANULACI\xd3N DE TRACKING" == $information->general->nombre_estado_tracking || $g->count()) {
      $a = explode("-", $information->general->nombre_oficina);
      $information->details = [(object)[
        'fecha_creacion' => $information->general->fecha_envio,
        'nombre_sede' => $a[0],
        'estado_tracking' => "SERVICIO CANCELADO",
        'obs' => "Tu env\xedo ya no existe en nuestro sistema."
      ]];
      $information->dates->registrado = $information->general->fecha_envio;
      $information->dates->origen = $a[0];
    }
    $m = $details->filter(fn($t, $e) => "ANULACI\xd3N DE COMPROBANTE PAGO" == $t->estado_tracking);
    if ("ANULACI\xd3N DE COMPROBANTE PAGO" == $information->general->nombre_estado_tracking || $m->count()) {
      $a = $information->general->nombre_oficina->split("-");
      $information->details = [(object)[
        'fecha_creacion' => $information->general->fecha_envio,
        'nombre_sede' => $a[0],
        'estado_tracking' => "COMPROBANTE ANULADO",
        'obs' => "Tu env\xedo ya no existe en nuestro sistema a solicitud del que env\xeda."
      ]];
      $information->dates->registrado = $information->general->fecha_envio;
      $information->dates->origen = $a[0];
    }
    $y = $details->filter(fn($t, $e) => "ENTREGADO" == $t->estado_tracking && ($t->obs = "Tu env\xedo ha sido entregado, \xa1Gracias por confiar en nosotros!" &&
        $t->img = "/assets/img/entregado.png"));

    $_ = false;
    foreach ($details as $t) {
      if ("ASIGNADO A DEVOLUCION" == $t->estado_tracking) {
        $_ = true;
      }
    }
    $details->filter(function ($t, $e) use ($_) {
      if ("ANULACI\xd3N DE TRACKING" == $t->estado_tracking || "ANULACI\xd3N DE COMPROBANTE PAGO" == $t->estado_tracking || "RECIBIDO DEL CLIENTE" == $t->estado_tracking || "RECEPCIONADO" == $t->estado_tracking || "REGISTRADO" == $t->estado_tracking || "ENTREGADO" == $t->estado_tracking || "EN PROCESO DE VERIFICACION POR MESA DE PARTES" == $t->estado_tracking || "MESA DE PARTES" == $t->estado_tracking || "ASIGNADO" == $t->estado_tracking || "EN TIENDA/AGENTE" == $t->estado_tracking || "EN CAMINO" == $t->estado_tracking || "RECEPCION DESPACHO" == $t->estado_tracking || "REZAGADO" == $t->estado_tracking || "DEVUELTO" == $t->estado_tracking || "MOTIVADO" == $t->estado_tracking || "AUSENTE" == $t->estado_tracking || "CONFIRMACION DE LLEGADA A SEDE" == $t->estado_tracking || "SERVICIO CANCELADO" == $t->estado_tracking || "COMPROBANTE ANULADO" == $t->estado_tracking || "EN PROVINCIA" == $t->estado_tracking || "EN RUTA" == $t->estado_tracking || "EN ALMACEN" == $t->estado_tracking || "EN ESCALA" == $t->estado_tracking || "NO ENTREGADO" == $t->estado_tracking || "SINIESTRADO" == $t->estado_tracking) {
        $n = explode("-", $t->fecha_creacion);
        return count($n) > 2 && ($t->fecha_creacion = ($n[2] . "/" . $n[1] . "/" . $n[0])) &&
          "ENTREGADO" == $t->estado_tracking && $_ && ($t->estado_tracking = "ENTREGADO POR DEVOLUCION" &&
            $t->obs = "Tu env\xedo ha sido devuelto, \xa1Gracias por confiar en nosotros!");
      }
      return false;
    });
    for ($v = [], $b = 0, $w = count($details); $b < $w; $b++) {
      $S = $details[$b];
      if (!isset($v[$S->estado_tracking . " - " . $S->fecha_creacion])) {
        $v[$S->estado_tracking . " - " . $S->fecha_creacion] = $S;
      }
    }
    $b = 0;
    $C = [];
    foreach ($v as $value) {
      $C[$b++] = $value;
    }
    $information->details = $C;
    if (null != $information->general->flg_devolucion && 1 == $information->general->flg_devolucion) {
      $t = $information->dates->origen;
      $information->dates->origen = $information->general->destino;
      $information->general->destino = $t;
    }
    if (count($information->details) > 0) {
      data_set($information, 'general.nombre_estado_tracking', data_get($information, 'details.0.estado_tracking'));
    }
    return $this->convert_from_latin1_to_utf8_recursively($information);
  }
}
