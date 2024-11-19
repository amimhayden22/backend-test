<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Default dengan sort berdasarkan kolom name
        $sortBy = $request->get('sort_by', 'name');
        // Default data diurutkan secara ascending
        $sortOrder = $request->get('sort_order', 'asc');
        $search = $request->get('search', null);

        $query = User::query();

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $query->orderBy($sortBy, $sortOrder);

        if(Auth::user()->role === 'manager'){
            $users = $query->where('company_id', Auth::user()->company_id)->paginate(10);
        }elseif (Auth::user()->role === 'superadmin') {
            $users = $query->paginate(10);
        }else{
            return response()->json(['message' => 'Upss, You are not allowed to access this feature!'], 403);
        }

        if ($users->isEmpty()) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        return UserResource::collection($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'          => ['required', 'string'],
            'email'         => ['required', 'email', 'email:rfc,dns', 'unique:users,email'],
            'password'      => ['required', 'min:8'],
            'role'          => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $createUser = User::query()->create([
            'company_id'    => Auth::user()->company_id ?? null,
            'name'          => $request->name,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'role'          => $request->role,
        ]);

        return response()->json([
            'message'   => 'success',
            'data'      => $createUser,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::query()->find($id);

        if (is_null($user)) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'          => ['required', 'string'],
            'email'         => ['required', 'email', 'email:rfc,dns', 'unique:users,email'],
            'password'      => ['required', 'min:8'],
            'role'          => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $user->where('id', $id)->update([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
        ]);

        return response()->json([
            'message'   => 'success',
            'data'      => $user,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::query()->find($id);

        if (is_null($user)) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'Deleted successfully'], 200);
    }
}
