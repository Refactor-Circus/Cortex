<?php

declare(strict_types=1);

namespace JayI\Cortex\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;

/**
 * @extends Factory<ConcreteAgentOverrideVersionModel>
 */
final class ConcreteAgentOverrideVersionFactory extends Factory
{
    protected $model = ConcreteAgentOverrideVersionModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'concrete_agent_override_id' => ConcreteAgentOverrideModel::factory(),
            'version' => 1,
            'content' => fake()->paragraph(),
        ];
    }
}
