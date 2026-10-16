/**
 * DuoBudget • Couple Finance Management & Real-time Loan Equalizer
 * Generic Profile Architecture (Default: Axel & Ariane)
 * Instant 60fps Slider Updates, Start Date Tracking & LCL Structured Packages
 */

// ============================================================
// 1. STATE & STORAGE MANAGEMENT
// ============================================================

const STORAGE_KEY = 'duobudget_data_v3';

const DEFAULT_STATE = {
  profiles: [
    {
      id: 'p1',
      name: 'Axel',
      initials: 'AX',
      role: 'Co-emprunteur',
      color: '#0ea5e9',
      income: 2800.00
    },
    {
      id: 'p2',
      name: 'Ariane',
      initials: 'AR',
      role: 'Co-emprunteuse',
      color: '#8b5cf6',
      income: 2600.00
    }
  ],
  splitRule: '50', // '50' for 50/50, or 'income' for prorata
  simulator: {
    borrowerId: 'p1',     // Axel
    lenderId: 'p2',       // Ariane
    title: 'Apport familial maison (Père d\'Ariane)',
    totalGift: 25000,     // Total apport du père d'Ariane
    userShare: 50,        // Part remboursée (50% = 12 500 €)
    durationYears: 15,    // 15 ans aligné sur le prêt bancaire
    interestRate: 0.0,    // 0% prêt familial
    method: 'offset',     // 'offset' (compte commun) ou 'direct' (virement direct)
    startMonth: '2026-03', // Mois de départ (Mars 2026 = 8 mois écoulés par rapport à Octobre 2026)
    monthsPaid: 8         // Nombre de mensualités déjà payées
  },
  lclItems: [
    {
      id: 'lcl-1',
      label: 'Mensualité Prêt Immobilier LCL',
      amount: 1150.00,
      type: 'monthly',
      category: 'credit',
      notes: 'Prélèvement principal amortissable 15 ans'
    },
    {
      id: 'lcl-2',
      label: 'Assurance Emprunteur (Prêt Immo)',
      amount: 38.00,
      type: 'monthly',
      category: 'credit',
      notes: 'Assurance décès / invalidité co-emprunteurs'
    },
    {
      id: 'lcl-3',
      label: 'Assurance Habitation Maison',
      amount: 34.00,
      type: 'monthly',
      category: 'credit',
      notes: 'Formule multirisque habitation complète'
    },
    {
      id: 'lcl-4',
      label: 'Taxe Foncière (Mensualisée)',
      amount: 110.00,
      type: 'annualized',
      category: 'taxe',
      notes: 'Total annuel 1 320 € lissé sur 12 mois'
    },
    {
      id: 'lcl-5',
      label: 'Provision Entretien Maison & Travaux',
      amount: 50.00,
      type: 'monthly',
      category: 'maintenance',
      notes: 'Réserve pour réparations ou entretiens périodiques'
    }
  ],
  revolutEnvelopes: [
    {
      id: 'rev-cat-1',
      name: 'Courses & Alimentation',
      budget: 550.00,
      icon: '🛒',
      color: '#10b981'
    },
    {
      id: 'rev-cat-2',
      name: 'Charges & Fluides (Énergie, Eau)',
      budget: 140.00,
      icon: '⚡',
      color: '#0ea5e9'
    },
    {
      id: 'rev-cat-3',
      name: 'Sorties & Restos',
      budget: 250.00,
      icon: '🥂',
      color: '#f59e0b'
    },
    {
      id: 'rev-cat-4',
      name: 'Internet & Abonnements Foyer',
      budget: 45.00,
      icon: '📶',
      color: '#8b5cf6'
    },
    {
      id: 'rev-cat-5',
      name: 'Maison, Déco & Jardin',
      budget: 80.00,
      icon: '🪴',
      color: '#ec4899'
    },
    {
      id: 'rev-cat-6',
      name: 'Animaux & Divers Foyer',
      budget: 90.00,
      icon: '🐾',
      color: '#64748b'
    }
  ],
  expenses: [
    {
      id: 'exp-1',
      date: '2026-10-15',
      desc: 'Courses Supermarché Carrefour',
      amount: 87.40,
      account: 'Revolut',
      category: 'Courses & Alimentation',
      payer: 'Carte Revolut Commune'
    },
    {
      id: 'exp-2',
      date: '2026-10-13',
      desc: 'Dîner Restaurant Italien',
      amount: 64.00,
      account: 'Revolut',
      category: 'Sorties & Restos',
      payer: 'Carte Revolut Commune'
    },
    {
      id: 'exp-3',
      date: '2026-10-11',
      desc: 'Facture EDF Électricité',
      amount: 128.50,
      account: 'Revolut',
      category: 'Charges & Fluides',
      payer: 'Carte Revolut Commune'
    },
    {
      id: 'exp-4',
      date: '2026-10-09',
      desc: 'Magasin Bricolage & Ampoules',
      amount: 42.90,
      account: 'Revolut',
      category: 'Maison, Déco & Jardin',
      payer: 'Axel'
    },
    {
      id: 'exp-5',
      date: '2026-10-05',
      desc: 'Box Fibre Internet Orange',
      amount: 39.99,
      account: 'Revolut',
      category: 'Internet & Abonnements Foyer',
      payer: 'Carte Revolut Commune'
    },
    {
      id: 'exp-6',
      date: '2026-10-05',
      desc: 'Prélèvement Mensualité Prêt Immo',
      amount: 1150.00,
      account: 'LCL',
      category: 'Prêt & Patrimoine',
      payer: 'Compte LCL'
    },
    {
      id: 'exp-7',
      date: '2026-10-05',
      desc: 'Assurance Emprunteur & Logement',
      amount: 72.00,
      account: 'LCL',
      category: 'Prêt & Patrimoine',
      payer: 'Compte LCL'
    }
  ]
};

let appState = loadState();

function loadState() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (raw) {
      const parsed = JSON.parse(raw);
      if (parsed.profiles && parsed.simulator) {
        return { ...DEFAULT_STATE, ...parsed };
      }
    }
  } catch (e) {
    console.warn('Storage fallback to default:', e);
  }
  return JSON.parse(JSON.stringify(DEFAULT_STATE));
}

let saveTimeout = null;
function saveStateDebounced() {
  if (saveTimeout) clearTimeout(saveTimeout);
  saveTimeout = setTimeout(() => {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(appState));
    } catch (e) {
      console.error('Save failed:', e);
    }
  }, 300);
}

function saveStateImmediate() {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(appState));
  } catch (e) {
    console.error('Save failed:', e);
  }
}

// Helpers
const fmtEUR = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' });
function formatCurrency(val) {
  return fmtEUR.format(Number(val) || 0);
}
function formatDateFR(dateStr) {
  if (!dateStr) return '';
  const parts = dateStr.split('-');
  if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
  return dateStr;
}

function getBorrowerProfile() {
  return appState.profiles.find(p => p.id === appState.simulator.borrowerId) || appState.profiles[0];
}
function getLenderProfile() {
  return appState.profiles.find(p => p.id === appState.simulator.lenderId) || appState.profiles[1];
}

// Date calculation helpers
function computeMonthsElapsedFromStart(startMonthStr) {
  if (!startMonthStr) return 0;
  const [sY, sM] = startMonthStr.split('-').map(Number);
  const now = new Date();
  const cY = now.getFullYear();
  const cM = now.getMonth() + 1;
  const diff = (cY - sY) * 12 + (cM - sM);
  return Math.max(0, diff);
}

function computeStartMonthFromMonthsElapsed(monthsElapsed) {
  const now = new Date();
  const targetDate = new Date(now.getFullYear(), now.getMonth() - monthsElapsed, 1);
  const y = targetDate.getFullYear();
  const m = String(targetDate.getMonth() + 1).padStart(2, '0');
  return `${y}-${m}`;
}

function computeEndDate(startMonthStr, totalMonths) {
  if (!startMonthStr || !totalMonths) return '';
  const [sY, sM] = startMonthStr.split('-').map(Number);
  const endTotalMonths = (sY * 12 + (sM - 1)) + totalMonths;
  const endYear = Math.floor(endTotalMonths / 12);
  const endMonth = (endTotalMonths % 12) + 1;
  const monthNames = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
  return `${monthNames[endMonth - 1]} ${endYear}`;
}

