# Directives Antigravity & Workflow Git

## 1. Validation & Commits Automatiques
- **Acceptation automatique** : Tous les commits générés par l'agent sont automatiquement acceptés et validés sans solliciter de confirmation préalable de l'utilisateur.
- **Workflow Git systématique** : Dès qu'une modification, une fonctionnalité ou une correction est terminée et validée par les tests :
  1. `git add .` (ou cibler les fichiers modifiés).
  2. `git commit -m "<type>(<scope>): <description claire en français ou anglais standard>"`.
  3. `git push origin main` systématique.
- **Tests obligatoires** : Exécuter `php tests/run_php_tests.php` avant tout commit pour garantir un statut 100% vert (40/40 tests).

## 2. Standards Techniques du Projet
- **Stack** : PHP 8.3 natif (MVC avec rendu serveur dans `templates/`, logique métier dans `src/`, persistance dans `data/app_state.json` et API REST dans `api.php`).
- **Design & Esthétique** : Interface sobre, élégante, matte dark slate (`#0a0d14`), bordures subtiles (`#1e293b`), aucune couleur criarde ni néon.
- **Enveloppe Travaux** : Respecter l'équilibre financier de base de 50 000,00 €, le suivi des lots et le solde exact des avances personnelles d'Axel et Ariane.
