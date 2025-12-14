<?php
// Modal for adding/editing inventory items
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../production/includes/db.php");

// Fetch categories for dropdown
try {
    $stmt = $pdo->query("SELECT category_id, category_name FROM category ORDER BY category_name ASC");
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $categories = [];
}
?>

<!-- Add Inventory Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1" role="dialog" aria-labelledby="addItemModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content" style="border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
      <div class="modal-header" style="background: #26B99A; border-bottom: none; padding: 20px 25px;">
        <h5 class="modal-title" id="addItemModalLabel" style="font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #ffffff; font-size: 18px; margin: 0;">Add Inventory Item</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #ffffff; opacity: 0.9; text-shadow: none;">
          <span aria-hidden="true" style="font-size: 28px;">&times;</span>
        </button>
      </div>
      <div class="modal-body" style="padding: 30px 30px 15px 30px; max-height: 70vh; overflow-y: auto; overflow-x: hidden;">
        <form id="addItemForm" method="post" action="add_item.php" enctype="multipart/form-data">
          <div class="row">
            <!-- Left Column: Pictures -->
            <div class="col-md-5">
              <div class="form-group">
                <label for="add_picture" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 10px; display: block;">Pictures <span class="text-muted" style="font-size: 12px; font-weight: normal;">(Multiple images allowed)</span></label>
                <div id="addImagePreview" style="margin-bottom: 15px; background: #f8f9fa; border-radius: 8px; padding: 15px; min-height: 300px;">
                  <div id="addPreviewContainer" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px;">
                    <!-- Preview images will be added here -->
                  </div>
                  <div id="addPreviewPlaceholder" style="text-align: center; padding: 100px 20px; color: #999;">
                    <p style="margin: 0;">No images selected</p>
                  </div>
                </div>
                <div class="clean-file-upload-wrapper">
                  <input type="file" class="clean-file-input" id="add_picture" name="picture[]" multiple accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" onchange="handleAddImagePreview(this)">
                  <label for="add_picture" class="clean-file-label">
                    <div class="file-icon-wrapper">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M16 13H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M16 17H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M10 9H9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </div>
                    <div class="file-label-content">
                      <span class="file-main-text">Choose Image Files</span>
                      <span class="file-selected-name" id="add_file_name"></span>
                    </div>
                    <div class="file-choose-btn">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                      <span>Choose</span>
                    </div>
                  </label>
                  <div class="file-info-text">
                    <span>Maximum file size: 5MB per image</span>
                    <span class="info-dot">•</span>
                    <span>Accepted formats: JPEG, PNG, GIF, WebP</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Column: Form Fields -->
            <div class="col-md-7" style="padding-left: 25px;">
              <div class="form-group" style="margin-bottom: 20px;">
                <label for="add_item_name" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Item Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="add_item_name" name="item_name" required style="border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 12px; font-size: 14px; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;">
              </div>

              <div class="form-group" style="margin-bottom: 20px;">
                <label for="add_category_id" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Category <span class="text-danger">*</span></label>
                <select class="form-control" id="add_category_id" name="category_id" required style="border: 1px solid #dee2e6; border-radius: 4px; font-size: 14px; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;">
                  <option value="">Select Category</option>
                  <?php foreach ($categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category['category_id']); ?>">
                      <?php echo htmlspecialchars($category['category_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group" style="margin-bottom: 20px;">
                <label for="add_description" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Description</label>
                <textarea class="form-control" id="add_description" name="description" rows="3" style="border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 12px; font-size: 14px; resize: vertical; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;"></textarea>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group" style="margin-bottom: 0;">
                    <label for="add_quantity" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Quantity <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="add_quantity" name="quantity" min="0" required style="border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 12px; font-size: 14px; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group" style="margin-bottom: 0;">
                    <label for="add_total_cost" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Total Cost <span class="text-danger">*</span></label>
                    <div class="input-group" style="position: relative;">
                      <div class="input-prefix" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6c757d; font-weight: 600; font-size: 14px; z-index: 10; pointer-events: none;">₱</div>
                      <input type="number" step="0.01" class="form-control" id="add_total_cost" name="total_cost" min="0" required style="border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 12px 10px 28px; font-size: 14px; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;">
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-group" style="margin-bottom: 20px; margin-top: 20px;">
                <label for="add_barcode" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Barcode</label>
                <div class="input-group" id="add_barcode_input_group">
                  <input type="text" class="form-control" id="add_barcode" name="barcode" placeholder="Enter barcode or generate automatically" style="border: 1px solid #dee2e6; border-radius: 4px 0 0 4px; padding: 10px 12px; font-size: 14px; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;">
                  <div class="input-group-append">
                    <button type="button" class="btn btn-info" id="add_generate_barcode" onclick="generateBarcode('add')" style="border-radius: 0 4px 4px 0; padding: 6px 12px; font-weight: 500; font-size: 13px;">
                      <i class="fa fa-barcode"></i> Generate
                    </button>
                  </div>
                </div>
                <div id="add_barcode_preview" style="margin-top: 10px; text-align: center; display: none;">
                  <svg id="add_barcode_svg"></svg>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 20px 25px; background: #f8f9fa;">
        <button type="button" class="btn btn-secondary" data-dismiss="modal" style="padding: 8px 20px; font-weight: 500; border-radius: 4px;">Close</button>
        <button type="submit" form="addItemForm" class="btn btn-success" style="padding: 8px 25px; font-weight: 500; border-radius: 4px;">Save Item</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Inventory Item Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1" role="dialog" aria-labelledby="editItemModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content" style="border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
      <div class="modal-header" style="background: #26B99A; border-bottom: none; padding: 20px 25px;">
        <h5 class="modal-title" id="editItemModalLabel" style="font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #ffffff; font-size: 18px; margin: 0;">Edit Inventory Item</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #ffffff; opacity: 0.9; text-shadow: none;">
          <span aria-hidden="true" style="font-size: 28px;">&times;</span>
        </button>
      </div>
      <div class="modal-body" style="padding: 30px 30px 15px 30px; max-height: 70vh; overflow-y: auto; overflow-x: hidden;">
        <form id="editItemForm" method="post" action="update_item.php" enctype="multipart/form-data">
          <input type="hidden" id="edit_item_id" name="item_id" value="">
          <div class="row">
            <!-- Left Column: Pictures -->
            <div class="col-md-5">
              <div class="form-group">
                <label for="edit_picture" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 10px; display: block;">Pictures <span class="text-muted" style="font-size: 12px; font-weight: normal;">(Multiple images allowed)</span></label>
                <div id="editImagePreview" style="margin-bottom: 15px; background: #f8f9fa; border-radius: 8px; padding: 15px; min-height: 300px;">
                  <div id="editPreviewContainer" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px;">
                    <!-- Preview images will be added here -->
                  </div>
                  <div id="editPreviewPlaceholder" style="text-align: center; padding: 100px 20px; color: #999;">
                    <p style="margin: 0;">No images selected</p>
                  </div>
                </div>
                <div class="clean-file-upload-wrapper">
                  <input type="file" class="clean-file-input" id="edit_picture" name="picture[]" multiple accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" onchange="handleEditImagePreview(this)">
                  <label for="edit_picture" class="clean-file-label">
                    <div class="file-icon-wrapper">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M16 13H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M16 17H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M10 9H9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </div>
                    <div class="file-label-content">
                      <span class="file-main-text">Choose Image Files</span>
                      <span class="file-selected-name" id="edit_file_name"></span>
                    </div>
                    <div class="file-choose-btn">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                      <span>Choose</span>
                    </div>
                  </label>
                  <div class="file-info-text">
                    <span>Maximum file size: 5MB per image</span>
                    <span class="info-dot">•</span>
                    <span>Accepted formats: JPEG, PNG, GIF, WebP</span>
                  </div>
                </div>
                <input type="hidden" id="edit_existing_images" name="existing_images" value="">
              </div>
            </div>

            <!-- Right Column: Form Fields -->
            <div class="col-md-7" style="padding-left: 25px;">
              <div class="form-group" style="margin-bottom: 20px;">
                <label for="edit_item_name" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Item Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="edit_item_name" name="item_name" required style="border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 12px; font-size: 14px; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;">
              </div>

              <div class="form-group" style="margin-bottom: 20px;">
                <label for="edit_category_id" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Category <span class="text-danger">*</span></label>
                <select class="form-control" id="edit_category_id" name="category_id" required style="border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 12px; font-size: 14px; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;">
                  <option value="">Select Category</option>
                  <?php foreach ($categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category['category_id']); ?>">
                      <?php echo htmlspecialchars($category['category_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group" style="margin-bottom: 20px;">
                <label for="edit_description" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Description</label>
                <textarea class="form-control" id="edit_description" name="description" rows="3" style="border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 12px; font-size: 14px; resize: vertical; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;"></textarea>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group" style="margin-bottom: 0;">
                    <label for="edit_quantity" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Quantity <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="edit_quantity" name="quantity" min="0" required style="border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 12px; font-size: 14px; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group" style="margin-bottom: 0;">
                    <label for="edit_total_cost" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Total Cost <span class="text-danger">*</span></label>
                    <div class="input-group" style="position: relative;">
                      <div class="input-prefix" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6c757d; font-weight: 600; font-size: 14px; z-index: 10; pointer-events: none;">₱</div>
                      <input type="number" step="0.01" class="form-control" id="edit_total_cost" name="total_cost" min="0" required style="border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 12px 10px 28px; font-size: 14px; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;">
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-group" style="margin-bottom: 20px; margin-top: 20px;">
                <label for="edit_barcode" style="color: #000000; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Barcode</label>
                <div class="input-group" id="edit_barcode_input_group">
                  <input type="text" class="form-control" id="edit_barcode" name="barcode" placeholder="Enter barcode or generate automatically" style="border: 1px solid #dee2e6; border-radius: 4px 0 0 4px; padding: 10px 12px; font-size: 14px; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;">
                  <div class="input-group-append">
                    <button type="button" class="btn btn-info" id="edit_generate_barcode" onclick="generateBarcode('edit')" style="border-radius: 0 4px 4px 0; padding: 6px 12px; font-weight: 500; font-size: 13px;">
                      <i class="fa fa-barcode"></i> Generate
                    </button>
                  </div>
                </div>
                <div id="edit_barcode_preview" style="margin-top: 10px; text-align: center; display: none;">
                  <svg id="edit_barcode_svg"></svg>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 20px 25px; background: #f8f9fa;">
        <button type="button" class="btn btn-secondary" data-dismiss="modal" style="padding: 8px 20px; font-weight: 500; border-radius: 4px;">Close</button>
        <button type="submit" form="editItemForm" class="btn btn-success" style="padding: 8px 25px; font-weight: 500; border-radius: 4px;">Update Item</button>
      </div>
    </div>
  </div>
</div>

<style>
  #addItemModal .form-control:focus,
  #editItemModal .form-control:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    outline: none;
  }
  
  #addItemModal .form-control:hover,
  #editItemModal .form-control:hover {
    border-color: #adb5bd;
  }
  
  #addItemModal .btn-success:hover,
  #editItemModal .btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
  }
  
  #addItemModal .btn-secondary:hover,
  #editItemModal .btn-secondary:hover {
    background-color: #5a6268;
    border-color: #545b62;
  }
  
  #addItemModal .modal-content,
  #editItemModal .modal-content {
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
  
  #addPreviewImg,
  #editPreviewImg {
    transition: all 0.3s ease;
  }
  
  #addPreviewImg:hover,
  #editPreviewImg:hover {
    transform: scale(1.02);
  }
  
  #addItemModal label,
  #editItemModal label {
    text-transform: none;
    letter-spacing: normal;
  }
  
  /* Clean File Upload Styling */
  .clean-file-upload-wrapper {
    position: relative;
    width: 100%;
    margin-bottom: 10px;
  }
  
  .clean-file-input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
    overflow: hidden;
    z-index: -1;
  }
  
  .clean-file-label {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 18px;
    background: #ffffff;
    border: 1.5px solid #e5e7eb;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 14px;
    min-height: 56px;
  }
  
  .clean-file-label:hover {
    border-color: #26B99A;
    background: #fafefe;
    box-shadow: 0 2px 8px rgba(38, 185, 154, 0.1);
  }
  
  .clean-file-label:active {
    transform: scale(0.998);
  }
  
  .file-icon-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: #f3f4f6;
    border-radius: 6px;
    color: #6b7280;
    flex-shrink: 0;
    transition: all 0.2s ease;
  }
  
  .clean-file-label:hover .file-icon-wrapper {
    background: #e6fcf7;
    color: #26B99A;
  }
  
  .file-label-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
  }
  
  .file-main-text {
    color: #374151;
    font-weight: 500;
    font-size: 14px;
    transition: color 0.2s ease;
  }
  
  .clean-file-label:hover .file-main-text {
    color: #26B99A;
  }
  
  .file-selected-name {
    color: #26B99A;
    font-weight: 600;
    font-size: 12px;
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
    display: none;
  }
  
  .file-selected-name.has-file {
    display: block;
  }
  
  .file-choose-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: #26B99A;
    color: #ffffff;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.2s ease;
    flex-shrink: 0;
    white-space: nowrap;
  }
  
  .file-choose-btn svg {
    flex-shrink: 0;
  }
  
  .clean-file-label:hover .file-choose-btn {
    background: #1ea082;
    transform: translateX(2px);
  }
  
  .clean-file-input:focus + .clean-file-label {
    border-color: #26B99A;
    box-shadow: 0 0 0 3px rgba(38, 185, 154, 0.1);
    outline: none;
  }
  
  /* When file is selected */
  .clean-file-input:valid + .clean-file-label {
    border-color: #26B99A;
    background: #f9fffe;
  }
  
  .file-info-text {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    font-size: 12px;
    color: #6b7280;
    margin-top: 8px;
    padding-left: 2px;
  }
  
  .file-info-text span:not(.info-dot) {
    color: #6b7280;
  }
  
  .info-dot {
    color: #d1d5db;
    font-weight: 300;
  }
  
  /* Custom Scrollbar for Modal Body */
  #addItemModal .modal-body,
  #editItemModal .modal-body {
    scrollbar-width: thin;
    scrollbar-color: #26B99A #f1f1f1;
  }
  
  #addItemModal .modal-body::-webkit-scrollbar,
  #editItemModal .modal-body::-webkit-scrollbar {
    width: 8px;
  }
  
  #addItemModal .modal-body::-webkit-scrollbar-track,
  #editItemModal .modal-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
  }
  
  #addItemModal .modal-body::-webkit-scrollbar-thumb,
  #editItemModal .modal-body::-webkit-scrollbar-thumb {
    background: #26B99A;
    border-radius: 10px;
    transition: background 0.3s ease;
  }
  
  #addItemModal .modal-body::-webkit-scrollbar-thumb:hover,
  #editItemModal .modal-body::-webkit-scrollbar-thumb:hover {
    background: #1e9d82;
  }
  
  /* Image Preview Grid Styles */
  #addPreviewContainer,
  #editPreviewContainer {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 10px;
    margin-bottom: 10px;
  }
  
  #addPreviewContainer > div,
  #editPreviewContainer > div {
    position: relative;
    aspect-ratio: 1;
    overflow: hidden;
    border-radius: 6px;
    background: #ffffff;
  }
  
  #addPreviewContainer img,
  #editPreviewContainer img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }
  
  #addPreviewContainer button,
  #editPreviewContainer button {
    position: absolute;
    top: 5px;
    right: 5px;
    background: #dc3545;
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    cursor: pointer;
    font-size: 18px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    z-index: 10;
  }
  
  #addPreviewContainer button:hover,
  #editPreviewContainer button:hover {
    background: #c82333;
    transform: scale(1.1);
  }
  
  #addPreviewPlaceholder,
  #editPreviewPlaceholder {
    text-align: center;
    padding: 20px;
    color: #999;
  }
