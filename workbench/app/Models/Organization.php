<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A host application's owner model, standing in for another package's
 * record (such as Roster's organization) on the workbench test host. It has
 * no morph alias, so its morph class is its class name.
 *
 * @property int $id
 * @property string $name
 */
#[Fillable(['name'])]
final class Organization extends Model {}
