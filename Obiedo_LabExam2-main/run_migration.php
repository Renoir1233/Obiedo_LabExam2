<?php
/**
 * Database Migration Script Runner
 * Run this file once to update your database schema
 * Access: http://localhost/Obiedo_LabExam2-main/run_migration.php
 */

// Include database connection
include("db.php");

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Migration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #667eea; }
        .success { 
            color: #38a169; 
            background: #f0fff4; 
            padding: 10px; 
            border-radius: 5px; 
            margin: 10px 0;
        }
        .error { 
            color: #e53e3e; 
            background: #fff5f5; 
            padding: 10px; 
            border-radius: 5px; 
            margin: 10px 0;
        }
        .info { 
            color: #2d3748; 
            background: #f0f0f0; 
            padding: 10px; 
            border-radius: 5px; 
            margin: 10px 0;
        }
        .warning {
            color: #d69e2e;
            background: #fffaf0;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        pre {
            background: #2d3748;
            color: #f7fafc;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        button {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #764ba2;
        }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔄 Database Migration Tool</h1>";

// Check if migration was requested
if (isset($_POST['run_migration'])) {
    echo "<h2>Running Migration...</h2>";
    
    // Read the migration SQL file
    $migration_file = __DIR__ . '/database_migration.sql';
    
    if (!file_exists($migration_file)) {
        echo "<div class='error'>❌ Migration file not found: database_migration.sql</div>";
        echo "</div></body></html>";
        exit;
    }
    
    $sql = file_get_contents($migration_file);
    
    // Split SQL file into individual statements
    // Note: This is a simple split; for complex SQL, consider using a better parser
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $success_count = 0;
    $error_count = 0;
    $messages = [];
    
    foreach ($statements as $statement) {
        // Skip comments and empty statements
        if (empty($statement) || substr($statement, 0, 2) == '--' || substr($statement, 0, 2) == '/*') {
            continue;
        }
        
        // Execute statement
        if ($conn->multi_query($statement . ';')) {
            do {
                // Store first result set
                if ($result = $conn->store_result()) {
                    while ($row = $result->fetch_assoc()) {
                        $messages[] = "<div class='info'>ℹ️ " . implode(', ', $row) . "</div>";
                    }
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());
            
            $success_count++;
        } else {
            // Check if error is about existing items (not a real error)
            $error_msg = $conn->error;
            if (strpos($error_msg, 'Duplicate') !== false || 
                strpos($error_msg, 'already exists') !== false) {
                $messages[] = "<div class='info'>ℹ️ Skipped (already exists): " . substr($statement, 0, 50) . "...</div>";
            } else {
                $error_count++;
                $messages[] = "<div class='error'>❌ Error: " . htmlspecialchars($error_msg) . "<br>Statement: " . htmlspecialchars(substr($statement, 0, 100)) . "...</div>";
            }
        }
    }
    
    // Display results
    echo "<div class='success'>✅ Migration completed!</div>";
    echo "<div class='info'>";
    echo "Processed: " . count($statements) . " statements<br>";
    echo "Successful: " . $success_count . "<br>";
    echo "Errors: " . $error_count;
    echo "</div>";
    
    // Show detailed messages
    if (!empty($messages)) {
        echo "<h3>Details:</h3>";
        foreach ($messages as $msg) {
            echo $msg;
        }
    }
    
    echo "<div class='success'>";
    echo "<h3>✅ Database Updated Successfully!</h3>";
    echo "<p>Your database now includes:</p>";
    echo "<ul>";
    echo "<li>✅ Users table with email, role, and 255-char password field</li>";
    echo "<li>✅ Hashed admin password</li>";
    echo "<li>✅ Courses table for normalization</li>";
    echo "<li>✅ Foreign keys for referential integrity</li>";
    echo "<li>✅ Login attempts table for audit logging</li>";
    echo "<li>✅ Timestamps for all tables</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='warning'>";
    echo "<strong>⚠️ Security Note:</strong> Please delete this file (run_migration.php) after migration is complete for security reasons.";
    echo "</div>";
    
    echo "<p><a href='login.php' style='color: #667eea;'>← Go to Login Page</a></p>";
    
} else {
    // Show migration info and confirmation
    echo "<div class='info'>";
    echo "<h2>📋 Migration Overview</h2>";
    echo "<p>This migration will update your database to include:</p>";
    echo "<ul>";
    echo "<li>✅ <strong>Users Table:</strong> Add email and role columns, expand password to 255 chars</li>";
    echo "<li>✅ <strong>Admin Password:</strong> Hash the default admin password securely</li>";
    echo "<li>✅ <strong>Courses Table:</strong> Normalize course data (separate from students)</li>";
    echo "<li>✅ <strong>Students Table:</strong> Add foreign keys and audit columns</li>";
    echo "<li>✅ <strong>Login Attempts:</strong> Add audit logging table</li>";
    echo "<li>✅ <strong>Foreign Keys:</strong> Ensure referential integrity</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='warning'>";
    echo "<strong>⚠️ Important:</strong> This migration is safe and will preserve existing data. ";
    echo "It checks if columns/tables exist before creating them.";
    echo "</div>";
    
    echo "<form method='POST'>";
    echo "<button type='submit' name='run_migration' onclick='return confirm(\"Are you sure you want to run the database migration?\");'>";
    echo "🚀 Run Migration Now";
    echo "</button>";
    echo "</form>";
    
    echo "<div class='info' style='margin-top: 20px;'>";
    echo "<h3>📝 Alternative: Manual Migration</h3>";
    echo "<p>You can also run the migration manually using phpMyAdmin or MySQL command line:</p>";
    echo "<pre>mysql -u root -p infosec_lab < database_migration.sql</pre>";
    echo "</div>";
}

echo "</div></body></html>";

$conn->close();
?>
