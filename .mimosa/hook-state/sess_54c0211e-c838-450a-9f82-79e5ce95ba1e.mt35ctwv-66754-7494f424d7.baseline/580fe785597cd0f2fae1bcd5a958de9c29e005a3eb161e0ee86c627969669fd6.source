<?php

declare(strict_types=1);

namespace App\Services\ProductReset;

use JsonSerializable;

final class ResetEvidence implements JsonSerializable
{
    public const int SCHEMA_VERSION = 1;

    public const array SECTIONS = [
        'metadata',
        'pre_reset_state',
        'pre_flight_checks',
        'source_verification',
        'backup_snapshot',
        'migration_phase',
        'data_import_phase',
        'post_reset_verification',
        'execution_summary',
    ];

    /**
     * @param  array<string, mixed>  $sections
     */
    public function __construct(
        private array $sections = [],
        private int $schemaVersion = self::SCHEMA_VERSION
    ) {
        foreach (self::SECTIONS as $section) {
            if (! isset($this->sections[$section])) {
                $this->sections[$section] = [];
            }
        }
    }

    /**
     * Create a new ResetEvidence VO.
     *
     * @param  array<string, mixed>  $sections
     */
    public static function create(array $sections = []): self
    {
        return new self($sections);
    }

    /**
     * Create VO from array structure.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $version = $data['schema_version'] ?? self::SCHEMA_VERSION;
        $sections = $data['sections'] ?? [];

        return new self($sections, $version);
    }

    public function getSchemaVersion(): int
    {
        return $this->schemaVersion;
    }

    /**
     * Get a specific section.
     *
     * @return array<string, mixed>
     */
    public function getSection(string $name): array
    {
        return $this->sections[$name] ?? [];
    }

    /**
     * Set a specific section.
     *
     * @param  array<string, mixed>  $data
     */
    public function setSection(string $name, array $data): self
    {
        $this->sections[$name] = $data;

        return $this;
    }

    /**
     * Get all sections.
     *
     * @return array<string, mixed>
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    /**
     * Convert the VO to array structure for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'sections' => $this->sections,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
