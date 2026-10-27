<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Cortex\Database\Factories\VirtualAgentVersionFactory;
use RefactorCircus\Cortex\Exceptions\ImmutableVersionException;
use RefactorCircus\Cortex\Support\Tables;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;

/**
 * One immutable version of a virtual agent's prompt.
 *
 * @property string $id
 * @property string $virtual_agent_id
 * @property int $version
 * @property string $content
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class VirtualAgentVersionModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<VirtualAgentVersionFactory> */
    use HasFactory;

    use HasUlids;

    protected $fillable = [
        'version',
        'content',
    ];

    public function getTable(): string
    {
        return Tables::name('virtual_agent_versions');
    }

    protected static function booted(): void
    {
        self::updating(function (): never {
            throw ImmutableVersionException::for('Virtual agent prompt');
        });
    }

    /**
     * @return BelongsTo<VirtualAgentModel, $this>
     */
    public function virtualAgent(): BelongsTo
    {
        return $this->belongsTo(VirtualAgentModel::class, 'virtual_agent_id');
    }

    protected static function newFactory(): VirtualAgentVersionFactory
    {
        return VirtualAgentVersionFactory::new();
    }
}
