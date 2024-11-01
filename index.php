<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Product_Management</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.css">
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
<!-- Form loading du lieu -->
<div id="loading" class="ui segment" style="position:fixed; right:30%; left: 30%; z-index:1001; display:none; margin-top: 12rem;">
  <div class="ui active dimmer" style="color:#e1e1e1;">
    <div class="ui indeterminate text loader"></div>
  </div>
  <p style="color:black;"> DANG LAY DU LIEU</p>
</div>
<!-- MESSage -->
<div class="notification" id="Changes" style="background-color: green;" >Cật nhật thành công</div>
<div class="notification" id="noChangesNote">Không có thay đổi nào được thực hiện</div>
<div class="notification" id="addNotice" style="background-color: green;">Thêm thành công</div>
<div class="notification" id="check">SKU trung vui long nhap lai</div>

<!-- Form xoa Sp -->
<div class="ui tiny modal" id="deleteConfirmationModal">
    <div class="content">
        <p>Bạn có chắc chắn muốn xóa sản phẩm này?</p>
    </div>
    <div class="actions">
    <button class="ui red button" id="confirmDeleteButton">Xóa</button>
    <button class="ui button" id="cancelDeleteButton">Hủy</button>     
    </div>
</div>
<!-- dell all -->
<div class="ui modal" id="delete-modal">
        <div class="content">
            <p>Bạn có chắc chắn muốn xóa tất cả sản phẩm?</p>
        </div>
        <div class="actions">
            <a href="ProductManagement.php?delete_all=true" class="ui red button">Xóa tất cả</a>
            <div class="ui cancel button">Hủy</div> 
        </div>
</div>
<!-- header container -->
<div class="container">
    <div class="title">
        <div class="btn-container">
        <button class="ui button" id="btnAddproduct">Add product</button>
        <button class="ui button" id="btnAddproperty">Add property</button>
        <button class="ui button" id="sync">Sync from VillaTheme</button>
        </div>
<div class="ui input">
        <input type="text" name="search" id="search" placeholder="Search product...">
        <i></i>
        <div id="search-results" class="ui relaxed divided list" style="display: none;"></div>
</div>
</div>
<!-- Filter section -->
<div class="Filter-container">
        <select class="ui dropdown" id="sort-by">
            <option value="Date">Date</option>
            <option value="Price">Price</option>
            <option value="Name">Name</option>
        </select>
        <select class="ui dropdown" id="sort-order">
            <option value="ASC">Asc</option>
            <option value="DESC">Desc</option>
        </select>
    <div class="field">
    <div class="ui multiple selection dropdown" id="category-dropdown">
            <input type="hidden" name="category[]">
            <i class="dropdown icon"></i>
            <div class="default text" style="color: black;">Category</div>
            <div class="menu">
                <?php
                include("db.php");
                $sql_category = "SELECT ID, Category_name FROM category";
                $result_category = $conn->query($sql_category);
                if ($result_category->num_rows > 0) {
                    while ($row = $result_category->fetch_assoc()) {
                        echo '<div class="item" data-value="' . $row['ID'] . '">' . $row['Category_name'] . '</div>';
                    }
                }
                ?>
            </div>
    </div>
    </div>
    <div class="field">
        <div class="ui multiple selection dropdown" id="tag-dropdown">
            <input type="hidden" name="tag[]">
            <i class="dropdown icon"></i>
            <div class="default text" style="color:black;"> Select Tag</div>
            <div class="menu">
            <?php
            $sql_tags = "SELECT ID, Tag_name FROM tag";
            $result_tags = $conn->query($sql_tags);
            if ($result_tags->num_rows > 0) {
                while ($row = $result_tags->fetch_assoc()) {
                    echo '<div class="item" data-value="' . $row['ID'] . '">' . $row['Tag_name'] . '</div>';
                }
            }
            ?>
            </div>
        </div>
    </div>
        <div><input type="date" name="startDate" placeholder="Start Date"></div>
        <div><input type="date" name="endDate" placeholder="End Date"></div>
<div class="ui input">
    <input type="text" id="price-from" placeholder="Price From" min="1" step="0.01">
</div>
<div class="ui input">
    <input type="text" id="price-into" placeholder="Price Into" min="1" step="0.01">
</div>

        <button class="ui button" id="btnFilter">Filter</button>
</div>

