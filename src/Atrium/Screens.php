<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Atrium;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;

/**
 * Cortex's own pages as screens of Atrium's extension registry, so other
 * packages can add tabs and sections to them without Cortex knowing them.
 *
 * Every page is a standard screen (`header.actions`, `before` and `after`,
 * plus `aside` on a detail screen or `row.actions` on a list screen) with
 * only the synthetic `overview` tab built in, opened by default. None has a
 * history slot or an audit subject: History attaches by wildcard.
 */
final class Screens
{
    /**
     * Declare a screen. Its `access()` asks an extension's `permission()` of
     * the policies in `cortex.policies`, as the signed-in user or a guest:
     * against the record (the screen's, or the row inside a row slot), or,
     * without one, against the subject class. With neither, as on the run
     * page, it grants nothing.
     *
     * @param  string  $kind  Screen::DETAIL or Screen::LIST
     * @param  class-string<Model>|null  $subject  What a permission is asked of when there is no record.
     */
    public static function declare(string $key, string $kind, ?Model $record, ?string $subject): Screen
    {
        return Screen::standard($key, $kind)
            ->record($record)
            ->defaultTab(Screen::OVERVIEW)
            ->access(static function (string $permission, Request $request, ?Model $record) use ($subject): bool {
                $target = $record ?? $subject;

                return $target !== null && ScreenAccess::allowsFor($request->user(), $permission, $target);
            });
    }
}
