/**
 * Formats a number into Indonesian Rupiah (IDR) currency format.
 * Example: 30000 -> Rp 30.000
 * @param value Number to format
 * @param includeSymbol Whether to include the 'Rp ' prefix
 */
export const formatCurrency = (value: number | string, includeSymbol = true) => {
  const num = typeof value === 'string' ? parseFloat(value) : value;
  
  if (isNaN(num)) return includeSymbol ? 'Rp 0' : '0';

  const formatted = new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(num);

  return includeSymbol ? `Rp ${formatted}` : formatted;
};

/**
 * Formats a date into a local human-readable string.
 * @param dateString ISO date or date object
 */
export const formatDate = (dateString: string | Date | null) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
};
