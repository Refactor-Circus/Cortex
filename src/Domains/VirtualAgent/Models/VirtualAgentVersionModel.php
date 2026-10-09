<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;
use RefactorCircus\Cortex\Database\Factories\VirtualAgentVersionFactory;
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

    protected $table = 'cortex_virtual_agent_versions';

    protected $fillable = [
        'version',
        'content',
    ];

    protected static function booted(): void
    {
        self::updating(function (): never {
            throw new LogicException('Virtual agent prompt versions are immutable. Create a new version instead.');
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
