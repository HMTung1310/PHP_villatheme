<?php
include("db.php"); // Kết nối đến cơ sở dữ liệu

$sku = $_POST['sku'] ?? '';
$product_id = $_POST['product_id'] ?? '';

// Kiểm tra SKU có tồn tại không, ngoại trừ sản phẩm hiện tại
$sql = "SELECT COUNT(*) FROM product WHERE SKU = ? AND ID != ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $sku, $product_id);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();

if ($count > 0) {
    echo 'exists'; // Trả về 'exists' nếu SKU đã tồn tại
} else {
    echo 'not_exists'; // Trả về 'not_exists' nếu SKU chưa tồn tại
}

$stmt->close();
$conn->close();
?>
