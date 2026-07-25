# ADR 0004: Shadow-Schema Import Pipeline

**Status:** Accepted
**Date:** 2026-07-25
**Context:** Each of the three sample products (Chinook, Northwind, Sakila) is populated from a source baseline — an upstream reference dataset. Two key operations must be supported: (a) first-time import that populates the product domain's schema, (b) Product Reset that restores a product domain to its source baseline, discarding local changes while preserving Domain Identities. Resets must have near-zero downtime — a bounded Reset Window during which only the affected product domain is unavailable. Resets must be atomic: if the reset fails partway through, the product domain must be recoverable to either its previous state or a known good state. Traditional approaches (migrate:fresh + seeder, TRUNCATE + INSERT in transactions) either lock tables for prolonged periods or cannot be rolled back safely for schemas with complex foreign key graphs.

**Decision:** Build the import pipeline using a shadow-schema pattern: build the new dataset in a shadow schema, verify it, then atomically swap it into place.

- **Shadow schema:** A temporary schema (e.g., `chinook_shadow`) is created as a clone of the target schema structure.
- **Import:** All source baseline data is imported into the shadow schema, including any data transformations and normalization.
- **Verification:** Baseline invariants are checked against the shadow schema — row counts, referential integrity, Source Identity Registry consistency.
- **Atomic swap:** A single DDL transaction performs: `DROP SCHEMA chinook CASCADE; ALTER SCHEMA chinook_shadow RENAME TO chinook;`. This is near-instantaneous.
- **Recovery:** If verification fails, the shadow schema is simply dropped — the live schema is untouched. If the swap transaction fails, a commit+RENAME from a backup snapshot is available.
- **Source Identity Registry** persists across resets in the `public` schema, mapping Source Identities to Domain Identities via the stable UUIDv7 keys.
- **Pin Manifest** records the exact upstream revision and artifact used for each import.

**Consequences:**
- **Positive:** Near-zero downtime reset — the swap is a metadata-only DDL operation that completes in milliseconds.
- **Positive:** Full isolation — a failed import never touches the live schema; no table locks during data loading.
- **Positive:** Atomic roll-forward — the swap is a single DDL transaction; failure leaves no partial state.
- **Positive:** Recovery path — the previous live schema can be preserved as a fallback or the shadow can be retried.
- **Positive:** Resets are independent per product domain — resetting Chinook does not affect Northwind or Sakila.
- **Tradeoff:** Requires double storage during the reset window — both live and shadow schemas exist simultaneously.
- **Tradeoff:** Schema must be defined independently of the data import (migrations are separate from seeding).
- **Tradeoff:** Foreign key relationships from shared infrastructure to product-domain tables (e.g., search documents) must be handled carefully across schema swaps.
- **Tradeoff:** PostgreSQL DDL within a transaction has some limitations (e.g., `DROP SCHEMA ... CASCADE` works transactionally, but concurrent DML on affected objects is blocked).

**Related:**
- [ADR 0001: Multi-Product Architecture](0001-multi-product-architecture.md) — per-product schema isolation
- [ADR 0002: UUIDv7 for All Entities](0002-uuidv7-for-all-entities.md) — stable Domain Identities across resets
- [CONTEXT.md](../../CONTEXT.md) — Product Import, Product Reset, Reset Window, Source Baseline, Source Identity Registry, Pin Manifest
