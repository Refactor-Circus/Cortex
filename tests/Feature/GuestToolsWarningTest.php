<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\GuestToolsWarning;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Mcp\CortexServer;
use RefactorCircus\Cortex\Tests\Fixtures\EchoServer;
use RefactorCircus\Cortex\Tests\Fixtures\PlainServer;
use RefactorCircus\Cortex\Tests\Fixtures\Policies\NoUpdateToolsMcpInstructionPolicy;
use RefactorCircus\Cortex\Tests\Fixtures\Policies\PartialUpdateToolsMcpInstructionPolicy;

/**
 * Whether the next application this test file creates boots as a web
 * request, and what that application logged.
 */
final class GuestToolsWarningWebBoot
{
    public static bool $enabled = false;

    /**
     * @var list<MessageLogged>
     */
    public static array $logged = [];
}

/**
 * Boots an application as a web request would (not in the console),
 * capturing what it logs, while `GuestToolsWarningWebBoot::$enabled` is set;
 * otherwise leaves the test environment alone.
 */
trait BootsGuestToolsWarningOnTheWeb
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        if (! GuestToolsWarningWebBoot::$enabled) {
            return;
        }

        $app['config']->set('cortex.mcp.servers', ['echo' => EchoServer::class, 'plain' => PlainServer::class]);

        (fn () => $this->isRunningInConsole = false)->call($app);

        $app['events']->listen(MessageLogged::class, function (MessageLogged $event): void {
            GuestToolsWarningWebBoot::$logged[] = $event;
        });
    }
}

uses(BootsGuestToolsWarningOnTheWeb::class);

/**
 * A host policy whose `updateTools` fails, as one that queries a table that
 * does not exist yet would.
 */
final class ThrowingUpdateToolsMcpInstructionPolicy
{
    public function updateTools(?Authenticatable $user, McpInstructionModel $instruction): bool
    {
        throw new RuntimeException('Policy table missing.');
    }
}

beforeEach(function (): void {
    GuestToolsWarning::reset();

    app(McpServerRegistry::class)->register('echo', EchoServer::class);
    app(McpServerRegistry::class)->register('plain', PlainServer::class);
});

/**
 * Make the running application look like a web request, not a console run.
 */
function guestToolsWarningOnTheWeb(): void
{
    (fn () => $this->isRunningInConsole = false)->call(app());
}

/**
 * Point `cortex.policies` and the Gate at a host policy for the overrides.
 *
 * @param  class-string  $policy
 */
function guestToolsWarningPolicy(string $policy): void
{
    config()->set('cortex.policies.'.McpInstructionModel::class, $policy);
    Gate::policy(McpInstructionModel::class, $policy);
}

function runGuestToolsWarning(): void
{
    app(GuestToolsWarning::class)->check();
}

it('logs once through the booted callback on a fresh web boot outside local', function (): void {
    GuestToolsWarningWebBoot::$enabled = true;
    GuestToolsWarningWebBoot::$logged = [];

    try {
        $app = $this->createApplication();
    } finally {
        GuestToolsWarningWebBoot::$enabled = false;

        // Hand the facades back to this test's application.
        Container::setInstance($this->app);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);
    }

    $warnings = array_values(array_filter(
        GuestToolsWarningWebBoot::$logged,
        fn (MessageLogged $event): bool => $event->level === 'warning' && str_contains($event->message, 'updateTools'),
    ));

    expect($app->runningInConsole())->toBeFalse()
        ->and($app->environment('local'))->toBeFalse()
        ->and($warnings)->toHaveCount(1)
        ->and($warnings[0]->context)->toBe(['servers' => ['echo', 'plain']]);
});

it('names every overridable server with the bundled policy', function (): void {
    guestToolsWarningOnTheWeb();
    Log::spy();

    runGuestToolsWarning();

    Log::shouldHaveReceived('warning')->once()->withArgs(
        fn (string $message, array $context): bool => str_contains($message, 'updateTools')
            && str_contains($message, 'README')
            && $context === ['servers' => ['echo', 'plain']],
    );
});

it('reads the bundled policy from the config without asking the Gate', function (): void {
    guestToolsWarningOnTheWeb();
    Log::spy();

    $asked = 0;
    Gate::before(function () use (&$asked): ?bool {
        $asked++;

        return null;
    });

    runGuestToolsWarning();

    expect($asked)->toBe(0);
    Log::shouldHaveReceived('warning')->once();
});

it('logs nothing in the local environment', function (): void {
    guestToolsWarningOnTheWeb();
    app()['env'] = 'local';
    Log::spy();

    runGuestToolsWarning();

    Log::shouldNotHaveReceived('warning');
});

it('logs nothing in the console', function (): void {
    expect(app()->runningInConsole())->toBeTrue();

    Log::spy();

    runGuestToolsWarning();

    Log::shouldNotHaveReceived('warning');
});

it('logs nothing when a host policy denies guests updateTools', function (): void {
    guestToolsWarningOnTheWeb();
    guestToolsWarningPolicy(NoUpdateToolsMcpInstructionPolicy::class);
    Log::spy();

    runGuestToolsWarning();

    Log::shouldNotHaveReceived('warning');
});

it('neither fails nor logs when the policy throws', function (): void {
    guestToolsWarningOnTheWeb();
    guestToolsWarningPolicy(ThrowingUpdateToolsMcpInstructionPolicy::class);
    Log::spy();

    runGuestToolsWarning();

    Log::shouldNotHaveReceived('warning');
});

it('names only the servers a host policy allows guests', function (): void {
    guestToolsWarningOnTheWeb();
    guestToolsWarningPolicy(PartialUpdateToolsMcpInstructionPolicy::class);
    Log::spy();

    runGuestToolsWarning();

    Log::shouldHaveReceived('warning')->once()->withArgs(
        fn (string $message, array $context): bool => str_contains($message, 'updateTools')
            && $context === ['servers' => [PartialUpdateToolsMcpInstructionPolicy::ALLOWED_SERVER]],
    );
});

it('never names the cortex server', function (): void {
    guestToolsWarningOnTheWeb();
    app(McpServerRegistry::class)->register('cortex-copy', CortexServer::class);
    Log::spy();

    runGuestToolsWarning();

    Log::shouldHaveReceived('warning')->once()->withArgs(
        fn (string $message, array $context): bool => $context === ['servers' => ['echo', 'plain', 'cortex-copy']],
    );
});

it('checks once per process', function (): void {
    guestToolsWarningOnTheWeb();
    Log::spy();

    runGuestToolsWarning();
    runGuestToolsWarning();

    Log::shouldHaveReceived('warning')->once();

    GuestToolsWarning::reset();
    runGuestToolsWarning();

    Log::shouldHaveReceived('warning')->twice();
});
