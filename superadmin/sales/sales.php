<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once("../../production/includes/db.php");

// Fetch sales data from database with user names and roles
try {
  $stmt = $pdo->query("SELECT s.sale_id, s.total_amount, s.payment_amount, s.change_amount, s.items, s.created_at, s.created_by, u.name as user_name, u.role as user_role FROM sales s LEFT JOIN users u ON s.created_by = u.user_id ORDER BY s.created_at DESC");
  $sales = $stmt->fetchAll();
} catch (PDOException $e) {
  $sales = [];
  $error_message = "Error loading sales: " . $e->getMessage();
}

include("../sa_components/header.php");
include("../sa_components/navigation.php");
include("../sa_components/sidebar.php");
include("../sa_components/top_navigation.php");
?>

<div class="col-md-12 col-sm-12 ">
  <div class="x_panel">
    <div class="x_title">
      <h2 class="section-title-sidebar">
        SALES
      </h2>
      <ul class="nav navbar-right panel_toolbox">
        <li>
          <div class="filter-wrapper">
            <select id="salesFilter" class="form-control" style="padding: 5px 10px; font-size: 13px; height: auto;">
              <option value="all">All</option>
              <option value="daily" selected>Daily</option>
              <option value="weekly">Weekly</option>
              <option value="monthly">Monthly</option>
            </select>
            <i class="fa fa-chevron-down dropdown-arrow" id="salesArrow"></i>
          </div>
        </li>
        </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">
      <div class="row">
        <div class="col-sm-12">
          <div class="card-box table-responsive">
            <?php if (isset($error_message)): ?>
              <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
              </div>
            <?php endif; ?>

            <table id="datatable" class="table table-striped table-bordered" style="width:100%; visibility: hidden;">
              <thead>
                <tr>
                  <th>Items</th>
                  <th>Total Amount</th>
                  <th>Payment</th>
                  <th>Change</th>
                  <th>Created By</th>
                  <th>Created At</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($sales)): ?>
                  <tr>
                    <td colspan="6" class="text-center">No sales records found.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($sales as $sale): ?>
                    <tr data-date="<?php echo !empty($sale['created_at']) ? date('Y-m-d', strtotime($sale['created_at'])) : ''; ?>">
                      <td><?php echo htmlspecialchars($sale['items'] ?? 'N/A'); ?></td>
                      <td style="font-weight: 600; color: #2A3F54;">₱ <?php echo number_format($sale['total_amount'], 2); ?></td>
                      <td>₱ <?php echo number_format($sale['payment_amount'], 2); ?></td>
                      <td>₱ <?php echo number_format($sale['change_amount'], 2); ?></td>
                      <td><?php
                          if (!empty($sale['user_name'])) {
                            echo htmlspecialchars($sale['user_name']);
                            if (!empty($sale['user_role'])) {
                              echo ' <span style="color: #999; font-size: 12px;">(' . htmlspecialchars(ucfirst($sale['user_role'])) . ')</span>';
                            }
                          } else {
                            echo 'N/A';
                          }
                          ?></td>
                      <td><?php echo !empty($sale['created_at']) ? date('M d, Y', strtotime($sale['created_at'])) . ' at ' . date('h:i A', strtotime($sale['created_at'])) : 'N/A'; ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- /page content -->
<?php include("../sa_components/footer.php") ?>
<?php include("../../production/includes/fd.php") ?>

