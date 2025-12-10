<?php
// Include database connection
require_once('../../production/includes/db.php');

header('Content-Type: application/json');

$stock_data = [];

try {
  // Get stock levels from invtry table, ordered by low stock first
  $stmt = $pdo->query("SELECT item_id, item_name, quantity FROM invtry ORDER BY quantity ASC, item_name ASC");
  $stock_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Prepare response
  $labels = [];
  $quantities = [];
  
  foreach ($stock_data as $item) {
    $labels[] = $item['item_name'];
    $quantities[] = (int)($item['quantity'] ?? 0);
  }

  echo json_encode([
    'success' => true,
    'labels' => $labels,
    'data' => $quantities,
    'items' => $stock_data
  ]);

} catch (PDOException $e) {
  echo json_encode([
    'success' => false,
    'message' => 'Error fetching stock data: ' . $e->getMessage(),
    'labels' => [],
    'data' => [],
    'items' => []
  ]);
}
?>

