<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_pending_updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('update_type'); // core|plugin|theme
            $table->string('item_slug');
            $table->string('item_name')->nullable();
            $table->string('current_version')->nullable();
            $table->string('available_version')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('detected_at');
            $table->timestamps();

            $table->unique(['site_id', 'update_type', 'item_slug']);
            $table->index(['site_id', 'update_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_pending_updates');
    }
};
