# HTTP persist() Requests

Controllers are routing glue — every method is one line:

```php
public function store(StorePromptRequest $request): JsonResponse
{
    return $request->persist();
}
```

The FormRequest is the complete HTTP use case — validation, authorization, action call, response:

```php
final class StorePromptRequest extends Request
{
    public function rules(): array
    {
        return CreatePromptAction::rules();
    }

    public function persist(): JsonResponse
    {
        $prompt = app(CreatePromptAction::class)->execute($this->validated());

        return (new PromptResource($prompt))->response()->setStatusCode(201);
    }
}
```

- Extend `RefactorCircus\Cortex\Http\Request` (abstract persist() enforces the shape). It extends `RefactorCircus\Keystone\Http\Requests\Request` but keeps asking the Gate as the signed-in user or as a guest
- One request class per operation; controllers stay identical across models and mirror the MCP request layer 1:1
- `rules()` always delegates to the action's static rules — never inline
- Status codes: 201 create, 200 default, 204 (Response) for deletes
- Never put action calls or response building in controllers
