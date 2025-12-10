<?php
// Include database connection
require_once('../../production/includes/db.php');

// Set timezone to Philippine Time
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'daily';

$top_products = [];

try {
  // Build the date condition based on filter
  $date_condition = "";
  
  switch ($filter) {
    case 'daily':
      $date_condition = "WHERE DATE(created_at) = CURDATE()";
      break;
    
    case 'weekly':
      // Current week (Sunday to Saturday)
      $current_day = date('w');
      $days_since_sunday = $current_day;
      $week_start = date('Y-m-d', strtotime("-{$days_since_sunday} days"));
      $week_end = date('Y-m-d', strtotime($week_start . " +6 days"));
      $date_condition = "WHERE DATE(created_at) >= '$week_start' AND DATE(created_at) <= '$week_end'";
      break;
    
    case 'monthly':
      $date_condition = "WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
      break;
    
    case 'yearly':
      $date_condition = "WHERE YEAR(created_at) = YEAR(CURDATE())";
      break;
    
    default:
      $date_condition = "WHERE DATE(created_at) = CURDATE()";
      break;
  }
  
  // Get sales items based on the filter
  $stmt = $pdo->query("SELECT items FROM sales $date_condition AND items IS NOT NULL AND items != ''");
  $sales = $stmt->fetchAll();

  // Count products sold
  $product_counts = [];

  foreach ($sales as $sale) {
    $items_string = $sale['items'];
    if (empty($items_string)) continue;

    // Split by comma to get individual items
    $items = explode(',', $items_string);

    foreach ($items as $item) {
      $item = trim($item);
      if (empty($item)) continue;

      // Check if item has quantity (format: "Item Name x2")
      if (preg_match('/^(.+?)\s*x\s*(\d+)$/i', $item, $matches)) {
        $item_name = trim($matches[1]);
        $quantity = intval($matches[2]);
      } else {
        $item_name = $item;
        $quantity = 1;
      }

      // Add to product counts
      if (isset($product_counts[$item_name])) {
        $product_counts[$item_name] += $quantity;
      } else {
        $product_counts[$item_name] = $quantity;
      }
    }
  }

  // Sort by quantity sold (descending) and get top 5
  arsort($product_counts);
  $top_products = array_slice($product_counts, 0, 5, true);

  // Prepare response
  $labels = array_keys($top_products);
  $data = array_values($top_products);

  echo json_encode([
    'success' => true,
    'labels' => $labels,
    'data' => $data,
    'filter' => $filter
  ]);

} catch (PDOException $e) {
  echo json_encode([
    'success' => false,
    'message' => 'Error fetching data: ' . $e->getMessage(),
    'labels' => [],
    'data' => []
  ]);
}
?>

