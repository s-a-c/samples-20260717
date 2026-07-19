# Upstream Sample Datasets Re-verification (T4.0 / #27)

Re-verification date: **2026-07-19**.
Original audit (decision #4): 2026-07-17, recorded in
[`research/sample-sources.md`](https://github.com/s-a-c/samples-20260717/blob/research/sample-sources/research/sample-sources.md)
(commit `b88045c365fbec43e47731516934965d91a3c603` on branch
`research/sample-sources`).

The two days between audit and re-verification are short, but this check
establishishes the verification contract (commit SHA + Git blob SHA + SHA-256
triple) that T4.1's importer must enforce on every reset, and records the
upstream's actual quiescence profile so T4.1 can decide how defensively to
cache/mirror artifacts.

## Summary table

| Dataset   | Upstream repo                         | Pinned rev (commit SHA)                | Current HEAD (default branch)          | Drift since pin | Drift since 2026-07-17 audit | Digest match |
| --------- | ------------------------------------- | -------------------------------------- | -------------------------------------- | --------------- | ---------------------------- | ------------ |
| Chinook   | `lerocha/chinook-database`            | `7f67772503d71ba90f19283c38e93923addb43fa` | `7f67772503d71ba90f19283c38e93923addb43fa` (`master`) | 0 commits       | 0 commits                    | YES          |
| Northwind | `jpwhite3/northwind-SQLite3`          | `4f56e7f5906dfd23b25244c5bfe8fb5da6402efd` | `4f56e7f5906dfd23b25244c5bfe8fb5da6402efd` (`main`)   | 0 commits       | 0 commits                    | YES          |
| Sakila    | `bradleygrant/sakila-sqlite3`         | `9394b42d13888c3d3d3d56cd7e9c84fadafb71c7` | `9394b42d13888c3d3d3d56cd7e9c84fadafb71c7` (`main`)   | 0 commits       | 0 commits                    | YES          |

All three pinned commits are at the tip of their respective default branches.
`compare/<pin>...HEAD` returned `status: identical`, `ahead_by: 0`,
`behind_by: 0`, `total_commits: 0` for each repo.

All three artifact SHA-256 digests computed locally from a fresh download match
the pins in #4 byte-for-byte. All three Git blob SHAs reported by the GitHub
contents API at the pinned refs match the pins in #4.

## Chinook detail

- **Upstream repo:** [`lerocha/chinook-database`](https://github.com/lerocha/chinook-database)
  (default branch `master`, sample database for SQL Server, Oracle, MySQL,
  PostgreSQL, SQLite, DB2).
- **Pinned revision:** [`7f67772503d71ba90f19283c38e93923addb43fa`](https://github.com/lerocha/chinook-database/commit/7f67772503d71ba90f19283c38e93923addb43fa)
  — "Fix DB2 initialization timing with background script", committed
  **2025-10-05T23:24:51Z**.
- **Current HEAD (`master`):** `7f67772503d71ba90f19283c38e93923addb43fa` —
  identical to the pin. Last `pushedAt`: 2025-10-05.
- **Artifact URL (immutable):** [`ChinookDatabase/DataSources/Chinook_Sqlite.sql` @ pinned commit](https://github.com/lerocha/chinook-database/blob/7f67772503d71ba90f19283c38e93923addb43fa/ChinookDatabase/DataSources/Chinook_Sqlite.sql).
- **Artifact size (observed):** 595,545 bytes.
- **Git blob SHA (computed via API at pin):** `299610dc69f04a2b16b3a2e45bd2af82ed2872a2`.
- **Git blob SHA (pinned in #4):** `299610dc69f04a2b16b3a2e45bd2af82ed2872a2`. **Match.**
- **Artifact SHA-256 (computed 2026-07-19):** `caf31d698a4a79c628215b552dfe6575e71be052ae02b8f18e763498f55f5d44`.
- **Artifact SHA-256 (pinned in #4):** `caf31d698a4a79c628215b552dfe6575e71be052ae02b8f18e763498f55f5d44`. **Match.**
- **Upstream activity since #4 audit:** zero commits on `master`, zero commits
  to `README.md`, zero commits to `LICENSE.md`, zero commits to
  `ChinookDatabase/DataSources/Chinook_Sqlite.sql`. No new releases; latest
  release remains `v1.4.5` (2024-02-12), which is the declared dataset/script
  version recorded in #4.
- **LICENSE.md at pin:** blob `7487a9edc2d42e50d7a38ab1fbdba33ac63230f7`, 1,117 bytes — unchanged.
- **Open issues of interest (none blocking):** #55 "Naming conventions for
  tables and fields", #54 "Add SQL analytics queries", #53 "Add SQL query
  examples", #50 "add chinook script for bigquery", #48 "Any chance to sponsor
  this repo?". None propose schema changes to the SQLite variant.
- **Notes:**
  - The `v1.4.5` tag points at commit `4a944a942426e1f3263fe539155fb7ef92b04b4a`,
    which is **older** than the pinned HEAD. The 23 commits between `v1.4.5`
    and the pin are infrastructure-only (DB2 docker init, VS Code configs,
    `.gitattributes` introduction, library upgrades, README touch-ups); none
    alter the dataset content. **However**, `git compare v1.4.5...<pin>` reports
    `Chinook_Sqlite.sql` as modified `15902+/15902-` — a wholesale line rewrite
    caused by the `.gitattributes`-driven CRLF→LF normalization introduced in
    that range. The dataset rows and DDL are unchanged; only the line endings
    differ. **Implication for T4.1:** the importer must fetch `Chinook_Sqlite.sql`
    by **commit SHA**, never by tag. Resolving by `@v1.4.5` would return a file
    with a different SHA-256 (CRLF variant) and the digest assertion would fail.
    The pin triple in #4 is correct and self-consistent; do not relax it to a tag.
  - `Chinook_Sqlite.sqlite` (the binary prebuilt) also exists at the pinned
    commit with zero `additions/deletions` between tag and HEAD — it was not
    regenerated when the SQL script was re-line-ended. This reinforces that the
    line-ending pass was cosmetic and the data is unchanged.

## Northwind detail

- **Upstream repo:** [`jpwhite3/northwind-SQLite3`](https://github.com/jpwhite3/northwind-SQLite3)
  (default branch `main`, "SQLite3 version of Microsoft's Northwind Database").
- **Pinned revision:** [`4f56e7f5906dfd23b25244c5bfe8fb5da6402efd`](https://github.com/jpwhite3/northwind-SQLite3/commit/4f56e7f5906dfd23b25244c5bfe8fb5da6402efd)
  — "Merge pull request #20 from rmgas1/main — fix(reports.sql): fix parse
  errors in SQLite 3.41.0+", committed **2025-01-15T18:25:44Z**.
- **Current HEAD (`main`):** `4f56e7f5906dfd23b25244c5bfe8fb5da6402efd` —
  identical to the pin. Last `pushedAt`: 2025-01-15.
- **Artifact URL (immutable):** [`dist/northwind.db` @ pinned commit](https://github.com/jpwhite3/northwind-SQLite3/blob/4f56e7f5906dfd23b25244c5bfe8fb5da6402efd/dist/northwind.db).
- **Artifact size (observed):** 24,702,976 bytes (~23.6 MiB).
- **Git blob SHA (computed via API at pin):** `da01968c34c9ad507f89b7a2e31121b53720287d`.
- **Git blob SHA (pinned in #4):** `da01968c34c9ad507f89b7a2e31121b53720287d`. **Match.**
- **Artifact SHA-256 (computed 2026-07-19):** `2f4f5c68dfcd33ba27373eae48c7a4869800c68095ee0f9f0da494f83382a877`.
- **Artifact SHA-256 (pinned in #4):** `2f4f5c68dfcd33ba27373eae48c7a4869800c68095ee0f9f0da494f83382a877`. **Match.**
- **Upstream activity since #4 audit:** zero commits on `main`, zero commits to
  `README.md`, zero commits to `LICENSE`, zero commits to `dist/northwind.db`.
  No new releases; the only release is `v0.1.0` (2022-08-29). There is a
  `v1.0.1` tag pointing at `205fc66f31fe62a2ae7d8985d708596299b01086`, which
  is older than the pinned HEAD.
- **LICENSE at pin:** blob `7b784d8b065952c289f6fe51adf74ed780c4d996`, 1,075 bytes — unchanged (MIT, JP White 2016).
- **Open issues of interest (none blocking):** #21 "Alx assignment: add SQL
  exercise solutions", #19 "Northwind Online version!", #11 "Countries with ISO
  codes", #10 "Providing a copy of the larger dataset in form of a release".
  None propose schema/data changes to the committed `dist/northwind.db`.
- **Notes:**
  - The repo is functionally dormant: last push was January 2025, last release
    was 2022. The pinned HEAD is the latest meaningful state.
  - Issue #10 asks for the larger dataset to be shipped as a release; that has
    not happened, and the committed `dist/northwind.db` (the 23.6 MiB artifact
    pinned in #4) remains the canonical source.

## Sakila detail

- **Upstream repo:** [`bradleygrant/sakila-sqlite3`](https://github.com/bradleygrant/sakila-sqlite3)
  (default branch `main`, "Sakila Sample Database - SQLite3 Port"). Repo name
  ends in `-sqlite3`; do **not** confuse with the older
  `bradleygrant/sakila` (different repo) or any `sakila-sqlite` (no `3`) fork.
- **Pinned revision:** [`9394b42d13888c3d3d3d56cd7e9c84fadafb71c7`](https://github.com/bradleygrant/sakila-sqlite3/commit/9394b42d13888c3d3d3d56cd7e9c84fadafb71c7)
  — "Updated description of About Sakila in README", committed
  **2020-12-23T09:00:59Z**.
- **Current HEAD (`main`):** `9394b42d13888c3d3d3d56cd7e9c84fadafb71c7` —
  identical to the pin. Last `pushedAt`: 2020-12-23.
- **Artifact URL (immutable):** [`sakila_master.db` @ pinned commit](https://github.com/bradleygrant/sakila-sqlite3/blob/9394b42d13888c3d3d3d56cd7e9c84fadafb71c7/sakila_master.db).
- **Artifact size (observed):** 5,791,744 bytes (~5.5 MiB).
- **Git blob SHA (computed via API at pin):** `248a237e7e51a3974e48ad87230605b1da0f356e`.
- **Git blob SHA (pinned in #4):** `248a237e7e51a3974e48ad87230605b1da0f356e`. **Match.**
- **Artifact SHA-256 (computed 2026-07-19):** `88c91a4a1a6b61f9d3f35904c0a173c887b25e73f20c3c2fdb073818c06f4268`.
- **Artifact SHA-256 (pinned in #4):** `88c91a4a1a6b61f9d3f35904c0a173c887b25e73f20c3c2fdb073818c06f4268`. **Match.**
- **Upstream activity since #4 audit:** zero commits on `main`, zero commits to
  `README.md`, zero commits to `LICENSE`, zero commits to `sakila_master.db`.
  No releases, no tags.
- **LICENSE at pin:** blob `589ff3d3d68282f1e2d044d20aac15381fb7c6f7`, 1,521 bytes — unchanged (BSD 3-Clause, Bradley Grant 2020).
- **Open issues:** none. The repo has zero open issues and zero open PRs.
- **Notes:**
  - This upstream is the most dormant of the three: last push December 2020,
    more than five years before the audit. Drift risk is negligible.
  - **Licensing caveat for T4.1:** The BSD 3-Clause license on this SQLite port
    covers the port itself. The underlying Sakila schema and sample data
    originate from MySQL AB / Oracle (Mike Hillyer's original Sakila). The
    README and schema header credit the MySQL documentation team. Oracle has
    not, to our knowledge, asserted a restrictive license on Sakila sample
    data — it is distributed as a sample/test fixture in the MySQL ecosystem —
    but T4.1 should (a) preserve the upstream's BSD notice, (b) retain the
    attribution credits to MySQL/Hillyer/Grant, and (c) ship a third-party
    notices page that names all three lineages. Redistribution of the SQLite
    `.db` artifact alongside the application is permitted under BSD; the only
    residual concern is the unspoken MySQL/Oracle provenance, which is
    mitigated by clear attribution and is industry-standard practice for Sakila
    ports.

## Drift findings

**None material.** All three upstreams are unchanged between the 2026-07-17
audit and this 2026-07-19 re-verification:

1. **Commit-level drift:** every pinned commit is still the tip of its default
   branch. `compare/<pin>...HEAD` returns `identical` / 0 commits ahead / 0
   behind for Chinook, Northwind, and Sakila.
2. **Artifact drift:** every canonical artifact's Git blob SHA at the pinned
   commit still matches #4, and every freshly downloaded artifact's SHA-256
   matches #4. No bytes have changed.
3. **License/README drift:** no commits to any `LICENSE`/`LICENSE.md` or
   `README.md` since the audit.
4. **Release/tag drift:** no new releases or tags on any of the three repos.
5. **Open-issue signals:** no open issue or PR on any upstream proposes a
   schema or data change to the pinned variants.

**One non-material observation worth recording for T4.1:** the Chinook
`v1.4.5` tag is **not** byte-equivalent to the pinned commit for the SQL
artifact. Between the tag (commit `4a944a94`) and the pinned HEAD
(`7f67772`), a `.gitattributes` introduction forced a CRLF→LF normalization
pass over `Chinook_Sqlite.sql`. The 15,902-line "diff" between tag and pin is
purely line-ending reformatting; the dataset content (rows, DDL, constraints)
is identical. The pin triple (commit SHA + blob SHA `299610dc…` + SHA-256
`caf31d69…`) captures the LF-normalized variant and is internally consistent.
T4.1 must resolve the artifact by commit SHA, not by tag, or the digest check
will fail.

## Open risks for T4.1

1. **Do not relax the pin triple.** Every artifact resolves today and every
   digest matches, but the Chinook v1.4.5 line-ending episode shows that even
   "stable" upstreams can have cosmetic refactors that break a tag-based pin.
   The importer must fetch by commit SHA and assert all three of: commit SHA,
   Git blob SHA, and SHA-256.
2. **No upstream mirror is required today.** All three upstreams are reachable
   via raw GitHub. Sakila and Northwind are dormant (last push 2020-12 and
   2025-01 respectively); Chinook last pushed 2025-10. A defensive vendor
   mirror is low-priority. The import contract from #4 (Section 6.1) already
   requires "Fetch only immutable commit URLs and verify SHA-256 before import"
   — that contract is sufficient.
3. **Sakila MySQL/Oracle provenance.** BSD-licensed port, but the underlying
   Sakila data traces to MySQL/Oracle. Standard industry practice is to
   redistribute with attribution; no Oracle enforcement is known. T4.1 must
   carry attribution forward into the app's third-party notices page.
4. **Northwind binary BLOB columns** (`Categories.Picture`, `Employees.Photo`)
   and the **23.6 MiB committed `dist/northwind.db`** make a tag-based or
   HEAD-based fetch dangerous: any future repo force-push or rebuild would
   silently change bytes. Commit SHA + SHA-256 covers this; do not weaken it.
5. **No gaps or broken upstreams.** Every URL resolves, every commit resolves,
   every artifact downloads, every digest matches. T4.1 can proceed with the
   pin manifest from #4 unchanged.

## Sources

All checks performed 2026-07-19 via the GitHub REST API (`gh api`) and raw
GitHub downloads.

- Decision #4: https://github.com/s-a-c/samples-20260717/issues/4
- Audit document (branch `research/sample-sources`, commit `b88045c`):
  https://github.com/s-a-c/samples-20260717/blob/research/sample-sources/research/sample-sources.md
- Ticket #27: https://github.com/s-a-c/samples-20260717/issues/27

Chinook:

- Repo: https://github.com/lerocha/chinook-database — `pushedAt` 2025-10-05T23:25:42Z, `updatedAt` 2026-07-19T08:04:05Z.
- Pinned commit: https://github.com/lerocha/chinook-database/commit/7f67772503d71ba90f19283c38e93923addb43fa (HEAD of `master`).
- Artifact: https://github.com/lerocha/chinook-database/blob/7f67772503d71ba90f19283c38e93923addb43fa/ChinookDatabase/DataSources/Chinook_Sqlite.sql
- Latest release: https://github.com/lerocha/chinook-database/releases/tag/v1.4.5 (2024-02-12, predates pin).

Northwind:

- Repo: https://github.com/jpwhite3/northwind-SQLite3 — `pushedAt` 2025-01-15T18:25:44Z, `updatedAt` 2026-07-15T17:30:10Z.
- Pinned commit: https://github.com/jpwhite3/northwind-SQLite3/commit/4f56e7f5906dfd23b25244c5bfe8fb5da6402efd (HEAD of `main`).
- Artifact: https://github.com/jpwhite3/northwind-SQLite3/blob/4f56e7f5906dfd23b25244c5bfe8fb5da6402efd/dist/northwind.db
- Latest release: https://github.com/jpwhite3/northwind-SQLite3/releases/tag/v0.1.0 (2022-08-29).

Sakila:

- Repo: https://github.com/bradleygrant/sakila-sqlite3 — `pushedAt` 2020-12-23T09:01:06Z, `updatedAt` 2026-06-09T23:59:05Z.
- Pinned commit: https://github.com/bradleygrant/sakila-sqlite3/commit/9394b42d13888c3d3d3d56cd7e9c84fadafb71c7 (HEAD of `main`).
- Artifact: https://github.com/bradleygrant/sakila-sqlite3/blob/9394b42d13888c3d3d3d56cd7e9c84fadafb71c7/sakila_master.db
- No releases, no tags.
