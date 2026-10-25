<?php

namespace Tests\Feature;

use App\Models\CollectionLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VaultApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
    }

    private function vaultToken(): string
    {
        return $this->owner->createToken('vault.ngtj.com', ['vault:read'])->plainTextToken;
    }

    /**
     * Two copies of one set (one since removed), a bulk lot and a BrickLink order.
     * Rows go in directly: creating a catalog item through the model asks Brickset.
     */
    private function seedCollection(): void
    {
        $item = DB::table('catalog_items')->insertGetId([
            'set_number' => '10294', 'set_number_variant' => 1, 'name' => 'Titanic', 'piece_count' => 9090,
            'retail_price' => 679.99, 'current_value_new' => 800.00, 'current_value_used' => 600.00,
            'year' => 2024, 'theme_group' => 'Model making', 'brickset_url' => '',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('sets')->insert([
            ['catalog_item_id' => $item, 'purchase_price' => 550.00, 'current_condition' => 'Built', 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
            ['catalog_item_id' => $item, 'purchase_price' => 500.00, 'current_condition' => 'Built', 'deleted_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('bulk_bricks')->insert(['acquired_location_id' => 1, 'piece_count' => 1000, 'cost' => 20.00, 'value' => 35.00, 'acquired_date' => '2025-06-01', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('bricklink_orders')->insert([
            'purchase_date' => '2026-01-05', 'seller_name' => 'seller', 'order_number' => 123, 'pieces' => 50,
            'total_cost' => 12.34, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_it_reports_the_used_value_with_new_and_retail_as_alternatives(): void
    {
        $this->seedCollection();

        $this->withToken($this->vaultToken())
            ->getJson('/api/vault/summary')
            ->assertOk()
            ->assertExactJson([
                'schema_version' => 1,
                'source' => 'mursten',
                'as_of' => today()->toDateString(),
                'currency' => 'USD',
                'market_value_cents' => 60000 + 3500 + 1234,
                'cost_basis_cents' => 55000 + 2000 + 1234,
                'item_count' => 1,
                'valuation_basis' => 'BrickLink used prices, refreshed monthly; bulk and orders at value',
                'alt_values' => ['new' => 80000 + 3500 + 1234, 'retail' => 67999 + 3500 + 1234],
            ]);
    }

    public function test_it_returns_the_monthly_log_as_history(): void
    {
        CollectionLog::create(['date' => '2026-08-31', 'used_value' => 100.5, 'new_value' => 120, 'retail_value' => 110, 'total_sets' => 3, 'piece_count' => 10]);
        CollectionLog::create(['date' => '2026-09-30', 'used_value' => 200, 'new_value' => 220, 'retail_value' => 210, 'total_sets' => 4, 'piece_count' => 20]);

        $this->withToken($this->vaultToken())
            ->getJson('/api/vault/history?from=2026-09-01')
            ->assertOk()
            ->assertJsonPath('points', [
                ['date' => '2026-09-30', 'market_value_cents' => 20000, 'cost_basis_cents' => null, 'item_count' => 4],
            ]);
    }

    /** @return array<string, array{string, string}> */
    public static function otherRoutes(): array
    {
        return [
            'catalog lookup' => ['GET', '/api/catalog/10294'],
            'catalog lookup with variant' => ['GET', '/api/catalog/10294/1'],
        ];
    }

    #[DataProvider('otherRoutes')]
    public function test_the_vault_token_is_refused_everywhere_else(string $method, string $uri): void
    {
        $this->withToken($this->vaultToken())->json($method, $uri)->assertForbidden();
    }

    public function test_the_vault_endpoints_need_the_vault_ability(): void
    {
        $token = $this->owner->createToken('other', ['catalog:read'])->plainTextToken;

        $this->withToken($token)->getJson('/api/vault/summary')->assertForbidden();
    }

    public function test_full_access_tokens_can_read_it_too(): void
    {
        $this->withToken($this->owner->createToken('api-token')->plainTextToken)
            ->getJson('/api/vault/summary')
            ->assertOk();
    }

    public function test_guests_get_a_401(): void
    {
        $this->getJson('/api/vault/summary')->assertUnauthorized();
    }

    public function test_the_token_command_makes_a_read_only_token_that_replaces_the_last(): void
    {
        $this->artisan('vault:token')->assertSuccessful();
        $this->artisan('vault:token')->expectsOutputToContain('Revoked the previous vault token')->assertSuccessful();

        $tokens = $this->owner->tokens()->where('name', 'vault.ngtj.com')->get();

        $this->assertCount(1, $tokens);
        $this->assertSame(['vault:read'], $tokens->first()->abilities);
        $this->assertTrue($tokens->first()->expires_at->isBetween(now()->addDays(364), now()->addDays(366)));
    }
}
