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
        Schema::create(Tables::name('tool_descriptions'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tool')->unique();
            // No FK: circular reference with cortex_tool_description_versions;
            // integrity is enforced when publishing.
            $table->ulid('published_version_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create(Tables::name('tool_description_versions'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tool_description_id')->constrained(Tables::name('tool_descriptions'))->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->text('content');
            $table->timestamps();

            $table->unique(['tool_description_id', 'version'], 'cortex_tool_description_versions_description_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::name('tool_description_versions'));
        Schema::dropIfExists(Tables::name('tool_descriptions'));
    }
};
