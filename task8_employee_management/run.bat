@echo off
echo ======================================================
echo   Employee Management Module (Spring Core) - task8_employee_management
echo ======================================================
echo Compiling project...
call mvn compile
if %errorlevel% neq 0 (
    echo.
    echo [ERROR] Compilation failed. Please ensure Maven and Java 17+ are installed.
    pause
    exit /b %errorlevel%
)

echo.
echo Running Application...
echo ======================================================
call mvn exec:java
echo ======================================================
echo.
echo Application finished.
pause
