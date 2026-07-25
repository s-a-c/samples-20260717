# ADR 0002: UUIDv7 for All Entities

**Status:** Accepted
**Date:** 2026-07-25
**Context:** The application spans three product domains plus shared infrastructure. Primary keys must satisfy several constraints: (a) globally unique across all domains to enable future merging or cross-product reference without collision, (b) sortable for efficient B-tree index performance and natural ordering in paginated results, (c) generateable offline so that import pipelines can assign stable identities before rows are persisted, (d) compatible with Laravel's Eloquent ORM conventions. Auto-increment integers satisfy none of these. Standard UUIDv4 is globally unique but not sortable, leading to index fragmentation and poor B-tree performance at scale. ULIDs are sortable and unique but are not native to PostgreSQL's `uuid` type. Sequential UUIDs (v1/v6) leak timing and MAC address information.

**Decision:** Use UUIDv7 for all entity primary keys across all product domains and shared infrastructure.

- UUIDv7 encodes a Unix-millisecond timestamp in the first 48 bits, followed by random bits, making them time-sortable and unique.
- Implemented via Laravel's `HasUuids` trait with a `newUniqueId()` override that generates UUIDv7 using Symfony's `UuidV7` generator.

  ```php
  use Symfony\Component\Uid\UuidV7;

  protected function newUniqueId(): string
  {
      return (string) new UuidV7();
  }
  ```

- Database columns use the `uuid` type for efficient binary storage (16 bytes vs 37 bytes for string representation).
- All models use `HasUuids` — both product-domain models and shared infrastructure models (users, teams, search documents, etc.).
- Foreign key relationships use the `uuid` type consistently.

**Consequences:**
- **Positive:** Globally unique — no collision risk across domains or during offline ID generation.
- **Positive:** Time-sortable — natural ordering matches insertion order; good B-tree performance.
- **Positive:** Offline generation — import pipelines can generate stable Domain Identities before rows reach the database.
- **Positive:** No DB sequence contention — critical for high-throughput import pipelines.
- **Tradeoff:** 16 bytes per key vs. 4 bytes (int) or 8 bytes (bigint) — moderate storage impact on indexed columns and foreign keys.
- **Tradeoff:** UUIDv7 is not a standard UUID variant in older RFC sense — tooling and some PG extensions may not sort it correctly without explicit awareness. Benchmarks confirm it matches ULID and Monotonic sort performance (see PostgreSQL UUIDv7 support in pgxn).
- **Tradeoff:** Slightly more verbose in raw SQL queries and debugging (cannot type a short int).

**Related:**
- [ADR 0004: Shadow-Schema Import Pipeline](0004-shadow-schema-import-pipeline.md) — offline ID generation critical for shadow-schema reset
- [CONTEXT.md](../../CONTEXT.md) — Domain Identity definition
