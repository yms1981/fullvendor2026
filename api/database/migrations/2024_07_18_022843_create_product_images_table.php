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
        Schema::create('product_images', function (Blueprint $table) {
            $table->bigInteger('img_id', true);
            $table->integer('product_id')->nullable()->comment('PK of Products');
            $table->integer('language_id')->nullable()->default(1);
            $table->string('images')->nullable();
            $table->dateTime('created')->nullable();
            $table->dateTime('updated')->useCurrent();
            $table->binary('Img')->nullable();
            $table->longText('sImgURL')->nullable();
            $table->longText('ImgURL')->nullable();
            $table->bigInteger('id_kor')->nullable()->default(0);
            $table->longText('Description')->nullable();
            $table->bigInteger('company_id')->nullable();
            $table->string('sku')->nullable();
            $table->boolean('img_default')->nullable();
            $table->bigInteger('img_order')->nullable()->default(1);
            $table->binary('imageBlob')->nullable();
            $table->boolean('image_status')->nullable()->default(true);
            $table->integer('comprimed')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
