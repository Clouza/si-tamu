<?php
require_once 'database.php';

try {
    echo "=== Database Migration ===\n\n";

    // check current table structure
    $check = $pdo->query("PRAGMA table_info(guest_entries)");
    $columns = $check->fetchAll(PDO::FETCH_ASSOC);

    $phone_exists = false;
    $ktp_nullable = false;

    foreach ($columns as $column) {
        if ($column['name'] === 'phone_number') {
            $phone_exists = true;
        }
        if ($column['name'] === 'ktp_number' && $column['notnull'] == 0) {
            $ktp_nullable = true;
        }
    }

    // check if migration is needed
    if ($phone_exists && $ktp_nullable) {
        echo "✓ Database sudah up-to-date\n";
        echo "  - phone_number column exists\n";
        echo "  - ktp_number is nullable\n";
        exit(0);
    }

    // migration needed
    echo "Migration diperlukan:\n";
    if (!$phone_exists) echo "  - Tambah kolom phone_number\n";
    if (!$ktp_nullable) echo "  - Ubah ktp_number menjadi nullable\n";
    echo "\n";

    // backup data
    echo "Step 1: Backup data...\n";
    $backup = $pdo->query("SELECT * FROM guest_entries")->fetchAll(PDO::FETCH_ASSOC);
    echo "✓ Backup " . count($backup) . " rows\n\n";

    // recreate table
    echo "Step 2: Recreate table...\n";
    $pdo->beginTransaction();

    $pdo->exec("
        CREATE TABLE guest_entries_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            visitor_name VARCHAR(255) NOT NULL,
            ktp_number VARCHAR(16),
            phone_number VARCHAR(20),
            institution VARCHAR(255) NOT NULL,
            job VARCHAR(255) NOT NULL,
            required_info TEXT NOT NULL,
            legal_product_purpose TEXT NOT NULL,
            visit_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Table baru dibuat\n\n";

    // restore data
    echo "Step 3: Restore data...\n";
    $stmt = $pdo->prepare("
        INSERT INTO guest_entries_new
        (id, visitor_name, ktp_number, phone_number, institution, job, required_info, legal_product_purpose, visit_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($backup as $row) {
        $stmt->execute([
            $row['id'],
            $row['visitor_name'],
            $row['ktp_number'],
            $row['phone_number'] ?? null,
            $row['institution'],
            $row['job'],
            $row['required_info'],
            $row['legal_product_purpose'],
            $row['visit_date']
        ]);
    }
    echo "✓ Restore " . count($backup) . " rows\n\n";

    // replace table
    echo "Step 4: Replace table...\n";
    $pdo->exec("DROP TABLE guest_entries");
    $pdo->exec("ALTER TABLE guest_entries_new RENAME TO guest_entries");
    echo "✓ Table replaced\n\n";

    $pdo->commit();

    echo "=== Migration Success ===\n";
    echo "✓ phone_number column added\n";
    echo "✓ ktp_number is now nullable\n";
    echo "✓ All data (" . count($backup) . " rows) preserved\n";

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
        echo "\n✗ Migration failed, rolled back\n";
    }
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
