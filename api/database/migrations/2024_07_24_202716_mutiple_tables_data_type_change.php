<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->modifyOrderCategories();
        $this->modifyCompanies();
        //fix error "Invalid datetime format: 1292 Incorrect datetime value: '0000-00-00 00:00:00' for column `appfv`.`catalog_cart`.`expired_date`"
        $this->modifyCatalogCart();
        $this->modifyCatalogSentMail();
        //fix error "Invalid datetime format: 1292 Incorrect datetime value: '0000-00-00 00:00:00' for columns
        $this->modifyOrders();
        $this->modifyDetailCar();
        $this->modifyCatalog();
        $this->modifyCategories();
        $this->modifyCompanyContacts();
        $this->modifyCompanyStatusOrder();
        $this->modifyCurrency();
        $this->modifyCustomerGroups();
        $this->modifyCustomers();
        $this->modifyImportHistory();
        $this->modifyInventory();
        $this->modifyLogs();
        $this->modifyNavbarRolId();
        $this->modifyOrderStatusHistory();
        $this->modifyProductImages();
        $this->modifyProducts();
        $this->modifyRol();

        $this->modifySatusRol();

        $this->modifyTermOfSales();

        $this->modifyUnits();

        $this->modifyUserProfiles();

        $this->modifySellers();
        $this->modifyWharehouseUsers();

        $this->modifyWordersList();

        $this->modifyConfig();

        $this->modifyOrderDetails();


        $this->modifyCountries();

        $this->modifyLanguages();

        $this->modifyNavbars();

        //CONSULTAR
