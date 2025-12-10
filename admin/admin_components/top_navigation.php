<?php
// Ensure session is started so we can access logged-in user information
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userName = $_SESSION['name'] ?? 'Guest';
$userRole = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Visitor';
$userInitials = '';

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