<?php
include("db.php");

if (isset($_GET['id'])) {
    $product_id = (int) $_GET['id']; // Chuyển đổi giá trị ID sang kiểu số nguyên để tránh lỗi SQL injection
    $sql = "SELECT p.*, 
                   GROUP_CONCAT(DISTINCT c.Category_name SEPARATOR ', ') AS category, 
                   GROUP_CONCAT(DISTINCT t.Tag_name SEPARATOR ', ') AS tag
            FROM Product p
            LEFT JOIN product_category pc ON p.ID = pc.product_id
            LEFT JOIN category c ON pc.category_id = c.ID
            LEFT JOIN product_tag pt ON p.ID = pt.product_id
            LEFT JOIN tag t ON pt.tag_id = t.ID
            WHERE p.ID = ?
            GROUP BY p.ID";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

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
    
    $stmt->close();
} else {
    echo '<div class="error">ID sản phẩm không hợp lệ.</div>';
}

$conn->close();
