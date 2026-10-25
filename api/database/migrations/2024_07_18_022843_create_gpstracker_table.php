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
        Schema::create('gpstracker', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('company_id')->default(0);
            $table->bigInteger('user_id')->default(0);
            $table->float('longitude')->default(0);
            $table->float('latitude')->default(0);
            $table->timestamp('created')->useCurrent();
            $table->bigInteger('login')->nullable();
            $table->string('address', 600)->nullable();
            $table->text('deviceinfo')->nullable()->default('-');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gpstracker');
    }
};
