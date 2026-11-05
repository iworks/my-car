#!/usr/bin/env bun
/**
 * Bundler entry point — replaces the old Grunt pipeline.
 *
 * Modes:
 *   dev (default) — watcher, compile on change (no ZIP, no i18n)
 *   compile       — one-shot asset compile (old `grunt` default task)
 *   build         — both flavors: build:wporg + build:github
 *   build:wporg   — production build → release/wporg/{name}.zip (with Plugin Check)
 *   build:github  — production build + .mo → release/github/{name}.zip
 *   make-pot      — translations only (.pot + .po update)
 *   update-libs   — git pull on the vendored iWorks libraries (submodules)
 *   install-libs  — git submodule add for iWorks libs: <https|git> <option|rate|all>
 *   test          — PHPUnit (tests/php/phpunit.xml)
 */

import { dirname, join, resolve } from "node:path";
import { existsSync, watch } from "node:fs";
import { executionTime, log } from "./consoleHelpers.mjs";
import { ProgressBar } from "./progressBar.mjs";
import { spawnAsync } from "./spawnAsync.mjs";
import { config } from "./config.mjs";
import {
  buildCSS,
  buildJS,
  formatSources,
  cleanCompiled,
} from "./pipelines.mjs";
import { prettierFile } from "./tools.mjs";
import { makeZip } from "./makeZip.mjs";
import { makePot } from "./makePot.mjs";
import { pluginCheck } from "./pluginCheck.mjs";

const root = resolve(dirname(new URL(import.meta.url).pathname), "..");

/**
 * Friendly error printer — one-liners, no stack traces.
 *
 * @param {*} error Thrown error.
 */
function printError(error) {
  const message = error?.friendly
    ? error.message
    : error?.stack || String(error);
  log(message, "e");
}

/**
 * One-shot asset compile (CSS + JS).
 *
 * @param {object} options
 * @param {boolean} options.maps Write source maps.
 * @param {boolean} options.strip Strip sourceMappingURL comments.
 * @param {boolean} options.format Run the prettier pass first.
 */
async function compileAssets({
  maps = false,
  strip = true,
  format = false,
} = {}) {
  if (format) {
    await formatSources();
  }
  await buildCSS({ maps, strip });
  await buildJS();
}

/**
 * Production build for one flavor.
 *
 * @param {string} flavor "wporg" or "github".
 */
async function build(flavor) {
  const start = Date.now();
  try {
    const steps = flavor === "wporg" ? 8 : 7;
    const bar = new ProgressBar(steps);
    bar.tick("clean");
    await cleanCompiled();
    bar.tick("prettier");
    await formatSources();
    bar.tick("css");
    await buildCSS({ maps: false, strip: true });
    bar.tick("js");
    await buildJS();
    bar.tick("i18n");
    await makePot(flavor === "github");
    bar.tick("zip");
    await makeZip(flavor);
    if (flavor === "wporg") {
      bar.tick("plugin-check");
      await pluginCheck(config.name, config.version);
    }
    bar.tick("done");
    log(
      `BUILD OK (${flavor}) in ${((Date.now() - start) / 1000).toFixed(2)}s`,
      "s",
    );
  } catch (error) {
    log("BUILD FAILED", "e");
    printError(error);
    process.exit(1);
  }
}

/**
 * Dev watcher — compile changed assets, no ZIP, no i18n.
 */
function dev() {
  const exitAfterMs = Bun.env.DEV_EXIT_AFTER_MS || "";
  if (exitAfterMs) {
    setTimeout(() => {
      log(`dev — exiting after ${exitAfterMs} ms (DEV_EXIT_AFTER_MS)`, "i");
      process.exit(0);
    }, Number(exitAfterMs));
  }
  log(
    "dev — watching for changes (no auto browser reload, refresh manually)",
    "i",
  );

  let timer = null;
  const queue = new Set();

  const processQueue = async () => {
    timer = null;
    const changed = [...queue];
    queue.clear();
    const scss = changed.some((f) => f.endsWith(".scss"));
    const js = changed.some(
      (f) => f.startsWith("assets/scripts/src/") && f.endsWith(".js"),
    );
    if (!scss && !js) {
      return;
    }
    try {
      if (js) {
        for (const file of changed.filter(
          (f) => f.startsWith("assets/scripts/src/") && f.endsWith(".js"),
        )) {
          await prettierFile(join(root, file));
        }
      }
      await compileAssets({ maps: true, strip: false });
      log(`dev — rebuilt (${changed.length} changed)`, "s");
    } catch (error) {
      printError(error);
    }
  };

  watch(root, { recursive: true }, (event, filename) => {
    if (!filename) {
      return;
    }
    const rel = filename.replace(/\\/g, "/");
    const relevant =
      (rel.startsWith("assets/sass/") && rel.endsWith(".scss")) ||
      (rel.startsWith("assets/scripts/src/") && rel.endsWith(".js"));
    if (!relevant) {
      return;
    }
    queue.add(rel);
    if (!timer) {
      timer = setTimeout(processQueue, 200);
    }
  });
}

