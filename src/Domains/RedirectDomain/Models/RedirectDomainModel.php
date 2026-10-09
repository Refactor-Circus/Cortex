<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Cortex\Database\Factories\RedirectDomainFactory;
use RefactorCircus\Foundation\Models\Concerns\DispatchesModelEvents;

/**
 * An origin MCP clients may register OAuth redirect URIs on, beside the
 * ones in `mcp.redirect_domains`. A domain belongs to an owner (an
 * organization, a user) or, without one, to the whole application; either
 * way every client may register on it, since registration is anonymous.
 *
 * @property string $id
 * @property string $domain
 * @property string|null $owner_type
 * @property string|null $owner_id
 * @property Model|null $owner
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class RedirectDomainModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<RedirectDomainFactory> */
    use HasFactory;

    use HasUlids;

    protected $table = 'cortex_redirect_domains';

    protected $fillable = [
        'domain',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo('owner');
    }

    /**
     * Domains of one owner, or those of no owner when null.
     *
     * @param  Builder<self>  $query
     */
    protected function scopeOwnedBy(Builder $query, ?Model $owner): void
    {
        if ($owner === null) {
            $query->whereNull('owner_type')->whereNull('owner_id');

            return;
        }

        $query->where('owner_type', $owner->getMorphClass())->where('owner_id', (string) $owner->getKey());
    }

    protected static function newFactory(): RedirectDomainFactory
    {
        return RedirectDomainFactory::new();
    }
}
