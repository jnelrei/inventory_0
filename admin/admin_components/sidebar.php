<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$shouldAnimateIntro = !empty($_SESSION['show_intro_animation']);
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<?php if ($shouldAnimateIntro): ?>
  <style>
  #sidebar-menu {
    opacity: 0;
    transform: translateX(-40px);
    animation: sidebarSlideIn 0.6s ease-out forwards;
    animation-delay: 0.35s;
  }
  @keyframes sidebarSlideIn {
    from {
      opacity: 0;
      transform: translateX(-40px);
    }
    to {
      opacity: 1;
      transform: translateX(0);
    }
  }
  </style>
<?php endif; ?>
<style>
.nav.side-menu li a {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0;
  padding: 10px 14px;
  transition: all 0.2s ease;
  color: #2f4050;
  font-weight: 500;
}
.nav.side-menu li a i {
  color: #8a9aa3;
  transition: color 0.2s ease;
  font-size: 18px;
}
.nav.side-menu li a:hover {
  background: rgba(26, 187, 156, 0.12);
  color: #13866a;
}
.nav.side-menu li a:hover i {
  color: #1ABB9C;
}
.nav.side-menu li.active > a {
  background: linear-gradient(135deg, #1ABB9C, #117a65);
  color: #fff;
  box-shadow: 0 10px 20px rgba(26, 187, 156, 0.25);
  position: relative;
}
.nav.side-menu li.active > a i {
  color: #fff;
}
.nav.side-menu li a .menu-label {
  flex: 1;
  white-space: nowrap;
}
.nav-sm .nav.side-menu li a {
  justify-content: center;
  padding: 14px 0;
}
.nav-sm .nav.side-menu li a .menu-label {
  display: none;
}
.nav-sm .nav.side-menu li a i {
  font-size: 22px;
}
</style>
  <!-- sidebar menu -->
            <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
              <div class="menu_section">
                <h3 class="mt-3">Home</h3>
                <ul class="nav side-menu">
                <li class="<?php echo $currentPage === 'adm_dashboard.php' ? 'active current-page' : ''; ?>">
                    <a href="../dashboard/adm_dashboard.php">
                      <i class="fa fa-bar-chart"></i>
                      <span class="menu-label">Dashboard</span>
                    </a>
                  </li>
                  <li class="<?php echo $currentPage === 'purchase.php' ? 'active current-page' : ''; ?>">
                    <a href="../purchase/purchase.php">
                      <i class="fa fa-shopping-cart"></i>
                      <span class="menu-label">Purchase Order</span>
                    </a>
                  </li> 
                  <li class="<?php echo in_array($currentPage, ['inventory.php', 'category.php']) ? 'active' : ''; ?>">
                    <a>
                      <i class="fa fa-archive"></i>
                      <span class="menu-label">Inventory</span>
                      <span class="fa fa-chevron-down"></span>
                    </a>
                    <ul class="nav child_menu">
                      <li class="<?php echo $currentPage === 'inventory.php' ? 'current-page' : ''; ?>">
                        <a href="../inventory/inventory.php">Inventory List</a>
                      </li>
                      <li class="<?php echo $currentPage === 'category.php' ? 'current-page' : ''; ?>">
                        <a href="../category/category.php">Category</a>
                      </li>
                    </ul>
                  </li>
                  <li class="<?php echo $currentPage === 'sales.php' ? 'active current-page' : ''; ?>">
                    <a href="../sales/sales.php">
                      <i class="fa fa-money"></i>
                      <span class="menu-label">Sales</span>
                    </a>
                  </li> 
                  <li class="<?php echo $currentPage === 'discount.php' ? 'active current-page' : ''; ?>">
                    <a href="../discount/discount.php">
                      <i class="fa fa-percent"></i>
                      <span class="menu-label">Discount</span>
                    </a>
                  </li> 
                  <li class="<?php echo $currentPage === 'reserve.php' ? 'active current-page' : ''; ?>">
                    <a href="../reservation/reserve.php">
                      <i class="fa fa-calendar"></i>
                      <span class="menu-label">Reservation</span>
                    </a>
                  </li> 
                </ul>
              </div>
            </div>
            <!-- /sidebar menu -->

          </div>
        </div>