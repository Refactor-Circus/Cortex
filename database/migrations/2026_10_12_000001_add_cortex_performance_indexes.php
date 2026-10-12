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
        // The pivot's unique index leads with virtual_agent_id, so looking up
        // an agent's parents (and the delete cascade) by sub_agent_id needs
        // its own index.
        Schema::table(Tables::name('virtual_agent_sub_agents'), function (Blueprint $table): void {
            $table->index('sub_agent_id', $this->subAgentIndex());
        });
    }

    public function down(): void
    {
        Schema::table(Tables::name('virtual_agent_sub_agents'), function (Blueprint $table): void {
            $table->dropIndex($this->subAgentIndex());
        });
    }

    private function subAgentIndex(): string
    {
        return Tables::name('virtual_agent_sub_agents').'_sub_agent_id_index';
    }
};
