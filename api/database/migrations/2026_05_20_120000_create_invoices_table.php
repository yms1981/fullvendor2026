<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		if (Schema::hasTable('invoices')) {
			return;
		}

		Schema::create('invoices', function (Blueprint $table) {
			$table->bigIncrements('invoice_id');
			$table->string('doc_number', 50)->nullable();
			$table->unsignedBigInteger('subscription_id');
			$table->unsignedBigInteger('company_id');
			$table->dateTime('date')->nullable();
			$table->dateTime('date_expired')->nullable();
			$table->integer('paid')->nullable();
			$table->unsignedTinyInteger('days')->nullable();
			$table->string('issuing_entity', 50)->nullable();
			$table->text('address_entity')->nullable();
			$table->string('country_entity', 50)->nullable();
			$table->string('state_entity', 50)->nullable();
			$table->string('city_entity', 50)->nullable();
			$table->text('concept')->nullable();
			$table->decimal('invoice_paid', 12, 2)->nullable();
			$table->decimal('debt', 12, 2)->nullable();
			$table->string('stripe_id', 255)->nullable();
			$table->string('stripe_doc', 512)->nullable();
			$table->string('stripe_url', 1024)->nullable();
			$table->string('stripe_pdf', 1024)->nullable();
			$table->string('stripe_status', 64)->nullable();
			$table->dateTime('created_at')->nullable();
			$table->dateTime('updated_at')->nullable();

			$table->index('company_id', 'invoices_company_id');
			$table->index('subscription_id', 'invoices_subscription_id');
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('invoices');
	}
};
