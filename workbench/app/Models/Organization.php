<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * A host application's owner model, standing in for another package's
 * record (such as Roster's organization) on the workbench test host, as
 * strictly as a real host has it: no morph alias (its morph class is its
 * class name), a ULID key, and routed by its slug, which is what the screens
 * that show it pass as the record (D-018).
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 */
#[Fillable(['name', 'slug'])]
final class Organization extends Model
{
    use HasUlids;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
