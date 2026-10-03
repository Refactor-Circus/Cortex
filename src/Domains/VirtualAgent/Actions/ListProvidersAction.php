<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Actions;

use JayI\Cortex\Domains\VirtualAgent\Events\ProvidersListedActionEvent;
use JayI\Cortex\Domains\VirtualAgent\Events\ProvidersListingActionEvent;
use Laravel\Ai\AiManager;
use Throwable;

final class ListProvidersAction
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [];
    }

    public function __construct(private readonly AiManager $manager) {}

    /**
     * List the providers (and their selectable models) agents can run on.
     *
     * The `cortex.providers` config is authoritative when set; otherwise every
     * text-capable provider configured for laravel/ai is offered with the
     * models it declares (default, smartest, cheapest).
     *
     * @return list<array{name: string, models: list<string>, default_model: string|null}>
     */
    public function execute(): array
    {
        ProvidersListingActionEvent::dispatch();

        $result = $this->perform();

        ProvidersListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * List the providers (and their selectable models) agents can run on.
     *
     * The `cortex.providers` config is authoritative when set; otherwise every
     * text-capable provider configured for laravel/ai is offered with the
     * models it declares (default, smartest, cheapest).
     *
     * @return list<array{name: string, models: list<string>, default_model: string|null}>
     */
    private function perform(): array
    {
        /** @var array<string, array<int, string>> $configured */
        $configured = (array) config('cortex.providers', []);

        if ($configured !== []) {
            return array_values(collect($configured)
                ->map(fn (array $models, string $name): array => [
                    'name' => $name,
                    'models' => array_values($models),
                    'default_model' => array_values($models)[0] ?? null,
                ])
                ->all());
        }

        return array_values(collect(array_keys((array) config('ai.providers', [])))
            ->map(function (string $name): ?array {
                try {
                    $provider = $this->manager->textProvider($name);

                    return [
                        'name' => $name,
                        'models' => array_values(array_unique([
                            $provider->defaultTextModel(),
                            $provider->smartestTextModel(),
                            $provider->cheapestTextModel(),
                        ])),
                        'default_model' => $provider->defaultTextModel(),
                    ];
                } catch (Throwable) {
                    return null;
                }
            })
            ->filter()
            ->all());
    }
}
