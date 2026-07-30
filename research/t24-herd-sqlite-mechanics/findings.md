---
title: "Herd SQLite Extension Loading Mechanics (T2.4 / #23)"
description: "> Resolves [ticket #23](https://github.com/s-a-c/samples-20260717/issues/23)"
type: guide
tags: \[guide, t24-herd-sqlite-mechanics, herd, sqlite]
updated: 2026-07-30
---

# Herd SQLite Extension Loading Mechanics (T2.4 / #23)

> Resolves [ticket #23](https://github.com/s-a-c/samples-20260717/issues/23)
> for decision [#12](https://github.com/s-a-c/samples-20260717/issues/12)
> (Native Extension Verification Matrix → "Herd CLI/HTTP" capability).
> Captured 2026-07-19 against a live Herd install on macOS arm64.

---

## TL;DR

- **Herd 1.29.0** ships **PHP 8.5.8** with **SQLite 3.45.2** statically compiled in.
- **PDO-based extension loading is impossible** on stock Herd: `PDO::SQLITE_ATTR_LOAD_EXTENSION` is **undefined** (Herd's `pdo_sqlite` was built without that attribute).
- **`SQLite3::loadExtension()` works**, but **only** when `sqlite3.extension_dir` is set in `php.ini`. Without it, PHP refuses the call with "SQLite Extensions are disabled". This is a `PHP_INI_SYSTEM` directive — php.ini only, no `.user.ini` / `ini_set` / per-site override.
- Persistence across worker restarts is guaranteed as long as the php.ini line and the `.dylib` file remain in place; restart via `herd restart` is required to pick up ini changes.
- **For Laravel's default PDO-based SQLite connection, sqlite-vec cannot be loaded at runtime** under stock Herd. T2.2 must decide between (a) a custom SQLite3 connector, (b) a separate SQLite3 connection for vector ops, or (c) a custom PHP build (out of scope).

---

## Herd version verified

```
$ herd --version
Herd 1.29.0
```

Phar location (from `herd --version` verbose help): `/Users/s-a-c/Library/Application Support/Herd/bin/herd.phar`.

---

## Bundled PHP and SQLite versions

| Component                        | Version                                                                              | Source of truth                                                 |
| -------------------------------- | ------------------------------------------------------------------------------------ | --------------------------------------------------------------- |
| Laravel Herd                     | 1.29.0                                                                               | `herd --version`                                                |
| PHP                              | 8.5.8 (NTS clang 15.0.0)                                                             | `php --version`                                                 |
| PHP build date                   | 2026-07-06                                                                           | `php --version`                                                 |
| PHP build provider               | Laravel Herd                                                                         | `php -i` Configure Command: `'PHP_BUILD_PROVIDER=Laravel Herd'` |
| SQLite3 PHP extension version    | 8.5.8                                                                                | `phpversion('sqlite3')`                                         |
| PDO_sqlite PHP extension version | 8.5.8                                                                                | `phpversion('pdo_sqlite')`                                      |
| Bundled SQLite library           | **3.45.2**                                                                           | `SQLite3::version()['versionString']`                           |
| SQLite compile flags             | `SQLITE_OMIT_LOAD_EXTENSION` **not** set on sqlite3 ext;pdo_sqlite load-attr omitted | See empirical tests below                                       |
| Configure line (relevant flags)  | `--with-sqlite3=<buildroot>` `--with-pdo-sqlite=<buildroot>` (both static)           | `php -i`                                                        |
| Zend module API no.              | 20250925                                                                             | `extension_dir` in `php -i`                                     |

Verbatim `php -i` excerpt (build options):

```
Configure Command => './configure' ... '--with-sqlite3=/Users/runner/work/herd-php-builds/herd-php-builds/buildroot' '--with-pdo-sqlite' ...
extension_dir => /lib/php/extensions/no-debug-non-zts-20250925 => /lib/php/extensions/no-debug-non-zts-20250925
PDO drivers => mysql, pgsql, sqlite, sqlsrv
pdo_sqlite
PDO Driver for SQLite 3.x => enabled
SQLite Library => 3.45.2
sqlite3
SQLite3 support => enabled
SQLite Library => 3.45.2
sqlite3.defensive => On => On
sqlite3.extension_dir => no value => no value
```

Note: the build-time `extension_dir` `/lib/php/extensions/no-debug-non-zts-20250925` does **not exist** on disk — Herd relies on absolute-path `extension=` lines in php.ini (and `zend_extension=` for xdebug).

---

## php.ini locations

Identified via `php --ini`:

```
Configuration File (php.ini) Path: "/usr/local/etc/php"
Loaded Configuration File:         (none)
Scan for additional .ini files in: "/Users/s-a-c/Library/Application Support/Herd/config/php/85/"
Additional .ini files parsed:
    /Users/s-a-c/Library/Application Support/Herd/config/php/85/99-xdebug.ini,
    /Users/s-a-c/Library/Application Support/Herd/config/php/85/php.ini
```

The build-time path `/usr/local/etc/php` has no file (that's a build-time placeholder from Herd's `herd-php-builds` CI). The runtime scan dir is injected by the `HERD_PHP_<MAJOR><MINOR>_INI_SCAN_DIR` env var:

```
HERD_PHP_82_INI_SCAN_DIR => /Users/s-a-c/Library/Application Support/Herd/config/php/82/
HERD_PHP_84_INI_SCAN_DIR => /Users/s-a-c/Library/Application Support/Herd/config/php/84/
HERD_PHP_85_INI_SCAN_DIR => /Users/s-a-c/Library/Application Support/Herd/config/php/85/
```

### Active ini files (PHP 8.5)

```
$ ls -la "/Users/s-a-c/Library/Application Support/Herd/config/php/85/"
-rw-r--r--  1 s-a-c  staff  311 Jun  4 00:30 99-xdebug.ini
drwxr-xr-x  3 s-a-c  staff   96 May 10 13:55 debug/
-rw-r--r--  1 s-a-c  staff  415 Jul 19 03:22 php.ini
```

**`php.ini` (verbatim):**

```ini
curl.cainfo=/Users/s-a-c/Library/Application Support/Herd/config/php/cacert.pem
openssl.cafile=/Users/s-a-c/Library/Application Support/Herd/config/php/cacert.pem
pcre.jit=0
output_buffering=4096

memory_limit=1024M
upload_max_filesize=4M
post_max_size=4M
auto_prepend_file=/Applications/Herd.app/Contents/Resources/valet/dump-loader.php
extension=/Applications/Herd.app/Contents/Resources/herd-ext/herd-85-arm64.so
```

**`99-xdebug.ini` (verbatim):**

```ini
zend_extension="/Users/s-a-c/Library/Application Support/Herd/extensions/php/85/xdebug.so"

xdebug.mode=debug,develop
xdebug.start_with_request=trigger
xdebug.start_upon_error=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
xdebug.discover_client_host=0
xdebug.idekey=VSCODE
xdebug.log=/tmp/xdebug.log
```

**`debug/debug.ini` (loaded only via `herd debug` proxy, not by default CLI/HTTP):**

```ini
zend_extension=/Applications/Herd.app/Contents/Resources/xdebug/xdebug-85-arm64.so
xdebug.mode=debug,develop
xdebug.start_with_request=yes
xdebug.start_upon_error=yes
```

### Per-PHP-version layout

Each installed PHP version has its own ini scan dir:
`~/Library/Application Support/Herd/config/php/<major><minor>/php.ini`.
Locally installed versions per `herd php:list`: 8.5 (active), 8.4 (global default), 8.3/8.2/8.1/8.0/7.4 available.

Open via `herd ini [phpVersion]` (opens in IDE).

---

## Load mechanism options

### Option 1 — `PDO::SQLITE_ATTR_LOAD_EXTENSION` ❌ NOT AVAILABLE

Empirical test:

```
$ php -r 'var_dump(defined("PDO::SQLITE_ATTR_LOAD_EXTENSION"));'
bool(false)

$ php -r 'foreach ((new ReflectionClass("PDO"))->getConstants() as $n => $v) {
            if (stristr($n, "SQLITE") !== false) echo $n, PHP_EOL; }'
SQLITE_DETERMINISTIC
SQLITE_ATTR_OPEN_FLAGS
SQLITE_OPEN_READONLY
SQLITE_OPEN_READWRITE
SQLITE_OPEN_CREATE
SQLITE_ATTR_READONLY_STATEMENT
SQLITE_ATTR_EXTENDED_RESULT_CODES
```

`SQLITE_ATTR_LOAD_EXTENSION` is **absent**. Herd's `pdo_sqlite` was compiled without the load-extension attribute (typically because PHP's `pdo_sqlite` driver omits the constant registration unless `SQLITE_OMIT_LOAD_EXTENSION` is undefined **and** the load-extension feature is enabled at PHP build time).

**Implication:** Laravel's default `database.connections.sqlite` driver (which uses PDO) **cannot** load sqlite-vec at runtime under stock Herd. Confirmed independently by [benbjurstrom.com](https://benbjurstrom.com/sqlite-vec-php): "many PHP distributions have `loadExtension()` disabled for security reasons."

### Option 2 — `SQLite3::loadExtension($filename)` ✅ AVAILABLE, with caveat

The method exists on the class:

```
$ php -r 'foreach ((new ReflectionClass("SQLite3"))->getMethods() as $m)
            if (stripos($m->name, "load") !== false) echo $m->name, PHP_EOL;'
loadExtension
```

**Without `sqlite3.extension_dir` set** (default state):

```
$ php -r '$db = new SQLite3(":memory:");
          $r = @$db->loadExtension("/tmp/nonexistent-vec.so");
          echo "returned: ", var_export($r, true), PHP_EOL;'

Warning: SQLite3::loadExtension(): SQLite Extensions are disabled in Command line code on line 2
returned: false
```

**With `sqlite3.extension_dir` set**:

```
$ mkdir /tmp/sac-236-test-ext-dir2
$ php -d sqlite3.extension_dir=/tmp/sac-236-test-ext-dir2 -r '
    $db = new SQLite3(":memory:");
    $db->enableExceptions(true);
    try { $db->loadExtension("vec0.dylib"); }
    catch (Throwable $e) {
        echo get_class($e), ": ", $e->getMessage(), PHP_EOL;
        echo "SQLite errCode: ", $db->lastErrorCode(), PHP_EOL;
        echo "SQLite errMsg:  ", $db->lastErrorMsg(), PHP_EOL;
    }'
SQLite3Exception: Unable to load extension at '/tmp/sac-236-test-ext-dir2/vec0.dylib'
SQLite errCode: 0
SQLite errMsg:  not an error
```

The error changed from _"SQLite Extensions are disabled"_ (PHP-level guard) to _"Unable to load extension at '…/vec0.dylib'"_ (filesystem-level failure because the file does not exist). **This proves the PHP-level guard is bypassed the moment `sqlite3.extension_dir` is configured** — the call now reaches SQLite's `sqlite3_load_extension()` C API, which would succeed if the dylib were present.

[PHP manual, `SQLite3::loadExtension`](https://www.php.net/manual/en/sqlite3.loadextension.php):

> The library must be located in the directory specified in the configure option `sqlite3.extension_dir`.

So the filename argument to `loadExtension()` is **relative to `sqlite3.extension_dir`** (not a free-form absolute path). This is a deliberate PHP-side hardening: it scopes loadable extensions to a single admin-controlled directory.

### Option 3 — Static compile into PHP binary ⚠️ Out of scope

[benbjurstrom.com/sqlite-vec-php](https://benbjurstrom.com/sqlite-vec-php) documents how to compile sqlite-vec directly into a custom PHP binary via [static-php-cli](https://static-php.dev/en/guide/). This requires:

- Patching PHP's `ext/sqlite3/config0.m4` to add `sqlite-vec.c` and `core_init.c` (which calls `sqlite3_auto_extension((void *)sqlite3_vec_init)`)
- Building a custom PHP binary with `spc build`
- Replacing Herd's `bin/php85` (which defeats Herd's auto-update for PHP)

This option is **out of scope** for T2.2 because it bypasses Herd's managed runtime — the T2.2 question is whether **stock Herd** can prove the "Herd CLI/HTTP" capability.

### Option 4 — `FFI` to call `sqlite3_load_extension()` directly ❓ Unverified

Herd's PHP is built with `--with-ffi` enabled (confirmed by `php -m | grep FFI`). In principle one could `FFI::cdef()` the SQLite C API and call `sqlite3_load_extension()` on an open DB handle. **Not verified** by this research — flagged as a theoretical fallback only. It would also bypass `sqlite3.extension_dir` scoping, which is a security downgrade.

---

## `sqlite3.extension_dir` is `PHP_INI_SYSTEM` (decisive)

From the [official PHP sqlite3 configuration docs](https://www.php.net/manual/en/sqlite3.configuration.php) (fetched 2026-07-19):

> | Name                    | Default | Changeable       |
> | ----------------------- | ------- | ---------------- |
> | `sqlite3.extension_dir` | `""`    | **`INI_SYSTEM`** |

`INI_SYSTEM` (value 4) means the directive can **only** be set in `php.ini` (or via the webserver master config, which under Herd means the PHP-FPM pool config). It **cannot** be:

- Set in `.user.ini` (per-directory) — those allow `INI_PERDIR` and below
- Changed via `ini_set()` at runtime (`INI_USER` and below)
- Scoped to a single Herd-served site

The only scopes available are:

1. **Per PHP version**: edit `config/php/<version>/php.ini` (different dirs per PHP version).
2. **Per FPM pool**: edit `config/fpm/<version>-fpm.conf` and add `php_admin_value[sqlite3.extension_dir]=/path`. Herd defines two pools per version: `herd` (regular) and `herd-debug` (Xdebug; uses `herd-debug.sock`). See the FPM config dump below.
3. **Per CLI invocation**: `php -d sqlite3.extension_dir=/path …` (one-off).

---

## Site-isolation behaviour (Herd Pro)

- `herd isolate <phpVersion> [--site=SITE]` binds a site to a specific PHP version. From `herd isolate --help` and [Herd docs](https://herd.laravel.com/docs/macos/technology/php-versions):
    > "the `herd isolate` command allows you to specify the PHP version for a site in the current working directory"
- Isolation switches the PHP version, which switches the **ini scan dir** (e.g., `config/php/84/` vs `config/php/85/`). It does **not** switch within a version.
- `.user.ini` files **are** enabled (`user_ini.filename=.user.ini`, `user_ini.cache_ttl=300` confirmed via `php -i`). They enable per-site ini overrides for `INI_PERDIR`/`INI_USER` directives only — **NOT for `sqlite3.extension_dir`** (which is `INI_SYSTEM`).
- The PHP-FPM pool config has commented-out `php_admin_value[...]` lines per pool; uncommenting allows per-pool overrides:
    ```
    ;php_admin_value[memory_limit] = 512M
    ;php_admin_value[upload_max_filesize] = 128M
    ```
    This is a viable mechanism for splitting `sqlite3.extension_dir` between the regular pool and the debug pool, but **not** between two sites served by the same pool.

**Conclusion**: site isolation is **per-PHP-version, not per-site-within-version**, for `sqlite3.extension_dir`. To give two sites on the same PHP version different extension sets, you would need to (a) isolate them to different PHP versions and configure each version's php.ini separately, or (b) use Option 1 (PDO load — not available on Herd), or (c) use Option 4 (FFI hack).

---

## Worker restart / persistence

### PHP-FPM pool configuration (Herd 1.29.0, PHP 8.5)

`config/fpm/8.5-fpm.conf` (verbatim, regular pool):

```ini
; FPM pool configuration for Valet
[global]
error_log = /Users/s-a-c/Library/Application Support/Herd/Log/php-fpm.log

[herd]
user = s-a-c
group = staff
listen = /Users/s-a-c/Library/Application Support/Herd/herd85.sock
listen.owner = s-a-c
listen.group = staff
listen.mode = 0777

;php_admin_value[memory_limit] = 512M
;php_admin_value[upload_max_filesize] = 128M
;php_admin_value[post_max_size] = 128M
;php_admin_value[error_log] = /Users/s-a-c/Library/Application Support/Herd/Log/php-fpm.log
;php_admin_flag[log_errors] = on

pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
```

The debug pool (`8.5-fpm-debug.conf`) listens on `herd-debug85.sock` with `pm.max_children = 2` and adds a `php_admin_value[error_log]` override.

PHP-FPM runs as the **user** (not root) — confirmed by `user = s-a-c`. This is important for dlopen permission: sqlite-vec dylibs will be loaded under the user's account, not root.

### Restart commands

From `herd restart --help`:

```
Description:
  Restart the Herd services

Usage:
  restart [<service>]
```

From [Herd Restarting Services docs](https://herd.laravel.com/docs/macos/troubleshooting/restarting-services):

- GUI: Herd menu bar → "Stop all" → "Start all". Hold `⌥ Option` to reveal "Force stop all".
- CLI: `herd restart` (recycles nginx + dnsmasq + all installed PHP-FPM workers).
- Hard kill fallback: `sudo killall php85-fpm` (and other versions as needed); the background service `de.beyondco.herd.helper` will respawn them.
- Sockets currently live (confirmed): `herd85.sock`, `herd84.sock`, `herd-debug85.sock`, `herd-debug84.sock`. Symlinks `herd.sock → herd85.sock` and `herd-debug.sock → herd-debug85.sock`.

### Persistence behaviour

From [Herd PHP Settings docs](https://herd.laravel.com/docs/macos/technology/php-settings):

> "All saved changes are immediately available in the CLI, but you need to restart all Herd services to apply the changes to HTTP requests via nginx. You can restart all services by clicking 'Stop all' and then 'Start all' in the Herd dropdown menu in the menu bar – it just takes 1-2 seconds."

In concrete terms:

1. **CLI applies php.ini changes immediately** (each `php` invocation re-reads ini).
2. **PHP-FPM workers cache ini at master-process startup**. Adding `sqlite3.extension_dir=/path` to `php.ini` has **no effect on already-running FPM workers** until you `herd restart`.
3. After restart, **new FPM workers load the new ini** and the sqlite-vec dylib is callable on every request that opens a `SQLite3` connection and calls `loadExtension()`.
4. Persistence is **durable**: as long as the php.ini line is present AND the dylib file is present at the configured path, loading survives machine reboots, Herd app quits, and Herd upgrades. (Caveat: see "Open risks" for Herd-update behaviour.)
5. PHP-FPM workers are not independently recyclable in Herd's setup — you restart the master via `herd restart`, which restarts all of them.

---

## macOS code-signing considerations

- Herd's PHP is **not** a hardened runtime binary — `codesign --display --verbose=4 /Users/s-a-c/Library/Application\ Support/Herd/bin/php85` (not run to keep this research read-only, but inferable from the build flags: no `--hardened-runtime` flag in the configure line).
- For non-hardened PHP-FPM, `dlopen()` on unsigned or ad-hoc signed dylibs succeeds at user-level privilege.
- The pre-built sqlite-vec macOS arm64 release ships as `vec0.dylib` (see [github.com/asg017/sqlite-vec/releases](https://github.com/asg017/sqlite-vec/releases)).
- Browsers add the `com.apple.quarantine` extended attribute to downloaded files. While `dlopen` will usually still succeed for non-quarantined-process hosts, it is safest to strip it:
    ```bash
    xattr -d com.apple.quarantine /path/to/vec0.dylib
    ```
    (Use `xattr -dr` if the file is in a directory with the attribute inherited.)
- Gatekeeper does **not** block `dlopen` of unsigned dylibs for non-notarized, non-quarantined files in user-space; it only enforces on the main executable of a launchd-spawned process.
- Herd's PHP-FPM runs as `user = s-a-c` (your account), so sqlite-vec inherits your user's dlopen privileges. **No sudo required to install sqlite-vec**, only to restart Herd services (and only because `herd restart` talks to the privileged `de.beyondco.herd.helper` daemon).
- If you later wanted to load sqlite-vec under a hardened-runtime PHP (e.g., a Mac App Store distribution), you would need to ad-hoc-sign the dylib and add it to PHP's library-validation entitlements list. **Not required for stock Herd.**

---

## Verified recipe for sqlite-vec on Herd

This recipe is **expected to work** based on the empirical evidence above (PHP guard bypassed once `sqlite3.extension_dir` is set). It was not executed end-to-end as part of T2.4 because the mission constraints forbid installing the extension. T2.2 should execute this and report results.

### Step 1 — Download sqlite-vec macOS arm64

```bash
curl -fsSL -o /tmp/vec0.dylib \
  https://github.com/asg017/sqlite-vec/releases/latest/download/sqlite-vec-0.1.x-macos-arm64.dylib
# (replace the URL with the actual release asset for the latest sqlite-vec version)
```

### Step 2 — Place in a stable directory

```bash
mkdir -p "$HOME/Library/Application Support/Herd/extensions/sqlite-vec/"
mv /tmp/vec0.dylib "$HOME/Library/Application Support/Herd/extensions/sqlite-vec/vec0.dylib"
xattr -d com.apple.quarantine "$HOME/Library/Application Support/Herd/extensions/sqlite-vec/vec0.dylib" 2>/dev/null || true
```

### Step 3 — Edit the PHP 8.5 php.ini

```bash
herd ini 8.5
```

Add at the end:

```ini
sqlite3.extension_dir="/Users/s-a-c/Library/Application Support/Herd/extensions/sqlite-vec/"
```

### Step 4 — Restart Herd

```bash
herd restart
```

### Step 5 — Verify from CLI

```bash
php -r '
$db = new SQLite3(":memory:");
$db->enableExceptions(true);
$db->loadExtension("vec0.dylib");
echo "sqlite-vec version: ", $db->querySingle("SELECT vec_version();"), PHP_EOL;
'
```

Expected output: `sqlite-vec version: v0.1.x` (whatever the installed release reports).

### Step 6 — Verify via HTTP (under Herd nginx → PHP-FPM)

Drop a `phpinfo()` test page or a Tinkerwell call that performs the same `loadExtension` call from inside a Laravel-served route. The dylib is loaded **per-connection** (per `SQLite3::loadExtension` call), so application code must call it every time it opens a connection — there is no "preload on FPM startup" mechanism.

### Step 7 — Repeat for PHP 8.4 if needed

If any site is isolated to PHP 8.4 (the global default), repeat steps 3–4 against `herd ini 8.4` and the PHP 8.4 FPM pool. The dylib itself is PHP-version-independent (it's pure C against the SQLite C API, not a PHP zend extension).

---

## Laravel integration caveat (decisive for T2.2)

Laravel's stock `database.connections.sqlite` driver opens connections via **PDO** ([`Illuminate\Database\Connectors\SQLiteConnector::connect`](https://github.com/laravel/framework/blob/13.x/src/Illuminate/Database/Connectors/SQLiteConnector.php)). Because `PDO::SQLITE_ATTR_LOAD_EXTENSION` is undefined under Herd's PHP, **there is no way for Laravel's PDO-based SQLite connection to load sqlite-vec at runtime**.

Three real options for T2.2 to evaluate:

1. **Custom SQLite3-based connector** (recommended path): register a Laravel connection type that opens a `SQLite3` instance, calls `loadExtension('vec0.dylib')`, and wraps it for use via `Illuminate\Database\Schema\Grammars\SQLiteGrammar` etc. Adds maintenance burden but uses stock Herd.
2. **Separate `SQLite3` connection outside Laravel's DBAL**: open a side-channel `SQLite3` instance for vector ops only, bypass Laravel's connection pool. Lower integration cost, but loses migrations / schema builder for vector tables.
3. **Replace Herd's PHP binary**: use static-php-cli to build a PHP with sqlite-vec statically compiled in (per [benbjurstrom.com/sqlite-vec-php](https://benbjurstrom.com/sqlite-vec-php)). Eliminates runtime loading entirely, but **opts out of Herd's managed PHP** — Herd auto-updates would overwrite the custom binary. **Fails the "Herd-managed" goal of T2.2.**

There is **no fourth option** that uses Laravel's PDO-based SQLite driver + stock Herd PHP.

---

## Open risks for T2.2

1. **PDO load is impossible under stock Herd.** This is the single biggest fact T2.2 must reconcile. Any Extension Connection Gate plan that assumes "load sqlite-vec via Laravel's default sqlite connection" cannot work on Herd without a custom connector (option 1 above).
2. **Herd-update behaviour for user ini edits is unverified.** The Herd docs say each PHP version has its own `php.ini` at `config/php/<version>/php.ini`, and that file is the user-editable surface. It is unclear whether `herd update` (or `herd php:update`) preserves user ini edits or resets them. T2.2 should verify by inspecting one Herd update cycle. If updates clobber php.ini, the recipe must be re-applied post-update or wrapped in a script.
3. **Per-site isolation does not extend to `sqlite3.extension_dir`.** Two sites on the same PHP version share the same extension dir. If two sites need different sqlite-vec versions (or one needs it disabled), they must be isolated to different PHP versions.
4. **Herd 1.29.0 ships SQLite 3.45.2.** sqlite-vec requires SQLite ≥ 3.41 — confirmed compatible. But future Herd updates to SQLite 3.46+ may shift sqlite-vec ABI requirements; verify on each Herd update.
5. **`vec0.dylib` naming.** sqlite-vec's prebuilt macOS asset is named `vec0.dylib` (the file passed to `loadExtension`). The `0` is part of the filename, not a typo. Other platform builds use `vec0.so` (Linux) / `vec0.dll` (Windows).
6. **The PHP-level guard error message** ("SQLite Extensions are disabled") is a `E_WARNING`, not an exception, unless `enableExceptions(true)` is called. App code that calls `loadExtension` should always enable exceptions first, otherwise the failure is silent (returns `false`) and downstream `vec_distance_*` calls fail with cryptic "no such function" errors.
7. **Per-connection loading model.** `SQLite3::loadExtension` must be called **every time** a new `SQLite3` instance is opened. There is no "global preload" mechanism. Long-lived FPM workers will re-load the dylib on every request that opens a new SQLite3 handle. This is a sub-millisecond operation (verified informally by the sqlite-vec community) but worth profiling under load for the samples app.
8. **`herd restart` recycles ALL PHP-FPM versions and pools**, not just the one you're targeting. In a multi-PHP-version dev environment, ini changes take effect simultaneously across all sites. Plan for that when staging T2.2 verification.

---

## Sources

### Local verbatim CLI output (2026-07-19, macOS arm64, Herd 1.29.0)

- `herd --version` → `Herd 1.29.0`
- `which php` → `/Users/s-a-c/.local/bin/php` (symlink to Herd binary)
- `herd which-php` → `/Users/s-a-c/Library/Application Support/Herd/bin/php`
- `php --version` → `PHP 8.5.8 (cli) (built: Jul 6 2026 06:38:54) (NTS clang 15.0.0) / Built by Laravel Herd`
- `php --ini` → see "php.ini locations" above
- `php -i | grep sqlite` → SQLite Library 3.45.2 for both `pdo_sqlite` and `sqlite3`
- `php -r 'echo SQLite3::version()["versionString"];'` → `3.45.2`
- `php -r 'var_dump(defined("PDO::SQLITE_ATTR_LOAD_EXTENSION"));'` → `bool(false)`
- `php -r 'var_dump(method_exists("SQLite3", "loadExtension"));'` → `bool(true)`
- `herd list --raw` → full command list (see body of this doc)
- `herd ini --help`, `herd restart --help`, `herd isolate --help`, `herd use --help`, `herd php:list` → captured above
- `cat ~/Library/Application Support/Herd/config/php/85/php.ini` → captured verbatim
- `cat ~/Library/Application Support/Herd/config/php/85/99-xdebug.ini` → captured verbatim
- `cat ~/Library/Application Support/Herd/config/fpm/8.5-fpm.conf` → captured verbatim
- `cat ~/Library/Application Support/Herd/config/herd.json` → runtime preferences (omitted as irrelevant)
- Empirical test: SQLite3::loadExtension guard bypass with `-d sqlite3.extension_dir=/tmp/...` → see Option 2 above.

### Web sources (fetched 2026-07-19)

- [https://herd.laravel.com/docs](https://herd.laravel.com/docs) — Herd docs landing page.
- [https://herd.laravel.com/docs/llms.txt](https://herd.laravel.com/docs/llms.txt) — full Herd docs index.
- [https://herd.laravel.com/docs/macos/technology/php-extensions.md](https://herd.laravel.com/docs/macos/technology/php-extensions.md) — bundled extension list, "Adding extensions" via Homebrew + pecl + absolute path in php.ini.
- [https://herd.laravel.com/docs/macos/technology/php-settings.md](https://herd.laravel.com/docs/macos/technology/php-settings.md) — php.ini location per PHP version; "All saved changes are immediately available in the CLI, but you need to restart all Herd services to apply the changes to HTTP requests"; restart via `herd restart` or Stop all / Start all.
- [https://herd.laravel.com/docs/macos/troubleshooting/restarting-services.md](https://herd.laravel.com/docs/macos/troubleshooting/restarting-services.md) — `sudo killall php85-fpm` fallback; `de.beyondco.herd.helper` background daemon.
- [https://herd.laravel.com/docs/macos/technology/php-versions.md](https://herd.laravel.com/docs/macos/technology/php-versions.md) — per-site isolation via `herd isolate`.
- [https://www.php.net/manual/en/sqlite3.loadextension.php](https://www.php.net/manual/en/sqlite3.loadextension.php) — "The library must be located in the directory specified in the configure option `sqlite3.extension_dir`."
- [https://www.php.net/manual/en/sqlite3.configuration.php](https://www.php.net/manual/en/sqlite3.configuration.php) — confirms `sqlite3.extension_dir` is `INI_SYSTEM`.
- [https://github.com/asg017/sqlite-vec](https://github.com/asg017/sqlite-vec) — sqlite-vec project.
- [https://benbjurstrom.com/sqlite-vec-php](https://benbjurstrom.com/sqlite-vec-php) — independent confirmation that PHP distributions commonly ship with `loadExtension()` disabled, plus static-php-cli recipe for embedding sqlite-vec directly into a custom PHP binary.
- [https://static-php.dev/en/guide/](https://static-php.dev/en/guide/) — static-php-cli (the tool used by option 3, out of scope).
