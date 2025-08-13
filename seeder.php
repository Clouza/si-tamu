<?php
require_once 'database.php';

// User data
$users = [
    [
        'nip' => 'admin123',
        'password' => 'admin123',
        'role' => 'admin'
    ],
    [
        'nip' => 'user123',
        'password' => 'user123',
        'role' => 'user'
    ]
];

try {
    foreach ($users as $user) {
        // Check if user already exists
        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE nip = ?");
        $check->execute([$user['nip']]);
        
        if ($check->fetchColumn() == 0) {
            // Insert user
            $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (nip, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$user['nip'], $hashed_password, $user['role']]);
            echo ucfirst($user['role']) . " user created successfully!\n";
            echo "NIP: " . $user['nip'] . "\n";
            echo "Password: " . $user['password'] . "\n\n";
        } else {
            echo ucfirst($user['role']) . " user already exists!\n";
        }
    }
    
    // Sample guest entries for testing
    $sample_entries = [
        [
            'visitor_name' => 'John Doe',
            'ktp_number' => '1234567890123456',
            'institution' => 'PT. Example Company',
            'job' => 'Software Developer',
            'required_info' => 'Informasi tentang peraturan daerah bidang teknologi',
            'legal_product_purpose' => 'Untuk pengembangan aplikasi sesuai regulasi daerah'
        ],
        [
            'visitor_name' => 'Jane Smith',
            'ktp_number' => '6543210987654321',
            'institution' => 'Universitas Bali',
            'job' => 'Peneliti',
            'required_info' => 'Data statistik penduduk untuk penelitian',
            'legal_product_purpose' => 'Penelitian akademik tentang demografi Bali'
        ]
    ];
    
    $entry_stmt = $pdo->prepare("
        INSERT INTO guest_entries (visitor_name, ktp_number, institution, job, required_info, legal_product_purpose) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($sample_entries as $entry) {
        $entry_stmt->execute([
            $entry['visitor_name'],
            $entry['ktp_number'], 
            $entry['institution'],
            $entry['job'],
            $entry['required_info'],
            $entry['legal_product_purpose']
        ]);
    }
    
    echo "Sample guest entries created successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>