@echo off
REM Database Setup Script for Hotel Senang Hati (Windows)
REM This script will create and setup the MySQL database

echo === Hotel Senang Hati Database Setup ===
echo.

REM Variables
set DB_NAME=hotel_senang_hati
set DB_USER=root
set DB_PASS=
set SCHEMA_FILE=database\schema.sql

REM Check if schema file exists
if not exist "%SCHEMA_FILE%" (
    echo Error: Schema file not found at %SCHEMA_FILE%
    pause
    exit /b 1
)

echo Setting up database: %DB_NAME%
echo.

REM Create database and import schema
echo Creating database and importing schema...
mysql -h localhost -u %DB_USER% -p%DB_PASS% < "%SCHEMA_FILE%"

if %errorlevel% == 0 (
    echo ✓ Database setup completed successfully!
    echo.
    echo Database Details:
    echo - Name: %DB_NAME%
    echo - Host: localhost
    echo - User: %DB_USER%
    echo - Charset: utf8mb4
    echo.
    echo Tables created:
    mysql -h localhost -u %DB_USER% -p%DB_PASS% -e "USE %DB_NAME%; SHOW TABLES;"
    echo.
    echo You can now use the Hotel Senang Hati application!
) else (
    echo ✗ Database setup failed!
    echo Please check your MySQL configuration and try again.
    echo.
    echo Make sure:
    echo 1. XAMPP/WAMP is running
    echo 2. MySQL service is started
    echo 3. MySQL is accessible from command line
)

echo.
pause
