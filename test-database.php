<?php
/**
 * Database Connection Test
 * Hotel Senang Hati - Test database connectivity
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once 'api/config/database.php';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Test - Hotel Senang Hati</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f8f9fa;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            color: #d4af37;
            margin-bottom: 30px;
        }
        .test-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #ddd;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
            color: #0c5460;
        }
        .warning {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        .code {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 3px;
            font-family: monospace;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏨 Hotel Senang Hati</h1>
            <h2>Database Connection Test</h2>
        </div>

        <?php
        // Test 1: PHP Version
        echo '<div class="test-item info">';
        echo '<h3>📋 PHP Environment</h3>';
        echo '<strong>PHP Version:</strong> ' . PHP_VERSION . '<br>';
        echo '<strong>Server:</strong> ' . $_SERVER['SERVER_SOFTWARE'] . '<br>';
        echo '<strong>Document Root:</strong> ' . $_SERVER['DOCUMENT_ROOT'];
        echo '</div>';

        // Test 2: Required Extensions
        echo '<div class="test-item">';
        echo '<h3>🔧 Required Extensions</h3>';
        
        $required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
        $extensions_ok = true;
        
        echo '<table>';
        foreach ($required_extensions as $ext) {
            $loaded = extension_loaded($ext);
            if (!$loaded) $extensions_ok = false;
            echo '<tr>';
            echo '<td>' . $ext . '</td>';
            echo '<td class="' . ($loaded ? 'status-ok' : 'status-error') . '">';
            echo $loaded ? '✓ Loaded' : '✗ Missing';
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        if ($extensions_ok) {
            echo '<div class="success">✓ All required extensions are loaded</div>';
        } else {
            echo '<div class="error">✗ Some required extensions are missing</div>';
        }
        echo '</div>';

        // Test 3: Database Connection
        echo '<div class="test-item">';
        echo '<h3>🗄️ Database Connection</h3>';
        
        try {
            $db = Database::getInstance();
            echo '<div class="success">✓ Database connection successful</div>';
            
            // Test connection
            if ($db->testConnection()) {
                echo '<div class="success">✓ Database ping successful</div>';
                
                // Get database info
                $info = $db->fetch("SELECT DATABASE() as db_name, NOW() as current_time, VERSION() as mysql_version");
                
                echo '<table>';
                echo '<tr><td><strong>Database Name:</strong></td><td>' . $info['db_name'] . '</td></tr>';
                echo '<tr><td><strong>MySQL Version:</strong></td><td>' . $info['mysql_version'] . '</td></tr>';
                echo '<tr><td><strong>Server Time:</strong></td><td>' . $info['current_time'] . '</td></tr>';
                echo '</table>';
                
            } else {
                echo '<div class="error">✗ Database ping failed</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="error">✗ Database connection failed: ' . $e->getMessage() . '</div>';
            echo '<div class="warning">';
            echo '<strong>Troubleshooting:</strong><br>';
            echo '1. Make sure MySQL/MariaDB is running<br>';
            echo '2. Check database credentials in api/config/database.php<br>';
            echo '3. Verify database "hotel_senang_hati" exists<br>';
            echo '4. Run setup-database.bat to create the database';
            echo '</div>';
        }
        echo '</div>';

        // Test 4: Database Tables
        if (isset($db)) {
            echo '<div class="test-item">';
            echo '<h3>📊 Database Tables</h3>';
            
            try {
                $tables = $db->fetchAll("SHOW TABLES");
                
                if (count($tables) > 0) {
                    echo '<div class="success">✓ Found ' . count($tables) . ' tables</div>';
                    echo '<table>';
                    echo '<tr><th>Table Name</th><th>Status</th></tr>';
                    
                    $expected_tables = ['rooms', 'guests', 'reservations', 'payments', 'staff', 'room_maintenance', 'guest_stays', 'hotel_settings'];
                    
                    foreach ($tables as $table) {
                        $table_name = array_values($table)[0];
                        $is_expected = in_array($table_name, $expected_tables);
                        
                        echo '<tr>';
                        echo '<td>' . $table_name . '</td>';
                        echo '<td class="' . ($is_expected ? 'status-ok' : 'status-error') . '">';
                        echo $is_expected ? '✓ Expected' : '? Unknown';
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                    
                    // Check if all expected tables exist
                    $existing_tables = array_map(function($table) {
                        return array_values($table)[0];
                    }, $tables);
                    
                    $missing_tables = array_diff($expected_tables, $existing_tables);
                    
                    if (empty($missing_tables)) {
                        echo '<div class="success">✓ All expected tables are present</div>';
                    } else {
                        echo '<div class="warning">⚠ Missing tables: ' . implode(', ', $missing_tables) . '</div>';
                        echo '<div class="info">Run setup-database.bat to create missing tables</div>';
                    }
                    
                } else {
                    echo '<div class="warning">⚠ No tables found in database</div>';
                    echo '<div class="info">Run setup-database.bat to create tables</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="error">✗ Could not check tables: ' . $e->getMessage() . '</div>';
            }
            echo '</div>';

            // Test 5: Sample Data
            echo '<div class="test-item">';
            echo '<h3>🏠 Sample Data</h3>';
            
            try {
                $room_count = $db->fetch("SELECT COUNT(*) as count FROM rooms");
                $guest_count = $db->fetch("SELECT COUNT(*) as count FROM guests");
                $reservation_count = $db->fetch("SELECT COUNT(*) as count FROM reservations");
                
                echo '<table>';
                echo '<tr><td><strong>Rooms:</strong></td><td>' . $room_count['count'] . ' records</td></tr>';
                echo '<tr><td><strong>Guests:</strong></td><td>' . $guest_count['count'] . ' records</td></tr>';
                echo '<tr><td><strong>Reservations:</strong></td><td>' . $reservation_count['count'] . ' records</td></tr>';
                echo '</table>';
                
                if ($room_count['count'] > 0) {
                    echo '<div class="success">✓ Sample room data available</div>';
                } else {
                    echo '<div class="warning">⚠ No room data found</div>';
                    echo '<div class="info">Sample data should be inserted automatically from schema.sql</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="error">✗ Could not check sample data: ' . $e->getMessage() . '</div>';
            }
            echo '</div>';
        }

        // Test 6: File Permissions
        echo '<div class="test-item">';
        echo '<h3>📁 File Permissions</h3>';
        
        $writable_dirs = ['logs', 'uploads', 'cache'];
        $permissions_ok = true;
        
        echo '<table>';
        foreach ($writable_dirs as $dir) {
            $exists = is_dir($dir);
            $writable = $exists ? is_writable($dir) : false;
            
            if (!$exists || !$writable) $permissions_ok = false;
            
            echo '<tr>';
            echo '<td>' . $dir . '/</td>';
            echo '<td class="' . (($exists && $writable) ? 'status-ok' : 'status-error') . '">';
            if ($exists) {
                echo $writable ? '✓ Writable' : '✗ Not writable';
            } else {
                echo '✗ Not found';
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        if (!$permissions_ok) {
            echo '<div class="warning">⚠ Some directories need to be created or made writable</div>';
        }
        echo '</div>';

        // Summary
        echo '<div class="test-item info">';
        echo '<h3>📝 Summary</h3>';
        echo '<p>If all tests pass, your Hotel Senang Hati application is ready to use!</p>';
        echo '<p><strong>Next Steps:</strong></p>';
        echo '<ul>';
        echo '<li>Access the hotel website: <a href="index.html">index.html</a></li>';
        echo '<li>Test room booking: <a href="reservation-form.html">reservation-form.html</a></li>';
        echo '<li>Check reservation history: <a href="reservation.html">reservation.html</a></li>';
        echo '<li>Test room details: <a href="room-detail.html">room-detail.html</a></li>';
        echo '</ul>';
        echo '</div>';
        ?>
    </div>
</body>
</html>
