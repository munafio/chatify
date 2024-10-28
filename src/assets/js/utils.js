/**
 * changes date string to time ago string.
 * @param dateString - The date string to convert to a time ago string.
 * @returns A string that tells the user how long ago the date was.
 */
function dateStringToTimeAgo(dateString) {
  const now = new Date();
  const date = new Date(dateString);
  const seconds = Math.floor((now - date) / 1000);
  const minutes = Math.floor(seconds / 60);
  const hours = Math.floor(minutes / 60);
  const days = Math.floor(hours / 24);
  const weeks = Math.floor(days / 7);
  if (seconds < 60) {
    return "just now";
  } else if (minutes < 60) {
    return `${minutes}m ago`;
  } else if (hours < 24) {
    return `${hours}h ago`;
  } else if (days < 7) {
    return `${days}d ago`;
  } else {
    return `${weeks}w ago`;
  }
}
/**
 * It returns a function that, when invoked, will wait for a specified amount of time before executing
 * the original function.
 * @param callback - The function to be executed after the delay.
 * @param delay - The amount of time to wait before calling the callback.
 * @returns A function that will call the callback function after a delay.
 */
function debounce(callback, delay) {
  let timerId;
  return function (...args) {
    clearTimeout(timerId);
    timerId = setTimeout(() => {
      callback.apply(this, args);
    }, delay);
  };
}

/**
 * Escapes special characters in a string to prevent XSS and other injection issues.
 * This function converts characters like `<`, `>`, `&`, and `"` into corresponding HTML entities,
 * preventing any inserted HTML or JavaScript code from being interpreted or executed.
 * 
 * @param {string} inputValue - The potentially unsafe input string that needs to be sanitized.
 * @returns {string} - The safe output string with special characters escaped as HTML entities.
 * 
 * Example usage:
 * const unsafeString = '<script>alert("XSS!")</script>';
 * const safeString = sanitizeInput(unsafeString);
 * console.log(safeString); // Output: "&lt;script&gt;alert(&quot;XSS!&quot;)&lt;/script&gt;"
 */


function sanitizeInput(inputValue) {
  const element = document.createElement('div');
  element.textContent = inputValue; // Escapa HTML ao inserir como texto puro
  return element.innerHTML; // Retorna o HTML escapado
}
