# 🏠 DuoBudget • Gestion de Budget de Couple & Simulateur de Prêt Dynamique

Application web moderne, fluide et réactive conçue pour la gestion financière de couple, paramétrée par défaut pour **Axel** et **Ariane**, avec une architecture générique de profils et un **simulateur de prêt ultra-réactif en temps réel**.

---

## 🌟 Nouveautés & Améliorations Apportées

### 1. 👥 Système Générique de Profils (Axel & Ariane)
- **Profils modulaires** : Le système est 100% générique tout en étant paramétré sur-mesure pour **Axel** et **Ariane**.
- **Gestionnaire de profils** (Onglet *Profils & Clés*) :
  - Modification des prénoms, rôles et couleurs.
  - Déclaration des revenus nets mensuels (ex: Axel 2 800 €, Ariane 2 600 €).
  - Choix de la clé de répartition de base :
    - **50% / 50% (Égalité stricte)**
    - **Proportionnelle aux revenus nets (Équité au prorata des salaires)**
- **Affectation dynamique partout** :
  - Sur le tableau de bord (cartes de contribution d'Axel et d'Ariane).
  - Dans les dropdowns de dépenses (*"Payé par Axel"*, *"Payé par Ariane"*).
  - Dans le simulateur (*Sélection dynamique de l'emprunteur et du prêteur*).

### 2. ⚡ Simulateur de Prêt Dynamique à la Volée (60 FPS)
- **Calculs instantanés en direct** : Dès qu'un curseur est déplacé, les calculs mathématiques se font en mémoire et mettent à jour le DOM sans rechargement ni latence.
- **Curseurs interactifs & champs numériques synchronisés** :
  - **Montant emprunté** (5 000 € à 150 000 €).
  - **Boutons rapides (Presets)** : [ 10 000 € ] [ 15 000 € ] [ **25 000 € (Défaut)** ] [ 35 000 € ] [ 50 000 € ].
  - **Quote-part** : [ 25% ] [ **50% (Moitié = 12 500 €)** ] [ 75% ] [ 100% ].
  - **Durée** : [ 5 ans ] [ 10 ans ] [ **15 ans (180 mois - Prêt Immo)** ] [ 20 ans ].
  - **Taux d'intérêt** (0,0% par défaut pour prêt familial amical).
- **Curseur rapide intégré sur le Tableau de bord** : Permet de tester différents montants d'apport directement depuis la vue d'ensemble sans changer d'onglet !
- **Calcul exact de la mensualité due à Ariane** :
  $$\text{Mensualité} = \frac{25\,000\text{ €} \times 50\%}{180\text{ mois}} = \mathbf{69,44\text{ € / mois}}$$
- **Explications mathématiques claires** :
  - **Option Compensation Compte Commun (Recommandée)** : Axel verse +69,44 € en plus, Ariane verse -69,44 € en moins $\rightarrow$ Axel verse **138,88 € de plus qu'Ariane** au total sur le compte joint LCL.
  - **Option Virement Direct** : Chacun verse sa part normale sur le compte joint, et Axel vire 69,44 €/mois sur le compte privé d'Ariane.

### 3. 🏛️ Compte Commun LCL (Maison Statique)
- Prêt immobilier principal (1 150 € / mois sur 15 ans).
- Assurances emprunteur et habitation.
- Taxe foncière mensualisée (110 € / mois = 1 320 € / an) pour lisser les dépenses.
- Visualisation mensuelle et projection annuelle.

### 4. 💳 Compte Commun Revolut (Vie Courante)
- Enveloppes budgétaires : Alimentation, Fluides, Sorties, Abonnements, Maison/Déco, Animaux/Divers.
- Jauges de consommation en temps réel et alertes de dépassement.
- Saisie des dépenses avec sélection du payeur (Carte commune, Axel ou Ariane).

### 5. 🔨 Enveloppe Travaux Rénovation (50 000 €) & Avances Personnelles
- **Suivi du fonds travaux global** :
  - **Budget initial alloué** : 50 000,00 € (prêt ou épargne dédiée travaux maison).
  - **Montant consommé** : 13 114,85 € (26,23% engagé).
  - **Solde restant disponible** : 36 885,15 € (73,77% disponible pour les futurs chantiers).
- **Contrôle et équilibrage des avances personnelles** :
  - **Avances Axel** : 5 302,00 € (40,43% des dépenses engagées).
  - **Avances Ariane** : 7 812,85 € (59,57% des dépenses engagées).
  - **Écart** : Ariane a avancé +2 510,85 € de plus qu'Axel.
- **Double perspective de règlement** :
  1. **Remboursement par le Compte Travaux (Option recommandée)** :
     - Le compte travaux rembourse **5 302,00 € à Axel** et **7 812,85 € à Ariane**.
     - Solde net restant après déblocage : **36 885,15 €**.
  2. **Rééquilibrage Interne Direct Axel ↔ Ariane (si partage 50/50 sans tirage)** :
     - Part équitable de chacun (50%) : 6 557,43 €.
     - **Axel doit virer 1 255,43 € à Ariane**.
- **Tableau de bord interactif des factures travaux** :
  - *Toiture Roman Avance* : 4 302,00 € (Axel) | 4 302,00 € (Ariane) $\rightarrow$ 8 604,00 € (Non remboursé)
  - *Toiture Roman Avance (Frais de bouche)* : 1 000,00 € (Axel) | 1 000,00 € (Ariane) $\rightarrow$ 2 000,00 € (Non remboursé)
  - *Toiture Roman* : 0,00 € (Axel) | 1 874,85 € (Ariane) $\rightarrow$ 1 874,85 € (Non remboursé)
  - *Acompte VMC* : 0,00 € (Axel) | 636,00 € (Ariane) $\rightarrow$ 636,00 € (Non remboursé)
  - **Interactivité complète** :
    - Bascule en un clic du badge `Non remboursé` $\leftrightarrow$ `✓ Remboursé`.
    - Bouton rapide d'ajout `+ Ajouter une dépense travaux` avec aide à la répartition (50/50, 100% Axel, 100% Ariane).
    - Modification et suppression en direct.
    - Filtres par statut (Tous / Non remboursés / Remboursés) et recherche instantanée.

---

## 🚀 Utilisation Locale

L'application est servie localement sur :
👉 **http://127.0.0.1:8085**

Fichiers sources dans le projet :
- [index.html](file:///c:/Users/User/.gemini/antigravity/scratch/tunasse/index.html)
- [styles.css](file:///c:/Users/User/.gemini/antigravity/scratch/tunasse/styles.css)
- [app.js](file:///c:/Users/User/.gemini/antigravity/scratch/tunasse/app.js)
