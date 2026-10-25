<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subscriptions')) {
            return;
        }
        if (! Schema::hasColumn('subscriptions', 'title')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->string('title', 255)->default('')->after('language_id');
            });
        }
        if (! Schema::hasColumn('subscriptions', 'description')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->text('description')->nullable()->after('title');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('subscriptions')) {
            return;
        }
        if (Schema::hasColumn('subscriptions', 'description')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
        if (Schema::hasColumn('subscriptions', 'title')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn('title');
            });
        }
    }
};
