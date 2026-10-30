# Standards Feedback

Every time work has to break a standard, add a row.

| Date | Spec | Standard | Deviation | Why |
| --- | --- | --- | --- | --- |
| 2026-10-30 | 2026-10-29-2038-register-roster-redirect-domains | errors/exceptions (Cortex: every thrown exception extends `CortexException`, never a raw SPL exception) | `Atrium\ScreenRegistrations` throws (and in production reports) a plain `InvalidArgumentException` for a malformed `cortex.atrium.redirect_domains.screens` entry, and rethrows the one Atrium raises for a bad target or slot. | Suite decision D-021 (same as Atrium D-022): malformed integration config throws `InvalidArgumentException` in local and testing and is reported and skipped elsewhere, so every package raises the same type Atrium does for the same mistake. |
