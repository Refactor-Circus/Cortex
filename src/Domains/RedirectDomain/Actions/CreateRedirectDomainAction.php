<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain\Actions;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Cortex\Domains\RedirectDomain\Events\RedirectDomainCreatedActionEvent;
use RefactorCircus\Cortex\Domains\RedirectDomain\Events\RedirectDomainCreatingActionEvent;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Domains\RedirectDomain\Services\RedirectDomains;

final class CreateRedirectDomainAction
{
    public function __construct(private readonly RedirectDomains $domains) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'max:255', function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value) || RedirectDomains::normalize($value) === null) {
                    $fail(__('cortex::cortex.redirect_domain_invalid'));
                }
            }],
        ];
    }

    /**
     * Add a domain for an owner, or for every client when there is none.
     * Adding one the owner already has returns it.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?Model $owner = null): RedirectDomainModel
    {
        RedirectDomainCreatingActionEvent::dispatch($data, $owner);

        $result = $this->perform($data, $owner);

        RedirectDomainCreatedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function perform(array $data, ?Model $owner): RedirectDomainModel
    {
        $domain = RedirectDomains::normalize((string) ($data['domain'] ?? ''))
            ?? throw ValidationException::withMessages(['domain' => __('cortex::cortex.redirect_domain_invalid')]);

        $existing = RedirectDomainModel::query()->ownedBy($owner)->where('domain', $domain)->first();

        if ($existing !== null) {
            return $existing;
        }

        $model = new RedirectDomainModel(['domain' => $domain]);

        if ($owner !== null) {
            $model->owner()->associate($owner);
        }

        $model->save();

        $this->domains->forget();

        return $model;
    }
}
