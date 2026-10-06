<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\Tool\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use JayI\Cortex\Database\Factories\ToolDescriptionFactory;
use JayI\Foundation\Models\Concerns\DispatchesModelEvents;

/**
 * A versioned description override for a registered tool, keyed by the
 * tool's registered name. The published version's content replaces the
 * description the tool class declares in code.
 *
 * @property string $id
 * @property string $tool
 * @property string|null $published_version_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ToolDescriptionModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<ToolDescriptionFactory> */
    use HasFactory;

    use HasUlids;

    protected $table = 'cortex_tool_descriptions';

    protected $fillable = [
        'tool',
    ];

    /**
     * @return HasMany<ToolDescriptionVersionModel, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ToolDescriptionVersionModel::class, 'tool_description_id');
    }

    /**
     * @return BelongsTo<ToolDescriptionVersionModel, $this>
     */
    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(ToolDescriptionVersionModel::class, 'published_version_id');
    }

    protected static function newFactory(): ToolDescriptionFactory
    {
        return ToolDescriptionFactory::new();
    }
}
