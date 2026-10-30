<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Atrium;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RefactorCircus\Atrium\Domains\Screens\Data\SectionDefinition;
use RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\ListRedirectDomainsAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Domains\RedirectDomain\Services\RedirectDomains;
use Throwable;

/**
 * Puts the redirect-domain UI on other packages' screens, as
 * `cortex.atrium.redirect_domains.screens` lists them: the `cortex.mcp` tab
 * for an entry of `tab`, else the `cortex.redirect-domains` section in the
 * named slot. The screen's record owns the domains shown.
 *
 * A malformed entry throws InvalidArgumentException in the local and testing
 * environments; elsewhere it is reported, skipped, and the rest still
 * register, so the application keeps booting.
 */
final class ScreenRegistrations
{
    /**
     * The entry value that adds a tab rather than a section.
     */
    private const string TAB = 'tab';

    private const string VIEW = 'cortex::ui.screens.redirect-domains';

    /**
     * Register every configured entry. Called from the service provider's
     * `boot()` while Atrium is installed and the dashboard UI is on.
     */
    public static function register(): void
    {
        try {
            $screens = self::screens();
        } catch (InvalidArgumentException $exception) {
            self::fail($exception);

            return;
        }

        foreach ($screens as $target => $slot) {
            try {
                self::add($target, $slot);
            } catch (InvalidArgumentException $exception) {
                self::fail($exception);
            }
        }
    }

    /**
     * The configured map, or the shipped default when a config published
     * before the key existed lacks it. An empty list stays empty.
     *
     * @return array<array-key, mixed>
     *
     * @throws InvalidArgumentException when the map is not an array
     */
    private static function screens(): array
    {
        $key = 'cortex.atrium.redirect_domains.screens';

        if (! config()->has($key)) {
            /** @var array{atrium: array{redirect_domains: array{screens: array<string, string>}}} $defaults */
            $defaults = require dirname(__DIR__, 2).'/config/cortex.php';

            return $defaults['atrium']['redirect_domains']['screens'];
        }

        $screens = config($key);

        if (! is_array($screens)) {
            throw new InvalidArgumentException("[{$key}] must be an array of screen keys to `tab` or a slot name.");
        }

        return $screens;
    }

    /**
     * @throws InvalidArgumentException for a target or slot that is not a non-empty string, or one Atrium rejects
     */
    private static function add(int|string $target, mixed $slot): void
    {
        if (! is_string($target) || $target === '') {
            throw new InvalidArgumentException("Redirect-domain screen [{$target}] in cortex.atrium.redirect_domains.screens must be a screen key, such as vendor.screen.");
        }

        if (! is_string($slot) || $slot === '') {
            throw new InvalidArgumentException("Redirect-domain screen [{$target}] in cortex.atrium.redirect_domains.screens needs `tab` or a slot name.");
        }

        if ($slot === self::TAB) {
            Atrium::screens()->tab($target, TabDefinition::make('cortex.mcp')
                ->label(self::translate('cortex::cortex.mcp'))
                ->icon('globe-alt')
                ->view(self::VIEW)
                ->authorize(self::visible(...))
                ->resolve(self::data(...)));

            return;
        }

        Atrium::screens()->section($target, $slot, SectionDefinition::make('cortex.redirect-domains')
            ->label(self::translate('cortex::cortex.redirect_domains'))
            ->view(self::VIEW)
            ->authorize(self::visible(...))
            ->resolve(self::data(...)));
    }

    /**
     * Throw where a developer sees it, else report and carry on.
     */
    private static function fail(InvalidArgumentException $exception): void
    {
        if (app()->environment('local', 'testing')) {
            throw $exception;
        }

        report($exception);
    }

    /**
     * Whether the viewer sees the tab or section on this record. It runs on
     * every render of every target screen, so the checks without a query
     * come first, and a policy that throws hides it rather than the page.
     */
    private static function visible(Request $request, ?Model $record): bool
    {
        if (! app(RedirectDomains::class)->enabled() || ! $record instanceof Model || ! $record->exists) {
            return false;
        }

        if (! array_key_exists('cortex', Atrium::pluginRegistry()->authorized($request))) {
            return false;
        }

        try {
            return ScreenAccess::allowsFor($request->user(), 'viewAny', RedirectDomainModel::class, [$record]);
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    /**
     * The owner's domains and the owner fields the forms post. Each domain's
     * owner is the record already loaded, so asking `delete` per row loads
     * no owner again.
     *
     * @return array<string, mixed>
     */
    private static function data(?Model $record): array
    {
        if ($record === null) {
            return [];
        }

        // Unpaged, so always a collection.
        $domains = app(ListRedirectDomainsAction::class)->execute($record);
        $domains = $domains instanceof Collection ? $domains : new Collection($domains->items());

        foreach ($domains as $domain) {
            $domain->setRelation('owner', $record);
        }

        return [
            'domains' => $domains,
            'ownerType' => $record->getMorphClass(),
            'ownerId' => (string) $record->getKey(),
        ];
    }

    private static function translate(string $key): string
    {
        $line = __($key);

        return is_string($line) ? $line : $key;
    }
}
