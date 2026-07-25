# ADR 0003: Postgres-Native Search (Hybrid)

**Status:** Accepted
**Date:** 2026-07-25
**Context:** Users need to search across three distinct product domains (Chinook, Northwind, Sakila) with fast, relevant results. Each domain has different entity types and textual content. Requirements: (a) full-text search for exact and fuzzy term matching, (b) semantic / vector search for meaning-based retrieval, (c) combined results from both approaches, (d) search can be scoped to one product domain or federated across all, (e) no external search service dependency to keep the application self-contained and deployable without paid infrastructure. PostgreSQL supports both GIN-indexed `tsvector` (full-text search, since PG 8.3) and `pgvector` (semantic search via HNSW-indexed vector similarity, via extension). An external search service (Algolia, Meilisearch, Elasticsearch) would add operational complexity and cost, and would require synchronization infrastructure.

**Decision:** Use postgres-native hybrid search combining GIN-indexed full-text search with HNSW-indexed pgvector similarity, fused via Reciprocal Rank Fusion (RRF).

- **Lexical tier:** GIN indexes on `tsvector` columns built from Search Documents. Supports English stemmed search with ranking via `ts_rank`.
- **Semantic tier:** HNSW indexes on 1024-dimensional `vector` columns in the `pgvector` extension. Embeddings generated via OpenAI's text-embedding-3-small model through the `laravel/ai` package.
- **Fusion:** Reciprocal Rank Fusion combines the candidate lists from both tiers into a single ranked result set.
- **Search Documents** are product-labelled materialized representations, stored in the shared `public` schema with a `product_domain` discriminator column for scoping.
- **Search Projections** keep lexical state transactionally current via model observers. Vector embeddings are generated asynchronously after commit and independently verified.
- **Federated Search** in the Admin Panel preserves product labels and routes each result to its owning Sample Panel.

**Consequences:**
- **Positive:** Zero external search service dependencies — no synchronization, no extra operational cost, no network latency.
- **Positive:** Transactional consistency for lexical search — no stale index lag.
- **Positive:** Single PostgreSQL connection handles search alongside application queries — simpler deployment topology.
- **Tradeoff:** Semantic search is eventually consistent (embedding generated post-commit). Requires verification mechanism.
- **Tradeoff:** Embedding generation costs (OpenAI API tokens) for each Search Document write.
- **Tradeoff:** Vector storage and HNSW index add disk and memory pressure on PostgreSQL.
- **Tradeoff:** 1024-dimensional vectors with HNSW indexing require the `pgvector` extension — not available on all PostgreSQL hosting platforms without enabling the extension.

**Related:**
- [ADR 0001: Multi-Product Architecture](0001-multi-product-architecture.md) — product-scoped search
- [CONTEXT.md](../../CONTEXT.md) — Search Document, Search Surface, Search Tier, Embedding Profile, Hybrid Retrieval, Federated Search, Search Projection
