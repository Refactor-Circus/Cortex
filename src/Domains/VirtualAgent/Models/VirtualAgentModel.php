<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use JayI\Cortex\Database\Factories\VirtualAgentFactory;
use JayI\Foundation\Models\Concerns\DispatchesModelEvents;

/**
 * An agent defined entirely in the database. Its prompt is versioned in
 * place: each version is immutable, and the published one is what runs.
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $published_version_id
 * @property string|null $provider
 * @property string|null $model
 * @property array<string, mixed>|null $settings
 * @property list<string> $tools
 * @property list<string> $concrete_sub_agents
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class VirtualAgentModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<VirtualAgentFactory> */
    use HasFactory;

    use HasUlids;

    protected $table = 'cortex_virtual_agents';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'provider',
        'model',
        'settings',
        'tools',
        'concrete_sub_agents',
    ];

    protected $attributes = [
        'tools' => '[]',
        'concrete_sub_agents' => '[]',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'tools' => 'array',
            'concrete_sub_agents' => 'array',
        ];
    }

    /**
     * @return HasMany<VirtualAgentVersionModel, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(VirtualAgentVersionModel::class, 'virtual_agent_id');
    }

    /**
     * @return BelongsTo<VirtualAgentVersionModel, $this>
     */
    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(VirtualAgentVersionModel::class, 'published_version_id');
    }

    /**
     * @return BelongsToMany<VirtualAgentModel, $this>
     */
    public function subAgents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'cortex_virtual_agent_sub_agents', 'virtual_agent_id', 'sub_agent_id');
    }

    /**
     * @return BelongsToMany<VirtualAgentModel, $this>
     */
    public function parentAgents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'cortex_virtual_agent_sub_agents', 'sub_agent_id', 'virtual_agent_id');
    }

    protected static function newFactory(): VirtualAgentFactory
    {
        return VirtualAgentFactory::new();
    }
}
