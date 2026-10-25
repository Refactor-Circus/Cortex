<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Log;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\GuestToolsWarning;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpInstructionOverrides;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpToolOverrides;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\ToolsColumn;
use RefactorCircus\Keystone\Support\ServiceProvider;
use Throwable;

class ConcreteServerServiceProvider extends ServiceProvider
{
    /**
     * The class names the two models had before the domain was renamed from
     * `McpServer`. Polymorphic columns and audit entries hold these strings,
     * and new rows keep writing them, so stored data stays uniform. A host's
     * published `cortex.policies` may still be keyed by them.
     *
     * @var array<string, class-string<Model>>
     */
    private const array OLD_MODEL_NAMES = [
        'RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel' => McpInstructionModel::class,
        'RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionVersionModel' => McpInstructionVersionModel::class,
    ];

    /**
     * Whether this process already ran the morph alias check.
     */
    private static bool $morphAliasesChecked = false;

    /**
     * The old `cortex.policies` keys moved by `remapOldPolicyKeys()`, each with
     * its new key and whether the host listed that new key as well. Logged
     * once from `boot()`.
     *
     * @var array<string, array{new: class-string<Model>, both: bool}>
     */
    private array $remappedPolicyKeys = [];

    public function register(): void
    {
        // Every provider registers before any boots, so the Cortex provider's
        // registerPolicies() in boot() already sees the new keys.
        $this->remapOldPolicyKeys();

        $this->app->singleton(McpServerRegistry::class);

        $this->app->scoped(McpInstructionOverrides::class);

        $this->app->scoped(McpToolOverrides::class);

        // Checked once per process; a worker sees a newly run migration after
        // it restarts.
        $this->app->singleton(ToolsColumn::class);
    }

    public function boot(): void
    {
        $this->warnAboutOldPolicyKeys();

        // Merges into the host's map (never replaces it, never enforces one).
        $this->keepMorphAliases(self::OLD_MODEL_NAMES);

        // A host or package may replace the map after Cortex booted.
        $this->app->booted(fn () => $this->checkMorphAliases());

        // Once every policy is registered: warn while guests may change a
        // server's tool list.
        $this->app->booted(fn () => $this->app->make(GuestToolsWarning::class)->check());

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }

    /**
     * Move `cortex.policies` entries keyed by an old model class name to the
     * new class, keeping the host's policy class. Without this the Gate would
     * never match the old key and the model would keep the bundled allow-all
     * policy. When the host lists the new key too, the new key wins. Old
     * policy class names given as values resolve through the shims.
     *
     * Runs from `register()`, before `registerPolicies()` in the Cortex
     * provider's `boot()`.
     *
     * @internal
     */
    public function remapOldPolicyKeys(): void
    {
        $key = $this->package()->configKey('policies');
        $config = $this->app->make(Repository::class);
        $policies = $config->get($key);

        if (! is_array($policies)) {
            return;
        }

        foreach (array_keys($policies) as $listed) {
            $new = $this->newModelName((string) $listed);

            if ($new === null) {
                continue;
            }

            $both = array_key_exists($new, $policies);

            if (! $both) {
                $policies[$new] = $policies[$listed];
            }

            unset($policies[$listed]);

            $this->remappedPolicyKeys[(string) $listed] = ['new' => $new, 'both' => $both];

            // The host named the old class, so alias it now: a host policy
            // that type-hints the old model name would otherwise fail its
            // parameter check, which never triggers autoloading.
            class_exists(ltrim((string) $listed, '\\'));
        }

        if ($this->remappedPolicyKeys !== []) {
            $config->set($key, $policies);
        }
    }

    /**
     * Log one deprecation warning for each `cortex.policies` key that
     * `remapOldPolicyKeys()` moved, then forget them.
     *
     * @internal
     */
    public function warnAboutOldPolicyKeys(): void
    {
        foreach ($this->remappedPolicyKeys as $old => ['new' => $new, 'both' => $both]) {
            $outcome = $both
                ? "[{$new}] is listed as well and wins, so the old entry is ignored; remove it."
                : "Its policy now applies to [{$new}]; key the entry by [{$new}] instead.";

            Log::warning("Cortex policy key [{$old}] in cortex.policies is deprecated: the model moved to [{$new}]. {$outcome} The old key stops working in the next major release.", [
                'old' => $old,
                'new' => $new,
            ]);
        }

        $this->remappedPolicyKeys = [];
    }

    /**
     * The new model class for an old model class name, or null when the name
     * is not one of them. PHP class names ignore case and a leading backslash.
     *
     * @return class-string<Model>|null
     */
    private function newModelName(string $name): ?string
    {
        foreach (self::OLD_MODEL_NAMES as $old => $model) {
            if (strcasecmp(ltrim($name, '\\'), $old) === 0) {
                return $model;
            }
        }

        return null;
    }

    /**
     * Warn when the morph map no longer resolves an old class name to its
     * model (a host replaced the map without Cortex's keys, so stored rows
     * stop loading), or when a model writes another alias than its old class
     * name (a host re-mapped it, so stored rows mix two strings).
     *
     * Runs once per process from the `booted` callback; logs one warning per
     * broken key and never throws.
     *
     * @internal
     */
    public function checkMorphAliases(): void
    {
        if (self::$morphAliasesChecked) {
            return;
        }

        self::$morphAliasesChecked = true;

        foreach (self::OLD_MODEL_NAMES as $old => $model) {
            try {
                $this->checkMorphAlias($old, $model);
            } catch (Throwable) {
                // A boot-time diagnostic must never break the application.
            }
        }
    }

    /**
     * Let the morph alias check run again in this process.
     *
     * @internal
     */
    public static function resetMorphAliasCheck(): void
    {
        self::$morphAliasesChecked = false;
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function checkMorphAlias(string $old, string $model): void
    {
        $resolved = Relation::getMorphedModel($old);

        if ($resolved !== $model) {
            Log::warning("Cortex morph alias [{$old}] no longer resolves to [{$model}], so stored rows that name it will not load. Keep it in Relation::morphMap(); see the README on host morph maps.", [
                'key' => $old,
                'model' => $model,
                'resolved' => $resolved,
            ]);

            return;
        }

        $written = (new $model)->getMorphClass();

        if ($written !== $old) {
            Log::warning("Cortex model [{$model}] is stored as [{$written}] instead of its morph alias [{$old}], so stored rows mix two strings. Do not map it under another alias; see the README on host morph maps.", [
                'key' => $old,
                'model' => $model,
                'morph_class' => $written,
            ]);
        }
    }
}
