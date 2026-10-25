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
        Schema::create('images', function (Blueprint $table) {
            $table->uuid()->primary();
            //$table->rawColumn('UUID',"char(36) default uuid() not null primary key");
            $table->string('name')->index();
            $table->string('path')->index();
            $table->string('type');
            $table->string('size');
            $table->string('extension');
            $table->string('mime_type');
            $table->string('original_name');
            $table->foreignIdFor(\App\Models\Company::class,'company_id')->nullable()->index();
            $table->string('language', 100)->nullable();
            $table->string('disk', 100)->default('minio');
            $table->timestamps();
        });


        Schema::table('product_images', function (Blueprint $table) {
            $table->foreignIdFor(\App\Models\Image::class,'image_id')->nullable()->index()->constrained('images','uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
        Schema::table('product_images',function (Blueprint $table){
            $table->dropForeign(['image_id']);
            $table->dropColumn('image_id');
        });
    }
};
