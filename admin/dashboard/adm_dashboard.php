<?php
// Include database connection
require_once('../../production/includes/db.php');

// Get total users count (excluding admin and superadmin)
try {
  $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role NOT IN ('admin', 'superadmin')");
  $result = $stmt->fetch();
  $total_users = $result['total_users'] ?? 0;
} catch (PDOException $e) {
  $total_users = 0; // Default to 0 if there's an error
}

// Get total items in stock from invtry table
try {
  $stmt = $pdo->query("SELECT SUM(quantity) as total_items FROM invtry");
  $result = $stmt->fetch();
  $total_items = $result['total_items'] ?? 0;
} catch (PDOException $e) {
  $total_items = 0; // Default to 0 if there's an error
}

// Get total categories count from category table
try {
  $stmt = $pdo->query("SELECT COUNT(*) as total_categories FROM category");
  $result = $stmt->fetch();
  $total_categories = $result['total_categories'] ?? 0;
} catch (PDOException $e) {
  $total_categories = 0; // Default to 0 if there's an error
}

// Get top best-selling products from sales table
$top_products = [];
try {
  $stmt = $pdo->query("SELECT items FROM sales WHERE items IS NOT NULL AND items != ''");
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

  // Sort by quantity sold (descending) and get top 3
  arsort($product_counts);
  $top_products = array_slice($product_counts, 0, 3, true);
} catch (PDOException $e) {
  $top_products = [];
}

// Get max sold count for progress bar calculation
$max_sold = !empty($top_products) ? max($top_products) : 1;