function computeRemainingTimeText(remainingMonths) {
  if (remainingMonths <= 0) return '🎉 Remboursement entièrement terminé !';
  const years = Math.floor(remainingMonths / 12);
  const months = remainingMonths % 12;
  let text = '';
  if (years > 0) text += `${years} an${years > 1 ? 's' : ''}`;
  if (months > 0) text += `${years > 0 ? ' et ' : ''}${months} mois`;
  return `⏱️ ${text} restant${remainingMonths > 1 ? 's' : ''} (${remainingMonths} mensualité${remainingMonths > 1 ? 's' : ''})`;
}

// ============================================================
// 2. MATHEMATICAL CALCULATION ENGINES
// ============================================================

function calculateLoanMetrics() {
  const { totalGift, userShare, durationYears, interestRate, method, monthsPaid, startMonth } = appState.simulator;
  
  const borrowedAmount = totalGift * (userShare / 100);
  const totalMonths = durationYears * 12;
  const annualRate = interestRate / 100;
  
  let monthlyPayment = 0;
  let totalCost = borrowedAmount;
  let totalInterest = 0;

  if (annualRate > 0 && totalMonths > 0) {
    const monthlyRate = annualRate / 12;
    monthlyPayment = borrowedAmount * (monthlyRate / (1 - Math.pow(1 + monthlyRate, -totalMonths)));
    totalCost = monthlyPayment * totalMonths;
    totalInterest = totalCost - borrowedAmount;
  } else {
    monthlyPayment = totalMonths > 0 ? (borrowedAmount / totalMonths) : 0;
  }

  const safePaidMonths = Math.min(monthsPaid || 0, totalMonths);
  const remainingMonths = Math.max(0, totalMonths - safePaidMonths);
  const capitalPaidSoFar = monthlyPayment * safePaidMonths;
  const remainingCapital = Math.max(0, totalCost - capitalPaidSoFar);
  const endDateText = computeEndDate(startMonth, totalMonths);
  const remainingTimeText = computeRemainingTimeText(remainingMonths);

  return {
    borrowedAmount,
    totalMonths,
    durationYears,
    monthlyPayment,
    totalCost,
    totalInterest,
    startMonth,
    monthsPaid: safePaidMonths,
    remainingMonths,
    capitalPaidSoFar,
    remainingCapital,
    endDateText,
    remainingTimeText,
    method
  };
}

function calculateBudgets() {
  // LCL structured calculation
  const creditItems = appState.lclItems.filter(i => (i.category === 'credit' || !i.category));
  const taxeItem = appState.lclItems.find(i => i.category === 'taxe') || { amount: 110.00 };
  const maintItems = appState.lclItems.filter(i => i.category === 'maintenance');

  const creditSubtotal = creditItems.reduce((acc, item) => acc + (Number(item.amount) || 0), 0);
  const taxeSubtotal = Number(taxeItem.amount) || 0;
  const maintSubtotal = maintItems.reduce((acc, item) => acc + (Number(item.amount) || 0), 0);

  const lclTotal = creditSubtotal + taxeSubtotal + maintSubtotal;
  const lclAnnual = lclTotal * 12;

  // Revolut
  const revolutBudgetTotal = appState.revolutEnvelopes.reduce((acc, cat) => acc + (Number(cat.budget) || 0), 0);
  const revolutAnnual = revolutBudgetTotal * 12;

  const currentMonthPrefix = new Date().toISOString().slice(0, 7);
  const revolutSpentThisMonth = appState.expenses
    .filter(e => e.account === 'Revolut' && e.date.startsWith(currentMonthPrefix))
    .reduce((acc, e) => acc + (Number(e.amount) || 0), 0);

  const globalMonthlyBudget = lclTotal + revolutBudgetTotal;
  const globalAnnualBudget = globalMonthlyBudget * 12;

  const loan = calculateLoanMetrics();

  // Split calculations
  const p1 = appState.profiles[0];
  const p2 = appState.profiles[1];

  let p1Ratio = 0.50;
  let p2Ratio = 0.50;

  if (appState.splitRule === 'income') {
    const totIncome = (p1.income || 0) + (p2.income || 0);
    if (totIncome > 0) {
      p1Ratio = (p1.income || 0) / totIncome;
      p2Ratio = (p2.income || 0) / totIncome;
    }
  }

  const p1LclBase = lclTotal * p1Ratio;
  const p2LclBase = lclTotal * p2Ratio;
  const p1RevBase = revolutBudgetTotal * p1Ratio;
  const p2RevBase = revolutBudgetTotal * p2Ratio;

  const isP1Borrower = (appState.simulator.borrowerId === p1.id);

  let p1Lcl = p1LclBase;
  let p2Lcl = p2LclBase;
  let p1Rev = p1RevBase;
  let p2Rev = p2RevBase;
  let p1Total = 0;
  let p2Total = 0;
  let diffAmount = 0;

  if (loan.method === 'offset') {
    if (isP1Borrower) {
      p1Lcl = p1LclBase + loan.monthlyPayment;
      p2Lcl = p2LclBase - loan.monthlyPayment;
    } else {
      p1Lcl = p1LclBase - loan.monthlyPayment;
      p2Lcl = p2LclBase + loan.monthlyPayment;
    }
    p1Total = p1Lcl + p1Rev;
    p2Total = p2Lcl + p2Rev;
    diffAmount = Math.abs(p1Total - p2Total);
  } else {
    p1Total = p1LclBase + p1RevBase + (isP1Borrower ? loan.monthlyPayment : 0);
    p2Total = p2LclBase + p2RevBase + (!isP1Borrower ? loan.monthlyPayment : 0);
    diffAmount = loan.monthlyPayment;
  }

  return {
    lclTotal,
    lclAnnual,
    creditSubtotal,
    taxeSubtotal,
    maintSubtotal,
    revolutBudgetTotal,
    revolutAnnual,
    revolutSpentThisMonth,
    globalMonthlyBudget,
    globalAnnualBudget,
    loan,
    p1Ratio,
    p2Ratio,
    split: {
      p1,
      p2,
      isP1Borrower,
      p1Total,
      p2Total,
      p1Lcl,
      p2Lcl,
      p1Rev,
      p2Rev,
      diffAmount
    }
  };
}

// ============================================================
// 3. FAST REAL-TIME SIMULATOR UPDATES (ON SLIDER / DATE INPUT)
// ============================================================

