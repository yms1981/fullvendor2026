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
        Schema::create('companies', function (Blueprint $table) {
            $table->bigIncrements('company_id');
            $table->integer('category_id')->nullable();
            $table->integer('language_id');
            $table->string('name')->nullable()->default(' ');
            $table->string('short_name')->nullable()->default('');
            $table->string('email')->nullable()->default('');
            $table->string('password')->nullable()->default('');
            $table->string('phone_number')->nullable()->default('');
            $table->string('cell_number')->nullable()->default('');
            $table->string('fax')->nullable()->default('');
            $table->string('image')->nullable()->default('');
            $table->text('address')->nullable();
            $table->string('country')->nullable()->default('');
            $table->string('state')->nullable()->default('');
            $table->string('city')->nullable()->default('');
            $table->string('zip_code')->nullable()->default('');
            $table->string('tax_condition')->nullable()->default('');
            $table->string('tax_id')->nullable()->default('');
            $table->float('max_number_of_clients')->nullable()->default(0);
            $table->float('max_number_of_orders')->nullable()->default(0);
            $table->float('max_number_of_vendors')->nullable()->default(0);
            $table->double('total_price')->nullable()->default(0);
            $table->string('website')->nullable()->default('');
            $table->dateTime('created')->nullable();
            $table->smallInteger('status')->nullable()->default(1)->comment('0=InActive 1= Active
');
            $table->string('slug_url')->nullable()->default(' ');
            $table->string('currency')->nullable()->default('');
            $table->integer('createdby_comp_id');
            $table->string('currency_code_id')->nullable()->default('');
            $table->text('token')->nullable();
            $table->integer('ordersend_time')->nullable();
            $table->integer('catalogsend_time')->nullable();
            $table->integer('format_id')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->integer('sell_with_inventory')->nullable()->default(1);
            $table->integer('rol_id')->nullable();
            $table->integer('show_inventory')->default(0);
            $table->integer('show_semaforo_stock')->nullable()->default(0);
            $table->string('sellers_ids', 250)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
