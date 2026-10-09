<?php

declare(strict_types=1);

namespace JayI\Cortex\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JayI\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;

/**
 * @extends Factory<RedirectDomainModel>
 */
final class RedirectDomainFactory extends Factory
{
    protected $model = RedirectDomainModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'domain' => 'https://'.fake()->unique()->domainName(),
        ];
    }
}
