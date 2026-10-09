<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionCreatedActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionCreatingActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use RefactorCircus\Cortex\Support\PublicationCache;

final class CreateVirtualAgentVersionAction
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
    public function execute(VirtualAgentModel $agent, array $data): VirtualAgentVersionModel
    {
        VirtualAgentVersionCreatingActionEvent::dispatch($agent, $data);

        $result = $this->perform($agent, $data);

        VirtualAgentVersionCreatedActionEvent::dispatch($agent, $result);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function perform(VirtualAgentModel $agent, array $data): VirtualAgentVersionModel
    {
        return DB::transaction(function () use ($agent, $data): VirtualAgentVersionModel {
            /** @var VirtualAgentVersionModel $version */
            $version = $agent->versions()->create([
                'version' => ((int) $agent->versions()->lockForUpdate()->max('version')) + 1,
                'content' => $data['content'],
            ]);

            if ($data['publish'] ?? false) {
                $agent->published_version_id = (string) $version->getKey();
                $agent->save();

                $agent->unsetRelation('publishedVersion');

                $this->cache->forget($this->cache->virtualAgentKey((string) $agent->getKey()));
            }

            return $version;
        });
    }
}
