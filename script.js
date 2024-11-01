$(function() {
    // Hiển thị form và overlay khi nhấn nút "Add product"
$('#btnAddproduct').click(function() {
        $('#addproductForm').fadeIn();
        $('#overlay').fadeIn();
});
    // Hiển thị form thêm thuộc tính (category/tag)
$('#btnAddproperty').click(function() {
        $('#addPropertyForm').fadeIn();
        $('#overlay').fadeIn();
});
$('.close-btn').click(function() {
    $('#addproductForm').fadeOut();
    $('#addPropertyForm').fadeOut();
    $('#editProductForm').fadeOut();
    $('#overlay').fadeOut();
});
$('.ui.dropdown').dropdown({
    onChange: function(value) {
        // Ẩn tất cả các ô nhập liệu
        $('#categoryInput').hide();
        $('#tagInput').hide();
        // Hiển thị ô nhập liệu tương ứng dựa trên lựa chọn
        if (value === 'category') {
            $('#categoryInput').show();
        } else if (value === 'tag') {
            $('#tagInput').show();
        }
    }
});
$('#category-dropdown').dropdown(); 
$('#tag-dropdown').dropdown();
$('#btnAddproduct').on('click', function() {
    $('#loading').show();
    
    $.ajax({
        url: 'ProductManagement.php?action=add_product',
        type: 'POST',
        dataType: 'json',
        data: {
            product_name: $('#productName').val(),
            sku: $('#sku').val(),
            price: $('#price').val(),
            feature_img: $('#featureImg').prop('files')[0], // Nếu bạn sử dụng file upload
            gallery: $('#gallery').prop('files') // Xử lý nhiều file upload nếu cần
        },
        success: function(response) {            
            if (response.status === 'success') {
                console.log(response);
                // Xóa nội dung cũ
                $('#product-details').empty();
                // Hiển thị sản phẩm mới thêm
                $('#product-details').append(
                    '<div class="product-item">' +
                    '<h3>' + response.product.Product_name + '</h3>' +
                    '<p>SKU: ' + response.product.SKU + '</p>' +
                    '<p>Price: ' + response.product.Price + '</p>' +
                    '<img src="' + response.product.Feature_img + '" alt="Feature Image">' +
                    '</div>'
                );
            } else {
                alert(response.message);
            }
        },
        error: function(xhr, status, error) {
            $('#loading').hide();
            console.error('Lỗi: ' + error);
            alert('Có lỗi xảy ra khi thêm sản phẩm.');
        }
    });
});
//Form Delete icon
$(document).on('click', 'a[href*="action=delete_product"]', function(event) {
        event.preventDefault();  // Ngăn hành động mặc định (chuyển trang)
        const deleteUrl = $(this).attr('href');  // Lấy URL cần xóa
        // Hiển thị modal xác nhận xóa
        $('#deleteConfirmationModal').modal('show');
        // Xử lý khi nhấn nút Xóa trong modal
        $('#confirmDeleteButton').off('click').on('click', function() {
            window.location.href = deleteUrl;  // Chuyển hướng đến URL để xóa
        });
        // Đóng modal khi nhấn nút Hủy
$('#cancelDeleteButton').on('click', function() {
    $('#deleteConfirmationModal').modal('hide');  // Ẩn modal
});
});
$('#open-modal').on('click', function(e) {
    e.preventDefault(); // Ngăn chặn hành động mặc định của liên kết
    $('#delete-modal').modal('show'); // Hiện modal
});
// Đóng modal khi nhấn nút Hủy
$('.ui.cancel.button').on('click', function() {
    $('#delete-modal').modal('hide'); // Đóng modal
});
//editProduct
$(document).on('click', 'a[href*="action=edit_product"]', function(e) {
    e.preventDefault();
    var productId = $(this).attr('href').split('id=')[1]; // Lấy ID sản phẩm từ URL

    // Kiểm tra xem productId có hợp lệ không
    if (!productId) {
        console.log("ID sản phẩm không hợp lệ.");
        return;
    }
    // Sử dụng AJAX để lấy dữ liệu sản phẩm từ server
    $.ajax({
        url: 'getProduct.php', // File xử lý để lấy dữ liệu sản phẩm
        method: 'GET',
        data: { id: productId }, // Gửi ID sản phẩm
        dataType: 'json',
        success: function(response) {
            console.log(response);
            // Kiểm tra xem phản hồi có chứa dữ liệu hợp lệ không
            if (response && response.ID) {
                // Hiển thị dữ liệu sản phẩm trong form chỉnh sửa
                $('#edit-product-id').val(response.ID);
                $('#edit-product-name').val(response.Product_name).data('original', response.Product_name); // Lưu giá trị gốc
                $('#edit-sku').val(response.SKU).data('original', response.SKU); // Lưu giá trị gốc
                $('#edit-price').val(response.Price).data('original', response.Price); // Lưu giá trị gốc
                $('#existing-feature-img').val(response.Feature_img);
                $('#existing-gallery').val(response.Gallery);

                // Hiển thị hình ảnh hiện tại nếu có
                if (response.Feature_img) {
                    $('#current-feature-img').attr('src', response.Feature_img).show();
                } else {
                    $('#current-feature-img').hide();
                }

                // Hiển thị gallery hiện tại nếu có
                if (response.Gallery) {
                    var galleryImages = response.Gallery.split(',');
                    $('#current-gallery-images').empty();
                    galleryImages.forEach(function(imgUrl) {
                        $('#current-gallery-images').append('<img src="' + imgUrl + '" width="250" style="margin:5px;">');
                    });
                }
                if (response.category_ids) {
                    var selectedCategories = response.category_ids.split(',');
                    $('#edit-categories').dropdown('set selected', selectedCategories);
                }
        
                // Chọn các tag đã được lưu
                if (response.tag_ids) {
                    var selectedTags = response.tag_ids.split(',');
                    $('#edit-tags').dropdown('set selected', selectedTags);
                }
                // Mở form chỉnh sửa
                $('#editProductForm').fadeIn();
                $('#overlay').fadeIn();
            } else {
                console.log("Không có dữ liệu sản phẩm hợp lệ.");
            }
        },
        error: function(xhr, status, error) {
            console.log("Lỗi: " + error);
            console.log("Mã trạng thái: " + xhr.status);
            console.log("Chi tiết phản hồi từ server: " + xhr.responseText);
        }
    });
});
$('#editProductForm').submit(function(event) {
    event.preventDefault();
    // Thu thập dữ liệu từ các trường form
    var productID = $('#edit-product-id').val();
    var productName = $('#edit-product-name').val().trim();
    var sku = $('#edit-sku').val().trim();
    var price = $('#edit-price').val().trim();

    // Kiểm tra giá trị giá
    if (price === "Khong co gia" || (price !== "" && (isNaN(price) || price < 0))) {
        console.log("Giá không hợp lệ");
        return; // Ngừng thực thi nếu giá không hợp lệ
    }
    $('#edit-price').val(price); 
    var existingFeatureImg = $('#existing-feature-img').val().trim();
    var existingGallery = $('#existing-gallery').val().trim();
    var category = $('#edit-categories').val();
    var tags = $('#edit-tags').val();

    // Lấy giá trị gốc từ dữ liệu đã lưu
    var originalProductName = $('#edit-product-name').data('original') ? $('#edit-product-name').data('original').trim() : "";
    var originalSku = $('#edit-sku').data('original') ? $('#edit-sku').data('original').trim() : "";
    var originalPrice = $('#edit-price').data('original') ? $('#edit-price').data('original').trim() : "";
    // Chuyển đổi giá trị price thành số hoặc null
    var currentPrice = price !== "" ? parseFloat(price) : null;
    var originalPriceValue = (originalPrice === "" || isNaN(parseFloat(originalPrice))) ? null : parseFloat(originalPrice);
    // Kiểm tra xem có giá trị nào thay đổi không
    var isChanged = (
        productName !== originalProductName ||
        sku !== originalSku ||
        (currentPrice !== originalPriceValue) && !(currentPrice === null && originalPriceValue === null) ||
        (existingFeatureImg && existingFeatureImg !== $('#current-feature-img').attr('src')) ||
        (existingGallery && existingGallery !== $('#current-gallery-images img').map(function() { return $(this).attr('src'); }).get().join(','))
    );
    console.log("Is Changed: ", isChanged);
    // Hiển thị giá trị của isChanged
    if (!isChanged) {
        console.log("Không có thay đổi nào được thực hiện.");
        $('#editProductForm').hide();
        $('#noChangesNote').animate({ right: '20px' }, 500).delay(1500).animate({ right: '-300px' }, 500);
        $('#overlay').hide();
        return; 
    }

    // Tạo đối tượng dữ liệu cho AJAX
    var data = {
        product_id: productID,
        product_name: productName,
        sku: sku,
        price: price,
        category: category,
        tag: tags,
        action: 'edit_product'
    };
    console.log(data);
    $.ajax({
        url: 'checkSKU.php', // Đường dẫn đến tệp PHP kiểm tra SKU
        type: 'POST',
        data: { sku: sku, product_id: productID }, // Gửi SKU và ID sản phẩm
        success: function(response) {
            if (response === 'exists') {
                
                $('#editProductForm').show();
                $('#overlay').hide();
                console.log("SKU đã tồn tại. Vui lòng chọn SKU khác.");
                $('#check').show();
                $('#check').animate({ right: '20px' }, 500).delay(1500).animate({ right: '-300px' }, 500);
                return; 
            }
            $.ajax({
                url: 'ProductManagement.php',
                type: 'POST',
                data: data,
                success: function(response) {
                    console.log(response);
                    $('#editProductForm').hide();
                    $('body').html(response).show();
                    $('#Changes').animate({ right: '20px' }, 500).delay(1500).animate({ right: '-300px' }, 500);
                },
                error: function(xhr, status, error) {
                    console.error('Lỗi:', error);
                }
            });
        },
        error: function(xhr, status, error) {
            console.error('Lỗi kiểm tra SKU:', error);
        }
    });
});
//Searching product 
$(document).ready(function() {
    let request;
    // Xử lý khi nhập từ khóa vào ô tìm kiếm
    $('#search').on('input', function() {
        var searchTerm = $(this).val();
        if (request) {
            request.abort(); // Hủy yêu cầu trước đó nếu có
        }
        if (searchTerm.length > 0) {
            request = $.ajax({
                url: 'Search.php', // Gửi yêu cầu tìm kiếm đến Search.php
                method: 'GET',
                data: { search: searchTerm },
                success: function(data) {
                    $('#search-results').html(data).show(); // Hiển thị kết quả tìm kiếm
                }
            });
        } else {
            $('#search-results').hide(); // Ẩn danh sách kết quả nếu không có từ khóa
        }
    });

    // Xử lý khi nhấp vào một sản phẩm trong danh sách tìm kiếm
    $(document).on('click', '.result-item', function() {
        var productId = $(this).data('id'); // Lấy ID sản phẩm
        fetchProductDetails(productId); // Gọi hàm lấy chi tiết sản phẩm
        $('#search-results').hide(); // Ẩn danh sách sau khi chọn
    });
    // Hàm lấy chi tiết sản phẩm và chèn vào bảng
    function fetchProductDetails(productId) {
        $.ajax({
            url: 'FetchProduct.php', // Gửi yêu cầu đến getProduct.php
            method: 'GET',
            data: { id: productId },
            success: function(response) {
                // Chèn HTML trực tiếp vào phần tử
                $('#product-details').html(response);
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                $('#product-details').html('<div class="error">Lỗi kết nối đến server.</div>');
            }
        });
    }
    
});
// Filter
$('#btnFilter').on('click', function() {
    // Thu thập dữ liệu lọc
    var sortBy = $('#sort-by').val();
    var sortOrder = $('#sort-order').val();
    var categories = $('#category-dropdown .item.active').map(function() {
        return $(this).data('value');
    }).get();
    var tags = $('#tag-dropdown .item.active').map(function() {
        return $(this).data('value');
    }).get();   
    var startDate = $('#start-date').val();
    var endDate = $('#end-date').val();
    var priceFrom = $('#price-from').val(); // Sử dụng id
    var priceInto = $('#price-into').val(); // Sử dụng id
    console.log('Category id',categories);
    console.log('Tag id',tags);
      if (parseFloat(priceFrom) < 0 || parseFloat(priceInto) < 0) {
            alert('Vui lòng nhập giá hợp lệ cho Price From và Price Into!');
            return;
        }
    $.ajax({
        url: 'filterProducts.php',
        type: 'POST',
        data: {
            sortBy: sortBy,
            sortOrder: sortOrder,
            categories: categories,
            tags: tags,
            startDate: startDate,
            endDate: endDate,
            priceFrom: priceFrom,
            priceInto: priceInto
        },
        success: function(response) {
            console.log(response);
            $('#product-details').html(response);
        },
        error: function() {
            alert('Lỗi khi lọc sản phẩm');
        }
    });
});
// SyncDataFromWeb
$('#sync').on('click', function() {
    $('#loading').show(); 
    $('#overlay').show(); 
    $.ajax({
        url: 'Scraping.php',
        method: 'POST',
        data: { action: 'scrape' }, 
        success: function(response) {
            $('#result').html(response); 
        },
        error: function(xhr, status, error) {
            $('#result').html('Có lỗi xảy ra: ' + error); // Hiển thị thông báo lỗi
        },
        complete: function() {
            $('#loading').hide(); // Ẩn thông báo loading khi hoàn tất
            $('#overlay').hide(); // Ẩn overlay khi hoàn tất
            location.reload(); // Tải lại trang
        }
    });
});
});



