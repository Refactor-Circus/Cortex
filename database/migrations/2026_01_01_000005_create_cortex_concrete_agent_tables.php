<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cortex_concrete_agent_overrides', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('agent')->unique();
            // No FK: circular reference with cortex_concrete_agent_override_versions;
            // integrity is enforced when publishing.
            $table->ulid('published_version_id')->nullable()->index();
            // Null keeps the toolset the class declares in code.
            $table->json('tools')->nullable();
            $table->timestamps();
        });

        Schema::create('cortex_concrete_agent_override_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('concrete_agent_override_id')
                ->constrained('cortex_concrete_agent_overrides', indexName: 'cortex_concrete_agent_override_versions_override_foreign')
                ->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->longText('content');
            $table->timestamps();

            $table->unique(['concrete_agent_override_id', 'version'], 'cortex_concrete_agent_override_versions_override_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cortex_concrete_agent_override_versions');
        Schema::dropIfExists('cortex_concrete_agent_overrides');
    }
};
