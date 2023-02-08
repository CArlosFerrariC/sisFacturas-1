<?php

namespace App\View\Components\dashboard\graficos;

use App\Abstracts\Widgets;
use App\Models\Pedido;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\Component;

class GraficoMetasDelMes extends Widgets
{
    public $novResult = [];
    public $dicResult = [];

    public $excludeNov = [];
    public $excludeDic = [];


    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        $this->startDate = now();
        $data_diciembre = $this->generarDataDiciembre();

        $now_submonth = $this->startDate->clone()->startOfMonth()->subMonth();
        $data_noviembre = $this->generarDataNoviembre($now_submonth);


        if (\auth()->user()->rol == User::ROL_ASESOR) {
            $this->novResult = [];
            $this->dicResult = [];
        }
        $now = $this->startDate->clone();

        return view('components.dashboard.graficos.grafico-metas-del-mes', compact('data_noviembre', 'data_diciembre', 'now', 'now_submonth'));
    }

    public function applyFilterCustom($query, CarbonInterface $date = null, $column = 'created_at')
    {
        if ($date == null) {
            $date = $this->startDate->clone();
        }
        return $query->whereBetween($column, [
            $date->clone()->startOfMonth(),
            $date->clone()->endOfMonth()->endOfDay()
        ]);
    }

    public function generarDataNoviembre($date)
    {
        if (auth()->user()->rol != User::ROL_ADMIN){
            $asesores = [];
        }
        else {
            $encargado = null;
            if (auth()->user()->rol == User::ROL_ENCARGADO) {
                $encargado = auth()->user()->id;
            }
            if (auth()->user()->rol == User::ROL_FORMACION) {
                $asesores = User::query()
                    ->activo()
                    ->rolAsesor()
                    ->get();
            }else{
                $asesores = User::query()
                    ->activo()
                    ->rolAsesor()
                    //->incluidoMeta()
                    ->when($encargado != null, function ($query) use ($encargado) {
                        return $query->where('supervisor', '=', $encargado);
                    })
                    ->get();
            }
        }

        $progressData = [];
        foreach ($asesores as $asesor) {
            if (auth()->user()->rol != User::ROL_ADMIN){
                if (auth()->user()->rol != User::ROL_ENCARGADO) {
                    if (auth()->user()->id != $asesor->id) {
                        continue;
                    }
                } else {
                    if (auth()->user()->id != $asesor->supervisor) {
                        continue;
                    }
                }
            }

            $metatotal = (float)$asesor->meta_pedido;
            $metatotal_2 = (float)$asesor->meta_pedido_2;
            $all = $this->applyFilterCustom(Pedido::query()->where('user_id', $asesor->id)->activo(), $date, 'created_at')
                ->count();
            $all_2 = $this->applyFilterCustom(Pedido::query()->where('user_id', $asesor->id)->activo(), $date, 'created_at')
                ->count();

            $pay = $this->applyFilterCustom(Pedido::query()->where('user_id', $asesor->id)->activo()->pagados(), $date, 'created_at')
                ->count();

            $item = [
                "identificador" => $asesor->identificador,
                "code" => "Asesor {$asesor->identificador}",
                "name" => $asesor->name,
                "total" => $all,
                "total_2" => $all_2,
                "current" => $pay,
                "meta" => $metatotal,
                "meta_2" => $metatotal_2,
            ];
            if ($asesor->excluir_meta) {
                if ($all > 0) {
                    $p = round(($pay / $all) * 100, 2);
                } else {
                    $p = 0;
                }
                if ($all_2 > 0) {
                    $p_2 = round(($pay / $all_2) * 100, 2);
                } else {
                    $p_2 = 0;
                }
                $item['progress'] = $p;
                $item['progress_2'] = $p_2;
                $this->excludeNov[] = $item;
            } else {
                $progressData[] = $item;
            }
        }
        $newData = [];
        $union = collect($progressData)->groupBy('identificador');
        foreach ($union as $identificador => $items) {
            foreach ($items as $item) {
                if (!isset($newData[$identificador])) {
                    $newData[$identificador] = $item;
                } else {
                    $newData[$identificador]['total'] += data_get($item, 'total');
                    $newData[$identificador]['total_2'] += data_get($item, 'total_2');
                    $newData[$identificador]['current'] += data_get($item, 'current');
                    $newData[$identificador]['meta'] += data_get($item, 'meta');
                    $newData[$identificador]['meta_2'] += data_get($item, 'meta_2');
                }
            }
            $newData[$identificador]['name'] = collect($items)->map(function ($item) {
                return explode(" ", data_get($item, 'name'))[0];
            })->first();
        }
        $progressData = collect($newData)->values()->map(function ($item) {
            $all = data_get($item, 'total');
            $all_2 = data_get($item, 'total_2');
            $pay = data_get($item, 'current');
            if ($all > 0) {
                $p = round(($pay / $all) * 100, 2);
            } else {
                $p = 0;
            }
            if ($all_2 > 0) {
                $p_2 = round(($pay / $all_2) * 100, 2);
            } else {
                $p_2 = 0;
            }
            $item['progress'] = $p;
            $item['progress_2'] = $p_2;
            return $item;
        })->sortBy('identificador')->all();

        $this->novResult = $progressData;

        $all = collect($progressData)->pluck('total')->sum();
        $all_2 = collect($progressData)->pluck('total_2')->sum();
        $pay = collect($progressData)->pluck('current')->sum();
        $meta = collect($progressData)->pluck('meta')->sum();
        $meta_2 = collect($progressData)->pluck('meta_2')->sum();
        if ($all > 0) {
            $p = round(($pay / $all) * 100, 2);
        } else {
            $p = 0;
        }
        if ($all_2 > 0) {
            $p_2 = round(($pay / $all_2) * 100, 2);
        } else {
            $p_2 = 0;
        }
        return (object)[
            "progress" => $p,
            "progress_2" => $p_2,
            "total" => $all,
            "total_2" => $all_2,
            "current" => $pay,
            "meta" => $meta,
            "meta_2" => $meta_2,
        ];
    }

    public function generarDataDiciembre()
    {
        if (auth()->user()->rol == User::ROL_LLAMADAS) {//HASTA MAÑANA
            $id = auth()->user()->id;
            $asesores = User::rolAsesor()->where('llamada', '=', $id)->get();
        }
        else{
            $encargado = null;
            if (auth()->user()->rol == User::ROL_ENCARGADO) {
                $encargado = auth()->user()->id;
            }
            if (auth()->user()->rol == User::ROL_FORMACION)
            {
                $asesores = User::query()
                    ->activo()
                    ->rolAsesor()
                    ->get();
            }else{
                $asesores = User::query()
                    ->activo()
                    ->rolAsesor()
                    //->incluidoMeta()
                    ->when($encargado != null, function ($query) use ($encargado) {
                        return $query->where('supervisor', '=', $encargado);
                    })
                    ->get();
            }

        }

        $progressData = [];
        foreach ($asesores as $asesor) {
            if (auth()->user()->rol != User::ROL_ADMIN){
                if (auth()->user()->rol != User::ROL_ENCARGADO) {
                    if (auth()->user()->id != $asesor->id) {
                        continue;
                    }
                } else {
                    if (auth()->user()->id != $asesor->supervisor) {
                        continue;
                    }
                }
            }

            $meta = (float)$asesor->meta_pedido;
            $meta_2 = (float)$asesor->meta_pedido_2;
            $asignados = $this->applyFilterCustom(Pedido::query()->whereUserId($asesor->id)->activo())->count();
            //$pay = $this->applyFilter(Pedido::query())->whereUserId($asesor->id)->activo()->pagados()->count();

            $item = [
                "identificador" => $asesor->identificador,
                "code" => "Asesor {$asesor->identificador}",
                "name" => $asesor->name,
                "meta" => $meta,
                "meta_2" => $meta_2,
                "total" => $asignados,
                //"current" => $pay,
            ];
            if ($asesor->excluir_meta) {
                if ($meta > 0) {
                    $p = round(($asignados / $meta) * 100, 2);
                } else {
                    $p = 0;
                }
                if ($meta_2 > 0) {
                    $p_2 = round(($asignados / $meta_2) * 100, 2);
                } else {
                    $p_2 = 0;
                }
                $item['progress'] = $p;
                $item['progress_2'] = $p_2;
                $this->excludeDic[] = $item;
            } else {
                $progressData[] = $item;
            }
        }

        $newData = [];
        $union = collect($progressData)->groupBy('identificador');//agrupamiento por asesor
        foreach ($union as $identificador => $items) {
            foreach ($items as $item) {
                if (!isset($newData[$identificador])) {
                    $newData[$identificador] = $item;
                } else {
                    $newData[$identificador]['meta'] += data_get($item, 'meta');
                    $newData[$identificador]['meta_2'] += data_get($item, 'meta_2');
                    $newData[$identificador]['total'] += data_get($item, 'total');
                    //$newData[$identificador]['current'] += data_get($item, 'current');
                }
            }
            $newData[$identificador]['name'] = collect($items)->map(function ($item) {
                return explode(" ", data_get($item, 'name'))[0];
            })->first();
        }
        $dicResult = collect($newData)->values()->map(function ($item) {
            $all = data_get($item, 'meta');
            $all_2 = data_get($item, 'meta_2');
            $asignados = data_get($item, 'total');
            if ($all > 0) {
                $p = round(($asignados / $all) * 100, 2);
            } else {
                $p = 0;
            }
            if ($all_2 > 0) {
                $p_2 = round(($asignados / $all_2) * 100, 2);
            } else {
                $p_2 = 0;
            }
            $item['progress'] = $p;
            $item['progress_2'] = $p_2;
            return $item;
        })->sortBy('identificador')->all();

        $this->dicResult = $dicResult;

        $metaTotal = collect($dicResult)->pluck('meta')->sum();
        $meta2Total = collect($dicResult)->pluck('meta_2')->sum();
        $asignados = collect($dicResult)->pluck('total')->sum();
        //$pagados = collect($dicResult)->pluck('current')->sum();
        if ($metaTotal > 0) {
            $p = intval(($asignados / $metaTotal) * 100);
        } else {
            $p = 0;
        }
        if ($meta2Total > 0) {
            $p2 = intval(($asignados / $meta2Total) * 100);
        } else {
            $p2 = 0;
        }
        return (object)[
            "progress" => $p,
            "progress_2" => $p2,
            "meta" => $metaTotal,
            "total" => $asignados,//$metaTotal,
            "current" => $asignados,
        ];
    }

}
