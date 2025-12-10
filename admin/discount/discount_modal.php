<?php
// Modal for adding/editing a discount
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../production/includes/db.php");
?>

<!-- Add Discount Modal -->
<div class="modal fade" id="addDiscountModal" tabindex="-1" role="dialog" aria-labelledby="addDiscountModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
      <div class="modal-header" style="background: #26B99A; border-bottom: none; padding: 20px 25px;">
        <h5 class="modal-title" id="addDiscountModalLabel" style="font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #ffffff; font-size: 18px; margin: 0;">Add Discount</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #ffffff; opacity: 0.9; text-shadow: none;">
          <span aria-hidden="true" style="font-size: 28px;">&times;</span>
        </button>
      </div>
      <div class="modal-body" style="padding: 20px 30px 15px 30px;">
        <form id="addDiscountForm" method="post" action="add_discount.php">
          <div class="form-group" style="margin-bottom: 15px;">
            <label for="add_discount_value" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Discount Value (%) <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="add_discount_value" name="discount_value" min="0" max="100" step="0.01" required style="border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 12px; font-size: 14px; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;">
          </div>
          <div class="form-group" style="margin-bottom: 15px;">
            <label for="add_start_date" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Start Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="add_start_date" name="start_date" required style="border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 12px; font-size: 14px; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;">
          </div>
          <div class="form-group" style="margin-bottom: 15px;">
            <label for="add_end_date" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">End Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="add_end_date" name="end_date" required style="border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 12px; font-size: 14px; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;">
          </div>
          <div class="form-group" style="margin-bottom: 0;">
            <label for="add_status" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Status <span class="text-danger">*</span></label>
            <select class="form-control" id="add_status" name="status" required style="border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 12px; font-size: 14px; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;">
              <option value="">Select Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 15px 25px; background: #f8f9fa;">
        <button type="button" class="btn btn-secondary" data-dismiss="modal" style="padding: 8px 20px; font-weight: 500; border-radius: 4px;">Close</button>
        <button type="submit" form="addDiscountForm" class="btn btn-success" style="padding: 8px 25px; font-weight: 500; border-radius: 4px;">Save Discount</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Discount Modal -->
<div class="modal fade" id="editDiscountModal" tabindex="-1" role="dialog" aria-labelledby="editDiscountModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
      <div class="modal-header" style="background: #26B99A; border-bottom: none; padding: 20px 25px;">
        <h5 class="modal-title" id="editDiscountModalLabel" style="font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #ffffff; font-size: 18px; margin: 0;">Edit Discount</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #ffffff; opacity: 0.9; text-shadow: none;">
          <span aria-hidden="true" style="font-size: 28px;">&times;</span>
        </button>
      </div>
      <div class="modal-body" style="padding: 20px 30px 15px 30px;">
        <form id="editDiscountForm" method="post" action="update_discount.php">
          <input type="hidden" id="edit_disc_id" name="disc_id" value="">
          <div class="form-group" style="margin-bottom: 15px;">
            <label for="edit_discount_value" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Discount Value (%) <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="edit_discount_value" name="discount_value" min="0" max="100" step="0.01" required style="border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 12px; font-size: 14px; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;">
          </div>
          <div class="form-group" style="margin-bottom: 15px;">
            <label for="edit_start_date" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Start Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="edit_start_date" name="start_date" required style="border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 12px; font-size: 14px; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;">
          </div>
          <div class="form-group" style="margin-bottom: 15px;">
            <label for="edit_end_date" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">End Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="edit_end_date" name="end_date" required style="border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 12px; font-size: 14px; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;">
          </div>
          <div class="form-group" style="margin-bottom: 0;">
            <label for="edit_status" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Status <span class="text-danger">*</span></label>
            <select class="form-control" id="edit_status" name="status" required style="border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 12px; font-size: 14px; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;">
              <option value="">Select Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 15px 25px; background: #f8f9fa;">
        <button type="button" class="btn btn-secondary" data-dismiss="modal" style="padding: 8px 20px; font-weight: 500; border-radius: 4px;">Close</button>
        <button type="submit" form="editDiscountForm" class="btn btn-success" style="padding: 8px 25px; font-weight: 500; border-radius: 4px;">Update Discount</button>
      </div>
    </div>
  </div>
</div>

<style>
  #addDiscountModal .form-control:focus,
  #editDiscountModal .form-control:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    outline: none;
  }
  
  #addDiscountModal .form-control:hover,
  #editDiscountModal .form-control:hover {
    border-color: #adb5bd;
  }
  
  #addDiscountModal .btn-success:hover,
  #editDiscountModal .btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
  }
  
  #addDiscountModal .btn-secondary:hover,
  #editDiscountModal .btn-secondary:hover {
    background-color: #5a6268;
    border-color: #545b62;
  }
  
  #addDiscountModal .modal-content,
  #editDiscountModal .modal-content {
    animation: modalFadeIn 0.3s ease-out;
  }
  
  @keyframes modalFadeIn {
    from {
      opacity: 0;
      transform: translateY(-50px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  
  #addDiscountModal label,
  #editDiscountModal label {
    text-transform: none;
    letter-spacing: normal;
  }
</style>

<script>
  $(document).ready(function() {
    // Date validation for Add Discount Form
    $('#add_start_date').on('change', function() {
      const startDate = new Date($(this).val());
      const endDateInput = $('#add_end_date');
      if (endDateInput.val()) {
        const endDate = new Date(endDateInput.val());
        if (endDate <= startDate) {
          alert('End date must be after start date');
          endDateInput.val('');
        }
      }
      // Set minimum date for end date
      endDateInput.attr('min', $(this).val());
    });

    $('#add_end_date').on('change', function() {
      const endDate = new Date($(this).val());
      const startDateInput = $('#add_start_date');
      if (startDateInput.val()) {
        const startDate = new Date(startDateInput.val());
        if (endDate <= startDate) {
          alert('End date must be after start date');
          $(this).val('');
        }
      }
    });

    // Date validation for Edit Discount Form
    $('#edit_start_date').on('change', function() {
      const startDate = new Date($(this).val());
      const endDateInput = $('#edit_end_date');
      if (endDateInput.val()) {
        const endDate = new Date(endDateInput.val());
        if (endDate <= startDate) {
          alert('End date must be after start date');
          endDateInput.val('');
        }
      }
      // Set minimum date for end date
      endDateInput.attr('min', $(this).val());
    });

    $('#edit_end_date').on('change', function() {
      const endDate = new Date($(this).val());
      const startDateInput = $('#edit_start_date');
      if (startDateInput.val()) {
        const startDate = new Date(startDateInput.val());
        if (endDate <= startDate) {
          alert('End date must be after start date');
          $(this).val('');
        }
      }
    });

    // Form submission validation
    $('#addDiscountForm').on('submit', function(e) {
      const startDate = new Date($('#add_start_date').val());
      const endDate = new Date($('#add_end_date').val());
      
      if (endDate <= startDate) {
        e.preventDefault();
        alert('End date must be after start date');
        return false;
      }
    });

    $('#editDiscountForm').on('submit', function(e) {
      const startDate = new Date($('#edit_start_date').val());
      const endDate = new Date($('#edit_end_date').val());
      
      if (endDate <= startDate) {
        e.preventDefault();
        alert('End date must be after start date');
        return false;
      }
    });
  });
</script>

