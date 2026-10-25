<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Suscripciones operativas: empresa + plan (tabla plan.plan_id) + fechas y estado.
	 */
	public function up(): void
	{
		if (Schema::hasTable('subscriptions')) {
			return;
		}

		Schema::create('subscriptions', function (Blueprint $table) {
			$table->bigIncrements('subscription_row_id');
			$table->unsignedBigInteger('company_id');
			$table->unsignedBigInteger('plan_id')->comment('plan.plan_id');
			$table->unsignedTinyInteger('status')->default(1);
			$table->date('start_period_date')->nullable();
			$table->date('end_period_date')->nullable();
			$table->text('notes')->nullable();
			$table->dateTime('created_at')->nullable();
			$table->dateTime('updated_at')->nullable();

			$table->index('company_id');
			$table->index('plan_id');
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('subscriptions');
	}
};
