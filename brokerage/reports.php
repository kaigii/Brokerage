<?php
include 'includes/connect.php';
include 'includes/header.php';

// 設定報告時間範圍
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-1 month'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>績效報告</h2>
        </div>
    </div>

    <!-- 時間範圍選擇 -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" id="dateRangeForm" class="row">
                <div class="col-md-4">
                    <label class="form-label">開始日期</label>
                    <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">結束日期</label>
                    <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block">更新報告</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 營業員績效摘要 -->
    <div class="card mb-4">
        <div class="card-header">
            <h4>營業員績效統計</h4>
        </div>
        <div class="card-body">
            <table class="table table-striped" id="brokerPerformanceTable">
                <thead>
                    <tr>
                        <th>營業員</th>
                        <th>客戶數</th>
                        <th>交易筆數</th>
                        <th>交易總額</th>
                        <th>平均每筆金額</th>
                        <th>客戶總投資組合市值</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT 
                            b.broker_id,
                            b.name AS broker_name,
                            COUNT(DISTINCT c.client_id) AS client_count,
                            COUNT(DISTINCT t.transaction_id) AS transaction_count,
                            SUM(t.quantity * t.price) AS total_transaction_amount,
                            SUM(p.market_value) AS total_portfolio_value
                           FROM brokers b
                           LEFT JOIN clients c ON b.broker_id = c.broker_id
                           LEFT JOIN transactions t ON c.client_id = t.client_id 
                               AND t.transaction_date BETWEEN '$start_date' AND '$end_date'
                           LEFT JOIN portfolios p ON c.client_id = p.client_id
                           GROUP BY b.broker_id
                           ORDER BY total_transaction_amount DESC";
                    
                    $result = $db->query($sql);
                    while ($row = $result->fetch_assoc()) {
                        $avg_transaction = $row['transaction_count'] > 0 ? 
                            $row['total_transaction_amount'] / $row['transaction_count'] : 0;
                        
                        echo "<tr>";
                        echo "<td>" . $row['broker_name'] . "</td>";
                        echo "<td>" . $row['client_count'] . "</td>";
                        echo "<td>" . $row['transaction_count'] . "</td>";
                        echo "<td>" . number_format($row['total_transaction_amount'], 2) . "</td>";
                        echo "<td>" . number_format($avg_transaction, 2) . "</td>";
                        echo "<td>" . number_format($row['total_portfolio_value'], 2) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 熱門股票統計 -->
    <div class="card mb-4">
        <div class="card-header">
            <h4>熱門股票統計</h4>
        </div>
        <div class="card-body">
            <table class="table table-striped" id="popularStocksTable">
                <thead>
                    <tr>
                        <th>股票代碼</th>
                        <th>股票名稱</th>
                        <th>交易次數</th>
                        <th>交易總額</th>
                        <th>買入次數</th>
                        <th>賣出次數</th>
                        <th>持有人數</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT 
                            s.stock_code,
                            s.stock_name,
                            COUNT(t.transaction_id) AS total_transactions,
                            SUM(t.quantity * t.price) AS total_amount,
                            SUM(CASE WHEN t.type = 'buy' THEN 1 ELSE 0 END) AS buy_count,
                            SUM(CASE WHEN t.type = 'sell' THEN 1 ELSE 0 END) AS sell_count,
                            COUNT(DISTINCT p.client_id) AS holder_count
                           FROM stocks s
                           LEFT JOIN transactions t ON s.stock_id = t.stock_id 
                               AND t.transaction_date BETWEEN '$start_date' AND '$end_date'
                           LEFT JOIN portfolios p ON s.stock_id = p.stock_id AND p.total_shares > 0
                           GROUP BY s.stock_id
                           HAVING total_transactions > 0
                           ORDER BY total_transactions DESC";
                    
                    $result = $db->query($sql);
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['stock_code'] . "</td>";
                        echo "<td>" . $row['stock_name'] . "</td>";
                        echo "<td>" . $row['total_transactions'] . "</td>";
                        echo "<td>" . number_format($row['total_amount'], 2) . "</td>";
                        echo "<td>" . $row['buy_count'] . "</td>";
                        echo "<td>" . $row['sell_count'] . "</td>";
                        echo "<td>" . $row['holder_count'] . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 客戶績效排行 -->
    <div class="card">
        <div class="card-header">
            <h4>客戶績效排行</h4>
        </div>
        <div class="card-body">
            <table class="table table-striped" id="clientPerformanceTable">
                <thead>
                    <tr>
                        <th>客戶姓名</th>
                        <th>負責營業員</th>
                        <th>總投資金額</th>
                        <th>目前市值</th>
                        <th>報酬金額</th>
                        <th>報酬率</th>
                        <th>持股數量</th>
                        <th>交易次數</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT 
                            c.name AS client_name,
                            b.name AS broker_name,
                            SUM(p.total_shares * p.average_cost) AS total_investment,
                            SUM(p.market_value) AS total_market_value,
                            COUNT(DISTINCT p.stock_id) AS stock_count,
                            COUNT(t.transaction_id) AS transaction_count
                           FROM clients c
                           LEFT JOIN brokers b ON c.broker_id = b.broker_id
                           LEFT JOIN portfolios p ON c.client_id = p.client_id
                           LEFT JOIN transactions t ON c.client_id = t.client_id 
                               AND t.transaction_date BETWEEN '$start_date' AND '$end_date'
                           GROUP BY c.client_id
                           HAVING total_investment > 0
                           ORDER BY (total_market_value - total_investment) DESC";
                    
                    $result = $db->query($sql);
                    while ($row = $result->fetch_assoc()) {
                        $profit = $row['total_market_value'] - $row['total_investment'];
                        $return_rate = ($row['total_market_value'] / $row['total_investment'] - 1) * 100;
                        
                        echo "<tr>";
                        echo "<td>" . $row['client_name'] . "</td>";
                        echo "<td>" . $row['broker_name'] . "</td>";
                        echo "<td>" . number_format($row['total_investment'], 2) . "</td>";
                        echo "<td>" . number_format($row['total_market_value'], 2) . "</td>";
                        echo "<td class='" . ($profit >= 0 ? 'text-success' : 'text-danger') . "'>" 
                            . number_format($profit, 2) . "</td>";
                        echo "<td class='" . ($return_rate >= 0 ? 'text-success' : 'text-danger') . "'>" 
                            . number_format($return_rate, 2) . "%</td>";
                        echo "<td>" . $row['stock_count'] . "</td>";
                        echo "<td>" . $row['transaction_count'] . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 初始化所有DataTables
    $('#brokerPerformanceTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Chinese-traditional.json"
        }
    });
    
    $('#popularStocksTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Chinese-traditional.json"
        },
        "order": [[2, "desc"]]
    });
    
    $('#clientPerformanceTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Chinese-traditional.json"
        },
        "order": [[4, "desc"]]
    });
});
</script>

<?php include 'includes/footer.php'; ?>
