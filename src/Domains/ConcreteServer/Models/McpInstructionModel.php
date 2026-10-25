<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use RefactorCircus\Cortex\Database\Factories\McpInstructionFactory;
use RefactorCircus\Cortex\Support\Tables;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;

/**
 * The overrides for a registered MCP server, keyed by the server's
 * registered name. The published version's content replaces the
 * instructions the server class declares in code, and a non-null `tools`
 * list replaces its code-declared tools (an empty list serves none).
 *
 * @property string $id
 * @property string $server
 * @property string|null $published_version_id
 * @property list<string>|null $tools
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class McpInstructionModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<McpInstructionFactory> */
    use HasFactory;

    use HasUlids;

    protected $fillable = [
        'server',
        'tools',
    ];

    public function getTable(): string
    {
        return Tables::name('mcp_instructions');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tools' => 'array',
        ];
    }

    /**
     * @return HasMany<McpInstructionVersionModel, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(McpInstructionVersionModel::class, 'mcp_instruction_id');
    }

    /**
     * @return BelongsTo<McpInstructionVersionModel, $this>
     */
    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(McpInstructionVersionModel::class, 'published_version_id');
    }

    protected static function newFactory(): McpInstructionFactory
    {
        return McpInstructionFactory::new();
    }
}
