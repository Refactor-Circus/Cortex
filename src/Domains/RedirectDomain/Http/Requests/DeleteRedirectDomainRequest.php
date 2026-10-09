<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\RedirectDomain\Http\Requests;

use Illuminate\Http\Response;
use JayI\Cortex\Domains\RedirectDomain\Actions\DeleteRedirectDomainAction;

final class DeleteRedirectDomainRequest extends RedirectDomainRequest
{
    public function authorize(): bool
    {
        return $this->allows('delete', $this->domain());
    }

    public function rules(): array
    {
        return DeleteRedirectDomainAction::rules();
    }

    public function persist(): Response
    {
        app(DeleteRedirectDomainAction::class)->execute($this->domain());

        return response()->noContent();
    }
}
