<div class="wrap">
  <h1>Cegal DASHBOARD</h1>
   <?php settings_errors(); ?>
  <ul class="nav nav-tabs">
    <li class="active"><a href="#tab-1">Manage Settings</a></li>
    <li><a href="#tab-2">Actions</a></li>
    <!-- <li><a href="#tab-3">Escanear Producto</a></li> -->
  </ul>
  <div class="tab-content">
    <div id="tab-1" class="tab-pane active">
      <?php include 'tab1_content.php'; ?>
    </div>
    <div id="tab-2" class="tab-pane">
      <?php include 'tab2_content.php'; ?>
    </div>
   <!-- <div id="tab-3" class="tab-pane">
      <?php /* include 'tab3_content.php'; */ ?>
   </div> -->
  </div>

</div>