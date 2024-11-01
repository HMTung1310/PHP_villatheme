<?php
include("db.php");

$sortBy = isset($_POST['sortBy']) ? $_POST['sortBy'] : 'Date'; 
$sortOrder = isset($_POST['sortOrder']) ? $_POST['sortOrder'] : 'ASC'; 
$categories = isset($_POST['categories']) ? $_POST['categories'] : [];
$tags = isset($_POST['tags']) ? $_POST['tags'] : [];
$startDate = isset($_POST['startDate']) ? $_POST['startDate'] : '';
$endDate = isset($_POST['endDate']) ? $_POST['endDate'] : '';
$priceFrom = isset($_POST['priceFrom']) ? $_POST['priceFrom'] : '';
$priceInto = isset($_POST['priceInto']) ? $_POST['priceInto'] : '';

$sql = "SELECT p.*, 
GROUP_CONCAT(DISTINCT c.Category_name SEPARATOR ', ') AS category, 
GROUP_CONCAT(DISTINCT t.Tag_name SEPARATOR ', ') AS tag
FROM Product p
LEFT JOIN product_category pc ON p.id = pc.product_id
LEFT JOIN category c ON pc.category_id = c.id
LEFT JOIN product_tag pt ON p.id = pt.product_id
LEFT JOIN tag t ON pt.tag_id = t.id
WHERE 1=1";

// Lọc theo ngày
if (!empty($startDate) && !empty($endDate)) {
    $sql .= " AND p.Date BETWEEN '$startDate' AND '$endDate'";
}

// Lọc theo giá
if (!empty($priceFrom) && !empty($priceInto)) {
    $sql .= " AND p.Price BETWEEN $priceFrom AND $priceInto";
}elseif (empty($priceFrom) && $priceInto == 0) {
    $sql .= " AND p.Price = 0.00";
}
// Lọc theo danh mục
if (!empty($categories) && is_array($categories)) {
    $categories_in = implode(",", array_map('intval', $categories));
    $sql .= " AND p.id IN (
                 SELECT pc.product_id 
                 FROM product_category pc 
                 WHERE pc.category_id IN ($categories_in)
              )";
}

// Lọc theo thẻ
if (!empty($tags) && is_array($tags)) {
    $tags_in = implode(",", array_map('intval', $tags));
    $sql .= " AND p.id IN (
                 SELECT pt.product_id 
                 FROM product_tag pt 
                 WHERE pt.tag_id IN ($tags_in)
              )";
}
$sql .= " GROUP BY p.id ORDER BY ";
if ($sortBy === 'Name') {
    $sql .= "p.Product_name $sortOrder";
} elseif ($sortBy === 'Price') {
    $sql .= "p.Price $sortOrder";
} else {
    $sql .= "p.Date $sortOrder"; // Sắp xếp theo ngày nếu không có lựa chọn nào khác
}
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
                    if (is_numeric($row["Price"])) {
                        echo '$' . htmlspecialchars($row["Price"]);
                    } else {
                        echo htmlspecialchars($row["Price"]);
                    }
                }    
                echo '</td>
                        <td>';    
        $feature_img = htmlspecialchars($row["Feature_img"]);
        if (filter_var($feature_img, FILTER_VALIDATE_URL)) {
            echo '<div>
                    <img src="' . $feature_img . '" alt="Image" width="100" style="margin-top: 5px;">
                  </div>';
        } elseif (file_exists('upload/' . $feature_img)) {
            echo '<div>
                    <img src="upload/' . $feature_img . '" alt="Image" width="100" style="margin-top: 5px;">
                  </div>';
        } else {
            echo '<div>Không có hình ảnh.</div>';
        }
        echo '</td>
                <td>';
        echo'<div class="gallery-container">';
        
        $gallery_string = $row["Gallery"];
        $upload_directory = 'upload/';
        
        $gallery_images = json_decode($gallery_string, true);
        
        if ($gallery_images === null) {
            $gallery_images = explode(',', $gallery_string);
        }
        
        if (!empty($gallery_images) && is_array($gallery_images)) {
            foreach ($gallery_images as $gallery_image) {
                $gallery_image = trim($gallery_image); 
        
                $image_name = basename($gallery_image);
        
                $image_path = $upload_directory . $image_name;
        
                if (file_exists($image_path)) {
                    echo '<img src="' . htmlspecialchars($image_path) . '" alt="Gallery Image" width="100" style="margin:5px;">';
                } else {
                    if (filter_var($gallery_image, FILTER_VALIDATE_URL)) {
                        if (copy($gallery_image, $image_path)) {
                            echo 'Tải ảnh về: ' . htmlspecialchars($image_name) . '<br>';
                        } else {
                            echo 'Lỗi khi tải ảnh về: ' . htmlspecialchars($image_name) . '<br>';
                            continue;
                        }
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
