<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		if (Schema::hasTable('issuing_entity')) {
			return;
		}

		Schema::create('issuing_entity', function (Blueprint $table) {
			$table->bigIncrements('entity_id');
			$table->string('entity_name', 100)->nullable();
			$table->text('entity_address')->nullable();
			$table->string('entity_country', 50)->nullable();
			$table->string('entity_state', 50)->nullable();
			$table->string('entity_city', 50)->nullable();
			$table->dateTime('created_at')->nullable();
			$table->dateTime('updated_at')->nullable();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('issuing_entity');
	}
};
