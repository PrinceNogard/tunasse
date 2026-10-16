/**
 * DuoBudget • Travaux UI & CSV Generator Module
 * Pure presentation formatting helpers for Renovation Lots & Expenses
 */

import { formatCurrency, sanitizeCsvCell } from './utils.js';

/**
 * Get status pill configuration for a renovation lot
 * @param {string} status 
 * @returns {{ label: string, cssClass: string }}
 */
export function getLotStatusConfig(status) {
  switch (status) {
    case 'ongoing':
      return { label: 'En cours', cssClass: 'status-ongoing' };
    case 'completed':
      return { label: 'Clôturé', cssClass: 'status-completed' };
    case 'reserve':
      return { label: 'Réserve', cssClass: 'status-reserve' };
    case 'future':
    default:
      return { label: 'À venir', cssClass: 'status-future' };
  }
}

/**
 * Compute progress bar color and clamped width for a lot
 * @param {number} percentConsumed 
 * @param {boolean} isOverBudget 
 * @returns {{ barColor: string, clampedPct: number }}
 */
export function getLotProgressBarConfig(percentConsumed = 0, isOverBudget = false) {
  let barColor = '#10b981'; // emerald
  if (isOverBudget) {
    barColor = '#f43f5e'; // rose
  } else if (percentConsumed >= 80) {
    barColor = '#f59e0b'; // amber
  }
  return {
    barColor,
    clampedPct: Math.min(100, Math.max(0, percentConsumed))
  };
}

/**
 * Format remaining variance badge for a lot
 * @param {number} remaining 
 * @param {number} allocatedBudget 
 * @returns {string} HTML string
 */
export function getLotRemainingHtml(remaining, allocatedBudget) {
  if (remaining < 0) {
    return `<span class="font-mono text-rose" style="font-weight:700;">-${formatCurrency(Math.abs(remaining))}</span>`;
  }
  if (remaining === 0 && allocatedBudget > 0) {
    return `<span class="font-mono text-dim">0,00 € (100%)</span>`;
  }
  return `<span class="font-mono text-emerald" style="font-weight:700;">+${formatCurrency(remaining)}</span>`;
}

/**
 * Generate full CSV string from travaux metrics (with UTF-8 BOM)
 * @param {Object} m 
 * @param {Date} [exportDate] 
 * @returns {string}
 */
export function generateTravauxCsvContent(m, exportDate = new Date()) {
  let csv = '\uFEFF'; // UTF-8 BOM for Microsoft Excel

  csv += 'DUO BUDGET - SUIVI FINANCIER DES TRAVAUX DE RENOVATION\n';
  csv += `Date d'export : ${exportDate.toLocaleDateString('fr-FR')} ${exportDate.toLocaleTimeString('fr-FR')}\n`;
  csv += `Budget global initial alloué : ${(m.initialBudget || 0).toFixed(2).replace('.', ',')} €\n`;
  csv += `Total des dépenses notées : ${(m.totalConsumed || 0).toFixed(2).replace('.', ',')} €\n`;
  csv += `Solde restant disponible : ${(m.remainingBudget || 0).toFixed(2).replace('.', ',')} €\n\n`;

  // Section 1: Lots Tracking
  csv += 'TABLEAU DE SUIVI BUDGETAIRE : BUDGET DE BASE VS DEPENSES REELLES PAR LOT\n';
  csv += 'Lot / Chantier;Statut;Budget de base (€);Dépensé réel (€);Avance Axel (€);Avance Ariane (€);Écart / Reste (€);Taux consommé (%)\n';

  for (const lot of (m.lots || [])) {
    const stConfig = getLotStatusConfig(lot.status);
    const nameSanitized = sanitizeCsvCell(lot.name || '');
    const stSanitized = sanitizeCsvCell(stConfig.label);
    const b = (lot.allocatedBudget || 0).toFixed(2).replace('.', ',');
    const s = (lot.totalSpent || 0).toFixed(2).replace('.', ',');
    const ax = (lot.spentAxel || 0).toFixed(2).replace('.', ',');
    const ar = (lot.spentAriane || 0).toFixed(2).replace('.', ',');
    const rem = (lot.remaining || 0).toFixed(2).replace('.', ',');
    const pct = (lot.percentConsumed || 0).toFixed(1).replace('.', ',');
    csv += `${nameSanitized};${stSanitized};${b};${s};${ax};${ar};${rem};${pct}%\n`;
  }

  const totBudget = (m.initialBudget || 0).toFixed(2).replace('.', ',');
  const totConsumed = (m.totalConsumed || 0).toFixed(2).replace('.', ',');
  const totAxel = (m.totalAxel || 0).toFixed(2).replace('.', ',');
  const totAriane = (m.totalAriane || 0).toFixed(2).replace('.', ',');
  const totRem = (m.remainingBudget || 0).toFixed(2).replace('.', ',');
  const totPct = (m.percentageConsumed || 0).toFixed(1).replace('.', ',');
  csv += `"TOTAL DU BUDGET TRAVAUX";"Global";${totBudget};${totConsumed};${totAxel};${totAriane};${totRem};${totPct}%\n\n`;

  // Section 2: Detailed items
  csv += 'DETAIL DES FACTURES ET ACOMPTES TRAVAUX NOTES\n';
  csv += 'Date;Raison / Intitulé;Lot rattaché;Avance Axel (€);Avance Ariane (€);Total (€);Statut Remboursement;Notes\n';

  for (const item of (m.items || [])) {
    const d = item.date || '';
    const r = sanitizeCsvCell(item.reason || '');
    const lot = (m.lots || []).find(l => l.id === item.lotId);
    const lotName = sanitizeCsvCell(lot ? lot.name : 'Non assigné');
    const ax = (Number(item.amountAxel) || 0).toFixed(2).replace('.', ',');
    const ar = (Number(item.amountAriane) || 0).toFixed(2).replace('.', ',');
    const tot = ((Number(item.amountAxel) || 0) + (Number(item.amountAriane) || 0)).toFixed(2).replace('.', ',');
    const st = sanitizeCsvCell(item.reimbursed ? 'Remboursé' : 'Non remboursé');
    const n = sanitizeCsvCell(item.notes || '');
    csv += `"${d}";${r};${lotName};${ax};${ar};${tot};${st};${n}\n`;
  }

  return csv;
}