<script>
  // Dropdown arrow animation - continuous toggle
  document.addEventListener('DOMContentLoaded', function() {
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
        setTimeout(function() {
          salesIsOpen = false;
          salesArrow.classList.remove('rotated');
        }, 100);
      });
    }
  });

  // Initialize DataTable with custom settings without flickering
  $(document).ready(function() {
    function initSalesTable() {
      if (typeof $.fn.DataTable === 'undefined') {
        setTimeout(initSalesTable, 50);
        return;
      }

      if ($.fn.DataTable.isDataTable('#datatable')) {
        $('#datatable').DataTable().destroy();
      }

      window.salesTable = $('#datatable').DataTable({
        "order": [
          [5, 'desc']
        ],
        "orderFixed": [
          [5, 'desc']
        ],
        "columnDefs": [{
            "orderable": false,
            "targets": [0]
        }],
        "initComplete": function() {
          $('#datatable').css('visibility', 'visible');
          setTimeout(function() {
            filterSales('daily');
          }, 100);
        }
      });
    }

    setTimeout(initSalesTable, 150);
  });

  // Filter sales by time period
  let currentDateFilter = null;
  let filterIndex = -1;

  function filterSales(period) {
    if (!window.salesTable) {
      console.error('Sales table not initialized');
      return;
    }

    if (filterIndex !== -1 && currentDateFilter !== null) {
      $.fn.dataTable.ext.search.splice(filterIndex, 1);
      filterIndex = -1;
      currentDateFilter = null;
    }

    if (period === 'all') {
      window.salesTable.draw();
      return;
    }

    const now = new Date();
    let startDate, endDate;

    if (period === 'daily') {
      startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
      startDate.setHours(0, 0, 0, 0);
      endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
      endDate.setHours(23, 59, 59, 999);
    } else if (period === 'weekly') {
      const day = now.getDay();
      const diff = now.getDate() - day + (day === 0 ? -6 : 1);
      startDate = new Date(now.getFullYear(), now.getMonth(), diff);
      startDate.setHours(0, 0, 0, 0);
      endDate = new Date(startDate);
      endDate.setDate(startDate.getDate() + 6);
      endDate.setHours(23, 59, 59, 999);
    } else if (period === 'monthly') {
      startDate = new Date(now.getFullYear(), now.getMonth(), 1);
      startDate.setHours(0, 0, 0, 0);
      endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0);
      endDate.setHours(23, 59, 59, 999);
    }

    const dateFilter = function(settings, data, dataIndex) {
      const tableId = settings.sTableId || (settings.nTable && settings.nTable.id) || '';
      if (tableId !== 'datatable') {
        return true;
      }

      let row = null;
      try {
        const api = new $.fn.dataTable.Api(settings);
        row = api.row(dataIndex).node();
      } catch (e) {
        const tbody = settings.nTable.querySelector('tbody');
        if (tbody && tbody.rows && tbody.rows[dataIndex]) {
          row = tbody.rows[dataIndex];
        }
      }

      if (!row) {
        return true;
      }

      const dateAttr = row.getAttribute ? row.getAttribute('data-date') : $(row).attr('data-date');
      if (!dateAttr || dateAttr === '') {
        return false;
      }

      const dateParts = dateAttr.split('-');
      if (dateParts.length !== 3) {
        return false;
      }

      const year = parseInt(dateParts[0], 10);
      const month = parseInt(dateParts[1], 10) - 1;
      const day = parseInt(dateParts[2], 10);

      if (isNaN(year) || isNaN(month) || isNaN(day)) {
        return false;
      }

      const rowDate = new Date(year, month, day);
      rowDate.setHours(0, 0, 0, 0);

      const start = new Date(startDate);
      start.setHours(0, 0, 0, 0);
      const end = new Date(endDate);
      end.setHours(23, 59, 59, 999);

      return rowDate >= start && rowDate <= end;
    };

    filterIndex = $.fn.dataTable.ext.search.length;
    $.fn.dataTable.ext.search.push(dateFilter);
    currentDateFilter = dateFilter;

    window.salesTable.draw();
  }

  // Handle select dropdown change event
  document.addEventListener('DOMContentLoaded', function() {
    const salesFilter = document.getElementById('salesFilter');
    if (salesFilter) {
      salesFilter.addEventListener('change', function() {
        filterSales(this.value);
      });
    }
  });
</script>

<style>
  .section-title-sidebar {
    font-weight: 400;
    letter-spacing: 1.2px;
    color: #2A3F54;
    font-size: 20px;
    margin: 0;
    padding: 8px 0;
    position: relative;
    display: inline-block;
  }

  .filter-wrapper {
    position: relative;
    display: inline-block;
    margin-top: 7px;
  }

  #salesFilter {
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

  #salesFilter:hover {
    border-color: #1ABB9C;
    background: linear-gradient(135deg, #ffffff 0%, #f0fdf9 100%);
    box-shadow: 0 4px 12px rgba(26, 187, 156, 0.2);
  }

  #salesFilter:focus {
    outline: none;
    border-color: #1ABB9C;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(26, 187, 156, 0.15);
  }

  #salesFilter option {
    padding: 12px 16px;
    font-weight: 500;
    background-color: #ffffff;
    color: #2A3F54;
  }

  #salesFilter option:checked {
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
</style>