</style>

<!-- JsBarcode Library for Barcode Generation -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<script>
  // Generate unique barcode
  function generateBarcode(mode) {
    // Generate a unique barcode number (using timestamp + random number)
    const timestamp = Date.now();
    const random = Math.floor(Math.random() * 10000);
    const barcodeValue = timestamp.toString() + random.toString().padStart(4, '0');
    
    const barcodeInput = document.getElementById(mode + '_barcode');
    const barcodePreview = document.getElementById(mode + '_barcode_preview');
    const barcodeSvg = document.getElementById(mode + '_barcode_svg');
    const barcodeInputGroup = document.getElementById(mode + '_barcode_input_group');
    
    if (barcodeInput) {
      barcodeInput.value = barcodeValue;
      
      // Generate barcode visual
      if (barcodeSvg && typeof JsBarcode !== 'undefined') {
        try {
          JsBarcode(barcodeSvg, barcodeValue, {
            format: "CODE128",
            width: 2,
            height: 60,
            displayValue: true,
            fontSize: 14,
            margin: 10
          });
          barcodePreview.style.display = 'block';
          
          // Hide the input group (input field and generate button) after generating
          if (barcodeInputGroup) {
            barcodeInputGroup.style.display = 'none';
          }
        } catch (error) {
          console.error('Error generating barcode:', error);
          barcodePreview.style.display = 'none';
        }
      } else {
        barcodePreview.style.display = 'none';
      }
    }
  }
  
  // Update barcode preview when barcode input changes
  function updateBarcodePreview(mode) {
    const barcodeInput = document.getElementById(mode + '_barcode');
    const barcodePreview = document.getElementById(mode + '_barcode_preview');
    const barcodeSvg = document.getElementById(mode + '_barcode_svg');
    
    if (barcodeInput && barcodeSvg && typeof JsBarcode !== 'undefined') {
      const barcodeValue = barcodeInput.value.trim();
      
      if (barcodeValue && barcodeValue.length > 0) {
        try {
          JsBarcode(barcodeSvg, barcodeValue, {
            format: "CODE128",
            width: 2,
            height: 60,
            displayValue: true,
            fontSize: 14,
            margin: 10
          });
          barcodePreview.style.display = 'block';
        } catch (error) {
          console.error('Error generating barcode preview:', error);
          barcodePreview.style.display = 'none';
        }
      } else {
        barcodePreview.style.display = 'none';
      }
    }
  }

  function addItem() {
    // Reset form when modal opens
    $('#addItemForm')[0].reset();
    // Reset image preview
    const previewContainer = document.getElementById('addPreviewContainer');
    const previewPlaceholder = document.getElementById('addPreviewPlaceholder');
    if (previewContainer) previewContainer.innerHTML = '';
    if (previewPlaceholder) previewPlaceholder.style.display = 'block';
    // Reset file name display
    const fileNameEl = document.getElementById('add_file_name');
    if (fileNameEl) {
      fileNameEl.textContent = '';
      fileNameEl.classList.remove('has-file');
    }
    // Reset barcode preview and show input group
    $('#add_barcode_preview').hide();
    $('#add_barcode').val('');
    $('#add_barcode_input_group').show();
    $('#addItemModal').modal('show');
  }

  function editItem(itemId) {
    // Fetch item data via AJAX
    $.ajax({
      url: 'get_item.php',
      type: 'GET',
      data: { item_id: itemId },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          const item = response.data;
          $('#edit_item_id').val(item.item_id);
          $('#edit_item_name').val(item.item_name);
          $('#edit_category_id').val(item.category_id);
          $('#edit_description').val(item.description || '');
          $('#edit_quantity').val(item.quantity);
          $('#edit_total_cost').val(item.total_cost);
          $('#edit_barcode').val(item.barcode || '');
          
          // Set existing images
          const existingImages = item.images || [];
          $('#edit_existing_images').val(JSON.stringify(existingImages));
          
          // Display existing images
          const previewContainer = document.getElementById('editPreviewContainer');
          const previewPlaceholder = document.getElementById('editPreviewPlaceholder');
          if (previewContainer) {
            previewContainer.innerHTML = '';
            if (existingImages && existingImages.length > 0) {
              previewPlaceholder.style.display = 'none';
              previewContainer.style.display = 'grid';
              existingImages.forEach((imgData) => {
                const previewDiv = document.createElement('div');
                previewDiv.style.position = 'relative';
                previewDiv.style.aspectRatio = '1';
                previewDiv.style.overflow = 'hidden';
                previewDiv.style.borderRadius = '6px';
                previewDiv.style.border = '2px solid #28a745';
                previewDiv.style.background = '#ffffff';
                
                const img = document.createElement('img');
                img.src = imgData.image;
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';
                img.style.display = 'block';
                
                // Remove button - use image path as identifier
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.innerHTML = '×';
                removeBtn.style.position = 'absolute';
                removeBtn.style.top = '5px';
                removeBtn.style.right = '5px';
                removeBtn.style.background = '#dc3545';
                removeBtn.style.color = '#fff';
                removeBtn.style.border = 'none';
                removeBtn.style.borderRadius = '50%';
                removeBtn.style.width = '24px';
                removeBtn.style.height = '24px';
                removeBtn.style.cursor = 'pointer';
                removeBtn.style.fontSize = '18px';
                removeBtn.style.lineHeight = '1';
                removeBtn.style.display = 'flex';
                removeBtn.style.alignItems = 'center';
                removeBtn.style.justifyContent = 'center';
                removeBtn.onclick = function() {
                  // Remove image by matching image path
                  const imagePath = imgData.image;
                  const updatedImages = existingImages.filter(img => img.image !== imagePath);
                  $('#edit_existing_images').val(JSON.stringify(updatedImages));
                  handleEditImagePreview(document.getElementById('edit_picture'));
                };
                
                previewDiv.appendChild(img);
                previewDiv.appendChild(removeBtn);
                previewContainer.appendChild(previewDiv);
              });
            } else {
              previewPlaceholder.style.display = 'block';
              previewContainer.style.display = 'none';
            }
          }
          
          // Reset file name display when editing (since we're editing existing item, no new file selected yet)
          const fileNameEl = document.getElementById('edit_file_name');
          if (fileNameEl) {
            fileNameEl.textContent = '';
            fileNameEl.classList.remove('has-file');
          }
          
          // Update barcode preview if barcode exists
          if (item.barcode && item.barcode.trim() !== '') {
            updateBarcodePreview('edit');
            // Hide input group if barcode exists
            $('#edit_barcode_input_group').hide();
          } else {
            $('#edit_barcode_preview').hide();
            // Show input group if no barcode
            $('#edit_barcode_input_group').show();
          }
          
          $('#editItemModal').modal('show');
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: response.message || 'Failed to load item data'
          });
        }
      },
      error: function(xhr, status, error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'An error occurred while loading item data: ' + error
        });
      }
    });
  }

  // Image preview functionality for add modal - shows immediately when files are selected
  function handleAddImagePreview(input) {
    const files = input.files;
    const previewContainer = document.getElementById('addPreviewContainer');
    const previewPlaceholder = document.getElementById('addPreviewPlaceholder');
    const fileNameDisplay = document.getElementById('add_file_name');
    
    if (!previewContainer) return;
    
    // Clear existing previews
    previewContainer.innerHTML = '';
    
    // Update file name display
    if (fileNameDisplay) {
      if (files && files.length > 0) {
        const fileCount = files.length;
        fileNameDisplay.textContent = fileCount + ' file' + (fileCount > 1 ? 's' : '') + ' selected';
        fileNameDisplay.classList.add('has-file');
      } else {
        fileNameDisplay.textContent = '';
        fileNameDisplay.classList.remove('has-file');
      }
    }
    
    if (files && files.length > 0) {
      previewPlaceholder.style.display = 'none';
      previewContainer.style.display = 'grid';
      
      const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
      const maxSize = 5 * 1024 * 1024; // 5MB in bytes
      let validFiles = [];
      
      // Process each file
      Array.from(files).forEach((file, fileIndex) => {
        // Validate file type
        if (!allowedTypes.includes(file.type)) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'Invalid File Type',
              text: file.name + ' is not a valid image file (JPEG, PNG, GIF, or WebP)'
            });
          }
          return;
        }
        
        // Validate file size
        if (file.size > maxSize) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'File Too Large',
              text: file.name + ' exceeds 5MB limit. Please choose a smaller image.'
            });
          }
          return;
        }
        
        validFiles.push(file);
        
        // Create preview element
        const reader = new FileReader();
        reader.onload = function(e) {
          const previewDiv = document.createElement('div');
          previewDiv.style.position = 'relative';
          previewDiv.style.aspectRatio = '1';
          previewDiv.style.overflow = 'hidden';
          previewDiv.style.borderRadius = '6px';
          previewDiv.style.border = '2px solid #28a745';
          previewDiv.style.background = '#ffffff';
          
          const img = document.createElement('img');
          img.src = e.target.result;
          img.style.width = '100%';
          img.style.height = '100%';
          img.style.objectFit = 'cover';
          img.style.display = 'block';
          
          // Remove button - store file index in closure
          const removeBtn = document.createElement('button');
          removeBtn.type = 'button';
          removeBtn.innerHTML = '×';
          removeBtn.style.position = 'absolute';
          removeBtn.style.top = '5px';
          removeBtn.style.right = '5px';
          removeBtn.style.background = '#dc3545';
          removeBtn.style.color = '#fff';
          removeBtn.style.border = 'none';
          removeBtn.style.borderRadius = '50%';
          removeBtn.style.width = '24px';
          removeBtn.style.height = '24px';
          removeBtn.style.cursor = 'pointer';
          removeBtn.style.fontSize = '18px';
          removeBtn.style.lineHeight = '1';
          removeBtn.style.display = 'flex';
          removeBtn.style.alignItems = 'center';
          removeBtn.style.justifyContent = 'center';
          (function(currentIndex) {
            removeBtn.onclick = function() {
              // Remove file from input using stored index
              const dt = new DataTransfer();
              Array.from(input.files).forEach((f, i) => {
                if (i !== currentIndex) dt.items.add(f);
              });
              input.files = dt.files;
              handleAddImagePreview(input);
            };
          })(fileIndex);
          
          previewDiv.appendChild(img);
          previewDiv.appendChild(removeBtn);
          previewContainer.appendChild(previewDiv);
        };
        reader.readAsDataURL(file);
      });
      
      if (validFiles.length === 0) {
        previewPlaceholder.style.display = 'block';
        previewContainer.style.display = 'none';
        input.value = '';
      }
    } else {
      previewPlaceholder.style.display = 'block';
      previewContainer.style.display = 'none';
    }
  }
  
  // Attach event handlers using both jQuery and vanilla JS for maximum compatibility
  $(document).ready(function() {
    // jQuery event handler
    $(document).on('change', '#add_picture', function() {
      handleAddImagePreview(this);
    });
    
    // Also attach directly to the element when modal is shown
    $('#addItemModal').on('shown.bs.modal', function() {
      const fileInput = document.getElementById('add_picture');
      if (fileInput) {
        fileInput.onchange = function() {
          handleAddImagePreview(this);
        };
      }
    });
  });

  // Image preview functionality for edit modal - shows immediately when files are selected
  function handleEditImagePreview(input) {
    const files = input.files;
    const previewContainer = document.getElementById('editPreviewContainer');
    const previewPlaceholder = document.getElementById('editPreviewPlaceholder');
    const existingImagesInput = document.getElementById('edit_existing_images');
    const fileNameDisplay = document.getElementById('edit_file_name');
    
    if (!previewContainer) return;
    
    // Get existing images
    let existingImages = [];
    if (existingImagesInput && existingImagesInput.value) {
      try {
        existingImages = JSON.parse(existingImagesInput.value);
      } catch(e) {
        existingImages = [];
      }
    }
    
    // Clear existing previews
    previewContainer.innerHTML = '';
    
    // Display existing images first
    if (existingImages && existingImages.length > 0) {
      existingImages.forEach((imgData) => {
        const previewDiv = document.createElement('div');
        previewDiv.style.position = 'relative';
        previewDiv.style.aspectRatio = '1';
        previewDiv.style.overflow = 'hidden';
        previewDiv.style.borderRadius = '6px';
        previewDiv.style.border = '2px solid #28a745';
        previewDiv.style.background = '#ffffff';
        
        const img = document.createElement('img');
        img.src = imgData.image;
        img.style.width = '100%';
        img.style.height = '100%';
        img.style.objectFit = 'cover';
        img.style.display = 'block';
        
        // Remove button for existing images - use image path as identifier
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.innerHTML = '×';
        removeBtn.style.position = 'absolute';
        removeBtn.style.top = '5px';
        removeBtn.style.right = '5px';
        removeBtn.style.background = '#dc3545';
        removeBtn.style.color = '#fff';
        removeBtn.style.border = 'none';
        removeBtn.style.borderRadius = '50%';
        removeBtn.style.width = '24px';
        removeBtn.style.height = '24px';
        removeBtn.style.cursor = 'pointer';
        removeBtn.style.fontSize = '18px';
        removeBtn.style.lineHeight = '1';
        removeBtn.style.display = 'flex';
        removeBtn.style.alignItems = 'center';
        removeBtn.style.justifyContent = 'center';
        removeBtn.onclick = function() {
          // Remove image by matching image path
          const imagePath = imgData.image;
          const updatedImages = existingImages.filter(img => img.image !== imagePath);
          existingImagesInput.value = JSON.stringify(updatedImages);
          handleEditImagePreview(input);
        };
        
        previewDiv.appendChild(img);
        previewDiv.appendChild(removeBtn);
        previewContainer.appendChild(previewDiv);
      });
    }
    
    // Update file name display
    if (fileNameDisplay) {
      if (files && files.length > 0) {
        const fileCount = files.length;
        fileNameDisplay.textContent = fileCount + ' new file' + (fileCount > 1 ? 's' : '') + ' selected';
        fileNameDisplay.classList.add('has-file');
      } else {
        fileNameDisplay.textContent = '';
        fileNameDisplay.classList.remove('has-file');
      }
    }
    
    if (files && files.length > 0) {
      previewPlaceholder.style.display = 'none';
      previewContainer.style.display = 'grid';
      
      const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
      const maxSize = 5 * 1024 * 1024; // 5MB in bytes
      let validFiles = [];
      let fileIndex = existingImages.length;
      
      // Process each new file
      Array.from(files).forEach((file, fileIndex) => {
        // Validate file type
        if (!allowedTypes.includes(file.type)) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'Invalid File Type',
              text: file.name + ' is not a valid image file (JPEG, PNG, GIF, or WebP)'
            });
          }
          return;
        }
        
        // Validate file size
        if (file.size > maxSize) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'File Too Large',
              text: file.name + ' exceeds 5MB limit. Please choose a smaller image.'
            });
          }
          return;
        }
        
        validFiles.push(file);
        
        // Create preview element
        const reader = new FileReader();
        reader.onload = function(e) {
          const previewDiv = document.createElement('div');
          previewDiv.style.position = 'relative';
          previewDiv.style.aspectRatio = '1';
          previewDiv.style.overflow = 'hidden';
          previewDiv.style.borderRadius = '6px';
          previewDiv.style.border = '2px solid #17a2b8';
          previewDiv.style.background = '#ffffff';
          
          const img = document.createElement('img');
          img.src = e.target.result;
          img.style.width = '100%';
          img.style.height = '100%';
          img.style.objectFit = 'cover';
          img.style.display = 'block';
          
          // Remove button for new files - store file index in closure
          const removeBtn = document.createElement('button');
          removeBtn.type = 'button';
          removeBtn.innerHTML = '×';
          removeBtn.style.position = 'absolute';
          removeBtn.style.top = '5px';
          removeBtn.style.right = '5px';
          removeBtn.style.background = '#dc3545';
          removeBtn.style.color = '#fff';
          removeBtn.style.border = 'none';
          removeBtn.style.borderRadius = '50%';
          removeBtn.style.width = '24px';
          removeBtn.style.height = '24px';
          removeBtn.style.cursor = 'pointer';
          removeBtn.style.fontSize = '18px';
          removeBtn.style.lineHeight = '1';
          removeBtn.style.display = 'flex';
          removeBtn.style.alignItems = 'center';
          removeBtn.style.justifyContent = 'center';
          (function(currentIndex) {
            removeBtn.onclick = function() {
              // Remove file from input using stored index
              const dt = new DataTransfer();
              Array.from(input.files).forEach((f, i) => {
                if (i !== currentIndex) dt.items.add(f);
              });
              input.files = dt.files;
              handleEditImagePreview(input);
            };
          })(fileIndex);
          
          previewDiv.appendChild(img);
          previewDiv.appendChild(removeBtn);
          previewContainer.appendChild(previewDiv);
        };
        reader.readAsDataURL(file);
      });
      
      if (validFiles.length === 0 && existingImages.length === 0) {
        previewPlaceholder.style.display = 'block';
        previewContainer.style.display = 'none';
        if (files.length === 0) {
          input.value = '';
        }
      }
    } else {
      if (existingImages.length === 0) {
        previewPlaceholder.style.display = 'block';
        previewContainer.style.display = 'none';
      }
    }
  }
  
  // Attach event handlers for edit modal
  $(document).ready(function() {
    // jQuery event handler
    $(document).on('change', '#edit_picture', function() {
      handleEditImagePreview(this);
    });
    
    // Also attach directly to the element when modal is shown
    $('#editItemModal').on('shown.bs.modal', function() {
      const fileInput = document.getElementById('edit_picture');
      if (fileInput) {
        fileInput.onchange = function() {
          handleEditImagePreview(this);
        };
      }
    });
    
    // Barcode input change handlers
    $('#add_barcode').on('input', function() {
      updateBarcodePreview('add');
    });
    
    $('#edit_barcode').on('input', function() {
      updateBarcodePreview('edit');
    });
  });
</script>
