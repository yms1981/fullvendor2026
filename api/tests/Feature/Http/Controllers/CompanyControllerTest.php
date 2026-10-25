<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Language;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WarehouseUser;
use Tests\Feature\Http\ResourceFeaturesTestCase;

class CompanyControllerTest extends ResourceFeaturesTestCase
{
    protected $resourceRoute = 'company';

    /**
     * destroy() exige sin pedidos y solo vínculos manager + vendedor por defecto en company_user.
     */
    public function test_destroy(): void
    {
        /** @var Company $company */
        $company = Company::factory()->create();
        $manager = User::factory()->create();
        $sellerUser = User::factory()->create();
        $vendor = Vendor::factory()->create([
            'company_id' => $company->company_id,
            'user_id' => $sellerUser->id,
        ]);
        $company->update(['default_seller_id' => $vendor->id]);

        CompanyUser::create([
            'company_id' => $company->company_id,
            'user_id' => $manager->id,
            'type' => 'manager',
        ]);
        CompanyUser::create([
            'company_id' => $company->company_id,
            'user_id' => $sellerUser->id,
            'type' => 'seller',
        ]);

        $response = $this->deleteJson(
            $this->getResourceUri() . '/' . $this->getModelIdForUrl($company)
        );
        $response->assertNoContent();

        $erased = Company::query()->where($this->getIdCondArray($company))->count();
        self::assertEquals(0, $erased);
    }

    public function testProductCount()
    {
        /** @var Language $lang */
        $lang = Language::factory()->create();
        /** @var Company $item */
        $item = $this->createResourceModelItems();
        /** @var WarehouseUser $warehouseUser */
        $warehouseUser = WarehouseUser::factory()->create(['company_id' => $item->company_id]);
        /** @var Category $category */
        $category = Category::factory()->create(['company_id' => $item->company_id]
        );
        for($i=0;$i<100;$i++)
        {
            $item->products()->save(
                Product::factory()
                    ->make(
                        [
                            'language_id' => $lang->language_id,
                            'category_id' => $category->cat_id,
                            'company_id' => $item->company_id,
                        ]
                    )
            );
        }

        $response = $this->get($this->getResourceUri() . '/' . $this->getModelIdForUrl($item) . "/products/count");
        $response->assertOk()->assertSee($item->products()->count());

        $responseEnabled = $this->get($this->getResourceUri() . '/' . $this->getModelIdForUrl($item) . "/products/count?enabled=1");
        $responseEnabled->assertOk()->assertSee($item->products()->where('status', 1)->count());
    }
}
