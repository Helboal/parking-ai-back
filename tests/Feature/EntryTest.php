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

        // Asignar el usuario a la sede como primaria (requerido para el nuevo flujo)
        \App\Models\UserBranch::create([
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'is_primary' => true,
        ]);

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
            'license_plate' => $this->vehicle->license_plate,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Entrada registrada exitosamente para vehículo registrado',
                'data' => [
                    'vehicle_registered' => true,
                ],
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'entry' => [
                        'id',
                        'entry_datetime',
                        'exit_datetime',
                        'status',
                        'entry_user_id',
                        'branch_id',
                        'vehicle_id',
                    ],
                    'vehicle_registered',
                    'vehicle_info' => [
                        'license_plate',
                        'brand',
                        'model',
                        'color',
                        'vehicle_type',
                    ],
                    'customer_info',
                    'subscription_info',
                    'capacity_info' => [
                        'total_spaces',
                        'occupied_spaces',
                        'available_spaces',
                    ],
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
            'license_plate' => $this->vehicle->license_plate,
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
            'license_plate' => $this->vehicle->license_plate,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'No hay cupos disponibles',
            ]);
    }

    /** @test */
    public function test_store_creates_entry_for_unregistered_vehicle()
    {
        // Crear capacidad para MOTO
        $motoType = VehicleType::firstOrCreate(['code' => 'MOTORCYCLE'], ['name' => 'Motocicleta']);
        BranchParkingCapacity::create([
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $motoType->id,
            'total_spaces' => 10,
            'occupied_spaces' => 0,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/admin/entries', [
            'license_plate' => 'ART46G', // Placa de MOTO que no existe
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Entrada registrada exitosamente para vehículo no registrado',
                'data' => [
                    'vehicle_registered' => false,
                    'vehicle_info' => [
                        'license_plate' => 'ART46G',
                        'registered' => false,
                    ],
                    'customer_info' => null,
                    'subscription_info' => null,
                ],
            ]);

        // Verificar que se guardó la placa en la entrada
        $this->assertDatabaseHas('entries', [
            'license_plate' => 'ART46G',
            'vehicle_id' => null,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function test_store_fails_with_invalid_plate_format()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/admin/entries', [
            'license_plate' => 'ABC12', // Formato inválido (muy corta)
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
            ])
            ->assertJsonValidationErrors(['license_plate']);

        // Probar con otro formato inválido
        $response2 = $this->actingAs($this->user, 'sanctum')->postJson('/api/admin/entries', [
            'license_plate' => '123ABC', // Formato inválido (números primero)
        ]);

        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['license_plate']);
    }

    /** @test */
    public function test_store_detects_active_subscription_automatically()
    {
        // Crear suscripción activa para el cliente
        $subscription = Subscription::factory()->create([
            'customer_id' => $this->vehicle->customer_id,
            'is_active' => true,
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(25),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/admin/entries', [
            'license_plate' => $this->vehicle->license_plate,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'subscription_info' => [
                        'id' => $subscription->id,
                        'is_active' => true,
                    ],
                ],
            ]);

        // Verificar que la entrada se asoció a la suscripción
        $this->assertDatabaseHas('entries', [
            'vehicle_id' => $this->vehicle->id,
            'subscription_id' => $subscription->id,
        ]);
    }

    /** @test */
    public function test_invoice_and_release_creates_invoice_and_completes_entry()
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

        $paymentMethod = \App\Models\PaymentMethod::firstOrCreate(['code' => 'CASH'], ['name' => 'Efectivo']);

        // PASO 2: Generar factura y pago
        $responseInvoice = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/admin/entries/{$this->vehicle->license_plate}/invoice", [
                'payment_method_id' => $paymentMethod->id,
            ]);

        $responseInvoice->assertStatus(200);

        // Verificar que se creó la factura
        $this->assertDatabaseHas('invoices', [
            'entry_id' => $entry->id,
        ]);

        // PASO 3: Registrar salida física
        $responseRelease = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/admin/entries/{$this->vehicle->license_plate}/release");

        $responseRelease->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'entry',
                    'invoice',
                ],
            ]);

        // Verificar que la entrada se completó
        $this->assertDatabaseHas('entries', [
            'id' => $entry->id,
            'status' => 'completed',
        ]);

        // Verificar que se liberó el cupo
        $this->assertDatabaseHas('branch_parking_capacity', [
            'branch_id' => $this->branch->id,
            'occupied_spaces' => 0,
        ]);
    }

    /** @test */
    public function test_subscription_entry_generates_zero_invoice_and_completes()
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

        $paymentMethod = \App\Models\PaymentMethod::firstOrCreate(['code' => 'CASH'], ['name' => 'Efectivo']);

        // PASO 2: Generar factura (con total = 0 por suscripción)
        $responseInvoice = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/admin/entries/{$this->vehicle->license_plate}/invoice", [
                'payment_method_id' => $paymentMethod->id,
            ]);

        $responseInvoice->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Factura generada exitosamente. Entrada por suscripción.',
            ]);

        // Verificar que se creó factura con total = 0
        $this->assertDatabaseHas('invoices', [
            'entry_id' => $entry->id,
            'total' => 0,
        ]);

        // PASO 3: Registrar salida física
        $responseRelease = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/admin/entries/{$this->vehicle->license_plate}/release");

        $responseRelease->assertStatus(200);

        // Verificar que la entrada se completó
        $this->assertDatabaseHas('entries', [
            'id' => $entry->id,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function test_release_fails_when_vehicle_has_not_paid()
    {
        $entry = Entry::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
        ]);

        // Intentar salida sin haber pagado (sin factura generada)
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/admin/entries/{$this->vehicle->license_plate}/release");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Debe pagar primero',
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
