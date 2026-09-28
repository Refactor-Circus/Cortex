<?php

declare(strict_types=1);

namespace JayI\Cortex\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Models\VirtualAgentVersion;

/**
 * @extends Factory<VirtualAgentVersion>
 */
final class VirtualAgentVersionFactory extends Factory
{
    protected $model = VirtualAgentVersion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'virtual_agent_id' => VirtualAgent::factory(),
            'version' => 1,
            'content' => fake()->paragraph(),
        ];
    }
}