function updateSimulatorRealtime(activeInputId = null) {
  const data = calculateBudgets();
  const { loan, split } = data;
  const borrower = getBorrowerProfile();
  const lender = getLenderProfile();

  // 1. Synchronize inputs without resetting the actively dragged element
  const sliderAmount = document.getElementById('slider-loan-amount');
  const numAmount = document.getElementById('num-loan-amount');
  const dashSlider = document.getElementById('dash-slider-amount');
  const sliderShare = document.getElementById('slider-user-share');
  const numShare = document.getElementById('num-user-share');
  const sliderDuration = document.getElementById('slider-loan-duration');
  const numInterest = document.getElementById('num-loan-interest');
  const sliderInterest = document.getElementById('slider-loan-interest');

  const startMonthInput = document.getElementById('input-loan-start-month');
  const numMonthsPaidInput = document.getElementById('num-months-paid');

  if (activeInputId !== 'num-loan-amount') numAmount.value = appState.simulator.totalGift;
  if (activeInputId !== 'slider-loan-amount') sliderAmount.value = appState.simulator.totalGift;
  if (dashSlider && activeInputId !== 'dash-slider-amount') dashSlider.value = appState.simulator.totalGift;

  if (activeInputId !== 'num-user-share') numShare.value = appState.simulator.userShare;
  if (activeInputId !== 'slider-user-share') sliderShare.value = appState.simulator.userShare;

  if (activeInputId !== 'slider-loan-duration') sliderDuration.value = appState.simulator.durationYears;
  document.getElementById('display-duration-years').textContent = appState.simulator.durationYears;
  document.getElementById('display-duration-months').textContent = loan.totalMonths;

  if (activeInputId !== 'num-loan-interest') numInterest.value = appState.simulator.interestRate;
  if (activeInputId !== 'slider-loan-interest') sliderInterest.value = appState.simulator.interestRate;

  // Date and months paid synchronization
  if (activeInputId !== 'input-loan-start-month' && startMonthInput) {
    startMonthInput.value = appState.simulator.startMonth;
  }
  if (activeInputId !== 'num-months-paid' && numMonthsPaidInput) {
    numMonthsPaidInput.value = loan.monthsPaid;
  }
  const labelElapsed = document.getElementById('label-elapsed-months');
  if (labelElapsed) {
    labelElapsed.textContent = `${loan.monthsPaid} mois`;
  }

  // 2. Metrics & Pills
  const fmtPayment = formatCurrency(loan.monthlyPayment);
  document.getElementById('sim-monthly-payment').textContent = fmtPayment;
  document.getElementById('top-repayment-monthly').textContent = `+${fmtPayment}`;
  document.getElementById('top-repayment-tag').textContent = `${loan.durationYears} ans`;
  document.getElementById('top-repayment-subtext').textContent = `${borrower.name} remb. ${lender.name} (Mois ${loan.monthsPaid}/${loan.totalMonths})`;

  const dashQuickVal = document.getElementById('dash-quick-loan-val');
  if (dashQuickVal) {
    dashQuickVal.textContent = `${formatCurrency(appState.simulator.totalGift)} • ${loan.durationYears} ans = ${fmtPayment}/m`;
  }

  // 3. Detailed Results Grid
  document.getElementById('sim-clarification-text').innerHTML = `
    Montant exact de la mensualité due par <strong>${borrower.name}</strong> à <strong>${lender.name}</strong> pendant <strong>${loan.durationYears} ans</strong> (${loan.totalMonths} mois).
  `;
  document.getElementById('sim-total-borrowed').textContent = formatCurrency(loan.borrowedAmount);
  document.getElementById('sim-borrowed-sub').textContent = `(${appState.simulator.userShare}% de ${formatCurrency(appState.simulator.totalGift)})`;

  document.getElementById('sim-total-months').textContent = `${loan.totalMonths} mois`;
  document.getElementById('sim-years-sub').textContent = `(${loan.durationYears} ans)`;

  document.getElementById('sim-total-interests').textContent = formatCurrency(loan.totalInterest);
  document.getElementById('sim-interest-sub').textContent = `(Taux à ${appState.simulator.interestRate.toFixed(2)}%)`;

  document.getElementById('sim-grand-total').textContent = formatCurrency(loan.totalCost);

  // History Pills in Simulator
  const paidSumPill = document.getElementById('sim-paid-sum-pill');
  if (paidSumPill) paidSumPill.textContent = formatCurrency(loan.capitalPaidSoFar);

  const remSumPill = document.getElementById('sim-remaining-sum-pill');
  if (remSumPill) remSumPill.textContent = formatCurrency(loan.remainingCapital);

  const endDatePill = document.getElementById('sim-end-date-pill');
  if (endDatePill) endDatePill.textContent = loan.endDateText;

  // 4. Mathematical Explanations
  const explainerBox = document.getElementById('offset-explainer-content');
  if (loan.method === 'offset') {
    explainerBox.innerHTML = `
      Pour éviter tout virement bancaire privé tous les mois entre vous, vous équilibrez directement via le compte commun LCL :<br>
      • <strong>${borrower.name}</strong> : verse <strong>+${fmtPayment}</strong> en plus sur le compte commun.<br>
      • <strong>${lender.name}</strong> : déduit <strong>-${fmtPayment}</strong> de sa part sur le compte commun.<br>
      • <strong>Écart visible sur le compte joint :</strong> ${borrower.name} verse <strong>${formatCurrency(loan.monthlyPayment * 2)} de plus que ${lender.name}</strong> au total chaque mois (le double de la mensualité).
    `;
  } else {
    explainerBox.innerHTML = `
      Vous partagez équitablement le montant exigé sur le compte commun LCL et Revolut.<br>
      En parallèle, <strong>${borrower.name}</strong> effectue un virement mensuel permanent de <strong>${fmtPayment} / mois</strong> depuis son compte bancaire personnel vers le compte bancaire personnel de <strong>${lender.name}</strong>.
    `;
  }

  // 5. Dashboard couple cards live update
  updateDashboardCoupleCards(data);

  // 6. LCL split live update
  updateLclSplitDisplay(data);

  // 7. Amortization and Progress
  document.getElementById('track-total-months').textContent = loan.totalMonths;
  document.getElementById('sim-counter-badge').innerHTML = `Mois ${loan.monthsPaid} / <span id="track-total-months">${loan.totalMonths}</span>`;

  const progressPct = loan.totalCost > 0 ? (loan.capitalPaidSoFar / loan.totalCost) * 100 : 0;
  document.getElementById('sim-progress-fill').style.width = `${Math.max(1, progressPct)}%`;
  document.getElementById('sim-paid-so-far').textContent = formatCurrency(loan.capitalPaidSoFar);
  document.getElementById('sim-remaining-capital').textContent = formatCurrency(loan.remainingCapital);

  const timeRemLabel = document.getElementById('sim-time-remaining-label');
  if (timeRemLabel) timeRemLabel.textContent = loan.remainingTimeText;

  renderAmortizationTable(loan);

  // 8. Update active presets highlights
  updatePresetButtonStyles();

  saveStateDebounced();
}

function updatePresetButtonStyles() {
  const currentAmt = appState.simulator.totalGift;
  const currentShare = appState.simulator.userShare;
  const currentDur = appState.simulator.durationYears;

  document.querySelectorAll('.preset-btn').forEach(btn => {
    const target = btn.dataset.target || (btn.dataset.amount ? 'amount' : null);
    const val = Number(btn.dataset.val || btn.dataset.amount);

    let isActive = false;
    if (target === 'amount' && val === currentAmt) isActive = true;
    if (target === 'share' && val === currentShare) isActive = true;
    if (target === 'duration' && val === currentDur) isActive = true;

    if (isActive) btn.classList.add('active');
    else if (target) btn.classList.remove('active');
  });
}

function updateDashboardCoupleCards(data) {
  const { split, loan } = data;
  const p1 = split.p1;
  const p2 = split.p2;
  const borrower = getBorrowerProfile();
  const lender = getLenderProfile();

  document.getElementById('name-p1-label').textContent = `Contribution de ${p1.name}`;
  document.getElementById('contrib-p1-total').textContent = formatCurrency(split.p1Total);
  document.getElementById('contrib-p1-lcl').textContent = formatCurrency(split.p1Lcl);
  document.getElementById('contrib-p1-rev').textContent = formatCurrency(split.p1Rev);

  document.getElementById('name-p2-label').textContent = `Contribution de ${p2.name}`;
  document.getElementById('contrib-p2-total').textContent = formatCurrency(split.p2Total);
  document.getElementById('contrib-p2-lcl').textContent = formatCurrency(split.p2Lcl);
  document.getElementById('contrib-p2-rev').textContent = formatCurrency(split.p2Rev);

  const fmtLoan = formatCurrency(loan.monthlyPayment);
  if (split.isP1Borrower) {
    document.getElementById('contrib-p1-breakdown').textContent = `dont +${fmtLoan} prêt (${loan.monthsPaid}/${loan.totalMonths} m.)`;
    document.getElementById('contrib-p1-loan').textContent = `+${fmtLoan}`;
    document.getElementById('contrib-p2-breakdown').textContent = loan.method === 'offset' ? `compensation prêt déduite (-${fmtLoan})` : `reçoit ${fmtLoan} en direct`;
    document.getElementById('contrib-p2-loan').textContent = `-${fmtLoan}`;
  } else {
    document.getElementById('contrib-p2-breakdown').textContent = `dont +${fmtLoan} prêt (${loan.monthsPaid}/${loan.totalMonths} m.)`;
    document.getElementById('contrib-p2-loan').textContent = `+${fmtLoan}`;
    document.getElementById('contrib-p1-breakdown').textContent = loan.method === 'offset' ? `compensation prêt déduite (-${fmtLoan})` : `reçoit ${fmtLoan} en direct`;
    document.getElementById('contrib-p1-loan').textContent = `-${fmtLoan}`;
  }

  // Bar ratios
  const sumBoth = split.p1Total + split.p2Total;
  const p1Pct = sumBoth > 0 ? (split.p1Total / sumBoth) * 100 : 50;
  const p2Pct = 100 - p1Pct;
  document.getElementById('bar-p1').style.width = `${p1Pct.toFixed(1)}%`;
  document.getElementById('bar-p2').style.width = `${p2Pct.toFixed(1)}%`;

  // Notice text
  const noticeEl = document.getElementById('equalizer-notice-text');
  if (loan.method === 'offset') {
    noticeEl.innerHTML = `
      <strong>Équilibrage automatique sur les comptes :</strong> 
      ${p1.name} verse <strong>${formatCurrency(split.p1Total)}</strong> et ${p2.name} verse <strong>${formatCurrency(split.p2Total)}</strong>.<br>
      ${borrower.name} verse <strong>${formatCurrency(split.diffAmount)} de plus que ${lender.name}</strong> sur le compte commun LCL pour compenser exactement la mensualité de <strong>${fmtLoan}</strong>.<br>
      <small style="color:#94a3b8;">📅 Progression : <strong>${loan.monthsPaid}</strong> mensualités déjà réglées (${formatCurrency(loan.capitalPaidSoFar)}), reste <strong>${formatCurrency(loan.remainingCapital)}</strong>.</small>
    `;
  } else {
    noticeEl.innerHTML = `
      <strong>Équilibrage par virement direct :</strong> 
      Vous alimentez tous les deux les comptes communs selon la clé (${appState.splitRule === '50' ? '50/50' : 'prorata'}), et ${borrower.name} verse séparément <strong>${fmtLoan}</strong> sur le compte personnel de ${lender.name}.
    `;
  }
}

