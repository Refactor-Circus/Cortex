<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\Tool\Http\Requests;

use Illuminate\Http\Response;
use JayI\Cortex\Domains\Tool\Actions\DeleteToolDescriptionAction;

final class DeleteToolDescriptionRequest extends ToolDescriptionRequest
{
    public function authorize(): bool
    {
        return $this->allows('delete', $this->description());
    }

    public function persist(): Response
    {
        app(DeleteToolDescriptionAction::class)->execute($this->description());

        return response()->noContent();
    }
}
