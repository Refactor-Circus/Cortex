<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Data;

use Closure;
use Illuminate\Validation\Rule;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Domains\Tool\Support\ToolName;
use RefactorCircus\Keystone\Data\Data;
use Throwable;

/**
 * Input for UpdateConcreteServerToolsAction: the tool list a concrete MCP
 * server serves instead of its code-declared tools, in the order given. An
 * empty list serves no tools; null stores no list, so the code tools serve.
 */
final readonly class UpdateConcreteServerToolsData extends Data
{
    /**
     * @param  list<string>|null  $tools
     */
    public function __construct(
        public ?array $tools,
    ) {}

    /**
     * Each entry names a tool the same way serving does: a tool the server's
     * code declares, by its `name()`, else a registered Cortex tool, by its
     * registration name (a code tool wins when both match). Two entries that
     * would be served under the same name fail on the later one, so the
     * served list never holds duplicates.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(string $server): array
    {
        $code = app(McpServerRegistry::class)->defaultTools($server);
        $registered = app(ToolRegistry::class)->names();

        return [
            'tools' => ['present', 'nullable', 'array', 'list', function (string $attribute, mixed $value, Closure $fail) use ($code, $registered): void {
                if (! is_array($value)) {
                    return;
                }

                $served = [];
                $entries = [];

                foreach ($value as $index => $entry) {
                    // Non-strings, unknown entries and repeats fail on their
                    // own rules below.
                    if (! is_string($entry) || in_array($entry, $entries, true)) {
                        continue;
                    }

                    $entries[] = $entry;
                    $name = self::servedName($entry, $code, $registered);

                    if ($name === null) {
                        continue;
                    }

                    if (array_key_exists($name, $served)) {
                        $fail("{$attribute}.{$index}", "The entry [{$entry}] is served as [{$name}], like [{$served[$name]}] before it.");

                        continue;
                    }

                    $served[$name] = $entry;
                }
            }],
            'tools.*' => ['string', 'distinct', Rule::in([...$code, ...$registered])],
        ];
    }

    /**
     * The name a client would see for the entry, or null when it names no
     * tool. A registered tool that cannot be built is named by its entry.
     *
     * @param  list<string>  $code
     * @param  list<string>  $registered
     */
    private static function servedName(string $entry, array $code, array $registered): ?string
    {
        if (in_array($entry, $code, true)) {
            return $entry;
        }

        if (! in_array($entry, $registered, true)) {
            return null;
        }

        $class = collect(app(ToolRegistry::class)->summaries())->firstWhere('name', $entry)['class'] ?? null;

        try {
            return is_string($class) ? ToolName::of(app($class)) : $entry;
        } catch (Throwable) {
            return $entry;
        }
    }
}
