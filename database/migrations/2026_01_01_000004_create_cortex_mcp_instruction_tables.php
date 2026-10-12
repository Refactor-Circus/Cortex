<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Cortex\Support\Tables;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(Tables::name('mcp_instructions'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('server')->unique();
            // No FK: circular reference with cortex_mcp_instruction_versions;
            // integrity is enforced when publishing.
            $table->ulid('published_version_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create(Tables::name('mcp_instruction_versions'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('mcp_instruction_id')->constrained(Tables::name('mcp_instructions'))->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->text('content');
            $table->timestamps();

            $table->unique(['mcp_instruction_id', 'version'], 'cortex_mcp_instruction_versions_instruction_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::name('mcp_instruction_versions'));
        Schema::dropIfExists(Tables::name('mcp_instructions'));
    }
};
