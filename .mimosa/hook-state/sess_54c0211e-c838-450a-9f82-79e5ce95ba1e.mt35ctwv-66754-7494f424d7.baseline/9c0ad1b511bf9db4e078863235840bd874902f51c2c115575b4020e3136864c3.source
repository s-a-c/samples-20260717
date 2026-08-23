<?php

declare(strict_types=1);

use App\Console\Commands\DossierGenerate;
use Illuminate\Support\Facades\File;

covers(DossierGenerate::class);

beforeEach(function () {
    $this->dossierDir = base_path('docs/15-delivery/1515-implementation-readiness-dossier');

    // Start each test from a clean dossier directory.
    if (is_dir($this->dossierDir)) {
        File::deleteDirectory($this->dossierDir);
    }
});

test('dossier generate scaffolds the contents and stage template files', function () {
    $dossierDir = $this->dossierDir;
    assert(is_string($dossierDir));

    $this->artisan('dossier:generate')
        ->assertSuccessful()
        ->expectsOutputToContain('Implementation-Readiness Dossier scaffolded');

    expect("{$dossierDir}/151501-contents.md")->toBeFile()
        ->and("{$dossierDir}/151502-stage-template.md")->toBeFile();
});

test('contents file includes the evidence checklist', function () {
    $dossierDir = $this->dossierDir;
    assert(is_string($dossierDir));

    $this->artisan('dossier:generate')->assertSuccessful();

    $contents = (string) file_get_contents("{$dossierDir}/151501-contents.md");

    expect($contents)
        ->toContain('Implementation-Readiness Dossier')
        ->toContain('Evidence checklist')
        ->toContain('Acceptance gates')
        ->toContain('Recovery procedure')
        ->toContain('Governance');
});

test('stage template file includes the per-stage structure', function () {
    $dossierDir = $this->dossierDir;
    assert(is_string($dossierDir));

    $this->artisan('dossier:generate')->assertSuccessful();

    $template = (string) file_get_contents("{$dossierDir}/151502-stage-template.md");

    expect($template)
        ->toContain('Stage N')
        ->toContain('Acceptance gates')
        ->toContain('composer ci:check')
        ->toContain('Recovery procedure');
});

test('dossier generate is idempotent without force', function () {
    $dossierDir = $this->dossierDir;
    assert(is_string($dossierDir));

    $this->artisan('dossier:generate')->assertSuccessful();

    // Mutate the contents to prove a second run does not overwrite.
    file_put_contents("{$dossierDir}/151501-contents.md", 'SENTINEL');

    $this->artisan('dossier:generate')
        ->assertSuccessful()
        ->expectsOutputToContain('already exist');

    expect(file_get_contents("{$dossierDir}/151501-contents.md"))->toBe('SENTINEL');
});

test('force overwrites existing dossier files', function () {
    $dossierDir = $this->dossierDir;
    assert(is_string($dossierDir));

    $this->artisan('dossier:generate')->assertSuccessful();

    file_put_contents("{$dossierDir}/151501-contents.md", 'SENTINEL');

    $this->artisan('dossier:generate', ['--force' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Overwrote');

    expect(file_get_contents("{$dossierDir}/151501-contents.md"))
        ->not->toBe('SENTINEL')
        ->toContain('Implementation-Readiness Dossier');
});
