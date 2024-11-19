<?php

namespace App\Http\Controllers\Api\V1\Company;

use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\CompanyResource;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
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

        $query = Company::query();

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $query->orderBy($sortBy, $sortOrder);

        $companies = $query->paginate(10);

        if ($companies->isEmpty()) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        return CompanyResource::collection($companies);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'          => ['required', 'string'],
            'email'         => ['required', 'email', 'email:rfc,dns', 'unique:companies,email'],
            'phone_number'  => ['required', 'numeric', 'min_digits:10', 'max_digits:13'],
        ]);

        $data = $validator->validate();

        DB::beginTransaction();

        try {
            $body = collect($data);

            $companyData = [
                'name'          => $body->get('name'),
                'email'         => $body->get('email'),
                'phone_number'  => $body->get('phone_number'),
            ];

            $createCompany = Company::query()->create($companyData);

            $userData = [
                'name'          => $body->get('name'),
                'email'         => $body->get('email'),
                'company_id'    => $createCompany->id,
                'password'      => Hash::make('Password123!'),
                'role'          => 'manager',
            ];

            User::query()->create($userData);

            DB::commit();

            return response()->json([
                'message' => 'success',
                'data' => [
                    'company' => $createCompany,
                    'user' => User::query()->where('company_id', $createCompany->id)->first(),
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
        $company = Company::query()->with('user')->find($id);

        if (is_null($company)) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        return new CompanyResource($company);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $company = Company::query()->find($id);

        if (is_null($company)) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'          => ['required', 'string'],
            'email'         => ['required', 'email', 'email:rfc,dns'],
            'phone_number'  => ['required', 'numeric', 'min_digits:10', 'max_digits:13'],
        ]);

        $data = $validator->validate();

        DB::beginTransaction();

        try {
            $body = collect($data);

            $companyData = [
                'name'          => $body->get('name'),
                'email'         => $body->get('email'),
                'phone_number'  => $body->get('phone_number'),
            ];

            $company->update($companyData);

            $user = User::query()->where('company_id', $company->id)->first();

            $userData = [
                'name'          => $body->get('name'),
                'email'         => $body->get('email'),
            ];

            $user->update($userData);

            DB::commit();

            return response()->json([
                'message' => 'Updated successfully',
                'data' => [
                    'company' => $company,
                    'user' => $user,
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
        $company = Company::query()->find($id);

        if (is_null($company)) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        $company->delete();

        return response()->json(['message' => 'Deleted successfully'], 200);
    }
}