function updateLclSplitDisplay(data) {
  const { split, loan, lclTotal } = data;
  const p1 = split.p1;
  const p2 = split.p2;

  document.getElementById('lcl-p1-name').textContent = `Part mensuelle de ${p1.name}`;
  document.getElementById('lcl-p1-amount').textContent = formatCurrency(split.p1Lcl);

  document.getElementById('lcl-p2-name').textContent = `Part mensuelle de ${p2.name}`;
  document.getElementById('lcl-p2-amount').textContent = formatCurrency(split.p2Lcl);

  const fmtLoan = formatCurrency(loan.monthlyPayment);
  if (loan.method === 'offset') {
    if (split.isP1Borrower) {
      document.getElementById('lcl-p1-sub').textContent = `Base (${formatCurrency(lclTotal * data.p1Ratio)}) + Prêt (+${fmtLoan})`;
      document.getElementById('lcl-p2-sub').textContent = `Base (${formatCurrency(lclTotal * data.p2Ratio)}) - Prêt (-${fmtLoan})`;
    } else {
      document.getElementById('lcl-p1-sub').textContent = `Base (${formatCurrency(lclTotal * data.p1Ratio)}) - Prêt (-${fmtLoan})`;
      document.getElementById('lcl-p2-sub').textContent = `Base (${formatCurrency(lclTotal * data.p2Ratio)}) + Prêt (+${fmtLoan})`;
    }
  } else {
    document.getElementById('lcl-p1-sub').textContent = `Quote-part exacte (${(data.p1Ratio * 100).toFixed(0)}%)`;
    document.getElementById('lcl-p2-sub').textContent = `Quote-part exacte (${(data.p2Ratio * 100).toFixed(0)}%)`;
  }
}

function renderAmortizationTable(loan) {
  const tbody = document.getElementById('sim-amort-tbody');
  tbody.innerHTML = '';

  const years = appState.simulator.durationYears;
  const annualPayment = loan.monthlyPayment * 12;
  const startY = appState.simulator.startMonth ? parseInt(appState.simulator.startMonth.split('-')[0]) : 2026;

  for (let y = 1; y <= years; y++) {
    const calendarYear = startY + (y - 1);
    const monthsPaidInYear = Math.max(0, Math.min(12, loan.monthsPaid - ((y - 1) * 12)));
    const monthsCumulativeAtYearEnd = Math.min(loan.totalMonths, y * 12);
    const paidAtYearEnd = loan.monthlyPayment * monthsCumulativeAtYearEnd;
    const remainingAtYearEnd = Math.max(0, loan.totalCost - paidAtYearEnd);

    let statusHtml = '';
    if (loan.monthsPaid >= y * 12) {
      statusHtml = `<span class="status-paid">✓ Déjà payé</span>`;
    } else if (loan.monthsPaid > (y - 1) * 12) {
      statusHtml = `<span class="status-current">En cours (${monthsPaidInYear}/12 m)</span>`;
    } else {
      statusHtml = `<span class="status-future">À venir</span>`;
    }

    // Show first 4 years, middle, and final years to maintain clean table
    if (y <= 4 || y === Math.round(years / 2) || y === years) {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><strong>Année ${y} <span style="font-size:0.75rem; color:var(--text-dim);">(${calendarYear})</span></strong></td>
        <td>${formatCurrency(loan.monthlyPayment)}/m</td>
        <td>${formatCurrency(paidAtYearEnd)}</td>
        <td class="font-mono text-amber">${formatCurrency(remainingAtYearEnd)}</td>
        <td class="text-center">${statusHtml}</td>
      `;
      tbody.appendChild(tr);
    } else if (y === 5 && years > 6) {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td colspan="5" class="text-center text-dim" style="padding: 4px;">•••</td>`;
      tbody.appendChild(tr);
    }
  }
}

// ============================================================
// 4. FULL RENDERER (PAGES & TABS)
// ============================================================

function renderAll() {
  const data = calculateBudgets();

  renderHeaderProfiles();
  renderTopSummary(data);
  renderDashboard(data);
  renderSimulatorView(data);
  renderLclTab(data);
  renderRevolutTab(data);
  renderProfilesTab();
}

function renderHeaderProfiles() {
  const strip = document.getElementById('header-profiles-display');
  const p1 = appState.profiles[0];
  const p2 = appState.profiles[1];

  strip.innerHTML = `
    <span class="header-profile-tag">
      <span class="header-profile-dot" style="background:${p1.color};"></span>
      <strong>${p1.name}</strong>
    </span>
    <span style="color:var(--text-dim); font-size:0.75rem;">&</span>
    <span class="header-profile-tag">
      <span class="header-profile-dot" style="background:${p2.color};"></span>
      <strong>${p2.name}</strong>
    </span>
  `;

  document.getElementById('initials-p1').textContent = p1.initials || p1.name.slice(0, 2).toUpperCase();
  document.getElementById('initials-p2').textContent = p2.initials || p2.name.slice(0, 2).toUpperCase();
  document.getElementById('lcl-avatar-p1').textContent = p1.initials || p1.name.slice(0, 2).toUpperCase();
  document.getElementById('lcl-avatar-p2').textContent = p2.initials || p2.name.slice(0, 2).toUpperCase();
}

function renderTopSummary(data) {
  document.getElementById('top-total-budget').textContent = formatCurrency(data.globalMonthlyBudget);
  document.getElementById('top-total-annual').textContent = `${formatCurrency(data.globalAnnualBudget)} / an`;

  document.getElementById('top-lcl-budget').textContent = formatCurrency(data.lclTotal);
  document.getElementById('top-revolut-budget').textContent = formatCurrency(data.revolutBudgetTotal);
  document.getElementById('top-revolut-spent').textContent = `${formatCurrency(data.revolutSpentThisMonth)} dépensés ce mois`;

  const borrower = getBorrowerProfile();
  const lender = getLenderProfile();
  document.getElementById('top-repayment-monthly').textContent = `+${formatCurrency(data.loan.monthlyPayment)}`;
  document.getElementById('top-repayment-subtext').textContent = `${borrower.name} remb. ${lender.name} (${data.loan.monthsPaid}/${data.loan.totalMonths} m.)`;
}

function renderDashboard(data) {
  const { lclTotal, revolutBudgetTotal, globalMonthlyBudget, lclAnnual, revolutAnnual, globalAnnualBudget } = data;

  updateDashboardCoupleCards(data);

  document.getElementById('forecast-lcl-annual').textContent = formatCurrency(lclAnnual);
  document.getElementById('forecast-revolut-annual').textContent = formatCurrency(revolutAnnual);
  document.getElementById('forecast-total-annual').textContent = formatCurrency(globalAnnualBudget);

  renderDonutChart(lclTotal, revolutBudgetTotal, globalMonthlyBudget);
  renderRecentExpensesTable();
}

