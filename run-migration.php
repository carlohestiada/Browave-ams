<?php
/**
 * Phase 4 Database Migration Runner
 * Adds trip_leg_id support to transportation_requests table
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = (new Database())->connect();
    
    echo "Starting Phase 4 migration...\n";
    
    // Read and execute the migration file
    $migrationSQL = file_get_contents(__DIR__ . '/migrations/001_add_trip_leg_id_to_transportation.sql');
    
    if (!$migrationSQL) {
        throw new Exception('Migration file not found or cannot be read');
    }
    
    // Split by semicolons and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $migrationSQL)), function($stmt) {
        return !empty($stmt) && !preg_match('/^--/', trim($stmt));
    });
    
    foreach ($statements as $statement) {
        // Skip comment lines
        if (preg_match('/^\s*--/', $statement)) {
            continue;
        }
        
        if (!empty(trim($statement))) {
            echo "Executing: " . substr($statement, 0, 80) . "...\n";
            $db->exec($statement);
        }
    }
    
    echo "✅ Phase 4 migration completed successfully!\n";
    
    // Verify the column was added
    $checkStmt = $db->prepare("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_name = 'transportation_requests' 
        AND column_name = 'trip_leg_id'
    ");
    $checkStmt->execute();
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "✅ Verified: trip_leg_id column exists\n";
    } else {
        echo "⚠️  Warning: trip_leg_id column not found after migration\n";
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
