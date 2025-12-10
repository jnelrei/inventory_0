
<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$shouldAnimateIntro = !empty($_SESSION['show_intro_animation']);
?>
  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <style>
          <?php if ($shouldAnimateIntro): ?>
          .nav_title .site_title img {
            opacity: 0;
            transform: translateX(-30px);
            animation: logoSlideIn 0.7s ease forwards;
            animation-delay: 0.3s;
          }
          @keyframes logoSlideIn {
            from {
              opacity: 0;
              transform: translateX(-30px);
            }
            to {
              opacity: 1;
              transform: translateX(0);
            }
          }
          <?php endif; ?>
          .nav_title .site_title {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0 12px 10px;
          }
          .nav_title .site_title img {
            height: 76px;
            width: auto;
            display: block;
            margin-left: 40px;
            transition: all 0.3s ease;
            max-width: 100%;
          }
          .nav-sm .nav_title .site_title {
            justify-content: center;
            padding: 14px 0;
          }
          .nav-sm .nav_title .site_title img {
            height: 48px;
            margin-left: 0;
          }
        </style>
        <div class="col-md-3 left_col menu_fixed">
          <div class="left_col scroll-view">
            <div class="navbar nav_title" style="border: 0;">
              <a href="adm_dashboard.php" class="site_title" title="Tumandok Crafts Industries Dashboard">
                <img src="../../images/furn.webp" alt="Tumandok Crafts wordmark">
                <span class="sr-only">Tumandok Crafts</span>
              </a>
            </div>

            <div class="clearfix"></div>

            <!-- menu profile quick info -->
            <div class="profile clearfix">
            </div>
            <!-- /menu profile quick info -->

            <br />