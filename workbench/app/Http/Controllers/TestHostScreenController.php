<?php

declare(strict_types=1);

namespace Workbench\App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use Workbench\App\Models\Organization;
use Workbench\App\Models\User;

/**
 * A host package's detail screen, standing in for Roster's (or any other
 * package's) in Cortex's tests, so Cortex never needs that package. Served
 * at `GET test-host/{key}/{id?}` (route `test-host`, `web` middleware) by the
 * test case. The key is the screen key, so one controller serves
 * `roster.organization`, `roster.user` and `acme.account`.
 *
 * Like a real host it authorizes first (`view` on the record, through the
 * Gate), declares `Screen::standard($key, Screen::DETAIL)` with the record and
 * a Gate-based `access()`, resolves its extensions and renders the tab bar,
 * the open registered tab's panel and every standard slot.
 *
 * The record is the workbench User for `roster.user` and an Organization for
 * any other key. Without an id it is an unsaved model of that class.
 */
final class TestHostScreenController
{
    public function __invoke(Request $request, ScreenExtensions $extensions, string $key, ?string $id = null): View
    {
        $record = self::record($key, $id);

        abort_unless(Gate::forUser($request->user())->allows('view', $record), 403);

        $screen = Screen::standard($key, Screen::DETAIL)
            ->record($record)
            ->access(fn (string $permission, Request $request, ?Model $record): bool => Gate::forUser($request->user())
                ->allows($permission, $record === null ? [] : [$record]));

        return view('workbench::test-host', [
            'resolved' => $extensions->resolve($screen, $request),
        ]);
    }

    private static function record(string $key, ?string $id): Model
    {
        $model = $key === 'roster.user' ? new User : new Organization;

        return $id === null ? $model : $model->newQuery()->findOrFail($id);
    }
}
