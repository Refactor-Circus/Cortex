<?php

declare(strict_types=1);

namespace JayI\Cortex\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JayI\Cortex\Domains\McpServer\Models\McpInstructionModel;

/**
 * @extends Factory<McpInstructionModel>
 */
final class McpInstructionFactory extends Factory
{
    protected $model = McpInstructionModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'server' => fake()->unique()->slug(2),
        ];
    }
}
