<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../production/includes/db.php");

// Fetch discount data
try {
    $stmt = $pdo->query("SELECT disc_id, discount_value, start_date, end_date, status, created_at FROM discount ORDER BY created_at DESC");
    $discounts = $stmt->fetchAll();
} catch (PDOException $e) {
    $discounts = [];
    $error_message = "Error loading discounts: " . $e->getMessage();
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
        DISCOUNT
      </h2>
      <button type="button"
              class="btn btn-success btn-sm"
              style="float: right; margin-top: 7px;"
              onclick="openAddDiscount()">
        <i class="fa fa-plus"></i> Add Discount
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

            <table id="datatable" class="table table-striped table-bordered" style="width:100%; visibility: hidden;">
          <thead>
            <tr>
                  <th>Discount Value</th>
              <th>Start Date</th>
              <th>End Date</th>
              <th>Status</th>
                  <th>Created At</th>
                  <th>Actions</th>
            </tr>
          </thead>
          <tbody>
                <?php if (empty($discounts)): ?>
            <tr>
                    <td colspan="6" class="text-center">No discounts found.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($discounts as $discount): ?>
                    <tr data-discount='<?php echo json_encode($discount, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>'>
                      <td><?php echo htmlspecialchars($discount['discount_value']); ?></td>
                      <td><?php echo !empty($discount['start_date']) ? date('M d, Y', strtotime($discount['start_date'])) : 'N/A'; ?></td>
                      <td><?php echo !empty($discount['end_date']) ? date('M d, Y', strtotime($discount['end_date'])) : 'N/A'; ?></td>
                      <td><?php echo htmlspecialchars(ucfirst($discount['status'] ?? '')); ?></td>
                      <td><?php echo !empty($discount['created_at']) ? date('M d, Y', strtotime($discount['created_at'])) . ' at ' . date('h:i A', strtotime($discount['created_at'])) : 'N/A'; ?></td>
                      <td style="text-align: center; white-space: nowrap;">
                        <button class="btn btn-sm btn-primary" onclick="openEditDiscount(this)" style="margin-right: 5px;">
                          <i class="fa fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteDiscount(this)">
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
<?php include("discount_modal.php"); ?>
<?php include("../admin_components/footer.php")?>
<?php include("../../production/includes/fd.php")?>

<script>
// Initialize DataTable with custom settings without flickering
$(document).ready(function() {
  function initDiscountTable() {
    if (typeof $.fn.DataTable === 'undefined') {
      setTimeout(initDiscountTable, 50);
      return;
    }

        if ($.fn.DataTable.isDataTable('#datatable')) {
            $('#datatable').DataTable().destroy();
    }

        $('#datatable').DataTable({
            "order": [[4, 'desc']], // Sort by Created At
            "columnDefs": [
                { "orderable": false, "targets": [5] } // Actions column
      ],
            "initComplete": function() {
                $('#datatable').css('visibility', 'visible');
      }
    });
  }

  setTimeout(initDiscountTable, 150);
});

// Open Add modal
function openAddDiscount() {
    const form = document.getElementById('addDiscountForm');
    if (form) {
        form.reset();
    }
    $('#addDiscountModal').modal('show');
}

// Open Edit modal
function openEditDiscount(button) {
    const row = button.closest('tr');
    if (!row) return;
    const dataAttr = row.getAttribute('data-discount');
    if (!dataAttr) return;

    let discount = null;
    try {
        discount = JSON.parse(dataAttr);
    } catch (e) {
        console.error('Invalid discount data', e);
        return;
    }

    document.getElementById('edit_disc_id').value = discount.disc_id || '';
    document.getElementById('edit_discount_value').value = discount.discount_value || '';
    document.getElementById('edit_start_date').value = discount.start_date ? discount.start_date.split(' ')[0] : '';
    document.getElementById('edit_end_date').value = discount.end_date ? discount.end_date.split(' ')[0] : '';
    document.getElementById('edit_status').value = discount.status || '';

    $('#editDiscountModal').modal('show');
}

// Delete handler
function deleteDiscount(button) {
    const row = button.closest('tr');
    if (!row) return;
    const dataAttr = row.getAttribute('data-discount');
    if (!dataAttr) return;

    let discount = null;
    try {
        discount = JSON.parse(dataAttr);
    } catch (e) {
        console.error('Invalid discount data', e);
        return;
    }

    if (!discount.disc_id) return;

    if (confirm('Are you sure you want to delete this discount?')) {
        const form = document.getElementById('deleteDiscountForm');
        if (form) {
            form.querySelector('#delete_disc_id').value = discount.disc_id;
            form.submit();
        }
    }
}
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