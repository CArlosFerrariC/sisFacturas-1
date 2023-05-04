<?php

namespace App\Http\Controllers;

//use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Porcentaje;
use App\Models\User;

//use App\DataTables\BasefriaDataTable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use DataTables;
use Illuminate\Support\HtmlString;

class BasefriaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

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

        return view('base_fria.index', compact('superasesor', 'users'));
    }

    public function indextabla(Request $request)
    {
        //
        //return $dataTable->render('base_fria.index');
        //if ($request->ajax()) {

        DB::enableQueryLog();


        $data = Cliente::
        join('users as u', 'clientes.user_id', 'u.id')
            ->select(['clientes.id',
                'clientes.nombre',
                'clientes.icelular',
                'clientes.celular',
                'u.identificador as identificador',
                'u.rol',
                'clientes.estado',
                'clientes.situacion'
            ])
            ->where('clientes.tipo', '0');

        if (Auth::user()->rol == 'Llamadas') {
        }
        else if (Auth::user()->rol == 'Asesor') {
            $usersasesores = User::where('users.rol', 'Asesor')
                //->where('users.estado', '1')
                ->where('users.clave_pedidos', Auth::user()->clave_pedidos)
                ->select(
                    DB::raw("users.clave_pedidos as clave_pedidos")
                )
                ->pluck('users.clave_pedidos');
            $data = $data->WhereIn('u.clave_pedidos', $usersasesores);
        } else if (Auth::user()->rol == 'Encargado') {
            $usersasesores = User::where('users.rol', 'Asesor')
                ->where('users.estado', '1')
                ->where('users.supervisor', Auth::user()->id)
                ->select(
                    DB::raw("users.clave_pedidos as clave_pedidos")
                )
                ->pluck('users.clave_pedidos');
            $data = $data->WhereIn("u.clave_pedidos", $usersasesores);
        }else if (Auth::user()->rol == User::ROL_ASISTENTE_PUBLICIDAD) {
            $usersasesores = User::where('users.rol', User::ROL_ASESOR)
                ->where('users.estado', '1')
                ->whereIn('users.clave_pedidos', ['15','16','17','18','19','20'])
                ->select(
                    DB::raw("users.clave_pedidos as clave_pedidos")
                )
                ->pluck('users.clave_pedidos');
            $data = $data->WhereIn('u.clave_pedidos', $usersasesores);
        } else {
            $data = $data;
        }
        $data = $data->get();

        // dd(DB::getQueryLog());
        // exit;

        return Datatables::of($data)
            ->addIndexColumn()
            ->editColumn('action', function ($row) {
                $btn = "";

                $btn = $btn . '<a href="" data-target="#modal-convertir" data-toggle="modal" data-opcion="' . $row->id . '"><button class="btn btn-info btn-sm"><i class="fas fa-person"></i> Convertir a cliente</button></a>';

                if (auth()->user()->can('base_fria.edit')):
                    $btn = $btn.'<a href="'.route('basefria.edit', $row->id).'" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Editar</a>';
                endif;

                //if(\auth()->user()->can('clientes.destroy')) {
                    if($row->estado=='1')
                    {
                        $btn = $btn . '<a href="" data-target="#modal-delete" data-toggle="modal" data-cliente="'.$row->id.'" data-asesor="'.trim($row->identificador).'"><button class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i> Bloquear</button></a>';
                    }
                //}

                return $btn;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function cargarid(Request $request)
    {
        if (!$request->basefria_id) {
            $html = '';
        } else {
            $data = Cliente::
            join('users as u', 'clientes.user_id', 'u.id')
                ->select('clientes.id',
                    'clientes.nombre',
                    'clientes.celular',
                    'u.identificador as identificador',
                    'u.rol'
                )
                ->where('clientes.estado', '1')
                ->where('clientes.tipo', '0')
                ->where('clientes.id', $request->basefria_id)
                ->get();

            $html = $data;
        }
        return response()->json(['html' => $html]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $users = User::query()->where('estado','=',1)->whereIn('rol',[User::ROL_ASESOR_ADMINISTRATIVO,User::ROL_ASESOR])
            ->select([
                'identificador', 'id'
            ])
            ->pluck('identificador', 'id');
        /*$usersB = User::where('users.estado', '1')
            ->whereIn('rol', [User::ROL_ASESOR_ADMINISTRATIVO])//51
            ->first();*/
        //dd($usersB);
        //$users = collect();
        //$users->put($usersB->id, $usersB->identificador);
        /*$usersall = User::select(
            DB::raw("CONCAT(identificador,' (ex ',IFNULL(exidentificador,''),')') AS identificador"), 'id'
        )
            ->where('users.rol', 'Asesor')
            ->whereIn('users.estado', ['0','1'])
            ->pluck('identificador', 'id');*/
       /* foreach ($usersall as $key => $value) {
            $users->put($key, $value);
        }*/
        return view('base_fria.create', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $user = User::where('id', $request->user_id)->first();//el asesor
        $letra=$user->letra;
        $searchCliente = Cliente::query()->with('user')->where('celular', '=', $request->celular)->first();

        $messages = [];

        if ($searchCliente != null) {
            $messages = [
                'celular.unique' => 'EL CELULAR INGRESADO SE ENCUENTA ASIGNADO AL ASESOR <b>' . $searchCliente->user->identificador.'</b>',
            ];
        }
        $request->validate([
            'celular' => 'required|unique:clientes',
        ], $messages);

        //return $request;
        try {
            DB::beginTransaction();

            $cliente = Cliente::create([
                'nombre' => $request->nombre,
                'celular' => $request->celular,
                'icelular'=> $letra,
                'user_id' => $request->user_id,
                'user_identificador' => $user->identificador,
                'user_clavepedido' => $user->clave_pedidos,
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
            $cliente->update([
                'correlativo'=>'BF'.($user->clave_pedidos).$cliente->id
            ]);
            DB::commit();
        } catch (\Throwable $th) {
            throw $th;
            /* DB::rollback();
            dd($th); */
        }
        return redirect()->route('basefria')->with('info', 'registrado');

    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Cliente $basefrium)
    {
        //
        //return $basefrium;
        $mirol = Auth::user()->rol;
        $users = User::where('users.estado', '1')
            ->whereIn('users.rol', ['Asesor', 'ASESOR ADMINISTRATIVO'])
            ->select(DB::raw('CONCAT(COALESCE(identificador, "") , " ", CASE WHEN COALESCE(exidentificador, "") <>"" THEN CONCAT("(", exidentificador,")") ELSE "" END ) AS full_identificador'),'id')
            ->pluck('full_identificador', 'id');
        $porcentajes = Porcentaje::where('cliente_id', $basefrium->id)->get();

        return view('base_fria.edit', compact('basefrium', 'users', 'porcentajes', 'mirol'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Cliente $basefrium)
    {
        $user = User::where('id', $request->user_id)->first();//el asesor
        if ($request->nombre==null){
            $request->nombre="";
        }
        $letra=$user->letra;
        //return $basefrium;

        $request->validate([
            //'nombre' => 'required',
            //'dni' => 'required',
            'celular' => 'required',
            //'provincia' => 'required',
            //'distrito' => 'required',
            //'direccion' => 'required',
            //'referencia' => 'required',
            //'porcentaje' => 'required',
        ]);

        $datosafgectados=$basefrium->update([
            'nombre' => $request->nombre,
            'dni' => $request->dni,
            'celular' => $request->celular,
            'icelular' => $letra,
            'user_id'=>$request->user_id,
            'tipo' => '0'
        ]);
        /*if ($datosafgectados > 0) {
            dd("VEr",$request,$basefrium);
        } else {
            dd($request,$basefrium,$user,$letra);
        }*/

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
    public function destroy($id)
    {
        //
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

            $nombre_Responsable=User::where('id',Auth::user()->id)->first()->name;

            $cliente->update([
                'motivo_anulacion' => $request->motivo,
                'responsable_anulacion' => $nombre_Responsable,
                'user_anulacion_id' => Auth::user()->id,
                'fecha_anulacion' => now(),
                'estado' => '0',
                'path_adjunto_anular' => null,
                'path_adjunto_anular_disk' => 'pstorage',
                'situacion'=>'BLOQUEADO',
            ]);

            $html = $cliente;

        }
        return response()->json(['html' => $html]);
    }

    public function updatebfpost(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'celular' => 'required',
            'porcentaje_fsb' => 'required',
            'porcentaje_esb' => 'required',
            'porcentaje_fcb' => 'required',
            'porcentaje_ecb' => 'required',
        ]);

        $cliente = Cliente::where('clientes.id', $request->hiddenID)->update([
            'nombre' => $request->nombre,
            'celular' => $request->celular,
            'deuda' => '0',
            'pidio' => '0',
            'tipo' => '1',
            'saldo' => '0',
            'fsb_porcentaje' => $request->porcentaje_fsb,
            'esb_porcentaje' => $request->porcentaje_esb,
            'fcb_porcentaje' => $request->porcentaje_fcb,
            'ecb_porcentaje' => $request->porcentaje_ecb
        ]);

        try {
            DB::beginTransaction();

            $nombreporcentaje = $request->nombreporcentaje;
            $valoresporcentaje = $request->porcentaje;
            $cont = 0;

            $p_fsb = Porcentaje::where('cliente_id', '=', $request->hiddenID)
                ->where('nombre','=','FISICO - sin banca')->first();
            if($p_fsb===null)
            {
                Porcentaje::create([
                    'cliente_id' => $request->hiddenID,
                    'cod_porcentaje' => 'FSB',
                    'nombre' => 'FISICO - sin banca',
                    'porcentaje' => $request->porcentaje_fsb,
                ]);
            }

            $p_esb = Porcentaje::where('cliente_id', '=', $request->hiddenID)
                ->where('nombre','=','ELECTRONICA - sin banca')->first();
            if($p_esb===null)
            {
                Porcentaje::create([
                    'cliente_id' => $request->hiddenID,
                    'cod_porcentaje' => 'ESB',
                    'nombre' => 'ELECTRONICA - sin banca',
                    'porcentaje' => $request->porcentaje_esb,
                ]);
            }

            $p_fcb = Porcentaje::where('cliente_id', '=', $request->hiddenID)
                ->where('nombre','=','FISICO - banca')->first();
            if($p_fcb===null)
            {
                Porcentaje::create([
                    'cliente_id' => $request->hiddenID,
                    'cod_porcentaje' => 'FCB',
                    'nombre' => 'FISICO - banca',
                    'porcentaje' => $request->porcentaje_fcb,
                ]);
            }

            $p_ecb = Porcentaje::where('cliente_id', '=', $request->hiddenID)
                ->where('nombre','=','ELECTRONICA - banca')->first();
            if($p_ecb===null)
            {
                Porcentaje::create([
                    'cliente_id' => $request->hiddenID,
                    'cod_porcentaje' => 'ECB',
                    'nombre' => 'ELECTRONICA - banca',
                    'porcentaje' => $request->porcentaje_fsb,
                ]);
            }



            DB::commit();
        } catch (\Throwable $th) {
            throw $th;
        }
        Cliente::createSituacionByCliente($request->hiddenID);

    }

    public function updatebf(Request $request, Cliente $cliente)
    {
        $request->validate([
            'nombre' => 'required',
            'dni' => 'required',
            'celular' => 'required',
            'provincia' => 'required',
            'distrito' => 'required',
            'direccion' => 'required',
            'referencia' => 'required',
            'porcentaje' => 'required',
        ]);

        $cliente->update([
            'nombre' => $request->nombre,
            'dni' => $request->dni,
            'celular' => $request->celular,
            'provincia' => $request->provincia,
            'distrito' => $request->distrito,
            'direccion' => $request->direccion,
            'referencia' => $request->referencia,
            'deuda' => '0',
            'pidio' => '0',
            'tipo' => '1'
        ]);
        try {
            DB::beginTransaction();

            // ALMACENANDO PAGO-PEDIDOS
            $nombreporcentaje = $request->nombreporcentaje;
            $valoresporcentaje = $request->porcentaje;
            $cont = 0;

            /* return $porcentaje; */
            while ($cont < count((array)$nombreporcentaje)) {

                Porcentaje::create([
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

    public function celularduplicado(Request $request)
    {

        $request->celular;
        $validar = Cliente::where('celular', $request->celular)->count();
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
