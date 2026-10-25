<?php

namespace App\Console\Commands;

use App\Enums\AddressCustomerTypes;
use App\Enums\OrderNoteTypes;
use App\Enums\OrderStatusCodes;
use App\Models\Address;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Currencybycountry;
use App\Models\Customer;
use App\Models\CatalogCategory;
use App\Enums\CompanyUserTypes;
use App\Models\Admin;
use App\Models\CategoryProduct;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\CustomerGroup;
use App\Models\CustomerSeller;
use App\Models\CustomerUser;
use App\Models\Normalize\Normalization;
use App\Models\DetailCar;
use App\Models\Language;
use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ManagerProfiles;
use App\Models\State;
use App\Models\StatusOrder;
use App\Models\TermsOfSale;
use App\Models\Unit;
use App\Models\User;
use App\Models\SellerProfiles;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseUser;
use Artisan;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class normalizeData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:normalize-data {--force : Force the operation to run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $benchmarks = [];


    protected $mvcUploadsPath = '/var/www/mvc_uploads';

    /** @var array  */
    protected $avoidedMigrations = [];
    protected $avoidableMigrations = [
        'dropCatalogCart'=>"database/migrations/2025_03_29_111845_drop_catalog_cart_table.php",
        'dropUnitsColumns'=>"database/migrations/2025_07_14_171811_drop_columns_from_units.php",
        'RemoveColumnsFromProductImages'=>"database/migrations/2025_07_14_103155_remove_columns_from_product_images.php",
        'removeColumnsFromAdmin' =>"database/migrations/2024_10_29_193802_structural_changes_in_admin_table.php",
        'removeColumnsFromSellers' =>"database/migrations/2025_02_25_180201_remove_unused_columns_from_sellers.php",
        'removeNotesColumnsFromOrders' =>"database/migrations/2025_06_02_213932_drop_notes_columns_from_orders.php",
        'removeAddressColumnsFromCompanies' =>"database/migrations/2025_06_02_215626_drop_address_columns_from_company.php",
        'removeAddressColumnsFromCustomers' =>"database/migrations/2025_06_02_220839_drop_address_columns_from_customer.php",
        'removeNewAddressColumnFromOrders' =>"database/migrations/2025_06_02_222929_drop_new_address_column_from_orders.php",
        'removeColumnsFromTerms' =>"database/migrations/2025_06_26_172323_drop_columns_from_terms.php",
        'changesInCurrencies' =>"database/migrations/2025_07_07_194815_relate_currency_with_companies_and_drop_currency_code_id.php",
        'dropCurrencybycountries' =>"database/migrations/2025_07_07_201637_drop_currencybycountries_table.php",
        'removeUnitTypeColumnFromProducts' =>"database/migrations/2025_07_14_170738_drop_column_unit_type_in_products.php",
        'removeRelationshipsWithWarehouseUsers' =>"database/migrations/2025_08_01_162500_drop_multiples_tables_relationships_with_warehouse_users.php",
        'removeColumnsFromWarehouseUsers' =>"database/migrations/2025_08_01_162515_drop_columns_from_warehouse_users.php",
        'removeColumnDefaultFromCustomerGroups' => "database/migrations/2025_08_11_162036_drop_default_column_from_customer_groups.php",
        'dropUserColumnFromCustomers' => "database/migrations/2025_08_05_114019_drop_user_id_column_from_customers.php"
    ];
    private $usersCache;

    /**
     * Rescues avoided avoidable migrations (useful when previous command exited on failure)
     * @return void
     */
    private function rescueNotUnavoided() {
        foreach($this->avoidableMigrations as $migration => $migrationFile) {
            if(File::exists("{$migrationFile}.avoid")) {
                File::move("{$migrationFile}.avoid", $migrationFile);
            }
        }
    }

    private function modelNormalizationPending($normalizationName) {
        return !Normalization::where('name', $normalizationName)->exists();
    }

    private function startModelNormalization($normalizationName,$message=null) {
        $this->benchmarks[$normalizationName] = Carbon::now();
        if(isset($message)) $this->info($message);
        $modelNormalizationPending = $this->modelNormalizationPending($normalizationName);
        $normTitle = Str::ucfirst(implode(' ',Str::ucsplit($normalizationName)));

        $this->info(($modelNormalizationPending)? "Normalizing {$normTitle}":"$normTitle already normalized");

        return $modelNormalizationPending;
    }

    private function endModelNormalization($normalizationName,$amountNormalized=0) {
        /** @var Carbon $startTime */
        $startTime = $this->benchmarks[$normalizationName];
        Normalization::create(
            [
                'name'=>$normalizationName,
                'time'=>$startTime->diffForHumans(['syntax'=>CarbonInterface::DIFF_ABSOLUTE]),
                'seconds' => $startTime->diffInSeconds(),
                'amount_normalized'=>$amountNormalized
            ]);
        $this->info(" ".Str::ucfirst(implode(' ',Str::ucsplit($normalizationName)))." Normalized");
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = Carbon::now();
        $this->info("[{$startTime->toDateTimeString()}] Starting normalization process");

        $this->rescueNotUnavoided();

        /** @var  $normalizeSchema */
        $this->initNormalizeDB();

        if($this->modelNormalizationPending('catalogCarts')) $this->avoidMigration('dropCatalogCart');
        if($this->modelNormalizationPending('unitsDefault')) $this->avoidMigration('dropUnitsColumns');
        if($this->modelNormalizationPending('productImages')) $this->avoidMigration('RemoveColumnsFromProductImages');
        if($this->modelNormalizationPending('adminUser')) $this->avoidMigration('removeColumnsFromAdmin');
        if($this->modelNormalizationPending('sellerUser')) $this->avoidMigration('removeColumnsFromSellers');
        if($this->modelNormalizationPending('orderNotes')) $this->avoidMigration('removeNotesColumnsFromOrders');
        if($this->modelNormalizationPending('companyAddress')) $this->avoidMigration('removeAddressColumnsFromCompanies');
        if($this->modelNormalizationPending('dropAddressColumnsFromCustomer')) $this->avoidMigration('removeAddressColumnsFromCustomers');
        if($this->modelNormalizationPending('orderAddress')) $this->avoidMigration('removeNewAddressColumnFromOrders');
        if($this->modelNormalizationPending('termsDefault')) $this->avoidMigration('removeColumnsFromTerms');
        if($this->modelNormalizationPending('currency')) $this->avoidMigration('changesInCurrencies');
        if($this->modelNormalizationPending('currency')) $this->avoidMigration('dropCurrencybycountries');
        if($this->modelNormalizationPending('productUnit')) $this->avoidMigration('removeUnitTypeColumnFromProducts');
        if($this->modelNormalizationPending('warehouseUser')) $this->avoidMigration('removeRelationshipsWithWarehouseUsers');
        if($this->modelNormalizationPending('warehouseUser')) $this->avoidMigration('removeColumnsFromWarehouseUsers');
        if($this->modelNormalizationPending('customerGroups')) $this->avoidMigration('removeColumnDefaultFromCustomerGroups');
        if($this->modelNormalizationPending('customerSellers')) $this->avoidMigration('dropUserColumnFromCustomers');

        if(!Schema::hasTable('users'))
        {
            $this->info("Modifiyng schema tables to APIv2-MVC");
            $this->modifySchemaFromFullvendorMVC();
        }

//      run seeders
        $this->runCustomSeeders();

//      normalize
        $this->normalizeProductImages();
        $this->normalizeCustomerSellers();
        $this->normalizeCatalogCategories();
        $this->normalizeProductCategories();

        $this->normalizeUnitsDefault();
        $this->normalizeCatalogCarts();

        $this->normalizeCompanyAddress();

        $this->normalizeCustomerCommercialAddress();
        $this->normalizeCustomerDispatchAddress();
        $this->normalizeCustomerCommercialDeliveryAddress();
        $this->dropAddressColumnsFromCustomer();

        $this->normalizeAdminUser();
        $this->normalizeCompanyUser();
        $this->normalizeWarehouseUser();
        $this->normalizeSellerUser();
        $this->normalizeCustomerUser();

        $this->normalizeOrderAddress();

        $this->normalizeOrderNotes();
        $this->normalizeTermsDefault();
        $this->normalizeCurrency();
        $this->normalizeProductUnit();
        //$this->normalizeCustomerGroups();
        $this->artisanMigrate('removeColumnDefaultFromCustomerGroups');;

        $finishTime = Carbon::now();
        $this->info("[{$finishTime->toDateTimeString()}] Total execution time: ".$startTime->diffForHumans(['syntax'=>CarbonInterface::DIFF_ABSOLUTE]));
    }

    protected function initNormalizeDB()
    {
        if ($this->option('force')) {
            $this->line('Force option, resetting up normalize database');
            if(File::exists(database_path("normalize.sqlite")))
                File::delete(database_path("normalize.sqlite"));
        }

        if(!File::exists(database_path("normalize.sqlite")))
        {
            File::put(database_path("normalize.sqlite"), "");
        }
        $normalizeSchema = Schema::connection("normalize");
        if(!$normalizeSchema->hasTable("normalizations")) {
            $normalizeSchema->create('normalizations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('time');
                $table->integer('seconds');
                $table->integer('amount_normalized')->nullable();
                $table->timestamps();
            });
        }
    }

    protected function avoidMigration($migrationName) {
        $migrationFile = $this->avoidableMigrations[$migrationName];
        $avoidedMigrationPath = base_path($migrationFile);
        $this->info("Avoiding (drop {$migrationName}) migration move $avoidedMigrationPath to $avoidedMigrationPath.avoid");
        File::move($avoidedMigrationPath, "{$avoidedMigrationPath}.avoid");
        $this->avoidedMigrations[$migrationName] = $avoidedMigrationPath;
        return $avoidedMigrationPath;
    }

    protected function unavoidMigration($avoidedMigrationName) {
        $avoidedMigration = $this->avoidedMigrations[$avoidedMigrationName] ?? $this->avoidableMigrations[$avoidedMigrationName];
        $this->info("Reset (drop $avoidedMigrationName) migration to be able to execute later");
        if(File::exists("{$avoidedMigration}.avoid"))
            File::move("{$avoidedMigration}.avoid",$avoidedMigration);
        unset($this->avoidedMigrations[$avoidedMigrationName]);
    }

    /**
     * @param array $avoided
     * @return void
     */
    protected function unavoidAvoidedMigrations() {
        foreach(array_keys($this->avoidedMigrations) as $avoidedMigrationName) {
            $this->unavoidMigration($avoidedMigrationName);
        }
        // Reset avoided migrations
        $this->avoidedMigrations = [];
    }

    protected function modifySchemaFromFullvendorMVC() {
        $this->info("Applying initial laravel table migrations");

        try {
            Artisan::call('migrate');
        } catch (QueryException $e) {
            $message = $e->getMessage();
            if(Str::contains($message,"'admin' already exists"))
               $this->info("   Expected error (admin already exists) continuing...");
            else
                $this->error("Error: $message");
        }
        $this->info("Seeding migrations table with the applied structure of MVC to avoid existing schema");
        Artisan::call('db:seed --class=FullvendorMVCSeeder');
        $this->info("Applying modifying migrations");
        Artisan::call('migrate');
        //$this->unavoidAvoidedMigrations();
    }

        /**
     * @param string $path
     * @param bool $rollback
     * @return void
     */
    public function artisanMigrate(string $avoidedMigrationName, bool $rollback = false): void
    {
        $command = $rollback ? 'migrate:rollback' : 'migrate';

        $migrationPath = $this->avoidableMigrations[$avoidedMigrationName];
        $this->unavoidMigration($avoidedMigrationName);

        Artisan::call($command,
            array(
                '--path' => $migrationPath,
                '--force' => true));
    }

    protected  function normalizeCatalogCarts(): void
    {
        if($this->startModelNormalization('catalogCarts','Migrating Catalog Carts into Order')) {
            $catalogCarts =
                DB::query()
                    ->select(["customer_id", "catalog_id", "product_id", "company_id", "user_id"])
                    ->from('catalog_cart')
                    ->whereDate('expired_date', '>', date('Y-m-d'))
                    ->get();

            $this->info("Total Catalog Carts to normalize in order: {$catalogCarts->count()}");
            $shoppingStatus = StatusOrder::getIdByCode(OrderStatusCodes::SHOPPING->value);
            if (!$shoppingStatus) {
                Artisan::call('db:seed --class=Database\\\\Seeders\\\\structure\\\\StatusOrdersTableSeeder');
                Artisan::call('db:seed --class=OrderFlowSeeder');
                $shoppingStatus = StatusOrder::getIdByCode(OrderStatusCodes::SHOPPING->value);
            }
            $this->withProgressBar($catalogCarts, function ($cart) use ($shoppingStatus) {
                $newOrder = new Order();
                $newOrder->customer_id = $cart->customer_id;
                $newOrder->company_id = $cart->company_id;
                $newOrder->seller_id = Vendor::whereId($cart->user_id)->exists() ? $cart->user_id : null;
                $newOrder->catalog_id = $cart->catalog_id;
                $newOrder->UUID = $newOrder->generate_uuid();
                $newOrder->tipo_d = "D";
                $newOrder->internal_notes = "";
                $newOrder->discount_type = 1;
                $newOrder->discount = 0;
                $newOrder->status_id = $shoppingStatus;
                $newOrder->order_number = $newOrder->generateOrderNumber($cart->company_id, "D");
                $newOrder->source = "normalization";
                $customer = Customer::query()->where('customer_id', $cart->customer_id)->first();
                $newOrder->language_id = Language::where('language_id', $customer->language_id)->exists(
                ) ? $customer->language_id : 1;
                $newOrder->new_address = "";

                if ($newOrder->save()) {
                    $this->generateOrderDetail($newOrder);
                }
            });
            $this->endModelNormalization('catalogCarts',$catalogCarts->count());
            $this->unavoidMigration('dropCatalogCart');
            $this->info("Applying migrate to execute avoided (drop catalog_carts) migration");
            Artisan::call('migrate');
            $this->info("Catalog Carts Normalized");
        }
    }

    protected  function normalizeUnitsDefault(): void
    {
        if($this->startModelNormalization('unitsDefault')) {

            $unitsDefault = Unit::query()->select(["company_id", "unit_id"])
                ->whereNotNull("company_id")
                ->where("default", 1)
                ->orWhere("default", "!=", 0);
            $this->info("Total Units Default to normalize in company: {$unitsDefault->count()}");
            $unitsDefault->each(function ($unitDefault) {
                $company = Company::where('company_id', $unitDefault->company_id)->first();
                if ($company && Unit::where('unit_id', $unitDefault->unit_id)->exists()) {
                    Company::where('company_id', $unitDefault->company_id)
                        ->update(['default_unit_id' => $unitDefault->unit_id]);
                }
            });
            $this->endModelNormalization('unitsDefault');
            $this->unavoidMigration('dropUnitsColumns');
        }
    }
    protected function normalizeCustomerSellers(): void
    {

        if($this->startModelNormalization('customerSellers')) {

            $customerSellers = Customer::query()->select(['customer_id', 'user_id'])->get();
            $this->info("Total Customer to normalize in customer_seller Customer: {$customerSellers->count()}");
            $this->withProgressBar($customerSellers, function ($customerSeller) {
                $users = collect(explode(',', $customerSeller->user_id));
                $users->each(function ($userId) use ($customerSeller) {
                    $userId = (int)$userId;
                    $existSeller = Vendor::where('id', $userId)->exists();
                    $existCustomerSeller = CustomerSeller::where('seller_id', $userId)->where('customer_id', $customerSeller->customer_id)->exists();
                    if ($existSeller && !$existCustomerSeller) {
                        CustomerSeller::create(
                            [
                                'seller_id' => $userId,
                                'customer_id' => $customerSeller->customer_id,
                            ]
                        );
                    }
                });
            });

            $this->endModelNormalization('customerSellers');
            $this->artisanMigrate('dropUserColumnFromCustomers');
        }
    }

    protected function normalizeProductCategories(): void
    {

        if($this->startModelNormalization('productCategories')) {
            $productCategories = Product::query()->select(['pro_id', 'category_id'])->get();
            $this->info("Total Product to normalize in category_product Categories: {$productCategories->count()}");
            $this->withProgressBar($productCategories, function ($productCategory) {
                $categories = collect(explode(',', $productCategory->category_id));
                $categories->each(function ($categoryId) use ($productCategory) {
                    $categoryId = (int)$categoryId;
                    if ($categoryId !== 0 && CategoryProduct::where('category_id', $categoryId)->where(
                            'product_id',
                            $productCategory->pro_id
                        )->count() == 0) {
                        CategoryProduct::create(
                            [
                                'category_id' => $categoryId,
                                'product_id' => $productCategory->pro_id,
                            ]
                        );
                    }
                });
            });
            $this->endModelNormalization('productCategories');
        }
    }

    protected function normalizeCatalogCategories(): void
    {

        if($this->startModelNormalization('catalogCategories')) {
            $orderCategories = OrderCategory::query()->select(['catalog_id', 'category_id', 'order', 'created_at']
            )->get();
            $this->info("Total Catalog to normalize in catalog_category Categories: {$orderCategories->count()}");

            $this->withProgressBar($orderCategories, function ($orderCategory) {
                $categoryId = $orderCategory->category_id;
                $catalogId = $orderCategory->catalog_id;
                $order = $orderCategory->order;

                if (
                    Category::where('cat_id', $categoryId)->exists()
                    && !CatalogCategory::where('category_id', $categoryId)->where('catalog_id', $catalogId)->exists()
                    && !CatalogCategory::where('catalog_id', $catalogId)->where('order', $order)->exists()
                ) {
                    (new CatalogCategory())->makeFillableCreatedAt()->fill([
                                                                               'category_id' => $categoryId,
                                                                               'catalog_id' => $catalogId,
                                                                               'order' => $orderCategory->order,
                                                                               'created_at' => $orderCategory->created_at,
                                                                           ])->save();
                }
            });
            $this->endModelNormalization('catalogCategories',$orderCategories->count());;
        }
    }

    protected function normalizeCompanyUser(): void
    {
        if($this->startModelNormalization('companyUser')) {
            $this->clearUsersCache();
            $originalCompanies = Company::select([
                                                     'company_id',
                                                     'name',
                                                     'email',
                                                     'password',
                                                     'createdby_comp_id',
                                                     'rol_id'
                                                 ])->get();

            $this->withProgressBar($originalCompanies, function ($company) {
                $user = $this->getUser($company);

                $companyId = $company->createdby_comp_id === 0 ? $company->company_id : $company->createdby_comp_id;
                $type = $this->getType($company);

                if ($user && Company::where('company_id', $companyId)->exists()) {
                    if (!$this->createCompanyUser($companyId, $user->id, $type, $company->rol_id)) {
                        $this->info(
                            "User {$user->id} email {$user->email} already has a profile assigned to the company {$companyId}"
                        );
                    }
                } else {
                    $this->info("Error creating CompanyUser.");
                }
            });

            $this->endModelNormalization('companyUser');
        }
    }

    protected function normalizeSellerUser(): void
    {
        if($this->startModelNormalization('sellerUser')) {
            $this->clearUsersCache();
            $sellers = Vendor::query()->select([
                                                   'id',
                                                   DB::raw("CONCAT(first_name, ' ', last_name) as name"),
                                                   'user_id',
                                                   'company_id',
                                                   'email',
                                                   'password',
                                                   'default'
                                               ])->get();

            $this->withProgressBar($sellers, /** @param Vendor $seller */ function (Vendor $seller) {
                $companyId = $seller->company_id;
                if (Company::where('company_id', $companyId)->exists()) {
                    $user = $this->getUser($seller);

                    if (!$user) {
                        $this->info("Error creating Seller CompanyUser.");
                        $seller->delete();
                        return;
                    }

                    $userId = $user->id;

                    if (CompanyUser::where('company_id', $companyId)->where('user_id', $userId)->where(
                        'type',
                        CompanyUserTypes::Seller
                    )->exists()) {
                        $existingSeller = Vendor::where('company_id', $companyId)->where('user_id', $userId)->first();
                        if ($existingSeller && $existingSeller->id != $seller->id) {
                            $seller->delete();
                            $this->info("Usuario existente {$user->email}");
                        } else {
                            $realSeller = Vendor::query()->where('email', $seller->email)->first();
                            $realSeller->user_id = $userId;
                            $realSeller->save();
                            $this->info("Usuario Guardado {$user->email}");
                        }
                    } else {
                        if ($this->createCompanyUser($companyId, $userId, CompanyUserTypes::Seller)) {
                            $seller->update(['user_id' => $userId]);
                        } else {
                            $this->info(
                                "User {$userId} email {$user->email} already has a profile assigned to the company {$companyId}"
                            );
                        }
                    }

                    if (!$seller->trashed() && $seller->default) {
                        Company::where('company_id',$companyId)->update(['default_seller_id' => $seller->id]);
                    }

                } else {
                    $this->info("Error creating Seller CompanyUser. Company id: {$companyId} doesn't exist.");
                    $seller->delete();
                }
            });
            $this->endModelNormalization('sellerUser');

            $this->artisanMigrate('removeColumnsFromSellers');
        }
    }

    protected function normalizeWarehouseUser(): void
    {
        if($this->startModelNormalization('warehouseUser')) {
            $this->clearUsersCache();
            $warehouses = WarehouseUser::select([
                                                    DB::raw("CONCAT(first_name, ' ', last_name) as name"),
                                                    'company_id',
                                                    'email',
                                                    'password'
                                                ])->get();

            $this->info("Migrating WarehouseUsers table");
            $this->artisanMigrate('removeRelationshipsWithWarehouseUsers');
            WarehouseUser::query()->truncate();
            $this->artisanMigrate('removeColumnsFromWarehouseUsers');

            $this->withProgressBar($warehouses, function ($warehouse) {
                $user = $this->getUser($warehouse);

                if ($user && Company::where('company_id', $warehouse->company_id)->exists()) {
                    if (!$this->createCompanyUser($warehouse->company_id, $user->id, CompanyUserTypes::Warehouse)) {
                        $this->info(
                            "User {$user->id} email {$user->email} already has a profile assigned to the company {$warehouse->company_id}"
                        );
                    }
                    $newWarehouse = Warehouse::create([
                        'name' => $warehouse->name,
                        'company_id' => $warehouse->company_id,
                        'address_id' => $warehouse->company->addresses->first()?->id ?? 1,
                    ]);
                    WarehouseUser::create([
                        'user_id' => $user->id,
                        'company_id' => $warehouse->company_id,
                        'warehouse_id' => $newWarehouse->id,
                    ]);
                } else {
                    $this->info(
                        "Error creating Warehouse CompanyUser."
                    );
                }
            });

            $this->endModelNormalization('warehouseUser');
        }
    }

    protected function normalizeAdminUser(): void
    {
        if($this->startModelNormalization('adminUser')) {
            $admins = Admin::select([
                                        'name',
                                        'email',
                                        'password'
                                    ])->get();

            $this->info("Migrating Admin table");
            $this->artisanMigrate('removeColumnsFromAdmin');
            Admin::query()->truncate();

            $this->withProgressBar($admins, function ($admin) {
                try {
                    $newUser = User::make([
                        'name'  => $admin->name,
                        'email' => $admin->email,
                    ]);

                    $newUser->password = $admin->password;
                    $newUser->save();

                    Admin::create([
                                      'user_id' => $newUser->id,
                                      'status' => 1 //active
                                  ]);
                } catch (\Exception $e) {
                    $this->info($e->getMessage());
                    $this->info("Error creating user, rolling back the migration");
                    $this->artisanMigrate('removeColumnsFromAdmin', true);
                    $this->info("Migration rolled back");
                }
            });

            $this->endModelNormalization('adminUser',$admins->count());
        }
    }

    protected function NormalizeCustomerUser(): void
    {
        if($this->startModelNormalization('customerUser')) {
            $this->clearUsersCache();

            $customers = Customer::query()->select(
                'customer_id',
                'company_id',
                'language_id',
                'name',
                'email',
                'password'
            )->get();
            $this->info("Total Customers to normalize as users: {$customers->count()}");
            $customersUsers = [];
            $companyUsers = [];

            $this->withProgressBar($customers, function ($customer) use (&$customersUsers, &$companyUsers) {
                try {
                    $user = $this->getUser($customer);

                    if ($user){
                        $cu = [
                            'customer_id' => $customer->customer_id,
                            'user_id' => $user->id
                        ];

                        $customersUsers[] = $cu;
                        $companyUsers[] = [
                            'company_id' => $customer->company_id,
                            'type' => CompanyUserTypes::Customer,
                            'user_id' => $user->id
                        ];
                    }
                } catch (\Exception $e) {
                    $this->info($e->getMessage());
                }
            });

            try {
                $chunkSize = 15000;
                foreach (array_chunk($customersUsers, $chunkSize) as $custUsChunk) {
                    CustomerUser::insertOrIgnore($custUsChunk);
                }
                foreach (array_chunk($companyUsers, $chunkSize) as $compUsChunk) {
                    CompanyUser::insertOrIgnore($compUsChunk);
                }
                $this->endModelNormalization('customerUser');
            } catch (\Exception $e) {
                $this->info($e->getMessage());
            }
        }
    }

    protected function normalizeCompanyAddress(): void
    {

        if($this->startModelNormalization('companyAddress')) {
            $this->info("Normalizing Company Address");

            $originalCompanies = Company::select([
                                                     'company_id',
                                                     'name',
                                                     'address',
                                                     'country',
                                                     'state',
                                                     'city',
                                                     'zip_code'
                                                 ])
                ->where(function ($query) {
                    $query->whereNotNull('address')->where('address', '!=', '')
                        ->orWhereNotNull('country')->where('country', '!=', '')
                        ->orWhereNotNull('state')->where('state', '!=', '')
                        ->orWhereNotNull('city')->where('city', '!=', '')
                        ->orWhereNotNull('zip_code')->where('zip_code', '!=', '');
                })
                ->get();

            $this->withProgressBar($originalCompanies, function ($company) {
                try {
                    $address = $this->normalizeAddress([
                                                           'street' => $company->address,
                                                           'zipcode' => $company->zip_code,
                                                           'zone' => null,
                                                           'country' => $company->country,
                                                           'state' => $company->state,
                                                           'city' => $company->city,
                                                       ]);

                    if ($address) {
                        $company->addresses()->attach($address->id, [
                            'is_primary' => false,
                        ]);
                    } else {
                        $this->warn("Could not create address for company #{$company->customer_id}");
                    }
                } catch (\Throwable $e) {
                    $this->error("Error creating address for company #{$company->customer_id}: {$e->getMessage()}");
                }
            });

            $this->endModelNormalization('companyAddress');
            $this->info('Dropping addresses columns from company table');
            $this->artisanMigrate('removeAddressColumnsFromCompanies');
        }
    }

    protected function normalizeCustomerCommercialAddress(): void
    {
        if($this->startModelNormalization('customerCommercialAddress')) {
            $this->info("Normalizing Customer Commercial Address");

            $originalCustomers = Customer::select([
                                                      'customer_id',
                                                      'name',
                                                      'commercial_address',
                                                      'commercial_country',
                                                      'commercial_state',
                                                      'commercial_city',
                                                      'commercial_zone',
                                                      'commercial_zip_code'
                                                  ])
                ->where(function ($query) {
                    $query->whereNotNull('commercial_address')->where('commercial_address', '!=', '')
                        ->orWhereNotNull('commercial_country')->where('commercial_country', '!=', '')
                        ->orWhereNotNull('commercial_state')->where('commercial_state', '!=', '')
                        ->orWhereNotNull('commercial_city')->where('commercial_city', '!=', '')
                        ->orWhereNotNull('commercial_zone')->where('commercial_zone', '!=', '')
                        ->orWhereNotNull('commercial_zip_code')->where('commercial_zip_code', '!=', '');
                })
                ->get();

            $this->withProgressBar($originalCustomers, function ($customer) {
                try {
                    $address = $this->normalizeAddress([
                                                           'street' => $customer->commercial_address,
                                                           'zipcode' => $customer->commercial_zip_code,
                                                           'zone' => $customer->commercial_zone,
                                                           'country' => $customer->commercial_country,
                                                           'state' => $customer->commercial_state,
                                                           'city' => $customer->commercial_city,
                                                       ]);

                    if ($address) {
                        $customer->addresses()->attach($address->id, [
                            'type' => AddressCustomerTypes::Commercial->value,
                            'is_primary' => false,
                        ]);
                    } else {
                        $this->warn("Could not create address for customer #{$customer->customer_id}");
                    }
                } catch (\Throwable $e) {
                    $this->error("Error creating address for customer #{$customer->customer_id}: {$e->getMessage()}");
                }
            });

            $this->endModelNormalization('customerCommercialAddress');
        }

    }

    protected function normalizeCustomerDispatchAddress(): void
    {
        if($this->startModelNormalization('customerDispatchAddress')) {
            $this->info("Normalizing Customer Dispatch Address");

            $originalCustomers = Customer::select([
                                                      'customer_id',
                                                      'name',
                                                      'dispatch_address',
                                                      'dispatch_country',
                                                      'dispatch_state',
                                                      'dispatch_city',
                                                      'dispatch_zone',
                                                      'dispatch_zip_code'
                                                  ])
                ->where(function ($query) {
                    $query->whereNotNull('dispatch_address')->where('dispatch_address', '!=', '')
                        ->orWhereNotNull('dispatch_country')->where('dispatch_country', '!=', '')
                        ->orWhereNotNull('dispatch_state')->where('dispatch_state', '!=', '')
                        ->orWhereNotNull('dispatch_city')->where('dispatch_city', '!=', '')
                        ->orWhereNotNull('dispatch_zone')->where('dispatch_zone', '!=', '')
                        ->orWhereNotNull('dispatch_zip_code')->where('dispatch_zip_code', '!=', '');
                })
                ->get();

            $this->withProgressBar($originalCustomers, function ($customer) {
                try {
                    $address = $this->normalizeAddress([
                                                           'street' => $customer->dispatch_address,
                                                           'zipcode' => $customer->dispatch_zip_code,
                                                           'zone' => $customer->dispatch_zone,
                                                           'country' => $customer->dispatch_country,
                                                           'state' => $customer->dispatch_state,
                                                           'city' => $customer->dispatch_city,
                                                       ]);

                    if ($address) {
                        $customer->addresses()->attach($address->id, [
                            'type' => AddressCustomerTypes::Dispatch->value,
                            'is_primary' => false,
                        ]);
                    } else {
                        $this->warn("Could not create address for customer #{$customer->customer_id}");
                    }
                } catch (\Throwable $e) {
                    $this->error("Error creating address for customer #{$customer->customer_id}: {$e->getMessage()}");
                }
            });
            $this->endModelNormalization('customerDispatchAddress');
        }
    }

    protected function normalizeCustomerCommercialDeliveryAddress(): void
    {
        if($this->startModelNormalization('customerCommercialDeliveryAddress')) {
            $this->info("Normalizing Customer Commercial Delivery Address");

            $originalCustomers = Customer::select([
                                                      'customer_id',
                                                      'name',
                                                      'commercial_delivery_address',
                                                      'commercial_country',
                                                      'commercial_state',
                                                      'commercial_city',
                                                      'commercial_zone',
                                                      'commercial_zip_code'
                                                  ])
                ->whereNotNull('commercial_delivery_address')
                ->where('commercial_delivery_address', '!=', '')
                ->where(function ($query) {
                    $query->orWhereNotNull('commercial_country')->where('commercial_country', '!=', '')
                        ->orWhereNotNull('commercial_state')->where('commercial_state', '!=', '')
                        ->orWhereNotNull('commercial_city')->where('commercial_city', '!=', '')
                        ->orWhereNotNull('commercial_zone')->where('commercial_zone', '!=', '')
                        ->orWhereNotNull('commercial_zip_code')->where('commercial_zip_code', '!=', '');
                })
                ->get();

            $this->withProgressBar($originalCustomers, function ($customer) {
                try {
                    $address = $this->normalizeAddress([
                                                           'street' => $customer->dispatch_address,
                                                           'zipcode' => $customer->dispatch_zip_code,
                                                           'zone' => $customer->dispatch_zone,
                                                           'country' => $customer->dispatch_country,
                                                           'state' => $customer->dispatch_state,
                                                           'city' => $customer->dispatch_city,
                                                       ]);

                    if ($address) {
                        $customer->addresses()->attach($address->id, [
                            'type' => AddressCustomerTypes::CommercialDelivery->value,
                            'is_primary' => false,
                        ]);
                    } else {
                        $this->warn("Could not create address for customer #{$customer->customer_id}");
                    }
                } catch (\Throwable $e) {
                    $this->error("Error creating address for customer #{$customer->customer_id}: {$e->getMessage()}");
                }
            });

            $this->endModelNormalization('customerCommercialDeliveryAddress');
        }
    }

    protected function dropAddressColumnsFromCustomer(): void
    {
        if($this->startModelNormalization('dropAddressColumnsFromCustomer')) {
            if (Normalization::where('name', 'customerCommercialAddress')->exists() &&
                Normalization::where('name', 'customerDispatchAddress')->exists() &&
                Normalization::where('name', 'customerCommercialDeliveryAddress')->exists()) {
                $this->endModelNormalization('dropAddressColumnsFromCustomer');
                $this->info("Dropping address columns from customer");
                $this->artisanMigrate('removeAddressColumnsFromCustomers');
            }
        }
    }

    protected function normalizeOrderAddress(): void
    {

        if($this->startModelNormalization('orderAddress')) {
            $this->info("Normalizing Order Address");

            $originalOrders = Order::select([
                                                'order_id',
                                                'new_address',
                                                'new_address_json'
                                            ])
                ->where('new_address', true)
                ->whereNotNull('new_address_json')
                ->where('new_address_json', '!=', '')
                ->get();

            $this->withProgressBar($originalOrders, function ($order) {
                try {
                    $addressFromJson = (object)$order->new_address_json;

                    $address = $this->normalizeAddress([
                                                           'street' => $addressFromJson->delivery_address ?? '',
                                                           'zipcode' => $addressFromJson->zip_code ?? '',
                                                           'zone' => $addressFromJson->zone ?? '',
                                                           'country' => $addressFromJson->country ?? '',
                                                           'state' => $addressFromJson->state ?? '',
                                                           'city' => $addressFromJson->city ?? '',
                                                       ]);

                    if ($address) {
                        $order->address_id = $address->id;
                        $order->save();
                    } else {
                        $this->warn("Could not create address for order #{$order->order_id}");
                    }
                } catch (\Throwable $e) {
                    $this->error("Error creating address for order #{$order->order_id}: {$e->getMessage()}");
                }
            });

            $this->endModelNormalization('orderAddress');
            $this->info("Dropping new_address column from orders");
            $this->artisanMigrate('removeNewAddressColumnFromOrders');
        }
    }

    protected function normalizeOrderNotes(): void
    {

        if($this->startModelNormalization('orderNotes')) {
            $originalOrders = Order::select([
                                                'order_id',
                                                'customer_id',
                                                'order_comments',
                                                'delivery_notes',
                                                'internal_notes',
                                                'new_address_json'
                                            ])
                ->where(function ($query) {
                    $query->orWhereNotNull('order_comments')->where('order_comments', '!=', '')
                        ->orWhereNotNull('delivery_notes')->where('delivery_notes', '!=', '')
                        ->orWhereNotNull('internal_notes')->where('internal_notes', '!=', '')
                        ->orWhereNotNull('new_address_json')->where('new_address_json', '!=', '');
                })
                ->get();

            $adminUser = Admin::first()->user;

            $this->withProgressBar($originalOrders, function ($order) use ($adminUser) {
                try {
                    $user = Customer::find($order->customer_id)->user ?? $adminUser;

                    $notes = [
                        OrderNoteTypes::Order->value => $order->order_comments,
                        OrderNoteTypes::Delivery->value => $order->delivery_notes,
                        OrderNoteTypes::Internal->value => $order->internal_notes,
                    ];

                    foreach ($notes as $type => $note) {
                        if (!empty($note)) {
                            $order->notes()->create([
                                                        'type' => $type,
                                                        'user_id' => $user->id,
                                                        'note' => $note,
                                                    ]);
                        }
                    }

                    if (!empty($order->new_address_json)) {
                        $address = (object)$order->new_address_json;
                        if (!empty($address->shipping_notes)) {
                            $order->notes()->create([
                                                        'type' => OrderNoteTypes::Shipping->value,
                                                        'user_id' => $user->id,
                                                        'note' => $address->shipping_notes,
                                                    ]);
                        }
                    }
                } catch (\Throwable $e) {
                    $this->error("Error creating note for order #{$order->order_id}: {$e->getMessage()}");
                }
            });

            $this->endModelNormalization('orderNotes');
            $this->info(
                "Dropping order_comments, delivery_notes, internal_notes and new_address_json columns from orders"
            );
            $this->artisanMigrate('removeNotesColumnsFromOrders');
        }
    }

    protected  function normalizeTermsDefault(): void
    {
        if($this->startModelNormalization('termsDefault')) {
            $termsDefault = TermsOfSale::query()
                ->select(["company_id", "term_id"])
                ->whereNotNull('company_id')
                ->where(function ($query) {
                    $query->where('default', 1)
                        ->orWhere('default', '!=', 0);
                })
                ->get();

            $this->info("Total Terms Default to normalize in company: {$termsDefault->count()}");

            $this->withProgressBar($termsDefault, function ($termDefault) {
                $company = Company::where('company_id', $termDefault->company_id)->first();
                if ($company) {
                    $company->update(['default_term_id' => $termDefault->term_id]);
                }
            });

            $this->endModelNormalization('termsDefault');
            $this->info('Dropping default, user_id and lang_id columns from terms_of_sales table');
            $this->artisanMigrate('removeColumnsFromTerms');
        }
    }

    protected  function normalizeCurrency(): void
    {
        if($this->startModelNormalization('currency')) {
            $currencies = Currencybycountry::all();

            $this->info("Total Currencies to normalize: {$currencies->count()}");

            $this->withProgressBar($currencies, function ($currency) {
                Currency::create([
                                     'name' => $currency->Name,
                                     'code' => $currency->Code,
                                     'symbol' => $currency->Symbol,
                                     'tags' => [
                                         'main_category' => $currency->GroupID,
                                         'country_category' => $currency->Contry,
                                     ]
                                 ]);
            });

            $this->endModelNormalization('currency');
            $this->info('Relate companies with currency & drop currency_code_id column in companies table');
            $this->artisanMigrate('changesInCurrencies');
            $this->info('Dropping currencybycountries table');
            $this->artisanMigrate('dropCurrencybycountries');
        }
    }

    protected  function normalizeProductUnit(): void
    {
        if($this->startModelNormalization('productUnit')) {
            $products = Product::whereNotNull('unit_type')
                ->where('unit_type', '!=', '')
                ->get();

            $this->info("Total Products to normalize: {$products->count()}");

            $unitsRaw = Unit::whereIn('company_id', $products->pluck('company_id')->unique())
                ->whereIn('unit', $products->pluck('unit_type')->unique())
                ->where('status', true)
                ->orderBy('unit_id')
                ->get();

            $units = $unitsRaw
                ->groupBy(fn($u) => $u->company_id . '|' . $u->unit)
                ->map(fn($group) => $group->first());

            $this->withProgressBar($products, function ($product) use ($units) {
                $key = $product->company_id . '|' . $product->unit_type;
                if (isset($units[$key])) {
                    $product->unit_id = $units[$key]->unit_id;
                    $product->save();
                }
            });

            $this->endModelNormalization('productUnit');
            $this->info('Dropping unit_type column in products table');
            $this->artisanMigrate('removeUnitTypeColumnFromProducts');
        }
    }

    protected  function normalizeCustomerGroups(): void
    {
        if($this->startModelNormalization('customerGroups')) {
            $customerGroups = CustomerGroup::all();

            $this->info("Total Customer Groups to normalize: {$customerGroups->count()}");

            $this->withProgressBar($customerGroups, function ($group) {
                if (Company::where('company_id', $group->company_id)->exists() && $group->default) {
                    Company::where('company_id', $group->company_id)->update(['default_customer_group_id' => $group->group_id]);
                }
            });

            $this->endModelNormalization('customerGroups');
            $this->info('Dropping default column in customer_groups');
            $this->artisanMigrate('removeColumnDefaultFromCustomerGroups');
        }
    }

    protected function normalizeAddress(array $data): ?Address
    {
        try {
            $country = $this->getOrCreateByIdOrName(Country::class, $data['country'] ?? null, [
                'sortname' => strtoupper(substr(trim($data['country'] ?? ''), 0, 2)),
                'phonecode' => '00000'
            ]);

            $state = $this->getOrCreateByIdOrName(State::class, $data['state'] ?? null, [
                'country_id' => $country->id
            ]);

            $city = $this->getOrCreateByIdOrName(City::class, $data['city'] ?? null, [
                'state_id' => $state->id
            ]);

            $zipcode = $data['zipcode'] ?? null;

            return Address::create([
                'street'     => $this->deleteExtraWhitespaces($data['street']) ?? '',
                'zipcode'    => is_numeric($zipcode) ? Str::limit($zipcode, 20, '') : '',
                'notes'      => !is_numeric($zipcode) ? Str::limit($zipcode, 255, '') : null,
                'zone'       => $data['zone'] ?? '',
                'country_id' => $country->id,
                'state_id'   => $state->id,
                'city_id'    => $city->id,
            ]);
        } catch (\Throwable $e) {
            $this->error("Error normalizing address: " . $e->getMessage());
            return null;
        }
    }

    private function normalizeProductImages()
    {
        /*
         * TODO: query all ProductImages, find the way to get the file to add Image, buffer image UUID to be
         * related to the product
         */
        if($this->startModelNormalization('productImages')) {
            $this->unavoidMigration('RemoveColumnsFromProductImages');
            Artisan::call('migrate');

            /*
             * TODO: apply the buffered relations for product image assignation
             */

            $this->endModelNormalization('productImages');
        }
    }

    function getOrCreateByIdOrName($modelClass, $value, $extra = []) {
        if (blank($value)) $value = 'Unknown';

        $value = $this->deleteExtraWhitespaces($value);

        return $modelClass::where('id', $value)
            ->orWhere('name', $value)
            ->first() ?? $modelClass::create(array_merge(['name' => Str::limit($value,30,'')], $extra));
    }

    function deleteExtraWhitespaces($string): array|string|null
    {
        return $string ? preg_replace('/\s+/', ' ', trim($string)) : null;

    }

    private function getType($company): CompanyUserTypes
    {
        if ($company->createdby_comp_id === 0 ) {
            return CompanyUserTypes::Manager;
        }

        if ($company->rol){
            if (Str::contains($company->rol->name, 'admin', true)){
                return CompanyUserTypes::Manager;
            }

            return CompanyUserTypes::Seller;
        }

        return CompanyUserTypes::Customer;
    }

    /**
     * @param mixed $companyId
     * @param $userId
     * @param CompanyUserTypes $type
     * @return bool
     */
    function createCompanyUser(mixed $companyId, $userId, CompanyUserTypes $type, $rolId = null): bool
    {
        if (!CompanyUser::where('company_id', $companyId)->where('user_id',$userId)->exists()) {
            if ($type == CompanyUserTypes::Manager) {
                if ($rolId){
                    $managerProfile = ManagerProfiles::where('rol_id',$rolId)->sole();
                }
                else $managerProfile = ManagerProfiles::whereNull('company_id')->where('name','ADMINISTRADOR ')->first();//default manager_profile

                $managerProfile->companyUsers()->create($this->getArr($companyId, $userId, $type));
                return true;
            }

            if ($type == CompanyUserTypes::Seller) {
                $sellerProfile = SellerProfiles::whereNull('company_id')->where('profile_name','Vendedor')->first();//default seller_profile
                $sellerProfile->companyUsers()->create($this->getArr($companyId, $userId, $type));
                return true;
            }
            CompanyUser::create($this->getArr($companyId, $userId, $type));
            return true;
        }
        return false;
    }

    /**
     * @param mixed $companyId
     * @param $userId
     * @param CompanyUserTypes $type
     * @return array
     */
    function getArr(mixed $companyId, $userId, CompanyUserTypes $type): array
    {
        return [
            'company_id' => $companyId,
            'user_id' => $userId,
            'type' => $type,
        ];
    }

    protected function userExists($email): bool
    {
        if(!isset($this->usersCache)) {
            $this->usersCache = User::all()->mapWithKeys(fn($usr,$key) => [strtolower($usr->email) => $usr] )->all();
        }

        $existingCachedUser = $this->getUserFromCacheByEmail($email);;
        return isset($existingCachedUser);
    }

    /**
     * @param $model
     * @return mixed
     */
    protected function getUserFromCacheByEmail($email): mixed
    {
        return array_key_exists(strtolower($email),$this->usersCache) ? $this->usersCache[strtolower($email)] : null;
    }

    protected function cacheUser($email,$user) {
        $this->usersCache[strtolower($email)] = $user;
    }

    protected function clearUsersCache()
    {
        unset($this->usersCache);
    }

    /**
     * @param $model
     * @return mixed
     */
    function getUser($model)
    {
        $validEmail = $this->extractEmail($model->email);
        if (!$validEmail) {
            return null;
        }

        if($this->userExists($validEmail)) {
            return $this->getUserFromCacheByEmail($validEmail);
        }
        $newUser = User::make([
            'name'  => $model->name,
            'email' => $validEmail,
        ]);

        $newUser->password = $model->password ?? bcrypt(Str::random());
        $newUser->save();

        $this->cacheUser($validEmail,$newUser);
        return $newUser;
    }

    private function runCustomSeeders(): void
    {
        if($this->startModelNormalization('customSeeders')) {
            $this->info('Running custom seeders...');
            Artisan::call('db:seed:custom');
            $this->info('Custom seeders executed successfully.');
            $this->normalizeCatalogCategories();
            $this->endModelNormalization('customSeeders');
        }
    }

    private function generateOrderDetail(Order $newOrder): void
    {
        $token = $newOrder->token_car;

        if ($token){
            $detailCar = DetailCar::where('token_car', $token)->get();
            if ($detailCar){
                $newOrderDetail = new OrderDetail();
                $newOrderDetail->order_id = $newOrder->order_id;
                $newOrderDetail->product_id = $detailCar->product_id;
                $newOrderDetail->qty = $detailCar->qty;
                $newOrderDetail->delivered_quantity = 0;
                $newOrderDetail->discount = $newOrder->discount;
                $newOrderDetail->id_kor = 0; //?
                $newOrderDetail->save();
            }
        }
    }

    private function extractEmail(string $string): ?string
    {
        $normalized = str_replace([';', ',', ' '], ',', $string);

        preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $normalized, $match);

        return $match[0] ?? null;
    }

}
