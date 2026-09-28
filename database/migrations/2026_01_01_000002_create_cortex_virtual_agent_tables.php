<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cortex_virtual_agents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            // No FK: circular reference with cortex_virtual_agent_versions;
            // integrity is enforced when publishing.
            $table->ulid('published_version_id')->nullable()->index();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->json('settings')->nullable();
            $table->json('tools')->nullable();
            $table->json('concrete_sub_agents')->nullable();
            $table->timestamps();
        });

        Schema::create('cortex_virtual_agent_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('virtual_agent_id')->constrained('cortex_virtual_agents')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->longText('content');
            $table->timestamps();

            $table->unique(['virtual_agent_id', 'version']);
        });

        Schema::create('cortex_virtual_agent_sub_agents', function (Blueprint $table): void {
            $table->foreignUlid('virtual_agent_id')->constrained('cortex_virtual_agents')->cascadeOnDelete();
            $table->foreignUlid('sub_agent_id')->constrained('cortex_virtual_agents')->cascadeOnDelete();

            $table->unique(['virtual_agent_id', 'sub_agent_id'], 'cortex_virtual_agent_sub_agents_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cortex_virtual_agent_sub_agents');
        Schema::dropIfExists('cortex_virtual_agent_versions');
        Schema::dropIfExists('cortex_virtual_agents');
    }
};
