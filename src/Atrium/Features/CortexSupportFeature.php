<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Atrium\Features;

use RefactorCircus\PennantPlus\Domains\Feature\Support\OnLayeredFeature;

/**
 * Switches Cortex in Atrium on and off: its navigation, search and pages.
 * On until its global value is set. The `SupportFeature` suffix matches
 * PennantPlus's default `gate.global_only` pattern, so only the global value
 * counts and per-user access stays with Cortex's policies.
 *
 * Needs refactor-circus/pennantplus. Point `cortex.atrium.features` at a subclass to
 * change the default, or at your own feature instead.
 */
class CortexSupportFeature extends OnLayeredFeature {}
