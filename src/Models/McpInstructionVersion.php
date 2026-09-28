<?php

declare(strict_types=1);

namespace JayI\Cortex\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use JayI\Cortex\Database\Factories\McpInstructionVersionFactory;
use JayI\Cortex\Models\Concerns\DispatchesModelEvents;
use LogicException;

/**
 * @property string $id
 * @property string $mcp_instruction_id
 * @property int $version
 * @property string $content
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class McpInstructionVersion extends Model
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
     * @return BelongsTo<McpInstruction, $this>
     */
    public function mcpInstruction(): BelongsTo
    {
        return $this->belongsTo(McpInstruction::class, 'mcp_instruction_id');
    }

    protected static function newFactory(): McpInstructionVersionFactory
    {
        return McpInstructionVersionFactory::new();
    }
}
