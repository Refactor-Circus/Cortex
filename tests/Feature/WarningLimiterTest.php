<?php

declare(strict_types=1);

use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RefactorCircus\Cortex\Support\PublicationCache;
use RefactorCircus\Cortex\Support\WarningLimiter;

beforeEach(function (): void {
    // No key left by an earlier test may suppress a warning here.
    Cache::store('array')->flush();

    $this->freezeTime();

    Log::spy();
});

/**
 * Assert the limiter wrote the warning exactly this many times so far.
 */
function expectLimitedWarnings(int $times, string $message = 'stale entry'): void
{
    Log::shouldHaveReceived('warning')->with($message, Mockery::any())->times($times);
}

function limiterWarns(string $server, string $subject, string $message = 'stale entry'): bool
{
    return app(WarningLimiter::class)->warn($server, $subject, $message, ['server' => $server, 'entry' => $subject]);
}

it('logs the first warning with its context and suppresses repeats within the window', function (): void {
    config()->set('cortex.mcp.warning_ttl', 120);

    expect(limiterWarns('echo', 'gone'))->toBeTrue()
        ->and(limiterWarns('echo', 'gone'))->toBeFalse();

    Log::shouldHaveReceived('warning')->once()->with('stale entry', ['server' => 'echo', 'entry' => 'gone']);
});

it('logs again once the configured window has passed', function (): void {
    config()->set('cortex.mcp.warning_ttl', 120);

    limiterWarns('echo', 'gone');

    $this->travel(119)->seconds();
    limiterWarns('echo', 'gone');

    expectLimitedWarnings(1);

    $this->travel(2)->seconds();
    limiterWarns('echo', 'gone');

    expectLimitedWarnings(2);
});

it('casts the configured window to an int', function (): void {
    config()->set('cortex.mcp.warning_ttl', '30');

    limiterWarns('echo', 'gone');

    $this->travel(29)->seconds();
    limiterWarns('echo', 'gone');

    expectLimitedWarnings(1);

    $this->travel(2)->seconds();
    limiterWarns('echo', 'gone');

    expectLimitedWarnings(2);
});

it('falls back to a one hour window when the key is absent', function (): void {
    config()->set('cortex.mcp', array_diff_key((array) config('cortex.mcp'), ['warning_ttl' => true]));

    expect(config()->has('cortex.mcp.warning_ttl'))->toBeFalse();

    limiterWarns('echo', 'gone');

    $this->travel(3599)->seconds();
    limiterWarns('echo', 'gone');

    expectLimitedWarnings(1);

    $this->travel(2)->seconds();
    limiterWarns('echo', 'gone');

    expectLimitedWarnings(2);
});

it('ships a one hour window in the package config', function (): void {
    $config = require dirname(__DIR__, 2).'/config/cortex.php';

    expect($config['mcp']['warning_ttl'])->toBe(3600);
});

it('clamps the window to at least one second', function (int|string $ttl): void {
    config()->set('cortex.mcp.warning_ttl', $ttl);

    // A zero or negative TTL would make Cache::add refuse every claim, so the
    // warning would never be written.
    expect(limiterWarns('echo', 'gone'))->toBeTrue()
        ->and(limiterWarns('echo', 'gone'))->toBeFalse();

    $this->travel(2)->seconds();

    expect(limiterWarns('echo', 'gone'))->toBeTrue();
})->with([0, -5, 'not a number']);

it('limits each entry and each server independently', function (): void {
    expect(limiterWarns('echo', 'first'))->toBeTrue()
        ->and(limiterWarns('echo', 'second'))->toBeTrue()
        ->and(limiterWarns('plain', 'first'))->toBeTrue()
        ->and(limiterWarns('echo', 'first'))->toBeFalse()
        ->and(limiterWarns('plain', 'first'))->toBeFalse();

    expectLimitedWarnings(3);
});

it('respects a claim another worker already holds', function (): void {
    $cache = app(PublicationCache::class);

    Cache::store('array')->add($cache->warningKey('echo', 'gone'), true, 60);

    expect(limiterWarns('echo', 'gone'))->toBeFalse()
        ->and(Cache::store('array')->has($cache->warningKey('echo', 'other')))->toBeFalse()
        ->and(limiterWarns('echo', 'other'))->toBeTrue()
        ->and(Cache::store('array')->has($cache->warningKey('echo', 'other')))->toBeTrue();

    expectLimitedWarnings(1);
});

it('bounds and prefixes its keys with the Cortex key family', function (): void {
    $cache = app(PublicationCache::class);

    $short = $cache->warningKey('echo', 'gone');
    $long = $cache->warningKey(str_repeat('s', 5000), str_repeat('e', 5000));

    expect($short)->toStartWith('{cortex.published}.')
        ->and($long)->toStartWith('{cortex.published}.')
        ->and(strlen($long))->toBe(strlen($short))
        ->and(strlen($long))->toBeLessThanOrEqual(128)
        ->and($cache->warningKey('echo', 'gone'))->toBe($short)
        ->and($cache->warningKey('echo', 'other'))->not->toBe($short)
        ->and($cache->warningKey('plain', 'gone'))->not->toBe($short)
        // The server and the subject are hashed apart, so they cannot run together.
        ->and($cache->warningKey('ab', 'c'))->not->toBe($cache->warningKey('a', 'bc'));
});

it('claims through the store the publication cache is pinned to', function (): void {
    config()->set('cache.stores.second', ['driver' => 'array']);
    config()->set('cortex.cache.store', 'second');

    limiterWarns('echo', 'gone');

    $key = app(PublicationCache::class)->warningKey('echo', 'gone');

    expect(Cache::store('second')->has($key))->toBeTrue()
        ->and(Cache::store('array')->has($key))->toBeFalse();
});

it('falls back to logging when the cache store throws', function (): void {
    $store = new class implements Store
    {
        /** @var list<string> */
        public array $calls = [];

        public function add(string $key, mixed $value, int $seconds): bool
        {
            return $this->fail('add');
        }

        public function get($key)
        {
            return $this->fail('get');
        }

        public function many(array $keys)
        {
            return $this->fail('many');
        }

        public function put($key, $value, $seconds)
        {
            return $this->fail('put');
        }

        public function putMany(array $values, $seconds)
        {
            return $this->fail('putMany');
        }

        public function increment($key, $value = 1)
        {
            return $this->fail('increment');
        }

        public function decrement($key, $value = 1)
        {
            return $this->fail('decrement');
        }

        public function forever($key, $value)
        {
            return $this->fail('forever');
        }

        public function touch($key, $seconds)
        {
            return $this->fail('touch');
        }

        public function forget($key)
        {
            return $this->fail('forget');
        }

        public function flush()
        {
            return $this->fail('flush');
        }

        public function getPrefix()
        {
            return '';
        }

        private function fail(string $method): never
        {
            $this->calls[] = $method;

            throw new RuntimeException('Cache store is down.');
        }
    };

    Cache::extend('throwing', fn () => Cache::repository($store));
    config()->set('cache.stores.throwing', ['driver' => 'throwing']);
    config()->set('cortex.cache.store', 'throwing');

    expect(limiterWarns('echo', 'gone'))->toBeTrue()
        ->and(limiterWarns('echo', 'gone'))->toBeTrue()
        // The claim is one atomic add, never a get followed by a put.
        ->and($store->calls)->toBe(['add', 'add']);

    expectLimitedWarnings(2);
});
