<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Cortex\Database\Factories\McpInstructionVersionFactory;
use RefactorCircus\Foundation\Models\Concerns\DispatchesModelEvents;
use LogicException;

/**
 * @property string $id
 * @property string $mcp_instruction_id
 * @property int $version
 * @property string $content
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class McpInstructionVersionModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<McpInstructionVersionFactory> */
    use HasFactory;

    use HasUlids;

    protected $table = 'cortex_mcp_instruction_versions';

    protected $fillable = [
        'version',
        'content',
    ];

    protected static function booted(): void
    {
        self::updating(function (): never {
            throw new LogicException('MCP server instruction versions are immutable. Create a new version instead.');
        });
    }

    /**
     * @return BelongsTo<McpInstructionModel, $this>
     */
    public function mcpInstruction(): BelongsTo
    {
        return $this->belongsTo(McpInstructionModel::class, 'mcp_instruction_id');
    }

    protected static function newFactory(): McpInstructionVersionFactory
    {
        return McpInstructionVersionFactory::new();
    }
}
