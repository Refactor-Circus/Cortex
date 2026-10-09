<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use RefactorCircus\Cortex\Database\Factories\ConcreteAgentOverrideFactory;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;

/**
 * Overrides for a registered concrete (class-based) agent, keyed by the
 * agent's registered name. The published version's content replaces the
 * prompt the class declares in code, and a non-null `tools` list replaces
 * its code-declared toolset.
 *
 * @property string $id
 * @property string $agent
 * @property string|null $published_version_id
 * @property list<string>|null $tools
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ConcreteAgentOverrideModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<ConcreteAgentOverrideFactory> */
    use HasFactory;

    use HasUlids;

    protected $table = 'cortex_concrete_agent_overrides';

    protected $fillable = [
        'agent',
        'tools',
    ];

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
     * @return HasMany<ConcreteAgentOverrideVersionModel, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ConcreteAgentOverrideVersionModel::class, 'concrete_agent_override_id');
    }

    /**
     * @return BelongsTo<ConcreteAgentOverrideVersionModel, $this>
     */
    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(ConcreteAgentOverrideVersionModel::class, 'published_version_id');
    }

    protected static function newFactory(): ConcreteAgentOverrideFactory
    {
        return ConcreteAgentOverrideFactory::new();
    }
}
