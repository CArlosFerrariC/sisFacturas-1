<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventsUnsigned;
use App\Models\ImageAgenda;
use App\Models\ImagenAgenda;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class FullCalenderController extends Controller
{
    public function indexcalendario(Request $request)
    {
        return view('fullcalendar.index');
    }

    public function token(Request $request)
    {
        setting()->load();
        if(!$request->clave)
        {
            return 0;
        }else{
            $clave=$request->clave;

            if(!\Hash::check($clave,setting("agenda_password")))
            {
                return 0;
            }else{
                return 1;
            }
        }
    }

    public function index(Request $request)
    {
        $eventss = [];
        $uneventss = [];

        $all_events = Event::where('status','=','1')->where('unsigned','=','0')->get();

        foreach ($all_events as $event)
        {
            $eventss[] = [
                'id'=>$event->id,
                'title' => $event->title,
                'start' => $event->start,
                'end' => $event->end,
                'color'=>$event->color,
                'textColor'=>$event->color,
                'backgroundColor'=>$event->color,
                'description' => $event->description,
                'attach'=>$event->attach,
            ];
        }

        $all_eventsunsigned = Event::where('status','=','1')->where('unsigned','=','1')->get();
        foreach ($all_eventsunsigned as $eventsunsigned)
        {
            $uneventss[] = [
                'id'=>$eventsunsigned->id,
                'titulo' => $eventsunsigned->title,
                'descripcion' => $eventsunsigned->description,
                'horainicio' => $eventsunsigned->created_at,
                'horafin' => $eventsunsigned->updated_at,
                'color'=>$eventsunsigned->color,
                'colortexto'=>"black",
                'colorfondo'=>$eventsunsigned->color,
                'attach'=>$eventsunsigned->attach,
            ];
        }
        return view('fullcalendar.fullcalendar', compact('eventss','uneventss'));

    }

    public function ajax(Request $request)
    {
        switch ($request->type) {
            case 'validate':
                $json=array('ok'=>true);
                return response()->json();
                break;
            case 'load':
                $events = [];
                $all_events = Event::where('status','=','1')->where('unsigned','=','0')->get();
                foreach ($all_events as $event)
                {
                    $events[] = [
                        'id'=>$event->id,
                        'title' => $event->title,
                        'description' => $event->description,
                        'start' => $event->start,
                        'end' => $event->end,
                        'color'=>$event->color,
                        'colorEvento'=>$event->colorEvento,
                        'fondoEvento'=>$event->fondoEvento,
                        'tipo'=>$event->tipo,
                        'frecuencia'=>$event->frecuencia,
                        'adjunto'=>Storage::disk('public')->url($event->attach),
                    ];
                }
                return response()->json($events);
                break;
            case 'modificar':
                $event=Event::where('id',$request->editar_evento)->first();
                $event->update([
                    'title'=>$request->calendario_nombre_evento_editar,
                    'description'=>$request->calendario_descripcion_evento_editar,
                ]);
                $files = $request->file('inputFilesEvent');
                if (isset($files)){
                    foreach($files as $file){
                        $fileattach=$file->store('agenda', 'pstorage');
                        $imageevent= ImageAgenda::create([
                            'unsigned' =>   1,
                            'event_id' =>   $event->id ,
                            'filename' =>   $file->getClientOriginalName() ,
                            'filepath' =>    $fileattach ,
                            'filetype' =>   $file->getClientOriginalExtension() ,
                            'status'   =>   1 ,
                        ]);

                        /*$fileattach=$file->store('agenda', 'pstorage');
                        $fileEvent =Event::where('id',$request->editar_evento)->first();
                        $fileEvent->update([
                            'attach'=> $fileattach,
                        ]);*/
                    }
                }
                return response()->json($event);
                break;
            case 'adddrop':
                //info de unsigned eventunsigned
                $eventUnsigned=EventsUnsigned::where('id',$request->eventunsigned)->first();
                $event = Event::create([
                    'title' => $eventUnsigned->title,
                    'start' => $request->dateStr,
                    'end' => $request->dateStr,
                    'color' => $eventUnsigned->color,
                ]);
                return response()->json($event);
            case 'add':
                $color='';$colorFondo='';
                if($request->colorBackground!='')
                {
                    $colorFondo=$request->colorBackground;$color='black';
                }else{
                    if($request->calendario_tipo_evento=='PAGO'){$colorFondo='#BA55D3';$color='black';}
                    else if($request->calendario_tipo_evento=='OTROS'){$colorFondo='#5F9F9F';$color="black";}
                }


                //analisis frecuencia
            $frecuencia_recorrido=null;
                switch($request->calendario_frecuencia_evento) {
                    case 'una_vez':
                        $frecuencia_recorrido = $request->calendario_start_evento;
                        $event = Event::create([
                            'title' => $request->calendario_nombre_evento,
                            'description' => $request->calendario_descripcion_evento_nuevo,
                            'start' => $request->calendario_start_evento,
                            'end' => $request->calendario_end_evento,
                            'color' => $colorFondo,
                            'colorEvento' => $color,
                            'fondoEvento' => $colorFondo,
                            'tipo' => $request->calendario_tipo_evento,
                            'frecuencia' => $request->calendario_frecuencia_evento,
                        ]);
                        $files = $request->file('inputFilesEvent');
                        if (isset($files) ){
                            foreach($files as $file){
                                $fileattach=$file->store('agenda', 'pstorage');
                                $imageevent= ImageAgenda::create([
                                    'unsigned' =>   1 ,
                                    'event_id' =>   $event->id ,
                                    'filename' =>   $file->getClientOriginalName() ,
                                    'filepath' =>    $fileattach ,
                                    'filetype' =>   $file->getClientOriginalExtension() ,
                                    'status'   =>   1 ,
                                ]);
                            }
                        }
                        if (isset($request->id_unsigned_event)){
                            $imagenesagendas=ImageAgenda::where('event_id',$request->id_unsigned_event)
                            ->where('unsigned',0)->where('status',1)->get();
                            foreach($imagenesagendas as $archivo){
                                $imageevent= ImageAgenda::create([
                                    'unsigned' =>   1 ,
                                    'event_id' =>   $event->id ,
                                    'filename' =>   $archivo->filename ,
                                    'filepath' =>   $archivo->filepath ,
                                    'filetype' =>   $archivo->filetype ,
                                    'status'   =>   1 ,
                                ]);
                                $archivo->update([
                                    'status'=> 0,
                                    'updated_at' => now(),
                                ]);
                            }

                        }
                        break;
                    case 'diario':
                        $inidia = Carbon::parse($request->calendario_start_evento)->clone()->startOfDay();
                        $findia = Carbon::parse($request->calendario_start_evento)->clone()->endOfMonth()->endOfDay();
                        $difference = ($inidia->diff($findia)->days < 1)
                            ? 'today'
                            : $inidia->diffForHumans($findia);
                        for ($i = 0; $i <= $difference; $i++) {
                            //llevar al dia
                            $fecha = $inidia->clone()->addDays($i)->format('Y-m-d');

                            $event = Event::create([
                                'title' => $request->calendario_nombre_evento,
                                'description' => $request->calendario_descripcion_evento_nuevo,
                                'start' => $fecha,
                                'end' => $fecha,
                                'color' => $colorFondo,
                                'colorEvento' => $color,
                                'fondoEvento' => $colorFondo,
                                'tipo' => $request->calendario_tipo_evento,
                                'frecuencia' => $request->calendario_frecuencia_evento,
                            ]);

                        }
                        $files = $request->file('inputFilesEvent');

                        if (isset($files) ){
                            foreach($files as $file){
                                $fileattach=$file->store('agenda', 'pstorage');
                                $fileEvent =Event::where('id',$event->id)->first();
                                $fileEvent->update([
                                    'attach'=> $fileattach,
                                ]);
                            }
                        }
                        break;
                    case 'ini_mes':
                        $startDate = Carbon::parse($request->calendario_start_evento);
                        if (!$startDate->day == 1) {
                            $startDate->addMonth();
                        }
                        $endDate = $startDate->clone()->addYear()->startOfYear()->subDay();
                        //$monthsRemaining = 12 - $startDate->month + 1;
                        $firstDayOfNextMonth = $startDate->clone()->firstOfMonth();

                        for ($date = $firstDayOfNextMonth; $date->lte($endDate); $date->addMonthsNoOverflow())
                        {
                            $fullmes=$date->clone()->firstOfMonth();
                            $event =Event::create([
                                'title' => $request->get('calendario_nombre_evento'),
                                'description' => $request->get('calendario_descripcion_evento_nuevo'),
                                'start' => $fullmes->startOfDay(),
                                'end' => $fullmes->endOfDay(),
                                'color' => $colorFondo,
                                'colorEvento' => $color,
                                'fondoEvento' => $colorFondo,
                                'tipo' => $request->get('calendario_tipo_evento'),
                                'frecuencia' => $request->get('calendario_frecuencia_evento'),
                            ]);
                        }
                        $files = $request->file('inputFilesEvent');
                        if (isset($files) ){
                            foreach($files as $file){
                                $fileattach=$file->store('agenda', 'pstorage');
                                $fileEvent =Event::where('id',$event->id)->first();
                                $fileEvent->update([
                                    'attach'=> $fileattach,
                                ]);
                            }
                        }
                        break;
                    case 'fin_mes':
                        $startDate = Carbon::parse($request->calendario_start_evento);
                        if (!$startDate->isLastOfMonth()) {
                            //$date->addMonth();
                            //$startDate=$startDate->clone()->lastOfMonth();
                        }
                        $endDate = $startDate->clone()->addYear()->startOfYear()->subDay();
                        $lastDayOfNextMonth = $startDate->clone()->lastOfMonth();

                        for ($date = $lastDayOfNextMonth; $date->lte($endDate); $date->addMonthsNoOverflow())
                        {
                            $fullmes=$date->clone()->lastOfMonth();
                            $event =Event::create([
                                'title' => $request->get('calendario_nombre_evento'),
                                'description' => $request->get('calendario_descripcion_evento_nuevo'),
                                'start' => $fullmes->startOfDay(),
                                'end' => $fullmes->endOfDay(),
                                'color' => $colorFondo,
                                'colorEvento' => $color,
                                'fondoEvento' => $colorFondo,
                                'tipo' => $request->get('calendario_tipo_evento'),
                                'frecuencia' => $request->get('calendario_frecuencia_evento'),
                            ]);
                        }
                        $files = $request->file('inputFilesEvent');

                        if (isset($files) ){
                            foreach($files as $file){
                                $fileattach=$file->store('agenda', 'pstorage');
                                $fileEvent =Event::where('id',$event->id)->first();
                                $fileEvent->update([
                                    'attach'=> $fileattach,
                                ]);
                            }
                        }
                        return response()->json($event);
                }
                break;
            case 'borrar':
                $event = Event::find($request->editar_evento)->update(['status'=>'0']);
                //delete();
                return response()->json($event);
            default:
                # code...
                break;
        }
        return 0;
    }

    public function eventsunsigned(Request $request)
    {
        $eventssunsigned = [];
        $all_eventsunsigned = EventsUnsigned::all();

        foreach ($all_eventsunsigned as $eventunsigned)
        {
            $eventssunsigned[] = [
                'id'=>$eventunsigned->id,
                'title' => $eventunsigned->title,
                'start' => $eventunsigned->start,
                'end' => $eventunsigned->end,
                'color'=>$eventunsigned->color,
                //'description' => 'description for All Day Event',
            ];
        }

        return response()->json($eventssunsigned);
    }

    public function ajaxunsigned(Request $request)
    {
        switch ($request->type) {
            case 'traslado':
                $event = Event::find($request->eliminar_evento)->update(
                    [
                        'unsigned'=>'0',
                        'start'=>$request->start,
                        'end'=>$request->end,
                        'colorEvento'=>'white',
                    ]
                );
                return response()->json($event);
                break;
            case 'add':
                $event = Event::create([
                    'title' => $request->calendario_nombre_evento,
                    'description'=>$request->calendario_descripcion_evento,
                    'start' => Carbon::parse(now())->format('Y-m-d'),
                    'end' => Carbon::parse(now())->format('Y-m-d'),
                    'color' => $request->calendario_color_evento,
                    'colorEvento' => $request->calendario_color_evento,
                    'fondoEvento' => $request->calendario_color_evento,
                    'grupo'=>'0',
                    'tipo'=>'OTROS',
                    'frecuencia'=>'una_vez',
                    'unsigned'=>'1',
                    'status'=>'1'
                ]);
                $files = $request->file('inputFilesEventU');
                if (isset($files)){
                    foreach($files as $file){
                        $fileattach=$file->store('agenda', 'pstorage');
                        $imageevent= ImageAgenda::create([
                            'unsigned' =>   0,
                            'event_id' =>   $event->id ,
                            'filename' =>   $file->getClientOriginalName() ,
                            'filepath' =>    $fileattach ,
                            'filetype' =>   $file->getClientOriginalExtension() ,
                            'status'   =>   1 ,
                        ]);
                    }
                }
                return response()->json($event);

            case 'delete':
                $event = Event::find($request->eliminar_evento)->update([
                    'status'=>'0'
                ]);
                //delete();
                return response()->json($event);
            default:
                # code...
                break;
        }
        //return 0;
    }


}
