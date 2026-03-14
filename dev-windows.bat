@echo off
REM ============================================================
REM Scruffs-N-Chyrrs Development Server Launcher (Windows)
REM ============================================================
REM This script starts the Dev Server for Laravel and Vite.
REM
REM Usage: Run this file from the project root directory
REM     dev-windows.bat
REM
REM The script will open all the necessary servers to run the
REM application.
REM
REM To stop the servers, close each terminal window or press CTRL+C.
REM ============================================================

echo ============================================================
echo Scruffs-N-Chyrrs Development Environment
echo ============================================================
echo.
echo Starting development servers...
echo.
echo Access the application at: http://127.0.0.1:8000
echo Vite HMR is available on the default port.
echo.
echo To stop, close the terminal windows or press CTRL+C in each.
echo ============================================================
echo.

REM Start Laravel server, queue listener, and Vite in a new window via concurrently.
start "Scruffs-N-Chyrrs Backend" cmd /k "composer run dev && pause"

echo.
echo Both servers are starting...
echo If you see errors, check that PHP and Node.js are installed correctly.