include("../admin_components/header.php");
include("../admin_components/navigation.php");
include("../admin_components/sidebar.php");
include("../admin_components/top_navigation.php");
?>
<style>
  .tile_count {
    display: flex;
    gap: 24px;
    margin-bottom: 0 !important;
    margin-top: 0 !important;
    padding: 0 10px;
  }

  .tile_count .tile_stats_count {
    flex: 1;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 12px;
    padding: 28px 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06), 0 4px 16px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.04);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-left: none;
    position: relative;
    overflow: hidden;
  }

  .tile_count .tile_stats_count:before {
    display: none !important;
  }

  .tile_count .tile_stats_count:hover {
    transform: translateY(-6px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1), 0 8px 24px rgba(0, 0, 0, 0.12), 0 16px 40px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(26, 187, 156, 0.1);
    border-color: rgba(26, 187, 156, 0.25);
  }

  .tile_count .tile_stats_count .count_top {
    white-space: normal;
    overflow: visible;
    text-overflow: clip;
    display: block;
    margin-bottom: 16px;
    padding-top: 24px;
    color: #5a6c7d;
    font-weight: 600;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: relative;
    z-index: 1;
  }

  .tile_count .tile_stats_count .count {
    font-size: 42px;
    font-weight: 800;
    color: #2A3F54;
    margin: 20px 0 16px 0;
    line-height: 1.1;
    letter-spacing: -1px;
    background: linear-gradient(135deg, #2A3F54, #1ABB9C);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    position: relative;
    z-index: 1;
  }

  .tile_count .tile_stats_count .icon {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 64px;
    color: rgba(26, 187, 156, 0.35);
    opacity: 1;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 0;
  }

  .tile_count .tile_stats_count:hover .icon {
    color: rgba(26, 187, 156, 0.5);
    transform: scale(1.15);
    opacity: 1;
  }

  @media (max-width: 768px) {
    .tile_count {
      flex-direction: column;
      gap: 16px;
    }

    .tile_count .tile_stats_count {
      padding: 24px 20px;
    }

    .tile_count .tile_stats_count .count {
      font-size: 36px;
    }
  }

  .dashboard_graph {
    margin-top: 0 !important;
    padding-top: 7px !important;
  }

  .section-title-main {
    font-weight: 400;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: #2A3F54;
    font-size: 18px;
    margin: 0;
    padding: 8px 0;
    position: relative;
    display: inline-block;
  }

  .section-title-main::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, #1ABB9C, rgba(26, 187, 156, 0.3));
    border-radius: 2px;
  }

  .section-title-sidebar {
    font-weight: 400;
    letter-spacing: 1.2px;
    color: #2A3F54;
    font-size: 16px;
    margin: 0;
    padding: 8px 0;
    position: relative;
    display: inline-block;
  }

  .section-title-sidebar::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, #1ABB9C, rgba(26, 187, 156, 0.3));
    border-radius: 2px;
  }

  .best-selling-item {
    margin-bottom: 20px;
    position: relative;
  }

  .best-selling-item p {
    margin: 0;
    font-weight: 500;
    color: #2A3F54;
    font-size: 14px;
  }

  .best-selling-count {
    font-weight: 600;
    color: #26B99A;
    font-size: 12px;
    white-space: nowrap;
  }

  .filter-wrapper {
    position: relative;
    display: inline-block;
  }

  #salesFilter,
  #productsFilter {
    border: 2px solid #e0e6ed;
    border-radius: 8px;
    background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
    color: #2A3F54;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 140px;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    padding: 10px 40px 10px 16px;
    font-size: 14px;
    letter-spacing: 0.3px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
  }

  #salesFilter:hover,
  #productsFilter:hover {
    border-color: #1ABB9C;
    background: linear-gradient(135deg, #ffffff 0%, #f0fdf9 100%);
    box-shadow: 0 4px 12px rgba(26, 187, 156, 0.2);
  }

  #salesFilter:focus,
  #productsFilter:focus {
    outline: none;
    border-color: #1ABB9C;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(26, 187, 156, 0.15);
  }

  #salesFilter option,
  #productsFilter option {
    padding: 12px 16px;
    font-weight: 500;
    background-color: #ffffff;
    color: #2A3F54;
  }

  #salesFilter option:checked,
  #productsFilter option:checked {
    background: linear-gradient(90deg, #1ABB9C 0%, #16a085 100%);
    color: #ffffff;
    font-weight: 700;
  }

  .dropdown-arrow {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    transition: all 0.3s ease;
    color: #1ABB9C;
    font-size: 13px;
    font-weight: bold;
  }

  .dropdown-arrow.rotated {
    transform: translateY(-50%) rotate(180deg);
    color: #16a085;
  }

  .filter-wrapper:hover .dropdown-arrow {
    color: #16a085;
  }

  .sales-chart-title {
    color: #2A3F54;
    font-weight: 500;
    font-size: 18px;
    margin: 0;
    letter-spacing: 0.3px;
  }

  /* Add padding below the chart containers */
  .col-md-6.col-sm-6 .x_panel {
    margin-bottom: 0;
  }

  /* Add space between chart rows */
  .charts-container .col-md-6.col-sm-6 {
    margin-bottom: 10px;
  }

  /* Add space between graphs and footer */
  .charts-container {
    padding-bottom: 795px;
  }
</style>

<div class="row" style="display: inline-block; width: 100%; margin-top: 0; padding-top: 0; margin-bottom: 10px;">
  <div class="tile_count">
    <div class="col-md-4 col-sm-4 tile_stats_count">
      <i class="fa fa-users icon"></i>
      <span class="count_top">Total Users</span>
      <div class="count" data-target="<?php echo $total_users; ?>">0</div>
    </div>
    <div class="col-md-4 col-sm-4 tile_stats_count">
      <i class="fa fa-cubes icon"></i>
      <span class="count_top">Total Items In Stock</span>
      <div class="count" data-target="<?php echo $total_items; ?>">0</div>
    </div>
    <div class="col-md-4 col-sm-4 tile_stats_count">
      <i class="fa fa-folder icon"></i>
      <span class="count_top">Total Categories</span>
      <div class="count" data-target="<?php echo $total_categories; ?>">0</div>
    </div>
  </div>
</div>


