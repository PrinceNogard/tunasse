@echo off
title DuoBudget - Serveur PHP
echo [DuoBudget] Lancement du serveur Web PHP 8.3...
cd /d "%~dp0"
start http://127.0.0.1:8085/index.php
php -S 127.0.0.1:8085 -t .
pause
