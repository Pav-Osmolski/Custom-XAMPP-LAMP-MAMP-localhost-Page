@echo off
setlocal enabledelayedexpansion
title Make Dev Certificate (interactive)

:: -------- Prompt for inputs --------
set "domain="
set /p domain=Enter primary domain [default: localhost]: 
if "%domain%"=="" set "domain=localhost"

set "extra="
set /p extra=Extra SANs (comma or space separated, optional): 

set "days="
set /p days=Validity in days [default: 365]: 
if "%days%"=="" set "days=365"

:: -------- Paths --------
set "SCRIPT_DIR=%~dp0"
set "OPENSSL=%SCRIPT_DIR%..\bin\openssl.exe"
set "OPENSSL_CONF=%SCRIPT_DIR%..\conf\openssl.cnf"
set "TEMPLATE=%SCRIPT_DIR%cert-template.conf"
set "TEMP_CONF=%SCRIPT_DIR%cert.conf"
set "OUT_DIR=%SCRIPT_DIR%%domain%"
set "LOG=%SCRIPT_DIR%cert.log"

:: -------- Prepare --------
echo Generating cert for %domain% > "%LOG%"
if not exist "%OPENSSL%" (
    echo [ERROR] OpenSSL not found at %OPENSSL% | tee -a "%LOG%"
    exit /b 1
)
if not exist "%TEMPLATE%" (
    echo [ERROR] Template config not found: %TEMPLATE% >> "%LOG%"
    exit /b 1
)
if not exist "%OUT_DIR%" mkdir "%OUT_DIR%"
if exist "%TEMP_CONF%" del /f /q "%TEMP_CONF%"

:: Replace {{DOMAIN}}
for /f "usebackq delims=" %%L in ("%TEMPLATE%") do (
  set "line=%%L"
  set "line=!line:{{DOMAIN}}=%domain%!"
  >> "%TEMP_CONF%" echo(!line!
)

:: -------- Build SAN list --------
set "SAN_LIST=DNS:%domain%,DNS:localhost,IP:127.0.0.1"
set "extra=%extra:,= %"
for %%T in (%extra%) do (
  set "tok=%%~T"
  if not "!tok!"=="" (
    set "SAN_LIST=!SAN_LIST!,DNS:!tok!"
  )
)

:: -------- Subject --------
set "SUBJECT=/emailAddress=youremail@example.com/C=US/ST=MO/L=Union/O=Company/CN=%domain%"

:: -------- Run OpenSSL --------
"%OPENSSL%" req ^
  -config "%TEMP_CONF%" ^
  -new ^
  -sha256 ^
  -newkey rsa:2048 ^
  -nodes ^
  -keyout "%OUT_DIR%\server.key" ^
  -x509 ^
  -days %days% ^
  -out "%OUT_DIR%\server.crt" ^
  -subj "%SUBJECT%" ^
  -addext "subjectAltName=!SAN_LIST!" ^
  >> "%LOG%" 2>&1

set "EXIT_CODE=%ERRORLEVEL%"
if exist "%TEMP_CONF%" del /f /q "%TEMP_CONF%"

if %EXIT_CODE% NEQ 0 (
    echo [FAIL] OpenSSL failed with exit code %EXIT_CODE%
    echo [FAIL] >> "%LOG%"
    exit /b %EXIT_CODE%
)

echo [OK] Certificate and key created in: %OUT_DIR%
echo [OK] >> "%LOG%"
endlocal
