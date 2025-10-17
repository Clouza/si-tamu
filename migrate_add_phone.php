<?php
require_once 'database.php';

try {
    // check if phone_number column already exists
    $check = $pdo->query("PRAGMA table_info(guest_entries)");
    $columns = $check->fetchAll(PDO::FETCH_ASSOC);

    $phone_exists = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'phone_number') {
            $phone_exists = true;
            break;
        }
    }

    if ($phone_exists) {
        echo "Kolom phone_number sudah ada. Tidak perlu migrasi.\n";
    } else {
        // add phone_number column
        $pdo->exec("ALTER TABLE guest_entries ADD COLUMN phone_number VARCHAR(20) DEFAULT NULL");
        echo "Berhasil menambahkan kolom phone_number ke tabel guest_entries.\n";
    }

    // check if ktp_number is nullable
    $ktp_nullable = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'ktp_number' && $column['notnull'] == 0) {
            $ktp_nullable = true;
            break;
        }
    }

    if (!$ktp_nullable) {
        echo "\nPerhatian: Kolom ktp_number masih NOT NULL.\n";
        echo "Untuk membuat ktp_number opsional, perlu recreate table.\n";
        echo "Apakah ingin melanjutkan? Ketik 'yes' untuk lanjut: ";

        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);

        if ($line === 'yes') {
            // backup and recreate table to make ktp_number nullable
            $pdo->beginTransaction();

            // create new table with ktp_number nullable
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

            // copy all data from old table to new table
            $pdo->exec("
                INSERT INTO guest_entries_new (id, visitor_name, ktp_number, phone_number, institution, job, required_info, legal_product_purpose, visit_date)
                SELECT id, visitor_name, ktp_number, phone_number, institution, job, required_info, legal_product_purpose, visit_date
                FROM guest_entries
            ");

            // drop old table
            $pdo->exec("DROP TABLE guest_entries");

            // rename new table
            $pdo->exec("ALTER TABLE guest_entries_new RENAME TO guest_entries");

            $pdo->commit();

            echo "Berhasil mengubah ktp_number menjadi opsional.\n";
            echo "Semua data tetap aman.\n";
        } else {
            echo "Migrasi dibatalkan.\n";
        }
    } else {
        echo "Kolom ktp_number sudah nullable (opsional).\n";
    }

    echo "\nMigrasi selesai!\n";

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