</div>
<!-- Overlay -->
<div class="overlay" id="overlay"></div>
<!-- Form Add product -->
<div id="addproductForm" class="Form-container">
    <span id="close-btn" class="close-btn">X</span>
    <h2 class="ui header">Product Form</h2>
    <div class="form-content">
        <form id="addProductContent" class="ui form" action="ProductManagement.php" method="POST" enctype="multipart/form-data">
        <div class="field">
            <input type="date" name="date" style="display:none">
            </div>
                <div class="field">
                    <label>Tên sản phẩm</label>
                    <input type="text" name="product_name" placeholder="Nhập tên sản phẩm" required>
                </div>
                <div class="field">
                    <label>SKU</label>
                    <input type="text" name="sku" placeholder="Nhập SKU" required>
                </div>
                <div class="field">
                    <label>Giá</label>
                    <input type="number" step="0.01" name="price" placeholder="Nhập giá sản phẩm" min="0" required>
                </div>
                <div class="field">
                    <label>Hình ảnh chính (Feature Image)</label>
                    <input type="file" name="feature_img">
                </div>
                <div class="field">
                    <label>Gallery (Nhiều hình ảnh)</label>
                    <input type="file" name="Gallery[]" multiple>
                </div>
                <div class="field">
    <label>Danh mục (Category)</label>
    <select class="ui dropdown" name="category[]" multiple>
        <option value="" disabled="" selected="">Category</option>
        <?php
        include("db.php");
        $sql_categories = "SELECT ID, Category_name FROM category";
        $result_categories = $conn->query($sql_categories);
        if ($result_categories->num_rows > 0) {
            while ($row = $result_categories->fetch_assoc()) {
                echo '<option value="' . $row['ID'] . '">' . $row['Category_name'] . '</option>';
            }
        }
        ?>
    </select>
</div>
<div class="field">
    <label>Thẻ (Tag)</label>
    <select class="ui dropdown" name="tag[]" multiple>
        <option value="" disabled="" selected="">Select tag </option>
        <?php
        $sql_tags = "SELECT ID, Tag_name FROM tag";
        $result_tags = $conn->query($sql_tags);
        if ($result_tags->num_rows > 0) {
            while ($row = $result_tags->fetch_assoc()) {
                echo '<option value="' . $row['ID'] . '">' . $row['Tag_name'] . '</option>';
            }
        }
        ?>
    </select>
</div>
</form>
</div>
<button class="ui button" type="submit" name="action" value="add_product" form="addProductContent">Thêm sản phẩm</button>

</div>
<!-- Form Add Property -->
<div id="addPropertyForm" class="ui modal" style="display:none">
    <span id="close-btn" class="close-btn">X</span>
    <div class="header">Property Form</div>
    <form action="ProductManagement.php" method="POST">
        <div class="ui dropdown">
            <div class="text">Chọn tag/category</div>
            <i class="dropdown icon"></i>
            <div class="menu">
                <div class="item" data-value="category">Category</div>
                <div class="item" data-value="tag">Tag</div>
            </div>
        </div>
        <input type="text" id="categoryInput" name="category" placeholder="Nhập tên category" style="display:none;">
        <input type="text" id="tagInput" name="tag" placeholder="Nhập tên tag" style="display:none;">
        <button class="ui button" type="submit" name="action" value="add_property">Thêm thuộc tính</button>
    </form>
