<?php

declare(strict_types=1);

namespace JayI\Cortex\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JayI\Cortex\Domains\McpServer\Models\McpInstructionModel;
use JayI\Cortex\Domains\McpServer\Models\McpInstructionVersionModel;

/**
 * @extends Factory<McpInstructionVersionModel>
 */
final class McpInstructionVersionFactory extends Factory
{
    protected $model = McpInstructionVersionModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mcp_instruction_id' => McpInstructionModel::factory(),
            'version' => 1,
            'content' => fake()->paragraph(),
        ];
    }
}
