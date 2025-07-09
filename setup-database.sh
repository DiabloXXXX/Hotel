#!/bin/bash
# Database Setup Script for Hotel Senang Hati
# This script will create and setup the MySQL database

echo "=== Hotel Senang Hati Database Setup ==="
echo ""

# Check if MySQL is running
echo "Checking MySQL service..."
if ! pgrep -x "mysqld" > /dev/null; then
    echo "MySQL is not running. Please start MySQL service first."
    echo "Windows: Start XAMPP/WAMP"
    echo "Linux: sudo systemctl start mysql"
    echo "macOS: brew services start mysql"
    exit 1
fi

echo "MySQL service is running ✓"
echo ""

# Variables
DB_NAME="hotel_senang_hati"
DB_USER="root"
DB_PASS=""
SCHEMA_FILE="database/schema.sql"

# Check if schema file exists
if [ ! -f "$SCHEMA_FILE" ]; then
    echo "Error: Schema file not found at $SCHEMA_FILE"
    exit 1
fi

echo "Setting up database: $DB_NAME"
echo ""

# Create database and import schema
echo "Creating database and importing schema..."
mysql -h localhost -u "$DB_USER" -p"$DB_PASS" < "$SCHEMA_FILE"

if [ $? -eq 0 ]; then
    echo "✓ Database setup completed successfully!"
    echo ""
    echo "Database Details:"
    echo "- Name: $DB_NAME"
    echo "- Host: localhost"
    echo "- User: $DB_USER"
    echo "- Charset: utf8mb4"
    echo ""
    echo "Tables created:"
    mysql -h localhost -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; SHOW TABLES;"
    echo ""
    echo "You can now use the Hotel Senang Hati application!"
else
    echo "✗ Database setup failed!"
    echo "Please check your MySQL configuration and try again."
    exit 1
fi
