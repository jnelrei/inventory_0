<?php
// Include database connection
require_once('../../production/includes/db.php');

// Set timezone to Philippine Time
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'monthly';

$dates = [];
$transaction_counts = [];

try {
  switch ($filter) {
    case 'daily':
      // All 24 hours - hourly data
      $stmt = $pdo->query("
        SELECT 
          HOUR(created_at) as hour_time,
          COUNT(*) as transaction_count
        FROM sales 
        WHERE DATE(created_at) = CURDATE()
        GROUP BY HOUR(created_at)
        ORDER BY hour_time ASC
      ");
      $sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
      
      // Create an array with all 24 hours initialized to 0
      $hourly_data = array_fill(0, 24, 0);
      
      // Fill in the actual data
      foreach ($sales_data as $row) {
        $hour = (int)$row['hour_time'];
        $hourly_data[$hour] = (int)$row['transaction_count'];
      }
      
      // Format for display
      for ($i = 0; $i < 24; $i++) {
        $dates[] = date('g A', strtotime($i . ':00'));
        $transaction_counts[] = $hourly_data[$i];
      }
      break;

    case 'weekly':
      // Current week (Sunday to Saturday)
      // Find the most recent Sunday (or today if today is Sunday)
      $current_day = date('w'); // 0 (Sunday) to 6 (Saturday)
      
      // Calculate days since last Sunday
      // If today is Sunday (0), days_since = 0
      // If today is Monday (1), days_since = 1
      // If today is Saturday (6), days_since = 6
      $days_since_sunday = $current_day;
      
      // Get the Sunday of current week
      $week_start = date('Y-m-d', strtotime("-{$days_since_sunday} days"));
      $week_end = date('Y-m-d', strtotime($week_start . " +6 days"));
      
      // Query for the week
      $stmt = $pdo->prepare("
        SELECT 
          DATE(created_at) as sale_date,
          COUNT(*) as transaction_count
        FROM sales 
        WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?
        GROUP BY DATE(created_at)
        ORDER BY sale_date ASC
      ");
      $stmt->execute([$week_start, $week_end]);
      $sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
      
      // Create array indexed by date
      $weekly_data = [];
      foreach ($sales_data as $row) {
        $weekly_data[$row['sale_date']] = (int)$row['transaction_count'];
      }
      
      // Generate all 7 days from Sunday to Saturday
      $day_names = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
      
      for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', strtotime($week_start . " +{$i} days"));
        
        $dates[] = $day_names[$i];
        $transaction_counts[] = isset($weekly_data[$date]) ? $weekly_data[$date] : 0;
      }
      break;

    case 'monthly':
      // Current month - daily data (showing all days 1 to last day)
      $stmt = $pdo->query("
        SELECT 
          DATE(created_at) as sale_date,
          COUNT(*) as transaction_count
        FROM sales 
        WHERE MONTH(created_at) = MONTH(CURDATE()) 
        AND YEAR(created_at) = YEAR(CURDATE())
        GROUP BY DATE(created_at)
        ORDER BY sale_date ASC
      ");
      $sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
      
      // Create array indexed by date
      $monthly_data = [];
      foreach ($sales_data as $row) {
        $monthly_data[$row['sale_date']] = (int)$row['transaction_count'];
      }
      
      // Get the number of days in current month
      $days_in_month = date('t'); // 28, 29, 30, or 31
      $current_month = date('Y-m');
      
      // Generate all days from 1 to last day of month
      for ($day = 1; $day <= $days_in_month; $day++) {
        $date = $current_month . '-' . sprintf('%02d', $day);
        $dates[] = date('M d', strtotime($date));
        $transaction_counts[] = isset($monthly_data[$date]) ? $monthly_data[$date] : 0;
      }
      break;

    case 'yearly':
      // Current year - monthly data (showing all 12 months)
      $stmt = $pdo->query("
        SELECT 
          MONTH(created_at) as month_num,
          COUNT(*) as transaction_count
        FROM sales 
        WHERE YEAR(created_at) = YEAR(CURDATE())
        GROUP BY MONTH(created_at)
        ORDER BY month_num ASC
      ");
      $sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
      
      // Create array indexed by month number
      $yearly_data = array_fill(1, 12, 0); // Initialize all 12 months with 0
      foreach ($sales_data as $row) {
        $yearly_data[(int)$row['month_num']] = (int)$row['transaction_count'];
      }
      
      // Generate all 12 months
      $month_names = ['January', 'February', 'March', 'April', 'May', 'June', 
                      'July', 'August', 'September', 'October', 'November', 'December'];
      
      for ($month = 1; $month <= 12; $month++) {
        $dates[] = $month_names[$month - 1];
        $transaction_counts[] = $yearly_data[$month];
      }
      break;

    default:
      // Default to daily
      $stmt = $pdo->query("
        SELECT 
          HOUR(created_at) as hour_time,
          COUNT(*) as transaction_count
        FROM sales 
        WHERE DATE(created_at) = CURDATE()
        GROUP BY HOUR(created_at)
        ORDER BY hour_time ASC
      ");
      $sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
      
      // Create an array with all 24 hours initialized to 0
      $hourly_data = array_fill(0, 24, 0);
      
      // Fill in the actual data
      foreach ($sales_data as $row) {
        $hour = (int)$row['hour_time'];
        $hourly_data[$hour] = (int)$row['transaction_count'];
      }
      
      // Format for display
      for ($i = 0; $i < 24; $i++) {
        $dates[] = date('g A', strtotime($i . ':00'));
        $transaction_counts[] = $hourly_data[$i];
      }
      break;
  }

  // Prepare date range info for display
  $date_range = '';
  if ($filter == 'weekly' && !empty($dates)) {
    // Extract start and end from the dates array
    $start_date = date('M d', strtotime($week_start));
    $end_date = date('M d', strtotime($week_end));
    $date_range = $start_date . ' to ' . $end_date;
  }

  echo json_encode([
    'success' => true,
    'dates' => $dates,
    'counts' => $transaction_counts,
    'date_range' => $date_range
  ]);

} catch (PDOException $e) {
  echo json_encode([
    'success' => false,
    'message' => 'Error fetching data: ' . $e->getMessage(),
    'dates' => [],
    'counts' => []
  ]);
}
?>

