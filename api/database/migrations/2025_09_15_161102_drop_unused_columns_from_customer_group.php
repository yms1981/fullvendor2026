<?php

use App\Enums\CustomerGroupTypes;
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
        Schema::table('customer_groups', function (Blueprint $table) {
            if (Schema::hasColumn('customer_groups', 'user_id')) {
                $table->dropForeign('customer_groups_user_id_foreign');
                $table->dropColumn('user_id');
            }

            if (Schema::hasColumn('customer_groups', 'language_id')) {
                $table->dropForeign('customer_groups_language_id_foreign');
                $table->dropColumn('language_id');
            }

            if (Schema::hasColumn('customer_groups', 'id_kor')) {
                $table->dropColumn('id_kor');
            }

            DB::table('customer_groups')
                ->where('percentage_on_price', 'Nothing')
                ->update(['percentage_on_price' => null]);

            DB::table('customer_groups')
                ->where('percentage_on_price', 'Increase on Price List %')
                ->update(['percentage_on_price' => CustomerGroupTypes::Increase->value]);

            DB::table('customer_groups')
                ->where('percentage_on_price', 'Decrease on Price List %')
                ->update(['percentage_on_price' => CustomerGroupTypes::Decrease->value]);
            $table->enum('percentage_on_price',CustomerGroupTypes::all())->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_groups', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            }

            if (!Schema::hasColumn('customer_groups', 'language_id')) {
                $table->foreignId('language_id')->nullable()->constrained('languages','language_id')->onDelete('set null');
            }

            if (!Schema::hasColumn('customer_groups', 'id_kor')) {
                $table->unsignedBigInteger('id_kor')->nullable();
            }

            $table->string('percentage_on_price')->nullable()->change();
        });
    }
};
