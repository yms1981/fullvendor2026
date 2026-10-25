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
        Schema::create('currencybycountries', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('GroupID', 300)->nullable();
            $table->string('Name', 300)->nullable();
            $table->longText('Contry')->nullable();
            $table->string('Code', 50)->nullable();
            $table->string('Symbol', 50)->nullable();
            $table->string('GroupIdspa')->nullable();
            $table->string('Namespa')->nullable();
            $table->string('default', 50)->nullable();


            if(!$this->isSqlite())
                $table->fullText(['GroupID', 'Name'], 'groupid_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencybycountries');
    }

    private function isSqlite(): bool
    {
        return 'sqlite' === Schema::connection($this->getConnection())
                ->getConnection()
                ->getPdo()
                ->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
};
