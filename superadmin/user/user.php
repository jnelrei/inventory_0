<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../production/includes/db.php");

// Fetch users data from database (excluding superadmin and admin roles)
try {
    $stmt = $pdo->query("SELECT user_id, name, username, password, role, created_at, picture FROM users WHERE role NOT IN ('superadmin', 'admin') ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
} catch(PDOException $e) {
    $users = [];
    $error_message = "Error loading users: " . $e->getMessage();
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
                      USERS
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
                    $error_message_user = '';
                    
                    if (isset($_SESSION['user_message'])) {
                        if ($_SESSION['user_success']) {
                            $show_success_alert = true;
                            $success_message = $_SESSION['user_message'];
                        } else {
                            $show_error_alert = true;
                            $error_message_user = $_SESSION['user_message'];
                        }
                        unset($_SESSION['user_message']);
                        unset($_SESSION['user_success']);
                    }
                    ?>
                    
                    <?php if ($show_error_alert): ?>
                      <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error_message_user); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                    <?php endif; ?>
                    
                    <table id="datatable" class="table table-striped table-bordered" style="width:100%; visibility: hidden;">
                      <thead>
                        <tr>
                          <th>Picture</th>
                          <th>Name</th>
                          <th>Username</th>
                          <th>Role</th>
                          <th>Created At</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (empty($users)): ?>
                          <tr>
                            <td colspan="5" class="text-center">No users found.</td>
                          </tr>
                        <?php else: ?>
                          <?php foreach ($users as $user): ?>
                            <tr>
                              <td style="text-align: center;">
                                <?php 
                                $picture_path = isset($user['picture']) && !empty($user['picture']) ? htmlspecialchars($user['picture']) : '';
                                if (!empty($picture_path)): 
                                  // Check if it's an absolute URL or relative path
                                  if (strpos($picture_path, 'http') === 0) {
                                    $img_path = $picture_path;
                                  } else {
                                    // Fetch from ../../images/ directory
                                    $img_path = '../../images/' . basename($picture_path);
                                  }
                                ?>
                                  <img src="<?php echo $img_path; ?>" alt="<?php echo htmlspecialchars($user['name']); ?>" style="max-width: 80px; max-height: 80px; object-fit: cover; border-radius: 50%;" onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2780%27 height=%2780%27%3E%3Ccircle fill=%27%23ddd%27 cx=%2740%27 cy=%2740%27 r=%2740%27/%3E%3Ctext fill=%27%23999%27 font-family=%27sans-serif%27 font-size=%2714%27 dy=%2710.5%27 font-weight=%27bold%27 x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27%3ENo Image%3C/text%3E%3C/svg%3E';">
                                <?php else: ?>
                                  <img src="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2780%27 height=%2780%27%3E%3Ccircle fill=%27%23ddd%27 cx=%2740%27 cy=%2740%27 r=%2740%27/%3E%3Ctext fill=%27%23999%27 font-family=%27sans-serif%27 font-size=%2714%27 dy=%2710.5%27 font-weight=%27bold%27 x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27%3ENo Image%3C/text%3E%3C/svg%3E" alt="No Image" style="max-width: 80px; max-height: 80px; object-fit: cover; border-radius: 50%; opacity: 0.7;">
                                <?php endif; ?>
                              </td>
                              <td><?php echo htmlspecialchars($user['name']); ?></td>
                              <td><?php echo htmlspecialchars($user['username']); ?></td>
                              <td>
                                  <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                              </td>
                              <td><?php echo !empty($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) . ' at ' . date('h:i A', strtotime($user['created_at'])) : 'N/A'; ?></td>
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
    function initUserTable() {
        // Check if DataTables is available
        if (typeof $.fn.DataTable === 'undefined') {
            setTimeout(initUserTable, 50);
            return;
        }
        
        // Destroy existing DataTable instance if it exists (from custom.js)
        if ($.fn.DataTable.isDataTable('#datatable')) {
            $('#datatable').DataTable().destroy();
        }
        
        // Initialize DataTable with custom configuration
        $('#datatable').DataTable({
            "order": [[4, 'desc']], // Sort by Created At column (index 4) in descending order
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
    setTimeout(initUserTable, 150);
    
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