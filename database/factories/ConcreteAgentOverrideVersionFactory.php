<?php

declare(strict_types=1);

namespace JayI\Cortex\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JayI\Cortex\Models\ConcreteAgentOverride;
use JayI\Cortex\Models\ConcreteAgentOverrideVersion;

/**
 * @extends Factory<ConcreteAgentOverrideVersion>
 */
final class ConcreteAgentOverrideVersionFactory extends Factory
{
    protected $model = ConcreteAgentOverrideVersion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'concrete_agent_override_id' => ConcreteAgentOverride::factory(),
            'version' => 1,
            'content' => fake()->paragraph(),
        ];
    }
}
