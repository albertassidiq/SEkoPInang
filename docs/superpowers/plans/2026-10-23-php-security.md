# PHP security update implementation plan

> Execute inline in the existing clone; the user authorized fixing and pushing the project.

**Goal:** Prepare SEkoPInang for patched PHP and remove known Composer dependency advisories without changing its business flows.

**Architecture:** Retain the existing Laravel application structure and database schema. Upgrade Laravel 9 to current Laravel 13, Jetstream 5, Sanctum 4 and Livewire 4. Require patched PHP 8.5, with a dependency-free preflight and CI checks.

**Tech stack:** PHP 8.5.12, Laravel 13, Blade, Livewire 4, Vite, MySQL; isolated SQLite tests and a CI MySQL job.

**Spec:** User request of 23 October 2026 and https://www.php.net/archive/2026.php#2026-10-21-5.

## Constraints and review focus

- PHP floor: 8.5.12; reject vulnerable patch releases, prereleases, EOL branches and untested branches.
- A repository change cannot repair a vulnerable PHP process; web handlers, CLI, queues and scheduled workers must be upgraded and restarted by the hosting operator.
- Keep the production database and APP_KEY intact; test with synthetic records only.
- Preserve public forms, private dashboard, disabled registration and profile/2FA interactions.
- Avoid duplicate Alpine initialization when moving to Livewire 4; verify browser interactions.

## Task 1: Establish verification and migrate dependencies

- [x] Record baseline Composer audit and existing test results using patched PHP.
- [x] Configure isolated test DB and application key, and build assets.
- [x] Update composer.json / composer.lock to supported framework packages; do not suppress security advisories.
- [x] Apply necessary Livewire 4 integration changes in JS and Blade.
- [x] Run the full PHP suite, Composer audit, asset build and application boot checks; fix observed migration regressions.

## Task 2: Enforce patched runtimes

- [x] Write version-boundary tests in tests/Unit/PhpRuntimeTest.php; demonstrate missing policy failure.
- [x] Add app/Support/PhpRuntime.php and scripts/check-php-runtime.php; wire Composer pre-install/pre-update hooks.
- [x] Verify rejection on the existing PHP 8.2.12 and acceptance on patched PHP; run boundary tests and full suite.
- [x] Add CI for PHP 8.5.12 with SQLite and MySQL: runtime preflight, install, audit, asset build and tests.

## Task 3: Operational handoff and push

- [x] Update README requirements and add docs/security/php2shell-2026.md with VM/hosting/container upgrade, restart, evidence and rollback instructions.
- [x] Run final tests, audit, build, lint and diff review; use an independent code reviewer.
- [x] Commit and push via albertassidiq; verify remote commit and CI. State explicitly that production PHP remains unverified until hosting access/evidence is supplied.

## Execution record

- Clone baseline: eeef1b2. GitHub owner/account verified as albertassidiq (Albert Assidiq), repository permission ADMIN.
- Local system runtime: XAMPP PHP 8.2.12. Separate official PHP 8.3.36 downloaded with SHA-256 verification for testing; system XAMPP untouched.
- Baseline Composer audit: 47 advisories affecting 14 packages, including the EOL Laravel 9 branch.
- Ruling: expand dependency migration because patched PHP plus the old framework leaves known application dependency advisories; cost is a wider regression test surface. User subsequently requested the newest releases, so the final targets are PHP 8.5.12 / Laravel 13 / Livewire 4.
- Ruling: work on fix/php2shell-security in the requested clone; push destination will be the existing repository, without rewriting history.
- Baseline verification: existing feature suite plus new compatibility coverage ran on PHP 8.3.36 with 34 tests / 67 assertions / 8 skipped; new coverage exposed the pre-existing absent `kedai-kopi.index` view. Retained access-control coverage for that route and limited authenticated rendering checks to existing views; missing CRUD templates remain a documented pre-existing limitation.
- Runtime policy tests failed for the missing policy before implementation. An additional probe rejected the initial policy because it accepted `8.5.13RC1`; explicit stable-version parsing corrects that case.
- Validation uses a source copy on the local C: drive because extracting dependencies on E: was slow. Production sources and lockfiles remain in E:/SEKOPINANG. A standalone, checksum-verified PHP 8.5.12 and Composer 2.10.3 are used without changing system XAMPP.
- Local final suite: 37 tests, 87 assertions, 29 passed / 8 disabled-feature skips, zero warnings/deprecations. Composer validate/platform/audit pass; npm production audit clean with 8 disclosed development-tool advisories. Browser login/profile/password-confirmation/2FA setup-cancel pass. Config, route and view caches compile.
- Windows optimized autoload generation was slow; local dependency install and final tests use an ordinary autoloader, with composer.json restored byte-for-byte afterward. CI retains the actual optimized-autoloader setting and must verify it on Linux.
- Independent read-only review: no Critical, Important or actionable Minor findings. Production patch status and existing missing CRUD templates were deliberately not certified.
- Code commit cb1ba45 pushed as albertassidiq. GitHub Actions run https://github.com/albertassidiq/SEkoPInang/actions/runs/39935279822 passed both SQLite and MySQL jobs: 37 tests / 87 assertions / 8 disabled-feature skips per job, including Composer/npm production audits, optimized dependency install, asset build and production cache compilation. Main receives the tested code by fast-forward, followed only by this verification record.
