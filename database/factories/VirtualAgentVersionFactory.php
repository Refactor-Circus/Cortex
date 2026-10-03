<?php

declare(strict_types=1);

namespace JayI\Cortex\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;

/**
 * @extends Factory<VirtualAgentVersionModel>
 */
final class VirtualAgentVersionFactory extends Factory
{
    protected $model = VirtualAgentVersionModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'virtual_agent_id' => VirtualAgentModel::factory(),
            'version' => 1,
            'content' => fake()->paragraph(),
        ];
    }
}
