<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\EmployeeResource;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
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

        $query = Employee::query();

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $query->orderBy($sortBy, $sortOrder);

        if (Auth::user()->role === 'manager') {
            $companies = $query->with('user')->whereHas('user', function($query){
                $query->where('company_id', Auth::user()->company_id);
            })->paginate(10);
        }elseif (Auth::user()->role === 'employee') {
            $companies = $query->with('user')->where('user_id', Auth::user()->id)->paginate(10);
        }else{
            $companies = $query->with('user')->paginate(10);
        }

        if ($companies->isEmpty()) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        return EmployeeResource::collection($companies);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (Auth::user()->role !== 'manager') {
            return response()->json(['message' => 'Upss, Only managers can add employee!'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'          => ['required', 'string'],
            'email'         => ['required', 'email', 'email:rfc,dns', 'unique:users,email'],
            'phone_number'  => ['required', 'numeric', 'min_digits:10', 'max_digits:13', 'unique:employees,phone_number'],
            'address'       => ['required', 'string'],
        ]);

        $data = $validator->validate();

        DB::beginTransaction();

        try {
            $body = collect($data);


            $userData = [
                'company_id'    => Auth::user()->company_id,
                'name'          => $body->get('name'),
                'email'         => $body->get('email'),
                'password'      => Hash::make('Password123!'),
                'role'          => 'employee',
            ];

            $createUser = User::query()->create($userData);

            $employeeData = [
                'user_id'       => $createUser->id,
                'name'          => $body->get('name'),
                'address'       => $body->get('address'),
                'phone_number'  => $body->get('phone_number'),
            ];

            $createEmployee = Employee::query()->create($employeeData);

            DB::commit();

            return response()->json([
                'message' => 'success',
                'data' => [
                    'employee' => $createEmployee,
                    'user' => User::query()->where('id', $createEmployee->user_id)->first(),
                ],
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $employee = Employee::query()->with('user')->find($id);

        if (is_null($employee)) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        return new EmployeeResource($employee);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $employee = Employee::query()->find($id);

        if (is_null($employee)) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'          => ['required', 'string'],
            'email'         => ['required', 'email', 'email:rfc,dns'],
            'phone_number'  => ['required', 'numeric', 'min_digits:10', 'max_digits:13'],
            'address'       => ['required', 'string'],
        ]);

        $data = $validator->validate();

        DB::beginTransaction();

        try {
            $body = collect($data);

            $employeeData = [
                'name'          => $body->get('name'),
                'address'       => $body->get('address'),
                'phone_number'  => $body->get('phone_number'),
            ];

            $employee->update($employeeData);

            $userData = [
                'name'          => $body->get('name'),
                'email'         => $body->get('email'),
            ];

            $employee->user->update($userData);

            DB::commit();

            return response()->json([
                'message' => 'success',
                'data' => [
                    'employee' => $employee,
                    'user' => User::query()->where('id', $employee->user_id)->first(),
                ],
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $employee = Employee::query()->find($id);

        if (is_null($employee)) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        DB::beginTransaction();

        try {
            $employee->delete();
            if ($employee->user) {
                $employee->user->delete();
            }

            DB::commit();

            return response()->json(['message' => 'Deleted successfully'], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
}
