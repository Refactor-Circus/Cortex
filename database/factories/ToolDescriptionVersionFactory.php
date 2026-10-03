<?php

declare(strict_types=1);

namespace JayI\Cortex\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JayI\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use JayI\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel;

/**
 * @extends Factory<ToolDescriptionVersionModel>
 */
final class ToolDescriptionVersionFactory extends Factory
{
    protected $model = ToolDescriptionVersionModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tool_description_id' => ToolDescriptionModel::factory(),
            'version' => 1,
            'content' => fake()->paragraph(),
        ];
    }
}
