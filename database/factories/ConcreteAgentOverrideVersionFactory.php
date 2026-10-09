<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;

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