/**
 * Update vendored iWorks libraries (git submodules).
 */
async function updateLibs() {
  const libs = [
    ["includes/iworks/options", "iWorks Options"],
    ["includes/iworks/rate", "iWorks Rate"],
  ];
  let failed = 0;
  for (const [dir, label] of libs) {
    if (!existsSync(join(root, dir))) {
      log(`${label} — ${dir} not found, skipped (git submodule add?)`, "w");
      continue;
    }
    const result = await spawnAsync("git", ["pull"], `git pull ${dir}`, false);
    if (result.stdout.trim()) {
      console.log(result.stdout.trim());
    }
    if (result.code !== 0) {
      failed++;
    }
  }
  if (failed) {
    const err = new Error(`${failed} library update(s) failed`);
    err.friendly = true;
    throw err;
  }
  log("update-libs — all iWorks libraries updated", "s");
}

/**
 * Install vendored iWorks libraries as git submodules.
 *
 * Usage: bun run install-libs <https|git> <option|rate|all>
 *
 * @param {string[]} args [protocol, module].
 */
function installLibsUsage() {
  log("Usage: bun run install-libs <https|git> <option|rate|all>", "e");
  process.exit(1);
}

async function installLibs(args) {
  const [protocol, module] = args || [];
  if (!protocol || !module) {
    installLibsUsage();
  }
  if (!["https", "git"].includes(protocol)) {
    log(`Invalid protocol "${protocol}". Use https or git.`, "e");
    installLibsUsage();
  }
  if (!["option", "rate", "all"].includes(module)) {
    log(`Invalid module "${module}". Use option, rate or all.`, "e");
    installLibsUsage();
  }
  const repos = {
    option: {
      repo: "iworks/wordpress-options-class.git",
      path: "includes/iworks/options",
      label: "iWorks Options",
    },
    rate: {
      repo: "iworks/iworks-rate.git",
      path: "includes/iworks/rate",
      label: "iWorks Rate",
    },
  };
  const targets = module === "all" ? Object.values(repos) : [repos[module]];
  const url = (repo) =>
    (protocol === "https" ? "https://github.com/" : "git@github.com:") + repo;
  let failed = 0;
  for (const { repo, path, label } of targets) {
    // An empty placeholder dir (fresh clone of a repo with registered
    // submodules) does not count as installed — only a real checkout does.
    if (existsSync(join(root, path, ".git"))) {
      log(`${label} — ${path} already exists, skipped`, "w");
      continue;
    }
    log(`${label} — git submodule add ${url(repo)} ${path}`, "i");
    const result = await spawnAsync(
      "git",
      // --force reuses an existing local .git dir and recovers a
      // half-registered state (.gitmodules entry without a working dir).
      ["submodule", "add", "--force", url(repo), path],
      `git submodule add ${path}`,
      false,
    );
    if (result.stdout.trim()) {
      console.log(result.stdout.trim());
    }
    if (result.code !== 0) {
      failed++;
    }
  }
  if (failed) {
    const err = new Error(`${failed} submodule install(s) failed`);
    err.friendly = true;
    throw err;
  }
  log("install-libs — done. Next: bun install && bun run build", "s");
}

/**
 * PHPUnit (grunt `test` parity, jshint dropped).
 */
async function test() {
  const result = await spawnAsync(
    "phpunit",
    ["--configuration", "tests/php/phpunit.xml", "--colors=always"],
    "PHPUnit",
    false,
  );
  if (result.stdout.trim()) {
    console.log(result.stdout.trim());
  }
  if (result.code !== 0) {
    log(
      "If phpunit is not installed, set up a WordPress test environment (see tests/php/bootstrap.php).",
      "w",
    );
    process.exit(result.code);
  }
  log("PHPUnit passed", "s");
}

// ─── Mode dispatch ────────────────────────────────────────────────────────────

const mode = process.argv[2] || "dev";

const modes = {
  dev: () => dev(),
  compile: () =>
    executionTime("compile", () =>
      compileAssets({ maps: true, strip: false, format: false }),
    ),
  build: async () => {
    // github first: a failing wporg Plugin Check must not block the github ZIP.
    await build("github");
    await build("wporg");
  },
  "build:wporg": () => build("wporg"),
  "build:github": () => build("github"),
  "make-pot": () => executionTime("make-pot", () => makePot(false)),
  "update-libs": () => executionTime("update-libs", () => updateLibs()),
  "install-libs": () => installLibs(process.argv.slice(3)),
  test: () => test(),
};

if (!modes[mode]) {
  log(
    `Unknown mode "${mode}". Available: ${Object.keys(modes).join(", ")}`,
    "e",
  );
  process.exit(1);
}

try {
  await modes[mode]();
} catch (error) {
  printError(error);
  process.exit(1);
}