<div class="charts-container">
  <div class="col-md-6 col-sm-6">
    <div class="x_panel">
      <div class="x_title">
        <h2 class="sales-chart-title">NUMBER OF SALES</h2>
        <ul class="nav navbar-right panel_toolbox">
          <li>
            <div class="filter-wrapper">
              <select id="salesFilter" class="form-control" style="padding: 5px 10px; font-size: 13px; height: auto;">
                <option value="daily" selected>Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="yearly">Yearly</option>
              </select>
              <i class="fa fa-chevron-down dropdown-arrow" id="salesArrow"></i>
            </div>
          </li>
        </ul>
        <div class="clearfix"></div>
      </div>
      <div class="x_content" style="height: 310px;">
        <canvas id="salesLineChart"></canvas>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-sm-6  ">
    <div class="x_panel">
      <div class="x_title">
        <h2 class="sales-chart-title">TOP SELLING PRODUCTS</h2>
        <ul class="nav navbar-right panel_toolbox">
          <li>
            <div class="filter-wrapper">
              <select id="productsFilter" class="form-control" style="padding: 5px 10px; font-size: 13px; height: auto;">
                <option value="daily" selected>Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="yearly">Yearly</option>
              </select>
              <i class="fa fa-chevron-down dropdown-arrow" id="productsArrow"></i>
            </div>
          </li>
        </ul>
        <div class="clearfix"></div>
      </div>
      <div class="x_content" style="height: 310px;">
        <canvas id="topProductsChart"></canvas>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-sm-6">
    <div class="x_panel">
      <div class="x_title">
        <h2 class="sales-chart-title">STOCK LEVEL</h2>
        <ul class="nav navbar-right panel_toolbox">
        </ul>
        <div class="clearfix"></div>
      </div>
      <div class="x_content" style="height: 310px;">
        <canvas id="stockLevelChart"></canvas>
      </div>
    </div>
  </div>
</div>






