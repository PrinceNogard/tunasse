<?php
declare(strict_types=1);

namespace Tunasse\Storage;

class JsonStore
{
    private string $filePath;

    public function __construct(?string $filePath = null)
    {
        $this->filePath = $filePath ?? (__DIR__ . '/../../data/app_state.json');
    }

    public static function getDefaultState(): array
    {
        return [
            'profiles' => [
                [
                    'id' => 'p1',
                    'name' => 'Axel',
                    'income' => 2600.00,
                    'color' => '#0ea5e9',
                    'initials' => 'AX',
                    'role' => 'Emprunteur (part 50% don)'
                ],
                [
                    'id' => 'p2',
                    'name' => 'Ariane',
                    'income' => 2400.00,
                    'color' => '#8b5cf6',
                    'initials' => 'AR',
                    'role' => 'Bénéficiaire don familial'
                ]
            ],
            'splitRule' => '50',
            'simulator' => [
                'totalGift' => 25000.00,
                'userShare' => 50,
                'durationYears' => 15,
                'interestRate' => 0.0,
                'borrowerId' => 'p1',
                'lenderId' => 'p2',
                'method' => 'offset',
                'startMonth' => '2026-03',
                'monthsPaid' => 8
            ],
            'travauxFund' => [
                'initialBudget' => 50000.00,
                'lots' => [
                    [
                        'id' => 'lot-1',
                        'name' => 'Toiture & Couverture (Chantier Roman)',
                        'allocatedBudget' => 15000.00,
                        'icon' => '🏠',
                        'status' => 'ongoing',
                        'notes' => 'Rénovation toiture et étanchéité'
                    ],
                    [
                        'id' => 'lot-2',
                        'name' => 'Logistique, Repas & Frais Chantier',
                        'allocatedBudget' => 2000.00,
                        'icon' => '🥪',
                        'status' => 'completed',
                        'notes' => 'Frais de bouche et intendance ouvriers'
                    ],
                    [
                        'id' => 'lot-3',
                        'name' => "Ventilation & Qualité de l'air (VMC)",
                        'allocatedBudget' => 1500.00,
                        'icon' => '🌀',
                        'status' => 'ongoing',
                        'notes' => 'Installation VMC hygroréglable'
                    ],
                    [
                        'id' => 'lot-4',
                        'name' => 'Menuiseries, Fenêtres & Isolation',
                        'allocatedBudget' => 12500.00,
                        'icon' => '🪟',
                        'status' => 'future',
                        'notes' => 'Changement vitrages et isolation combles'
                    ],
                    [
                        'id' => 'lot-5',
                        'name' => 'Électricité, Réseau & Domotique',
                        'allocatedBudget' => 7500.00,
                        'icon' => '⚡',
                        'status' => 'future',
                        'notes' => 'Remise aux normes et tableau'
                    ],
                    [
                        'id' => 'lot-6',
                        'name' => 'Plomberie & Sanitaires (SdB)',
                        'allocatedBudget' => 6500.00,
                        'icon' => '🚿',
                        'status' => 'future',
                        'notes' => "Réseaux d'eau et salle de bain"
                    ],
                    [
                        'id' => 'lot-7',
                        'name' => 'Réserve Imprévus & Contingence',
                        'allocatedBudget' => 5000.00,
                        'icon' => '🛡️',
                        'status' => 'reserve',
                        'notes' => 'Marge de sécurité imprévus chantier'
                    ]
                ],
                'items' => [
                    [
                        'id' => 'trv-1',
                        'date' => '2026-10-10',
                        'reason' => 'Toiture Roman Avance',
                        'lotId' => 'lot-1',
                        'amountAxel' => 4302.00,
                        'amountAriane' => 4302.00,
                        'reimbursed' => false,
                        'notes' => 'Avance conjointe démarrage chantier toiture'
                    ],
                    [
                        'id' => 'trv-2',
                        'date' => '2026-10-11',
                        'reason' => 'Toiture Roman Avance (Frais de bouche)',
                        'lotId' => 'lot-2',
                        'amountAxel' => 1000.00,
                        'amountAriane' => 1000.00,
                        'reimbursed' => false,
                        'notes' => 'Avance conjointe logistique & repas chantier'
                    ],
                    [
                        'id' => 'trv-3',
                        'date' => '2026-10-14',
                        'reason' => 'Toiture Roman',
                        'lotId' => 'lot-1',
                        'amountAxel' => 0.00,
                        'amountAriane' => 1874.85,
                        'reimbursed' => false,
                        'notes' => 'Règlement direct effectué par Ariane'
                    ],
                    [
                        'id' => 'trv-4',
                        'date' => '2026-10-15',
                        'reason' => 'acompte vmc',
                        'lotId' => 'lot-3',
                        'amountAxel' => 0.00,
                        'amountAriane' => 636.00,
                        'reimbursed' => false,
                        'notes' => 'Acompte matériel VMC par Ariane'
                    ]
                ]
            ],
            'lclItems' => [
                [
                    'id' => 'lcl-1',
                    'label' => 'Mensualité Prêt Immobilier LCL',
                    'amount' => 1150.00,
                    'type' => 'monthly',
                    'category' => 'credit',
                    'notes' => 'Prélèvement principal amortissable 15 ans'
                ],
                [
                    'id' => 'lcl-2',
                    'label' => 'Assurance Emprunteur (Prêt Immo)',
                    'amount' => 38.00,
                    'type' => 'monthly',
                    'category' => 'credit',
                    'notes' => 'Assurance décès / invalidité co-emprunteurs'
                ],
                [
                    'id' => 'lcl-3',
                    'label' => 'Assurance Habitation Maison',
                    'amount' => 34.00,
                    'type' => 'monthly',
                    'category' => 'credit',
                    'notes' => 'Formule multirisque habitation complète'
                ],
                [
                    'id' => 'lcl-4',
                    'label' => 'Taxe Foncière (Mensualisée)',
                    'amount' => 110.00,
                    'type' => 'annualized',
                    'category' => 'taxe',
                    'notes' => 'Total annuel 1 320 € lissé sur 12 mois'
                ],
                [
                    'id' => 'lcl-5',
                    'label' => 'Provision Entretien Maison & Travaux',
                    'amount' => 50.00,
                    'type' => 'monthly',
                    'category' => 'maintenance',
                    'notes' => 'Réserve pour réparations ou entretiens périodiques'
                ]
            ],
            'revolutEnvelopes' => [
                [
                    'id' => 'rev-cat-1',
                    'name' => 'Courses & Alimentation',
                    'budget' => 550.00,
                    'icon' => '🛒',
                    'color' => '#10b981'
                ],
                [
                    'id' => 'rev-cat-2',
                    'name' => 'Charges & Fluides (Énergie, Eau)',
                    'budget' => 140.00,
                    'icon' => '⚡',
                    'color' => '#0ea5e9'
                ],
                [
                    'id' => 'rev-cat-3',
                    'name' => 'Sorties & Restos',
                    'budget' => 250.00,
                    'icon' => '🥂',
                    'color' => '#f59e0b'
                ],
                [
                    'id' => 'rev-cat-4',
                    'name' => 'Internet & Abonnements Foyer',
                    'budget' => 45.00,
                    'icon' => '📶',
                    'color' => '#8b5cf6'
                ],
                [
                    'id' => 'rev-cat-5',
                    'name' => 'Maison, Déco & Jardin',
                    'budget' => 80.00,
                    'icon' => '🪴',
                    'color' => '#ec4899'
                ],
                [
                    'id' => 'rev-cat-6',
                    'name' => 'Animaux & Divers Foyer',
                    'budget' => 90.00,
                    'icon' => '🐾',
                    'color' => '#64748b'
                ]
            ],
            'expenses' => [
                [
                    'id' => 'exp-1',
                    'date' => '2026-10-15',
                    'desc' => 'Courses Supermarché Carrefour',
                    'amount' => 87.40,
                    'account' => 'Revolut',
                    'category' => 'Courses & Alimentation',
                    'payer' => 'Carte Revolut Commune'
                ],
                [
                    'id' => 'exp-2',
                    'date' => '2026-10-13',
                    'desc' => 'Dîner Restaurant Italien',
                    'amount' => 64.00,
                    'account' => 'Revolut',
                    'category' => 'Sorties & Restos',
                    'payer' => 'Carte Revolut Commune'
                ],
                [
                    'id' => 'exp-3',
                    'date' => '2026-10-11',
                    'desc' => 'Facture EDF Électricité',
                    'amount' => 128.50,
                    'account' => 'Revolut',
                    'category' => 'Charges & Fluides (Énergie, Eau)',
                    'payer' => 'Carte Revolut Commune'
                ],
                [
                    'id' => 'exp-4',
                    'date' => '2026-10-09',
                    'desc' => 'Magasin Bricolage & Ampoules',
                    'amount' => 42.90,
                    'account' => 'Revolut',
                    'category' => 'Maison, Déco & Jardin',
                    'payer' => 'Axel'
                ],
                [
                    'id' => 'exp-5',
                    'date' => '2026-10-05',
                    'desc' => 'Box Fibre Internet Orange',
                    'amount' => 39.99,
                    'account' => 'Revolut',
                    'category' => 'Internet & Abonnements Foyer',
                    'payer' => 'Carte Revolut Commune'
                ],
                [
                    'id' => 'exp-6',
                    'date' => '2026-10-05',
                    'desc' => 'Prélèvement Mensualité Prêt Immo',
                    'amount' => 1150.00,
                    'account' => 'LCL',
                    'category' => 'Prêt & Patrimoine',
                    'payer' => 'Compte LCL'
                ],
                [
                    'id' => 'exp-7',
                    'date' => '2026-10-05',
                    'desc' => 'Assurance Emprunteur & Logement',
                    'amount' => 72.00,
                    'account' => 'LCL',
                    'category' => 'Prêt & Patrimoine',
                    'payer' => 'Compte LCL'
                ]
            ]
        ];
    }