//        Schema::table('sessions', function (Blueprint $table) {
//            $table->bigIncrements('id')->change();
//        });

        $this->modifyStates();

        $this->modifyCatalogRequest();

        $this->modifyChangesApp();

        $this->modifyCities();

        $this->modifyDeliveredOrders();

        $this->modifyFormatDate();

        $this->modifyKeys();

        $this->modifySessionCart();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_categories', function (Blueprint $table) {
            $table->integer('category_id')->change();
            $table->integer('catalog_id')->change();
            $table->integer('company_id')->nullable()->change();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->integer('language_id')->change();
            $table->integer('createdby_comp_id')->change();
            $table->string('currency_code_id')->nullable()->change();
            $table->integer('rol_id')->nullable()->change();
        });

        if (Schema::hasColumn('companies', 'company_category_id'))
        {
            Schema::table('companies', function (Blueprint $table)
            {
                $table->renameColumn('company_category_id', 'category_id');
            });

            Schema::table('companies', function (Blueprint $table) {
                $table->integer('category_id')->nullable()->change();
            });
        }

        Schema::table('catalog_cart', function (Blueprint $table) {
            $table->integer('catalog_id')->nullable()->change();

            $table->bigInteger('customer_id')->nullable()->change();
            $table->bigInteger('product_id')->nullable()->change();
            $table->integer('user_id')->nullable()->change();
            $table->integer('company_id')->nullable()->change();
            $table->integer('order_id')->nullable()->change();
        });

        Schema::table('catalog_sent_mail', function (Blueprint $table) {
            $table->integer('catalog_id')->change();
            $table->integer('catlog_reqest_id')->change();
            $table->integer('company_id')->nullable()->change();
        });

        Schema::table('orders', function (Blueprint $table) {$table->double('discount')->change();});

        Schema::table('orders', function (Blueprint $table) {
            $table->bigInteger('order_id')->autoIncrement()->change();
            $table->integer('catalog_id')->nullable()->change();
            $table->integer('cart_id')->nullable()->change();
            $table->bigInteger('company_id')->nullable()->change();
            $table->bigInteger('user_id')->nullable()->change();
            $table->tinyInteger('language_id')->change();
            $table->bigInteger('customer_id')->change();
            $table->integer('warehouse_user_id')->nullable()->change();
            $table->bigInteger('catalog_id')->nullable()->change();
            $table->float('discount',12)->change();
        });

        Schema::table('detail_car', function (Blueprint $table) {
            $table->integer('cart_id')->nullable()->change();
            $table->integer('company_id')->nullable()->change();
            $table->integer('product_id')->nullable()->change();
            $table->integer('customer_id')->nullable()->change();
            $table->integer('session_id')->nullable()->change();
        });

        Schema::table('catalog', function (Blueprint $table) {
            $table->bigInteger('catalog_id')->autoIncrement()->change();
            $table->bigInteger('company_id')->change();
            $table->text('cat_id')->change();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->bigInteger('cat_id')->autoIncrement()->change();
            $table->integer('company_id')->change();
            $table->integer('language_id')->nullable()->change();
        });

        Schema::table('company_contacts', function (Blueprint $table) {
            $table->bigInteger('company_id')->nullable()->change();
        });

        Schema::table('company_status_order', function (Blueprint $table) {
            $table->integer('company_id')->nullable()->change();
        });

        Schema::table('currency', function (Blueprint $table) {
            $table->integer('currency_id')->autoIncrement()->change();
            $table->integer('company_id')->change();
        });

        Schema::table('customer_groups', function (Blueprint $table) {
            $table->bigInteger('group_id')->autoIncrement()->change();
            $table->integer('company_id')->nullable()->change();
            $table->integer('language_id')->nullable()->change();
            $table->integer('user_id')->nullable()->change();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->bigInteger('customer_id')->autoIncrement()->change();
            $table->integer('company_id')->change();
            $table->integer('language_id')->nullable()->change();
            $table->string('user_id')->nullable()->change();
            $table->integer('term_id')->nullable()->change();
            $table->integer('group_id')->nullable()->change();
        });

        Schema::table('import_history', function (Blueprint $table) {
            $table->integer('company_id')->change();
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->bigInteger('company_id')->change();
        });

        Schema::table('logs', function (Blueprint $table) {
            $table->bigInteger('company_id')->change();
            $table->integer('user_id')->change();
        });

        Schema::table('navbar_rol_id', function (Blueprint $table) {
            $table->integer('company_id')->nullable()->change();
            $table->integer('navbar_id')->nullable()->change();
            $table->integer('rol_id')->nullable()->change();
        });

        Schema::table('order_status_history', function (Blueprint $table) {
            $table->integer('company_id')->nullable()->change();
            $table->integer('order_id')->nullable()->change();
            $table->integer('user_id')->nullable()->change();
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->integer('product_id')->nullable()->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->bigInteger('pro_id')->autoIncrement()->change();
            $table->bigInteger('company_id')->nullable()->change();
            $table->integer('language_id')->change();
            $table->bigInteger('warehouse_user_id')->nullable()->change();
            $table->string('category_id')->nullable()->change();
        });

        Schema::table('rol', function (Blueprint $table) {
            $table->integer('rol_id')->autoIncrement()->change();
            $table->integer('company_id')->nullable()->change();
        });

        Schema::table('status_rol', function (Blueprint $table) {
            $table->integer('company_id')->nullable()->change();
            $table->integer('rol_id')->nullable()->change();
        });

        Schema::table('terms_of_sales', function (Blueprint $table) {
            $table->bigInteger('term_id')->autoIncrement()->change();
            $table->integer('company_id')->nullable()->change();
            $table->unsignedBigInteger('language_id')->nullable()->change();
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::table('units', function (Blueprint $table) {
            $table->integer('company_id')->change();
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->bigInteger('company_id')->nullable()->change();
        });

        Schema::table('sellers', function (Blueprint $table) {
            $table->bigInteger('company_id')->nullable()->change();
        });

        Schema::table('warehouse_users', function (Blueprint $table) {
            $table->bigInteger('company_id')->nullable()->change();
            $table->dateTime('deleted_at')->change();
        });

        Schema::table('wordersList', function (Blueprint $table) {
            $table->timestamp('updated')->default('0000-00-00 00:00:00')->change();
            $table->bigInteger('company_id')->nullable()->change();
            $table->bigInteger('order_id')->change();
            $table->bigInteger('user_id')->nullable()->change();
            $table->tinyInteger('language_id')->change();
            $table->bigInteger('customer_id')->change();
            $table->integer('warehouse_user_id')->nullable()->change();
        });

        Schema::table('consoleconfig', function (Blueprint $table) {
            $table->bigInteger('company_id')->nullable()->change();
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->bigInteger('product_id')->change();
            $table->bigInteger('order_id')->change();
            $table->integer('company_id')->nullable()->change();
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->bigInteger('product_id')->change();
        });

        Schema::table('countries', function (Blueprint $table) {
            $table->integer('id')->autoIncrement()->change();
        });

        Schema::table('languages', function (Blueprint $table) {
            $table->integer('language_id')->autoIncrement()->change();
        });

        Schema::table('navbars', function (Blueprint $table) {
            $table->integer('navbar_id')->autoIncrement()->change();
        });

        Schema::table('states', function (Blueprint $table) {
            $table->integer('id')->autoIncrement()->change();
            $table->integer('country_id')->change();
        });

        Schema::table('catalog_request', function (Blueprint $table) {
            $table->integer('user_id')->change();
            $table->integer('customer_id')->change();
        });

        Schema::table('changes_app', function (Blueprint $table) {
            $table->bigInteger('company_id')->change();
            $table->bigInteger('user_id')->nullable()->change();
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->integer('state_id')->change();
        });

        Schema::table('delivered_orders', function (Blueprint $table) {
            $table->bigInteger('order_id')->change();
            $table->bigInteger('product_id')->change();
        });

        Schema::table('format_date', function (Blueprint $table) {
            $table->integer('language_id')->nullable()->change();
        });

        Schema::table('keys', function (Blueprint $table) {
            $table->integer('user_id')->change();
        });

        Schema::table('session_cart', function (Blueprint $table) {
            $table->integer('company_id')->nullable()->change();
            $table->integer('customer_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyOrderCategories(): void
    {
        Schema::table('order_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->change();
            $table->unsignedBigInteger('catalog_id')->change();
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyCompanies(): void
    {
        DB::table('companies')->where('currency_code_id', '')->update(['currency_code_id' => null]);
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedBigInteger('language_id')->change();
            $table->unsignedBigInteger('createdby_comp_id')->nullable()->change();
            $table->unsignedBigInteger('currency_code_id')->nullable()->change();
            $table->unsignedBigInteger('rol_id')->nullable()->change();
        });

        if (Schema::hasColumn('companies', 'category_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->bigInteger('category_id')->nullable()->change();
            });

            Schema::table('companies', function (Blueprint $table) {
                $table->renameColumn('category_id', 'company_category_id');
            });
        }
    }

    /**
     * @return void
     */
    protected function modifyCatalogCart(): void
    {
        DB::table('catalog_cart')->where('expired_date', '0000-00-00 00:00:00')->update(['expired_date' => null]);
        Schema::table('catalog_cart', function (Blueprint $table) {
            $table->unsignedBigInteger('catalog_id')->nullable()->change();
            $table->unsignedBigInteger('customer_id')->nullable()->change();
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->unsignedBigInteger('company_id')->nullable()->change();
            $table->unsignedBigInteger('order_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyCatalogSentMail(): void
    {
        Schema::table('catalog_sent_mail', function (Blueprint $table) {
            $table->unsignedBigInteger('catalog_id')->change();
            $table->bigInteger('catlog_reqest_id')->change();
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyOrders(): void
    {
        DB::table('orders')->where('date_confirmed', '0000-00-00 00:00:00')->update(['date_confirmed' => null]);
        DB::table('orders')->where('send_email_order', '0000-00-00 00:00:00')->update(['send_email_order' => null]);
        DB::table('orders')->where('date_rejected_email', '0000-00-00 00:00:00')->update(['date_rejected_email' => null]
        );
        DB::table('orders')->where('date_expired_email', '0000-00-00 00:00:00')->update(['date_expired_email' => null]);
        Schema::table('orders', function (Blueprint $table) {
            $table->bigIncrements('order_id')->change();
            $table->bigInteger('catalog_id')->nullable()->change();
            $table->bigInteger('cart_id')->nullable()->change();
            $table->unsignedBigInteger('company_id')->nullable()->change();
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->renameColumn('user_id', 'seller_id');
            $table->unsignedBigInteger('language_id')->change();
            $table->unsignedBigInteger('customer_id')->change();
            $table->unsignedBigInteger('warehouse_user_id')->nullable()->change();
            $table->unsignedBigInteger('catalog_id')->nullable()->change();
            $table->double('discount')->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyDetailCar(): void
    {
        Schema::table('detail_car', function (Blueprint $table) {
            $table->bigInteger('cart_id')->nullable()->change();
            $table->unsignedBigInteger('company_id')->nullable()->change();
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->unsignedBigInteger('customer_id')->nullable()->change();
            $table->string('session_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyCatalog(): void
    {
        Schema::table('catalog', function (Blueprint $table) {
            $table->bigIncrements('catalog_id')->change();
            $table->unsignedBigInteger('company_id')->change();
            //WIP: dropeamos esta columna? al ya tener la tabla pivote
            //$table->unsignedBigInteger('cat_id')->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyCategories(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->bigIncrements('cat_id')->change();
            $table->unsignedBigInteger('company_id')->change();
            $table->unsignedBigInteger('language_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyCompanyContacts(): void
    {
        Schema::table('company_contacts', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyCompanyStatusOrder(): void
    {
        Schema::table('company_status_order', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyCurrency(): void
    {
        Schema::table('currency', function (Blueprint $table) {
            $table->bigIncrements('currency_id')->change();
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyCustomerGroups(): void
    {
        Schema::table('customer_groups', function (Blueprint $table) {
            $table->bigIncrements('group_id')->change();
            $table->unsignedBigInteger('company_id')->nullable()->change();
            $table->unsignedBigInteger('language_id')->nullable()->change();
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyCustomers(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->bigIncrements('customer_id')->change();
            $table->unsignedBigInteger('company_id')->change();
            $table->unsignedBigInteger('language_id')->nullable()->change();
            //WIP: dropeamos esta columna? al ya tener la tabla pivote
            //$table->unsignedBigInteger('user_id')->nullable()->change();
            $table->unsignedBigInteger('term_id')->nullable()->change();
            $table->unsignedBigInteger('group_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyImportHistory(): void
    {
        Schema::table('import_history', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyInventory(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->change();
            $table->unsignedBigInteger('product_id')->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyLogs(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->change();
            $table->unsignedBigInteger('user_id')->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyNavbarRolId(): void
    {
        Schema::table('navbar_rol_id', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
            $table->unsignedBigInteger('navbar_id')->nullable()->change();
            $table->unsignedBigInteger('rol_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyOrderStatusHistory(): void
    {
        Schema::table('order_status_history', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
            $table->unsignedBigInteger('order_id')->nullable()->change();
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyProductImages(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->unsignedBigInteger('language_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyProducts(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->bigIncrements('pro_id')->change();
            $table->unsignedBigInteger('company_id')->nullable()->change();
            $table->unsignedBigInteger('language_id')->change();
            $table->unsignedBigInteger('warehouse_user_id')->nullable()->change();
            //WIP: Dropeamos la columna? ya tenemos la tabla pivote
            //$table->unsignedBigInteger('category_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyRol(): void
    {
        Schema::table('rol', function (Blueprint $table) {
            $table->bigIncrements('rol_id')->change();
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifySatusRol(): void
    {
        Schema::table('status_rol', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
            $table->unsignedBigInteger('rol_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyTermOfSales(): void
    {
        Schema::table('terms_of_sales', function (Blueprint $table) {
            $table->bigIncrements('term_id')->change();
            $table->unsignedBigInteger('company_id')->nullable()->change();
            $table->unsignedBigInteger('language_id')->nullable()->change();
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyUnits(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyUserProfiles(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifySellers(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyWharehouseUsers(): void
    {
//fix error "Invalid datetime format: 1292 Incorrect datetime value: '0000-00-00 00:00:00' for columns
        DB::table('warehouse_users')->where('created', '0000-00-00 00:00:00')->update(['created' => null]);
        Schema::table('warehouse_users', function (Blueprint $table) {
            $table->dateTime('deleted_at')->nullable()->change();
        });
        //mariadb doesn't accept '0000-00-00 00:00:00' as a "null" value
        DB::table('warehouse_users')->where('deleted_at', '0000-00-00 00:00:00')->update(['deleted_at' => null]);

        Schema::table('warehouse_users', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyWordersList(): void
    {
        Schema::table('wordersList', function (Blueprint $table) {
            $table->timestamp('updated')->nullable()->useCurrentOnUpdate()->change();
            $table->unsignedBigInteger('company_id')->nullable()->change();
            $table->unsignedBigInteger('order_id')->change();
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->unsignedBigInteger('language_id')->change();
            $table->unsignedBigInteger('customer_id')->change();
            $table->unsignedBigInteger('warehouse_user_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyConfig(): void
    {
        Schema::table('consoleconfig', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyOrderDetails(): void
    {
        DB::table('order_details')->where('delivered_date', '0000-00-00 00:00:00')->update(['delivered_date' => null]);
        Schema::table('order_details', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->change();
            $table->unsignedBigInteger('order_id')->change();
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyCountries(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->bigIncrements('id')->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyLanguages(): void
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->bigIncrements('language_id')->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyNavbars(): void
    {
        Schema::table('navbars', function (Blueprint $table) {
            $table->bigIncrements('navbar_id')->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyStates(): void
    {
        Schema::table('states', function (Blueprint $table) {
            $table->bigIncrements('id')->change();
            $table->unsignedBigInteger('country_id')->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyCatalogRequest(): void
    {
        Schema::table('catalog_request', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->change();
            $table->unsignedBigInteger('customer_id')->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyChangesApp(): void
    {
        Schema::table('changes_app', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->change();
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyCities(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->unsignedBigInteger('state_id')->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyDeliveredOrders(): void
    {
        Schema::table('delivered_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->change();
            $table->unsignedBigInteger('product_id')->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyFormatDate(): void
    {
        Schema::table('format_date', function (Blueprint $table) {
            $table->unsignedBigInteger('language_id')->nullable()->change();
        });
    }

    /**
     * @return void
     */
    protected function modifyKeys(): void
    {
        Schema::table('keys', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->change();
        });
    }

    /**
     * @return void
     */
    protected function modifySessionCart(): void
    {
        Schema::table('session_cart', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
            $table->unsignedBigInteger('customer_id')->nullable()->change();
        });
    }
};
