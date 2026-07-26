# Samples Application Context

This application presents Chinook, Northwind, and Pagila as distinct sample products, with additional products added over time. Shared application capabilities connect them without treating their unrelated business concepts as one domain.

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. 🗣 Language](#1--language)

</details>

---

## 1. 🗣 Language

**Sample Product**:
One independently recognisable reference dataset and its user experience — currently Chinook, Northwind, or Pagila, with the set governed by the `SamplesProduct` enum and extended over time.
_Avoid_: tenant, customer account

**Product Domain**:
The bounded set of concepts belonging to one Sample Product. A Product Domain does not share business identity with another Product Domain.
_Avoid_: shared business domain, unified catalogue

**Product Import**:
The single pipeline that materialises and validates a Source Baseline in one Product Domain, for both first-time population and Product Reset.
_Avoid_: separate seeder, ad hoc restore

**Product Reset**:
A deliberate atomic restoration of one Product Domain to its Source Baseline. It discards that domain's local changes while preserving Domain Identities and leaving other Product Domains, the Core Application, and team-owned artefacts unchanged.
_Avoid_: replay, clone, rollback

**Reset Window**:
The bounded period beginning when a Reset Run is accepted, during which one Sample Product is unavailable while its Product Reset and derived-index validation complete.
_Avoid_: global maintenance, background refresh

**Reset Run**:
The durable record of one Product Reset attempt, including its progress, evidence, outcome, and any recovery needed before the Sample Product can return to service.
_Avoid_: invisible job, fire-and-forget reset

**Reset Evidence**:
The compact verification record attached to a Reset Run. It identifies the baseline and outcome without preserving discarded domain rows, Blob Assets, or field-level edits.
_Avoid_: backup, change journal

**Baseline Invariant**:
A condition that must hold before a Product Import may publish or a Sample Product may return to service.
_Avoid_: warning, best-effort check

**Core Application**:
The application-wide concerns shared by every Sample Product, including identity, teams, and common user-owned artefacts.
_Avoid_: fourth sample product

**Product Entitlement**:
Permission to enter a Sample Product. Product Entitlement is universal for authenticated users and is never granted through team membership.
_Avoid_: team licence, data tenancy

**Team Artefact**:
A team-visible Saved Search or Dashboard owned by one team and attributed to its creator, even after that creator's membership ends. It does not alter shared sample data or restrict a Sample Product.
_Avoid_: tenant copy, private dataset

**Team Owner**:
A member responsible for one team's membership and Team Artefacts. A Team Owner has no authority over shared sample data.
_Avoid_: global administrator, data curator

**Global Capability**:
A user-level authorization assignment for globally shared sample data or application operations. It is independent of team membership.
_Avoid_: team role, product licence

**Sample Curator**:
The Global Capability to edit or remove Source-Derived Entities in Sample Panels without running imports or Product Resets. It is granted per Sample Product through the `{product}_curator` role; a user may hold it for one, some, or all products. System Operator (`super_admin`) implies the capability for every Sample Product.
_Avoid_: team editor, system operator

**System Operator**:
The Global Capability to use Admin Panel operations, manage Product Imports and Product Resets, retry recovery, and inspect Reset Evidence. It includes Sample Curator authority.
_Avoid_: team owner, unrestricted user

**Reset Confirmation**:
The fresh authentication and product-specific acknowledgement by a System Operator that accepts a Product Reset or retry for the current Source Baseline Revision.
_Avoid_: modal click, generic warning

**Domain Entity**:
A business concept that belongs to exactly one Product Domain, even when another Sample Product uses the same word.
_Avoid_: globally shared entity

**Source-Derived Entity**:
A Domain Entity materialised from a Source Baseline and retaining a Source Identity. It may be changed or removed but is never created directly.
_Avoid_: user-created source row, synthetic source record

**Domain Identity**:
A stable, application-issued identity for a Domain Entity that survives restoring its Sample Product to its Source Baseline.
_Avoid_: source key, transient import key

**Sample Panel**:
The administrative experience dedicated to one Sample Product and its Domain Entities.
_Avoid_: shared admin

**Admin Panel**:
The application-wide administrative experience for cross-product operations, reporting, and navigation. It does not own Sample Product CRUD.
_Avoid_: overall product panel

**Product Portfolio**:
The Admin Panel overview of Sample Product health and labelled footprints. It does not unify unrelated business entities or KPIs.
_Avoid_: shared business dashboard, data warehouse

**Portfolio Card**:
A product-scoped display of one Reporting Read Model in the Product Portfolio. It may show native operational, catalogue, transaction, or financial facts but never a cross-product aggregate or comparison.
_Avoid_: shared KPI, global ranking

**Portfolio Configuration**:
The scope-specific selection and layout of allowed Portfolio Cards. A System Operator owns global defaults; teams arrange only their Team Dashboards without defining reporting logic.
_Avoid_: formula builder, team-wide global setting

**Product Portfolio Snapshot**:
The derived status of one Sample Product used by the Product Portfolio. It holds generic operational facts, not an analytical business entity.
_Avoid_: warehouse dimension, shared record

**Reporting Read Model**:
A purpose-built, product-labelled representation for one read use case. It creates neither shared business identity nor a write path.
_Avoid_: universal model, reporting source of truth

**Search Document**:
A product-labelled representation of one Domain Entity for lexical and semantic retrieval. It preserves the identity of its owning Product Domain.
_Avoid_: shared domain entity

**Search Surface**:
The approved non-sensitive text and metadata from a Domain Entity that may form its Search Document. It excludes raw Blob Assets, contact data, location data, and relationship-only rows.
_Avoid_: mirror record, all-fields index

**Search Tier**:
The retrieval level assigned to a Search Document: hybrid retrieval, lexical-only lookup, or exclusion from direct search.
_Avoid_: index setting, search free-for-all

**Embedding Profile**:
The pinned provider, model, dimensions, and normalisation policy used to generate compatible semantic vectors. A Search Document may only be compared with vectors from its Embedding Profile.
_Avoid_: provider fallback, mixed vectors

**Hybrid Retrieval**:
The rank fusion of separate lexical and semantic candidate lists using Reciprocal Rank Fusion. It does not compare raw FTS and vector scores.
_Avoid_: score blending, semantic tie-breaker

**Federated Search**:
An Admin Panel search across Product Domains that preserves product labels and routes every result to its owning Sample Panel.
_Avoid_: cross-domain join, universal resource

**Search Projection**:
The FTS and semantic-vector index materialised from a Search Document. Its lexical representation is transactionally current; its vector representation is derived after commit and independently verified.
_Avoid_: source of truth, opaque cache

**Pin Manifest**:
The versioned source record that defines a Source Baseline through its upstream revision, artifact, digest, and attribution.
_Avoid_: latest upstream, unverified download

**Source Artifact Cache**:
The local, verified copy of a Source Baseline that is available for reproducible import and Product Reset.
_Avoid_: live download, vendored database

**Source Baseline**:
The pinned, immutable upstream dataset from which a Sample Product can be restored.
_Avoid_: working copy, tenant dataset

**Source Baseline Revision**:
A reviewed version of a Source Baseline defined by one Pin Manifest. A Product Reset always restores its Sample Product to the current Source Baseline Revision.
_Avoid_: upstream head, user-selected version

**Source Identity**:
The exact identifier by which an upstream sample dataset recognises a record, including every component of a composite identifier. It remains distinct from the application's Domain Identity.
_Avoid_: application key, generated import key

**Source Identity Registry**:
The durable association between a Source Identity and the matching Domain Identity across independent resets of a Sample Product.
_Avoid_: import cache, source table

**Optional Association**:
A relationship that may be absent and, when present, is an explicit association within one Product Domain.
_Avoid_: nullable foreign key, empty relationship

**Restricted Relationship**:
A relationship whose related records must be resolved explicitly before removal; it never silently removes or clears another Domain Entity.
_Avoid_: cascade, implicit unlink

**Blob Asset**:
Binary content owned by one Product Domain and associated with a specific Domain Entity.
_Avoid_: global attachment, opaque field

**Derived Media Type**:
A recomputable classification of a Blob Asset's content, kept distinct from whatever type label its upstream source supplied.
_Avoid_: trusted source MIME type, permanent inference

**SQLite Extension Manifest**:
A reviewed record that identifies an approved native SQLite extension release, its supported platform assets, and their digests.
_Avoid_: latest binary, unpinned plugin

**SQLite Extension Cache**:
A locally verified copy of an approved SQLite Extension Manifest asset, available for trusted connection bootstrap without a request-time download.
_Avoid_: arbitrary dylib, live download

**Extension Support Matrix**:
The host operating system and CPU architectures eligible to use approved native SQLite extensions. Its initial members are macOS arm64 and Linux x86_64.
_Avoid_: runs anywhere, best-effort architecture

**Extension Connection Gate**:
The mandatory admission check for an eligible SQLite connection. A connection passes only when its approved native extension is available and its declared capability is demonstrably present.
_Avoid_: lazy loader, best-effort fallback

**Vector Capability Probe**:
The stable, non-mutating proof that an SQLite connection has the vector capability required by the active Embedding Profile.
_Avoid_: file-exists check, assumed compatibility

**Native Extension Fault**:
A detected breach of the Extension Connection Gate that makes the required vector capability unavailable. It makes the application database unavailable until corrected.
_Avoid_: degraded search, warning-only fault

**Offline Extension Diagnostics**:
The database-independent inspection that identifies a Native Extension Fault without relying on an application database connection.
_Avoid_: public debug page, request-time troubleshooting

**Extension Fault Response**:
The intentionally minimal HTTP response to a Native Extension Fault. It communicates unavailability and a Diagnostic Correlation without disclosing host or binary details.
_Avoid_: debug payload, transparent loader error

**Diagnostic Correlation**:
The stable identifier that links an Extension Fault Response to its private diagnostic record.
_Avoid_: stack trace, public environment detail

**Extension Health Report**:
The read-only private account of whether an Extension Connection Gate can admit an eligible SQLite connection and, if not, why.
_Avoid_: channel-specific diagnosis, repair log

**Extension Diagnostics Command**:
The Artisan command that renders an Extension Health Report for operators and automation without mutating the cache or application database.
_Avoid_: repair command, debug endpoint

**Extension Sync Command**:
The explicit Artisan command that brings the local SQLite Extension Cache into conformity with the SQLite Extension Manifest for the current Extension Support Matrix entry.
_Avoid_: Composer hook, request-time installer

**Extension Cache Root**:
The application-private storage location at `storage/app/sqlite-extensions/` that holds disposable, verified SQLite Extension Cache assets.
_Avoid_: vendor directory, host-global cache

**Native Extension Verification Matrix**:
The layered evidence required to accept a supported platform's native SQLite extension capability across manifest trust, Laravel connections, Herd CLI/HTTP, Pest, and CI.
_Avoid_: mock-only proof, manual-only check

**Acceptance Gate**:
A non-negotiable condition that must be satisfied before an implementation is ready to accept. Every Acceptance Gate has named evidence.
_Avoid_: advisory checklist, best-effort test

**Acceptance Evidence**:
The repeatable result that demonstrates one Acceptance Gate has passed in its required environment.
_Avoid_: assumed coverage, informal confidence

**Acceptance Stage**:
One risk-ordered delivery increment whose required Acceptance Gates must pass before the next increment begins.
_Avoid_: big-bang phase, informal milestone

**Baseline Evidence Fixture**:
The reviewed, manifest-backed expectation for one Source Baseline's artifact, transformed schema, identities, counts, and declared normalization outcomes.
_Avoid_: full database copy, sampled confidence check

**Reset Isolation Proof**:
The repeatable evidence that a Product Reset succeeds or fails without altering other Product Domains, the Core Application, or Team Artefacts.
_Avoid_: happy-path reset, transaction-only claim

**Authorization Acceptance Matrix**:
The reviewed role, action, and panel proof that exercises every authorization boundary through both its policy and user-facing action. It never treats Team Artefact scope as a filter on shared sample data.
_Avoid_: policy-only coverage, assumed panel denial

**Golden Search Corpus**:
The reviewed, versioned set of product-labelled search queries and expected ownership, relevance, filter, and route outcomes. It uses exact assertions for deterministic lexical retrieval and top-k relevance assertions for semantic or hybrid retrieval.
_Avoid_: unrepeatable demos, exact semantic ordering

**Two-Environment Operational Gate**:
The acceptance requirement that every change pass the reproducible Linux CI verification path and that each release candidate also retain evidence from the supported Herd macOS arm64 CLI and HTTP verification path.
_Avoid_: CI-only proof, informal local check

**Implementation-Readiness Dossier**:
The version-controlled operational record that maps each approved decision to its acceptance gates, automated checks, operator commands, evidence location, and recovery procedure. Generated evidence remains in CI or release artifacts rather than the repository.
_Avoid_: scattered runbooks, issue-only operations manual

**Global Role Administration**:
The Admin Panel capability to create and assign runtime-managed roles that group Global Capabilities, using the convention `super_admin` plus one `{product}_curator` role per Sample Product. The convention is documented, not code-locked. It does not manage Team Ownership, team membership, or universal Product Entitlement.
_Avoid_: tenant administration, per-product licence management

**Supplementary Activity**:
An append-only record of a selected user, security, or operator event that adds audit context without establishing Product Import or Product Reset truth.
_Avoid_: Reset Evidence, operational source of truth
