<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('plan')) {
            Schema::table('plan', function (Blueprint $table) {
                if (! Schema::hasColumn('plan', 'stripeld')) {
                    $table->string('stripeld', 255)->nullable()->after('price');
                }
                if (! Schema::hasColumn('plan', 'stripeName')) {
                    $table->string('stripeName', 255)->nullable()->after('stripeld');
                }
                if (! Schema::hasColumn('plan', 'stripe_status')) {
                    $table->string('stripe_status', 64)->nullable()->after('stripeName');
                }
            });
        }

        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (! Schema::hasColumn('subscriptions', 'stripe_subscription_id')) {
                    $table->string('stripe_subscription_id', 255)->nullable()->after('notes');
                }
                if (! Schema::hasColumn('subscriptions', 'stripe_status')) {
                    $table->string('stripe_status', 64)->nullable()->after('stripe_subscription_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('plan')) {
            Schema::table('plan', function (Blueprint $table) {
                foreach (['stripeld', 'stripeName', 'stripe_status'] as $col) {
                    if (Schema::hasColumn('plan', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                foreach (['stripe_subscription_id', 'stripe_status'] as $col) {
                    if (Schema::hasColumn('subscriptions', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
