# Vélocité & Sécurité Anti-Régression

## 1. Règle d'Or de Vélocité (Diffs Chirurgicaux)
- **INTERDICTION ABSOLUE** de réécrire intégralement un fichier existant de plus de 40 lignes avec `write_to_file`.
- **OBLIGATION STRICTE** d'utiliser `replace_file_content` ou `multi_replace_file_content` pour cibler exclusivement les lignes modifiées. Cela divise le temps d'attente de génération par 20 à 50 (1,5s au lieu de 100s).
- **Concision des réponses** : Réponses directes, techniques et sans verbiage inutile pour limiter l'inflation de contexte et accélérer les échanges.

## 2. Contrôles Anti-Régression par Stack

### A. Stack PHP / Backend & Base de Données
- **Requêtes préparées obligatoires** : Zéro concaténation de variable brute dans les requêtes SQL (sécurité PDO systématique).
- **Échappement XSS systématique** : Toutes les données dynamiques affichées dans les vues/templates doivent être protégées via `htmlspecialchars` ou la classe `Format::`.
- **Intégrité des données** : Préservation du schéma, des types et des migrations dans `data/app_state.json`.

### B. Stack JS / Frontend & DOM
- **Préservation du stockage** : Préservation stricte de la structure des données dans `localStorage` et compatibilité avec l'hydratation serveur (`window.__INITIAL_STATE__`).
- **Calculs financiers fiables** : Maintien de l'exactitude des calculs de prêts (annuités, taux zéro/composés) et de l'enveloppe travaux 50 000,00 €.
- **Sécurité DOM** : Privilégier `textContent` ou assainir toute injection HTML dynamique.

## 3. Vélocité des Tests & Hooks
- Tout script de validation ou hook pré/post-commit doit s'exécuter en **moins de 150 ms** (`scripts/check_anti_regression.php` s'exécute en < 10 ms).
- Exécuter systématiquement `php tests/run_php_tests.php` et `php scripts/check_anti_regression.php` avant tout commit automatique.
