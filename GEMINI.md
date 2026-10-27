# Instructions Agent Gemini / Antigravity

- **Acceptation automatique des commits** : Tous les commits sont automatiquement acceptés et validés.
- Dès qu'un changement est effectué et testé avec succès (`php tests/run_php_tests.php`), commiter et pousser automatiquement vers `origin/main` sans demander confirmation.
- **Diffs chirurgicaux** : Ne jamais réécrire un fichier existant > 40 lignes avec `write_to_file`. Utiliser `replace_file_content` pour cibler les lignes modifiées.
- **Contrôles ultra-rapides** : Valider via `php scripts/check_anti_regression.php` (< 15 ms).
- **Isolation stricte des données** : Ne jamais commiter `data/app_state.json`. Les données du serveur distant font foi et ne doivent jamais être écrasées par le dev local ou git.

