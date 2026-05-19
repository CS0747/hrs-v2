<?php
/**
 * Schedule Management Enhancement - Migration Runner
 * Run this script to apply database migrations
 */

// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'geamh_hris';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}

echo "Starting migration...\n\n";

// 1. Create shift_legends table
echo "Creating shift_legends table...\n";
$sql = "CREATE TABLE IF NOT EXISTS shift_legends (
  id INT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(10) NOT NULL,
  department VARCHAR(100) DEFAULT NULL COMMENT 'NULL for standard legends',
  time_range VARCHAR(50) NOT NULL,
  color_primary VARCHAR(7) NOT NULL,
  color_secondary VARCHAR(7) DEFAULT NULL,
  display_order INT DEFAULT 0,
  active BOOLEAN DEFAULT TRUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_code_dept (code, department),
  INDEX idx_department (department),
  INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "✓ shift_legends table created\n";
} else {
    echo "⚠ " . $conn->error . "\n";
}

// 2. Add columns to schedules table
echo "\nEnhancing schedules table...\n";

$columns = [
    "schedule_date DATE DEFAULT NULL",
    "start_time TIME DEFAULT NULL",
    "end_time TIME DEFAULT NULL",
    "shift_code VARCHAR(10) DEFAULT NULL",
    "shift_name VARCHAR(50) DEFAULT NULL",
    "status ENUM('Submitted', 'Pending', 'Missing') DEFAULT 'Pending'",
    "submitted_date DATETIME DEFAULT NULL",
    "last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    "created_by INT DEFAULT NULL",
    "remarks TEXT DEFAULT NULL"
];

foreach ($columns as $column) {
    $columnName = explode(' ', $column)[0];
    $sql = "ALTER TABLE schedules ADD COLUMN IF NOT EXISTS $column";
    if ($conn->query($sql)) {
        echo "✓ Added column: $columnName\n";
    } else {
        if (strpos($conn->error, 'Duplicate column') !== false) {
            echo "⚠ Column already exists: $columnName\n";
        } else {
            echo "✗ Error adding $columnName: " . $conn->error . "\n";
        }
    }
}

// 3. Add indexes
echo "\nAdding indexes...\n";

$indexes = [
    "idx_schedule_date" => "schedule_date",
    "idx_shift_code" => "shift_code",
    "idx_status" => "status"
];

foreach ($indexes as $indexName => $column) {
    $sql = "ALTER TABLE schedules ADD INDEX $indexName ($column)";
    if ($conn->query($sql)) {
        echo "✓ Added index: $indexName\n";
    } else {
        if (strpos($conn->error, 'Duplicate key') !== false) {
            echo "⚠ Index already exists: $indexName\n";
        } else {
            echo "✗ Error adding index $indexName: " . $conn->error . "\n";
        }
    }
}

// 4. Create schedule_transmittals table
echo "\nCreating schedule_transmittals table...\n";
$sql = "CREATE TABLE IF NOT EXISTS schedule_transmittals (
  id INT PRIMARY KEY AUTO_INCREMENT,
  department VARCHAR(100) NOT NULL,
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,
  page_number INT DEFAULT 1,
  staff_count INT DEFAULT 0,
  submitted_count INT DEFAULT 0,
  date_submitted DATE DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  generated_by INT DEFAULT NULL,
  generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_department (department),
  INDEX idx_period (period_start, period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "✓ schedule_transmittals table created\n";
} else {
    echo "⚠ " . $conn->error . "\n";
}

// 5. Populate shift_legends
echo "\nPopulating shift_legends...\n";

$legends = [
    // Standard legends
    ['85', NULL, '8:00 AM - 5:00 PM', '#000000', NULL, 1],
    ['OFF', NULL, 'Off Duty', '#F44336', NULL, 2],
    // Nursing legends
    ['62', 'Nursing', '6:00 AM - 2:00 PM', '#2196F3', NULL, 1],
    ['210', 'Nursing', '2:00 PM - 10:00 PM', '#4CAF50', NULL, 2],
    ['106', 'Nursing', '10:00 PM - 6:00 AM', '#F44336', NULL, 3],
    ['610', 'Nursing', '6:00 AM - 10:00 PM', '#2196F3', '#4CAF50', 4],
    ['26', 'Nursing', '2:00 PM - 6:00 AM', '#4CAF50', '#F44336', 5],
    ['85', 'Nursing', '8:00 AM - 5:00 PM', '#000000', NULL, 6],
    ['OFF', 'Nursing', 'Off Duty', '#F44336', NULL, 7]
];

$insertedCount = 0;
foreach ($legends as $legend) {
    $code = $legend[0];
    $dept = $legend[1] === NULL ? 'NULL' : "'" . $legend[1] . "'";
    $timeRange = $legend[2];
    $colorPrimary = $legend[3];
    $colorSecondary = $legend[4] === NULL ? 'NULL' : "'" . $legend[4] . "'";
    $displayOrder = $legend[5];
    
    $sql = "INSERT INTO shift_legends (code, department, time_range, color_primary, color_secondary, display_order, active)
            VALUES ('$code', $dept, '$timeRange', '$colorPrimary', $colorSecondary, $displayOrder, TRUE)
            ON DUPLICATE KEY UPDATE
              time_range = VALUES(time_range),
              color_primary = VALUES(color_primary),
              color_secondary = VALUES(color_secondary),
              display_order = VALUES(display_order),
              active = VALUES(active)";
    
    if ($conn->query($sql)) {
        $insertedCount++;
    }
}

echo "✓ Inserted/updated $insertedCount shift legends\n";

// Verification
echo "\n" . str_repeat('=', 60) . "\n";
echo "Verification:\n";

$result = $conn->query("SELECT COUNT(*) as count FROM shift_legends");
if ($result) {
    $row = $result->fetch_assoc();
    echo "✓ shift_legends: {$row['count']} legends\n";
}

$result = $conn->query("SHOW COLUMNS FROM schedules LIKE 'schedule_date'");
if ($result && $result->num_rows > 0) {
    echo "✓ schedules: Enhanced with new columns\n";
}

$result = $conn->query("SELECT COUNT(*) as count FROM schedule_transmittals");
if ($result) {
    $row = $result->fetch_assoc();
    echo "✓ schedule_transmittals: {$row['count']} transmittals\n";
}

echo str_repeat('=', 60) . "\n";
echo "\n✓ Migration completed successfully!\n";

$conn->close();
