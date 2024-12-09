/**
 * Utilities used by various components of the Brandfolder browser.
 */


/**
 * Format a date (or date+time) string according to our preferred
 * date-only format.
 */
export function bfBrowserFormatDate(date: string) {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(new Date(date))
}

/**
 * Format a date+time string according to our preferred format.
 */
export function bfBrowserFormatDateAndTime(datetime: string) {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: 'numeric',
    minute: 'numeric',
  }).format(new Date(datetime))
}

/**
 * Format a file size (in bytes) as a human-readable string.
 */
export function bfBrowserFormatFilesize(size: number) {
  let sizeString = ''
  if (size) {
    const units = ['B', 'KB', 'MB', 'GB', 'TB']
    let unitIndex = 0
    let sizeInUnits = size
    while (sizeInUnits >= 1024 && unitIndex < units.length - 1) {
      sizeInUnits /= 1024
      unitIndex++
    }
    sizeString = `${sizeInUnits.toFixed(1)} ${units[unitIndex]}`
  }

  return sizeString
}
