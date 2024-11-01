<?php
include("db.php");
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'scrape') {
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // URL chính để bắt đầu lấy dữ liệu
        $main_url = 'https://villatheme.com/extensions/';
        $html = file_get_contents($main_url);

        if ($html === false) {
            echo "Không thể tải nội dung từ URL.";
            exit;
        }

        // Lấy tất cả các trang con để xác định số trang phân trang
        $pageUrls = [$main_url];
        $nextPage = $main_url; // Khởi tạo biến để theo dõi các trang tiếp theo

        // Lặp để lấy các trang phân trang
        do {
            // Lấy nội dung của trang hiện tại
            $html = file_get_contents($nextPage);
            if ($html === false) {
                echo "Không thể tải nội dung từ $nextPage<br>";
                break; // Thoát nếu không thể tải trang
            }

            // Tìm liên kết đến trang tiếp theo
            preg_match('/<a class="next page-numbers" href="([^"]+)"/', $html, $paginationMatch);
            $nextPage = !empty($paginationMatch) ? $paginationMatch[1] : null;

            if ($nextPage) {
                $pageUrls[] = $nextPage; // Thêm trang tiếp theo vào danh sách
            }
        } while ($nextPage); // Lặp cho đến khi không còn trang tiếp theo

        $scrapedCount = 0; // Khởi tạo biến đếm số lượng sản phẩm đã scrape

        // Lặp qua từng trang trong mảng
        foreach ($pageUrls as $url) {
            $html = file_get_contents($url);
            if ($html === false) {
                echo "Không thể tải nội dung từ $url<br>";
                continue;
            }

            // Lấy tất cả các liên kết sản phẩm
            preg_match_all('/<li class="product[^>]*>\s*<div class="col-sm-6">\s*<a href="([^"]+)"/s', $html, $links);
            $child_links = array_unique(array_map('trim', $links[1]));

            foreach ($child_links as $childUrl) {
                if ($scrapedCount >= 5) { // Kiểm tra nếu đã lấy đủ 5 sản phẩm
                    break; // Thoát khỏi vòng lặp sau khi lấy đủ 5 sản phẩm
                }

                // Tải nội dung từ trang con
                $child_html = file_get_contents($childUrl);
                if ($child_html === false) {
                    echo "Không thể tải nội dung từ $childUrl<br>";
                    continue;
                }

                // Lấy các thông tin từ trang con
                preg_match('/<h1 class="product_title entry-title">(.*?)<\/h1>/', $child_html, $titleMatch);
                $title = isset($titleMatch[1]) ? html_entity_decode(trim($titleMatch[1])) : 'N/A';

                // Lấy giá từ thẻ <span class="price">
                preg_match('/<p class="price">(.*?)<\/p>/', $child_html, $priceMatch);
                $currentPrice = ' $0 ';
                if (!empty($priceMatch[1])) {
                    preg_match('/<span class="woocommerce-Price-amount amount">.*?<bdi><span class="woocommerce-Price-currencySymbol">(.*?)<\/span>(.*?)<\/bdi>.*?<\/span>/', $priceMatch[1], $currentPriceMatch);
                    if (isset($currentPriceMatch[1]) && isset($currentPriceMatch[2])) {
                        
                        $currentPrice = html_entity_decode(trim($currentPriceMatch[1] . $currentPriceMatch[2]));
                        $currentPrice = str_replace('$',' ', $currentPrice); 
                    }
                }
                preg_match('/<span class="sku_wrapper">SKU:\s*<span class="sku">(.*?)<\/span><\/span>/', $child_html, $skuMatch);
                if (isset($skuMatch[1])) {
                    $sku = trim($skuMatch[1]);
                } else {
                    $sku = 'SKU' . $defaultSkuCounter;
                    $defaultSkuCounter++; // Tăng SKU counter
                }
               // Kiểm tra nếu SKU hoặc tên sản phẩm đã tồn tại
                $stmt = $conn->prepare("SELECT id FROM product WHERE SKU = :sku OR Product_name = :title");
                $stmt->bindParam(':sku', $sku);
                $stmt->bindParam(':title', $title);
                $stmt->execute();
                $stmt = $conn->prepare("SELECT id FROM product WHERE SKU = :sku OR Product_name = :title");
                $stmt->bindParam(':sku', $sku);
                
                $stmt->bindParam(':title', $title);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    // Bỏ qua sản phẩm nếu nó đã tồn tại
                    continue;
                }


                preg_match('/<span class="posted_in">.*?<a[^>]*>(.*?)<\/a>/', $child_html, $categoryMatch);
                $category = isset($categoryMatch[1]) ? trim($categoryMatch[1]) : 'N/A';

                preg_match('/<span class="tagged_as">.*?<\/span>/', $child_html, $tagsMatch);
                $tagsHTML = isset($tagsMatch[0]) ? $tagsMatch[0] : '';

                // Sử dụng DOMDocument để phân tích cú pháp HTML
                libxml_use_internal_errors(true); // Bỏ qua lỗi phân tích cú pháp
                $dom = new DOMDocument();
                $dom->loadHTML($tagsHTML);
                libxml_clear_errors();

                // Lấy tất cả các thẻ <a>
                $tagsArray = [];
                foreach ($dom->getElementsByTagName('a') as $link) {
                    $tagText = trim($link->textContent); // Lấy nội dung văn bản của thẻ <a>

                    // Kiểm tra xem tag đã tồn tại trong mảng hay chưa
                    if (!in_array($tagText, $tagsArray)) {
                        $tagsArray[] = $tagText; // Thêm tag vào mảng nếu chưa tồn tại
                    }
                }

                // Chuyển đổi thành chuỗi, ngăn cách bởi dấu phẩy
                $tagsString = implode(', ', $tagsArray);
                
                preg_match('/<a[^>]*>\s*<img[^>]+src="([^"]+)"[^>]*>/', $child_html, $featureImgMatch);
                $featureImg = isset($featureImgMatch[1]) ? trim($featureImgMatch[1]) : 'N/A';
                 // Lấy gallery
                 $pattern = '/<div[^>]+data-thumb="[^"]*"[^>]*>.*?<img[^>]+src="([^"]*)"[^>]*>/si';
                 preg_match_all($pattern, $child_html, $galleryMatches);
 
                 if (!empty($galleryMatches[1])) {
                     $galleryImages = array_unique($galleryMatches[1]);
                     // Lưu gallery dưới dạng chuỗi các URL phân cách bởi dấu phẩy
                     $gallery = implode(', ', $galleryImages);
                 } 
                $currentDate = date('Y-m-d');

                // Save product to the database with date
                $stmt = $conn->prepare("INSERT INTO product (Product_name, SKU, Price, Feature_img, Gallery, Date) VALUES (:title, :sku, :price, :feature_img, :Gallery, :currentDate)");
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':sku', $sku);
                $stmt->bindParam(':price', $currentPrice);
                $stmt->bindParam(':feature_img', $featureImg);
                $stmt->bindParam(':Gallery', $gallery);
                $stmt->bindParam(':currentDate', $currentDate); // Bind the current date
                $stmt->execute();
                $productId = $conn->lastInsertId(); // Lấy ID sản phẩm vừa thêm

                // Xử lý danh mục (category)
                $stmt = $conn->prepare("SELECT id FROM category WHERE Category_name = :Category_name");
                $stmt->bindParam(':Category_name', $category);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    $categoryId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
                } else {
                    $stmt = $conn->prepare("INSERT INTO category (Category_name) VALUES (:Category_name)");
                    $stmt->bindParam(':Category_name', $category);
                    $stmt->execute();
                    $categoryId = $conn->lastInsertId();
                }

                // Lưu mối quan hệ giữa sản phẩm và danh mục vào bảng product_category
                $stmt = $conn->prepare("INSERT INTO product_category (product_id, category_id) VALUES (:product_id, :category_id)");
                $stmt->bindParam(':product_id', $productId);
                $stmt->bindParam(':category_id', $categoryId);
                $stmt->execute();

                // Xử lý thẻ (tags)
                if (!empty($tagsString)) {
                    $tagsArray = explode(', ', $tagsString);
                    foreach ($tagsArray as $tag) {
                        $stmt = $conn->prepare("SELECT id FROM tag WHERE Tag_name = :Tag_name");
                        $stmt->bindParam(':Tag_name', $tag);
                        $stmt->execute();
                        if ($stmt->rowCount() > 0) {
                            $tagId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
                        } else {
                            $stmt = $conn->prepare("INSERT INTO tag (Tag_name) VALUES (:Tag_name)");
                            $stmt->bindParam(':Tag_name', $tag);
                            $stmt->execute();
                            $tagId = $conn->lastInsertId();
                        }

                        // Lưu mối quan hệ giữa sản phẩm và thẻ vào bảng product_tag
                        $stmt = $conn->prepare("INSERT INTO product_tag (product_id, tag_id) VALUES (:product_id, :tag_id)");
                        $stmt->bindParam(':product_id', $productId);
                        $stmt->bindParam(':tag_id', $tagId);
                        $stmt->execute();
                    }
                }

                $scrapedCount++; // Tăng biến đếm sau khi thành công lưu một sản phẩm
            }
        }
        // Sau khi tất cả dữ liệu đã được lưu
    } catch (PDOException $e) {
        echo "Lỗi: " . $e->getMessage();
    }
    exit;
}
?>
