<?php

namespace App\Http\Controllers;

/* use Validator; */

use App\Models\Cliente;
use App\Models\CuentaBancaria;
use App\Models\DetallePedido;
use App\Models\DireccionEnvio;
use App\Models\PagoPedido;
use App\Models\Pedido;
use App\Models\Porcentaje;
use App\Models\User;
use App\Models\ListadoResultado;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use DataTables;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $dateM = Carbon::now()->format('m');
        $dateY = Carbon::now()->format('Y');
        $mirol = Auth::user()->rol;

        $anios = [
            "2020" => '2020 - 2021',
            "2021" => '2021 - 2022',
            "2022" => '2022 - 2023',
            "2023" => '2023 - 2024',
            "2024" => '2024 - 2025',
            "2025" => '2025 - 2026',
            "2026" => '2026 - 2027',
            "2027" => '2027 - 2028',
            "2028" => '2028 - 2029',
            "2029" => '2029 - 2030',
            "2030" => '2030 - 2031',
            "2031" => '2031 - 2032',
        ];


        $superasesor = User::where('rol', 'Super asesor')->count();

        return view('clientes.index', compact('anios', 'dateM', 'dateY', 'superasesor', 'mirol'));
    }

    public function clienteslistarecoger(Request $request)
    {
        $asesor = $request->user_id;
        $data = Cliente::
        join('users as u', 'clientes.user_id', 'u.id')
            ->select([
                'clientes.*'
            ])
            ->where('clientes.estado', '1')
            ->whereNotNull('clientes.situacion')
            ->whereNotIn('clientes.situacion', ['BASE FRIA', 'ABANDONO', 'ABANDONO RECIENTE', 'BLOQUEADO'])
            ->where("clientes.user_id", $asesor)
            ->where('clientes.tipo', '1');


        return datatables()->query(DB::table($data))//Datatables::of($data)
        ->addIndexColumn()
            ->editColumn('estado', function ($cliente) {

                return '<span class="badge badge-success">aa</span>';

            })
            ->addColumn('action', function ($row) {
                return '<button class="btn btn-success elegir">Elegir</button>';
            })
            ->rawColumns(['action', 'estado'])
            ->toJson();

    }

    public function pedidosclienteslistarecoger(Request $request)
    {
        $data = Pedido::
        join('clientes as c', 'pedidos.cliente_id', 'c.id')
            ->join('users as u', 'c.user_id', 'u.id')
            ->select([
                'pedidos.*'
            ])
            ->where("estado_sobre", "1")
            ->where('pedidos.cliente_id', $request->cliente_id)
            ->where('pedidos.estado', '1')
            ->whereIn('condicion_envio_code',
                [Pedido::ENTREGADO_SIN_SOBRE_CLIENTE_INT, Pedido::ENTREGADO_SIN_ENVIO_CLIENTE,
                    Pedido::ENTREGADO_CLIENTE_INT, Pedido::RECEPCIONADO_OLVA,
                    Pedido::EN_CAMINO_OLVA, Pedido::EN_TIENDA_AGENTE_OLVA, Pedido::ENTREGADO_PROVINCIA
                ]);

        return datatables()->query(DB::table($data))
            ->addIndexColumn()
            ->editColumn('estado', function ($pedido) {
                return '<span class="badge badge-success">aa</span>';
            })
            ->addColumn('action', function ($row) {
                return '<button class="btn btn-success elegir">Elegir</button>';
            })
            ->rawColumns(['action', 'estado'])
            ->toJson();

    }

    public function historialrecoger(Request $request)
    {
        $data = DireccionEnvio::
        select([
            'direccion_envios.*'
        ])
            ->where('direccion_envios.cliente_id', $request->cliente_id)
            ->where('direccion_envios.salvado', '1')
            ->where("direccion_envios.estado", "1");

        return datatables()->query(DB::table($data))
            ->addIndexColumn()
            ->editColumn('estado', function ($pedido) {
                return '<span class="badge badge-success">aa</span>';
            })
            ->addColumn('action', function ($row) {
                return '<button class="btn btn-success elegir">Elegir</button>';
            })
            ->rawColumns(['action', 'estado'])
            ->toJson();

    }

    public function indextabla(Request $request)
    {
        //
        $dateM = Carbon::now()->format('m');
        $dateY = Carbon::now()->format('Y');

        $data = null;

        $anios = [
            "2020" => '2020 - 2021',
            "2021" => '2021 - 2022',
            "2022" => '2022 - 2023',
            "2023" => '2023 - 2024',
            "2024" => '2024 - 2025',
            "2025" => '2025 - 2026',
            "2026" => '2026 - 2027',
            "2027" => '2027 - 2028',
            "2028" => '2028 - 2029',
            "2029" => '2029 - 2030',
            "2030" => '2030 - 2031',
            "2031" => '2031 - 2032',
        ];

        $data = Cliente:://CLIENTES SIN PEDIDOS
        join('users as u', 'clientes.user_id', 'u.id')
            ->leftjoin('pedidos as p', 'clientes.id', 'p.cliente_id')
            //->where('clientes.estado', '1')
            ->where('clientes.tipo', '1')
            ->groupBy(
                'clientes.id',
                'clientes.nombre',
                'clientes.icelular',
                'clientes.celular',
                'clientes.estado',
                'u.name',
                'u.identificador',
                'clientes.provincia',
                'clientes.distrito',
                'clientes.direccion',
                'clientes.deuda',
                'clientes.pidio',
                'clientes.situacion'
            )
            ->select('clientes.id',
                'clientes.nombre',
                'clientes.icelular',
                'clientes.celular',
                'clientes.estado',
                'clientes.estado as estado_int',
                'u.name as user',
                'u.identificador',
                'clientes.provincia',
                'clientes.distrito',
                'clientes.direccion',
                'clientes.pidio',

                DB::raw(" (select (dp.codigo) from pedidos dp where dp.estado=1 and dp.cliente_id=clientes.id order by dp.created_at desc limit 1) as ultimo_pedido "),

                DB::raw('count(p.created_at) as cantidad'),
                DB::raw('MAX(p.created_at) as fecha'),
                DB::raw('MAX(DATE_FORMAT(p.created_at, "%d")) as dia'),
                DB::raw('MAX(DATE_FORMAT(p.created_at, "%m")) as mes'),
                DB::raw('MAX(DATE_FORMAT(p.created_at, "%Y")) as anio'),
                DB::raw('MONTH(CURRENT_DATE()) as dateM'),
                DB::raw('YEAR(CURRENT_DATE()) as dateY'),
                DB::raw(" (select count(ped.id) from pedidos ped where ped.cliente_id=clientes.id and ped.pago in (0,1) and ped.pagado in (0,1) and cast(ped.created_at as date) >='" . now()->startOfMonth()->format('Y-m-d') . "' and ped.estado=1) as pedidos_mes_deuda "),
                DB::raw(" (select count(ped2.id) from pedidos ped2 where ped2.cliente_id=clientes.id and ped2.pago in (0,1) and ped2.pagado in (0,1) and cast(ped2.created_at as date) <='" . now()->startOfMonth()->subMonth()->endOfMonth()->endOfDay()->format('Y-m-d') . "'  and ped2.estado=1) as pedidos_mes_deuda_antes "),
                'clientes.deuda',
                //DB::raw(" (select lr.s_2022_11 from clientes c inner join listado_resultados lr on c.id=lr.id limit 1) as situacion")
                'clientes.situacion'
            );

        if (Auth::user()->rol == "Llamadas") {

            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.llamada', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');
            //$pedidos=$pedidos->WhereIn('pedidos.user_id',$usersasesores);
            $data = $data->WhereIn("u.identificador", $usersasesores);


        } else if (Auth::user()->rol == "Jefe de llamadas") {
            /*$usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.llamada', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $data = $data->WhereIn("u.identificador", $usersasesores);*/
        } elseif (Auth::user()->rol == "Asesor") {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.identificador', Auth::user()->identificador)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');
            $data = $data->WhereIn("u.identificador", $usersasesores);

        } else if (Auth::user()->rol == "Encargado") {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.supervisor', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $data = $data->WhereIn("u.identificador", $usersasesores);
        } elseif (Auth::user()->rol == User::ROL_ASESOR_ADMINISTRATIVO) {
            //$asesorB=User::activo()->where('identificador','=','B')->pluck('id')
            $data = $data->Where("u.identificador", '=', 'B');
        }
        //$data=$data->get();

        return datatables()->query(DB::table($data))//Datatables::of($data)

        ->addIndexColumn()
            ->addColumn('estado_int', function ($cliente) {
                if ($cliente->estado == '0') {
                    return $cliente->estado;
                } else {
                    return $cliente->estado;
                }


            })
            ->editColumn('estado', function ($cliente) {
                $badge_estado = '';
                if ($cliente->estado_int == '1') {
                    $badge_estado .= '<span class="badge badge-success" style="background-color:red !important;">' . Cliente::ANULADO . '</span>';
                    return $badge_estado;
                }

            })
            ->addColumn('action', function ($row) {
                $btn = "";

                if (\auth()->user()->can('clientes.edit')) {
                    if ($row->estado == '1') {
                        $btn = $btn . '<a href="' . route('clientes.edit', $row->id) . '" class="btn btn-warning btn-sm"> <i class="fas fa-edit"></i> Editar</a>';
                    }
                }

                $btn = $btn . '<a href="' . route('clientes.show', $row->id) . '" class="btn btn-info btn-sm"> <i class="fas fa-eye"></i> Ver</a>';

                if (\auth()->user()->can('clientes.destroy')) {
                    if ($row->estado == '1') {
                        $btn = $btn . '<a href="" data-target="#modal-delete" data-toggle="modal" data-cliente="' . $row->id . '" data-asesor="' . trim($row->identificador) . '"><button class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i> Bloquear</button></a>';
                    }
                }

                $btn = $btn . '<a href="" data-target="#modal-historial-situacion-cliente" data-toggle="modal" data-cliente="' . $row->id . '"><button class="btn btn-success btn-sm"><i class="fas fa-trash-alt"></i> Historico</button></a>';

                if (
                    ($row->pedidos_mes_deuda == 0 && $row->pedidos_mes_deuda_antes > 0) ||
                    ($row->pedidos_mes_deuda > 0 && $row->pedidos_mes_deuda_antes > 0) ||
                    ($row->pedidos_mes_deuda > 0 && $row->pedidos_mes_deuda_antes == 0)
                ) {
                    $btn = $btn . '<a href="" data-target="#modal_clientes_deudas_model" data-toggle="modal" data-cliente="' . $row->id . '"><button class="btn btn-dark btn-sm"><i class="fas fa-money"></i> Deudas</button></a>';
                }

                return $btn;
            })
            ->rawColumns(['action', 'estado', 'estado_int'])
            ->toJson();
        //}
    }

    public function clientestablasituacion(Request $request)
    {
        $idconsulta = $request->cliente;
        $idconsulta;
        $data = ListadoResultado::where('id', $idconsulta)
            ->select('id',
                'a_2021_11',
                'a_2021_12',
                'a_2022_01',
                'a_2022_02',
                'a_2022_03',
                'a_2022_04',
                'a_2022_05',
                'a_2022_06',
                'a_2022_07',
                'a_2022_08',
                'a_2022_09',
                'a_2022_10',
                'a_2022_11',
                'a_2022_12',
                'a_2023_01',
                's_2021_11',
                's_2021_12',
                's_2022_01',
                's_2022_02',
                's_2022_03',
                's_2022_04',
                's_2022_05',
                's_2022_06',
                's_2022_07',
                's_2022_08',
                's_2022_09',
                's_2022_10',
                's_2022_11',
                's_2022_12',
                's_2023_01',
            );

        return datatables()->query(DB::table($data))
            ->toJson();

    }

    public function create()
    {
        /*$users = User::where('users.estado','1')
        ->whereIn('users.rol', ['Asesor', 'Super asesor'])
        ->pluck('identificador', 'id');*/

        $users = User::select(
            DB::raw("CONCAT(identificador,' (ex ',IFNULL(exidentificador,''),')') AS identificador"), 'id'
        )
            ->where('users.rol', 'Asesor')
            ->where('users.estado', '1')
            ->pluck('identificador', 'id');

        return view('clientes.create', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //return $request->all();
        $request->validate([
            'celular' => 'required|unique:clientes',
        ]);

        $user = User::where('id', $request->user_id)->first();//el asesor
        $letra = $user->letra;

        try {
            DB::beginTransaction();

            $cliente = Cliente::create([
                'nombre' => $request->nombre,
                'celular' => $request->celular,
                'icelular' => $letra,
                'user_id' => $request->user_id,
                'tipo' => $request->tipo,
                'provincia' => $request->provincia,
                'distrito' => $request->distrito,
                'direccion' => $request->direccion,
                'referencia' => $request->referencia,
                'dni' => $request->dni,
                'deuda' => '0',
                'pidio' => '0',
                'estado' => '1'
            ]);

            // ALMACENANDO PORCENTAJES
            $nombreporcentaje = $request->nombreporcentaje;
            $valoresporcentaje = $request->porcentaje;
            $cont = 0;

            /* return $porcentaje; */
            while ($cont < count((array)$nombreporcentaje)) {

                $porcentaje = Porcentaje::create([
                    'cliente_id' => $cliente->id,
                    'nombre' => $nombreporcentaje[$cont],
                    'porcentaje' => $valoresporcentaje[$cont],

                ]);
                $cont++;
            }

            DB::commit();
        } catch (\Throwable $th) {
            throw $th;
            /* DB::rollback();
            dd($th); */
        }

        return redirect()->route('clientes.index')->with('info', 'registrado');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Cliente $cliente)
    {
        //return $cliente;
        $users = User::where('users.estado', '1')
            ->where('users.rol', 'Asesor')
            ->pluck('name', 'id');
        $porcentajes = Porcentaje::where('cliente_id', $cliente->id)->get();

        return view('clientes.show', compact('cliente', 'users', 'porcentajes'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Cliente $cliente)
    {
        $mirol = Auth::user()->rol;
        $users = User::where('users.estado', '1')
            ->whereIn('users.rol', ['Asesor', 'ASESOR ADMINISTRATIVO'])
            ->pluck('name', 'id');
        $porcentajes = Porcentaje::where('cliente_id', $cliente->id)->get()->map(function ($porcentaje, $index) {
            $porcentaje->rownumber = $index + 1;
            return $porcentaje;
        });

        $ultimopedido=Pedido::where('cliente_id',$cliente->id)
            ->activo()
            ->orderBy('created_at','desc')
            ->limit(1)
            ->first();
        $porcentaje_retorno=0;
        if($ultimopedido)
        {
            $ultimopedido_fecha=Carbon::parse($ultimopedido->created_at)->format('Y_m');
            $mes_situacion_up='s_'.$ultimopedido_fecha;
            $mes_situacion_actual='s_'.date('Y').'_'.date('M');
            if($ultimopedido_fecha==$mes_situacion_actual)
            {
                $situacion='RECURRENTE';
            }else{
                $fecha = now()->startOfDay();
                $fecha_comparar=Carbon::parse($ultimopedido->created_at)->startOfDay();
                $count=$fecha_comparar->diffInMonths($fecha);
                $situacion="";
                switch($count)
                {
                    case 1:
                        $situacion='RECURRENTE';
                        break;
                    case 2:
                        $situacion='ABANDONO RECIENTE';
                        break;
                    case 3:
                        $situacion='ABANDONO';
                        break;
                    default:
                        $situacion='ABANDONO';
                        break;
                }
            }
            $asesor=Cliente::where("id",$cliente->id)->first()->user_id;
            $asesor_identi=User::where('id',$asesor)->first()->identificador;
            $ultimopedido_fecha_comparacion=Carbon::parse($ultimopedido->created_at)->format('Y-m-d');

            if($asesor_identi=='01')
            {
                if($situacion=='ABANDONO' && $ultimopedido_fecha_comparacion<'2022-11-01'){
                    $porcentaje_retorno=1.5;
                }
            }else{
                if($situacion=='ABANDONO' && $ultimopedido_fecha_comparacion<'2022-11-01'){
                    $porcentaje_retorno=1.8;
                }
                else if($situacion=='ABANDONO' && $ultimopedido_fecha_comparacion>='2022-11-01'){
                    $porcentaje_retorno=2.0;
                }
            }
        }
        return view('clientes.edit', compact('cliente', 'users', 'porcentajes', 'mirol','porcentaje_retorno'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Cliente $cliente)
    {
        $request->validate([
            'celular' => 'required',
        ]);

        $cliente->update($request->all());

        $user = User::where('id', $request->user_id)->first();//el asesor
        $letra = $user->letra;
        $cliente->update([
            'icelular' => $letra
        ]);

        $idporcentaje = $request->idporcentaje;
        $valoresporcentaje = $request->porcentaje;
        $cont = 0;
        /* return $request->all(); */
        $valor = Porcentaje::find($idporcentaje); /* return $valor; */
        while ($cont < count((array)$idporcentaje)) {
            $valor[$cont]->update([
                'porcentaje' => $valoresporcentaje[$cont]
            ]);
            $cont++;
        }

        if ($request->tipo === '1') {
            return redirect()->route('clientes.index')->with('info', 'actualizado');
        } else {
            return redirect()->route('basefria')->with('info', 'actualizado');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Cliente $cliente)
    {
        $cliente->update([
            'estado' => '0'
        ]);

        return redirect()->route('clientes.index')->with('info', 'eliminado');
    }

    public function destroyid(Request $request)
    {
        if (!$request->hiddenID) {
            $html = '';
        } else {
            $cliente = Cliente::findOrFail($request->hiddenID);
            $filePaths = [];
            $files = $request->attachments;
            if (is_array($files)) {
                foreach ($files as $file) {
                    if ($file instanceof UploadedFile) {
                        $filePaths[] = $file->store("clientes_adjuntos", "pstorage");
                    }
                }
            }

            setting()->load();
            foreach ($filePaths as $index => $path) {
                $key = "pedido." . $cliente->id . ".adjuntos_file." . $index;
                $keyd = "pedido." . $cliente->id . ".adjuntos_disk." . $index;
                setting([
                    $key => $path,
                    $keyd => 'pstorage'
                ]);
            }
            setting()->save();

            $nombre_Responsable = User::where('id', Auth::user()->id)->first()->name;

            $cliente->update([
                'motivo_anulacion' => $request->motivo,
                'responsable_anulacion' => $nombre_Responsable,
                //'condicion' => 'ANULADO',
                //'condicion_code' => Pedido::ANULADO_INT,
                //'modificador' => 'USER' . Auth::user()->id,
                'user_anulacion_id' => Auth::user()->id,
                'fecha_anulacion' => now(),
                //'fecha_anulacion_confirm' => now(),
                'estado' => '0',
                'path_adjunto_anular' => null,
                'path_adjunto_anular_disk' => 'pstorage',
                'situacion' => 'BLOQUEADO',
            ]);

            $html = $cliente;

        }
        return response()->json(['html' => $html]);
    }

    public function createbf()
    {
        $usersB = User::where('users.estado', '1')
            ->where('identificador', 'B')
            ->first();

        $users = collect();
        $users->put($usersB->id, $usersB->identificador);
        $usersall = User::select(
            DB::raw("CONCAT(identificador,' (ex ',IFNULL(exidentificador,''),')') AS identificador"), 'id'
        )
            ->where('users.rol', 'Asesor')
            ->where('users.estado', '1')
            ->pluck('identificador', 'id');
        foreach ($usersall as $key => $value) {
            $users->put($key, $value);
        }
        return view('base_fria.create', compact('users'));
    }

    public function storebf(Request $request)
    {
        /* $request->validate([
                'celular' => 'required|unique:clientes',*/

        $cliente = Cliente::where('celular', $request->celular)->first();
        $letra = "";
        if ($cliente !== null) {

            $user = User::where('id', $cliente->user_id)->first();

            $messages = [
                'unique' => 'EL CELULAR INGRESADO SE ENCUENTA ASIGNADO AL ASESOR ' . $user->identificador,
            ];


            if ($user->exidentificador == '01' ||
                $user->exidentificador == '03' ||
                $user->exidentificador == '05' ||
                $user->exidentificador == '07' ||
                $user->exidentificador == '09' ||
                $user->exidentificador == '11' ||
                $user->exidentificador == '13' ||
                $user->exidentificador == '15' ||
                $user->exidentificador == '17' ||
                $user->exidentificador == '19'
            ) {
                $letra = "A";
            }

            if ($user->exidentificador == '02' ||
                $user->exidentificador == '04' ||
                $user->exidentificador == '06' ||
                $user->exidentificador == '08' ||
                $user->exidentificador == '10' ||
                $user->exidentificador == '12' ||
                $user->exidentificador == '14' ||
                $user->exidentificador == '16' ||
                $user->exidentificador == '18' ||
                $user->exidentificador == '20'
            ) {
                $letra = "B";
            }

            $validator = Validator::make($request->all(), [
                'celular' => 'required|unique:clientes',
            ], $messages);

            if ($validator->fails()) {
                return redirect('clientes.createbf')
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $cliente = Cliente::create([
            'nombre' => $request->nombre,
            'celular' => $request->celular,
            'user_id' => $request->user_id,
            'tipo' => $request->tipo,
            'deuda' => '0',
            'pidio' => '0',
            'estado' => '1',
            'icelular' => $letra,
        ]);

        return redirect()->route('basefria')->with('info', 'registrado');
    }

    public function editbf(Cliente $cliente)
    {
        $users = User::where('users.estado', '1')
            ->where('users.rol', 'Asesor')
            ->pluck('name', 'id');

        return view('base_fria.edit', compact('cliente', 'users'));
    }

    public function clientedeasesor(Request $request)
    {
        $html = '<option value="">' . trans('---- SELECCIONE CLIENTE ----') . '</option>';
        $usersasesores = User::query()
            //->where('users.estado', '1')
            ->whereIdentificador($request->user_id)
            ->select(
              DB::raw("users.id as id")
            )
            ->pluck('users.id');

        $clientes = Cliente::query()
            ->where('clientes.estado', '=', '1')
            ->where('clientes.tipo', '=', '1')
            ->whereIn('clientes.user_id',$usersasesores)
            /*->when($request->user_id, function ($query) use ($request) {
                return $query->whereIn('clientes.user_id', User::query()->select('users.id')->whereIdentificador($request->user_id));
            })*/
            ->orderBy('clientes.id')
            ->get();


        foreach ($clientes as $cliente) {
            $cliente->pedidos_mes_deuda = $cliente->pedidos()->activo()->noPagados()->whereBetween('pedidos.created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();
            $cliente->pedidos_mes_deuda_antes = $cliente->pedidos()->activo()->noPagados()->where('pedidos.created_at', '<=', now()->startOfMonth()->subMonth()->endOfMonth()->endOfDay())->count();

            //auth::user()->rol=='Administrador'
            if (auth::user()->rol == 'Administrador' || auth::user()->rol == 'Asistente de Administración' || auth::user()->identificador == 'B') {

                $html .= '<option style="color:black" value="' . $cliente->id . '">' . $cliente->celular . (($cliente->icelular != null) ? '-' . $cliente->icelular : '') . '  -  ' . $cliente->nombre . '</option>';
            } else {
                if ($cliente->crea_temporal == 1) {
                    //falta considerar el tiempo ahora menos el tiempo activado temporal
                    $html .= '<option style="color:lightblue" value="' . $cliente->id . '">' . $cliente->celular . '-' . $cliente->icelular . '  -  ' . $cliente->nombre . ' (Temporal)</option>';
                } else {
                    //considerar deuda real
                    $saldo = DetallePedido::query()->whereEstado(1)
                        ->whereIn('pedido_id',
                            Pedido::query()->select('pedidos.id')
                                ->where('pedidos.cliente_id', '=', $cliente->id)
                                ->activo()
                        )->sum('saldo');
                    //pago | pagado
                    $deuda_anterior = Pedido::query()->noPagados()->activo()
                        ->where('pedidos.cliente_id', '=', $cliente->id)
                        ->whereBetween('created_at', [now()->startOfMonth()->subMonth()->startOfMonth(), now()->startOfMonth()->subMonth()->endOfMonth()->endOfDay()])
                        ->count();

                    $deuda_pedidos_5 = Pedido::query()->noPagados()->activo()
                        ->where('pedidos.cliente_id', '=', $cliente->id)
                        ->count();

                    //aca evalua primero si tiene deuda el mes anterior y si tiene pedidos cond deuda mayor a 5
                    if ($deuda_anterior > 0 || $deuda_pedidos_5 > 5) {
                        if (auth::user()->rol == User::ROL_APOYO_ADMINISTRATIVO) {
                            $html .= '<option style="color:red" value="' . $cliente->id . '">' . $cliente->celular . '-' . $cliente->icelular . '  -  ' . $cliente->nombre . '  (' . ($saldo > 0 ? 'Con Deuda' : '') . ')</option>';
                        } else {
                            $html .= '<option disabled style="color:red" value="' . $cliente->id . '">' . $cliente->celular . '-' . $cliente->icelular . '  -  ' . $cliente->nombre . '  (' . ($saldo > 0 ? 'Con Deuda' : '') . ')</option>';
                        }
                    } else {
                        //y si no tiene esod e arriba pasa a evaluar esta parte
                        if ($cliente->pedidos_mes_deuda > 0 && $cliente->pedidos_mes_deuda_antes == 0) {
                            $html .= '<option style="color:' . ($saldo == 0 ? 'green' : 'lightblue') . '" value="' . $cliente->id . '">' . $cliente->celular . '-' . $cliente->icelular . '  -  ' . $cliente->nombre . '  (' . ($saldo == 0 ? 'Sin Deuda ' : 'Con Deuda') . ')</option>';
                        } else if (($cliente->pedidos_mes_deuda > 0 && $cliente->pedidos_mes_deuda_antes > 0) || ($cliente->pedidos_mes_deuda == 0 && $cliente->pedidos_mes_deuda_antes > 0)) {
                            if (auth::user()->rol == User::ROL_APOYO_ADMINISTRATIVO) {
                                $html .= '<option style="color:' . ($saldo == 0 ? 'green' : 'red') . '" value="' . $cliente->id . '">' . $cliente->celular . '-' . $cliente->icelular . '  -  ' . $cliente->nombre . '**CLIENTE CON DEUDA**</option>';
                            } else {
                                $html .= '<option ' . ($saldo == 0 ? '' : 'disabled') . ' style="color:' . ($saldo == 0 ? 'green' : 'red') . '" value="' . $cliente->id . '">' . $cliente->celular . '-' . $cliente->icelular . '  -  ' . $cliente->nombre . '**CLIENTE CON DEUDA**</option>';
                            }

                        } else {
                            $html .= '<option style="color:' . ($saldo == 0 ? 'green' : 'red') . '" value="' . $cliente->id . '">' . $cliente->celular . '-' . $cliente->icelular . '  -  ' . $cliente->nombre . '  (' . ($saldo == 0 ? 'Sin Deuda' : 'Con Deuda') . ')</option>';
                        }
                    }
                }
            }
        }

        return response()->json(['html' => $html]);
    }

    public function clientedeasesorpagos(Request $request)
    {
        //ahora con el identificador de  Usuarios
        $mirol = Auth::user()->rol;
        $clientes = null;
        //$user_id=User::where('identificador',$request->user_id)->pluck('id');
        $clientes = Cliente::where('clientes.estado', '1')
            ->when($request->user_id, function ($query) use ($request) {
                return $query->whereIn('clientes.user_id', User::query()->select('users.id')->whereIdentificador($request->user_id));
            })
            //->where('clientes.estado', '1')
            ->where("clientes.tipo", "1");
        $html = "";

        $clientes = $clientes->orderBy('id', 'ASC')
            ->get([
                'clientes.id',
                'clientes.deuda',
                'clientes.crea_temporal',
                'clientes.activado_tiempo',
                'clientes.activado_pedido',
                'clientes.temporal_update',
                'clientes.icelular',
                'clientes.celular',
                'clientes.nombre',
                DB::raw(" (select count(ped.id) from pedidos ped where ped.cliente_id=clientes.id and ped.pago in (0,1) and ped.pagado in (0,1) and ped.created_at >='" . now()->startOfMonth()->format('Y-m-d h:i:s') . "' and ped.estado=1) as pedidos_mes_deuda "),
                DB::raw(" (select count(ped2.id) from pedidos ped2 where ped2.cliente_id=clientes.id and ped2.pago in (0,1) and ped2.pagado in (0,1) and ped2.created_at <='" . now()->startOfMonth()->subMonth()->endOfMonth()->endOfDay()->format('Y-m-d h:i:s') . "' and ped2.estado=1) as pedidos_mes_deuda_antes "),
            ]);


        //$cliente->pedidos_mes_deuda = $cliente->pedidos()->activo()->noPagados()->whereBetween('pedidos.created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();
        //$cliente->pedidos_mes_deuda_antes = $cliente->pedidos()->activo()->noPagados()->where('pedidos.created_at', '<=', now()->startOfMonth()->subMonth()->endOfMonth()->endOfDay())->count();

        $html = '<option value="">' . trans('---- SELECCIONE CLIENTE ----') . '</option>';

        foreach ($clientes as $cliente) {
            //Auth::user()->rol=='Administrador'
            if ($mirol == 'Administrador') {
                $html .= '<option style="color:black" value="' . $cliente->id . '">' . $cliente->celular . (($cliente->icelular != null) ? '-' . $cliente->icelular : '') . '  -  ' . $cliente->nombre . '</option>';
            } else {
                /*if($cliente->crea_temporal==1)
                {
                    //falta considerar el tiempo ahora menos el tiempo activado temporal
                    $html .= '<option style="color:yellow" value="' . $cliente->id . '">' . $cliente->celular.'-'.$cliente->icelular. '  -  ' . $cliente->nombre . '</option>';
                }else*/
                {
                    $saldo = DetallePedido::query()->whereEstado(1)->whereIn('pedido_id',
                        Pedido::query()->select('pedidos.id')
                            ->where('pedidos.cliente_id', '=', $cliente->id)
                            ->whereEstado(1)
                    )->sum('saldo');

                    //considerar deuda real
                    if ($cliente->pedidos_mes_deuda > 0 && $cliente->pedidos_mes_deuda_antes == 0) {
                        $html .= '<option ' . ($saldo == 0 ? 'disabled' : '') . ' style="color:' . ($saldo == 0 ? 'green' : 'lightblue') . '" value="' . $cliente->id . '">' . $cliente->celular . '-' . $cliente->icelular . '  -  ' . $cliente->nombre . '  (' . ($saldo == 0 ? 'Sin Deuda' : '') . ')</option>';
                    } else if (($cliente->pedidos_mes_deuda > 0 && $cliente->pedidos_mes_deuda_antes > 0) || ($cliente->pedidos_mes_deuda == 0 && $cliente->pedidos_mes_deuda_antes > 0)) {
                        $html .= '<option ' . ($saldo == 0 ? 'disabled' : '') . ' style="color:' . ($saldo == 0 ? 'green' : 'black') . '" value="' . $cliente->id . '">' . $cliente->celular . '-' . $cliente->icelular . '  -  ' . $cliente->nombre . '**CLIENTE CON DEUDA**</option>';
                    } else {
                        $html .= '<option ' . ($saldo == 0 ? 'disabled' : '') . '  style="color:' . ($saldo == 0 ? 'green' : 'red') . '" value="' . $cliente->id . '">' . $cliente->celular . '-' . $cliente->icelular . '  -  ' . $cliente->nombre . '  (' . ($saldo == 0 ? 'Sin Deuda' : '') . ')</option>';
                    }
                }
            }
        }

        return response()->json(['html' => $html]);
    }


    public function clientedeasesorparapagos(Request $request)
    {
        if (!$request->user_id || $request->user_id == '') {
            $html = '<option value="">' . trans('---- SELECCIONE CLIENTE ----') . '</option>';
        } else {

            $html = '<option value="">' . trans('---- SELECCIONE CLIENTE ----') . '</option>';
            $clientes = Cliente::where('clientes.user_id', $request->user_id)
                ->where('clientes.tipo', '1')
                ->get();
            foreach ($clientes as $cliente) {
                if ($cliente->deuda == "0") {
                    $html .= '<option disabled style="color:#000" value="' . $cliente->id . '">' . $cliente->celular . '  -  ' . $cliente->nombre . '</option>';
                } else {
                    if (Auth::user()->rol == 'Asesor') {
                        $html .= '<option   style="color:#fff" value="' . $cliente->id . '">' . $cliente->celular . '  -  ' . $cliente->nombre . '**CLIENTE CON DEUDA**</option>';
                    } else if (Auth::user()->rol == 'Llamadas') {
                        $html .= '<option   style="color:#fff" value="' . $cliente->id . '">' . $cliente->celular . '  -  ' . $cliente->nombre . '**CLIENTE CON DEUDA**</option>';
                    } else if (Auth::user()->rol == 'Jefe de lamadas') {
                        $html .= '<option  style="color:#fff" value="' . $cliente->id . '">' . $cliente->celular . '  -  ' . $cliente->nombre . '**CLIENTE CON DEUDA**</option>';
                    } else {
                        $html .= '<option  style="color:#fff" value="' . $cliente->id . '">' . $cliente->celular . '  -  ' . $cliente->nombre . '</option>';
                    }
                }

            }
        }

        return response()->json(['html' => $html]);
    }

    public function pedidosenvioclientetabla(Request $request)
    {
        $pedidos = null;
        if (!$request->cliente_id) {
            return datatables()->toJson();
        } else {

            $idrequest = $request->cliente_id;
            $pedidos = Pedido::join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
                ->select(
                    [
                        'pedidos.id',
                        'dp.codigo',
                        'dp.nombre_empresa',
                        'pedidos.da_confirmar_descarga',
                        'pedidos.condicion_envio',
                        //DB::raw(" (select dd.nombre_empresa from detalle_pedidos de where de.pedido_id=direcion_grupos.id) as clientes "),
                    ]
                )
                ->where('pedidos.cliente_id', $idrequest)
                ->where('pedidos.estado', '1')
                ->sinDireccionEnvio()
                ->whereIn('pedidos.condicion_envio_code', [
                    Pedido::EN_ATENCION_OPE_INT,
                    Pedido::POR_ATENDER_OPE_INT, Pedido::ATENDIDO_OPE_INT, Pedido::ENVIO_COURIER_JEFE_OPE_INT,
                    Pedido::RECIBIDO_JEFE_OPE_INT,
                    Pedido::RECEPCION_COURIER_INT,
                ]);
            //->whereIn('pedidos.envio', [Pedido::ENVIO_CONFIRMAR_RECEPCION, Pedido::ENVIO_RECIBIDO]);
            //->get();

            return Datatables::query(DB::table($pedidos))
                ->addIndexColumn()
                ->editColumn('condicion_envio', function ($pedido) {
                    $badge_estado='';

                    $color = Pedido::getColorByCondicionEnvio($pedido->condicion_envio);
                    $badge_estado.= '<span class="badge badge-success" style="background-color: ' . $color . '!important;">' . $pedido->condicion_envio . '</span>';
                    return $badge_estado;
                })
                ->rawColumns(['condicion_envio'])
                ->make(true);
        }
    }

    public function recojolistclientes(Request $request)
    {
        $pedidos = null;


        $idrequest = $request->cliente_id;
        $pedidos = Pedido::join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
                        ->join('clientes as c', 'pedidos.cliente_id', 'c.id')
            ->select(
                [
                    'pedidos.id as pedidoid',
                    'c.id',
                    'dp.codigo',
                    'dp.nombre_empresa',

                ]
            )
            ->where('pedidos.cliente_id', $idrequest);

        return Datatables::query(DB::table($pedidos))
            ->addIndexColumn()
            ->make(true);
    }


    public function indexabandono()
    {
        $dateM = Carbon::now()->format('m');
        $dateY = Carbon::now()->format('Y');
        $mirol = Auth::user()->rol;

        $anios = [
            "2020" => '2020 - 2021',
            "2021" => '2021 - 2022',
            "2022" => '2022 - 2023',
            "2023" => '2023 - 2024',
            "2024" => '2024 - 2025',
            "2025" => '2025 - 2026',
            "2026" => '2026 - 2027',
            "2027" => '2027 - 2028',
            "2028" => '2028 - 2029',
            "2029" => '2029 - 2030',
            "2030" => '2030 - 2031',
            "2031" => '2031 - 2032',
        ];

        $superasesor = User::where('rol', 'Super asesor')->count();

        return view('clientes.abandonos', compact('anios', 'dateM', 'dateY', 'superasesor', 'mirol'));
    }

    public function indexRecientes()
    {
        $dateM = Carbon::now()->format('m');
        $dateY = Carbon::now()->format('Y');
        $mirol = Auth::user()->rol;

        $anios = [
            "2020" => '2020 - 2021',
            "2021" => '2021 - 2022',
            "2022" => '2022 - 2023',
            "2023" => '2023 - 2024',
            "2024" => '2024 - 2025',
            "2025" => '2025 - 2026',
            "2026" => '2026 - 2027',
            "2027" => '2027 - 2028',
            "2028" => '2028 - 2029',
            "2029" => '2029 - 2030',
            "2030" => '2030 - 2031',
            "2031" => '2031 - 2032',
        ];

        $superasesor = User::where('rol', 'Super asesor')->count();

        return view('clientes.AbandonoRecientes', compact('anios', 'dateM', 'dateY', 'superasesor', 'mirol'));
    }

    public function indexRecientesIntermedio()
    {
        $dateM = Carbon::now()->format('m');
        $dateY = Carbon::now()->format('Y');
        $mirol = Auth::user()->rol;

        $anios = [
            "2020" => '2020 - 2021',
            "2021" => '2021 - 2022',
            "2022" => '2022 - 2023',
            "2023" => '2023 - 2024',
            "2024" => '2024 - 2025',
            "2025" => '2025 - 2026',
            "2026" => '2026 - 2027',
            "2027" => '2027 - 2028',
            "2028" => '2028 - 2029',
            "2029" => '2029 - 2030',
            "2030" => '2030 - 2031',
            "2031" => '2031 - 2032',
        ];

        $superasesor = User::where('rol', 'Super asesor')->count();

        return view('clientes.AbandonoRecientesIntermedio', compact('anios', 'dateM', 'dateY', 'superasesor', 'mirol'));
    }

    public function indexabandonotabla(Request $request)
    {
        //


        $dateM = Carbon::now()->format('m');
        $dateY = Carbon::now()->format('Y');

        $data = null;

        $anios = [
            "2020" => '2020 - 2021',
            "2021" => '2021 - 2022',
            "2022" => '2022 - 2023',
            "2023" => '2023 - 2024',
            "2024" => '2024 - 2025',
            "2025" => '2025 - 2026',
            "2026" => '2026 - 2027',
            "2027" => '2027 - 2028',
            "2028" => '2028 - 2029',
            "2029" => '2029 - 2030',
            "2030" => '2030 - 2031',
            "2031" => '2031 - 2032',
        ];

        $data = Cliente:://CLIENTES SIN PEDIDOS
        join('users as u', 'clientes.user_id', 'u.id')
            ->leftjoin('pedidos as p', 'clientes.id', 'p.cliente_id')
            ->where('clientes.estado', '1')
            ->where('clientes.tipo', '1')
            ->when($request->has("situacion"), function ($query) use ($request) {
                $query->whereIn('clientes.situacion', [Cliente::ABANDONO_RECIENTE]);
            })
            ->when(!$request->has("situacion"), function ($query) use ($request) {
                $query->whereIn('clientes.situacion', [Cliente::ABANDONO]);
            })
            ->groupBy(
                'clientes.id',
                'clientes.nombre',
                'clientes.icelular',
                'clientes.celular',
                'clientes.estado',
                'u.name',
                'u.identificador',
                'clientes.provincia',
                'clientes.distrito',
                'clientes.direccion',
                'clientes.deuda',
                'clientes.pidio',
                'clientes.situacion'
            )
            ->select('clientes.id',
                'clientes.nombre',
                'clientes.icelular',
                'clientes.celular',
                'clientes.estado',
                'u.name as user',
                'u.identificador',
                'clientes.provincia',
                'clientes.distrito',
                'clientes.direccion',
                'clientes.pidio',
                DB::raw('count(p.created_at) as cantidad'),
                DB::raw('MAX(p.created_at) as fecha'),
                DB::raw('MAX(DATE_FORMAT(p.created_at, "%d")) as dia'),
                DB::raw('MAX(DATE_FORMAT(p.created_at, "%m")) as mes'),
                DB::raw('MAX(DATE_FORMAT(p.created_at, "%Y")) as anio'),
                DB::raw('MONTH(CURRENT_DATE()) as dateM'),
                DB::raw('YEAR(CURRENT_DATE()) as dateY'),
                DB::raw(" (select count(ped.id) from pedidos ped where ped.cliente_id=clientes.id and ped.pago in (0,1) and ped.pagado in (0,1) and ped.created_at >='" . now()->startOfMonth()->format('Y-m-d h:i:s') . "' and ped.estado=1) as pedidos_mes_deuda "),
                DB::raw(" (select count(ped2.id) from pedidos ped2 where ped2.cliente_id=clientes.id and ped2.pago in (0,1) and ped2.pagado in (0,1) and ped2.created_at <='" . now()->startOfMonth()->subMonth()->endOfMonth()->endOfDay()->format('Y-m-d h:i:s') . "'  and ped2.estado=1) as pedidos_mes_deuda_antes "),
                'clientes.deuda',
                'clientes.situacion'
                , DB::raw("(select DATE_FORMAT(dp1.created_at,'%Y-%m-%d %h:%i:%s') from pedidos dp1 where dp1.estado=1 and dp1.cliente_id=clientes.id order by dp1.created_at desc limit 1) as fechaultimopedido")
                , DB::raw(" (select (dp.codigo) from pedidos dp where dp.estado=1 and dp.cliente_id=clientes.id order by dp.created_at desc limit 1) as codigoultimopedido ")
            );

        if (Auth::user()->rol == "Llamadas") {

            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.llamada', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');
            //$pedidos=$pedidos->WhereIn('pedidos.user_id',$usersasesores);
            $data = $data->WhereIn("u.identificador", $usersasesores);

        } else if (Auth::user()->rol == "Jefe de llamadas") {

            /*$usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.llamada', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $data = $data->WhereIn("u.identificador", $usersasesores);*/

        } elseif (Auth::user()->rol == "Asesor") {

            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.identificador', Auth::user()->identificador)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $data = $data->WhereIn('u.identificador', $usersasesores);

        } else if (Auth::user()->rol == "Encargado") {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.supervisor', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $data = $data->WhereIn("u.identificador", $usersasesores);
        } else {

            $data = $data;

        }
        //$data=$data->get();

        return Datatables::of(DB::table($data))
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                $btn = "";
                return $btn;
            })
            ->rawColumns(['action'])
            ->make(true);
        //}
    }

    public function indexabandonointermediotabla(Request $request)
    {
        $dateM = Carbon::now()->format('m');
        $dateY = Carbon::now()->format('Y');

        $data = null;

        $anios = [
            "2020" => '2020 - 2021',
            "2021" => '2021 - 2022',
            "2022" => '2022 - 2023',
            "2023" => '2023 - 2024',
            "2024" => '2024 - 2025',
            "2025" => '2025 - 2026',
            "2026" => '2026 - 2027',
            "2027" => '2027 - 2028',
            "2028" => '2028 - 2029',
            "2029" => '2029 - 2030',
            "2030" => '2030 - 2031',
            "2031" => '2031 - 2032',
        ];

        $data = Cliente::
        join('users as u', 'clientes.user_id', 'u.id')
            ->join('listado_resultados as lr', 'clientes.id', 'lr.id')
            ->leftjoin('pedidos as p', 'clientes.id', 'p.cliente_id')
            ->where('clientes.estado', '1')
            ->where('clientes.tipo', '1')
            ->where('clientes.situacion', 'CASI ABANDONO')
            //->where('lr.s_2022_11','ABANDONO RECIENTE')
            //->where('lr.s_2022_12','ABANDONO')
            /*->when($request->has("situacion"), function ($query) use ($request) {
                $query->whereIn('clientes.situacion', [Cliente::ABANDONO_RECIENTE]);
            })
            ->when(!$request->has("situacion"), function ($query) use ($request) {
                $query->whereIn('clientes.situacion', [Cliente::ABANDONO_PERMANENTE]);
            })*/
            ->groupBy(
                'clientes.id',
                'clientes.nombre',
                'clientes.icelular',
                'clientes.celular',
                'clientes.estado',
                'u.name',
                'u.identificador',
                'clientes.provincia',
                'clientes.distrito',
                'clientes.direccion',
                'clientes.deuda',
                'clientes.pidio',
                'clientes.situacion'
            )
            ->select('clientes.id',
                'clientes.nombre',
                'clientes.icelular',
                'clientes.celular',
                'clientes.estado',
                'u.name as user',
                'u.identificador',
                'clientes.provincia',
                'clientes.distrito',
                'clientes.direccion',
                'clientes.pidio',
                DB::raw('count(p.created_at) as cantidad'),
                DB::raw('MAX(p.created_at) as fecha'),
                DB::raw('MAX(DATE_FORMAT(p.created_at, "%d")) as dia'),
                DB::raw('MAX(DATE_FORMAT(p.created_at, "%m")) as mes'),
                DB::raw('MAX(DATE_FORMAT(p.created_at, "%Y")) as anio'),
                DB::raw('MONTH(CURRENT_DATE()) as dateM'),
                DB::raw('YEAR(CURRENT_DATE()) as dateY'),
                DB::raw(" (select count(ped.id) from pedidos ped where ped.cliente_id=clientes.id and ped.pago in (0,1) and ped.pagado in (0,1) and ped.created_at >='" . now()->startOfMonth()->format('Y-m-d h:i:s') . "' and ped.estado=1) as pedidos_mes_deuda "),
                DB::raw(" (select count(ped2.id) from pedidos ped2 where ped2.cliente_id=clientes.id and ped2.pago in (0,1) and ped2.pagado in (0,1) and ped2.created_at <='" . now()->startOfMonth()->subMonth()->endOfMonth()->endOfDay()->format('Y-m-d h:i:s') . "'  and ped2.estado=1) as pedidos_mes_deuda_antes "),
                'clientes.deuda',
                'clientes.situacion'
                , DB::raw("(select DATE_FORMAT(dp1.created_at,'%Y-%m-%d %h:%i:%s') from pedidos dp1 where dp1.estado=1 and dp1.cliente_id=clientes.id order by dp1.created_at desc limit 1) as fechaultimopedido")
                , DB::raw(" (select (dp.codigo) from pedidos dp where dp.estado=1 and dp.cliente_id=clientes.id order by dp.created_at desc limit 1) as codigoultimopedido ")
            );

        if (Auth::user()->rol == "Llamadas") {

            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.llamada', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');
            $data = $data->WhereIn("u.identificador", $usersasesores);

        } elseif (Auth::user()->rol == "Asesor") {

            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.identificador', Auth::user()->identificador)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $data = $data->WhereIn('u.identificador', $usersasesores);

        } else if (Auth::user()->rol == "Encargado") {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.supervisor', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $data = $data->WhereIn("u.identificador", $usersasesores);
        } else {

            $data = $data;

        }

        return Datatables::of(DB::table($data))
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                $btn = "";
                return $btn;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function indexrecurrente()
    {
        $dateM = Carbon::now()->format('m');
        $dateY = Carbon::now()->format('Y');
        $mirol = Auth::user()->rol;

        $data = null;

        $anios = [
            "2020" => '2020 - 2021',
            "2021" => '2021 - 2022',
            "2022" => '2022 - 2023',
            "2023" => '2023 - 2024',
            "2024" => '2024 - 2025',
            "2025" => '2025 - 2026',
            "2026" => '2026 - 2027',
            "2027" => '2027 - 2028',
            "2028" => '2028 - 2029',
            "2029" => '2029 - 2030',
            "2030" => '2030 - 2031',
            "2031" => '2031 - 2032',
        ];

        $superasesor = User::where('rol', 'Super asesor')->count();
        if (Auth::user()->rol == "Llamadas" || Auth::user()->rol == "Llamadas") {
            $users = User::
            where('estado', '1')
                ->whereIn('rol', ['Asesor', 'Super asesor'])
                ->where('users.llamada', Auth::user()->id)
                ->pluck('identificador', 'id');
        } else {
            $users = User::
            where('estado', '1')
                ->whereIn('rol', ['Asesor', 'Super asesor'])
                //->where('users.llamada', Auth::user()->id)
                ->pluck('identificador', 'id');
        }
        return view('clientes.recurrentes', compact('superasesor', 'users', 'dateM', 'dateY', 'anios', 'mirol'));
    }

    public function indexnuevo()
    {
        $dateM = Carbon::now()->format('m');
        $dateY = Carbon::now()->format('Y');
        $mirol = Auth::user()->rol;

        $data = null;

        $anios = [
            "2020" => '2020 - 2021',
            "2021" => '2021 - 2022',
            "2022" => '2022 - 2023',
            "2023" => '2023 - 2024',
            "2024" => '2024 - 2025',
            "2025" => '2025 - 2026',
            "2026" => '2026 - 2027',
            "2027" => '2027 - 2028',
            "2028" => '2028 - 2029',
            "2029" => '2029 - 2030',
            "2030" => '2030 - 2031',
            "2031" => '2031 - 2032',
        ];

        $superasesor = User::where('rol', 'Super asesor')->count();
        if (Auth::user()->rol == "Llamadas" || Auth::user()->rol == "Llamadas") {
            $users = User::
            where('estado', '1')
                ->whereIn('rol', ['Asesor', 'Super asesor'])
                ->where('users.llamada', Auth::user()->id)
                ->pluck('identificador', 'id');
        } else {
            $users = User::
            where('estado', '1')
                ->whereIn('rol', ['Asesor', 'Super asesor'])
                //->where('users.llamada', Auth::user()->id)
                ->pluck('identificador', 'id');
        }
        return view('clientes.nuevos', compact('superasesor', 'users', 'dateM', 'dateY', 'mirol', 'anios'));
    }

    public function indexrecuperado()
    {
        $dateM = Carbon::now()->format('m');
        $dateY = Carbon::now()->format('Y');
        $mirol = Auth::user()->rol;

        $data = null;

        $anios = [
            "2020" => '2020 - 2021',
            "2021" => '2021 - 2022',
            "2022" => '2022 - 2023',
            "2023" => '2023 - 2024',
            "2024" => '2024 - 2025',
            "2025" => '2025 - 2026',
            "2026" => '2026 - 2027',
            "2027" => '2027 - 2028',
            "2028" => '2028 - 2029',
            "2029" => '2029 - 2030',
            "2030" => '2030 - 2031',
            "2031" => '2031 - 2032',
        ];

        $superasesor = User::where('rol', 'Super asesor')->count();
        if (Auth::user()->rol == "Llamadas" || Auth::user()->rol == "Llamadas") {
            $users = User::
            where('estado', '1')
                ->whereIn('rol', ['Asesor', 'Super asesor'])
                ->where('users.llamada', Auth::user()->id)
                ->pluck('identificador', 'id');
        } else {
            $users = User::
            where('estado', '1')
                ->whereIn('rol', ['Asesor', 'Super asesor'])
                //->where('users.llamada', Auth::user()->id)
                ->pluck('identificador', 'id');
        }
        return view('clientes.recuperados', compact('superasesor', 'users', 'dateM', 'dateY', 'mirol', 'anios'));
    }


    public function indexRecuperadoRecientes()
    {
        $dateM = Carbon::now()->format('m');
        $dateY = Carbon::now()->format('Y');
        $mirol = Auth::user()->rol;

        $data = null;

        $anios = [
            "2020" => '2020 - 2021',
            "2021" => '2021 - 2022',
            "2022" => '2022 - 2023',
            "2023" => '2023 - 2024',
            "2024" => '2024 - 2025',
            "2025" => '2025 - 2026',
            "2026" => '2026 - 2027',
            "2027" => '2027 - 2028',
            "2028" => '2028 - 2029',
            "2029" => '2029 - 2030',
            "2030" => '2030 - 2031',
            "2031" => '2031 - 2032',
        ];

        $superasesor = User::where('rol', 'Super asesor')->count();
        if (Auth::user()->rol == "Llamadas" || Auth::user()->rol == "Llamadas") {
            $users = User::
            where('estado', '1')
                ->whereIn('rol', ['Asesor', 'Super asesor'])
                ->where('users.llamada', Auth::user()->id)
                ->pluck('identificador', 'id');
        } else {
            $users = User::
            where('estado', '1')
                ->whereIn('rol', ['Asesor', 'Super asesor'])
                //->where('users.llamada', Auth::user()->id)
                ->pluck('identificador', 'id');
        }
        return view('clientes.recuperadosRecientes', compact('superasesor', 'users', 'dateM', 'dateY', 'mirol', 'anios'));
    }


    public function indexnuevotabla(Request $request)
    {
        //
        $dateM = Carbon::now()->format('m');
        $dateY = Carbon::now()->format('Y');

        $data = null;

        $anios = [
            "2020" => '2020 - 2021',
            "2021" => '2021 - 2022',
            "2022" => '2022 - 2023',
            "2023" => '2023 - 2024',
            "2024" => '2024 - 2025',
            "2025" => '2025 - 2026',
            "2026" => '2026 - 2027',
            "2027" => '2027 - 2028',
            "2028" => '2028 - 2029',
            "2029" => '2029 - 2030',
            "2030" => '2030 - 2031',
            "2031" => '2031 - 2032',
        ];

        $data = Cliente:://CLIENTES SIN PEDIDOS
        join('users as u', 'clientes.user_id', 'u.id')
            ->leftjoin('pedidos as p', 'clientes.id', 'p.cliente_id')
            ->where('clientes.estado', '1')
            ->where('clientes.tipo', '1')
            ->whereIn('clientes.situacion', [Cliente::NUEVO])
            ->groupBy(
                'clientes.id',
                'clientes.nombre',
                'clientes.icelular',
                'clientes.celular',
                'clientes.estado',
                'u.name',
                'u.identificador',
                'clientes.provincia',
                'clientes.distrito',
                'clientes.direccion',
                'clientes.deuda',
                'clientes.pidio',
                'clientes.situacion'
            )
            ->select('clientes.id',
                'clientes.nombre',
                'clientes.icelular',
                'clientes.celular',
                'clientes.estado',
                'u.name as user',
                'u.identificador',
                'clientes.provincia',
                'clientes.distrito',
                'clientes.direccion',
                'clientes.pidio',
                DB::raw('count(p.created_at) as cantidad'),
                DB::raw('MAX(p.created_at) as fecha'),
                DB::raw('MAX(DATE_FORMAT(p.created_at, "%d")) as dia'),
                DB::raw('MAX(DATE_FORMAT(p.created_at, "%m")) as mes'),
                DB::raw('MAX(DATE_FORMAT(p.created_at, "%Y")) as anio'),
                DB::raw('MONTH(CURRENT_DATE()) as dateM'),
                DB::raw('YEAR(CURRENT_DATE()) as dateY'),
                DB::raw(" (select count(ped.id) from pedidos ped where ped.cliente_id=clientes.id and ped.pago in (0,1) and ped.pagado in (0,1) and ped.created_at >='" . now()->startOfMonth()->format('Y-m-d h:i:s') . "' and ped.estado=1) as pedidos_mes_deuda "),
                DB::raw(" (select count(ped2.id) from pedidos ped2 where ped2.cliente_id=clientes.id and ped2.pago in (0,1) and ped2.pagado in (0,1) and ped2.created_at <='" . now()->startOfMonth()->subMonth()->endOfMonth()->endOfDay()->format('Y-m-d h:i:s') . "'  and ped2.estado=1) as pedidos_mes_deuda_antes "),
                'clientes.deuda',
                //DB::raw(" (select lr.s_2022_11 from clientes c inner join listado_resultados lr on c.id=lr.id limit 1) as situacion")
                'clientes.situacion'
                , DB::raw("(select DATE_FORMAT(dp1.created_at,'%Y-%m-%d %h:%i:%s') from pedidos dp1 where dp1.estado=1 and dp1.cliente_id=clientes.id order by dp1.created_at desc limit 1) as fechaultimopedido")
                , DB::raw(" (select (dp.codigo) from pedidos dp where dp.estado=1 and dp.cliente_id=clientes.id order by dp.created_at desc limit 1) as codigoultimopedido ")
            );

        if (Auth::user()->rol == "Llamadas") {

            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.llamada', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');
            //$pedidos=$pedidos->WhereIn('pedidos.user_id',$usersasesores);
            $data = $data->WhereIn("u.identificador", $usersasesores);


        } else if (Auth::user()->rol == "Jefe de llamadas") {

            /*$usersasesores = User::where('users.rol', 'Asesor')
                  ->where('users.estado', '1')
                  ->where('users.llamada', Auth::user()->id)
                  ->select(
                      DB::raw("users.identificador as identificador")
                  )
                  ->pluck('users.identificador');

              $data = $data->WhereIn("u.identificador", $usersasesores);*/
        } elseif (Auth::user()->rol == "Asesor") {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.identificador', Auth::user()->identificador)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $data = $data->WhereIn('u.identificador', $usersasesores);
        } else if (Auth::user()->rol == "Encargado") {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.supervisor', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $data = $data->WhereIn("u.identificador", $usersasesores);
        } else {

            $data = $data;

        }
        //$data=$data->get();

        return Datatables::of(DB::table($data))
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                $btn = "";
                return $btn;
            })
            ->rawColumns(['action'])
            ->make(true);
        //}
    }

    public function indexrecurrentetabla(Request $request)
    {
        //
        $dateM = Carbon::now()->format('m');
        $dateY = Carbon::now()->format('Y');

        $data = null;

        $anios = [
            "2020" => '2020 - 2021',
            "2021" => '2021 - 2022',
            "2022" => '2022 - 2023',
            "2023" => '2023 - 2024',
            "2024" => '2024 - 2025',
            "2025" => '2025 - 2026',
            "2026" => '2026 - 2027',
            "2027" => '2027 - 2028',
            "2028" => '2028 - 2029',
            "2029" => '2029 - 2030',
            "2030" => '2030 - 2031',
            "2031" => '2031 - 2032',
        ];

        $data = Cliente:://CLIENTES SIN PEDIDOS
        join('users as u', 'clientes.user_id', 'u.id')
            ->leftjoin('pedidos as p', 'clientes.id', 'p.cliente_id')
            ->where('clientes.estado', '1')
            ->where('clientes.tipo', '1')
            ->whereIn('clientes.situacion', [Cliente::RECURRENTE])
            ->groupBy(
                'clientes.id',
                'clientes.nombre',
                'clientes.icelular',
                'clientes.celular',
                'clientes.estado',
                'u.name',
                'u.identificador',
                'clientes.provincia',
                'clientes.distrito',
                'clientes.direccion',
                'clientes.deuda',
                'clientes.pidio',
                'clientes.situacion'
            )
            ->select('clientes.id',
                'clientes.nombre',
                'clientes.icelular',
                'clientes.celular',
                'clientes.estado',
                'u.name as user',
                'u.identificador',
                'clientes.provincia',
                'clientes.distrito',
                'clientes.direccion',
                'clientes.pidio',
                DB::raw('count(p.created_at) as cantidad'),
                DB::raw('MAX(p.created_at) as fecha'),
                DB::raw('MAX(DATE_FORMAT(p.created_at, "%d")) as dia'),
                DB::raw('MAX(DATE_FORMAT(p.created_at, "%m")) as mes'),
                DB::raw('MAX(DATE_FORMAT(p.created_at, "%Y")) as anio'),
                DB::raw('MONTH(CURRENT_DATE()) as dateM'),
                DB::raw('YEAR(CURRENT_DATE()) as dateY'),
                DB::raw(" (select count(ped.id) from pedidos ped where ped.cliente_id=clientes.id and ped.pago in (0,1) and ped.pagado in (0,1) and ped.created_at >='2022-11-01 00:00:00' and ped.estado=1) as pedidos_mes_deuda "),
                DB::raw(" (select count(ped2.id) from pedidos ped2 where ped2.cliente_id=clientes.id and ped2.pago in (0,1) and ped2.pagado in (0,1) and ped2.created_at <='2022-10-31 00:00:00'  and ped2.estado=1) as pedidos_mes_deuda_antes "),
                'clientes.deuda',
                //DB::raw(" (select lr.s_2022_11 from clientes c inner join listado_resultados lr on c.id=lr.id limit 1) as situacion")
                'clientes.situacion'
                , DB::raw("(select DATE_FORMAT(dp1.created_at,'%Y-%m-%d %h:%i:%s') from pedidos dp1 where dp1.estado=1 and dp1.cliente_id=clientes.id order by dp1.created_at desc limit 1) as fechaultimopedido")
                , DB::raw(" (select (dp.codigo) from pedidos dp where dp.estado=1 and dp.cliente_id=clientes.id order by dp.created_at desc limit 1) as codigoultimopedido ")
            );

        if (Auth::user()->rol == "Llamadas") {

            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.llamada', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');
            //$pedidos=$pedidos->WhereIn('pedidos.user_id',$usersasesores);
            $data = $data->WhereIn("u.identificador", $usersasesores);


        } else if (Auth::user()->rol == "Jefe de llamadas") {
            /*$usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.llamada', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $data = $data->WhereIn("u.identificador", $usersasesores);*/
        } elseif (Auth::user()->rol == "Asesor") {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.identificador', Auth::user()->identificador)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $data = $data->WhereIn('u.identificador', $usersasesores);
        } else if (Auth::user()->rol == "Encargado") {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.supervisor', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $data = $data->WhereIn("u.identificador", $usersasesores);
        } else {

            $data = $data;

        }
        //$data=$data->get();

        return Datatables::of(DB::table($data))
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                $btn = "";
                return $btn;
            })
            ->rawColumns(['action'])
            ->make(true);
        //}
    }

    public function indexrecuperadotabla(Request $request)
    {
        //
        $dateM = Carbon::now()->format('m');
        $dateY = Carbon::now()->format('Y');

        $data = null;

        $anios = [
            "2020" => '2020 - 2021',
            "2021" => '2021 - 2022',
            "2022" => '2022 - 2023',
            "2023" => '2023 - 2024',
            "2024" => '2024 - 2025',
            "2025" => '2025 - 2026',
            "2026" => '2026 - 2027',
            "2027" => '2027 - 2028',
            "2028" => '2028 - 2029',
            "2029" => '2029 - 2030',
            "2030" => '2030 - 2031',
            "2031" => '2031 - 2032',
        ];

        $data = Cliente:://CLIENTES SIN PEDIDOS
        join('users as u', 'clientes.user_id', 'u.id')
            ->leftjoin('pedidos as p', 'clientes.id', 'p.cliente_id')
            ->where('clientes.estado', '1')
            ->where('clientes.tipo', '1')
            ->when($request->has("situacion"), function ($query) use ($request) {
                $query->whereIn('clientes.situacion', [$request->situacion]);
            })
            ->when(!$request->has("situacion"), function ($query) use ($request) {
                $query->whereIn('clientes.situacion', [Cliente::RECUPERADO_PERMANENTE]);
            })
            ->groupBy(
                'clientes.id',
                'clientes.nombre',
                'clientes.icelular',
                'clientes.celular',
                'clientes.estado',
                'u.name',
                'u.identificador',
                'clientes.provincia',
                'clientes.distrito',
                'clientes.direccion',
                'clientes.deuda',
                'clientes.pidio',
                'clientes.situacion'
            )
            ->select('clientes.id',
                'clientes.nombre',
                'clientes.icelular',
                'clientes.celular',
                'clientes.estado',
                'u.name as user',
                'u.identificador',
                'clientes.provincia',
                'clientes.distrito',
                'clientes.direccion',
                'clientes.pidio',
                DB::raw('count(p.created_at) as cantidad'),
                DB::raw('MAX(p.created_at) as fecha'),
                DB::raw('MAX(DATE_FORMAT(p.created_at, "%d")) as dia'),
                DB::raw('MAX(DATE_FORMAT(p.created_at, "%m")) as mes'),
                DB::raw('MAX(DATE_FORMAT(p.created_at, "%Y")) as anio'),
                DB::raw('MONTH(CURRENT_DATE()) as dateM'),
                DB::raw('YEAR(CURRENT_DATE()) as dateY'),
                DB::raw(" (select count(ped.id) from pedidos ped where ped.cliente_id=clientes.id and ped.pago in (0,1) and ped.pagado in (0,1) and ped.created_at >='" . now()->startOfMonth()->format('Y-m-d h:i:s') . "' and ped.estado=1) as pedidos_mes_deuda "),
                DB::raw(" (select count(ped2.id) from pedidos ped2 where ped2.cliente_id=clientes.id and ped2.pago in (0,1) and ped2.pagado in (0,1) and ped2.created_at <='" . now()->startOfMonth()->subMonth()->endOfMonth()->endOfDay()->format('Y-m-d h:i:s') . "'  and ped2.estado=1) as pedidos_mes_deuda_antes "),
                'clientes.deuda',
                //DB::raw(" (select lr.s_2022_11 from clientes c inner join listado_resultados lr on c.id=lr.id limit 1) as situacion")
                'clientes.situacion'
                , DB::raw("(select DATE_FORMAT(dp1.created_at,'%Y-%m-%d %h:%i:%s') from pedidos dp1 where dp1.estado=1 and dp1.cliente_id=clientes.id order by dp1.created_at desc limit 1) as fechaultimopedido")
                , DB::raw(" (select (dp.codigo) from pedidos dp where dp.estado=1 and dp.cliente_id=clientes.id order by dp.created_at desc limit 1) as codigoultimopedido ")
            );

        if (Auth::user()->rol == "Llamadas") {

            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.llamada', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');
            //$pedidos=$pedidos->WhereIn('pedidos.user_id',$usersasesores);
            $data = $data->WhereIn("u.identificador", $usersasesores);


        } else if (Auth::user()->rol == "Jefe de llamadas") {
            /*$usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.llamada', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $data = $data->WhereIn("u.identificador", $usersasesores);*/
        } elseif (Auth::user()->rol == "Asesor") {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.identificador', Auth::user()->identificador)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $data = $data->WhereIn('u.identificador', $usersasesores);

        } else if (Auth::user()->rol == "Encargado") {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.supervisor', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $data = $data->WhereIn("u.identificador", $usersasesores);
        }

        return Datatables::of(DB::table($data))
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                $btn = "";
                return $btn;
            })
            ->rawColumns(['action'])
            ->make(true);
        //}
    }

    public function deudasCopyAjax(Request $request)
    {
        $this->validate($request, [
            'cliente_id' => 'required'
        ]);
        $cliente = Cliente::query()->with('user')->findOrFail($request->cliente_id);
        $mensajesRandom = [];
        $mensajesRandom[] = '*Amigo buenos dias, por favor neceisto que me cancele el pago, le mando el resumen*';
        $mensajesRandom[] = '*Amigo buenos días el saldito pendiente que tenemos por favor no se olvide, le envio el detalle.';
        $mensajesRandom[] = '*Buenas tardes amigo, le envio el total de la deuda para que me deposite por favor, le mando el resumen para que me cancele. ';

        $messajeKey = array_rand($mensajesRandom);
        $messaje = $mensajesRandom[$messajeKey];

        $pedidos = Pedido::query()->with(['cliente', 'pagoPedidos', 'detallePedido'])
            ->activo()
            ->join('detalle_pedidos as dp', 'pedidos.id', 'dp.pedido_id')
            ->where('dp.estado', '1')
            ->where('pedidos.pendiente_anulacion', '0')
            ->select([
                'pedidos.id',
                'pedidos.codigo',
                'pedidos.created_at',
                'dp.nombre_empresa',
                'dp.cantidad',
                'dp.porcentaje',
                'dp.ft',
                'dp.courier',
                'dp.total',
                'dp.saldo as diferencia',
            ])
            //->where('pedidos.created_at', '<=', now()->subDays(7)->format('Y-m-d H:i:s'))
            ->where('pedidos.cliente_id', $cliente->id)
            //->where('pedidos.condicion_code', '<>', Pedido::ANULADO_INT)
            ->get()
            ->map(function (Pedido $pedido) {
                $pedido->adelanto = $pedido->pagoPedidos()->activo()->sum('abono');
                $pedido->deuda_total = $pedido->detallePedidos()->activo()->sum("saldo");
                return $pedido;
            })
            ->filter(fn(Pedido $pedido) => $pedido->deuda_total>3);
            //->filter(fn(Pedido $pedido) => $pedido->adelanto <= ($pedido->deuda_total - 3));
        $totalDeuda = $pedidos->sum('diferencia');

        $identificador = (float)$cliente->user->identificador;
        if ($identificador <= 5) {
            $titular_cuenta = 'EPIFANIO';
        } else {
            $titular_cuenta = 'GABRIEL';
        }

        $cuentas_bancarias = CuentaBancaria::query()
            ->select(['cuenta_bancarias.*', 'entidad_bancarias.nombre as entidad_bancaria', 'titulares.nombre as titular_cuenta'])
            ->join('entidad_bancarias', 'entidad_bancarias.id', 'cuenta_bancarias.banco')
            ->join('titulares', 'titulares.id', 'cuenta_bancarias.titular')
            ->activo()
            ->where('titulares.nombre', 'like', '%' . $titular_cuenta . '%')
            ->get()
            ->groupBy('titular_cuenta');

        return response()->json([
            "html" => view('clientes.response.modal_data_clientes_deuda', compact('messaje', 'pedidos', 'totalDeuda', 'cliente', 'cuentas_bancarias'))->render()
        ]);
    }

    public function celularduplicado(Request $request)
    {

        $request->celular;
        $validar = Cliente::where('celular', $request->celular)->where('id', '<>', $request->id)->count();
        //return $validar;
        $status = true;
        $data = 'NO PUEDE CONTINUAR';
        if ($validar > 0) {
            $status = false;
            $data = 'NO PUEDE CONTINUAR';
        }

        return response()->json([
            "html" => array('status' => $status, 'data' => $data)
        ]);
    }
}
