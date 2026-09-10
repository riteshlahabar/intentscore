@echo off
REM ---------------------------------------------------------------------------
REM Keeps the IntentScore Instagram worker running.
REM
REM Unlike run.bat, which does ONE pass and exits, this stays open and asks the
REM portal for queued audits every poll_seconds (config.php, currently 10). That
REM is what makes "Fetch Profile" in the portal finish in seconds instead of
REM waiting for the next scheduled pass.
REM
REM To start it automatically at login, put a SHORTCUT to this file in:
REM     shell:startup
REM (press Win+R, type shell:startup, drop the shortcut in that folder)
REM
REM Leave the window open. Closing it stops the worker and audits will queue up
REM silently again. Ctrl+C stops it deliberately.
REM
REM If php is not on PATH, replace "php" below with the full path to php.exe,
REM for example C:\xampp\php\php.exe
REM ---------------------------------------------------------------------------

cd /d "%~dp0"

title IntentScore Instagram worker - leave this window open

:restart
echo.
echo [%DATE% %TIME%] starting worker in loop mode...
php worker.php --loop

REM Only reached if the worker exits: a crash, a dropped connection, or Ctrl+C.
REM Wait, then start again, so a transient network failure does not silently end
REM the day's audits.
echo.
echo [%DATE% %TIME%] worker stopped - restarting in 15 seconds. Close this window to stop.
timeout /t 15 >nul
goto restart
