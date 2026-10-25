<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            //missing semaphore columns
            if (!Schema::hasColumn('companies', 'sem_red_hasta')) {
                $table->integer('sem_red_hasta')->nullable();
            }
            if (!Schema::hasColumn('companies', 'sem_amarillo_desde')) {
                $table->integer('sem_amarillo_desde')->nullable();
            }
            if (!Schema::hasColumn('companies', 'sem_amarillo_hasta')) {
                $table->integer('sem_amarillo_hasta')->nullable();
            }
            if (!Schema::hasColumn('companies', 'sem_verde_desde')) {
                $table->integer('sem_verde_desde')->nullable();
            }
            if (!Schema::hasColumn('companies', 'sem_verde_hasta')) {
                $table->integer('sem_verde_hasta')->nullable();
            }
            if (!Schema::hasColumn('companies', 'sem_red')) {
                $table->string('sem_red')->nullable()->comment("Label to replace red color");
            }
            if (!Schema::hasColumn('companies', 'sem_amarillo')) {
                $table->string('sem_amarillo')->nullable()->comment("Label to replace yellow color");
            }
            if (!Schema::hasColumn('companies', 'sem_verde')) {
                $table->string('sem_verde')->nullable()->comment("Label to replace green color");
            }

            if (!Schema::hasColumn('companies', 'publish_price_list')) {
                //missing publish price list
                $table->boolean('publish_price_list')->default(false)->after('show_semaforo_stock');
            }
            if (!Schema::hasColumn('companies', 'show_plus_taxes_label')) {
                //missing plus taxes label
                $table->boolean('show_plus_taxes_label')->after('publish_price_list');
            }
            if (!Schema::hasColumn('companies', 'plus_taxes_label')) {
                //missing plus taxes label
                $table->string('plus_taxes_label')->nullable()->comment("Customized label to replace 'Plus taxes'");
            }
            if (!Schema::hasColumn('companies', 'email_client_on_salesman_order')) {
                $table->boolean('email_client_on_salesman_order')
                    ->after('show_semaforo_stock')
                    ->default(true);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('sem_red_hasta');
            $table->dropColumn('sem_amarillo_desde');
            $table->dropColumn('sem_amarillo_hasta');
            $table->dropColumn('sem_verde_desde');
            $table->dropColumn('sem_verde_hasta');
            $table->dropColumn('sem_red');
            $table->dropColumn('sem_amarillo');
            $table->dropColumn('sem_verde');
            $table->dropColumn('publish_price_list');
            $table->dropColumn('show_plus_taxes_label');
            $table->dropColumn('plus_taxes_label');

            $table->dropColumn('email_client_on_salesman_order');
        });
    }
};
