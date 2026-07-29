<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->timestamp('last_seen_at')->nullable()->after('status');
            $table->timestamp('connected_at')->nullable()->after('last_seen_at');
            $table->timestamp('disconnected_at')->nullable()->after('connected_at');
            $table->json('plugins_snapshot')->nullable()->after('disconnected_at');
            $table->json('themes_snapshot')->nullable()->after('plugins_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn([
                'last_seen_at',
                'connected_at',
                'disconnected_at',
                'plugins_snapshot',
                'themes_snapshot',
            ]);
        });
    }
};
