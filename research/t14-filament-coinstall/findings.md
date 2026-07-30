---
title: "Filament 5 + Livewire Teams Starter Co-installation (T1.4 / #19)"
description: "Research date: 2026-07-19"
type: guide
tags: \[guide, t14-filament-coinstall, filament, "5"]
updated: 2026-07-30
---

# Filament 5 + Livewire Teams Starter Co-installation (T1.4 / #19)

Research date: 2026-07-19
Repo: s-a-c/samples-20260717 (Laravel Livewire starter kit)
Ticket: https://github.com/s-a-c/samples-20260717/issues/19
Parent: #15 (wayfinder map)
Consumer: T1.1 (Filament install order decision)

---

## Verified Filament version

- **Current major:** Filament **v5.x** (stable).
- **v5.0.0 release date:** **2026-01-16** (released by Dan Harrin; see GitHub release `filamentphp/filament@v5.0.0`, commit `bd02fca`). Subsequent patch releases up through at least v5.3.x are referenced in community trackers (e.g. laraveldaily "Filament v5.3.0 adds deferred tab badge loading…", retrieved 2026-07-19).
- **Runtime requirements (Filament v5):**
    - PHP **8.2+** (repo: PHP ^8.5 ✅)
    - Laravel **v11.28+** (repo: laravel/framework ^13.17 ✅)
    - Livewire **v4.0+** (repo: livewire/livewire ^4.1 ✅ — Filament v5 was specifically the release that moved from Livewire 3 to Livewire 4 stable; see PR #18965 "chore(deps): update Livewire to stable v4.0 release")
    - Tailwind **v4.0+** (repo uses Flux ^2.13 which already requires Tailwind v4 ✅)
- **Install command (Laravel 13 + PHP 8.5):**

    ```bash
    composer require filament/filament:"^5.0"
    php artisan filament:install --panels
    ```

    (No need for the v4→v5 upgrade script — that is only for projects already running Filament v4. This is a greenfield install.)

- **Result:** the installer creates `app/Providers/Filament/AdminPanelProvider.php` (default panel id `admin`, default path `/admin`) and must be auto-registered in `bootstrap/providers.php` (Filament's installer registers it but the repo uses the Laravel 11+ `bootstrap/providers.php` flat list — verify after install per Filament docs warning).

**Sources:**

- https://filamentphp.com/docs/5.x/introduction/installation (accessed 2026-07-19)
- https://filamentphp.com/docs/5.x/upgrade-guide (accessed 2026-07-19)
- https://github.com/filamentphp/filament/releases/tag/v5.0.0 (accessed 2026-07-19)
- Context7 `/websites/filamentphp_5_x` — queries: installation, multi-panel, auth guard

---

## Install recipe (step-by-step)

Run from the repo root, on a clean working tree (do NOT commit during this research spike):

```bash
# 1. Pull Filament v5 (panel builder meta-package). -W widens transitive deps
#    so Livewire 4.1 etc. resolve cleanly. --no-update defers until step 2.
composer require filament/filament:"^5.0" -W --no-update

# 2. Resolve and install.
composer update

# 3. Run the Filament panel installer. Creates app/Providers/Filament/AdminPanelProvider.php
#    with default path /admin and id 'admin'. Also publishes panel assets & layout.
php artisan filament:install --panels

# 4. VERIFY bootstrap/providers.php contains App\Providers\Filament\AdminPanelProvider.
#    The installer should add it automatically, but the docs explicitly warn to check.
#    If missing, append it manually.

# 5. Publish Filament's shared config (optional but recommended for review).
php artisan vendor:publish --tag=filament-config

# 6. Storage:link (Filament expects a public disk for file uploads).
php artisan storage:link

# 7. Build frontend assets. Filament v5 ships its own panel layout/CSS — Flux's
#    existing app.css is NOT replaced; Filament renders its own Blade layout.
pnpm run build

# 8. Create the first admin user (interactive).
php artisan make:filament-user
```

**Rationale notes:**

- Step 1–2: split into two commands per Filament v5 upgrade-guide pattern so composer can resolve Livewire ^4.1 + Filament's own Livewire constraint simultaneously.
- Step 3 creates the panel layout under `resources/views/filament/` (or similar) — it does NOT modify the Flux resources/views/layouts/guest.blade.php or app.blade.php used by the starter.
- Step 7: Vite is already wired (vite.config.js exists). Filament's CSS imports go through `@filamentStyles` / `@filamentScripts` in the panel layout, NOT in the Flux app layout.

**Do NOT run** (these are for v4→v5 migrations only):

- `composer require filament/upgrade:"^5.0" --dev`
- `vendor/bin/filament-v5`

---

## Multi-panel setup

Per decision #5 (Admin + 3 product panels), each panel is a separate PanelProvider class under `app/Providers/Filament/`:

```
app/Providers/Filament/
  AdminPanelProvider.php         # /admin   — Shield + super-admin only
  ChinookPanelProvider.php       # /chinook
  NorthwindPanelProvider.php     # /northwind
  PagilaPanelProvider.php        # /pagila
```

All four are registered in `bootstrap/providers.php`. Each provider follows the same shape:

```php
use Filament\Panel;
use Filament\PanelProvider;

class ChinookPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('chinook')
            ->path('chinook')
            ->authGuard('web')           // shared with Fortify, see below
            ->login()                    // OPTIONAL — see Fortify coexistence
            ->passwordReset()
            ->emailVerification()
            ->discoverResources(in: app_path('Filament/Chinook/Resources'), for: 'App\\Filament\\Chinook\\Resources')
            ->discoverPages(in: app_path('Filament/Chinook/Pages'), for: 'App\\Filament\\Chinook\\Pages')
            ->discoverWidgets(in: app_path('Filament/Chinook/Widgets'), for: 'App\\Filament\\Chinook\\Widgets')
            ->middleware([...])
            ->authMiddleware([...]);
    }
}
```

**Isolation mechanism (verified against Filament v5 docs):**

- Each panel has its own URL `path()`. Default `/admin` is unused by the starter (`routes/web.php` only has `/` and `/{current_team}/...`).
- Each panel discovers Resources/Pages/Widgets from its own namespace path, so Chinook resources never collide with Pagila resources.
- Access control is per-panel via `FilamentUser::canAccessPanel(Panel $panel)` on the User model — different gating rule per `panel->getId()`.
- Filament docs explicitly support multiple panels: "You can create as many panels as you like within a Laravel installation, but you only need to install it once." (installation guide)

**Tenancy note:** Filament v5 has native `->tenant(Team::class)` support with `tenantMiddleware()` and `tenantRoutePrefix()`. The starter's `App\Models\Team` could in principle be used as a Filament tenant model. **However**, decision #5 treats the three product panels as product-scoped, not team-scoped — the Admin panel is global. Whether product panels are also team-tenant-scoped is a T1.1 question, not a research question.

**Sources:**

- https://filamentphp.com/docs/5.x/panel-configuration (accessed 2026-07-19)
- https://filamentphp.com/docs/5.x/users/tenancy (accessed 2026-07-19)

---

## Conflicts with the Livewire teams starter

| #   | Starter component (file)                                                                                                                        | Filament v5 counterpart                                                                                                                                                                  | Conflict / resolution                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| --- | ----------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | `routes/web.php:6` — `Route::view('/', 'welcome')`                                                                                              | Filament default path `/admin`                                                                                                                                                           | **No conflict.** `/admin` not in starter routes.                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| 2   | `routes/web.php:8-12` — `Route::prefix('{current_team}')->middleware([...EnsureTeamMembership::class])`                                         | Filament routes mounted at `/admin` (not under `{current_team}`)                                                                                                                         | **No conflict.** Filament panel routes bypass the team URL prefix entirely. Decision: do NOT wrap AdminPanelProvider in team middleware.                                                                                                                                                                                                                                                                                                                                                                          |
| 3   | `app/Http/Middleware/EnsureTeamMembership.php` — guards team routes, aborts 403 if user not on team                                             | Not applied to Filament routes by default                                                                                                                                                | **No conflict (intentional).** Admin panel access is global. If a product panel needs team gating, use Filament's `->tenant(Team::class)` + `->tenantMiddleware([EnsureTeamMembership::class], isPersistent: true)`.                                                                                                                                                                                                                                                                                              |
| 4   | `app/Http/Middleware/SetTeamUrlDefaults.php` — sets `URL::defaults(['current_team' => ..., 'team' => ...])` as web middleware                   | Filament routes accept no `{current_team}` parameter                                                                                                                                     | **No breakage.** Laravel silently ignores defaults for parameters the route does not declare. The team URL prefix pattern simply does not apply to Filament URLs.                                                                                                                                                                                                                                                                                                                                                 |
| 5   | Fortify auth routes at root: `/login`, `/register`, `/two-factor-challenge`, `/password/*`, `/passkeys/*` (`config/fortify.php` `prefix => ''`) | Filament panel auth slugs `login`, `register`, `password-reset/reset`, `email-verification/verify` — but ONLY registered if `->login()`, `->registration()` etc. are called on the panel | **RESOLUTION:** Do NOT call `->login()` / `->registration()` / `->passwordReset()` on the Admin panel — rely on Fortify's existing auth surface. If a separate Filament-branded login IS desired, use `->loginRouteSlug('admin-login')` and a non-root prefix to avoid slug collision with Fortify's `/login`.                                                                                                                                                                                                    |
| 6   | `app/Http/Responses/LoginResponse.php` — redirects to `/{team.slug}{Fortify::redirects('login')}` (i.e. `/{team.slug}/dashboard`)               | After successful Fortify login, user is bounced to `/{team.slug}/dashboard`, NOT `/admin`                                                                                                | **CONFLICT (cosmetic).** Admin users must manually navigate to `/admin` after login. Resolution options: (a) check `redirect()->intended()` first (Fortify already does this if `url.intended` is set, e.g. user hit `/admin` first → redirected to `/login` → comes back to `/admin`); (b) extend `LoginResponse` to inspect the user's roles and redirect super_admins to `/admin`. **The cleanest default:** rely on `redirect()->intended()` — Fortify already calls this. Verify with a manual test in T1.1. |
| 7   | `app/Http/Responses/TwoFactorLoginResponse.php` — same team-scoped redirect pattern                                                             | Same as #6 after 2FA challenge                                                                                                                                                           | **Same resolution.** `redirect()->intended()` should preserve the original intended URL across the 2FA challenge. Verify in T1.1.                                                                                                                                                                                                                                                                                                                                                                                 |
| 8   | `app/Policies/TeamPolicy.php` — owns team authorization                                                                                         | Shield generates resource policies                                                                                                                                                       | **No conflict.** Shield only generates policies for Resource-models that lack a policy; TeamPolicy is already registered. If a `TeamResource` is added under Admin panel, either exclude it via `filament-shield.resources.exclude` or let Shield generate a separate TeamResourcePolicy and merge carefully. Recommend exclude.                                                                                                                                                                                  |
| 9   | `App\Models\User` — single guard `web`, single user model, `HasTeams` trait                                                                     | Filament reads the same `App\Models\User` via the `web` guard; Shield adds `Spatie\Permission\Traits\HasRoles` trait                                                                     | **No conflict.** Trait composition: `use HasTeams, HasRoles;` — no method-name collisions (`HasTeams` exposes `teams()`, `teamRole()`, etc.; `HasRoles` exposes `roles()`, `hasRole()`, etc.).                                                                                                                                                                                                                                                                                                                    |
| 10  | `config/auth.php` — single guard `web`, single provider `users`                                                                                 | Filament default `authGuard('web')`                                                                                                                                                      | **No conflict.** Filament explicitly supports the default session guard. No new guard needed.                                                                                                                                                                                                                                                                                                                                                                                                                     |

---

## Fortify auth coexistence

**Key fact:** Filament v5 does NOT register auth routes unless you explicitly enable them on a panel (`->login()`, `->registration()`, etc.). Out of the box, an unauthenticated user hitting `/admin` is bounced to whatever `/login` route the app exposes — which is Fortify's.

**Verified auth flow with no Filament auth surface enabled:**

1. User navigates to `/admin`.
2. Filament's `auth` middleware (configured via `->authMiddleware([])`) checks the `web` guard.
3. Unauthenticated → redirect to `/login` (the app's default login route, owned by Fortify).
4. Fortify renders `pages::auth.login` view (`FortifyServiceProvider::configureViews`).
5. User submits credentials. Fortify's `LoginResponse` fires.
6. **If user has 2FA enabled** → Fortify renders `pages::auth.two-factor-challenge`. After successful challenge, `TwoFactorLoginResponse` fires.
7. **If passkey login** → `PasskeyLoginResponse` fires.
8. All three responses currently redirect to `redirect()->intended($this->redirectPathForCurrentTeam(...))`. The `intended()` fallback preserves the original `/admin` URL the user tried to visit.
9. User lands at `/admin`. Filament reads the session, finds `web` guard authenticated, lets them in.
10. `User::canAccessPanel(Panel $panel)` gates whether they may use the admin panel.

**2FA + passkeys:** No conflict. Fortify's 2FA challenge happens BEFORE Filament's session is established. Filament v5 does not ship its own 2FA flow, so Fortify remains the single source of truth for credential verification.

**Rate limiting:** Fortify's `limiters` config (`login`, `two-factor`, `passkeys`) applies to its own routes only. Filament's panel routes do not need additional rate limiting for auth because they delegate to Fortify.

**One real risk:** if the implementer DOES call `->login()` on a panel by mistake, two `/login` routes may register and behavior becomes non-deterministic. **T1.1 must explicitly forbid `->login()` / `->registration()` on the Admin panel** (and on product panels unless a deliberate decision is made).

**Sources:**

- https://filamentphp.com/docs/5.x/users (accessed 2026-07-19)
- https://filamentphp.com/docs/5.x/users/overview (accessed 2026-07-19)
- `app/Providers/FortifyServiceProvider.php:42-99`
- `app/Http/Responses/Concerns/RedirectsToCurrentTeam.php`

---

## Filament Shield integration

### Compatibility (verified)

- **Filament Shield 4.x** supports BOTH Filament 4.x and Filament 5.x (see the README's Compatibility table: "4.x → 4.x & 5.x").
- The Shield v4 README explicitly carries a "FILAMENT 5.x" badge linked to `https://filamentphp.com/docs/5.x/panels/installation`.
- Spatie Permission is pulled in transitively by Shield — no separate `composer require spatie/laravel-permission` needed.

### Install (decision #13: Admin-only Shield)

```bash
# 1. Require Shield (pulls Spatie Permission transitively).
composer require bezhansalleh/filament-shield

# 2. Publish Shield's config.
php artisan vendor:publish --tag="filament-shield-config"

# 3. Add HasRoles trait to App\Models\User (alongside existing HasTeams).
#    User model: use HasTeams, HasRoles; (no method-name conflicts).

# 4. Set 'auth_provider_model' => 'App\\Models\\User' in config/filament-shield.php.

# 5. Run interactive setup. Targets the Admin panel by default (panel id 'admin').
php artisan shield:setup

# 6. Generate permissions + policies for Admin-panel resources only.
php artisan shield:generate --all --option=policies_and_permissions --panel=admin

# 7. Create the super_admin role and assign to a user.
php artisan shield:super-admin --user=1 --panel=admin
```

### Scope-to-Admin-panel approach

Per decision #13, Shield must NOT generate permissions for the 3 product panels. The mechanism:

1. **Register the `FilamentShieldPlugin` ONLY in `AdminPanelProvider`**, NOT in Chinook/Northwind/Pagila panel providers:

    ```php
    // AdminPanelProvider.php
    ->plugins([
        \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
    ])
    ```

2. **Always pass `--panel=admin`** to `shield:generate`, `shield:super-admin`, `shield:seeder`. The `--panel` flag scopes entity discovery and permission generation to that panel only.
3. **Exclude TeamResource** (if one ever exists) in `config/filament-shield.php`:

    ```php
    'resources' => [
        'exclude' => [
            \App\Filament\Admin\Resources\TeamResource::class,
        ],
    ],
    ```

    This preserves the starter's `TeamPolicy` as the sole authority over team authorization.

4. **Spatie team-tenancy OFF.** Set `config('permission.teams')` to `false` (the default) — Shield's permissions should be global (role-based), not team-scoped. The starter already has its own team-role system (`App\Enums\TeamRole`); conflating it with Spatie's team_id scoping would create two parallel role systems on the same `team_id` field.

### Coexistence with TeamPolicy

- Shield generates policies for **Filament Resource models** (Chinook models, etc.), not for the starter's `Team` model — UNLESS a `TeamResource` is added.
- The starter's existing `TeamPolicy` continues to govern team membership/CRUD operations on `App\Models\Team` via standard Laravel policy resolution.
- If Shield's policy generation accidentally produces a `TeamPolicy` (because the model has a Filament Resource), set `'resources.exclude'` per step 3 above. The Shield config has `'policies.merge' => true` by default — meaning its generator will MERGE methods with an existing policy rather than overwrite, which is the safe behavior.

**Sources:**

- https://github.com/bezansalleh/filament-shield/blob/main/README.md (accessed 2026-07-19) — Compatibility matrix, Installation, Resources.exclude, Policies.merge, Commands
- Context7 `/bezhansalleh/filament-shield` — install, configure, generate, scope

---

## Open risks for T1.1

1. **Login redirect after Fortify auth.** The starter's `LoginResponse` / `TwoFactorLoginResponse` redirect to `/{team.slug}/dashboard`, not `/admin`. `redirect()->intended()` _should_ preserve the original `/admin` URL through the login flow, but this needs a manual test in T1.1. If it does not work, modify `LoginResponse` to inspect a `super_admin` role and bounce to `/admin`.

2. **Auth middleware ordering.** `bootstrap/app.php` currently appends `SetTeamUrlDefaults` to the `web` middleware group. Filament's panel routes inherit the `web` group middleware. `SetTeamUrlDefaults` calls `URL::defaults(['current_team' => ..., 'team' => ...])` only if `$request->user()?->currentTeam` is non-null. This is safe but worth verifying that no Filament route accidentally takes a `{team}` parameter and inherits the default.

3. **Tailwind v4 layout.** Filament's panel layout (installed by `filament:install --panels`) is distinct from the Flux app layout. Vite must compile both. The starter's `vite.config.js` should not need changes — Filament injects its assets via `@filamentStyles` / `@filamentScripts` in its OWN layout, not the Flux one. Verify in T1.1 by hitting `/admin` and checking that no Tailwind classes are missing.

4. **Shield v4 → v5 forward-compat.** Shield 4.x currently targets both Filament 4.x and 5.x, but a future Shield 5.x release could split. Pin Shield in `composer.json` to a known-good minor (e.g. `^4.7`) at install time.

5. **Spatie Permission cache.** Spatie Permission uses a cache (`permission.cache` key). The starter's `AppServiceProvider` does not currently clear it on deploy. T3.3 should add `php artisan permission:cache:reset` to the deploy script. Out of scope here.

6. **Filament's `make:filament-user` writes to the existing `users` table.** The starter's User model has team relationships; the Filament user-creation command does not create a personal team. The new admin user will need `current_team_id` set or LoginResponse will abort(403). T1.1 must seed the admin user with a personal team OR adjust `RedirectsToCurrentTeam::currentTeam()` to fall back gracefully.

7. **`bootstrap/providers.php` registration.** Filament docs explicitly warn to verify that the new `AdminPanelProvider` lands in `bootstrap/providers.php`. The starter uses the Laravel 11+ flat-list providers pattern (verified at `bootstrap/providers.php:1-9`); the Filament installer SHOULD append it automatically, but verify post-install.

8. **Livewire 4 / Filament 5 co-confirmation.** Filament v5.0.0 release notes confirm Livewire v4.0 stable support (PR #18965). Livewire v4.1 is what the starter ships. No conflict, but if composer resolution picks Livewire 4.0.x over 4.1.x during the `-W` widen, downgrade risk exists. Pin `livewire/livewire:^4.1` after install.

---

## Sources

| Source                          | Type          | URL                                                                                                                                                                                                                                                        | Date accessed |
| ------------------------------- | ------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------- |
| Filament v5 installation        | Official docs | https://filamentphp.com/docs/5.x/introduction/installation                                                                                                                                                                                                 | 2026-07-19    |
| Filament v5 upgrade guide       | Official docs | https://filamentphp.com/docs/5.x/upgrade-guide                                                                                                                                                                                                             | 2026-07-19    |
| Filament v5 panel configuration | Official docs | https://filamentphp.com/docs/5.x/panel-configuration                                                                                                                                                                                                       | 2026-07-19    |
| Filament v5 users (auth)        | Official docs | https://filamentphp.com/docs/5.x/users, /5.x/users/overview                                                                                                                                                                                                | 2026-07-19    |
| Filament v5 tenancy             | Official docs | https://filamentphp.com/docs/5.x/users/tenancy                                                                                                                                                                                                             | 2026-07-19    |
| Filament v5.0.0 release         | GitHub        | https://github.com/filamentphp/filament/releases/tag/v5.0.0                                                                                                                                                                                                | 2026-07-19    |
| Filament Shield README          | GitHub        | https://github.com/bezansalleh/filament-shield/blob/main/README.md                                                                                                                                                                                         | 2026-07-19    |
| Filament v5 docs (Context7)     | Context7      | `/websites/filamentphp_5_x`                                                                                                                                                                                                                                | 2026-07-19    |
| Filament Shield docs (Context7) | Context7      | `/bezhansalleh/filament-shield`                                                                                                                                                                                                                            | 2026-07-19    |
| Livewire v4 upgrade guide       | Official docs | https://livewire.laravel.com/docs/4.x/upgrading                                                                                                                                                                                                            | 2026-07-19    |
| Livewire v4 navigate/pages      | Official docs | https://livewire.laravel.com/docs/4.x/navigate, /4.x/pages                                                                                                                                                                                                 | 2026-07-19    |
| Starter kit source (repo)       | Local files   | `composer.json`, `app/Providers/*`, `app/Http/Middleware/*`, `app/Http/Responses/*`, `app/Policies/TeamPolicy.php`, `app/Concerns/HasTeams.php`, `config/auth.php`, `config/fortify.php`, `routes/web.php`, `bootstrap/app.php`, `bootstrap/providers.php` | 2026-07-19    |
