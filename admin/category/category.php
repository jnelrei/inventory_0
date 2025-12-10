<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../production/includes/db.php");

// Fetch categories from database
try {
    $stmt = $pdo->query("SELECT category_id, category_name, created_at FROM category ORDER BY created_at DESC");
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $categories = [];
    $error_message = "Error loading categories: " . $e->getMessage();
}

include("../admin_components/header.php");
include("../admin_components/navigation.php");
include("../admin_components/sidebar.php");
include("../admin_components/top_navigation.php");
?>
    
    <div class="col-md-12 col-sm-12 ">
                <div class="x_panel">
                  <div class="x_title">
                    <h2 class="section-title-sidebar">
                      CATEGORY
                    </h2>
                    <button type="button"
                            class="btn btn-success btn-sm"
                            style="float: right; margin-top: 7px;"
                            onclick="addCategory()">
                      <i class="fa fa-plus"></i> Add Category
                    </button>
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
                    $error_message = '';
                    
                    if (isset($_SESSION['category_message'])) {
                        if ($_SESSION['category_success']) {
                            $show_success_alert = true;
                            $success_message = $_SESSION['category_message'];
                        } else {
                            $show_error_alert = true;
                            $error_message = $_SESSION['category_message'];
                        }
                        unset($_SESSION['category_message']);
                        unset($_SESSION['category_success']);
                    }
                    ?>
                    
                    <?php if ($show_error_alert): ?>
                      <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                    <?php endif; ?>
                    
                    <table id="datatable" class="table table-striped table-bordered" style="width:100%; visibility: hidden;">
                      <thead>
                        <tr>
                          <th>Category Name</th>
                          <th>Created At</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (empty($categories)): ?>
                          <tr>
                            <td colspan="3" class="text-center">No categories found.</td>
                          </tr>
                        <?php else: ?>
                          <?php foreach ($categories as $category): ?>
                            <?php
                              $createdAtFormatted = '';
                              if (!empty($category['created_at'])) {
                                  $timestamp = strtotime($category['created_at']);
                                  if ($timestamp !== false) {
                                      // Example: November 29, 2025 at 3:45 PM
                                      $createdAtFormatted = date('F j, Y', $timestamp) . ' at ' . date('g:i A', $timestamp);
                                  }
                              }
                            ?>
                            <tr>
                              <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                              <td><?php echo htmlspecialchars($createdAtFormatted); ?></td>
                              <td style="text-align: center;">
                                <button class="btn btn-sm btn-primary" onclick="editCategory(<?php echo $category['category_id']; ?>)" style="margin-right: 5px;">
                                  <i class="fa fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteCategory(<?php echo $category['category_id']; ?>)">
                                  <i class="fa fa-trash"></i> Delete
                                </button>
                              </td>
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
<?php include("category_modal.php"); ?>
<?php include("../admin_components/footer.php")?>
<?php include("../../production/includes/fd.php")?>

<script>
// Initialize DataTable with custom settings without flickering
$(document).ready(function() {
    function initCategoryTable() {
        // Check if DataTables is available
        if (typeof $.fn.DataTable === 'undefined') {
            setTimeout(initCategoryTable, 50);
            return;
        }
        
        // Destroy existing DataTable instance if it exists (from custom.js)
        if ($.fn.DataTable.isDataTable('#datatable')) {
            $('#datatable').DataTable().destroy();
        }
        
        // Initialize DataTable with custom configuration
        $('#datatable').DataTable({
            "order": [[1, 'desc']], // Sort by Created At column (index 1) in descending order
            "initComplete": function() {
                // Show table only after initialization is complete (prevents flickering)
                $('#datatable').css('visibility', 'visible');
            }
        });
    }
    
    // Initialize after custom.js loads, but hide flickering
    setTimeout(initCategoryTable, 150);
    
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