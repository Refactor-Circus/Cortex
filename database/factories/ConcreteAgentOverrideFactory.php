<?php

declare(strict_types=1);

namespace JayI\Cortex\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JayI\Cortex\Models\ConcreteAgentOverride;

/**
 * @extends Factory<ConcreteAgentOverride>
 */
final class ConcreteAgentOverrideFactory extends Factory
{
    protected $model = ConcreteAgentOverride::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agent' => fake()->unique()->slug(2),
            'tools' => null,
        ];
    }
}
