<?php

declare(strict_types=1);

namespace JayI\Cortex\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;

/**
 * @extends Factory<ConcreteAgentOverrideModel>
 */
final class ConcreteAgentOverrideFactory extends Factory
{
    protected $model = ConcreteAgentOverrideModel::class;

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
