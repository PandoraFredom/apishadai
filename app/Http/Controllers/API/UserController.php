<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list = User::all();
        if ($list->count() > 0) {
            return $this->sendResponse(UserResource::collection($list), 'success');
        }
        return $this->sendResponse(null, 'No se encontraron informacion', 404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return $this->sendResponse(null, 'Not implemented', 501);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::find($id);
        if ($user) {
            return $this->sendResponse(UserResource::make($user), 'success');
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        return $this->sendResponse(null, 'Not implemented', 501);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //try delete user
        try {
            $user = User::find($id);
            if ($user) {
                $user->delete();
                return $this->sendResponse(true, 'Usuario eliminado correctamente', 200);
            }
            return $this->sendResponse(false, 'No se encontro informacion', 404);
        } catch (\Throwable $th) {
            return $this->sendResponse(false, 'Usuario no disponible para eliminar.', 500);
        }
    }

    public function findlikenombre(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->sendResponse(null, $validator->errors()->first(), 422);
        }

        $nombre = $request->input('nombre');
        $users = User::where('name', 'like', "%$nombre%")->get();

        if ($users->count() > 0) {
            return $this->sendResponse(UserResource::collection($users), 'success');
        }
        return $this->sendResponse(null, 'No se encontraron usuarios con ese nombre', 404);
        
      
       
       
    }
    
}
