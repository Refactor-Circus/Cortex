<?php

declare(strict_types=1);

namespace JayI\Cortex\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use JayI\Cortex\Models\VirtualAgent;

/**
 * @extends Factory<VirtualAgent>
 */
final class VirtualAgentFactory extends Factory
{
    protected $model = VirtualAgent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $name = fake()->unique()->sentence(2),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'provider' => null,
            'model' => null,
            'settings' => null,
            'tools' => [],
            'concrete_sub_agents' => [],
        ];
    }

    /**
     * Give the agent a published first prompt version.
     */
    public function published(string $content = 'You are a helpful assistant.'): self
    {
        return $this->afterCreating(function (VirtualAgent $agent) use ($content): void {
            $version = $agent->versions()->create(['version' => 1, 'content' => $content]);

            $agent->published_version_id = (string) $version->getKey();
            $agent->save();
        });
    }
}
