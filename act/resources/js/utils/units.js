/**
 * Convert a pixel value to vmin units based on the current (or provided) viewport.
 *
 * vmin is 1% of the smaller viewport dimension (min(width, height)).
 *
 * @param {number} pixels - The pixel value to convert.
 * @param {Object} [options]
 * @param {number} [options.viewportWidth] - Optional explicit viewport width in pixels.
 * @param {number} [options.viewportHeight] - Optional explicit viewport height in pixels.
 * @returns {number} - The equivalent value in vmin units (numeric, without the 'vmin' suffix).
 */
export function pxToVmin(pixels, options) {
  const opts = options || {};

  const inferredWidth = typeof window !== "undefined" ? window.innerWidth : undefined;
  const inferredHeight = typeof window !== "undefined" ? window.innerHeight : undefined;

  const viewportWidth = typeof opts.viewportWidth === "number" ? opts.viewportWidth : inferredWidth;
  const viewportHeight = typeof opts.viewportHeight === "number" ? opts.viewportHeight : inferredHeight;

  if (
    typeof pixels !== "number" ||
    !isFinite(pixels) ||
    typeof viewportWidth !== "number" ||
    typeof viewportHeight !== "number" ||
    viewportWidth <= 0 ||
    viewportHeight <= 0
  ) {
    return 0;
  }

  const oneVminInPx = Math.min(viewportWidth, viewportHeight) / 100;
  return pixels / oneVminInPx;
}

/**
 * Helper to format a pixel value directly into a CSS-ready string in vmin units.
 *
 * @param {number} pixels - The pixel value to convert.
 * @param {Object} [options]
 * @returns {string} - A string like "3.5vmin".
 */
export function pxToVminString(pixels, options) {
  const value = pxToVmin(pixels, options);
  return `${value}vmin`;
}


