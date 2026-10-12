<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Cortex\Domains\Tool\Data\CreateToolDescriptionVersionData;
use RefactorCircus\Cortex\Domains\Tool\Events\ToolDescriptionVersionCreatedActionEvent;
use RefactorCircus\Cortex\Domains\Tool\Events\ToolDescriptionVersionCreatingActionEvent;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel;
use RefactorCircus\Cortex\Support\PublicationCache;

final class CreateToolDescriptionVersionAction
{
    public function __construct(private readonly PublicationCache $cache) {}

    public function execute(string $tool, CreateToolDescriptionVersionData $data): ToolDescriptionVersionModel
    {
        ToolDescriptionVersionCreatingActionEvent::dispatch($tool, $data);

        $result = $this->perform($tool, $data);

        ToolDescriptionVersionCreatedActionEvent::dispatch($tool, $result);

        return $result;
    }

    private function perform(string $tool, CreateToolDescriptionVersionData $data): ToolDescriptionVersionModel
    {
        return DB::transaction(function () use ($tool, $data): ToolDescriptionVersionModel {
            /** @var ToolDescriptionModel $description */
            $description = ToolDescriptionModel::query()->firstOrCreate(['tool' => $tool]);

            // Lock the parent row, not the aggregate: PostgreSQL refuses FOR UPDATE with MAX().
            $description->newQuery()->whereKey($description->getKey())->lockForUpdate()->value($description->getKeyName());

            /** @var ToolDescriptionVersionModel $version */
            $version = $description->versions()->create([
                'version' => ((int) $description->versions()->max('version')) + 1,
                'content' => $data->content,
            ]);

            if ($data->publish) {
                $description->published_version_id = $version->getKey();
                $description->save();

                DB::afterCommit(fn () => $this->cache->forget($this->cache->toolDescriptionsKey()));
            }

            return $version;
        });
    }
}
