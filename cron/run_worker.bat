@echo off
REM =====================================================================
REM ScholarPlanner - Background Queue Worker Runner for Windows / Laragon
REM =====================================================================
REM Usage:
REM   run_worker.bat          (Runs continuously in a loop every 10 seconds)
REM   run_worker.bat --once   (Executes a single queue batch and exits)
REM =====================================================================

cd /d "%~dp0\.."

if "%1"=="--once" (
    echo [*] Running single queue worker batch...
    php cron/queue_worker.php --batch-size=50
    exit /b %ERRORLEVEL%
)

echo =====================================================================
echo  ScholarPlanner Notification Queue Worker (Local Development Mode)
echo  Polling and processing outbox queue every 10 seconds.
echo  Press Ctrl+C to terminate.
echo =====================================================================

:loop
php cron/queue_worker.php --batch-size=50
timeout /t 10 /nobreak >nul
goto loop
