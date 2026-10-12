<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Support;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;

/**
 * The `exists` rule for each `sub_agents.*` slug, answered from one query for
 * the whole list rather than one per slug. Errors keep `exists`' key and
 * message (`sub_agents.0`: "The selected sub_agents.0 is invalid.").
 *
 * An instance belongs to one validation: build a new one in each `rules()`.
 */
final class SubAgentSlugsExist implements DataAwareRule, ValidationRule
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * @var array<string, true>|null
     */
    private ?array $existing = null;

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;
        $this->existing = null;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! isset($this->existing()[$value])) {
            $fail('validation.exists')->translate();
        }
    }

    /**
     * @return array<string, true>
     */
    private function existing(): array
    {
        if ($this->existing !== null) {
            return $this->existing;
        }

        $selected = $this->data['sub_agents'] ?? [];
        $slugs = is_array($selected) ? array_values(array_unique(array_filter($selected, is_string(...)))) : [];

        $existing = $slugs === [] ? [] : VirtualAgentModel::query()->whereIn('slug', $slugs)->pluck('slug')->all();

        $this->existing = [];

        foreach ($existing as $slug) {
            $this->existing[(string) $slug] = true;
        }

        return $this->existing;
    }
}
