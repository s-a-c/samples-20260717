<?php

declare(strict_types=1);

use App\Services\ProductReset\ResetEvidence;

covers(ResetEvidence::class);

test('reset evidence reports schema version 1', function () {
    expect(ResetEvidence::SCHEMA_VERSION)->toBe(1)
        ->and(ResetEvidence::create()->getSchemaVersion())->toBe(1);
});

test('reset evidence declares exactly nine sections', function () {
    expect(ResetEvidence::SECTIONS)->toHaveCount(9)
        ->and(ResetEvidence::SECTIONS)->toContain('metadata')
        ->and(ResetEvidence::SECTIONS)->toContain('execution_summary');
});

test('reset evidence initializes every declared section as an empty array by default', function () {
    $evidence = ResetEvidence::create();

    expect($evidence->getSections())->toHaveCount(count(ResetEvidence::SECTIONS));

    foreach (ResetEvidence::SECTIONS as $section) {
        expect($evidence->getSection($section))->toBe([]);
    }
});

test('reset evidence stores and retrieves a valid section configuration', function () {
    $evidence = ResetEvidence::create()
        ->setSection('metadata', ['operator' => 'admin', 'product' => 'chinook']);

    expect($evidence->getSection('metadata'))->toBe(['operator' => 'admin', 'product' => 'chinook']);
});

test('reset evidence create preserves caller-provided sections while defaulting the rest', function () {
    $evidence = ResetEvidence::create(['execution_summary' => ['duration_ms' => 1200]]);

    expect($evidence->getSection('execution_summary'))->toBe(['duration_ms' => 1200])
        ->and($evidence->getSection('metadata'))->toBe([]);
});

test('reset evidence rejects unknown section keys by returning an empty array', function () {
    $evidence = ResetEvidence::create();

    // An unknown key never throws; it resolves to an empty array.
    expect($evidence->getSection('nonexistent_section'))->toBe([]);
});

test('reset evidence round-trips through toArray and fromArray', function () {
    $evidence = ResetEvidence::create()
        ->setSection('metadata', ['operator' => 'admin'])
        ->setSection('execution_summary', ['duration_ms' => 900]);

    $restored = ResetEvidence::fromArray($evidence->toArray());

    expect($restored->getSchemaVersion())->toBe(1)
        ->and($restored->getSection('metadata'))->toBe(['operator' => 'admin'])
        ->and($restored->getSection('execution_summary'))->toBe(['duration_ms' => 900]);
});

test('reset evidence fromArray falls back to defaults for missing fields', function () {
    $restored = ResetEvidence::fromArray([]);

    expect($restored->getSchemaVersion())->toBe(1)
        ->and($restored->getSections())->toHaveCount(count(ResetEvidence::SECTIONS));
});

test('reset evidence is json serializable with its schema version and sections', function () {
    $evidence = ResetEvidence::create(['metadata' => ['operator' => 'admin']]);

    $json = json_encode($evidence, JSON_THROW_ON_ERROR);

    expect($json)->toBeJson()
        ->and($json)->toContain('"schema_version":1')
        ->and($json)->toContain('"metadata"')
        ->and($json)->toContain('admin');
});