function renderDonutChart(lcl, rev, total) {
  const svg = document.getElementById('global-donut-svg');
  const legend = document.getElementById('global-donut-legend');
  const centerTotal = document.getElementById('donut-center-total');

  centerTotal.textContent = `${Math.round(total)} €`;

  if (total <= 0) {
    svg.innerHTML = '';
    legend.innerHTML = '<span class="text-dim">Aucune donnée</span>';
    return;
  }

  const lclPct = (lcl / total) * 100;
  const revPct = (rev / total) * 100;
  const r = 15.9155;
  const lclDash = `${lclPct} ${100 - lclPct}`;
  const revDash = `${revPct} ${100 - revPct}`;
  const revOffset = -lclPct;

  svg.innerHTML = `
    <circle cx="21" cy="21" r="${r}" fill="transparent" stroke="rgba(255,255,255,0.06)" stroke-width="5" transform="translate(29, 29)"></circle>
    <circle cx="21" cy="21" r="${r}" fill="transparent" stroke="#0ea5e9" stroke-width="5" stroke-dasharray="${lclDash}" stroke-dashoffset="0" transform="translate(29, 29)"></circle>
    <circle cx="21" cy="21" r="${r}" fill="transparent" stroke="#8b5cf6" stroke-width="5" stroke-dasharray="${revDash}" stroke-dashoffset="${revOffset}" transform="translate(29, 29)"></circle>
  `;

  legend.innerHTML = `
    <div class="legend-item">
      <div class="legend-label-wrap">
        <span class="legend-color-dot" style="background: #0ea5e9;"></span>
        <span>LCL Package Immo Statique (${lclPct.toFixed(0)}%)</span>
      </div>
      <span class="legend-val text-cyan">${formatCurrency(lcl)}</span>
    </div>
    <div class="legend-item">
      <div class="legend-label-wrap">
        <span class="legend-color-dot" style="background: #8b5cf6;"></span>
        <span>Revolut Vie Commune (${revPct.toFixed(0)}%)</span>
      </div>
      <span class="legend-val text-violet">${formatCurrency(rev)}</span>
    </div>
  `;
}

function renderSimulatorView(data) {
  const borrower = getBorrowerProfile();
  const lender = getLenderProfile();

  const bSelect = document.getElementById('select-loan-borrower');
  const lSelect = document.getElementById('select-loan-lender');

  bSelect.innerHTML = appState.profiles.map(p => 
    `<option value="${p.id}" ${p.id === appState.simulator.borrowerId ? 'selected' : ''}>${p.name}</option>`
  ).join('');

  lSelect.innerHTML = appState.profiles.map(p => 
    `<option value="${p.id}" ${p.id === appState.simulator.lenderId ? 'selected' : ''}>${p.name} (via don de son père)</option>`
  ).join('');

  document.getElementById('sim-hero-description').innerHTML = `
    Le père de <strong>${lender.name}</strong> lui a donné <strong>${formatCurrency(appState.simulator.totalGift)}</strong> en apport. <strong>${borrower.name}</strong> s'est engagé à lui rembourser la moitié (50% = ${formatCurrency(appState.simulator.totalGift / 2)}) sur <strong>${appState.simulator.durationYears} ans</strong> (${appState.simulator.durationYears * 12} mois, même durée que le prêt bancaire). Définis ton <strong>mois de départ</strong> pour suivre précisément ce qui a déjà été versé et ce qu'il reste à rembourser.
  `;

  document.getElementById('method-direct').checked = (appState.simulator.method === 'direct');
  document.getElementById('method-offset').checked = (appState.simulator.method === 'offset');

  const fmtPayment = formatCurrency(data.loan.monthlyPayment);
  document.getElementById('method-desc-offset').innerHTML = `
    Zéro virement privé ! ${borrower.name} verse <strong>+${fmtPayment}</strong> de plus au compte joint LCL et ${lender.name} verse <strong>-${fmtPayment}</strong> de moins.
  `;
  document.getElementById('method-desc-direct').innerHTML = `
    Vous alimentez selon la clé convenue le compte joint, et ${borrower.name} effectue un virement mensuel séparé de <strong>${fmtPayment}</strong> sur le compte perso de ${lender.name}.
  `;

  updateSimulatorRealtime();
}

/**
 * Renders LCL structured packages:
 * - Package 1: Crédit Immobilier & Assurances (Prêt + Assurances)
 * - Package 2: Fiscalité Logement (Taxe Foncière)
 * - Package 3: Prévoyance & Entretien
 */
function renderLclTab(data) {
  const { lclTotal, lclAnnual, creditSubtotal, taxeSubtotal, maintSubtotal } = data;

  document.getElementById('lcl-banner-total').textContent = formatCurrency(lclTotal);
  document.getElementById('lcl-banner-annual').textContent = `${formatCurrency(lclAnnual)} / an`;

  // Subtotal badges
  document.getElementById('subtotal-credit-pack').textContent = formatCurrency(creditSubtotal);
  document.getElementById('subtotal-taxe-pack').textContent = formatCurrency(taxeSubtotal);
  document.getElementById('subtotal-maintenance-pack').textContent = formatCurrency(maintSubtotal);

  // Sync Taxe Foncière inputs
  const tfAnnualInput = document.getElementById('input-tf-annual');
  const tfMonthlyInput = document.getElementById('input-tf-monthly');
  if (tfAnnualInput) tfAnnualInput.value = Math.round(taxeSubtotal * 12);
  if (tfMonthlyInput) tfMonthlyInput.value = Math.round(taxeSubtotal);

  // Render Credit items
  const creditContainer = document.getElementById('lcl-credit-items');
  creditContainer.innerHTML = '';
  const creditItems = appState.lclItems.filter(i => (i.category === 'credit' || !i.category));

  creditItems.forEach(item => {
    creditContainer.appendChild(createLclItemRow(item));
  });

  // Render Maintenance items
  const maintContainer = document.getElementById('lcl-maintenance-items');
  maintContainer.innerHTML = '';
  const maintItems = appState.lclItems.filter(i => i.category === 'maintenance');

  maintItems.forEach(item => {
    maintContainer.appendChild(createLclItemRow(item));
  });

  // Highlight block
  document.getElementById('ps-credit-val').textContent = formatCurrency(creditSubtotal);
  document.getElementById('ps-taxe-val').textContent = formatCurrency(taxeSubtotal);
  document.getElementById('ps-maint-val').textContent = formatCurrency(maintSubtotal);
  document.getElementById('ps-total-val').textContent = formatCurrency(lclTotal);
  document.getElementById('lcl-pack-count-tag').textContent = `${appState.lclItems.length} postes fixes`;

  updateLclSplitDisplay(data);
  renderLclStackedBar(lclTotal);
}

function createLclItemRow(item) {
  const annualVal = item.amount * 12;
  const row = document.createElement('div');
  row.className = 'lcl-item-row';
  row.innerHTML = `
    <div class="lcl-item-left">
      <div class="lcl-item-icon">
        ${getItemIcon(item.label)}
      </div>
      <div class="lcl-item-info">
        <h4>${item.label}</h4>
        <p>${item.notes || ''}</p>
      </div>
    </div>
    <div class="lcl-item-right">
      <div>
        <div class="lcl-item-price">${formatCurrency(item.amount)} / mois</div>
        <span class="lcl-item-annual">${formatCurrency(annualVal)} / an</span>
      </div>
      <button class="lcl-actions-btn delete-btn btn-delete-lcl" data-id="${item.id}" title="Supprimer">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
      </button>
    </div>
  `;
  return row;
}

function getItemIcon(label) {
  const l = label.toLowerCase();
  if (l.includes('prêt') || l.includes('immo')) return '🏠';
  if (l.includes('assurance')) return '🛡️';
  if (l.includes('taxe')) return '📋';
  if (l.includes('travaux') || l.includes('entretien')) return '🔨';
  return '🏦';
}

function renderLclStackedBar(total) {
  const stack = document.getElementById('lcl-progress-stack');
  const legend = document.getElementById('lcl-stack-legend');
  stack.innerHTML = '';
  legend.innerHTML = '';

  if (total <= 0) return;

  const colors = ['#0ea5e9', '#6366f1', '#8b5cf6', '#f59e0b', '#10b981', '#ec4899'];

  appState.lclItems.forEach((item, idx) => {
    const color = colors[idx % colors.length];
    const pct = (item.amount / total) * 100;

    const seg = document.createElement('div');
    seg.className = 'stack-segment';
    seg.style.width = `${pct}%`;
    seg.style.backgroundColor = color;
    seg.title = `${item.label}: ${formatCurrency(item.amount)} (${pct.toFixed(1)}%)`;
    stack.appendChild(seg);

    const leg = document.createElement('div');
    leg.className = 'legend-item';
    leg.innerHTML = `
      <div class="legend-label-wrap">
        <span class="legend-color-dot" style="background: ${color};"></span>
        <span style="font-size:0.75rem;">${item.label} (${pct.toFixed(0)}%)</span>
      </div>
    `;
    legend.appendChild(leg);
  });
}

