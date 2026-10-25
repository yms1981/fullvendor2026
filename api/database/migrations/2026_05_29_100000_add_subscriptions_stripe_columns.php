<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alinea la tabla `subscriptions` con el objeto \Stripe\Subscription:
     * IDs, periodos, trials, cancelación, currency, collection_method y created.
     * Todas las columnas se añaden de forma idempotente.
     */
    public function up(): void
    {
        if (! Schema::hasTable('subscriptions')) {
            return;
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('subscriptions', 'stripe_subscription_id')) {
                $table->string('stripe_subscription_id', 255)->nullable()->after('notes');
            }
            if (! Schema::hasColumn('subscriptions', 'stripe_status')) {
                $table->string('stripe_status', 64)->nullable()->after('stripe_subscription_id');
            }
            if (! Schema::hasColumn('subscriptions', 'stripe_price_id')) {
                $table->string('stripe_price_id', 255)->nullable()->after('stripe_status');
            }
            if (! Schema::hasColumn('subscriptions', 'stripe_currency')) {
                $table->string('stripe_currency', 8)->nullable()->after('stripe_price_id');
            }
            if (! Schema::hasColumn('subscriptions', 'stripe_collection_method')) {
                $table->string('stripe_collection_method', 32)->nullable()->after('stripe_currency');
            }
            if (! Schema::hasColumn('subscriptions', 'stripe_cancel_at_period_end')) {
                $table->boolean('stripe_cancel_at_period_end')->default(false)->after('stripe_collection_method');
            }
            if (! Schema::hasColumn('subscriptions', 'stripe_current_period_start')) {
                $table->dateTime('stripe_current_period_start')->nullable()->after('stripe_cancel_at_period_end');
            }
            if (! Schema::hasColumn('subscriptions', 'stripe_current_period_end')) {
                $table->dateTime('stripe_current_period_end')->nullable()->after('stripe_current_period_start');
            }
            if (! Schema::hasColumn('subscriptions', 'stripe_trial_start')) {
                $table->dateTime('stripe_trial_start')->nullable()->after('stripe_current_period_end');
            }
            if (! Schema::hasColumn('subscriptions', 'stripe_trial_end')) {
                $table->dateTime('stripe_trial_end')->nullable()->after('stripe_trial_start');
            }
            if (! Schema::hasColumn('subscriptions', 'stripe_cancel_at')) {
                $table->dateTime('stripe_cancel_at')->nullable()->after('stripe_trial_end');
            }
            if (! Schema::hasColumn('subscriptions', 'stripe_canceled_at')) {
                $table->dateTime('stripe_canceled_at')->nullable()->after('stripe_cancel_at');
            }
            if (! Schema::hasColumn('subscriptions', 'stripe_ended_at')) {
                $table->dateTime('stripe_ended_at')->nullable()->after('stripe_canceled_at');
            }
            if (! Schema::hasColumn('subscriptions', 'stripe_latest_invoice_id')) {
                $table->string('stripe_latest_invoice_id', 255)->nullable()->after('stripe_ended_at');
            }
            if (! Schema::hasColumn('subscriptions', 'stripe_default_payment_method_id')) {
                $table->string('stripe_default_payment_method_id', 255)->nullable()->after('stripe_latest_invoice_id');
            }
            if (! Schema::hasColumn('subscriptions', 'stripe_created_at')) {
                $table->dateTime('stripe_created_at')->nullable()->after('stripe_default_payment_method_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('subscriptions')) {
            return;
        }

        $columns = [
            'stripe_created_at',
            'stripe_default_payment_method_id',
            'stripe_latest_invoice_id',
            'stripe_ended_at',
            'stripe_canceled_at',
            'stripe_cancel_at',
            'stripe_trial_end',
            'stripe_trial_start',
            'stripe_current_period_end',
            'stripe_current_period_start',
            'stripe_cancel_at_period_end',
            'stripe_collection_method',
            'stripe_currency',
            'stripe_price_id',
            // stripe_status y stripe_subscription_id los gestiona la migración 2026_05_24.
        ];

        Schema::table('subscriptions', function (Blueprint $table) use ($columns) {
            foreach ($columns as $col) {
                if (Schema::hasColumn('subscriptions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
