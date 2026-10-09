<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;

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
