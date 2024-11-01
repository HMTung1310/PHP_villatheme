<?php
    include("db.php");

    $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

    if ($searchTerm != '') {
        $sql = "SELECT ID, Product_name FROM Product WHERE Product_name LIKE '%$searchTerm%' LIMIT 10";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<div class="item result-item" data-id="' . $row['ID'] . '">';
                echo $row['Product_name'];
                echo '</div>';
            }
        } else {
            echo '<div class="item">  </div>';
        }
    }
    $conn->close();
    ?>  