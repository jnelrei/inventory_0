<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../production/includes/db.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit();
}

// Get user information from session
$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'] ?? 'Guest';
$userRole = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Admin';
$userInitials = '';

// Generate initials
if (!empty($userName)) {
    $parts = preg_split('/\s+/', trim($userName));
    foreach ($parts as $part) {
        $userInitials .= strtoupper(substr($part, 0, 1));
        if (strlen($userInitials) === 2) {
            break;
        }
    }
}
$userInitials = $userInitials ?: 'U';

// Fetch user data from database
$userData = null;
try {
    $stmt = $pdo->prepare("SELECT user_id, name, username, role, created_at, picture FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch();
} catch(PDOException $e) {
    $userData = null;
}

// Get profile picture path
$profilePicture = $userData['picture'] ?? null;
// Check if file exists (handle both absolute and relative paths)
if ($profilePicture) {
    $fullPath = (strpos($profilePicture, '/') === 0) ? $profilePicture : __DIR__ . '/' . $profilePicture;
    if (!file_exists($fullPath)) {
        $profilePicture = null; // Picture doesn't exist, show initials instead
    }
}

// Fetch recent inventory activities (if activity tracking exists, otherwise use created items)
$recentActivities = [];
try {
    $stmt = $pdo->prepare("SELECT item_id, item_name, created_at FROM invtry WHERE created_at IS NOT NULL ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recentActivities = $stmt->fetchAll();
} catch(PDOException $e) {
    $recentActivities = [];
}

// Fetch inventory stats
$stats = [
    'total_items' => 0,
    'total_categories' => 0,
    'total_value' => 0
];
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM invtry");
    $stats['total_items'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM category");
    $stats['total_categories'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT SUM(total_cost) as total FROM invtry");
    $result = $stmt->fetch();
    $stats['total_value'] = $result['total'] ?? 0;
} catch(PDOException $e) {
    // Keep defaults
}

include("../admin_components/header.php");
include("../admin_components/navigation.php");
include("../admin_components/sidebar.php");
include("../admin_components/top_navigation.php");
?>

<div class="col-md-12 col-sm-12 ">
  <div class="x_panel">
    <div class="x_title">
      <h2 class="section-title-sidebar">User Report <small>Activity report</small></h2>
      <ul class="nav navbar-right panel_toolbox">
        <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
          <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
            <a class="dropdown-item" href="javascript:;">Settings 1</a>
            <a class="dropdown-item" href="javascript:;">Settings 2</a>
          </div>
        </li>
        <li><a class="close-link"><i class="fa fa-close"></i></a></li>
      </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">
      <div class="col-md-3 col-sm-3  profile_left">
        <div class="profile_img">
          <div id="crop-avatar">
            <!-- Current avatar -->
            <?php 
            if ($profilePicture): 
              $imgPath = (strpos($profilePicture, '/') === 0) ? $profilePicture : $profilePicture;
              $fullPath = __DIR__ . '/' . $imgPath;
              if (file_exists($fullPath)): ?>
              <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="Profile Picture" class="avatar-view img-responsive" style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px; cursor: pointer;" onclick="editProfile()">
            <?php 
              else: ?>
              <div class="avatar-view" style="width: 100%; height: 200px; background: linear-gradient(135deg, #1ABB9C, #117a65); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 72px; font-weight: 600; cursor: pointer;" onclick="editProfile()">
                <?php echo htmlspecialchars($userInitials); ?>
              </div>
            <?php 
              endif;
            else: ?>
              <div class="avatar-view" style="width: 100%; height: 200px; background: linear-gradient(135deg, #1ABB9C, #117a65); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 72px; font-weight: 600; cursor: pointer;" onclick="editProfile()">
                <?php echo htmlspecialchars($userInitials); ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <h3><?php echo htmlspecialchars($userName); ?></h3>

        <ul class="list-unstyled user_data">
          <li><i class="fa fa-map-marker user-profile-icon"></i> Philippines</li>
          <li><i class="fa fa-briefcase user-profile-icon"></i> <?php echo htmlspecialchars($userRole); ?></li>
          <?php if ($userData && isset($userData['username'])): ?>
          <li class="m-top-xs">
            <i class="fa fa-user user-profile-icon"></i>
            <span><?php echo htmlspecialchars($userData['username']); ?></span>
          </li>
          <?php endif; ?>
          <?php if ($userData && isset($userData['created_at'])): ?>
          <li class="m-top-xs">
            <i class="fa fa-calendar user-profile-icon"></i>
            <span>Member since <?php echo date('M Y', strtotime($userData['created_at'])); ?></span>
          </li>
          <?php endif; ?>
        </ul>

        <a class="btn btn-success" href="javascript:;" onclick="editProfile()"><i class="fa fa-edit m-right-xs"></i>Edit Profile</a>
        <br />

        <!-- start skills -->
        <h4>System Skills</h4>
        <ul class="list-unstyled user_data">
          <li>
            <p>Inventory Management</p>
            <div class="progress progress_sm">
              <div class="progress-bar bg-green" role="progressbar" data-transitiongoal="85"></div>
            </div>
          </li>
          <li>
            <p>Sales Management</p>
            <div class="progress progress_sm">
              <div class="progress-bar bg-green" role="progressbar" data-transitiongoal="70"></div>
            </div>
          </li>
          <li>
            <p>Category Management</p>
            <div class="progress progress_sm">
              <div class="progress-bar bg-green" role="progressbar" data-transitiongoal="90"></div>
            </div>
          </li>
          <li>
            <p>Purchase Orders</p>
            <div class="progress progress_sm">
              <div class="progress-bar bg-green" role="progressbar" data-transitiongoal="65"></div>
            </div>
          </li>
        </ul>
        <!-- end of skills -->

      </div>
      <div class="col-md-9 col-sm-9 ">

        <div class="profile_title">
          <div class="col-md-6">
            <h2>User Activity Report</h2>
          </div>
          <div class="col-md-6">
            <div id="reportrange" class="pull-right" style="margin-top: 5px; background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #E6E9ED">
              <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>
              <span></span> <b class="caret"></b>
            </div>
          </div>
        </div>

        <!-- start stats -->
        <div class="row" style="margin-bottom: 10px;">
          <div class="col-md-4 col-sm-4" style="margin-bottom: 5px;">
            <div class="x_panel tile fixed_height_310">
              <div class="x_content">
                <div class="dashboard-widget-content">
                  <h4>Total Items</h4>
                  <h2><?php echo number_format($stats['total_items']); ?></h2>
                  <small>Inventory Items</small>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4 col-sm-4" style="margin-bottom: 5px;">
            <div class="x_panel tile fixed_height_310">
              <div class="x_content">
                <div class="dashboard-widget-content">
                  <h4>Categories</h4>
                  <h2><?php echo number_format($stats['total_categories']); ?></h2>
                  <small>Product Categories</small>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4 col-sm-4" style="margin-bottom: 5px;">
            <div class="x_panel tile fixed_height_310">
              <div class="x_content">
                <div class="dashboard-widget-content">
                  <h4>Total Value</h4>
                  <h2>₱<?php echo number_format($stats['total_value'], 2); ?></h2>
                  <small>Inventory Value</small>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- end stats -->

        <div class="" role="tabpanel" data-example-id="togglable-tabs">
          <ul id="myTab" class="nav nav-tabs bar_tabs" role="tablist">
            <li role="presentation" class="active"><a href="#tab_content1" id="home-tab" role="tab" data-toggle="tab" aria-expanded="true">Recent Activity</a></li>
            <li role="presentation" class=""><a href="#tab_content2" role="tab" id="profile-tab" data-toggle="tab" aria-expanded="false">Projects Worked on</a></li>
            <li role="presentation" class=""><a href="#tab_content3" role="tab" id="profile-tab2" data-toggle="tab" aria-expanded="false">Profile</a></li>
          </ul>
          <div id="myTabContent" class="tab-content">
            <div role="tabpanel" class="tab-pane active " id="tab_content1" aria-labelledby="home-tab">

              <!-- start recent activity -->
              <ul class="messages">
                <?php if (empty($recentActivities)): ?>
                  <li>
                    <div class="message_wrapper">
                      <h4 class="heading">No recent activity</h4>
                      <blockquote class="message">Start managing your inventory to see activities here.</blockquote>
                    </div>
                  </li>
                <?php else: ?>
                  <?php foreach ($recentActivities as $activity): ?>
                    <li>
                      <div class="avatar-view" style="width: 50px; height: 50px; background: linear-gradient(135deg, #1ABB9C, #117a65); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #fff; font-size: 20px; font-weight: 600; float: left; margin-right: 15px;">
                        <?php echo strtoupper(substr($activity['item_name'], 0, 1)); ?>
                      </div>
                      <div class="message_date">
                        <h3 class="date text-info"><?php echo date('d', strtotime($activity['created_at'])); ?></h3>
                        <p class="month"><?php echo date('M', strtotime($activity['created_at'])); ?></p>
                      </div>
                      <div class="message_wrapper">
                        <h4 class="heading"><?php echo htmlspecialchars($activity['item_name']); ?></h4>
                        <blockquote class="message">New inventory item added to the system.</blockquote>
                        <br />
                        <p class="url">
                          <span class="fs1 text-info" aria-hidden="true" data-icon=""></span>
                          <a href="../inventory/inventory.php"><i class="fa fa-archive"></i> View in Inventory</a>
                        </p>
                      </div>
                    </li>
                  <?php endforeach; ?>
                <?php endif; ?>
              </ul>
              <!-- end recent activity -->

            </div>
            <div role="tabpanel" class="tab-pane fade" id="tab_content2" aria-labelledby="profile-tab">

              <!-- start user projects -->
              <table class="data table table-striped no-margin">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Project Name</th>
                    <th>Category</th>
                    <th class="hidden-phone">Items</th>
                    <th>Progress</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  // Fetch categories with item counts
                  $projects = [];
                  try {
                    $stmt = $pdo->query("
                      SELECT c.category_id, c.category_name, COUNT(i.item_id) as item_count 
                      FROM category c 
                      LEFT JOIN invtry i ON c.category_id = i.category_id 
                      GROUP BY c.category_id, c.category_name 
                      ORDER BY item_count DESC 
                      LIMIT 10
                    ");
                    $projects = $stmt->fetchAll();
                  } catch(PDOException $e) {
                    $projects = [];
                  }
                  
                  if (empty($projects)): ?>
                    <tr>
                      <td colspan="5" class="text-center">No projects available</td>
                    </tr>
                  <?php else: ?>
                    <?php $counter = 1; ?>
                    <?php foreach ($projects as $project): ?>
                      <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><?php echo htmlspecialchars($project['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($project['category_name']); ?></td>
                        <td class="hidden-phone"><?php echo $project['item_count']; ?></td>
                        <td class="vertical-align-mid">
                          <?php 
                          $maxItems = max(array_column($projects, 'item_count'));
                          $progress = $maxItems > 0 ? ($project['item_count'] / $maxItems * 100) : 0;
                          ?>
                          <div class="progress">
                            <div class="progress-bar progress-bar-success" data-transitiongoal="<?php echo round($progress); ?>" style="width: <?php echo round($progress); ?>%;"></div>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
              <!-- end user projects -->

            </div>
            <div role="tabpanel" class="tab-pane fade" id="tab_content3" aria-labelledby="profile-tab">
              <div class="row">
                <div class="col-md-12">
                  <h4>Profile Information</h4>
                  <table class="table table-striped">
                    <tbody>
                      <tr>
                        <th width="30%">Name:</th>
                        <td><?php echo htmlspecialchars($userName); ?></td>
                      </tr>
                      <?php if ($userData && isset($userData['username'])): ?>
                      <tr>
                        <th>Username:</th>
                        <td><?php echo htmlspecialchars($userData['username']); ?></td>
                      </tr>
                      <?php endif; ?>
                      <tr>
                        <th>Role:</th>
                        <td><?php echo htmlspecialchars($userRole); ?></td>
                      </tr>
                      <?php if ($userData && isset($userData['created_at'])): ?>
                      <tr>
                        <th>Member Since:</th>
                        <td><?php echo date('F d, Y', strtotime($userData['created_at'])); ?></td>
                      </tr>
                      <?php endif; ?>
                      <tr>
                        <th>Account ID:</th>
                        <td>#<?php echo htmlspecialchars($userId); ?></td>
                      </tr>
                    </tbody>
                  </table>
                  
                  <p class="text-muted">
                    Welcome to Tumandok Crafts Industries inventory management system. You are currently logged in as <?php echo htmlspecialchars($userRole); ?>. 
                    This profile page shows your activity and contributions to the system.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog" aria-labelledby="editProfileModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content" style="border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
      <div class="modal-header" style="background: #26B99A; border-bottom: none; padding: 20px 25px;">
        <h5 class="modal-title" id="editProfileModalLabel" style="font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #ffffff; font-size: 18px; margin: 0;">Edit Profile</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #ffffff; opacity: 0.9; text-shadow: none;">
          <span aria-hidden="true" style="font-size: 28px;">&times;</span>
        </button>
      </div>
      <div class="modal-body" style="padding: 30px;">
        <form id="editProfileForm" enctype="multipart/form-data">
          <input type="hidden" id="existing_picture" name="existing_picture" value="<?php echo htmlspecialchars($profilePicture ?? ''); ?>">
          <input type="hidden" id="delete_picture" name="delete_picture" value="0">
          
          <div class="row">
            <!-- Left Column: Profile Picture -->
            <div class="col-md-5">
              <div class="form-group">
                <label style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 10px; display: block;">Profile Picture</label>
                <div id="profileImagePreview" style="text-align: center; margin-bottom: 15px; background: #f8f9fa; border-radius: 8px; padding: 15px;">
                  <?php 
                  if ($profilePicture): 
                    $imgPath = (strpos($profilePicture, '/') === 0) ? $profilePicture : $profilePicture;
                    $fullPath = __DIR__ . '/' . $imgPath;
                    if (file_exists($fullPath)): ?>
                    <img id="previewImg" src="<?php echo htmlspecialchars($imgPath); ?>" alt="Profile Picture" style="max-width: 100%; max-height: 280px; border-radius: 6px; border: 2px solid #ddd; padding: 10px; background: #ffffff;">
                  <?php 
                    else: ?>
                    <div style="width: 100%; height: 280px; background: linear-gradient(135deg, #1ABB9C, #117a65); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 80px; font-weight: 600;">
                      <?php echo htmlspecialchars($userInitials); ?>
                    </div>
                  <?php 
                    endif;
                  else: ?>
                    <div style="width: 100%; height: 280px; background: linear-gradient(135deg, #1ABB9C, #117a65); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 80px; font-weight: 600;">
                      <?php echo htmlspecialchars($userInitials); ?>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="clean-file-upload-wrapper">
                  <input type="file" class="clean-file-input" id="profile_picture" name="picture" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" onchange="handleProfileImagePreview(this)">
                  <label for="profile_picture" class="clean-file-label">
                    <div class="file-icon-wrapper">
                      <i class="fa fa-upload"></i>
                    </div>
                    <div class="file-label-content">
                      <span class="file-main-text">Choose Image File</span>
                      <span class="file-selected-name" id="profile_file_name"></span>
                    </div>
                    <div class="file-choose-btn">
                      <i class="fa fa-folder-open"></i>
                      <span>Choose</span>
                    </div>
                  </label>
                  <div class="file-info-text">
                    <span>Maximum file size: 5MB</span>
                    <span class="info-dot">•</span>
                    <span>Accepted formats: JPEG, PNG, GIF, WebP</span>
                  </div>
                </div>
                <?php 
                if ($profilePicture): 
                  $imgPath = (strpos($profilePicture, '/') === 0) ? $profilePicture : $profilePicture;
                  $fullPath = __DIR__ . '/' . $imgPath;
                  if (file_exists($fullPath)): ?>
                  <button type="button" class="btn btn-danger btn-sm mt-2" onclick="deleteProfilePicture()" style="width: 100%;">
                    <i class="fa fa-trash"></i> Delete Picture
                  </button>
                <?php 
                  endif;
                endif; ?>
              </div>
            </div>

            <!-- Right Column: Form Fields -->
            <div class="col-md-7" style="padding-left: 25px;">
              <div class="form-group" style="margin-bottom: 20px;">
                <label for="edit_name" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Full Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="edit_name" name="name" value="<?php echo htmlspecialchars($userName); ?>" required style="border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 12px; font-size: 14px;">
              </div>

              <div class="form-group" style="margin-bottom: 20px;">
                <label for="edit_username" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Username <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="edit_username" name="username" value="<?php echo htmlspecialchars($userData['username'] ?? ''); ?>" required style="border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 12px; font-size: 14px;">
              </div>

              <div class="form-group" style="margin-bottom: 0;">
                <label style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Role</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($userRole); ?>" disabled style="background-color: #e9ecef; border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 12px; font-size: 14px;">
                <small class="form-text text-muted">Role cannot be changed from here</small>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 15px 25px;">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" onclick="saveProfile()" style="background-color: #26B99A; border-color: #26B99A;">
          <i class="fa fa-save"></i> Save Changes
        </button>
      </div>
    </div>
  </div>
</div>

<?php include("../admin_components/footer.php")?>
<?php include("../../production/includes/fd.php")?>

<script>
$(document).ready(function() {
  // Initialize date range picker
  if ($('#reportrange span').length) {
    $('#reportrange').daterangepicker({
      startDate: moment().subtract(29, 'days'),
      endDate: moment(),
      ranges: {
        'Today': [moment(), moment()],
        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
        'This Month': [moment().startOf('month'), moment().endOf('month')],
        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
      }
    }, function(start, end) {
      $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
    });
    
    $('#reportrange span').html(moment().subtract(29, 'days').format('MMMM D, YYYY') + ' - ' + moment().format('MMMM D, YYYY'));
  }

  // Initialize progress bars
  $('.progress .progress-bar').each(function() {
    var $this = $(this);
    var goal = $this.data('transitiongoal');
    if (goal) {
      $this.css('width', goal + '%');
    }
  });

  // Initialize bootstrap-progressbar
  if ($.fn.bootstrapProgressbar) {
    $('.progress .progress-bar').bootstrapProgressbar({
      transition_delay: 1500,
      refresh_speed: 50
    });
  }
});

function editProfile() {
  $('#editProfileModal').modal('show');
}

function handleProfileImagePreview(input) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
      $('#previewImg').attr('src', e.target.result).show();
      $('#profileImagePreview div').hide();
      $('#profile_file_name').text(input.files[0].name);
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function deleteProfilePicture() {
  Swal.fire({
    title: 'Are you sure?',
    text: "Do you want to delete your profile picture?",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      $('#delete_picture').val('1');
      $('#profileImagePreview img').hide();
      $('#profileImagePreview').html('<div style="width: 100%; height: 280px; background: linear-gradient(135deg, #1ABB9C, #117a65); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 80px; font-weight: 600;"><?php echo htmlspecialchars($userInitials); ?></div>');
      $('#profile_picture').val('');
      $('#profile_file_name').text('');
      
      Swal.fire({
        icon: 'success',
        title: 'Picture Removed',
        text: 'The picture will be deleted when you save your profile.',
        confirmButtonColor: '#1ABB9C',
        timer: 2000,
        timerProgressBar: true
      });
    }
  });
}

function saveProfile() {
  var formData = new FormData($('#editProfileForm')[0]);
  
  // Show loading
  Swal.fire({
    title: 'Saving...',
    html: 'Please wait while we update your profile.',
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });
  
  $.ajax({
    url: 'update_profile.php',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    dataType: 'json',
    success: function(response) {
      if (response.success) {
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: response.message,
          confirmButtonColor: '#1ABB9C',
          timer: 2000,
          timerProgressBar: true
        }).then(() => {
          location.reload();
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          html: response.message,
          confirmButtonColor: '#1ABB9C'
        });
      }
    },
    error: function(xhr, status, error) {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'An error occurred: ' + error,
        confirmButtonColor: '#1ABB9C'
      });
    }
  });
}
</script>

<style>
.profile_left {
  padding: 20px;
}

.profile_img {
  margin-bottom: 20px;
}

.profile_left h3 {
  margin-top: 20px;
  margin-bottom: 15px;
  color: #2A3F54;
  font-weight: 600;
}

.user-profile-icon {
  width: 25px;
  text-align: center;
  color: #73879C;
}

.list-unstyled.user_data {
  padding-left: 0;
  list-style: none;
}

.list-unstyled.user_data li {
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
}

.list-unstyled.user_data li:last-child {
  border-bottom: none;
}

.profile_title {
  margin-bottom: 20px;
}

.profile_title h2 {
  margin: 0;
  color: #2A3F54;
}

.messages {
  list-style: none;
  padding: 0;
}

.messages li {
  padding: 15px 0;
  border-bottom: 1px solid #f0f0f0;
  position: relative;
  min-height: 70px;
}

.messages li:last-child {
  border-bottom: none;
}

.message_date {
  float: left;
  width: 60px;
  margin-right: 15px;
  text-align: center;
}

.message_date .date {
  font-size: 24px;
  font-weight: 600;
  margin: 0;
  line-height: 1;
}

.message_date .month {
  font-size: 12px;
  text-transform: uppercase;
  color: #73879C;
  margin: 0;
}

.message_wrapper {
  margin-left: 75px;
}

.message_wrapper .heading {
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 5px 0;
  color: #2A3F54;
}

.message_wrapper .message {
  font-size: 14px;
  color: #73879C;
  border-left: none;
  padding: 0;
  margin: 0 0 10px 0;
}

.message_wrapper .url a {
  color: #1ABB9C;
  text-decoration: none;
}

.message_wrapper .url a:hover {
  text-decoration: underline;
}

.bar_tabs {
  border-bottom: 2px solid #E6E9ED;
}

.bar_tabs li.active a {
  border-bottom: 2px solid #1ABB9C;
  color: #1ABB9C;
}

.tile {
  background: #fff;
  border: 1px solid #E6E9ED;
  border-radius: 4px;
  padding: 15px;
  margin-bottom: 20px;
}

.tile .x_content {
  padding: 10px 0;
}

.dashboard-widget-content h4 {
  color: #73879C;
  font-size: 14px;
  margin: 0 0 10px 0;
}

.dashboard-widget-content h2 {
  color: #2A3F54;
  font-size: 32px;
  font-weight: 600;
  margin: 0 0 5px 0;
}

.dashboard-widget-content small {
  color: #73879C;
  font-size: 12px;
}

.fixed_height_320 {
  height: 120px;
}

.table-striped > tbody > tr:nth-of-type(odd) {
  background-color: #f9f9f9;
}

/* File Upload Styles */
.clean-file-upload-wrapper {
  position: relative;
}

.clean-file-input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.clean-file-label {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: #ffffff;
  border: 2px dashed #dee2e6;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
  gap: 12px;
}

.clean-file-label:hover {
  border-color: #26B99A;
  background: #f8fff9;
}

.file-icon-wrapper {
  color: #26B99A;
  font-size: 20px;
  display: flex;
  align-items: center;
}

.file-label-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.file-main-text {
  color: #2A3F54;
  font-weight: 500;
  font-size: 14px;
}

.file-selected-name {
  color: #73879C;
  font-size: 12px;
  display: none;
}

.file-selected-name:not(:empty) {
  display: block;
}

.file-choose-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: #26B99A;
  color: white;
  border-radius: 4px;
  font-size: 13px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.clean-file-label:hover .file-choose-btn {
  background: #1a9a7f;
}

.file-info-text {
  margin-top: 8px;
  font-size: 11px;
  color: #73879C;
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.info-dot {
  color: #adb5bd;
}
</style>