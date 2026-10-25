<?php
/**
 * @var $company object the current company information
 * @var $pageTexts array the translated string conglomerated for this view
 * @var $products array the list of products
 */
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Image Upload</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .preview-container {
            max-width: 300px;
            margin: 20px auto;
        }
        .image-preview {
            width: 100%;
            height: 200px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .result-container {
            margin-top: 20px;
            padding: 15px;
            border-radius: 4px;
            display: none;
        }
        .success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center mb-4">Test Image Upload</h1>
                <p class="text-center">This page allows you to test uploading images for products.</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5>Upload Image for Product</h5>
                    </div>
                    <div class="card-body">
                        <form id="uploadForm" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="product_id">Select Product</label>
                                <select class="form-control" id="product_id" name="product_id" required>
                                    <option value="">Select a product</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?= $product->pro_id ?>"><?= $product->name ?> (<?= $product->sku ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="image">Select Image</label>
                                <input type="file" class="form-control-file" id="image" name="image" accept="image/*" required>
                            </div>

                            <div class="preview-container">
                                <div class="image-preview" id="imagePreview">
                                    <span>Image Preview</span>
                                </div>
                            </div>
                            <input type="hidden" name="action" value="upload"/>
                            <button type="submit" class="btn btn-primary btn-block">Upload Image</button>
                        </form>

                        <div class="result-container" id="resultContainer">
                            <h5 id="resultTitle"></h5>
                            <p id="resultMessage"></p>
                            <div id="resultDetails"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12 text-center">
                <a href="<?= company_url('products') ?>" class="btn btn-secondary">Back to Products</a>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Image preview
            $('#image').change(function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#imagePreview').html('<img src="' + e.target.result + '" alt="Preview">');
                    }
                    reader.readAsDataURL(file);
                } else {
                    $('#imagePreview').html('<span>Image Preview</span>');
                }
            });

            // Form submission
            $('#uploadForm').submit(function(e) {
                e.preventDefault();

                const formData = new FormData(this);

                $.ajax({
                    url: '<?= company_url('products/testImageUpload') ?>',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    beforeSend: function() {
                        $('#resultContainer').removeClass('success error').hide();
                        $('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Uploading...');
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#resultContainer').addClass('success').removeClass('error');
                            $('#resultTitle').text('Success!');
                            $('#resultMessage').text(response.message);

                            let details = '<p><strong>Image Name:</strong> ' + response.image_name + '</p>';
                            details += '<p><strong>Product ID:</strong> ' + response.product_id + '</p>';
                            details += '<p>You can now go to the product edit page to see the uploaded image.</p>';
                            details += '<p><a href="' + '<?= company_url('products/edit/') ?>' + response.product_id + '" class="btn btn-sm btn-success">Edit Product</a></p>';

                            $('#resultDetails').html(details);
                        } else {
                            $('#resultContainer').addClass('error').removeClass('success');
                            $('#resultTitle').text('Error!');
                            $('#resultMessage').text(response.message);
                            $('#resultDetails').html('');
                        }

                        $('#resultContainer').show();
                        $('button[type="submit"]').prop('disabled', false).text('Upload Image');
                    },
                    error: function(xhr, status, error) {
                        $('#resultContainer').addClass('error').removeClass('success');
                        $('#resultTitle').text('Error!');
                        $('#resultMessage').text('An error occurred while uploading the image.');
                        $('#resultDetails').html('<p>Error details: ' + error + '</p>');

                        $('#resultContainer').show();
                        $('button[type="submit"]').prop('disabled', false).text('Upload Image');
                    }
                });
            });
        });
    </script>
</body>
</html>
