<?php
/**
 * Ojasva Events - SQLite Database Configuration & Initialization
 * Uses PDO SQLite
 */

$dbPath = __DIR__ . '/database.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // 1. Create 'login' table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS login (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT DEFAULT 'admin',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // 2. Create 'leads' table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS leads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT NOT NULL,
            email TEXT NOT NULL,
            event_type TEXT NOT NULL,
            event_date TEXT,
            venue TEXT,
            guests TEXT,
            package TEXT,
            budget TEXT,
            details TEXT,
            status TEXT DEFAULT 'New',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // 3. Seed default admin credentials: ID = admin@123, Password = admin
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login WHERE username = :username");
    $stmt->execute([':username' => 'admin@123']);
    $userExists = $stmt->fetchColumn();

    if (!$userExists) {
        $hashedPassword = password_hash('admin', PASSWORD_DEFAULT);
        $insertStmt = $pdo->prepare("
            INSERT INTO login (username, password, role) 
            VALUES (:username, :password, 'admin')
        ");
        $insertStmt->execute([
            ':username' => 'admin@123',
            ':password' => $hashedPassword
        ]);
    }

    // 4. Seed initial sample lead if leads table is empty
    $leadCount = $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
    if ($leadCount == 0) {
        $insertLead = $pdo->prepare("
            INSERT INTO leads (name, phone, email, event_type, event_date, venue, guests, package, budget, details, status)
            VALUES (:name, :phone, :email, :event_type, :event_date, :venue, :guests, :package, :budget, :details, :status)
        ");
        $insertLead->execute([
            ':name'       => 'Rajesh & Priya Malhotra',
            ':phone'      => '+91 98765 43210',
            ':email'      => 'rajesh.malhotra@gmail.com',
            ':event_type' => 'Wedding',
            ':event_date' => '2026-12-15',
            ':venue'      => 'Patna / Royal Heritage',
            ':guests'     => '350',
            ':package'    => 'Platinum',
            ':budget'     => '500000',
            ':details'    => 'Planning a 3-day destination wedding with Sangeet night and floral mandap.',
            ':status'     => 'New'
        ]);
    }

} catch (PDOException $e) {
    die("Database Connection Error: " . htmlspecialchars($e->getMessage()));
}

