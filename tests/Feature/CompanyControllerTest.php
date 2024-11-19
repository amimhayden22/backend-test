<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompanyControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->create([
            'role' => 'superadmin',
        ]);
    }

    public function a_superadmin_can_create_a_company()
    {
        $this->actingAs($this->superadmin, 'api');

        $data = [
            'name' => 'Gemilang',
            'email' => 'gemilang@test.com',
            'phone_number' => 6285713456789,
        ];

        $response = $this->postJson('/api/v1/companies', $data);

        $response->assertStatus(201)
                 ->assertJson([
                     'data' => [
                         'name' => 'Gemilang',
                         'email' => 'gemilang@test.com',
                         'phone_number' => 6285713456789,
                     ],
                 ]);

        $this->assertDatabaseHas('companies', $data);
    }

    public function a_user_without_superadmin_role_cannot_create_a_company()
    {
        $user = User::factory()->create(['role' => 'manager']);
        $this->actingAs($user, 'api');

        $data = [
            'name' => 'Gemilang',
            'email' => 'gemilang@test.com',
            'phone_number' => 6285713456789,
        ];

        $response = $this->postJson('/api/v1/companies', $data);

        $response->assertStatus(403)
                 ->assertJson(['message' => 'Forbidden']);
    }

    public function it_can_show_a_company_detail()
    {
        $this->actingAs($this->superadmin, 'api');

        $company = Company::factory()->create();

        $response = $this->getJson("/api/v1/companies/{$company->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'name' => $company->name,
                         'email' => $company->email,
                         'phone_number' => $company->phone_number,
                     ],
                 ]);
    }

    public function it_can_delete_a_company()
    {
        $this->actingAs($this->superadmin, 'api');

        $company = Company::factory()->create();

        $response = $this->deleteJson("/api/v1/companies/{$company->id}");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'success']);

        $this->assertSoftDeleted('companies', ['id' => $company->id]);
    }
}
