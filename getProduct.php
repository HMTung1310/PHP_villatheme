<?php
include("db.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json'); // Đảm bảo nội dung trả về là JSON

// Lấy ID sản phẩm từ tham số GET
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id > 0) {
    // Câu truy vấn SQL để lấy thông tin sản phẩm
    $sql = "SELECT p.*, 
                   GROUP_CONCAT(DISTINCT c.ID) AS category_ids, 
                   GROUP_CONCAT(DISTINCT t.ID) AS tag_ids,
                   GROUP_CONCAT(DISTINCT c.Category_name SEPARATOR ', ') AS categories, 
                   GROUP_CONCAT(DISTINCT t.Tag_name SEPARATOR ', ') AS tags
            FROM Product p
            LEFT JOIN product_category pc ON p.ID = pc.product_id
            LEFT JOIN category c ON pc.category_id = c.ID
            LEFT JOIN product_tag pt ON p.ID = pt.product_id
            LEFT JOIN tag t ON pt.tag_id = t.ID
            WHERE p.ID = ?
            GROUP BY p.ID";

    $stmt = $conn->prepare($sql);
    
    // Kiểm tra lỗi khi chuẩn bị câu lệnh SQL
    if ($stmt === false) {
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("i", $product_id);
    if (!$stmt->execute()) {
        echo json_encode(['error' => 'Query execution failed: ' . $stmt->error]);
        exit;
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        // Thêm trường Feature_img và Gallery vào kết quả
        $product['Feature_img'] = htmlspecialchars($product['Feature_img']);
        $product['Gallery'] = htmlspecialchars($product['Gallery']);
        
        // Trả về JSON bao gồm tất cả thông tin sản phẩm
        echo json_encode($product);
    } else {
        echo json_encode(['error' => 'Product not found']);
    }
} else {
    echo json_encode(['error' => 'Invalid product ID']);
}
$conn->close();
?>
