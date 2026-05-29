/**
 * Efficient positioning utilities to replace getBoundingClientRect
 * These functions avoid layout thrashing and provide better performance
 */

/**
 * Get element position and dimensions using offset properties
 * More efficient than getBoundingClientRect for repeated calls
 */
export const getElementPosition = (element) => {
  if (!element) return { left: 0, top: 0, width: 0, height: 0, right: 0, bottom: 0 };
  
  const rect = {
    left: element.offsetLeft,
    top: element.offsetTop,
    width: element.offsetWidth,
    height: element.offsetHeight
  };
  
  // Calculate right and bottom
  rect.right = rect.left + rect.width;
  rect.bottom = rect.top + rect.height;
  
  return rect;
};

/**
 * Get element position relative to viewport (including scroll)
 * Similar to getBoundingClientRect but more efficient
 */
export const getElementViewportPosition = (element) => {
  if (!element) return { left: 0, top: 0, width: 0, height: 0, right: 0, bottom: 0 };
  
  const rect = getElementPosition(element);
  
  // Add scroll offset for viewport-relative positioning
  const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
  const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
  
  return {
    left: rect.left + scrollLeft,
    top: rect.top + scrollTop,
    width: rect.width,
    height: rect.height,
    right: rect.right + scrollLeft,
    bottom: rect.bottom + scrollTop
  };
};

/**
 * Get element position relative to document (absolute positioning)
 * Most efficient for positioning calculations
 */
export const getElementAbsolutePosition = (element) => {
  if (!element) return { left: 0, top: 0, width: 0, height: 0, right: 0, bottom: 0 };
  
  let left = 0;
  let top = 0;
  let current = element;
  
  // Calculate absolute position by traversing up the DOM tree
  while (current && current !== document.body) {
    left += current.offsetLeft - current.scrollLeft + current.clientLeft;
    top += current.offsetTop - current.scrollTop + current.clientTop;
    current = current.offsetParent;
  }
  
  return {
    left,
    top,
    width: element.offsetWidth,
    height: element.offsetHeight,
    right: left + element.offsetWidth,
    bottom: top + element.offsetHeight
  };
};

/**
 * Cache element dimensions to avoid repeated measurements
 * Useful for elements that don't change size frequently
 */
export const createElementCache = () => {
  const cache = new Map();
  
  return {
    get: (element, key = 'default') => {
      const cacheKey = `${element?.tagName || 'unknown'}-${key}`;
      return cache.get(cacheKey);
    },
    
    set: (element, data, key = 'default') => {
      const cacheKey = `${element?.tagName || 'unknown'}-${key}`;
      cache.set(cacheKey, data);
    },
    
    clear: () => cache.clear(),
    
    // Get cached position or calculate and cache
    getPosition: (element, key = 'default') => {
      let position = cache.get(element, key);
      if (!position) {
        position = getElementPosition(element);
        cache.set(element, position, key);
      }
      return position;
    }
  };
};

/**
 * Debounced position update for performance
 * Useful for resize events and frequent updates
 */
export const createDebouncedPositionUpdater = (callback, delay = 16) => {
  let timeoutId = null;
  
  return (...args) => {
    if (timeoutId) clearTimeout(timeoutId);
    timeoutId = setTimeout(() => callback(...args), delay);
  };
};

/**
 * Check if element is in viewport efficiently
 * Alternative to IntersectionObserver for simple cases
 */
export const isElementInViewport = (element) => {
  if (!element) return false;
  
  const rect = getElementViewportPosition(element);
  const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
  const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
  
  return (
    rect.left >= 0 &&
    rect.top >= 0 &&
    rect.right <= viewportWidth &&
    rect.bottom <= viewportHeight
  );
};

/**
 * Get distance between two elements
 * More efficient than using getBoundingClientRect
 */
export const getElementDistance = (element1, element2) => {
  if (!element1 || !element2) return 0;
  
  const pos1 = getElementAbsolutePosition(element1);
  const pos2 = getElementAbsolutePosition(element2);
  
  const center1 = {
    x: pos1.left + pos1.width / 2,
    y: pos1.top + pos1.height / 2
  };
  
  const center2 = {
    x: pos2.left + pos2.width / 2,
    y: pos2.top + pos2.height / 2
  };
  
  const deltaX = center2.x - center1.x;
  const deltaY = center2.y - center1.y;
  
  return Math.sqrt(deltaX * deltaX + deltaY * deltaY);
};
