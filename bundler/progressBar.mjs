/**
 * Minimal progress bar for the build pipeline.
 */

const WIDTH = 30;

/**
 * Shared dirty-line flag: the bar writes without a trailing newline, so
 * log() knows when to break the line first.
 */
export const progressState = { dirty: false };

export class ProgressBar {
  constructor(total) {
    this.total = total;
    this.current = 0;
    this.label = "";
  }

  /**
   * Advance one step.
   *
   * @param {string} label Label of the finished step.
   */
  tick(label = "") {
    this.current += 1;
    this.label = label;
    const ratio = Math.min(this.current / this.total, 1);
    const filled = Math.round(ratio * WIDTH);
    const bar = "█".repeat(filled) + "░".repeat(WIDTH - filled);
    process.stdout.write(
      `\r\x1b[K${bar} ${this.current}/${this.total} ${this.label}`,
    );
    progressState.dirty = true;
    if (this.current >= this.total) {
      process.stdout.write("\n");
      progressState.dirty = false;
    }
  }
}
