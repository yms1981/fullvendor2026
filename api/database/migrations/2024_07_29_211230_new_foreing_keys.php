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
        Schema::withoutForeignKeyConstraints(function () {
            Schema::table('companies', function (Blueprint $table) {
                $table->foreign('company_category_id')->references('category_id')->on('companies_categories');
                $table->foreign('language_id')->references('language_id')->on('languages');
                $table->foreign('createdby_comp_id')->references('company_id')->on('companies');
                $table->foreign('currency_code_id')->references('currency_id')->on('currency');
                $table->foreign('rol_id')->references('rol_id')->on('rol');
            });

            Schema::table('order_categories', function (Blueprint $table) {
                $table->foreign('category_id')->references('cat_id')->on('categories');
                $table->foreign('company_id')->references('company_id')->on('companies');
                $table->foreign('catalog_id')->references('catalog_id')->on('catalog');
            });

            Schema::table('catalog_cart', function (Blueprint $table) {
                $table->foreign('catalog_id')->references('catalog_id')->on('catalog');
                $table->foreign('customer_id')->references('customer_id')->on('customers');
                $table->foreign('product_id')->references('pro_id')->on('products');
                $table->foreign('user_id')->references('id')->on('users');
                $table->foreign('company_id')->references('company_id')->on('companies');
                $table->foreign('order_id')->references('order_id')->on('orders');
            });

            Schema::table('catalog_sent_mail', function (Blueprint $table) {
                $table->foreign('catalog_id')->references('catalog_id')->on('catalog');
                $table->foreign('catlog_reqest_id')->references('request_id')->on('catalog_request');
                $table->foreign('company_id')->references('company_id')->on('companies');

            });

            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('catalog_id')->references('catalog_id')->on('catalog');
                $table->foreign('cart_id')->references('cart_id')->on('catalog_cart');
                $table->foreign('company_id')->references('company_id')->on('companies');
                $table->foreign('seller_id')->references('id')->on('sellers')->onDelete('cascade');
                $table->foreign('language_id')->references('language_id')->on('languages');
                $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
                $table->foreign('warehouse_user_id')->references('user_id')->on('warehouse_users');
            });

            Schema::table('detail_car', function (Blueprint $table) {
                $table->foreign('cart_id')->references('cart_id')->on('catalog_cart');
                $table->foreign('company_id')->references('company_id')->on('companies');
                $table->foreign('product_id')->references('pro_id')->on('products');
                $table->foreign('customer_id')->references('customer_id')->on('customers');
                //WIP: Tienen diferente cotejamiento, que hacemos?
                //$table->foreign('session_id')->references('id')->on('sessions');
            });

            Schema::table('product_images', function (Blueprint $table) {
                $table->foreign('product_id')->references('pro_id')->on('products');
            });

    //        cart → products
    //        Schema::table('', function (Blueprint $table) {
    //            $table->foreign('cart_id')->references('cart_id')->on('catalog_cart');
    //        });

            Schema::table('order_details', function (Blueprint $table) {
                $table->foreign('product_id')->references('pro_id')->on('products');
            });

            Schema::table('inventory', function (Blueprint $table) {
                $table->foreign('product_id')->references('pro_id')->on('products');
                $table->foreign('company_id')->references('company_id')->on('companies');
            });

            Schema::table('catalog', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
                //WIP: drop column? pivot table already created
                //$table->foreign('cat_id')->references('cat_id')->on('categories');
            });

            Schema::table('categories', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
                $table->foreign('language_id')->references('language_id')->on('languages');
            });

            Schema::table('company_contacts', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
            });

            Schema::table('company_status_order', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
            });

            Schema::table('consoleconfig', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
            });

            Schema::table('currency', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
            });

            Schema::table('customer_groups', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
            });

            Schema::table('customers', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
                $table->foreign('language_id')->references('language_id')->on('languages');
                //WIP: create pivot table & drop column?
                //$table->foreign('user_id')->references('id')->on('users');
                $table->foreign('term_id')->references('term_id')->on('terms_of_sales');
                $table->foreign('group_id')->references('group_id')->on('customer_groups');
            });

            Schema::table('import_history', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
            });

            Schema::table('logs', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });

            Schema::table('navbar_rol_id', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
                $table->foreign('navbar_id')->references('navbar_id')->on('navbars');
                $table->foreign('rol_id')->references('rol_id')->on('rol');
            });

            Schema::table('order_status_history', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
                $table->foreign('order_id')->references('order_id')->on('orders')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });

            Schema::table('products', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
                $table->foreign('language_id')->references('language_id')->on('languages');
                $table->foreign('warehouse_user_id')->references('user_id')->on('warehouse_users');
                //WIP: drop column? pivot table already created
                //$table->foreign('category_id')->references('cat_id')->on('categories');
            });

            Schema::table('rol', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
            });

            Schema::table('status_rol', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
                $table->foreign('rol_id')->references('rol_id')->on('rol');
            });

            Schema::table('terms_of_sales', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
                $table->foreign('language_id')->references('language_id')->on('languages');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });

            Schema::table('units', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
            });

            Schema::table('user_profiles', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
            });

            Schema::table('sellers', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
            });

            Schema::table('warehouse_users', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
            });

            Schema::table('wordersList', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
                $table->foreign('order_id')->references('order_id')->on('orders')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('language_id')->references('language_id')->on('languages');
                $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
                $table->foreign('warehouse_user_id')->references('user_id')->on('warehouse_users');
            });

            Schema::table('catalog_request', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
            });

            Schema::table('changes_app', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
                $table->foreign('user_id')->references('id')->on('users');
            });

            Schema::table('cities', function (Blueprint $table) {
                $table->foreign('state_id')->references('id')->on('states');
            });

            Schema::table('customer_groups', function (Blueprint $table) {
                $table->foreign('language_id')->references('language_id')->on('languages');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });

            Schema::table('delivered_orders', function (Blueprint $table) {
                $table->foreign('order_id')->references('order_id')->on('orders')->onDelete('cascade');
                $table->foreign('product_id')->references('pro_id')->on('products');
            });

            Schema::table('format_date', function (Blueprint $table) {
                $table->foreign('language_id')->references('language_id')->on('languages');
            });

            Schema::table('keys', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });

            Schema::table('session_cart', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
                $table->foreign('customer_id')->references('customer_id')->on('customers');
            });

            Schema::table('states', function (Blueprint $table) {
                $table->foreign('country_id')->references('id')->on('countries');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign('company_category_id');
            $table->dropForeign('language_id');
            $table->dropForeign('createdby_comp_id');
            $table->dropForeign('currency_code_id');
            $table->dropForeign('rol_id');
        });

        Schema::table('order_categories', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['company_id']);
            $table->dropForeign(['catalog_id']);
        });

        Schema::table('catalog_cart', function (Blueprint $table) {
            $table->dropForeign(['catalog_id']);
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['product_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['company_id']);
            $table->dropForeign(['order_id']);
        });

        Schema::table('catalog_sent_mail', function (Blueprint $table) {
            $table->dropForeign(['catalog_id']);
            $table->dropForeign(['catlog_reqest_id']);
            $table->dropForeign(['company_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['catalog_id']);
            $table->dropForeign(['cart_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['language_id']);
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['warehouse_user_id']);
        });

        Schema::table('detail_car', function (Blueprint $table) {
            $table->dropForeign(['cart_id']);
            $table->dropForeign(['company_id']);
            $table->dropForeign(['product_id']);
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['session_id']);
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['company_id']);
        });

        Schema::table('catalog', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['cat_id']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['language_id']);
        });

        Schema::table('company_contacts', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('company_status_order', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('consoleconfig', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('currency', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('customer_groups', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['language_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['term_id']);
            $table->dropForeign(['group_id']);
        });

        Schema::table('import_history', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('logs', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('navbar_rol_id', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['navbar_id']);
            $table->dropForeign(['rol_id']);
        });

        Schema::table('order_status_history', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['order_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['language_id']);
            $table->dropForeign(['warehouse_user_id']);
            $table->dropForeign(['category_id']);
        });

        Schema::table('rol', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('status_rol', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['rol_id']);
        });

        Schema::table('terms_of_sales', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['language_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('sellers', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('warehouse_users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('wordersList', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['order_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['language_id']);
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['warehouse_user_id']);
        });

        Schema::table('catalog_request', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['customer_id']);
        });

        Schema::table('changes_app', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->dropForeign(['state_id']);
        });

        Schema::table('customer_groups', function (Blueprint $table) {
            $table->dropForeign(['language_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('delivered_orders', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['product_id']);
        });

        Schema::table('format_date', function (Blueprint $table) {
            $table->dropForeign(['language_id']);
        });

        Schema::table('keys', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('session_cart', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['customer_id']);
        });

        Schema::table('states', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
        });

    }

};
