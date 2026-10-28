# Drop the paratest conflict pin and require pest ^5.3.4

Campaign "Drop the paratest conflict pin" (RingMaster campaign 1), task #20.

Cortex and PennantPlus each require the other's dev-main, so the two pin-drops were made together by hand (PennantPlus PR #7) instead of through the factory, and checked against each other: pest 5.3.4 with paratest 7.27.4, `vendor/bin/pest --parallel` passes (656 tests).

## Acceptance criteria

1. composer.json has no `conflict` on brianium/paratest and requires pestphp/pest ^5.3.4.
2. CI is green once PennantPlus PR #7 is merged too.
