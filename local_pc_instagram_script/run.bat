@echo off
REM One pass of the IntentScore Instagram worker. Point Windows Task Scheduler at this
REM file and set it to repeat every 5 minutes; the console window closes on its own.
REM
REM If php is not on PATH, replace "php" below with the full path to php.exe,
REM for example C:\xampp\php\php.exe

cd /d "%~dp0"
php worker.php >> worker.log 2>&1
