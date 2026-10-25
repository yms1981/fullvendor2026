<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Planes de suscripción (admin): idioma, periodo, estado, cuotas y precio.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->bigIncrements('subscription_id');
            $table->integer('language_id');
            $table->string('title', 255)->default('');
            $table->text('description')->nullable();
            $table->string('billing_period', 20);
            $table->string('lifecycle_status', 20);
            $table->unsignedInteger('max_clients')->default(0);
            $table->unsignedInteger('max_orders')->default(0);
            $table->unsignedInteger('max_users')->default(0);
            $table->decimal('price', 12, 2)->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index('language_id');
            // Sin FK: en bases existentes `languages.language_id` puede diferir en
            // signed/unsigned respecto a integer() y provocar errno 150.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
