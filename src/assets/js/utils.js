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
 * Sanitizes a potentially unsafe string to prevent XSS and other injection issues.
 * This function escapes special characters such as `<`, `>`, `&`, and `"` by converting
 * them into their corresponding HTML entities. This ensures that any inserted HTML or JavaScript
 * code is treated as plain text and not executed by the browser.
 * 
 * @param {string} inputValue - The input string that may contain unsafe characters.
 * @returns {string} - A sanitized output string with special characters converted to HTML entities.
 * 
 * @throws {Error} - Throws an error if the input is not a string.
 * 
 * Example usage:
 * const unsafeString = '<script>alert("XSS!")</script>';
 * const safeString = sanitizeInput(unsafeString);
 * console.log(safeString); // Output: "&lt;script&gt;alert(&quot;XSS!&quot;)&lt;/script&gt;"
 * 
 * Test cases:
 * const testInputs = [
 *   "<script>alert('XSS')</script>",
 *   "<img src=x onerror=alert('XSS')>",
 *   "<iframe src='javascript:alert(\"XSS\")'></iframe>",
 *   "><script>alert('XSS')</script>",
 *   "<body onload=alert('XSS')>",
 *   "<img src='data:image/svg+xml;base64,PHN2ZyBvbmxvYWQ9YWxlcnQoJ1hTUycpPjwvc3ZnPg=='>",
 *   "<div style='width: expression(alert(\"XSS\"))'>",
 *   "<a href='javascript:alert(\"XSS\")'>Click me</a>",
 *   "<div onmouseover=alert('XSS')>Hover me</div>",
 *   "<script>alert(String.fromCharCode(88,83,83))</script>",
 *   "<svg/onload=alert('XSS')>",
 *   "<form><button formaction=javascript:alert('XSS')>Submit</button></form>",
 *   "<div style='background-image: url(javascript:alert(\"XSS\"))'>",
 *   "<img src='data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0nMS4wJyBlbmNvZGluZz0nVVRGLTgnPz48c3ZnIG9ubG9hZD0nYWxlcnQoIkhBQ0tFRCIpJz4KPC9zdmc+Cg==' />"
 * ];
 */



function sanitizeInput(inputValue) {
  if (typeof inputValue !== 'string') {
    return '';
  }

  if (!/^[a-zA-Z0-9\s\-_]+$/.test(inputValue)) {
    return inputValue
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }
  return inputValue

}