</div>
<!-- Edit Product Form -->
<div id="editProductForm" class="Form-container">
<span id="close-btn" class="close-btn">X</span>
<h2 class="ui header">Edit Product Form</h2>
<div class="form-content">
        <form id="editProductContent" class="ui form" action="ProductManagement.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="product_id" id="edit-product-id">
            <input type="hidden" name="existing_feature_img" id="existing-feature-img">
            <input type="hidden" name="existing_gallery" id="existing-gallery">
            <div class="field">
                <label>Tên sản phẩm</label>
                <input type="text" name="product_name" id="edit-product-name" placeholder="Nhập tên sản phẩm" required>
            </div>
            <div class="field">
                <label>SKU</label>
                <input type="text" name="sku" id="edit-sku" placeholder="Nhập SKU">
            </div>
            <div class="field">
                <label>Giá</label>
                <input type="number" step="0.01" name="price" id="edit-price" placeholder="Nhập giá sản phẩm" min="0">
            </div>
            <div class="field">
                <label>Hình ảnh chính (Feature Image)</label>
                <input type="file" name="feature_img">
                <img id="current-feature-img" width="250" style="display:none;">
            </div>
            <div class="field">
                <label>Gallery (Nhiều hình ảnh)</label>
                <input type="file" name="Gallery[]" multiple>
                <div id="current-gallery-images"></div>
            </div>
            <div class="field">
                <label>Danh mục (Category)</label>
                <select class="ui dropdown" name="category[]" id="edit-categories" multiple>
                    <option value="" disabled="" selected="">Chọn category</option>
                    <?php
                    include("db.php");
                    $sql_categories = "SELECT ID, Category_name FROM category";
                    $result_categories = $conn->query($sql_categories);
                    if ($result_categories->num_rows > 0) {
                        while ($row = $result_categories->fetch_assoc()) {
                            echo '<option value="' . $row['ID'] . '">' . htmlspecialchars($row['Category_name']) . '</option>';
                        }
                    }
                    ?>                
                </select>
            </div>
            <div class="field">
                <label>Thẻ (Tag)</label>
                <select class="ui dropdown" name="tag[]" id="edit-tags" multiple>
                    <option value="" disabled="" selected="">Chọn tag</option>
                    <?php
                    $sql_tags = "SELECT ID, Tag_name FROM tag";
                    $result_tags = $conn->query($sql_tags);
                    if ($result_tags->num_rows > 0) {
                        while ($row = $result_tags->fetch_assoc()) {
                            echo '<option value="' . $row['ID'] . '">' . htmlspecialchars($row['Tag_name']) . '</option>';
                        }
                    }
                    ?>                
                </select>
            </div>

        </form>

</div>
<button id="submitEditProduct" class="ui button" type="submit" name="action" value="edit_product" form="editProductContent">Cập nhật sản phẩm</button>
</div>
<!-- Table Product -->
<div class="ui container">
            <table class="ui celled table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Product Name</th>
                        <th>SKU</th>
                        <th>Price</th>
                        <th>Feature Image</th>
                        <th>Gallery</th>
                        <th>Categories</th>
                        <th>Tags</th>
                        <th>Action
                        <a href="#" class="ui icon button" id="open-modal">
                            <i class="trash icon"></i>
                        </a>
                        </th>   
                    </tr>
                </thead>
                <tbody id="product-details">
                <?php
include("db.php");
$limit = 5;
// Lấy trang hiện tại từ GET (mặc định là 1 nếu không có)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit; // Tính toán offset

// Lấy tổng số sản phẩm
$total_sql = "SELECT COUNT(*) as total FROM Product";
$total_result = $conn->query($total_sql);
$total_row = $total_result->fetch_assoc();
$total_products = $total_row['total'];
$total_pages = ceil($total_products / $limit); // Tính toán tổng số trang

// Lấy dữ liệu từ cơ sở dữ liệu để hiển thị
$sql = "SELECT p.*, 
GROUP_CONCAT(DISTINCT c.Category_name SEPARATOR ', ') AS category, 
GROUP_CONCAT(DISTINCT t.Tag_name SEPARATOR ', ') AS tag
FROM
    Product p
LEFT JOIN product_category pc ON
    p.id = pc.product_id
LEFT JOIN category c ON
    pc.category_id = c.id
LEFT JOIN product_tag pt ON
    p.id = pt.product_id
LEFT JOIN tag t ON
    pt.tag_id = t.id
