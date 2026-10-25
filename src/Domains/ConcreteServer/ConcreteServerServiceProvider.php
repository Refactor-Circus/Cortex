<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Log;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpInstructionOverrides;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Keystone\Support\ServiceProvider;
use Throwable;

class ConcreteServerServiceProvider extends ServiceProvider
{
    /**
     * The class names the two models had before the domain was renamed from
     * `McpServer`. Polymorphic columns and audit entries hold these strings,
     * and new rows keep writing them, so stored data stays uniform.
     *
     * @var array<string, class-string<Model>>
     */
    private const array MORPH_ALIASES = [
        'RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel' => McpInstructionModel::class,
        'RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionVersionModel' => McpInstructionVersionModel::class,
    ];

    /**
     * Whether this process already ran the morph alias check.
     */
    private static bool $morphAliasesChecked = false;

    public function register(): void
    {
        $this->app->singleton(McpServerRegistry::class);

        $this->app->scoped(McpInstructionOverrides::class);
    }

    public function boot(): void
    {
        // Merges into the host's map (never replaces it, never enforces one).
        $this->keepMorphAliases(self::MORPH_ALIASES);

        // A host or package may replace the map after Cortex booted.
        $this->app->booted(fn () => $this->checkMorphAliases());

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
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

        foreach (self::MORPH_ALIASES as $old => $model) {
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