<script>
  (function() {
    function animateCount(element) {
      const target = parseInt(element.getAttribute('data-target'));
      const duration = 4000; // 4 seconds for slower animation
      const startTime = Date.now();
      const startValue = 0;

      function updateCount() {
        const currentTime = Date.now();
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);

        // Strong ease-out: Fast at start, slows down dramatically near the target
        // Using quartic ease-out for strong deceleration
        const t = 1 - progress;
        const ease = 1 - (t * t * t * t); // Strong ease-out quartic

        const currentValue = Math.floor(startValue + (target - startValue) * ease);

        // Format number with comma if needed
        element.textContent = currentValue.toLocaleString();

        if (progress < 1) {
          requestAnimationFrame(updateCount);
        } else {
          // Ensure final value is set correctly
          element.textContent = target.toLocaleString();
        }
      }

      updateCount();
    }

    function startAnimation() {
      const countElements = document.querySelectorAll('.tile_count .tile_stats_count .count');
      countElements.forEach((element, index) => {
        // Reset to 0 first
        element.textContent = '0';
        // Stagger the animations with longer delay for smoother cascading effect
        setTimeout(() => {
          animateCount(element);
        }, index * 200);
      });
    }

    // Animate on page load
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', function() {
        setTimeout(startAnimation, 300);
      });
    } else {
      setTimeout(startAnimation, 300);
    }

    // Animate when dashboard area is clicked
    const dashboardArea = document.querySelector('.tile_count');
    if (dashboardArea) {
      dashboardArea.addEventListener('click', function(e) {
        // Only trigger if clicking on the container or cards, not on other elements
        if (e.target.closest('.tile_stats_count') || e.target === dashboardArea) {
          startAnimation();
        }
      });
    }

    // Also animate when clicking on individual cards
    const cards = document.querySelectorAll('.tile_stats_count');
    cards.forEach(card => {
      card.addEventListener('click', function() {
        startAnimation();
      });
    });
  })();

  // Initialize progress bars for best-selling products
  $(document).ready(function() {
    // Wait a bit for the page to fully load
    setTimeout(function() {
      if ($(".progress .progress-bar")[0]) {
        $('.progress .progress-bar').progressbar();
      }
    }, 500);
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  // Shared flag to prevent infinite loops in filter synchronization
  let isFilterSyncing = false;

  // Dropdown arrow animation - continuous toggle
  document.addEventListener('DOMContentLoaded', function() {
    // Sales filter arrow
    const salesFilter = document.getElementById('salesFilter');
    const salesArrow = document.getElementById('salesArrow');
    let salesIsOpen = false;

    if (salesFilter && salesArrow) {
      salesFilter.addEventListener('mousedown', function() {
        salesIsOpen = !salesIsOpen;
        if (salesIsOpen) {
          salesArrow.classList.add('rotated');
        } else {
          salesArrow.classList.remove('rotated');
        }
      });

      salesFilter.addEventListener('blur', function() {
        salesIsOpen = false;
        salesArrow.classList.remove('rotated');
      });

      salesFilter.addEventListener('change', function() {
        // Close after selection
        setTimeout(function() {
          salesIsOpen = false;
          salesArrow.classList.remove('rotated');
        }, 100);
      });
    }

    // Products filter arrow
    const productsFilter = document.getElementById('productsFilter');
    const productsArrow = document.getElementById('productsArrow');
    let productsIsOpen = false;

    if (productsFilter && productsArrow) {
      productsFilter.addEventListener('mousedown', function() {
        productsIsOpen = !productsIsOpen;
        if (productsIsOpen) {
          productsArrow.classList.add('rotated');
        } else {
          productsArrow.classList.remove('rotated');
        }
      });

      productsFilter.addEventListener('blur', function() {
        productsIsOpen = false;
        productsArrow.classList.remove('rotated');
      });

      productsFilter.addEventListener('change', function() {
        // Close after selection
        setTimeout(function() {
          productsIsOpen = false;
          productsArrow.classList.remove('rotated');
        }, 100);
      });
    }
  });

  // Sales Line Chart with Dynamic Filtering
  document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesLineChart');
    let salesChart = null;

    if (ctx) {
      // Initialize chart
      function createChart(labels, data) {
        // Destroy existing chart if it exists
        if (salesChart) {
          salesChart.destroy();
        }

        salesChart = new Chart(ctx, {
          type: 'line',
          data: {
            labels: labels,
            datasets: [{
              label: 'Number of Sales',
              data: data,
              borderColor: 'rgba(26, 187, 156, 1)',
              backgroundColor: 'rgba(26, 187, 156, 0.1)',
              borderWidth: 3,
              fill: true,
              tension: 0.4,
              pointRadius: 5,
              pointBackgroundColor: 'rgba(26, 187, 156, 1)',
              pointBorderColor: '#fff',
              pointBorderWidth: 2,
              pointHoverRadius: 7,
              pointHoverBackgroundColor: 'rgba(26, 187, 156, 1)',
              pointHoverBorderColor: '#fff',
              pointHoverBorderWidth: 3
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 2,
            plugins: {
              legend: {
                display: false
              },
              tooltip: {
                mode: 'index',
                intersect: false,
                backgroundColor: 'rgba(42, 63, 84, 0.95)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: 'rgba(26, 187, 156, 0.5)',
                borderWidth: 1,
                padding: 12,
                displayColors: true,
                callbacks: {
                  label: function(context) {
                    let label = context.dataset.label || '';
                    if (label) {
                      label += ': ';
                    }
                    if (context.parsed.y !== null) {
                      label += context.parsed.y + ' sale' + (context.parsed.y !== 1 ? 's' : '');
                    }
                    return label;
                  }
                }
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                min: 0,
                grid: {
                  color: 'rgba(0, 0, 0, 0.05)',
                  drawBorder: false
                },
                ticks: {
                  stepSize: 1,
                  font: {
                    size: 11
                  }
                }
              },
              x: {
                grid: {
                  display: false,
                  drawBorder: false
                },
                ticks: {
                  maxRotation: 45,
                  minRotation: 45,
                  font: {
                    size: 12,
                    weight: 'bold'
                  },
                  color: '#2A3F54'
                }
              }
            },
            interaction: {
              mode: 'nearest',
              axis: 'x',
              intersect: false
            },
            hover: {
              mode: 'index',
              intersect: false
            }
          }
        });
      }

      // Fetch sales data based on filter
      function fetchSalesData(filter) {
        fetch('get_sales_data.php?filter=' + filter)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              createChart(data.dates, data.counts);

              // Update date range display
              const dateRangeElement = document.getElementById('salesDateRange');
              if (dateRangeElement) {
                if (data.date_range) {
                  dateRangeElement.textContent = '(' + data.date_range + ')';
                } else {
                  dateRangeElement.textContent = '';
                }
              }
            } else {
              console.error('Error fetching sales data:', data.message);
            }
          })
          .catch(error => {
            console.error('Error:', error);
          });
      }

      // Load initial data (daily by default)
      fetchSalesData('daily');

      // Handle filter change with synchronization
      const filterSelect = document.getElementById('salesFilter');
      const productsFilterSelect = document.getElementById('productsFilter');
      
      if (filterSelect) {
        filterSelect.addEventListener('change', function() {
          const selectedValue = this.value;
          fetchSalesData(selectedValue);
          
          // Synchronize the products filter by triggering its change event
          if (!isFilterSyncing && productsFilterSelect && productsFilterSelect.value !== selectedValue) {
            isFilterSyncing = true;
            productsFilterSelect.value = selectedValue;
            // Trigger change event to update the products chart
            const changeEvent = new Event('change', { bubbles: true });
            productsFilterSelect.dispatchEvent(changeEvent);
            setTimeout(() => { isFilterSyncing = false; }, 100);
          }
        });
      }
    }
  });

  // Top Selling Products Chart with Dynamic Filtering
  document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('topProductsChart');
    let productsChart = null;

    if (ctx) {
      // Create color palette
      const colors = [
        'rgba(26, 187, 156, 0.8)',
        'rgba(52, 152, 219, 0.8)',
        'rgba(155, 89, 182, 0.8)',
        'rgba(241, 196, 15, 0.8)',
        'rgba(230, 126, 34, 0.8)'
      ];

      const borderColors = [
        'rgba(26, 187, 156, 1)',
        'rgba(52, 152, 219, 1)',
        'rgba(155, 89, 182, 1)',
        'rgba(241, 196, 15, 1)',
        'rgba(230, 126, 34, 1)'
      ];

      // Initialize chart
      function createProductsChart(labels, data) {
        // Destroy existing chart if it exists
        if (productsChart) {
          productsChart.destroy();
        }

        productsChart = new Chart(ctx, {
          type: 'pie',
          data: {
            labels: labels,
            datasets: [{
              data: data,
              backgroundColor: colors.slice(0, labels.length),
              borderColor: borderColors.slice(0, labels.length),
              borderWidth: 2
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: true,
                position: 'right',
                labels: {
                  usePointStyle: true,
                  padding: 15,
                  font: {
                    size: 13,
                    weight: 'bold'
                  },
                  color: '#2A3F54',
                  boxWidth: 15,
                  boxHeight: 15
                }
              },
              tooltip: {
                backgroundColor: 'rgba(42, 63, 84, 0.95)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: 'rgba(26, 187, 156, 0.5)',
                borderWidth: 1,
                padding: 12,
                displayColors: true,
                callbacks: {
                  label: function(context) {
                    const label = context.label || '';
                    const value = context.parsed || 0;
                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                    return label + ': ' + value + ' units (' + percentage + '%)';
                  }
                }
              }
            }
          }
        });
      }

      // Fetch top products data based on filter
      function fetchTopProducts(filter) {
        fetch('get_top_products.php?filter=' + filter)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              createProductsChart(data.labels, data.data);
            } else {
              console.error('Error fetching top products:', data.message);
            }
          })
          .catch(error => {
            console.error('Error:', error);
          });
      }

      // Load initial data (daily by default)
      fetchTopProducts('daily');

      // Handle filter change with synchronization
      const filterSelect = document.getElementById('productsFilter');
      const salesFilterSelect = document.getElementById('salesFilter');
      
      if (filterSelect) {
        filterSelect.addEventListener('change', function() {
          const selectedValue = this.value;
          fetchTopProducts(selectedValue);
          
          // Synchronize the sales filter by triggering its change event
          if (!isFilterSyncing && salesFilterSelect && salesFilterSelect.value !== selectedValue) {
            isFilterSyncing = true;
            salesFilterSelect.value = selectedValue;
            // Trigger change event to update the sales chart
            const changeEvent = new Event('change', { bubbles: true });
            salesFilterSelect.dispatchEvent(changeEvent);
            setTimeout(() => { isFilterSyncing = false; }, 100);
          }
        });
      }
    }
  });

  // Stock Level Chart with AJAX
  document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('stockLevelChart');
    let stockChart = null;

    if (ctx) {
      // Color palettes
      const colorPalette = [
        'rgba(26, 187, 156, 0.8)',
        'rgba(52, 152, 219, 0.8)',
        'rgba(155, 89, 182, 0.8)',
        'rgba(241, 196, 15, 0.8)',
        'rgba(230, 126, 34, 0.8)',
        'rgba(231, 76, 60, 0.8)',
        'rgba(46, 204, 113, 0.8)',
        'rgba(142, 68, 173, 0.8)'
      ];

      const borderPalette = [
        'rgba(26, 187, 156, 1)',
        'rgba(52, 152, 219, 1)',
        'rgba(155, 89, 182, 1)',
        'rgba(241, 196, 15, 1)',
        'rgba(230, 126, 34, 1)',
        'rgba(231, 76, 60, 1)',
        'rgba(46, 204, 113, 1)',
        'rgba(142, 68, 173, 1)'
      ];

      // Initialize chart function
      function createStockChart(labels, quantities) {
        // Destroy existing chart if it exists
        if (stockChart) {
          stockChart.destroy();
        }

        // Create color arrays based on data length
        const colors = labels.map((label, index) => colorPalette[index % colorPalette.length]);
        const borderColors = labels.map((label, index) => borderPalette[index % borderPalette.length]);

        // Initialize chart
        stockChart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [{
              label: 'Stock Quantity',
              data: quantities,
              backgroundColor: colors,
              borderColor: borderColors,
              borderWidth: 2
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false
              },
              tooltip: {
                backgroundColor: 'rgba(42, 63, 84, 0.95)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: 'rgba(26, 187, 156, 0.5)',
                borderWidth: 1,
                padding: 12,
                displayColors: true,
                callbacks: {
                  label: function(context) {
                    return 'Stock: ' + context.parsed.y + ' units';
                  }
                }
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                min: 0,
                grid: {
                  color: 'rgba(0, 0, 0, 0.05)',
                  drawBorder: false
                },
                ticks: {
                  stepSize: 1,
                  font: {
                    size: 11
                  }
                }
              },
              x: {
                grid: {
                  display: false,
                  drawBorder: false
                },
                ticks: {
                  maxRotation: 45,
                  minRotation: 45,
                  font: {
                    size: 12,
                    weight: 'bold'
                  },
                  color: '#2A3F54'
                }
              }
            }
          }
        });
      }

      // Fetch stock data via AJAX
      function fetchStockData() {
        fetch('get_stock_data.php')
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              createStockChart(data.labels, data.data);
            } else {
              console.error('Error fetching stock data:', data.message);
              // Create empty chart on error
              createStockChart([], []);
            }
          })
          .catch(error => {
            console.error('Error:', error);
            // Create empty chart on error
            createStockChart([], []);
          });
      }

      // Load initial data
      fetchStockData();
    }
  });
</script>

<!-- /page content -->
 <?php include("../admin_components/footer.php") ?>
<?php include("../../production/includes/fd.php") ?>