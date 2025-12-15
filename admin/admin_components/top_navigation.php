<?php
// Ensure session is started so we can access logged-in user information
if (session_status() === PHP_SESSION_NONE) {
<<<<<<< HEAD
    session_start();
=======
  session_start();
>>>>>>> bffd17eb2ccfbbfa430d2dfe62f4af6da5ab7e21
}

$userName = $_SESSION['name'] ?? 'Guest';
$userRole = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Visitor';
$userInitials = '';

if (!empty($userName)) {
<<<<<<< HEAD
    $parts = preg_split('/\s+/', trim($userName));
    foreach ($parts as $part) {
        $userInitials .= strtoupper(substr($part, 0, 1));
        if (strlen($userInitials) === 2) {
            break;
        }
    }
}
$userInitials = $userInitials ?: 'U';
?>
<style>
.user-profile {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 6px 16px;
  border-radius: 999px;
  transition: background-color 0.2s;
  color: #2f4050;
  font-weight: 500;
}
.user-profile.dropdown-toggle::after {
  display: none;
}
.user-profile:hover {
  background-color: rgba(26, 187, 156, 0.1);
  text-decoration: none;
}
.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #1ABB9C, #117a65);
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}
.user-meta {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}
.user-name {
  font-size: 14px;
  font-weight: 600;
  color: #2f4050;
}
.user-role {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #8897a2;
}
.user-chevron {
  margin-left: 6px;
  font-size: 12px;
  color: #98a6ad;
  transition: transform 0.2s ease, color 0.2s ease;
}
.user-profile.dropdown-toggle[aria-expanded="true"] .user-chevron {
  transform: rotate(180deg);
  color: #1ABB9C;
}
.dropdown-usermenu {
  min-width: 240px;
  border: none;
  border-radius: 12px;
  padding: 0;
  overflow: hidden;
  box-shadow: 0 20px 35px rgba(15, 23, 42, 0.18);
}
.dropdown-user-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px;
  background: linear-gradient(135deg, #f0fff6, #e5fffb);
  border-bottom: 1px solid rgba(0,0,0,0.05);
}
.dropdown-user-card .user-avatar {
  width: 44px;
  height: 44px;
  font-size: 18px;
}
.dropdown-user-card .user-name {
  font-size: 15px;
}
.dropdown-user-card .user-role {
  font-size: 11px;
}
.dropdown-actions {
  display: flex;
  gap: 8px;
  padding: 12px 20px;
  border-bottom: 1px solid #f0f2f5;
  background-color: #fff;
}
.dropdown-actions a {
  flex: 1;
  border: 1px solid #e1e6ed;
  border-radius: 10px;
  padding: 8px 10px;
  text-align: center;
  font-size: 12px;
  color: #2f4050;
  display: inline-flex;
  flex-direction: column;
  gap: 2px;
  transition: all 0.2s ease;
}
.dropdown-actions a i {
  color: #1ABB9C;
}
.dropdown-actions a:hover {
  border-color: #1ABB9C;
  box-shadow: 0 8px 15px rgba(26, 187, 156, 0.15);
}
.dropdown-usermenu .dropdown-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 20px;
  color: #2f4050;
  transition: all 0.2s ease;
}
.dropdown-usermenu .dropdown-item i {
  width: 18px;
  text-align: center;
  color: #8897a2;
}
.dropdown-usermenu .dropdown-item:hover {
  background-color: #f7f9fb;
  color: #1f2a37;
}
.dropdown-usermenu .dropdown-item:hover i {
  color: #1ABB9C;
}
.dropdown-usermenu .dropdown-item.logout {
  color: #dc3545;
}
.dropdown-usermenu .dropdown-item.logout:hover {
  background-color: #fff5f5;
}
.nav.toggle {
  display: flex;
  align-items: center;
  padding-top: 12px;
}
.nav.toggle #menu_toggle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 36px;
}
.left_col .nav_title {
  display: flex;
  align-items: center;
  min-height: 78px;
}
.left_col .nav_title .site_title {
  display: flex !important;
  align-items: center;
  gap: 10px;
  padding: 12px 0;
}
.left_col .nav_title img {
  margin-top: 4px;
}
.left_col .nav_title span {
  margin-top: 4px;
}
</style>
<!-- top navigation -->
        <div class="top_nav">
            <div class="nav_menu">
                <div class="nav toggle">
                  <a id="menu_toggle"><i class="fa fa-bars"></i></a>
                </div>
                <nav class="nav navbar-nav">
                <ul class=" navbar-right">
                  <li class="nav-item dropdown open" style="padding-left: 15px;">
                    <a href="javascript:;" class="user-profile dropdown-toggle" aria-haspopup="true" id="navbarDropdown" data-toggle="dropdown" aria-expanded="false">
                      <span class="user-avatar"><?php echo htmlspecialchars($userInitials); ?></span>
                      <span class="user-meta">
                        <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars($userRole); ?></span>
                      </span>
                      <i class="fa fa-chevron-down user-chevron"></i>
                    </a>
                    <div class="dropdown-menu dropdown-usermenu pull-right" aria-labelledby="navbarDropdown">
                      <div class="dropdown-user-card">
                        <span class="user-avatar"><?php echo htmlspecialchars($userInitials); ?></span>
                        <div>
                          <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
                          <div class="user-role"><?php echo htmlspecialchars($userRole); ?></div>
                        </div>
                      </div>
                      <div class="dropdown-actions">
                        <a href="javascript:;">
                          <i class="fa fa-clipboard"></i>
                          <span>Activity</span>
                        </a>
                        <a href="javascript:;">
                          <i class="fa fa-bell"></i>
                          <span>Alerts</span>
                        </a>
                      </div>
                      <a class="dropdown-item" href="../profile/profile.php">
                        <i class="fa fa-user"></i>
                        <span>Profile</span>
                      </a>
                      <a class="dropdown-item" href="javascript:;">
                        <i class="fa fa-cog"></i>
                        <span>Settings</span>
                      </a>
                      <div class="dropdown-divider" style="margin: 0;"></div>
                      <a class="dropdown-item logout" href="javascript:void(0);" onclick="confirmLogout()">
                        <i class="fa fa-sign-out"></i>
                        <span>Log Out</span>
                      </a>
                    </div>
                  </li>
                </ul>
              </nav>
            </div>
          </div>
        <!-- /top navigation -->
         
        <!-- page content -->
        <div class="right_col" role="main">
