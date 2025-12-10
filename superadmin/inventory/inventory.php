<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../production/includes/db.php");

// Fetch inventory data from database
try {
    $stmt = $pdo->query("SELECT i.item_id, i.item_name, i.category_id, c.category_name, i.description, i.quantity, i.total_cost, i.picture, i.created_at FROM invtry i LEFT JOIN category c ON i.category_id = c.category_id ORDER BY i.created_at DESC");
    $inventory_items = $stmt->fetchAll();
} catch(PDOException $e) {
    $inventory_items = [];
    $error_message = "Error loading inventory: " . $e->getMessage();
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
                      INVENTORY
                    </h2>
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
                    
                    <?php 
                    // Store session messages in variables for JavaScript
                    $show_success_alert = false;
                    $success_message = '';
                    $show_error_alert = false;
                    $error_message_inv = '';
                    
                    if (isset($_SESSION['inventory_message'])) {
                        if ($_SESSION['inventory_success']) {
                            $show_success_alert = true;
                            $success_message = $_SESSION['inventory_message'];
                        } else {
                            $show_error_alert = true;
                            $error_message_inv = $_SESSION['inventory_message'];
                        }
                        unset($_SESSION['inventory_message']);
                        unset($_SESSION['inventory_success']);
                    }
                    ?>
                    
                    <?php if ($show_error_alert): ?>
                      <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error_message_inv); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                    <?php endif; ?>
                    
                    <table id="datatable" class="table table-striped table-bordered" style="width:100%; visibility: hidden;">
                      <thead>
                        <tr>
                          <th>Picture</th>
                          <th>Item Name</th>
                          <th>Category</th>
                          <th>Description</th>
                          <th>Quantity</th>
                          <th>Total Cost</th>
                          <th>Created At</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (empty($inventory_items)): ?>
                          <tr>
                            <td colspan="7" class="text-center">No inventory items found.</td>
                          </tr>
                        <?php else: ?>
                          <?php foreach ($inventory_items as $item): ?>
                            <tr>
                              <td style="text-align: center;">
                                <?php 
                                $picture_path = isset($item['picture']) && !empty($item['picture']) ? $item['picture'] : '';
                                if (!empty($picture_path)): 
                                  // Extract just the filename from the path
                                  $filename = basename($picture_path);
                                  // Construct path to admin inventory images
                                  $img_path = '../../admin/inventory/images/' . htmlspecialchars($filename);
                                ?>
                                  <img src="<?php echo $img_path; ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>" style="max-width: 80px; max-height: 80px; object-fit: cover; border-radius: 4px;" onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2780%27 height=%2780%27%3E%3Crect fill=%27%23ddd%27 width=%2780%27 height=%2780%27/%3E%3Ctext fill=%27%23999%27 font-family=%27sans-serif%27 font-size=%2714%27 dy=%2710.5%27 font-weight=%27bold%27 x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27%3ENo Image%3C/text%3E%3C/svg%3E';">
                                <?php else: ?>
                                  <img src="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2780%27 height=%2780%27%3E%3Crect fill=%27%23ddd%27 width=%2780%27 height=%2780%27/%3E%3Ctext fill=%27%23999%27 font-family=%27sans-serif%27 font-size=%2714%27 dy=%2710.5%27 font-weight=%27bold%27 x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27%3ENo Image%3C/text%3E%3C/svg%3E" alt="No Image" style="max-width: 80px; max-height: 80px; object-fit: cover; border-radius: 4px; opacity: 0.7;">
                                <?php endif; ?>
                              </td>
                              <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                              <td><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></td>
                              <td><?php echo htmlspecialchars($item['description'] ?? ''); ?></td>
                              <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                              <td>₱ <?php echo number_format($item['total_cost'], 2); ?></td>
                              <td><?php echo !empty($item['created_at']) ? date('M d, Y', strtotime($item['created_at'])) . ' at ' . date('h:i A', strtotime($item['created_at'])) : 'N/A'; ?></td>
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
<?php include("../sa_components/footer.php")?>
<?php include("../../production/includes/fd.php")?>

<script>
// Initialize DataTable with custom settings without flickering
$(document).ready(function() {
    function initInventoryTable() {
        // Check if DataTables is available
        if (typeof $.fn.DataTable === 'undefined') {
            setTimeout(initInventoryTable, 50);
            return;
        }
        
        // Destroy existing DataTable instance if it exists (from custom.js)
        if ($.fn.DataTable.isDataTable('#datatable')) {
            $('#datatable').DataTable().destroy();
        }
        
        // Initialize DataTable with custom configuration
        $('#datatable').DataTable({
            "order": [[6, 'desc']], // Sort by Created At column (index 6) in descending order
            "columnDefs": [
                { "orderable": false, "targets": [0] } // Disable sorting on Picture column (index 0)
            ],
            "initComplete": function() {
                // Show table only after initialization is complete (prevents flickering)
                $('#datatable').css('visibility', 'visible');
            }
        });
    }
    
    // Initialize after custom.js loads, but hide flickering
    setTimeout(initInventoryTable, 150);
    
    // Show SweetAlert for success messages
    <?php if ($show_success_alert): ?>
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: '<?php echo addslashes($success_message); ?>',
        timer: 3000,
        timerProgressBar: true,
        showConfirmButton: true,
        confirmButtonText: 'OK',
        confirmButtonColor: '#26B99A'
    });
    <?php endif; ?>
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
</style>