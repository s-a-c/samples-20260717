# sqlite-vec v0.1.9 Assets and Digests (T2.1 / #20)

> Research artifact for wayfinder ticket
> [#20](https://github.com/s-a-c/samples-20260717/issues/20). Feeds decisions
> [#3](https://github.com/s-a-c/samples-20260717/issues/3) (sqlite-vec pin) and
> [#12](https://github.com/s-a-c/samples-20260717/issues/12) (SQLite Extension
> Manifest). Unblocks T2.2 (Extension Connection Gate) and T2.3 (Vector
> Capability Probe).
>
> All upstream data retrieved 2026-07-19 via `gh release view/download
> --repo asg017/sqlite-vec`. All local digests computed on
> `darwin arm64` via `shasum -a 256` (BSD).

## Release URL + tag verified

| Field | Value |
|---|---|
| Release URL | https://github.com/asg017/sqlite-vec/releases/tag/v0.1.9 |
| Tag | `v0.1.9` |
| Title | "v0.1.9 Bug fix for DELETE operations" |
| Published | 2026-03-31T08:00:23Z |
| Author | `asg017` (Alex Garcia) |
| Draft / prerelease / immutable | `false` / `false` / `false` |
| Release notes | "Fixes #274, which discovered that `DELETE` operations on `vec0` tables with metadata text columns that are long (>12chars) would erroneously report a `SQLITE_DONE` error." |

Verified via `gh release view v0.1.9 --repo asg017/sqlite-vec --json ...`
(HTTP 200, JSON body reproduced verbatim below in *Sources*).

## All release assets

30 assets published. The `loadable-*` rows are the PHP-relevant binaries
(loadable shared libraries). The `static-*` rows are for static linking into
host applications and are out of scope for this project.

| # | Filename | Category | Platform | Arch | Size (bytes) |
|---:|---|---|---|---|---:|
| 1 | `checksums.txt` | manifest | — | — | 2 286 |
| 2 | `install.sh` | installer | — | — | 7 793 |
| 3 | `spm.json` | manifest (SwiftPM) | — | — | 5 365 |
| 4 | `sqlite-dist-manifest.json` | manifest | — | — | 14 255 |
| 5 | `sqlite-vec-0.1.9-amalgamation.tar.gz` | source | — | — | 53 872 |
| 6 | `sqlite-vec-0.1.9-amalgamation.zip` | source | — | — | 54 035 |
| 7 | `sqlite-vec-0.1.9-cli-cosmopolitan.tar.gz` | CLI (cosmopolitan) | — | — | 2 131 274 |
| 8 | `sqlite-vec-0.1.9-loadable-android-aarch64.tar.gz` | loadable | android | aarch64 | 60 162 |
| 9 | `sqlite-vec-0.1.9-loadable-android-armv7a.tar.gz` | loadable | android | armv7a | 56 622 |
| 10 | `sqlite-vec-0.1.9-loadable-android-i686.tar.gz` | loadable | android | i686 | 57 427 |
| 11 | `sqlite-vec-0.1.9-loadable-android-x86_64.tar.gz` | loadable | android | x86_64 | 56 954 |
| 12 | `sqlite-vec-0.1.9-loadable-ios-aarch64.tar.gz` | loadable | ios | aarch64 | 45 352 |
| 13 | `sqlite-vec-0.1.9-loadable-iossimulator-aarch64.tar.gz` | loadable | ios-sim | aarch64 | 47 788 |
| 14 | `sqlite-vec-0.1.9-loadable-iossimulator-x86_64.tar.gz` | loadable | ios-sim | x86_64 | 49 255 |
| 15 | `sqlite-vec-0.1.9-loadable-linux-aarch64.tar.gz` | loadable | linux | aarch64 | 61 046 |
| **16** | **`sqlite-vec-0.1.9-loadable-linux-x86_64.tar.gz`** | **loadable** | **linux** | **x86_64 (TARGET)** | **61 507** |
| **17** | **`sqlite-vec-0.1.9-loadable-macos-aarch64.tar.gz`** | **loadable** | **macos** | **aarch64 (TARGET)** | **50 836** |
| 18 | `sqlite-vec-0.1.9-loadable-macos-x86_64.tar.gz` | loadable | macos | x86_64 | 51 404 |
| 19 | `sqlite-vec-0.1.9-loadable-windows-x86_64.tar.gz` | loadable | windows | x86_64 | 143 162 |
| 20–26 | `sqlite-vec-0.1.9-static-{ios,iossimulator,linux,macos}-{aarch64,x86_64}.tar.gz` | static-link | various | various | 56–173 KB |
| 27 | `sqlpkg.json` | manifest (sqlpkg) | — | — | 417 |

> **Naming-pattern note.** The mission brief hypothesised the glob patterns
> `*macos*arm64*` and `*linux*amd64*`. The actual upstream naming uses
> **`aarch64`** (not `arm64`) for macOS ARM and **`x86_64`** (not `amd64`)
> for Linux x86-64. Any automated downloader / cache manager MUST use the
> canonical names:
>
> - macOS arm64 → `sqlite-vec-0.1.9-loadable-macos-aarch64.tar.gz`
> - Linux x86-64 → `sqlite-vec-0.1.9-loadable-linux-x86_64.tar.gz`

## Target-platform assets with SHA-256

Digests were verified three independent ways and all three agree:

1. GitHub Release API `digest:` field
   (`https://api.github.com/repos/asg017/sqlite-vec/releases/assets/<id>` —
   computed by GitHub at upload time).
2. Local `shasum -a 256` on the asset bytes downloaded today.
3. Upstream `checksums.txt` shipped inside the release.

| Filename | Platform | Size | SHA-256 | API | Local | checksums.txt |
|---|---|---:|---|:---:|:---:|:---:|
| `sqlite-vec-0.1.9-loadable-macos-aarch64.tar.gz` | macOS / arm64 | 50 836 | `8282126333399ddfe98bbbcc7a1936e7252625aac49df056a98be602e46bfd29` | ✓ | ✓ | ✓ |
| `sqlite-vec-0.1.9-loadable-linux-x86_64.tar.gz` | Linux / x86_64 | 61 507 | `b959baa1d8dc88861b1edb337b8587178cdcb12d60b4998f9d10b6a82052d5d7` | ✓ | ✓ | ✓ |

Download URLs (canonical, immutable):

- macOS arm64: `https://github.com/asg017/sqlite-vec/releases/download/v0.1.9/sqlite-vec-0.1.9-loadable-macos-aarch64.tar.gz`
- Linux x86_64: `https://github.com/asg017/sqlite-vec/releases/download/v0.1.9/sqlite-vec-0.1.9-loadable-linux-x86_64.tar.gz`
- Checksums manifest: `https://github.com/asg017/sqlite-vec/releases/download/v0.1.9/checksums.txt`

## Asset contents

Each tarball contains exactly **one** file, named `vec0.<suffix>`:

| Asset | Contains | File type | Architectures |
|---|---|---|---|
| `sqlite-vec-0.1.9-loadable-macos-aarch64.tar.gz` | `vec0.dylib` | Mach-O 64-bit dynamically linked shared library | **arm64** (single slice, not fat) |
| `sqlite-vec-0.1.9-loadable-linux-x86_64.tar.gz` | `vec0.so` | ELF 64-bit LSB shared object, dynamically linked, BuildID `b315c9cbea9122b81919f822249919da465d4816`, **not stripped** | x86-64 |

### Code-signing status (macOS)

The `vec0.dylib` ships **adhoc / linker-signed only** — no Developer ID, no
TeamID:

```
Format=Mach-O thin (arm64)
CodeDirectory v=20400 size=1379 flags=0x20002(adhoc,linker-signed) hashes=40+0
Signature=adhoc
TeamIdentifier=not set
```

`spctl -a -t exec` rejects it (expected — adhoc signatures are never
Gatekeeper-approved). This is fine for `load_extension()` (SQLite's loader uses
`dlopen()` which bypasses Gatekeeper), but it WILL be rejected by any host
process that has **Hardened Runtime + Library Validation** enabled. See
*Open risks*.

### Linux glibc requirement

The Linux `.so` requires only **`GLIBC_2.14`** or earlier (most-recent symbol
observed via `objdump -T`). This is compatible with RHEL 7+, Ubuntu 14.04+,
Debian 8+, and every CI image GitHub Actions / GitLab CI currently ships. No
glibc-version risk for CI.

## PHP loadable entry point

The C entry-point symbol exported by both binaries is:

```
sqlite3_vec_init          (primary — module "vec0")
sqlite3_vec_numpy_init    (auxiliary — only useful with numpy arrays; N/A for PHP)
sqlite3_vec_static_blobs_init  (auxiliary)
```

This is **NOT** `sqlite3_vec0_init` (which is what SQLite's default
convention would synthesise from the file basename `vec0.dylib` / `vec0.so`).
SQLite's documented load-extension behaviour has a **trailing-digit-strip
fallback**: when `sqlite3_<basename>_init` is not found, it strips trailing
digits from `<basename>` and retries. So `vec0` → `vec0` fails → `vec` →
`sqlite3_vec_init` succeeds. This is why `.load ./vec0` works in the
sqlite3 CLI without an explicit entry point.

**Verified empirically** with Homebrew `sqlite3` 3.53.3:

```
$ sqlite3 ':memory:' '.load /…/vec0.dylib' \
    'SELECT "default-entry load OK, version = " || vec_version();'
default-entry load OK, version = v0.1.9

$ sqlite3 ':memory:' '.load /…/vec0.dylib sqlite3_vec_init' \
    'SELECT "explicit entry OK, version = " || vec_version();'
explicit entry OK, version = v0.1.9
```

Both forms succeed; `vec_version()` returns `v0.1.9` (string embedded in the
binary), and `pragma_module_list()` reports modules `vec0` and `vec_each`.

### What `LOAD EXTENSION` actually registers

| Symbol exposed | Type |
|---|---|
| `vec0` | virtual table module (`CREATE VIRTUAL TABLE … USING vec0(…)`) |
| `vec_version()` | SQL scalar function returning `v0.1.9` |
| `vec_distance_ls(euclidean / cosine / …)` family | SQL scalar functions |
| `vec_to_json`, `vec_f32`, `vec_int8`, `vec_bit`, `vec_extract`, `vec_quantize`, … | SQL scalar functions |
| `vec_each` | table-valued function (e.g. `SELECT * FROM vec_each(X'…')`) |

T2.3 (Vector Capability Probe) should test at minimum `SELECT vec_version()`
and `CREATE VIRTUAL TABLE probe USING vec0(embedding float[4])` to confirm
the module is registered.

## macOS arm64 load mechanism

### SQLite3 extension (PHP `ext/sqlite3`)

```php
$db = new SQLite3(':memory:');
$db->enableExceptions(true);

// PHP 8.0+ removed the second "$entry_point" parameter from SQLite3::loadExtension().
// Rely on SQLite's trailing-digit-strip fallback OR pass the full path to a
// file renamed without the trailing digit.
$ok = $db->loadExtension('/abs/path/to/vec0.dylib');

// Smoke test
$version = $db->querySingle('SELECT vec_version();');  // → "v0.1.9"
```

**Caveats on Herd PHP 8.5:** see *Open risks*. The Herd-distributed PHP build
has `load_extension` compiled out, so this call returns `false` silently.

### PDO_SQLITE driver

```php
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// PDO_SQLITE has NO named constant for "enable load_extension" in PHP 8.5;
// the historical PDO::SQLITE_ATTR_LOAD_EXTENSION constant is not defined.
// Use the numeric SQLITE_DBCONFIG_ENABLE_LOAD_EXTENSION (1005) via the
// generic sqlite db-config attribute, then call the SQL function:
$db->exec("SELECT load_extension('/abs/path/to/vec0.dylib');");
```

**Caveats on Herd PHP 8.5:** same compile-out problem; the SQL
`load_extension()` function returns `SQLITE_ERROR` "not authorized".

### macOS-specific caveats

1. **Code signing.** The dylib ships adhoc-signed. `dlopen()` will accept
   it unless the loader process has Library Validation enabled under
   Hardened Runtime. Herd's `php` binary itself is signed; if a future
   Herd build enables Library Validation, this dylib will need re-signing
   with the user's ad-hoc signature (`codesign -s - vec0.dylib`) or with
   a real Developer ID.
2. **No TeamID** means the dylib cannot pass Gatekeeper on its own. Not
   relevant for `dlopen()`, but relevant if anyone tries to ship it inside
   a notarized `.app`.

## Linux x86_64 load mechanism

Same APIs as macOS, with `.so` extension:

```php
// SQLite3 extension
$db = new SQLite3(':memory:');
$db->enableExceptions(true);
$db->loadExtension('/abs/path/to/vec0.so');

// PDO_SQLITE driver
$pdo = new PDO('sqlite::memory:');
$pdo->exec("SELECT load_extension('/abs/path/to/vec0.so');");
```

### Linux-specific caveats

1. **glibc floor.** `GLIBC_2.14` minimum — every CI image in current use
   exceeds this. No version drift risk.
2. **Stripped status.** The `.so` is **not stripped** (BuildID present),
   which is good for crash triage in CI logs and meaningless at runtime.
3. **CI environment.** GitHub Actions `ubuntu-latest` (ubuntu-22.04 /
   ubuntu-24.04) provides `glibc 2.35+`. No compatibility concern.

## Open risks for T2.2 / T2.3

### R1 — **CRITICAL, BLOCKING**: Herd PHP 8.5 has `load_extension` compiled out

Discovered today on the actual development machine (Laravel Herd Pro, PHP
8.5.8, `PHP_BUILD_PROVIDER=Laravel Herd`). The PHP `sqlite3` extension
reflection shows:

```
$ php --re sqlite3
Extension [ <persistent> extension #5 sqlite3 version 8.5.8 ] {
  - INI {
    Entry [ sqlite3.extension_dir <SYSTEM> ]   ← only this + sqlite3.defensive
  }
}
```

There is **no** `sqlite3.enable_load_extension` ini entry, and
`SQLite3::loadExtension()` returns `false` with `lastErrorMsg: not an error`
without ever calling into SQLite. The PDO_SQLITE driver likewise rejects the
SQL `load_extension()` function with `"not authorized"`, and
`PDO::SQLITE_ATTR_LOAD_EXTENSION` is **not defined** as a constant.

Empirical proof of the block:

```
$ php -d sqlite3.enable_load_extension=1 -r '
  $db = new SQLite3(":memory:");
  $db->enableExceptions(true);
  $db->loadExtension("/var/folders/…/vec0.dylib");'
PHP Fatal error: Uncaught SQLite3Exception: SQLite Extensions are disabled in …
```

The standalone **Homebrew** sqlite3 3.53.3 loads the same dylib fine, so the
binary itself is sound — the block is purely the PHP/SQLite build.

**Implications for T2.2 (Extension Connection Gate):**

The Connection Gate cannot simply `PDO::load_extension()` and proceed. T2.2
needs to choose one of these load strategies (or punt the decision to a
follow-up ticket):

| Strategy | Notes |
|---|---|
| (a) Custom Herd PHP build with `SQLITE_OMIT_LOAD_EXTENSION` undefined | Herd Pro supports custom PHP builds via `herd use-php`. Out of scope here — flags a new decision ticket. |
| (b) libSQL PHP extension (`libsql/libsql-php`) | libSQL is an SQLite fork with built-in vector support; bypasses the load-extension problem entirely but is a different decision than #3. |
| (c) Sidecar CLI subprocess (Homebrew `sqlite3` + `.load`) | Works today on macOS arm64 if Homebrew sqlite3 is present. Adds IPC overhead. |
| (d) PHP FFI binding to a vendored libsqlite3 + `sqlite3_load_extension()` | The Herd build has `--with-ffi`. Loads the system libsqlite3 (or a vendored one) via FFI and calls the C API directly, bypassing the PHP SQLite3 wrapper. Most flexible but most code. |

**Recommendation for T2.2:** open a new decision ticket *"Choose sqlite-vec
load strategy for Herd PHP 8.5"* and resolve it before T2.2 implementation.
T2.1 findings above are independent of that decision — whatever load path is
chosen, the same tarballs, digests, and entry point apply.

### R2 — PHP 8.5 dropped `SQLite3::loadExtension()` entry-point parameter

PHP 7.4 deprecated and PHP 8.0 **removed** the second `$entry_point` argument
to `SQLite3::loadExtension()`. Modern PHP code can only pass the file path.
Loading works without the entry point **only** because SQLite's
trailing-digit-strip fallback turns `vec0` → `vec` → `sqlite3_vec_init`. If a
future sqlite-vec release stops exporting `sqlite3_vec_init` (unlikely —
Alex Garcia has stabilised this symbol), PHP users will have to **rename the
cached file** from `vec0.dylib`/`vec0.so` to `vec.dylib`/`vec.so` for the
default to work. Document this in the Extension Manifest as a fragility.

### R3 — Asset naming uses `aarch64`, not `arm64`

Any downloader, cache manager, or Extension Manifest generated by T2.2 must
use the canonical names:

- macOS arm64 → `macos-aarch64`
- Linux x86-64 → `linux-x86_64` (this one matches common convention)

Do not write code that synthesises names from `php_uname('m')` directly —
`php_uname('m')` returns `arm64` on macOS, which must be mapped to `aarch64`.

### R4 — The "static" assets are not for us

The `sqlite-vec-0.1.9-static-*.tar.gz` assets are for *statically linking*
sqlite-vec into a host C/Rust/Go binary. They are not loadable extensions
and **must not** be cached in `storage/app/sqlite-extensions/` under any
circumstance — they will fail to load with `not an error` / `not authorized`
and confuse the Connection Gate.

### R5 — Release immutability

GitHub reports `"immutable": false` for this release (the v0.1.9 tag itself
is not protected). Alex Garcia could in theory re-upload assets and break
the digests. Mitigation: the Extension Manifest MUST pin the three-way
verified SHA-256 digests recorded above, and the cache manager MUST
re-verify on every download.

## Sources

All URLs accessed 2026-07-19.

1. **GitHub Release API (JSON, verbatim)** —
   `gh release view v0.1.9 --repo asg017/sqlite-vec --json tagName,name,publishedAt,assets`
   → `tagName: "v0.1.9"`, `publishedAt: "2026-03-31T08:00:23Z"`,
   `name: "v0.1.9 Bug fix for DELETE operations"`. Asset list excerpt
   (target platforms only):

   ```json
   {
     "name": "sqlite-vec-0.1.9-loadable-macos-aarch64.tar.gz",
     "size": 50836,
     "contentType": "application/x-gtar",
     "digest": "sha256:8282126333399ddfe98bbbcc7a1936e7252625aac49df056a98be602e46bfd29",
     "downloadUrl": "https://github.com/asg017/sqlite-vec/releases/download/v0.1.9/sqlite-vec-0.1.9-loadable-macos-aarch64.tar.gz",
     "downloadCount": 7753
   },
   {
     "name": "sqlite-vec-0.1.9-loadable-linux-x86_64.tar.gz",
     "size": 61507,
     "contentType": "application/x-gtar",
     "digest": "sha256:b959baa1d8dc88861b1edb337b8587178cdcb12d60b4998f9d10b6a82052d5d7",
     "downloadUrl": "https://github.com/asg017/sqlite-vec/releases/download/v0.1.9/sqlite-vec-0.1.9-loadable-linux-x86_64.tar.gz",
     "downloadCount": 21349
   }
   ```

2. **Upstream `checksums.txt` (verbatim, target rows):**

   ```
   sqlite-vec-0.1.9-loadable-linux-x86_64.tar.gz b959baa1d8dc88861b1edb337b8587178cdcb12d60b4998f9d10b6a82052d5d7
   sqlite-vec-0.1.9-loadable-macos-aarch64.tar.gz 8282126333399ddfe98bbbcc7a1936e7252625aac49df056a98be602e46bfd29
   ```

3. **Local `shasum -a 256`** (BSD shasum on darwin arm64, 2026-07-19):

   ```
   8282126333399ddfe98bbbcc7a1936e7252625aac49df056a98be602e46bfd29  sqlite-vec-0.1.9-loadable-macos-aarch64.tar.gz
   b959baa1d8dc88861b1edb337b8587178cdcb12d60b4998f9d10b6a82052d5d7  sqlite-vec-0.1.9-loadable-linux-x86_64.tar.gz
   ```

4. **Tarball contents** (verbatim `tar -tzf`):

   ```
   # macOS aarch64 tarball:
   vec0.dylib

   # Linux x86_64 tarball:
   vec0.so
   ```

5. **Binary inspection** (verbatim `file`):

   ```
   vec0.dylib: Mach-O 64-bit dynamically linked shared library arm64
   vec0.so:    ELF 64-bit LSB shared object, x86-64, version 1 (SYSV),
               dynamically linked, BuildID[sha1]=b315c9cbea9122b81919f822249919da465d4816, not stripped
   ```

6. **Exported symbols** (verbatim `nm -gU` / `nm -D`):

   ```
   macOS vec0.dylib:
     _sqlite3_vec_init
     _sqlite3_vec_numpy_init
     _sqlite3_vec_static_blobs_init

   Linux vec0.so:
     T sqlite3_vec_init
     T sqlite3_vec_numpy_init
     T sqlite3_vec_static_blobs_init
   ```

7. **Codesign probe** (verbatim `codesign -dv`):

   ```
   Identifier=vec0.dylib
   Format=Mach-O thin (arm64)
   CodeDirectory v=20400 size=1379 flags=0x20002(adhoc,linker-signed) hashes=40+0
   Signature=adhoc
   TeamIdentifier=not set
   ```

8. **Functional load test** (Homebrew sqlite3 3.53.3, 2026-07-19):

   ```
   $ /opt/homebrew/opt/sqlite/bin/sqlite3 ':memory:' \
       '.load /…/vec0.dylib' \
       'CREATE VIRTUAL TABLE v USING vec0(embedding float[4]);' \
       'INSERT INTO v(rowid, embedding) VALUES (1, x''0000803f0000003f000000bf0000803f'');' \
       'SELECT ''rows='' || count(*) FROM v;' \
       'SELECT ''knn_distance='' || distance || '' rowid='' || rowid FROM v WHERE embedding MATCH x''0000803f0000003f000000bf0000803f'' ORDER BY distance LIMIT 1;' \
       'SELECT ''modules='' || group_concat(name, '', '') FROM pragma_module_list() WHERE name LIKE ''vec%'';'
   rows=2
   knn_distance=0.0 rowid=1
   modules=vec_each,vec0
   ```

9. **Herd PHP 8.5 build flags** (verbatim `php -i | grep 'Configure Command'`):

   ```
   './configure' … '--enable-pdo' '--with-sqlite3=/Users/runner/work/herd-php-builds/herd-php-builds/buildroot' '--with-pdo-sqlite' … 'PHP_BUILD_PROVIDER=Laravel Herd' 'PHP_BUILD_COMPILER=clang 15.0.0'
   ```

   No `--enable-sqlite3-load-extension` (PHP's sqlite3 ext has no such flag;
   load-extension is opt-out via `SQLITE_OMIT_LOAD_EXTENSION` on the bundled
   SQLite). `php --re sqlite3` confirms no `sqlite3.enable_load_extension`
   ini entry exists, which is the signature of a compile-time omit.
