<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Cortex\Domains\Tool\Events\ToolDescriptionVersionCreatedActionEvent;
use RefactorCircus\Cortex\Domains\Tool\Events\ToolDescriptionVersionCreatingActionEvent;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel;
use RefactorCircus\Cortex\Support\PublicationCache;

final class CreateToolDescriptionVersionAction
{
    public function __construct(private readonly PublicationCache $cache) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'content' => ['required', 'string'],
            'publish' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(string $tool, array $data): ToolDescriptionVersionModel
    {
        ToolDescriptionVersionCreatingActionEvent::dispatch($tool, $data);

        $result = $this->perform($tool, $data);

        ToolDescriptionVersionCreatedActionEvent::dispatch($tool, $result);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function perform(string $tool, array $data): ToolDescriptionVersionModel
    {
        return DB::transaction(function () use ($tool, $data): ToolDescriptionVersionModel {
            /** @var ToolDescriptionModel $description */
            $description = ToolDescriptionModel::query()->firstOrCreate(['tool' => $tool]);

            /** @var ToolDescriptionVersionModel $version */
            $version = $description->versions()->create([
                'version' => ((int) $description->versions()->lockForUpdate()->max('version')) + 1,
                'content' => $data['content'],
            ]);

            if ($data['publish'] ?? false) {
                $description->published_version_id = $version->getKey();
                $description->save();

                $this->cache->forget($this->cache->toolDescriptionsKey());
            }

            return $version;
        });
    }
}
