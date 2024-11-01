<?php
// Kết nối cơ sở dữ liệu
include("db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Nhận dữ liệu từ form
    $action = $_POST['action'] ?? '';

    if ($action == "add_product") {
        // Xử lý thêm sản phẩm
        addProduct($conn);
    } elseif ($action == "add_property") {
        // Xử lý thêm thuộc tính
        addProperty($conn);
    }elseif ($action == "edit_product") {
        editProduct($conn);  // Call editProduct to update the product
    }
}
if ($_SERVER["REQUEST_METHOD"] == "GET") {
        if (isset($_GET['action']) && $_GET['action'] == "delete_product") {
            $id = $_GET['id'] ?? '';  // Lấy ID từ GET
            if ($id) {
                deleteProductById($id, $conn);  // Xóa sản phẩm
            }
        }
    }
function addProduct($conn) {
        $date = date('Y-m-d'); 
        $product_name = $_POST['product_name'];
        $sku = $_POST['sku'];
        $price = $_POST['price'];
    
        // Xử lý feature_img
        $feature_img_name = handleFileUpload('feature_img');
    
        // Xử lý Gallery
        $gallery_images = handleGalleryUpload('Gallery');
        $gallery_json = json_encode($gallery_images);
    
        // Thêm sản phẩm vào bảng product
        $sql_product = "INSERT INTO product (Date, Product_name, SKU, Price, Feature_img, Gallery) 
                        VALUES ('$date', '$product_name', '$sku', '$price', '$feature_img_name', '$gallery_json')";
    
        if ($conn->query($sql_product) === TRUE) {
            $product_id = $conn->insert_id; // Lấy ID của sản phẩm vừa được thêm
    
            // Lấy thông tin sản phẩm vừa thêm
            $new_product = [
                'ID' => $product_id,
                'Product_name' => $product_name,
                'SKU' => $sku,
                'Price' => $price,
                'Feature_img' => $feature_img_name,
                'Gallery' => $gallery_images // Mảng hình ảnh gallery
            ];
    
            linkCategoriesAndTags($conn, $product_id);
            
            // Trả về dữ liệu sản phẩm vừa thêm
            echo json_encode([$new_product]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi: ' . $conn->error]);
        }
        exit();
    }
    
// Hàm xử lý file upload cho feature image
function handleFileUpload($input_name) {
    if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] == 0) {
        $file_name = basename($_FILES[$input_name]['name']);
        $target_dir = "C:\\xampp\\htdocs\\Finals\\upload\\";
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES[$input_name]['tmp_name'], $target_file)) {
            return $file_name;
        } else {
            echo "Có lỗi trong việc tải lên ảnh.";
            return "";
        }
    }
    return "";
}
// Hàm xử lý upload gallery
function handleGalleryUpload($input_name) {
    $gallery_images = [];
    if (isset($_FILES[$input_name]) && !empty($_FILES[$input_name]['name'][0])) {
        $gallery_files = $_FILES[$input_name];
        $total_files = count($gallery_files['name']);

        for ($i = 0; $i < $total_files; $i++) {
            if ($gallery_files['error'][$i] == 0) {
                $gallery_image_name = basename($gallery_files['name'][$i]);
                $target_gallery_file = "C:\\xampp\\htdocs\\Finals\\upload\\" . $gallery_image_name;

                if (move_uploaded_file($gallery_files['tmp_name'][$i], $target_gallery_file)) {
                    $gallery_images[] = $gallery_image_name;
                } else {
                    echo "Có lỗi trong việc tải lên ảnh: " . htmlspecialchars($gallery_image_name);
                }
            }
        }
    }
    return $gallery_images;
}
// Hàm liên kết sản phẩm với category và tag
function linkCategoriesAndTags($conn, $product_id) {
    // Liên kết với categories
    if (isset($_POST['category']) && is_array($_POST['category'])) {
        foreach ($_POST['category'] as $category_id) {
            $check_category = "SELECT COUNT(*) as count FROM category WHERE ID = '$category_id'";
            $result_check = $conn->query($check_category);
            $row_check = $result_check->fetch_assoc();

            if ($row_check['count'] > 0) {
                $sql_category = "INSERT INTO product_category (product_id, category_id) VALUES ('$product_id', '$category_id')";
                $conn->query($sql_category);
            } else {
                echo "Danh mục với ID $category_id không tồn tại!";
            }
        }
    }

    // Liên kết với tags
    if (isset($_POST['tag']) && is_array($_POST['tag'])) {
        foreach ($_POST['tag'] as $tag_id) {
            $sql_tag = "INSERT INTO product_tag (product_id, tag_id) VALUES ('$product_id', '$tag_id')";
            $conn->query($sql_tag);
        }
    } else {
        echo "Không có thẻ nào được chọn!";
    }
}
// Hàm thêm thuộc tính (category/tag)
function addProperty($conn) {
    $category = $_POST['category'] ?? '';
    $tag = $_POST['tag'] ?? '';
    // Thêm danh mục
    if (!empty($category)) {
        $sql_category = "INSERT INTO category (Category_name) VALUES ('$category')";
        if ($conn->query($sql_category) === TRUE) {
        } else {
            echo "Lỗi: " . $conn->error;
        }
    }
    // Thêm tag
    if (!empty($tag)) {
        $sql_tag = "INSERT INTO tag (Tag_name) VALUES ('$tag')";
        if ($conn->query($sql_tag) === TRUE) {
        } else {
            echo "Lỗi: " . $conn->error;
        }
    }
    header("Location: index.php");
    exit();
}
// Hàm xóa sản phẩm theo ID
function deleteProductById($id,$conn) {
    // Sử dụng Prepared Statements để tránh SQL Injection
    $sql = "DELETE FROM product WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            header("Location: index.php");
            exit();
        } else {
            echo "Lỗi: " . $stmt->error;
        }
        
        $stmt->close();
    } else {
        echo "Lỗi khi chuẩn bị truy vấn: " . $conn->error;
    }
}
// Hàm xóa tất cả sản phẩm
function deleteAllProducts($conn) {
    // Xóa tất cả sản phẩm
    $sql_delete = "DELETE FROM product";    
    
    if ($conn->query($sql_delete) === TRUE) {
        // Đặt lại AUTO_INCREMENT
        $sql_reset_auto_increment = "ALTER TABLE product AUTO_INCREMENT = 1";
        if ($conn->query($sql_reset_auto_increment) === FALSE) {
            // Nếu có lỗi khi đặt lại AUTO_INCREMENT
            header("Location: index.php?message=error&details=Lỗi khi đặt lại AUTO_INCREMENT: " . $conn->error);
            exit();
        }
        header("Location: /Hoangtung_hoangmanhtung/");
        exit();
    } else {
        header( $conn->error);
        exit();
    }
}
// Kiểm tra yêu cầu xóa sản phẩm
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    deleteProductById($id, $conn);
} elseif (isset($_GET['delete_all']) && $_GET['delete_all'] == 'true') {
    deleteAllProducts($conn);
} 
// Hàm sửa sản phẩm
function editProduct($conn) {
    $product_id = $_POST['product_id'] ?? '';

    if (!$product_id) {
        // Nếu sản phẩm không hợp lệ, chuyển hướng về trang index và hiển thị thông báo
        header("Location: index.php?error=Sản phẩm không hợp lệ.");
        exit();
    }
    
    // Lấy thông tin sản phẩm hiện tại
    $sql_fetch_images = "SELECT Feature_img, Gallery, Product_name, SKU, Price FROM product WHERE ID = ?";
    $stmt_fetch_images = $conn->prepare($sql_fetch_images);
    $stmt_fetch_images->bind_param("i", $product_id);
    $stmt_fetch_images->execute();
    $result = $stmt_fetch_images->get_result();
    
    if ($result->num_rows == 0) {
        // Nếu sản phẩm không tồn tại, chuyển hướng và thông báo
        header("Location: index.php?error=Sản phẩm không tồn tại.");
        exit();
    }
    $row = $result->fetch_assoc();
    $existing_feature_img = $row['Feature_img'];
    $existing_gallery = $row['Gallery'];
    $existing_product_name = $row['Product_name'];
    $existing_sku = $row['SKU'];
    $existing_price = $row['Price'];
    // Lấy dữ liệu từ form
    $product_name = $_POST['product_name'] ?? '';
    $sku = $_POST['sku'] ?? '';
    $price = $_POST['price'] ?? '';
    // So sánh các giá trị hiện tại với giá trị từ form
    $is_changed = false;
    if ($product_name !== $existing_product_name || $sku !== $existing_sku || $price !== $existing_price) {
        $is_changed = true;
    }

    // Xử lý upload hình ảnh nếu có thay đổi
    $feature_img_name = handleFileUpload('feature_img');
    if ($feature_img_name !== "" || $feature_img_name=$existing_feature_img) {
        $is_changed = true; // Nếu có hình ảnh mới được tải lên, đánh dấu là đã thay đổi
    }

    $gallery_images = handleGalleryUpload('Gallery');
    if (!empty($gallery_images)) {
        $is_changed = true; // Nếu có hình ảnh mới cho gallery, đánh dấu là đã thay đổi
    }

    // Nếu không có thay đổi nào, chuyển hướng mà không cập nhật
    if (!$is_changed) {
        header("Location: index.php?error=Không có thay đổi nào được thực hiện.");
        exit();
    }

    // Cập nhật sản phẩm
    $gallery_json = json_encode($gallery_images);
    $sql_update = "UPDATE product SET 
                   Product_name = COALESCE(NULLIF(?, ''), Product_name),
                   SKU = COALESCE(NULLIF(?, ''), SKU),
                   Price = COALESCE(NULLIF(?, ''), Price),
                   Feature_img = COALESCE(NULLIF(?, ''), Feature_img),
                   Gallery = COALESCE(NULLIF(?, ''), Gallery) 
                   WHERE ID = ?";

    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ssdsdi", $product_name, $sku, $price, $feature_img_name, $gallery_json, $product_id);

    if ($stmt_update->execute()) {
        header("location: index.php");
    } else {
        header("location: index.php"); 
    }exit(); 
}
// Đóng kết nối
$conn->close();
?>