GROUP BY p.id
LIMIT $limit OFFSET $offset"; // Thêm limit và offset vào truy vấn
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Hiển thị dữ liệu
    while ($row = $result->fetch_assoc()) {
        echo '<tr>
                <td>';
        
        // Chuyển đổi và hiển thị ngày theo định dạng dd/mm/yyyy
        $date = new DateTime($row["Date"]);
        echo $date->format("d/m/Y");
        
        echo '</td>
                <td>' . htmlspecialchars($row["Product_name"]) . '</td>
                <td>' . htmlspecialchars($row["SKU"]) . '</td>
                <td>';
                if (!empty($row["Price"])) {
                    // Kiểm tra xem giá trị có phải là số hay không
                    if (is_numeric($row["Price"])) {
                        echo '$' . htmlspecialchars($row["Price"]);
                    } else {
                        echo htmlspecialchars($row["Price"]);
                    }
                }    
                echo '</td>
                        <td>';    
        $feature_img = htmlspecialchars($row["Feature_img"]);
        // Kiểm tra xem có phải là URL hợp lệ hay không
        if (filter_var($feature_img, FILTER_VALIDATE_URL)) {
            echo '<div>
                    <img src="' . $feature_img . '" alt="Image" width="100" style="margin-top: 5px;">
                  </div>';
        } 
        // Kiểm tra xem có phải là đường dẫn tệp trên máy chủ hay không
        elseif (file_exists('upload/' . $feature_img)) {
            echo '<div>
                    <img src="upload/' . $feature_img . '" alt="Image" width="100" style="margin-top: 5px;">
                  </div>';
        } else {
            // Nếu không có ảnh hoặc không hợp lệ
            echo '<div>Không có hình ảnh.</div>';
        }
        echo '</td>
                <td>';
        echo'<div class="gallery-container">';
        // Lấy dữ liệu từ cột Gallery
        $gallery_string = $row["Gallery"];
        $upload_directory = 'upload/'; // Thư mục lưu trữ ảnh đã tải về
        
        // Kiểm tra nếu là JSON array hoặc chuỗi phân tách bởi dấu phẩy
        $gallery_images = json_decode($gallery_string, true);
        
        if ($gallery_images === null) {
            // Nếu không phải JSON, xử lý dưới dạng chuỗi phân tách bởi dấu phẩy
            $gallery_images = explode(',', $gallery_string);
        }
        
        if (!empty($gallery_images) && is_array($gallery_images)) {
            foreach ($gallery_images as $gallery_image) {
                $gallery_image = trim($gallery_image); // Loại bỏ khoảng trắng thừa
        
                // Lấy tên file từ URL hoặc tên ảnh đã tải lên
                $image_name = basename($gallery_image);
        
                // Đường dẫn file trong thư mục
                $image_path = $upload_directory . $image_name;
        
                // Kiểm tra nếu ảnh đã tồn tại trong thư mục (tức là đã được tải lên trước đó)
                if (file_exists($image_path)) {
                    // Hiển thị ảnh từ thư mục, ẩn chức năng tải ảnh
                    echo '<img src="' . htmlspecialchars($image_path) . '" alt="Gallery Image" width="100" style="margin:5px;">';
                } else {
                    // Nếu ảnh chưa tồn tại trong thư mục, kiểm tra URL hợp lệ và tải ảnh về
                    if (filter_var($gallery_image, FILTER_VALIDATE_URL)) {
                        // Tải ảnh về thư mục nếu chưa tồn tại
                        if (copy($gallery_image, $image_path)) {
                            echo 'Tải ảnh về: ' . htmlspecialchars($image_name) . '<br>';
                        } else {
                            echo 'Lỗi khi tải ảnh về: ' . htmlspecialchars($image_name) . '<br>';
                            continue; // Bỏ qua nếu lỗi
                        }
        
                        // Hiển thị ảnh từ thư mục sau khi tải về
                        echo '<img src="' . htmlspecialchars($image_path) . '" alt="Gallery Image" width="100" style="margin:5px;">';
                    } else {
                        echo '<div>Ảnh không hợp lệ: ' . htmlspecialchars($gallery_image) . '</div>';
                    }
                }
            }
        } else {
            echo 'Không có ảnh trong gallery.';
        }
        
        echo'</div>';
        echo '</td>
              <td>' . ($row["category"] ? htmlspecialchars($row["category"]) : 'Chưa có danh mục') . '</td>
              <td>' . ($row["tag"] ? htmlspecialchars($row["tag"]) : 'Chưa có thẻ') . '</td>
              <td>
                <a href="ProductManagement.php?action=edit_product&id=' . $row["ID"] . '" class="ui icon button edit-button"><i class="edit icon"></i></a>
                <a href="ProductManagement.php?action=delete_product&id=' . $row["ID"] . '" class="ui icon button"><i class="trash icon"></i></a>
              </td>
            </tr>';
    }
} else {
    echo "<tr><td colspan='9'>Chưa có sản phẩm nào.</td></tr>";
}

$conn->close();
?>

                </tbody>
                
            </table>
</div>
<!-- Pagination -->
<div class="pagination">
            <?php
            if ($page > 1) {
                echo '<a href="?page=' . ($page - 1) . '"><i class="arrow left icon"></i></a>';
            }
            for ($i = 1; $i <= $total_pages; $i++) {
                echo '<a href="?page=' . $i . '" class="' . ($i == $page ? 'active' : '') . '">' . $i . '</a>';
            }
            if ($page < $total_pages) {
                echo '<a href="?page=' . ($page + 1) . '"><i class="arrow right icon"></i></a>';
            }
            ?>
</div>
        <!-- Scripts -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.js"></script>
        <script src="script.js"></script>
    </body>
</html>