=======
  $parts = preg_split('/\s+/', trim($userName));
  foreach ($parts as $part) {
    $userInitials .= strtoupper(substr($part, 0, 1));
    if (strlen($userInitials) === 2) {
      break;
    }
  }
}
$userInitials = $userInitials ?: 'U';

// Include low stock data fetching
require_once(__DIR__ . '/get_low_stock.php');
?>
<style>
  .user-profile {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 16px;
    border-radius: 999px;
    transition: background-color 0.2s;
    color: #2f4050;
    font-weight: 500;
  }

  .user-profile.dropdown-toggle::after {
    display: none;
  }

  .user-profile:hover {
    background-color: rgba(26, 187, 156, 0.1);
    text-decoration: none;
  }

  .user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1ABB9C, #117a65);
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
  }

  .user-meta {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
  }

  .user-name {
    font-size: 14px;
    font-weight: 600;
    color: #2f4050;
  }

  .user-role {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #8897a2;
  }

  .user-chevron {
    margin-left: 6px;
    font-size: 12px;
    color: #98a6ad;
    transition: transform 0.2s ease, color 0.2s ease;
  }

  .user-profile.dropdown-toggle[aria-expanded="true"] .user-chevron {
    transform: rotate(180deg);
    color: #1ABB9C;
  }

  .dropdown-usermenu {
    min-width: 240px;
    border: none;
    border-radius: 12px;
    padding: 0;
    overflow: hidden;
    box-shadow: 0 20px 35px rgba(15, 23, 42, 0.18);
  }

  .dropdown-user-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px;
    background: linear-gradient(135deg, #f0fff6, #e5fffb);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  }

  .dropdown-user-card .user-avatar {
    width: 44px;
    height: 44px;
    font-size: 18px;
  }

  .dropdown-user-card .user-name {
    font-size: 15px;
  }

  .dropdown-user-card .user-role {
    font-size: 11px;
  }

  .dropdown-actions {
    display: flex;
    gap: 8px;
    padding: 12px 20px;
    border-bottom: 1px solid #f0f2f5;
    background-color: #fff;
  }

  .dropdown-actions a {
    flex: 1;
    border: 1px solid #e1e6ed;
    border-radius: 10px;
    padding: 8px 10px;
    text-align: center;
    font-size: 12px;
    color: #2f4050;
    display: inline-flex;
    flex-direction: column;
    gap: 2px;
    transition: all 0.2s ease;
  }

  .dropdown-actions a i {
    color: #1ABB9C;
  }

  .dropdown-actions a:hover {
    border-color: #1ABB9C;
    box-shadow: 0 8px 15px rgba(26, 187, 156, 0.15);
  }

  .dropdown-usermenu .dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: #2f4050;
    transition: all 0.2s ease;
  }

  .dropdown-usermenu .dropdown-item i {
    width: 18px;
    text-align: center;
    color: #8897a2;
  }

  .dropdown-usermenu .dropdown-item:hover {
    background-color: #f7f9fb;
    color: #1f2a37;
  }

  .dropdown-usermenu .dropdown-item:hover i {
    color: #1ABB9C;
  }

  .dropdown-usermenu .dropdown-item.logout {
    color: #dc3545;
  }

  .dropdown-usermenu .dropdown-item.logout:hover {
    background-color: #fff5f5;
  }

  .nav.toggle {
    display: flex;
    align-items: center;
    padding-top: 12px;
  }

  .nav.toggle #menu_toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 36px;
  }

  .left_col .nav_title {
    display: flex;
    align-items: center;
    min-height: 78px;
  }

  .left_col .nav_title .site_title {
    display: flex !important;
    align-items: center;
    gap: 10px;
    padding: 12px 0;
  }

  .left_col .nav_title img {
    margin-top: 4px;
  }

  .left_col .nav_title span {
    margin-top: 4px;
  }

  .notification-bell {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    border: 2px solid #98a6ad;
    color: #2f4050;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-left: 12px;
    margin-top: 5px;
  }

  .notification-bell.dropdown-toggle::after {
    display: none;
  }

  .notification-bell:hover {
    background-color: rgba(26, 187, 156, 0.1);
    border-color: #1ABB9C;
    color: #1ABB9C;
  }

  .notification-bell .fa-bell {
    position: relative;
  }

  .notification-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: #fff;
    border-radius: 10px;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 600;
    padding: 0 4px;
    box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
    border: 2px solid #fff;
  }

  .notification-dropdown {
    min-width: 320px;
    max-width: 380px;
    width: 350px;
    border: none;
    border-radius: 12px;
    padding: 0;
    overflow: hidden;
    box-shadow: 0 20px 35px rgba(15, 23, 42, 0.18);
    margin-right: -240px;
    transform: translateX(-175px);
  }

  .notification-header {
    padding: 16px 20px;
    background: #ffffff;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .notification-header h6 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    color: #2f4050;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .notification-header .mark-all-read {
    font-size: 12px;
    color: #1ABB9C;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
    padding: 4px 8px;
    border-radius: 4px;
  }

  .notification-header .mark-all-read:hover {
    color: #117a65;
    background-color: rgba(26, 187, 156, 0.1);
  }

  .notification-body {
    max-height: 270px;
    overflow-y: auto;
    overflow-x: hidden;
  }

  .notification-body::-webkit-scrollbar {
    width: 6px;
  }

  .notification-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
  }

  .notification-body::-webkit-scrollbar-thumb {
    background: #dc3545;
    border-radius: 10px;
  }

  .notification-body::-webkit-scrollbar-thumb:hover {
    background: #c82333;
  }

  .notification-item {
    padding: 12px 20px;
    border-bottom: 1px solid #f0f2f5;
    transition: background-color 0.2s;
    cursor: pointer;
  }

  .notification-item:hover {
    background-color: #f7f9fb;
  }

  .notification-item.unread {
    background: linear-gradient(135deg, #fff5f5, #ffe5e5);
    border-left: 4px solid #dc3545;
    border-radius: 0 8px 8px 0;
    margin: 4px 0;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.1);
  }

  .notification-item.unread:hover {
    background: linear-gradient(135deg, #ffe5e5, #ffd5d5);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.15);
  }

  .notification-item .notification-content {
    display: flex;
    gap: 12px;
  }

  .notification-item.unread .notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: #fff;
    font-size: 16px;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
  }

  .notification-item .notification-text {
    flex: 1;
  }

  .notification-item.unread .notification-text .title {
    font-size: 14px;
    font-weight: 700;
    color: #dc3545;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .notification-item.unread .notification-text .message {
    font-size: 13px;
    color: #721c24;
    line-height: 1.5;
    font-weight: 500;
  }

  .notification-item .notification-time {
    font-size: 11px;
    color: #98a6ad;
    margin-top: 4px;
  }

  .notification-empty {
    padding: 40px 20px;
    text-align: center;
    color: #98a6ad;
  }

  .notification-empty i {
    font-size: 48px;
    margin-bottom: 12px;
    opacity: 0.3;
  }

  .notification-empty p {
    margin: 0;
    font-size: 14px;
  }
