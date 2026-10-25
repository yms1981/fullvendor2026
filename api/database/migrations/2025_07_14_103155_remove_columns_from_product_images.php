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
        Schema::table('product_images', function (Blueprint $table) {
            //
            $table->dropColumn('language_id');
            $table->dropColumn('images');
            $table->dropColumn('Img');
            $table->dropColumn('sImgURL');
            $table->dropColumn('ImgURL');
            $table->dropColumn('id_kor');
            $table->dropColumn('Description');
            $table->dropColumn('sku');
            $table->dropColumn('img_default');
            $table->dropColumn('imageBlob');
            $table->dropColumn('image_status');
            $table->dropColumn('comprimed');
            $table->dropColumn('company_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignIdFor(\App\Models\Image::class,'default_image_id')->nullable()->after('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->integer('language_id')->nullable()->default(1);
            $table->string('images')->nullable();
            $table->binary('Img')->nullable();
            $table->longText('sImgURL')->nullable();
            $table->longText('ImgURL')->nullable();
            $table->bigInteger('id_kor')->nullable()->default(0);
            $table->longText('Description')->nullable();
            $table->string('sku')->nullable();
            $table->boolean('img_default')->nullable();
            $table->binary('imageBlob')->nullable();
            $table->boolean('image_status')->nullable()->default(true);
            $table->integer('comprimed')->nullable()->default(0);
            $table->integer('company_id')->default(0)->comment('PK of companies');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['default_image_id']);
        });
    }
};