function renderRevolutTab(data) {
  const { revolutBudgetTotal, revolutSpentThisMonth } = data;

  document.getElementById('rev-banner-total').textContent = formatCurrency(revolutBudgetTotal);
  document.getElementById('rev-banner-spent').textContent = `Dépensé : ${formatCurrency(revolutSpentThisMonth)} ce mois`;

  const payerFilter = document.getElementById('filter-rev-payer');
  const curPayerVal = payerFilter.value || 'all';
  payerFilter.innerHTML = `
    <option value="all">Payeur : Tous</option>
    <option value="Carte Revolut Commune">Carte Revolut Commune</option>
    ${appState.profiles.map(p => `<option value="${p.name}">Avancé par ${p.name}</option>`).join('')}
  `;
  payerFilter.value = curPayerVal;

  const container = document.getElementById('revolut-envelopes-list');
  container.innerHTML = '';

  const currentMonthPrefix = new Date().toISOString().slice(0, 7);

  appState.revolutEnvelopes.forEach(env => {
    const spent = appState.expenses
      .filter(e => e.account === 'Revolut' && e.category === env.name && e.date.startsWith(currentMonthPrefix))
      .reduce((sum, e) => sum + (Number(e.amount) || 0), 0);

    const pct = env.budget > 0 ? (spent / env.budget) * 100 : 0;
    const remaining = Math.max(0, env.budget - spent);
    const isOver = spent > env.budget;

    const item = document.createElement('div');
    item.className = 'envelope-item';
    item.innerHTML = `
      <div class="envelope-top">
        <div class="envelope-name-wrap">
          <span class="envelope-icon">${env.icon || '📦'}</span>
          <span class="envelope-name">${env.name}</span>
        </div>
        <div class="envelope-numbers">
          <strong>${formatCurrency(spent)}</strong> / ${formatCurrency(env.budget)}
        </div>
      </div>
      <div class="envelope-progress-bg">
        <div class="envelope-progress-bar" style="width: ${Math.min(100, pct)}%; background-color: ${isOver ? '#f43f5e' : (env.color || '#8b5cf6')};"></div>
      </div>
      <div class="envelope-footer">
        <span>${pct.toFixed(0)}% consommé</span>
        <span style="color: ${isOver ? '#f43f5e' : '#10b981'}; font-weight: 600;">
          ${isOver ? `Dépassement de +${formatCurrency(spent - env.budget)}` : `Reste ${formatCurrency(remaining)}`}
        </span>
      </div>
    `;
    container.appendChild(item);
  });

  renderRevolutExpensesTable();
}

function renderRevolutExpensesTable() {
  const tbody = document.getElementById('revolut-expenses-tbody');
  const catFilter = document.getElementById('filter-rev-cat').value;
  const payerFilter = document.getElementById('filter-rev-payer').value;

  tbody.innerHTML = '';

  const filtered = appState.expenses
    .filter(e => e.account === 'Revolut')
    .filter(e => catFilter === 'all' || e.category === catFilter)
    .filter(e => {
      if (payerFilter === 'all') return true;
      if (payerFilter.startsWith('Avancé par ')) {
        const pName = payerFilter.replace('Avancé par ', '');
        return e.payer === pName;
      }
      return e.payer === payerFilter;
    })
    .sort((a, b) => b.date.localeCompare(a.date));

  if (filtered.length === 0) {
    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-dim" style="padding: 20px;">Aucune dépense trouvée avec ces filtres</td></tr>`;
    return;
  }

  filtered.forEach(e => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${formatDateFR(e.date)}</td>
      <td><strong>${e.desc}</strong></td>
      <td><span class="badge-tag" style="background: rgba(139, 92, 246, 0.15); color: #c084fc;">${e.category}</span></td>
      <td><span class="badge-tag" style="background: rgba(255,255,255,0.06);">${e.payer}</span></td>
      <td class="text-right font-mono" style="font-weight: 700;">${formatCurrency(e.amount)}</td>
      <td class="text-center">
        <button class="delete-btn btn-delete-expense" data-id="${e.id}" title="Supprimer">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        </button>
      </td>
    `;
    tbody.appendChild(tr);
  });
}

function renderRecentExpensesTable() {
  const tbody = document.getElementById('recent-expenses-tbody');
  tbody.innerHTML = '';

  const recent = [...appState.expenses]
    .sort((a, b) => b.date.localeCompare(a.date))
    .slice(0, 5);

  recent.forEach(e => {
    const isLcl = e.account === 'LCL';
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${formatDateFR(e.date)}</td>
      <td><strong>${e.desc}</strong></td>
      <td>
        <span class="bank-tag ${isLcl ? 'lcl-tag' : 'rev-tag'}">${e.account}</span>
      </td>
      <td><span class="badge-tag" style="background: rgba(255,255,255,0.06);">${e.category}</span></td>
      <td>${e.payer}</td>
      <td class="text-right font-mono" style="font-weight: 700; color: ${isLcl ? '#38bdf8' : '#ffffff'};">${formatCurrency(e.amount)}</td>
      <td class="text-center">
        <button class="delete-btn btn-delete-expense" data-id="${e.id}" title="Supprimer">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        </button>
      </td>
    `;
    tbody.appendChild(tr);
  });
}

function renderProfilesTab() {
  const container = document.getElementById('profiles-container');
  container.innerHTML = '';

  appState.profiles.forEach((p, idx) => {
    const card = document.createElement('div');
    card.className = 'profile-edit-card';
    card.innerHTML = `
      <div class="profile-edit-header">
        <div style="display:flex; align-items:center; gap:12px;">
          <div class="profile-avatar-large" style="background:${p.color};">
            ${p.initials || p.name.slice(0, 2).toUpperCase()}
          </div>
          <div>
            <h4 style="font-size:0.95rem; font-weight:700;">Profil ${idx + 1} : ${p.name}</h4>
            <span class="profile-role-badge">${p.role || 'Membre'}</span>
          </div>
        </div>
      </div>
      <div class="form-group-row">
        <div class="form-field">
          <label>Prénom / Nom</label>
          <input type="text" class="custom-input profile-name-input" data-id="${p.id}" value="${p.name}">
        </div>
        <div class="form-field">
          <label>Revenu Net Mensuel (€)</label>
          <input type="number" step="50" class="custom-input profile-income-input" data-id="${p.id}" value="${p.income || 2500}">
        </div>
      </div>
    `;
    container.appendChild(card);
  });

  document.getElementById('rule-50-50').checked = (appState.splitRule === '50');
  document.getElementById('rule-income').checked = (appState.splitRule === 'income');
}

