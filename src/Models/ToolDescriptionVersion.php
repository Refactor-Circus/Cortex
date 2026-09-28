<?php

declare(strict_types=1);

namespace JayI\Cortex\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use JayI\Cortex\Database\Factories\ToolDescriptionVersionFactory;
use JayI\Cortex\Models\Concerns\DispatchesModelEvents;
use LogicException;

/**
 * @property string $id
 * @property string $tool_description_id
 * @property int $version
 * @property string $content
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ToolDescriptionVersion extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<ToolDescriptionVersionFactory> */
    use HasFactory;

    use HasUlids;

    protected $table = 'cortex_tool_description_versions';

    protected $fillable = [
        'version',
        'content',
    ];

    protected static function booted(): void
    {
        self::updating(function (): never {
            throw new LogicException('Tool description versions are immutable. Create a new version instead.');
        });
    }

    /**
     * @return BelongsTo<ToolDescription, $this>
     */
    public function toolDescription(): BelongsTo
    {
        return $this->belongsTo(ToolDescription::class, 'tool_description_id');
    }

    protected static function newFactory(): ToolDescriptionVersionFactory
    {
        return ToolDescriptionVersionFactory::new();
    }
}
