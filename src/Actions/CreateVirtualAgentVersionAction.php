<?php

declare(strict_types=1);

namespace JayI\Cortex\Actions;

use Illuminate\Support\Facades\DB;
use JayI\Cortex\Events\Action\VirtualAgentVersionCreatedActionEvent;
use JayI\Cortex\Events\Action\VirtualAgentVersionCreatingActionEvent;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Models\VirtualAgentVersion;
use JayI\Cortex\Support\PublicationCache;

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
    public function execute(VirtualAgent $agent, array $data): VirtualAgentVersion
    {
        VirtualAgentVersionCreatingActionEvent::dispatch($agent, $data);

        $result = $this->perform($agent, $data);

        VirtualAgentVersionCreatedActionEvent::dispatch($agent, $result);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function perform(VirtualAgent $agent, array $data): VirtualAgentVersion
    {
        return DB::transaction(function () use ($agent, $data): VirtualAgentVersion {
            /** @var VirtualAgentVersion $version */
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
