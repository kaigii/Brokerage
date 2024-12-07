<?php
include 'includes/connect.php';
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2>系統概況</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">營業員總數</h5>
                <?php
                $result = $db->query("SELECT COUNT(*) as count FROM brokers");
                $row = $result->fetch_assoc();
                echo "<h2 class='card-text'>" . $row['count'] . "</h2>";
                ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">客戶總數</h5>
                <?php
                $result = $db->query("SELECT COUNT(*) as count FROM clients");
                $row = $result->fetch_assoc();
                echo "<h2 class='card-text'>" . $row['count'] . "</h2>";
                ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">今日交易總數</h5>
                <?php
                $result = $db->query("SELECT COUNT(*) as count FROM transactions WHERE DATE(transaction_date) = CURDATE()");
                $row = $result->fetch_assoc();
                echo "<h2 class='card-text'>" . $row['count'] . "</h2>";
                ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">最近交易</h5>
                <table class="table">
                    <thead>
                        <tr>
                            <th>客戶</th>
                            <th>股票</th>
                            <th>類型</th>
                            <th>金額</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT t.*, c.name as client_name, s.stock_code 
                                FROM transactions t 
                                JOIN clients c ON t.client_id = c.client_id 
                                JOIN stocks s ON t.stock_id = s.stock_id 
                                ORDER BY t.transaction_date DESC LIMIT 5";
                        $result = $db->query($sql);
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row['client_name'] . "</td>";
                            echo "<td>" . $row['stock_code'] . "</td>";
                            echo "<td>" . ($row['type'] == 'buy' ? '買入' : '賣出') . "</td>";
                            echo "<td>" . number_format($row['price'] * $row['quantity']) . "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">熱門股票</h5>
                <table class="table">
                    <thead>
                        <tr>
                            <th>代碼</th>
                            <th>名稱</th>
                            <th>現價</th>
                            <th>交易次數</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT s.*, COUNT(t.transaction_id) as trade_count 
                                FROM stocks s 
                                LEFT JOIN transactions t ON s.stock_id = t.stock_id 
                                GROUP BY s.stock_id 
                                ORDER BY trade_count DESC LIMIT 5";
                        $result = $db->query($sql);
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row['stock_code'] . "</td>";
                            echo "<td>" . $row['stock_name'] . "</td>";
                            echo "<td>" . number_format($row['current_price'], 2) . "</td>";
                            echo "<td>" . $row['trade_count'] . "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
