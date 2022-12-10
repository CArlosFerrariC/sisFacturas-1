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

        if (Auth::user()->rol == "Llamadas" || Auth::user()->rol == "Llamadas")
        {
            $users = User::
                where('estado', '1')
                ->whereIn('rol', ['Asesor', 'Super asesor'])
                ->where('users.llamada', Auth::user()->id)
                ->pluck('identificador', 'id');
        }else{
            $users = User::
                where('estado', '1')
                ->whereIn('rol', ['Asesor', 'Super asesor'])
                //->where('users.llamada', Auth::user()->id)
                ->pluck('identificador', 'id');
        }

        return view('base_fria.index', compact( 'superasesor', 'users'));
    }

    public function indextabla(Request $request)
    {
        //
        //return $dataTable->render('base_fria.index');
        //if ($request->ajax()) {

        $data = Cliente::
            join('users as u', 'clientes.user_id', 'u.id')
            ->select('clientes.id', 
                    'clientes.nombre', 
                    'clientes.icelular', 
                    'clientes.celular', 
                    'u.identificador as identificador',
                    'u.rol'
                    )
            ->where('clientes.estado','1')
            ->where('clientes.tipo','0');
            //->get();

        if(Auth::user()->rol == 'Llamadas')
        {
            $usersasesores = User::where('users.rol', 'Asesor')
                -> where('users.estado', '1')
                -> where('users.llamada', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');
            $data=$data->WhereIn("u.identificador",$usersasesores);

        }else if(Auth::user()->rol == 'Jefe de llamadas')
        {


            $usersasesores = User::where('users.rol', 'Asesor')
                -> where('users.estado', '1')
                -> where('users.llamada', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $data=$data->WhereIn("u.identificador",$usersasesores);



        }else if(Auth::user()->rol == 'Asesor')
        {
            $usersasesores = User::where('users.rol', 'Asesor')
                -> where('users.estado', '1')
                -> where('users.identificador', Auth::user()->identificador)
                ->select(
                    DB::raw("users.id as id")
                )
                ->pluck('users.id');

            $data=$data->WhereIn('u.id',$usersasesores);

        }else if(Auth::user()->rol == 'Encargado')
        {

            $usersasesores = User::where('users.rol', 'Asesor')
                -> where('users.estado', '1')
                -> where('users.supervisor', Auth::user()->id)
                ->select(
                    DB::raw("users.identificador as identificador")
                )
                ->pluck('users.identificador');

            $data=$data->WhereIn("u.identificador",$usersasesores);

        }else{
            $data=$data;
        }
        $data=$data->get();

            return Datatables::of($data)
                    ->addIndexColumn()
                    ->addColumn('action', function($row){
                            $btn="";
                            return $btn;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
    }

    public function cargarid(Request $request)
    {
        if (!$request->basefria_id) {
            $html='';
        } else {
            $data = Cliente::
            join('users as u', 'clientes.user_id', 'u.id')
            ->select('clientes.id', 
                    'clientes.nombre', 
                    'clientes.celular', 
                    'u.identificador as identificador',
                    'u.rol'
                    )
            ->where('clientes.estado','1')
            ->where('clientes.tipo','0')
            ->where('clientes.id',$request->basefria_id)
            ->get();

            $html=$data;
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
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function updatebfpost(Request $request)
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
        //$id=null;
        //Selection::whereId($id)->update($request->all());
        $cliente = Cliente::where('clientes.id',$request->hiddenID)->update([
            'nombre' => $request->nombre,
            'dni' => $request->dni,
            'celular' => $request->celular,
            'provincia' => $request->provincia,
            'distrito' => $request->distrito,
            'direccion' => $request->direccion,
            'referencia' => $request->referencia,
            'deuda' => '0',
            'pidio' => '0',
            'tipo' => '1',
            'saldo' => '0'

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
                    'cliente_id' => $request->hiddenID,//$cliente->id,//
                    'nombre' => $nombreporcentaje[$cont],
                    'porcentaje' => $valoresporcentaje[$cont],
                ]);
                $cont++;
            }
        DB::commit();
        } catch (\Throwable $th) {
            throw $th;
        }

        //return redirect()->route('clientes.index')->with('info','registrado');
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

        return redirect()->route('clientes.index')->with('info','registrado');
    }
}
