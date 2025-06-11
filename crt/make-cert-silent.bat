@echo off
setlocal enabledelayedexpansion

:: Get domain from first argument
if "%~1"=="" (
    echo [ERROR] No domain provided. > cert.log
    exit /b 1
) else (
    set "domain=%~1"
)

:: Paths
set "SCRIPT_DIR=%~dp0"
set "OPENSSL_CONF=%SCRIPT_DIR%..\conf\openssl.cnf"
set "TEMPLATE=%SCRIPT_DIR%cert-template.conf"
set "TEMP_CONF=%SCRIPT_DIR%cert.conf"
set "OUT_DIR=%SCRIPT_DIR%%domain%"
set "OPENSSL=%SCRIPT_DIR%..\bin\openssl.exe"
set "LOG=%SCRIPT_DIR%cert.log"
set "SUCCESS=[OK] Certificate and key created in: %OUT_DIR%"
set "FAILURE=[FAIL] Certificate generation failed."

:: Init log
echo Generating cert for %domain% > "%LOG%"

if not exist "%OPENSSL%" (
    echo [ERROR] OpenSSL not found at %OPENSSL% >> "%LOG%"
    exit /b 1
)

if not exist "%TEMPLATE%" (
    echo [ERROR] Template config not found: %TEMPLATE% >> "%LOG%"
    exit /b 1
)

if not exist "%OUT_DIR%" mkdir "%OUT_DIR%"

if exist "%TEMP_CONF%" del /f /q "%TEMP_CONF%"

for /f "tokens=1,* delims=¶" %%A in ('"findstr /n ^^ %TEMPLATE%"') do (
    set line=%%A
    for /f "tokens=1,* delims=:" %%a in ("!line!") do (
        set "content=%%b"
        if "!content!"=="" (
            echo.>> "%TEMP_CONF%"
        ) else (
            set "modified=!content:{{DOMAIN}}=%domain%!"
            echo !modified!>> "%TEMP_CONF%"
        )
    )
)

:: Run OpenSSL and check result
"%OPENSSL%" req -config "%TEMP_CONF%" -new -sha256 -newkey rsa:2048 -nodes -keyout "%OUT_DIR%\server.key" -x509 -days 365 -out "%OUT_DIR%\server.crt" -subj "/emailAddress=youremail@example.com/C=US/ST=MO/L=Union/O=Company/CN=%domain%" >> "%LOG%" 2>&1
set "EXIT_CODE=%ERRORLEVEL%"

if exist "%TEMP_CONF%" del /f /q "%TEMP_CONF%"

if %EXIT_CODE% NEQ 0 (
    echo %FAILURE%
    echo %FAILURE% >> "%LOG%"
    exit /b %EXIT_CODE%
)

echo %SUCCESS%
echo %SUCCESS% >> "%LOG%"
