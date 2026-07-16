<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json; charset=utf-8');

$memberId = $_SESSION['member_id'] ?? null;
if (!$memberId) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$productId = isset($_POST['product_id']) ? sanitize($_POST['product_id']) : '';
if (empty($productId)) {
    echo json_encode(['success' => false, 'error' => 'Invalid product']);
    exit;
}

try {
    // Ensure favorites table exists (safe to run repeatedly)
    $pdo->exec("CREATE TABLE IF NOT EXISTS favorite (
        favorite_id VARCHAR(32) PRIMARY KEY,
        member_id VARCHAR(32) NOT NULL,
        product_id VARCHAR(32) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY(member_id, product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Check if already favorited
    $stmt = $pdo->prepare("SELECT favorite_id FROM favorite WHERE member_id = ? AND product_id = ? LIMIT 1");
    $stmt->execute([$memberId, $productId]);
    $row = $stmt->fetch();
    if ($row) {
        // Remove favorite
        $stmt = $pdo->prepare("DELETE FROM favorite WHERE favorite_id = ?");
        $stmt->execute([$row['favorite_id']]);
        // Get product name and updated count
        $stmt2 = $pdo->prepare("SELECT name FROM product WHERE product_id = ? LIMIT 1");
        $stmt2->execute([$productId]);
        $productName = ($stmt2->fetch()['name']) ?? '';
        $stmt3 = $pdo->prepare("SELECT COUNT(*) as cnt FROM favorite WHERE member_id = ?");
        $stmt3->execute([$memberId]);
        $favCount = (int)($stmt3->fetch()['cnt'] ?? 0);
        echo json_encode(['success' => true, 'action' => 'removed', 'product_name' => $productName, 'favorites_count' => $favCount]);
        exit;
    } else {
        // Add favorite
        $favId = generateSequentialId($pdo, 'F', 'favorite', 'favorite_id');
        $stmt = $pdo->prepare("INSERT INTO favorite (favorite_id, member_id, product_id) VALUES (?, ?, ?)");
        $stmt->execute([$favId, $memberId, $productId]);
        // Get product name and updated count
        $stmt2 = $pdo->prepare("SELECT name FROM product WHERE product_id = ? LIMIT 1");
        $stmt2->execute([$productId]);
        $productName = ($stmt2->fetch()['name']) ?? '';
        $stmt3 = $pdo->prepare("SELECT COUNT(*) as cnt FROM favorite WHERE member_id = ?");
        $stmt3->execute([$memberId]);
        $favCount = (int)($stmt3->fetch()['cnt'] ?? 0);
        echo json_encode(['success' => true, 'action' => 'added', 'product_name' => $productName, 'favorites_count' => $favCount]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}
