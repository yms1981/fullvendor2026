<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErpApiSectionTest extends TestCase
{
    private string $apiKey = 'test-erp-api-key';

    protected function setUp(): void
    {
        parent::setUp();
        config(['erp.api_key' => $this->apiKey]);
    }

    public function test_ping_rejects_missing_api_key(): void
    {
        $this->getJson('/api/v2/erp/ping')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_ping_rejects_invalid_api_key(): void
    {
        $this->withHeader('X-Api-Key', 'wrong')
            ->getJson('/api/v2/erp/ping')
            ->assertUnauthorized();
    }

    public function test_ping_ok_with_valid_api_key(): void
    {
        $this->withHeader('X-Api-Key', $this->apiKey)
            ->getJson('/api/v2/erp/ping')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', '1');
    }

    public function test_orders_require_company_id(): void
    {
        $this->withHeader('X-Api-Key', $this->apiKey)
            ->getJson('/api/v2/erp/orders')
            ->assertStatus(422)
            ->assertJsonPath('message', 'company_id is required');
    }

    public function test_products_stock_update_requires_api_key(): void
    {
        $this->postJson('/api/v2/erp/products/stock-update', [
            'company_id' => 1,
            'items' => [['sku' => 'X', 'stock' => 1]],
        ])->assertUnauthorized();
    }

    public function test_customers_upsert_requires_api_key(): void
    {
        $this->postJson('/api/v2/erp/customers/upsert', [
            'company_id' => 1,
            'cust_id_kor' => 'X',
            'name' => 'Test',
        ])->assertUnauthorized();
    }

    public function test_stock_update_requires_items_with_valid_key(): void
    {
        $this->withHeader('X-Api-Key', $this->apiKey)
            ->postJson('/api/v2/erp/products/stock-update', [
                'company_id' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'items[] is required');
    }

    public function test_customer_upsert_requires_company_id(): void
    {
        $this->withHeader('X-Api-Key', $this->apiKey)
            ->postJson('/api/v2/erp/customers/upsert', [
                'cust_id_kor' => 'X',
                'name' => 'Test',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'company_id is required');
    }
}
