/**
 * DuoBudget • State Management & Default Schema Module
 */

export const STORAGE_KEY = 'duobudget_data_v3';

export const DEFAULT_STATE = {
  profiles: [
    {
      id: 'p1',
      name: 'Axel',
      income: 2600.00,
      color: '#0ea5e9',
      initials: 'AX',
      role: 'Emprunteur (part 50% don)'
    },
    {
      id: 'p2',
      name: 'Ariane',
      income: 2400.00,
      color: '#8b5cf6',
      initials: 'AR',
      role: 'Bénéficiaire don familial'
    }
  ],
  splitRule: '50', // '50' or 'income'
  simulator: {
    totalGift: 25000.00,
    userShare: 50,
    durationYears: 15,
    interestRate: 0.0,
    borrowerId: 'p1',
    lenderId: 'p2',
    method: 'offset', // 'offset' or 'direct'
    startMonth: '2026-03',
    monthsPaid: 8
  },
  travauxFund: {
    initialBudget: 50000.00,
    lots: [
      {
        id: 'lot-1',
        name: 'Toiture & Couverture (Chantier Roman)',
        allocatedBudget: 15000.00,
        icon: '🏠',
        status: 'ongoing',
        notes: 'Rénovation toiture et étanchéité'
      },
      {
        id: 'lot-2',
        name: 'Logistique, Repas & Frais Chantier',
        allocatedBudget: 2000.00,
        icon: '🥪',
        status: 'completed',
        notes: 'Frais de bouche et intendance ouvriers'
      },
      {
        id: 'lot-3',
        name: 'Ventilation & Qualité de l\'air (VMC)',
        allocatedBudget: 1500.00,
        icon: '🌀',
        status: 'ongoing',
        notes: 'Installation VMC hygroréglable'
      },
      {
        id: 'lot-4',
        name: 'Menuiseries, Fenêtres & Isolation',
        allocatedBudget: 12500.00,
        icon: '🪟',
        status: 'future',
        notes: 'Changement vitrages et isolation combles'
      },
      {
        id: 'lot-5',
        name: 'Électricité, Réseau & Domotique',
        allocatedBudget: 7500.00,
        icon: '⚡',
        status: 'future',
        notes: 'Remise aux normes et tableau'
      },
      {
        id: 'lot-6',
        name: 'Plomberie & Sanitaires (SdB)',
        allocatedBudget: 6500.00,
        icon: '🚿',
        status: 'future',
        notes: 'Réseaux d\'eau et salle de bain'
      },
      {
        id: 'lot-7',
        name: 'Réserve Imprévus & Contingence',
        allocatedBudget: 5000.00,
        icon: '🛡️',
        status: 'reserve',
        notes: 'Marge de sécurité imprévus chantier'
      }
    ],
    items: [
      {
        id: 'trv-1',
        date: '2026-10-10',
        reason: 'Toiture Roman Avance',
        lotId: 'lot-1',
        amountAxel: 4302.00,
        amountAriane: 4302.00,
        reimbursed: false,
        notes: 'Avance conjointe démarrage chantier toiture'
      },
      {
        id: 'trv-2',
        date: '2026-10-11',
        reason: 'Toiture Roman Avance (Frais de bouche)',
        lotId: 'lot-2',
        amountAxel: 1000.00,
        amountAriane: 1000.00,
        reimbursed: false,
        notes: 'Avance conjointe logistique & repas chantier'
      },
      {
        id: 'trv-3',
        date: '2026-10-14',
        reason: 'Toiture Roman',
        lotId: 'lot-1',
        amountAxel: 0.00,
        amountAriane: 1874.85,
        reimbursed: false,
        notes: 'Règlement direct effectué par Ariane'
      },
      {
        id: 'trv-4',
        date: '2026-10-15',
        reason: 'acompte vmc',
        lotId: 'lot-3',
        amountAxel: 0.00,
        amountAriane: 636.00,
        reimbursed: false,
        notes: 'Acompte matériel VMC par Ariane'
      }
    ]
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

/**
 * Validate and migrate stored state ensuring all required sections are present
 * @param {Object} parsed 
 * @param {Object} defaultState 
 * @returns {Object}
 */
export function validateAndMigrateState(parsed, defaultState = DEFAULT_STATE) {
  if (!parsed || typeof parsed !== 'object') {
    return JSON.parse(JSON.stringify(defaultState));
  }

  const merged = { ...defaultState, ...parsed };

  if (!merged.travauxFund || !Array.isArray(merged.travauxFund.items) || merged.travauxFund.items.length === 0) {
    merged.travauxFund = JSON.parse(JSON.stringify(defaultState.travauxFund));
  } else if (!Array.isArray(merged.travauxFund.lots) || merged.travauxFund.lots.length === 0) {
    merged.travauxFund.lots = JSON.parse(JSON.stringify(defaultState.travauxFund.lots));
  }

  if (!Array.isArray(merged.lclItems) || merged.lclItems.length === 0) {
    merged.lclItems = JSON.parse(JSON.stringify(defaultState.lclItems));
  }

  if (!Array.isArray(merged.revolutEnvelopes) || merged.revolutEnvelopes.length === 0) {
    merged.revolutEnvelopes = JSON.parse(JSON.stringify(defaultState.revolutEnvelopes));
  }

  if (!Array.isArray(merged.profiles) || merged.profiles.length < 2) {
    merged.profiles = JSON.parse(JSON.stringify(defaultState.profiles));
  }

  return merged;
}

/**
 * Load state from localStorage with fallback
 * @param {string} key 
 * @param {Object} defaultState 
 * @returns {Object}
 */
export function loadState(key = STORAGE_KEY, defaultState = DEFAULT_STATE) {
  try {
    if (typeof window !== 'undefined' && window.__INITIAL_STATE__) {
      return validateAndMigrateState(window.__INITIAL_STATE__, defaultState);
    }
    if (typeof localStorage !== 'undefined') {
      const raw = localStorage.getItem(key) || localStorage.getItem('duobudget_data_v3') || localStorage.getItem('duobudget_state_v3');
      if (raw) {
        const parsed = JSON.parse(raw);
        return validateAndMigrateState(parsed, defaultState);
      }
    }
  } catch (e) {
    console.warn('Storage fallback to default:', e);
  }
  return JSON.parse(JSON.stringify(defaultState));
}
