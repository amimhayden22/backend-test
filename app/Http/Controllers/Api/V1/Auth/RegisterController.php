<?php

namespace App\Http\Controllers\Api\V1\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'      => ['required', 'string'],
            'email'     => ['required', 'email', 'email:rfc,dns', 'unique:users,email'],
            'password'  => ['required', 'min:8'],
            'role'      => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $createUser = User::query()->create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
        ]);

        return response()->json([
            'message'   => 'success',
            'data'      => $createUser,
        ], 201);
    }
}
