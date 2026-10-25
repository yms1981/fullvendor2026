<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admin', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('banner', function (Blueprint $table) {
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('catalog', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
        });

        Schema::table('catalog_category', function (Blueprint $table) {
            if (!Schema::hasColumn('catalog_category', 'created_at')) {
                $table->timestamps();
            }
            if (!Schema::hasColumn('catalog_category', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('catalog_request', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('catalog_sent_mail', function (Blueprint $table) {
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });

        Schema::table('category_product', function (Blueprint $table) {
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('changes_app', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('cms', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
            if (!Schema::hasColumn('companies', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('companies_categories', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
            if (!Schema::hasColumn('companies_categories', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('company_contacts', function (Blueprint $table) {
            $table->timestamps();
            if (!Schema::hasColumn('companies_contacts', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('company_status_order', function (Blueprint $table) {
            if (!Schema::hasColumn('company_status_order', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('consoleconfig', function (Blueprint $table) {
            $table->timestamps();
            if (!Schema::hasColumn('consoleconfig', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('consoleusers', function (Blueprint $table) {
            $table->timestamps();
            if (!Schema::hasColumn('consoleusers', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('countries', function (Blueprint $table) {
            $table->timestamps();
            if (!Schema::hasColumn('countries', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('currency', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
            if (!Schema::hasColumn('currency', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('currencybycountries', function (Blueprint $table) {
            $table->timestamps();
            if (!Schema::hasColumn('currencybycountries', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('currency_formats', function (Blueprint $table) {
            $table->timestamps();
            if (!Schema::hasColumn('currency_formats', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('customer_groups', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });

        Schema::table('customer_user', function (Blueprint $table) {
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('delivered_orders', function (Blueprint $table) {
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('detail_car', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });

        Schema::table('deviceinfos', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('enquiries', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('format_date', function (Blueprint $table) {
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('format_numeric', function (Blueprint $table) {
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('import_history', function (Blueprint $table) {
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable();
            $table->softDeletes();
        });

        Schema::table('keys', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });

        Schema::table('languages', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });

        Schema::table('logs', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });

        Schema::table('mails_logs', function (Blueprint $table) {
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('navbars', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('navbar_rol_id', function (Blueprint $table) {
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('newsletter', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('order_categories', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });

        Schema::table('order_status_history', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('session_cart', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('states', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });

        Schema::table('status_cart', function (Blueprint $table) {
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('status_orders', function (Blueprint $table) {
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('status_rol', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('terms_of_sales', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });

        Schema::table('units', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
        });

        Schema::table('sellers', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('warehouse_users', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
        });

        Schema::table('wordersList', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('banner', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropTimestamps();
        });

        Schema::table('catalog', function (Blueprint $table) {
            $this->myDropColumnIfExists('catalog', 'updated_at');
        });

        Schema::table('catalog_category', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropTimestamps();
        });

        Schema::table('catalog_request', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('catalog_sent_mail', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropTimestamps();
        });

        Schema::table('categories', function (Blueprint $table) {
            $this->myDropColumnIfExists('categories', 'updated_at');
            $table->dropSoftDeletes();
        });

        Schema::table('category_product', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropTimestamps();
        });

        Schema::table('changes_app', function (Blueprint $table) {
            $this->myDropColumnIfExists('changes_app', 'updated_at');
            $table->dropSoftDeletes();
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropTimestamps();
        });

        Schema::table('cms', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('companies', function (Blueprint $table) {
            $this->myDropColumnIfExists('companies', 'updated_at');
            $table->dropSoftDeletes();
        });

        Schema::table('companies_categories', function (Blueprint $table) {
            $this->myDropColumnIfExists('companies_categories', 'updated_at');
            $table->dropSoftDeletes();
        });

        Schema::table('company_contacts', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropTimestamps();
        });

        Schema::table('company_status_order', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('consoleconfig', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropTimestamps();
        });

        Schema::table('consoleusers', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropTimestamps();
        });

        Schema::table('countries', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropTimestamps();
        });

        Schema::table('currency', function (Blueprint $table) {
            $this->myDropColumnIfExists('currency', 'updated_at');
            $table->dropSoftDeletes();
        });

        Schema::table('currencybycountries', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropTimestamps();
        });

        Schema::table('currency_formats', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropTimestamps();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('customer_groups', function (Blueprint $table) {
            $this->myDropColumnIfExists('customer_groups', 'updated_at');
            $table->dropSoftDeletes();
        });

        Schema::table('customer_user', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropTimestamps();
        });

        Schema::table('delivered_orders', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropTimestamps();
        });

        Schema::table('detail_car', function (Blueprint $table) {
            $this->myDropColumnIfExists('detail_car', 'updated_at');
            $table->dropSoftDeletes();
        });

        Schema::table('deviceinfos', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('format_date', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropTimestamps();
        });

        Schema::table('format_numeric', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropTimestamps();
        });

        Schema::table('import_history', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropTimestamps();
        });

        Schema::table('inventory', function (Blueprint $table) {
            $this->myDropColumnIfExists('inventory', 'created_at');
            $table->dropSoftDeletes();
        });

        Schema::table('keys', function (Blueprint $table) {
            $this->myDropColumnIfExists('keys', 'updated_at');
            $table->dropSoftDeletes();
        });

        Schema::table('languages', function (Blueprint $table) {
            $this->myDropColumnIfExists('languages', 'updated_at');
            $table->dropSoftDeletes();
        });

        Schema::table('logs', function (Blueprint $table) {
            $this->myDropColumnIfExists('logs', 'updated_at');
            $table->dropSoftDeletes();
        });

        Schema::table('mails_logs', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropTimestamps();
        });

        Schema::table('navbars', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('navbar_rol_id', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropTimestamps();
        });

        Schema::table('newsletter', function (Blueprint $table) {
            $this->myDropColumnIfExists('newsletter', 'updated_at');
            $table->dropSoftDeletes();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('order_categories', function (Blueprint $table) {
            $this->myDropColumnIfExists('order_categories', 'updated_at');
            $table->dropSoftDeletes();
        });

        Schema::table('order_details', function (Blueprint $table) {
            $this->myDropColumnIfExists('order_details', 'updated_at');
            $table->dropSoftDeletes();
        });

        Schema::table('order_status_history', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('products', function (Blueprint $table) {
            $this->myDropColumnIfExists('products', 'updated_at');
            $table->dropSoftDeletes();
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropTimestamps();
        });

        Schema::table('session_cart', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropTimestamps();
        });

        Schema::table('states', function (Blueprint $table) {
            $this->myDropColumnIfExists('states', 'updated_at');
            $table->dropSoftDeletes();
        });

        Schema::table('status_cart', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropTimestamps();
        });

        Schema::table('status_orders', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropTimestamps();
        });

        Schema::table('status_rol', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('terms_of_sales', function (Blueprint $table) {
            $this->myDropColumnIfExists('terms_of_sales', 'updated_at');
            $table->dropSoftDeletes();
        });

        Schema::table('units', function (Blueprint $table) {
            $this->myDropColumnIfExists('units', 'updated_at');
            $table->dropSoftDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $this->myDropColumnIfExists('user_profiles', 'updated_at');
        });

        Schema::table('sellers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('warehouse_users', function (Blueprint $table) {
            $this->myDropColumnIfExists('warehouse_users', 'updated_at');
        });

        Schema::table('wordersList', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }

    public function myDropColumnIfExists($tableName, $columnName): void
    {
        if (Schema::hasColumn($tableName, $columnName)) {
            Schema::table($tableName, function (Blueprint $table) use ($columnName) {
                $table->dropColumn($columnName);
            });
        }
    }
};
