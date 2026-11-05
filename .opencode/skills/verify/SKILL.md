---
name: verify
description: post-work verification — full project build and a check that everything works
---

When a task that changed code (PHP, JS, SCSS) is finished, **before reporting success** run:

## 1. Sanity-check diffs

- `git diff --stat` — compare the changes against the list of files you actually edited. **Any changed file you did not edit is an alarm signal** (a stray build/formatter step overwriting sources).
- The build's prettier step rewrites JS sources in place — formatting-only diffs in `assets/scripts/src/**` right after a build are expected, anything else is not.
- Compiled files (`assets/styles/`, `assets/scripts/my-car-*.js`, `languages/*.mo`) are gitignored — they should never appear in a diff at all.

## 2. Verification with a full build

Do not declare success based on a single quick command — run the **full pipeline** for the changed asset type (each must pass end-to-end):

- **JS (`assets/scripts/src/**`) and SCSS (`assets/sass/**`)**: full `bun run build:wporg` — must pass end-to-end (prettier → css → js → i18n → wporg ZIP). Use `bun run build:github` when the GitHub release path is affected.
- **PHP (`includes/iworks/my-car/`, `etc/`, main file, `assets/templates/`)**: `php -l` on every changed file (new files included), plus `~/.config/composer/vendor/bin/phpcs` (config: `phpcs.xml.dist`) — must not introduce *new* WPCS errors (security/escaping/i18n sniffs are hard requirements; remaining Squiz.Commenting style items are tracked debt). If a WordPress test env is available, `bun run test` (PHPUnit) is a bonus.
- **Bundler changes (`bundler/**`)**: `bun run compile` + `bun run build` (both flavors) and check the staged ZIP contents (rules order is load-bearing — see `build.md`).

## 3. Report

Summarize the build result and the list of verified files. If the build does not pass — fix it before reporting task completion (or clearly list the blockers for the user to decide). Never write "done" without a successful completion of step 2.