</style>
<!-- top navigation -->
<div class="top_nav">
  <div class="nav_menu">
    <div class="nav toggle">
      <a id="menu_toggle"><i class="fa fa-bars"></i></a>
    </div>
    <nav class="nav navbar-nav">
      <ul class=" navbar-right">
        <li class="nav-item dropdown open" style="padding-right: 8px;">
          <a href="javascript:;" class="user-profile dropdown-toggle" aria-haspopup="true" id="navbarDropdown" data-toggle="dropdown" aria-expanded="false">
            <span class="user-avatar"><?php echo htmlspecialchars($userInitials); ?></span>
            <span class="user-meta">
              <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
              <span class="user-role"><?php echo htmlspecialchars($userRole); ?></span>
            </span>
            <i class="fa fa-chevron-down user-chevron"></i>
          </a>
          <div class="dropdown-menu dropdown-usermenu pull-right" aria-labelledby="navbarDropdown">
            <div class="dropdown-user-card">
              <span class="user-avatar"><?php echo htmlspecialchars($userInitials); ?></span>
              <div>
                <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($userRole); ?></div>
              </div>
            </div>
            <a class="dropdown-item" href="javascript:;">
              <i class="fa fa-user"></i>
              <span>Profile</span>
            </a>
            <a class="dropdown-item" href="javascript:;">
              <i class="fa fa-cog"></i>
              <span>Settings</span>
            </a>
            <div class="dropdown-divider" style="margin: 0;"></div>
            <a class="dropdown-item logout" href="javascript:void(0);" onclick="confirmLogout()">
              <i class="fa fa-sign-out"></i>
              <span>Log Out</span>
            </a>
          </div>
        </li>
        <li class="nav-item dropdown" style="padding-left: 8px;">
          <a href="javascript:;" class="notification-bell dropdown-toggle" aria-haspopup="true" id="notificationDropdown" data-toggle="dropdown" aria-expanded="false">
            <i class="fa fa-bell"></i>
            <?php if ($low_stock_count > 0): ?>
              <span class="notification-badge"><?php echo $low_stock_count; ?></span>
            <?php endif; ?>
          </a>
          <div class="dropdown-menu notification-dropdown pull-right" aria-labelledby="notificationDropdown">
            <div class="notification-header">
              <h6>Notifications</h6>
            </div>
            <div class="notification-body">
              <?php if ($low_stock_count > 0): ?>
                <?php foreach ($low_stock_items as $item): ?>
              <div class="notification-item unread">
                <div class="notification-content">
                  <div class="notification-icon">
                    <i class="fa fa-exclamation"></i>
                  </div>
                  <div class="notification-text">
                    <div class="title">Low Stock Alert</div>
                        <div class="message">Item "<?php echo htmlspecialchars($item['item_name']); ?>" is running low on stock (<?php echo htmlspecialchars($item['quantity']); ?> remaining)</div>
                </div>
              </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="notification-empty">
                  <i class="fa fa-bell-slash"></i>
                  <p>No low stock items</p>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </li>
      </ul>
    </nav>
  </div>
</div>
<!-- /top navigation -->

<!-- page content -->
<div class="right_col" role="main">
>>>>>>> bffd17eb2ccfbbfa430d2dfe62f4af6da5ab7e21
