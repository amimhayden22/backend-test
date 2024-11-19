<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create([
            'role' => 'manager',
        ]);
    }

    public function a_manager_can_view_all_employees()
    {
        Employee::factory()->count(5)->create();

        $this->actingAs($this->manager, 'api');

        $response = $this->getJson('/api/employees');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id', 'name', 'phone_number', 'address',
                         ],
                     ],
                 ]);
    }

    public function a_manager_can_view_an_employee_detail()
    {
        $this->actingAs($this->manager, 'api');

        $employee = Employee::factory()->create();

        $response = $this->getJson("/api/employees/{$employee->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'id' => $employee->id,
                         'name' => $employee->name,
                         'phone_number' => $employee->phone_number,
                         'address' => $employee->address,
                     ],
                 ]);
    }

    public function a_manager_can_create_an_employee()
    {
        $this->actingAs($this->manager, 'api');

        $data = [
            'name' => 'John Doe',
            'phone_number' => '1234567890',
            'address' => '123 Main Street',
        ];

        $response = $this->postJson('/api/employees', $data);

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'success',
                     'data' => [
                         'name' => 'John Doe',
                         'phone_number' => '1234567890',
                         'address' => '123 Main Street',
                     ],
                 ]);

        $this->assertDatabaseHas('employees', $data);
    }

    public function a_manager_can_update_an_employee()
    {
        $this->actingAs($this->manager, 'api');

        $employee = Employee::factory()->create();

        $updatedData = [
            'name' => 'Jane Doe',
            'phone_number' => '0987654321',
            'address' => '456 Elm Street',
        ];

        $response = $this->putJson("/api/employees/{$employee->id}", $updatedData);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'success',
                     'data' => [
                         'id' => $employee->id,
                         'name' => 'Jane Doe',
                         'phone_number' => '0987654321',
                         'address' => '456 Elm Street',
                     ],
                 ]);

        $this->assertDatabaseHas('employees', $updatedData);
    }

    public function a_manager_can_delete_an_employee()
    {
        $this->actingAs($this->manager, 'api');

        $employee = Employee::factory()->create();

        $response = $this->deleteJson("/api/employees/{$employee->id}");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'success']);

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }
}
