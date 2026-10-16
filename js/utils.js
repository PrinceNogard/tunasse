/**
 * DuoBudget • Utilities & Formatting Module
 * Pure functions with zero DOM dependencies.
 */

const fmtEUR = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' });

/**
 * Format a number into standard French currency format (e.g. 1 250,00 €)
 * @param {number|string} val 
 * @returns {string}
 */
export function formatCurrency(val) {
  const num = Number(val);
  return fmtEUR.format(Number.isFinite(num) ? num : 0);
}

/**
 * Format YYYY-MM-DD date string to French DD/MM/YYYY
 * @param {string} dateStr 
 * @returns {string}
 */
export function formatDateFR(dateStr) {
  if (!dateStr || typeof dateStr !== 'string') return '';
  const parts = dateStr.split('-');
  if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
  return dateStr;
}

/**
 * Compute the number of calendar months elapsed since startMonth (format: YYYY-MM)
 * @param {string} startMonthStr 
 * @param {Date} [referenceDate] 
 * @returns {number}
 */
export function computeMonthsElapsedFromStart(startMonthStr, referenceDate = new Date()) {
  if (!startMonthStr || typeof startMonthStr !== 'string') return 0;
  const parts = startMonthStr.split('-').map(Number);
  if (parts.length !== 2 || isNaN(parts[0]) || isNaN(parts[1])) return 0;
  
  const [sY, sM] = parts;
  const cY = referenceDate.getFullYear();
  const cM = referenceDate.getMonth() + 1;
  const diff = (cY - sY) * 12 + (cM - sM);
  return Math.max(0, diff);
}

/**
 * Compute the starting YYYY-MM from a given number of months elapsed
 * @param {number} monthsElapsed 
 * @param {Date} [referenceDate] 
 * @returns {string}
 */
export function computeStartMonthFromMonthsElapsed(monthsElapsed, referenceDate = new Date()) {
  const safeMonths = Math.max(0, Math.floor(Number(monthsElapsed) || 0));
  const targetDate = new Date(referenceDate.getFullYear(), referenceDate.getMonth() - safeMonths, 1);
  const y = targetDate.getFullYear();
  const m = String(targetDate.getMonth() + 1).padStart(2, '0');
  return `${y}-${m}`;
}

/**
 * Compute end date text (e.g. 'Avril 2041') from startMonth and total duration in months
 * @param {string} startMonthStr 
 * @param {number} totalMonths 
 * @returns {string}
 */
export function computeEndDate(startMonthStr, totalMonths) {
  if (!startMonthStr || !totalMonths) return '';
  const parts = startMonthStr.split('-').map(Number);
  if (parts.length !== 2) return '';
  
  const [sY, sM] = parts;
  const endTotalMonths = (sY * 12 + (sM - 1)) + Number(totalMonths);
  const endYear = Math.floor(endTotalMonths / 12);
  const endMonth = (endTotalMonths % 12) + 1;
  
  const monthNames = [
    'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
  ];
  return `${monthNames[endMonth - 1]} ${endYear}`;
}

/**
 * Generate human-readable remaining time text
 * @param {number} remainingMonths 
 * @returns {string}
 */
export function computeRemainingTimeText(remainingMonths) {
  const rem = Math.max(0, Math.round(Number(remainingMonths) || 0));
  if (rem === 0) return '🎉 Remboursement entièrement terminé !';
  
  const years = Math.floor(rem / 12);
  const months = rem % 12;
  const parts = [];
  if (years > 0) parts.push(`${years} an${years > 1 ? 's' : ''}`);
  if (months > 0) parts.push(`${months} mois`);
  
  const text = parts.join(' et ');
  return `⏱️ ${text} restant${rem > 1 ? 's' : ''} (${rem} mensualité${rem > 1 ? 's' : ''})`;
}

/**
 * Sanitize cell values for CSV formatting (handles quotes, semicolons)
 * @param {string|number} val 
 * @returns {string}
 */
export function sanitizeCsvCell(val) {
  if (val === null || val === undefined) return '""';
  const str = String(val).replace(/"/g, '""');
  return `"${str}"`;
}
