<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Domains\RedirectDomain\Policies\RedirectDomainPolicy;
use RuntimeException;

/**
 * The bundled redirect-domain policy, with switches a test flips: abilities
 * it denies outright, a `viewAny` that throws, and a `viewAny` that runs one
 * query, as an app's own policy might. Call `reset()` after each test.
 */
final class SwitchableRedirectDomainPolicy extends RedirectDomainPolicy
{
    /**
     * Abilities denied to everyone.
     *
     * @var list<string>
     */
    public static array $denies = [];

    public static bool $throws = false;

    public static bool $queries = false;

    public static function reset(): void
    {
        self::$denies = [];
        self::$throws = false;
        self::$queries = false;
    }

    public function viewAny(?Authenticatable $user, ?Model $owner = null): bool
    {
        if (self::$throws) {
            throw new RuntimeException('The redirect-domain policy failed.');
        }

        if (self::$queries && $owner !== null) {
            RedirectDomainModel::query()->ownedBy($owner)->exists();
        }

        return ! in_array('viewAny', self::$denies, true) && parent::viewAny($user, $owner);
    }

    public function create(?Authenticatable $user, ?Model $owner = null): bool
    {
        return ! in_array('create', self::$denies, true) && parent::create($user, $owner);
    }

    public function delete(?Authenticatable $user, RedirectDomainModel $domain): bool
    {
        return ! in_array('delete', self::$denies, true) && parent::delete($user, $domain);
    }
}
