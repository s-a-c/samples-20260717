<?php

declare(strict_types=1);

// Guard for Wayfinder #15 / ADR 100328 (Larastan baseline policy).
//
// ADR 100328 mandates `level: max` with NO open-ended baseline ratchet: every
// error that remains suppressed in `phpstan-baseline.neon` must carry a
// citation comment naming the retiring ticket — `# bd:<id>`, `# gh-<n>`, or
// `# framework-idiom:`. Permanent framework-idiom carve-outs live instead in
// `phpstan.neon` `ignoreErrors` (exempt, not counted here). This test fails the
// build when the baseline regrows uncited — exactly the failure that let #46's
// annotations disappear after a 2026-07-27 regeneration.

it('requires every phpstan baseline entry to cite a ticket', function (): void {
    $baseline = __DIR__.'/../../phpstan-baseline.neon';

    if (! file_exists($baseline)) {
        $this->markTestSkipped(
            'No phpstan-baseline.neon present — ADR 100328 terminal state reached.',
        );
    }

    $contents = file_get_contents($baseline);
    assert($contents !== false);

    // Each baseline entry has exactly one `message:` line.
    $entryCount = preg_match_all('/^\s+message:\s+/m', $contents) ?: 0;
    $citationCount = preg_match_all(
        '/^\s+#\s*(bd:|gh-|framework-idiom:)/m',
        $contents,
    ) ?: 0;

    // Refuse to pass vacuously: a baseline file must contain real entries.
    expect($entryCount)->toBeGreaterThan(0, 'phpstan-baseline.neon exists but has no message: entries — regenerate it.');

    expect($citationCount)
        ->toBeGreaterThanOrEqual($entryCount, sprintf(
            'phpstan-baseline.neon has %d entries but only %d carry a citation comment '
            .'(# bd:<id> | # gh-<n> | # framework-idiom:). See ADR 100328 / Wayfinder #18.',
            $entryCount,
            $citationCount,
        ));
});
