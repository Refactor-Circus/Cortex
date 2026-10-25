<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Cortex\Support\Tables;

/**
 * The tool override of a concrete MCP server: a nullable json list of tool
 * names on its override row. Null serves the server's code tools.
 *
 * Both directions check the column first, so a host that copied this file
 * next to migrations it already published can run it again safely. Rolling
 * back drops the column and every stored tool override with it.
 */
return new class extends Migration
{
    public function up(): void
    {
        $table = Tables::name('mcp_instructions');

        if (Schema::hasColumn($table, 'tools')) {
            return;
        }

        Schema::table($table, function (Blueprint $table): void {
            $table->json('tools')->nullable()->after('published_version_id');
        });
    }

    public function down(): void
    {
        $table = Tables::name('mcp_instructions');

        if (! Schema::hasColumn($table, 'tools')) {
            return;
        }

        Schema::table($table, function (Blueprint $table): void {
            $table->dropColumn('tools');
        });
    }
};
