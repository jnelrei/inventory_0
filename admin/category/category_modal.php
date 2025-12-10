<?php
// Modal for adding/editing a category
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../production/includes/db.php");
?>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
      <div class="modal-header" style="background: #26B99A; border-bottom: none; padding: 20px 25px;">
        <h5 class="modal-title" id="addCategoryModalLabel" style="font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #ffffff; font-size: 18px; margin: 0;">Add Category</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #ffffff; opacity: 0.9; text-shadow: none;">
          <span aria-hidden="true" style="font-size: 28px;">&times;</span>
        </button>
      </div>
      <div class="modal-body" style="padding: 20px 30px 15px 30px;">
        <form id="addCategoryForm" method="post" action="add_category.php">
          <div class="form-group" style="margin-bottom: 0;">
            <label for="add_category_name" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Category Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="add_category_name" name="category_name" required style="border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 12px; font-size: 14px; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;">
          </div>
        </form>
      </div>
      <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 15px 25px; background: #f8f9fa;">
        <button type="button" class="btn btn-secondary" data-dismiss="modal" style="padding: 8px 20px; font-weight: 500; border-radius: 4px;">Close</button>
        <button type="submit" form="addCategoryForm" class="btn btn-success" style="padding: 8px 25px; font-weight: 500; border-radius: 4px;">Save Category</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" role="dialog" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
      <div class="modal-header" style="background: #26B99A; border-bottom: none; padding: 20px 25px;">
        <h5 class="modal-title" id="editCategoryModalLabel" style="font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #ffffff; font-size: 18px; margin: 0;">Edit Category</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #ffffff; opacity: 0.9; text-shadow: none;">
          <span aria-hidden="true" style="font-size: 28px;">&times;</span>
        </button>
      </div>
      <div class="modal-body" style="padding: 20px 30px 15px 30px;">
        <form id="editCategoryForm" method="post" action="update_category.php">
          <input type="hidden" id="edit_category_id" name="category_id" value="">
          <div class="form-group" style="margin-bottom: 0;">
            <label for="edit_category_name" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Category Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="edit_category_name" name="category_name" required style="border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 12px; font-size: 14px; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;">
          </div>
        </form>
      </div>
      <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 15px 25px; background: #f8f9fa;">
        <button type="button" class="btn btn-secondary" data-dismiss="modal" style="padding: 8px 20px; font-weight: 500; border-radius: 4px;">Close</button>
        <button type="submit" form="editCategoryForm" class="btn btn-success" style="padding: 8px 25px; font-weight: 500; border-radius: 4px;">Update Category</button>
      </div>
    </div>
  </div>
</div>

<style>
  #addCategoryModal .form-control:focus,
  #editCategoryModal .form-control:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    outline: none;
  }
  
  #addCategoryModal .form-control:hover,
  #editCategoryModal .form-control:hover {
    border-color: #adb5bd;
  }
  
  #addCategoryModal .btn-success:hover,
  #editCategoryModal .btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
  }
  
  #addCategoryModal .btn-secondary:hover,
  #editCategoryModal .btn-secondary:hover {
    background-color: #5a6268;
    border-color: #545b62;
  }
  
  #addCategoryModal .modal-content,
  #editCategoryModal .modal-content {
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
  
  #addCategoryModal label,
  #editCategoryModal label {
    text-transform: none;
    letter-spacing: normal;
  }
</style>

<script>
  function addCategory() {
    // Reset form when modal opens
    $('#addCategoryForm')[0].reset();
    $('#addFormMessage').hide();
    $('#addCategoryModal').modal('show');
  }

  function editCategory(categoryId) {
    // Fetch category data via AJAX
    $.ajax({
      url: 'get_category.php',
      type: 'GET',
      data: { category_id: categoryId },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          $('#edit_category_id').val(response.data.category_id);
          $('#edit_category_name').val(response.data.category_name);
          $('#editFormMessage').hide();
          $('#editCategoryModal').modal('show');
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: response.message || 'Failed to load category data'
          });
        }
      },
      error: function(xhr, status, error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'An error occurred while loading category data: ' + error
        });
      }
    });
  }

  function deleteCategory(categoryId) {
    // First fetch category name
    $.ajax({
      url: 'get_category.php',
      type: 'GET',
      data: { category_id: categoryId },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          const categoryName = response.data.category_name;
          
          Swal.fire({
            title: 'Are you sure?',
            html: 'Do you want to delete <span style="color: red; font-weight: bold;">' + categoryName + '</span>?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
          }).then((result) => {
            if (result.isConfirmed) {
              // Delete via AJAX
              $.ajax({
                url: 'delete_category.php',
                type: 'POST',
                data: { category_id: categoryId },
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    Swal.fire({
                      icon: 'success',
                      title: 'Deleted!',
                      text: response.message || 'Category has been deleted.',
                      timer: 3000,
                      timerProgressBar: true,
                      showConfirmButton: true,
                      confirmButtonText: 'OK',
                      confirmButtonColor: '#26B99A'
                    }).then((result) => {
                      if (result.isConfirmed || result.dismiss === Swal.DismissReason.timer) {
                        window.location.reload();
                      }
                    });
                  } else {
                    Swal.fire({
                      icon: 'error',
                      title: 'Error',
                      text: response.message || 'Failed to delete category'
                    });
                  }
                },
                error: function(xhr, status, error) {
                  Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred: ' + error
                  });
                }
              });
            }
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: response.message || 'Failed to load category data'
          });
        }
      },
      error: function(xhr, status, error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'An error occurred while loading category data: ' + error
        });
      }
    });
  }

</script>

