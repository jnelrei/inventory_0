<?php
// Fetch low stock items from inventory
// This file fetches all items with quantity <= 5 from the invtry table

// Include database connection
require_once('../../production/includes/db.php');

// Initialize variables
$low_stock_items = [];
$low_stock_count = 0;

// Fetch low stock items (quantity <= 5)
try {
  $stmt = $pdo->query("SELECT item_id, item_name, quantity FROM invtry WHERE quantity <= 5 ORDER BY quantity ASC");
  $low_stock_items = $stmt->fetchAll();
  $low_stock_count = count($low_stock_items);
} catch (PDOException $e) {
  // If there's an error, set empty arrays and count to 0
  $low_stock_items = [];
  $low_stock_count = 0;
  // Optionally log the error (uncomment if you have error logging)
  // error_log("Error fetching low stock items: " . $e->getMessage());
}
?>

