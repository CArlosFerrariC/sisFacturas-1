<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FullCalenderController extends Controller
{
    public function index(Request $request)
    {
        $eventss = [];
        //if($request->ajax()) {
            //dd('ajax');
            $all_events = Event::all();



            foreach ($all_events as $event)
            {
                $eventss[] = [
                    'id'=>$event->id,
                    'title' => $event->title,
                    'start' => $event->start,
                    'end' => $event->end,
                    'color'=>'purple'
                ];
            }
        //dd($eventss);
            /*$data = Event::whereDate('start', '>=', $request->start)
                ->whereDate('end',   '<=', $request->end)
                ->get(['id', 'title', 'start', 'end']);*/
            //dd($data);
            //return response()->json($data);
        //}
            return view('fullcalendar.fullcalendar', compact('eventss'));
        //return view('fullcalendar.fullcalendar');
    }

    public function ajax(Request $request)
    {
        switch ($request->type) {
            case 'add':
                $event = Event::create([
                    'title' => $request->calendario_nombre_evento,
                    'start' => $request->calendario_start_evento,
                    'end' => $request->calendario_start_evento,
                    /*'color' => $request->calendario_color_evento,*/
                ]);
                return response()->json($event);
            case 'update':
                $event = Event::find($request->id)->update([
                    'title' => $request->title,
                    'start' => $request->start,
                    'end' => $request->end,
                ]);
                return response()->json($event);
            case 'delete':
                $event = Event::find($request->eliminar_evento)->delete();
                return response()->json($request->eliminar_evento);
            default:
                # code...
                break;
        }
        return 0;
    }

}
