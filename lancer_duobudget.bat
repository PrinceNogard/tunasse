@echo off
echo [DuoBudget] Lancement de l'application...
start http://127.0.0.1:8085
php -S 127.0.0.1:8085 -t " %~dp0\
pause