// ============================================================
// 5. EVENT LISTENERS & LIVE CONTROLS
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
  renderAll();

  // Tab Navigation
  document.querySelectorAll('.nav-tab').forEach(tabBtn => {
    tabBtn.addEventListener('click', () => {
      document.querySelectorAll('.nav-tab').forEach(b => {
        b.classList.remove('active');
        b.setAttribute('aria-selected', 'false');
      });
      document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));

      tabBtn.classList.add('active');
      tabBtn.setAttribute('aria-selected', 'true');
      const targetPane = document.getElementById(`pane-${tabBtn.dataset.tab}`);
      if (targetPane) targetPane.classList.add('active');
    });
  });

  // Quick jumps
  const jumpBtn = document.getElementById('btn-jump-to-simulator');
  if (jumpBtn) {
    jumpBtn.addEventListener('click', () => {
      document.getElementById('tab-btn-simulator').click();
    });
  }
  const quickEqualizerCard = document.getElementById('card-quick-equalizer');
  if (quickEqualizerCard) {
    quickEqualizerCard.addEventListener('click', () => {
      document.getElementById('tab-btn-simulator').click();
    });
  }
  document.getElementById('btn-view-all-expenses').addEventListener('click', () => {
    document.getElementById('tab-btn-revolut').click();
  });

  // ==========================================================
  // REAL-TIME SLIDERS & LIVE NUMBER INPUTS (60 FPS)
  // ==========================================================
  const sliderAmount = document.getElementById('slider-loan-amount');
  const numAmount = document.getElementById('num-loan-amount');
  const dashSlider = document.getElementById('dash-slider-amount');

  const sliderShare = document.getElementById('slider-user-share');
  const numShare = document.getElementById('num-user-share');

  const sliderDuration = document.getElementById('slider-loan-duration');
  const sliderInterest = document.getElementById('slider-loan-interest');
  const numInterest = document.getElementById('num-loan-interest');

  const startMonthInput = document.getElementById('input-loan-start-month');
  const numMonthsPaidInput = document.getElementById('num-months-paid');

  // Amount slider
  sliderAmount.addEventListener('input', () => {
    appState.simulator.totalGift = Number(sliderAmount.value) || 25000;
    updateSimulatorRealtime('slider-loan-amount');
  });
  numAmount.addEventListener('input', () => {
    appState.simulator.totalGift = Number(numAmount.value) || 25000;
    updateSimulatorRealtime('num-loan-amount');
  });

  if (dashSlider) {
    dashSlider.addEventListener('input', () => {
      appState.simulator.totalGift = Number(dashSlider.value) || 25000;
      updateSimulatorRealtime('dash-slider-amount');
    });
  }

  // Share slider
  sliderShare.addEventListener('input', () => {
    appState.simulator.userShare = Number(sliderShare.value) || 50;
    updateSimulatorRealtime('slider-user-share');
  });
  numShare.addEventListener('input', () => {
    appState.simulator.userShare = Number(numShare.value) || 50;
    updateSimulatorRealtime('num-user-share');
  });

  // Duration slider
  sliderDuration.addEventListener('input', () => {
    appState.simulator.durationYears = Number(sliderDuration.value) || 15;
    updateSimulatorRealtime('slider-loan-duration');
  });

  // Interest slider
  sliderInterest.addEventListener('input', () => {
    appState.simulator.interestRate = Number(sliderInterest.value) || 0;
    updateSimulatorRealtime('slider-loan-interest');
  });
  numInterest.addEventListener('input', () => {
    appState.simulator.interestRate = Number(numInterest.value) || 0;
    updateSimulatorRealtime('num-loan-interest');
  });

  // Start Month input
  if (startMonthInput) {
    startMonthInput.addEventListener('input', () => {
      appState.simulator.startMonth = startMonthInput.value;
      appState.simulator.monthsPaid = computeMonthsElapsedFromStart(startMonthInput.value);
      updateSimulatorRealtime('input-loan-start-month');
    });
  }

  // Months Paid number input
  if (numMonthsPaidInput) {
    numMonthsPaidInput.addEventListener('input', () => {
      const paid = Math.max(0, parseInt(numMonthsPaidInput.value) || 0);
      appState.simulator.monthsPaid = paid;
      appState.simulator.startMonth = computeStartMonthFromMonthsElapsed(paid);
      updateSimulatorRealtime('num-months-paid');
    });
  }

  // Stepper buttons
  const btnMinus = document.getElementById('btn-step-minus');
  const btnPlus = document.getElementById('btn-step-plus');
  if (btnMinus) {
    btnMinus.addEventListener('click', () => {
      appState.simulator.monthsPaid = Math.max(0, (appState.simulator.monthsPaid || 0) - 1);
      appState.simulator.startMonth = computeStartMonthFromMonthsElapsed(appState.simulator.monthsPaid);
      updateSimulatorRealtime();
    });
  }
  if (btnPlus) {
    btnPlus.addEventListener('click', () => {
      appState.simulator.monthsPaid = (appState.simulator.monthsPaid || 0) + 1;
      appState.simulator.startMonth = computeStartMonthFromMonthsElapsed(appState.simulator.monthsPaid);
      updateSimulatorRealtime();
    });
  }

  // ==========================================================
  // QUICK PRESET BUTTONS (1-CLICK UPDATE)
  // ==========================================================
  document.addEventListener('click', (e) => {
    const presetBtn = e.target.closest('.preset-btn');
    if (!presetBtn) return;

    if (presetBtn.dataset.target === 'amount' || presetBtn.dataset.amount) {
      const amt = Number(presetBtn.dataset.val || presetBtn.dataset.amount);
      if (amt) {
        appState.simulator.totalGift = amt;
        updateSimulatorRealtime();
      }
    } else if (presetBtn.dataset.target === 'share') {
      const sh = Number(presetBtn.dataset.val);
      if (sh) {
        appState.simulator.userShare = sh;
        updateSimulatorRealtime();
      }
    } else if (presetBtn.dataset.target === 'duration') {
      const dur = Number(presetBtn.dataset.val);
      if (dur) {
        appState.simulator.durationYears = dur;
        updateSimulatorRealtime();
      }
    } else if (presetBtn.dataset.start) {
      const st = presetBtn.dataset.start;
      document.querySelectorAll('[data-start]').forEach(b => b.classList.remove('active'));
      presetBtn.classList.add('active');

      if (st === 'current') {
        const now = new Date();
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        appState.simulator.startMonth = `${y}-${m}`;
        appState.simulator.monthsPaid = 0;
      } else if (st === '6m') {
        appState.simulator.monthsPaid = 6;
        appState.simulator.startMonth = computeStartMonthFromMonthsElapsed(6);
      } else if (st === '8m') {
        appState.simulator.monthsPaid = 8;
        appState.simulator.startMonth = computeStartMonthFromMonthsElapsed(8);
      } else if (st === '1y') {
        appState.simulator.monthsPaid = 12;
        appState.simulator.startMonth = computeStartMonthFromMonthsElapsed(12);
      } else if (st === '2y') {
        appState.simulator.monthsPaid = 24;
        appState.simulator.startMonth = computeStartMonthFromMonthsElapsed(24);
      } else if (st === 'jan24') {
        appState.simulator.startMonth = '2024-01';
        appState.simulator.monthsPaid = computeMonthsElapsedFromStart('2024-01');
      }
      updateSimulatorRealtime();
    }
  });

  // Method radios
  document.querySelectorAll('input[name="repay-method"]').forEach(radio => {
    radio.addEventListener('change', () => {
      appState.simulator.method = radio.value;
      saveStateImmediate();
      updateSimulatorRealtime();
    });
  });

  // Borrower / Lender selects
  document.getElementById('select-loan-borrower').addEventListener('change', (e) => {
    appState.simulator.borrowerId = e.target.value;
    saveStateImmediate();
    updateSimulatorRealtime();
  });
  document.getElementById('select-loan-lender').addEventListener('change', (e) => {
    appState.simulator.lenderId = e.target.value;
    saveStateImmediate();
    updateSimulatorRealtime();
  });

  // Reset simulator
  document.getElementById('btn-reset-simulator').addEventListener('click', () => {
    appState.simulator.totalGift = 25000;
    appState.simulator.userShare = 50;
    appState.simulator.durationYears = 15;
    appState.simulator.interestRate = 0.0;
    appState.simulator.method = 'offset';
    appState.simulator.startMonth = '2026-03';
    appState.simulator.monthsPaid = 8;
    saveStateImmediate();
    updateSimulatorRealtime();
  });

  // ==========================================================
  // TAXE FONCIERE INTERACTIVE SYNC (ANNUAL <-> MONTHLY)
  // ==========================================================
  const tfAnnual = document.getElementById('input-tf-annual');
  const tfMonthly = document.getElementById('input-tf-monthly');

  if (tfAnnual && tfMonthly) {
    tfAnnual.addEventListener('input', () => {
      const ann = parseFloat(tfAnnual.value) || 0;
      const m = Math.round((ann / 12) * 100) / 100;
      tfMonthly.value = m;
      updateTaxeFonciereState(m);
    });

    tfMonthly.addEventListener('input', () => {
      const m = parseFloat(tfMonthly.value) || 0;
      const ann = Math.round(m * 12);
      tfAnnual.value = ann;
      updateTaxeFonciereState(m);
    });
  }

  function updateTaxeFonciereState(monthlyAmount) {
    let taxe = appState.lclItems.find(i => i.category === 'taxe');
    if (!taxe) {
      taxe = {
        id: 'lcl-taxe',
        label: 'Taxe Foncière (Mensualisée)',
        amount: monthlyAmount,
        type: 'annualized',
        category: 'taxe',
        notes: `Total annuel ${Math.round(monthlyAmount * 12)} € lissé sur 12 mois`
      };
      appState.lclItems.push(taxe);
    } else {
      taxe.amount = monthlyAmount;
      taxe.notes = `Total annuel ${Math.round(monthlyAmount * 12)} € lissé sur 12 mois`;
    }
    saveStateDebounced();
    const data = calculateBudgets();
    renderTopSummary(data);
    renderDashboard(data);
    updateLclSplitDisplay(data);
    document.getElementById('lcl-banner-total').textContent = formatCurrency(data.lclTotal);
    document.getElementById('lcl-banner-annual').textContent = `${formatCurrency(data.lclAnnual)} / an`;
    document.getElementById('subtotal-taxe-pack').textContent = formatCurrency(data.taxeSubtotal);
    document.getElementById('ps-taxe-val').textContent = formatCurrency(data.taxeSubtotal);
    document.getElementById('ps-total-val').textContent = formatCurrency(data.lclTotal);
  }

  // ==========================================================
  // PROFILES TAB LIVE EDITS
  // ==========================================================
  document.getElementById('profiles-container').addEventListener('input', (e) => {
    const nameInput = e.target.closest('.profile-name-input');
    if (nameInput) {
      const pId = nameInput.dataset.id;
      const profile = appState.profiles.find(x => x.id === pId);
      if (profile) {
        profile.name = nameInput.value.trim() || profile.name;
        profile.initials = profile.name.slice(0, 2).toUpperCase();
        saveStateDebounced();
        renderHeaderProfiles();
        updateSimulatorRealtime();
      }
    }

    const incomeInput = e.target.closest('.profile-income-input');
    if (incomeInput) {
      const pId = incomeInput.dataset.id;
      const profile = appState.profiles.find(x => x.id === pId);
      if (profile) {
        profile.income = parseFloat(incomeInput.value) || 0;
        saveStateDebounced();
        updateSimulatorRealtime();
      }
    }
  });

  // Split rule radio
  document.querySelectorAll('input[name="split-rule-mode"]').forEach(radio => {
    radio.addEventListener('change', () => {
      appState.splitRule = radio.value;
      const splitPill = document.getElementById('split-mode-label');
      if (splitPill) {
        splitPill.textContent = (appState.splitRule === '50') ? '50 / 50 + Prêt compensé' : 'Prorata Revenus + Prêt';
      }
      saveStateImmediate();
      updateSimulatorRealtime();
    });
  });

  // ==========================================================
  // REVOLUT FILTERS
  // ==========================================================
  document.getElementById('filter-rev-cat').addEventListener('change', renderRevolutExpensesTable);
  document.getElementById('filter-rev-payer').addEventListener('change', renderRevolutExpensesTable);

  // ==========================================================
  // MODAL EXPENSE (ADD / DELETE)
  // ==========================================================
  const expenseModal = document.getElementById('expense-modal');
  const btnQuickExpense = document.getElementById('btn-quick-expense');
  const btnAddRevExpense = document.getElementById('btn-add-revolut-expense');
  const btnCloseExpenseModal = document.getElementById('btn-close-expense-modal');
  const btnCancelModal = document.getElementById('btn-cancel-modal');
  const expenseForm = document.getElementById('expense-form');

  function openExpenseModal(defaultAccount = 'Revolut') {
    expenseForm.reset();
    document.getElementById('modal-expense-id').value = '';
    document.getElementById('modal-expense-date').value = new Date().toISOString().slice(0, 10);
    document.getElementById('modal-expense-account').value = defaultAccount;

    const payerSelect = document.getElementById('modal-expense-payer');
    payerSelect.innerHTML = `
      <option value="Carte Revolut Commune">Compte Commun Revolut</option>
      <option value="Compte LCL">Compte Commun LCL</option>
      ${appState.profiles.map(p => `<option value="${p.name}">Avancé par ${p.name} (fonds perso)</option>`).join('')}
    `;

    expenseModal.style.display = 'flex';
  }

  function closeExpenseModal() {
    expenseModal.style.display = 'none';
  }

  btnQuickExpense.addEventListener('click', () => openExpenseModal('Revolut'));
  btnAddRevExpense.addEventListener('click', () => openExpenseModal('Revolut'));
  btnCloseExpenseModal.addEventListener('click', closeExpenseModal);
  btnCancelModal.addEventListener('click', closeExpenseModal);

  expenseForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const newExp = {
      id: 'exp-' + Date.now(),
      desc: document.getElementById('modal-expense-desc').value.trim(),
      amount: parseFloat(document.getElementById('modal-expense-amount').value) || 0,
      date: document.getElementById('modal-expense-date').value,
      account: document.getElementById('modal-expense-account').value,
      category: document.getElementById('modal-expense-cat').value,
      payer: document.getElementById('modal-expense-payer').value
    };

    appState.expenses.unshift(newExp);
    saveStateImmediate();
    closeExpenseModal();
    renderAll();
  });

  document.addEventListener('click', (e) => {
    const delBtn = e.target.closest('.btn-delete-expense');
    if (delBtn) {
      const expId = delBtn.dataset.id;
      if (confirm('Voulez-vous supprimer cette dépense ?')) {
        appState.expenses = appState.expenses.filter(x => x.id !== expId);
        saveStateImmediate();
        renderAll();
      }
    }
  });

  // ==========================================================
  // MODAL LCL STATIC CHARGES (ADD / DELETE)
  // ==========================================================
  const lclModal = document.getElementById('lcl-item-modal');
  const btnAddLclItem = document.getElementById('btn-add-lcl-item');
  const btnCloseLclModal = document.getElementById('btn-close-lcl-modal');
  const btnCancelLclModal = document.getElementById('btn-cancel-lcl-modal');
  const lclForm = document.getElementById('lcl-item-form');

  function openLclModal() {
    lclForm.reset();
    document.getElementById('modal-lcl-id').value = '';
    lclModal.style.display = 'flex';
  }
  function closeLclModal() {
    lclModal.style.display = 'none';
  }

  btnAddLclItem.addEventListener('click', openLclModal);
  btnCloseLclModal.addEventListener('click', closeLclModal);
  btnCancelLclModal.addEventListener('click', closeLclModal);

  lclForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const categoryVal = document.getElementById('modal-lcl-category') ? document.getElementById('modal-lcl-category').value : 'credit';
    const newItem = {
      id: 'lcl-' + Date.now(),
      label: document.getElementById('modal-lcl-label').value.trim(),
      amount: parseFloat(document.getElementById('modal-lcl-amount').value) || 0,
      type: 'monthly',
      category: categoryVal,
      notes: document.getElementById('modal-lcl-notes').value.trim()
    };

    appState.lclItems.push(newItem);
    saveStateImmediate();
    closeLclModal();
    renderAll();
  });

  document.addEventListener('click', (e) => {
    const delBtn = e.target.closest('.btn-delete-lcl');
    if (delBtn) {
      const lclId = delBtn.dataset.id;
      if (confirm('Supprimer cette charge fixe LCL ?')) {
        appState.lclItems = appState.lclItems.filter(x => x.id !== lclId);
        saveStateImmediate();
        renderAll();
      }
    }
  });

  // Revolut add envelope
  document.getElementById('btn-add-revolut-cat').addEventListener('click', () => {
    const newName = prompt('Nom de la nouvelle enveloppe budgétaire Revolut :');
    if (newName) {
      const newBudget = parseFloat(prompt('Budget alloué par mois (€) :', '100')) || 100;
      appState.revolutEnvelopes.push({
        id: 'rev-cat-' + Date.now(),
        name: newName,
        budget: newBudget,
        icon: '🏷️',
        color: '#6366f1'
      });
      saveStateImmediate();
      renderAll();
    }
  });

  // ==========================================================
  // EXPORT / IMPORT JSON
  // ==========================================================
  document.getElementById('btn-export-data').addEventListener('click', exportDataJson);
  document.getElementById('btn-export-json').addEventListener('click', exportDataJson);

  function exportDataJson() {
    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(appState, null, 2));
    const dlAnchor = document.createElement('a');
    dlAnchor.setAttribute("href", dataStr);
    dlAnchor.setAttribute("download", `DuoBudget_${appState.profiles[0].name}_${appState.profiles[1].name}_${new Date().toISOString().slice(0, 10)}.json`);
    document.body.appendChild(dlAnchor);
    dlAnchor.click();
    dlAnchor.remove();
  }

  const importFileInput = document.getElementById('import-json-file');
  importFileInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (event) => {
      try {
        const imported = JSON.parse(event.target.result);
        if (imported.profiles && imported.simulator && imported.lclItems && imported.revolutEnvelopes) {
          appState = imported;
          saveStateImmediate();
          renderAll();
          alert('Données importées avec succès !');
        } else {
          alert('Fichier JSON incompatible avec DuoBudget.');
        }
      } catch (err) {
        alert('Erreur lors de la lecture du fichier JSON : ' + err.message);
      }
    };
    reader.readAsText(file);
  });

  document.getElementById('btn-reset-all-data').addEventListener('click', () => {
    if (confirm('Attention : réinitialiser toutes les données aux valeurs par défaut pour Axel et Ariane ?')) {
      appState = JSON.parse(JSON.stringify(DEFAULT_STATE));
      saveStateImmediate();
      renderAll();
      alert('Application réinitialisée avec succès !');
    }
  });
});
