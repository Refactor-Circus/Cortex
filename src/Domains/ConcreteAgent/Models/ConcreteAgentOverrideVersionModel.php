<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Cortex\Database\Factories\ConcreteAgentOverrideVersionFactory;
use RefactorCircus\Cortex\Exceptions\ImmutableVersionException;
use RefactorCircus\Cortex\Support\Tables;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;

/**
 * One immutable version of a concrete agent's prompt override.
 *
 * @property string $id
 * @property string $concrete_agent_override_id
 * @property int $version
 * @property string $content
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ConcreteAgentOverrideVersionModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<ConcreteAgentOverrideVersionFactory> */
    use HasFactory;

    use HasUlids;

    protected $fillable = [
        'version',
        'content',
    ];

    public function getTable(): string
    {
        return Tables::name('concrete_agent_override_versions');
    }

    protected static function booted(): void
    {
        self::updating(function (): never {
            throw ImmutableVersionException::for('Concrete agent prompt');
        });
    }

    /**
     * @return BelongsTo<ConcreteAgentOverrideModel, $this>
     */
    public function concreteAgentOverride(): BelongsTo
    {
        return $this->belongsTo(ConcreteAgentOverrideModel::class, 'concrete_agent_override_id');
    }

    protected static function newFactory(): ConcreteAgentOverrideVersionFactory
    {
        return ConcreteAgentOverrideVersionFactory::new();
    }
}
