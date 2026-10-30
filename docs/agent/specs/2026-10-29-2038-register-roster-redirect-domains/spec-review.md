
## Round 1

Revised `spec.md`. The open questions (Q-1 host authorization, scope split, Q-2 no-Atrium proof, Q-3 redirect target, owner-type allow-list) were left as written; every change below holds under any answer.

- **Goal / Depends on:** states the three deliverables and adds a Depends-on list (Atrium screen-extensions-registry, PR #16), restating the Atrium behaviours relied on (unauthorized `?tab=` is 403, authorization order, slot layout, `InvalidArgumentException`) so the criteria stand alone.
- **Criterion 1:** `ScreenRegistrations` falls back to the default map when the key is absent (shallow config merge risk), not when empty; test with a published-style config. The `roster` grep now bans the string everywhere in `src/`, comments included (no comment-stripping needed).
- **Criterion 2 and Design:** non-string and empty entries and bad slots throw `InvalidArgumentException` from `boot()`, as a deliberate, documented, tested fail-fast.
- **Criterion 3:** closure runs no query when disabled or for a non-model; policy exceptions hide the section; query-count and throwing-policy tests added.
- **Criterion 6:** added morph-alias and unregistered-type cases, and a visibility-negatives dataset with named fixtures.
- **Criterion 7:** redirect target accepted only on exact scheme, host and port match, path and query only; owner types limited to what `RedirectDomains::owner()` already resolves (no new allow-list, so that open question stays open); partial owner is 422; tests for lookalike host, userinfo and scheme mismatch.
- **Criterion 9:** per-kind leakage columns (row.actions on detail, aside on list), no history slot, and an explicit no-row-slot check for configured rows.
- **Criterion 11:** layout claims tied to rendered-HTML assertions (aside landmark, `assertSeeInOrder`, last `td`, `assertDontSee` with an open tab).
- **Criterion 16:** bootstrap asserts the PSR-4 removal and throws if `findFile` still resolves Atrium; the CI lane is stated as the authoritative proof.
- **Criterion 17:** README states "Atrium is suggested" and the dashboard being absent without Atrium; CHANGELOG adds the `?tab=mcp` to `cortex.mcp` and route-name change; docs tests use specific phrases and per-topic keywords.
- **Out of scope:** adds the `history` slot and audit subject, old `?tab=mcp` links, row slots for configured rows, and unchanged ownerless dashboard behaviour.
- **Not changed:** `shape.md` (stale Scope, Standards and D-002) was outside this revision step and still needs updating to match the spec. The spec title is kept; the Goal now says the Roster domains are one of three parts.
