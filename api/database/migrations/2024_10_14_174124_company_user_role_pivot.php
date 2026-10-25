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
        Schema::create('company_user', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained('companies','company_id');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('type', config('enums.company_user_types'));
            $table->unsignedBigInteger('profileable_id')->nullable();
            $table->string('profileable_type')->nullable();
            //$table->unique(['user_id', 'company_id','type']);
            $table->primary(['user_id', 'company_id','type']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_user');
    }
};
