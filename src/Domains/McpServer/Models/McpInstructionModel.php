<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use JayI\Cortex\Database\Factories\McpInstructionFactory;
use JayI\Foundation\Models\Concerns\DispatchesModelEvents;

/**
 * A versioned instructions override for a registered MCP server, keyed by
 * the server's registered name. The published version's content replaces
 * the instructions the server class declares in code.
 *
 * @property string $id
 * @property string $server
 * @property string|null $published_version_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class McpInstructionModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<McpInstructionFactory> */
    use HasFactory;

    use HasUlids;

    protected $table = 'cortex_mcp_instructions';

    protected $fillable = [
        'server',
    ];

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
