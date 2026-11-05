# AGENTS.md â My Car

> Working rules only. Technical knowledge lives in topic docs â read on demand, never preload.

## What the project is

The "My Car" WordPress plugin:
main file + PHP classes (`/includes/iworks`) + options config (`/etc/options.php`)
+ assets (`/assets`), built with a bun bundler (`/bundler` → `release/` ZIPs);
sources contain build-time placeholders (`PLUGIN_VERSION`, `CURRENT_YEAR`, …)

## Docs map â read only what the task needs

| Task | Read first |
| --- | --- |
| PHP classes, admin layer, post types, options, new class | `.opencode/docs/classes.md` |
| Bun bundler, build, dev watcher, ZIPs, placeholders, translations (.pot/.po) | `.opencode/docs/build.md` |
| Loading flow, assets, enqueue, i18n, dependencies, project conventions | `.opencode/docs/project.md` |
| After any task that changed code | skill `verify` (`.opencode/skills/verify/SKILL.md`) |

- Read ONLY the docs relevant to the current task â do not load everything upfront.
- When a change alters documented behavior, update the corresponding doc in the same task.

## Evergreen rules (every task)

- All documentation and README in English.
- `CHANGELOG` is edited only by the user â never touch it unless the user explicitly asks
  (desired format when asked: `## X.Y.Z - RRRR.MM`, categories `Added` / `Improved` / `Fixed` /
  `Removed` (tabs), English, concise).
- Never commit, push, amend or create PRs without an explicit request.
- Naming: kebab-case files (`class-iworks-my-car-{name}.php`);
  PHP classes `iworks_my_car_` prefix, snake_case, lowercase.
- Version lives only in `package.json` â placeholders (`PLUGIN_VERSION`,
  `BUILDTIMESTAMP`) are replaced by the bundler during build; never hardcode real values in sources.
- Do not fix known typos/pitfalls without approval (see topic docs).

## Commands

- `bun run dev` â watcher, compile assets on change
- `bun run compile` â one-shot asset compile
- `bun run build` â production build, both ZIPs (wporg + github)
- `bun run build:wporg` â production build, wp.org ZIP only
- `bun run build:github` â production build, GitHub ZIP only
- `bun run make-pot` â translations (.pot + .po update)
- `bun run install-libs <https|git> <option|rate|all>` â add iWorks libraries as git submodules
- `bun run update-libs` â git pull on the installed iWorks submodules
- `bun run test` â PHPUnit
- `postinstall` â runs automatically on `bun install` (downloads wp-cli.phar)