    public function load(): array
    {
        if (!file_exists($this->filePath)) {
            $default = self::getDefaultState();
            $this->save($default);
            return $default;
        }

        $content = @file_get_contents($this->filePath);
        if ($content === false || trim($content) === '') {
            $default = self::getDefaultState();
            $this->save($default);
            return $default;
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            $default = self::getDefaultState();
            $this->save($default);
            return $default;
        }

        return $this->migrate($decoded);
    }

    public function save(array $state): bool
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return @file_put_contents($this->filePath, $json, LOCK_EX) !== false;
    }

    public function reset(): array
    {
        $default = self::getDefaultState();
        $this->save($default);
        return $default;
    }

    private function migrate(array $state): array
    {
        $default = self::getDefaultState();

        if (empty($state['profiles'])) {
            $state['profiles'] = $default['profiles'];
        }
        if (!isset($state['splitRule'])) {
            $state['splitRule'] = '50';
        }
        if (empty($state['simulator'])) {
            $state['simulator'] = $default['simulator'];
        }
        if (empty($state['lclItems'])) {
            $state['lclItems'] = $default['lclItems'];
        }
        if (empty($state['revolutEnvelopes'])) {
            $state['revolutEnvelopes'] = $default['revolutEnvelopes'];
        }
        if (!isset($state['expenses'])) {
            $state['expenses'] = $default['expenses'];
        }

        if (empty($state['travauxFund'])) {
            $state['travauxFund'] = $default['travauxFund'];
        } else {
            if (empty($state['travauxFund']['lots'])) {
                $state['travauxFund']['lots'] = $default['travauxFund']['lots'];
            }
            if (!isset($state['travauxFund']['items'])) {
                $state['travauxFund']['items'] = $default['travauxFund']['items'];
            }
            if (!isset($state['travauxFund']['initialBudget'])) {
                $state['travauxFund']['initialBudget'] = 50000.00;
            }
        }

        return $state;
    }
}
