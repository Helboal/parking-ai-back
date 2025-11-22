<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchParkingCapacity;
use App\Models\BranchRate;
use App\Models\BranchTax;
use App\Models\Customer;
use App\Models\Entry;
use App\Models\EntryType;
use App\Models\Subscription;
use App\Models\Tax;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntryTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $branch;

    protected $vehicle;

    protected $entryType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->branch = Branch::factory()->create();

        $customer = Customer::factory()->create();
        $vehicleType = VehicleType::firstOrCreate(['code' => 'CAR'], ['name' => 'Automóvil']);
        $this->vehicle = Vehicle::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_type_id' => $vehicleType->id,
        ]);

        // Crear capacidad para el tipo de vehículo
        BranchParkingCapacity::create([
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $vehicleType->id,
            'total_spaces' => 10,
            'occupied_spaces' => 0,
        ]);

        $this->entryType = EntryType::firstOrCreate(['code' => 'REGULAR'], ['name' => 'Regular']);
    }

    /** @test */
    public function test_store_creates_entry_successfully()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/admin/entries', [
            'branch_id' => $this->branch->id,
            'vehicle_id' => $this->vehicle->id,
            'entry_type_id' => $this->entryType->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'entry_datetime',
                    'exit_datetime',
                    'status',
                    'entry_user_id',
                    'branch_id',
                    'vehicle_id',
                ],
            ]);

        $this->assertDatabaseHas('entries', [
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
        ]);

        // Verificar que se decrementó el cupo
        $this->assertDatabaseHas('branch_parking_capacity', [
            'branch_id' => $this->branch->id,
            'occupied_spaces' => 1,
        ]);
    }

    /** @test */
    public function test_store_fails_when_vehicle_already_inside()
    {
        // Crear entrada activa
        Entry::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/admin/entries', [
            'branch_id' => $this->branch->id,
            'vehicle_id' => $this->vehicle->id,
            'entry_type_id' => $this->entryType->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'El vehículo ya se encuentra en el parqueadero',
            ]);
    }

    /** @test */
    public function test_store_fails_when_no_capacity_available()
    {
        // Llenar la capacidad
        BranchParkingCapacity::where('branch_id', $this->branch->id)->update([
            'total_spaces' => 1,
            'occupied_spaces' => 1,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/admin/entries', [
            'branch_id' => $this->branch->id,
            'vehicle_id' => $this->vehicle->id,
            'entry_type_id' => $this->entryType->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'No hay cupos disponibles',
            ]);
    }

    /** @test */
    public function test_store_validates_active_subscription()
    {
        $subscription = Subscription::factory()->create([
            'customer_id' => $this->vehicle->customer_id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/admin/entries', [
            'branch_id' => $this->branch->id,
            'vehicle_id' => $this->vehicle->id,
            'entry_type_id' => $this->entryType->id,
            'subscription_id' => $subscription->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'La suscripción no está activa',
            ]);
    }

    /** @test */
    public function test_register_exit_creates_invoice_automatically()
    {
        // Incrementar la capacidad ocupada primero (simular que el vehículo entró)
        $capacity = BranchParkingCapacity::where('branch_id', $this->branch->id)
            ->where('vehicle_type_id', $this->vehicle->vehicle_type_id)
            ->first();
        $capacity->increment('occupied_spaces');

        // Crear entrada activa
        $entry = Entry::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'entry_datetime' => now()->subHours(2), // 2 horas = 120 minutos
            'exit_datetime' => null,
        ]);

        // Crear tarifa
        BranchRate::create([
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicle->vehicle_type_id,
            'rate_per_minute' => 100,
            'is_active' => true,
        ]);

        // Crear impuesto
        $tax = Tax::firstOrCreate(['code' => 'IVA'], ['name' => 'IVA', 'percentage' => 19, 'is_active' => true]);
        BranchTax::create([
            'branch_id' => $this->branch->id,
            'tax_id' => $tax->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson("/api/admin/entries/{$entry->id}/exit");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'entry',
                    'invoice' => [
                        'id',
                        'rate_per_minute',
                        'subtotal',
                        'tax_amount',
                        'total',
                        'entry_id',
                    ],
                ],
            ]);

        // Verificar que se creó la factura
        $this->assertDatabaseHas('invoices', [
            'entry_id' => $entry->id,
        ]);

        // Verificar que se liberó el cupo
        $this->assertDatabaseHas('branch_parking_capacity', [
            'branch_id' => $this->branch->id,
            'occupied_spaces' => 0,
        ]);
    }

    /** @test */
    public function test_register_exit_does_not_create_invoice_for_subscription()
    {
        // Incrementar la capacidad ocupada primero (simular que el vehículo entró)
        $capacity = BranchParkingCapacity::where('branch_id', $this->branch->id)
            ->where('vehicle_type_id', $this->vehicle->vehicle_type_id)
            ->first();
        $capacity->increment('occupied_spaces');

        $subscription = Subscription::factory()->create([
            'customer_id' => $this->vehicle->customer_id,
            'is_active' => true,
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(25),
        ]);

        // Crear entrada con suscripción
        $entry = Entry::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'subscription_id' => $subscription->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson("/api/admin/entries/{$entry->id}/exit");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Salida registrada exitosamente. Entrada por suscripción, no se genera factura.',
            ]);

        // Verificar que NO se creó factura
        $this->assertDatabaseMissing('invoices', [
            'entry_id' => $entry->id,
        ]);
    }

    /** @test */
    public function test_register_exit_fails_when_entry_not_active()
    {
        $entry = Entry::factory()->create([
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson("/api/admin/entries/{$entry->id}/exit");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'La entrada no está activa',
            ]);
    }

    /** @test */
    public function test_index_returns_all_entries()
    {
        Entry::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/admin/entries');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'entry_datetime',
                        'status',
                        'vehicle_id',
                        'branch_id',
                    ],
                ],
            ]);
    }

    /** @test */
    public function test_show_returns_entry()
    {
        $entry = Entry::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/admin/entries/{$entry->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'entry_datetime',
                    'status',
                ],
            ]);
    }

    /** @test */
    public function test_endpoints_require_authentication()
    {
        $response = $this->getJson('/api/admin/entries');
        $response->assertStatus(401);

        $response = $this->postJson('/api/admin/entries', []);
        $response->assertStatus(401);
    }
}
