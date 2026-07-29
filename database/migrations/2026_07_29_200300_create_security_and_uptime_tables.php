<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_scans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('scan_type'); // malware|vulnerability|integrity
            $table->string('status')->default('completed');
            $table->unsignedSmallInteger('score')->nullable();
            $table->json('findings')->nullable();
            $table->timestamp('scanned_at');
            $table->timestamps();

            $table->index(['site_id', 'scanned_at']);
        });

        Schema::create('security_login_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('username')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('success')->default(false);
            $table->timestamp('attempted_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['site_id', 'attempted_at']);
        });

        Schema::create('uptime_checks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->boolean('is_up')->default(true);
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->json('performance')->nullable();
            $table->timestamp('checked_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['site_id', 'checked_at']);
        });

        Schema::create('incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('type'); // downtime|performance|security
            $table->string('status')->default('open'); // open|resolved
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('uptime_checks');
        Schema::dropIfExists('security_login_attempts');
        Schema::dropIfExists('security_scans');
    }
};
