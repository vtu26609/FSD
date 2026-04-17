@echo off
echo Building Student CRUD Application...
call mvn clean install -DskipTests
if %ERRORLEVEL% NEQ 0 (
    echo Building failed!
    pause
    exit /b %ERRORLEVEL%
)
echo Starting Application...
call mvn spring-boot:run
pause
