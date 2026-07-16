<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$type = isset($_GET['type']) ? sanitize($_GET['type']) : 'product';

if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

$results = [];
try {
    if ($type === 'product') {
        $stmt = $pdo->prepare("SELECT product_id, name FROM product WHERE (product_id LIKE ? OR name LIKE ? OR description LIKE ?) LIMIT 10");
        $term = '%' . $q . '%';
        $stmt->execute([$term, $term, $term]);
        $rows = $stmt->fetchAll();
        foreach ($rows as $r) {
            $results[] = [
                'id' => $r['product_id'],
                'label' => $r['name'] . ' (' . $r['product_id'] . ')',
                'url' => '/product/detail.php?id=' . urlencode($r['product_id'])
            ];
        }
    } elseif ($type === 'member') {
        $stmt = $pdo->prepare("SELECT member_id, name, email FROM member WHERE (member_id LIKE ? OR name LIKE ? OR email LIKE ?) LIMIT 10");
        $term = '%' . $q . '%';
        $stmt->execute([$term, $term, $term]);
        $rows = $stmt->fetchAll();
        foreach ($rows as $r) {
            $results[] = [
                'id' => $r['member_id'],
                'label' => $r['name'] . ' (' . $r['email'] . ')',
                'url' => '/member/view.php?id=' . urlencode($r['member_id'])
            ];
        }
    } elseif ($type === 'order') {
        $stmt = $pdo->prepare("SELECT order_id, total_amount FROM order_header WHERE order_id LIKE ? LIMIT 10");
        $term = '%' . $q . '%';
        $stmt->execute([$term]);
        $rows = $stmt->fetchAll();
        foreach ($rows as $r) {
            $results[] = [
                'id' => $r['order_id'],
                'label' => 'Order ' . $r['order_id'] . ' - ' . $r['total_amount'],
                'url' => '/order/detail.php?id=' . urlencode($r['order_id'])
            ];
        }
    }
} catch (PDOException $e) {
    // ignore errors and return empty array
}

echo json_encode($results);
