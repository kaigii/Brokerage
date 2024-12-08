<?php
include 'includes/connect.php';
include 'includes/header.php';

// 設定報告時間範圍
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-1 month'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// 更新所有投資組合的市值
$update_sql = "UPDATE portfolios p 
               JOIN stocks s ON p.stock_id = s.stock_id 
               SET p.market_value = p.total_shares * s.current_price";
$db->query($update_sql);
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
                        <th class="text-end">客戶數</th>
                        <th class="text-end">交易筆數</th>
                        <th class="text-end">交易總額</th>
                        <th class="text-end">平均每筆金額</th>
                        <th class="text-end">客戶總投資組合市值</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT 
                            b.broker_id,
                            b.name AS broker_name,
                            COUNT(DISTINCT c.client_id) AS client_count,
                            COUNT(DISTINCT t.transaction_id) AS transaction_count,
                            COALESCE(SUM(t.quantity * t.price), 0) AS total_transaction_amount,
                            COALESCE(SUM(p.market_value), 0) AS total_portfolio_value
                           FROM brokers b
                           LEFT JOIN clients c ON b.broker_id = c.broker_id
                           LEFT JOIN transactions t ON c.client_id = t.client_id 
                               AND t.transaction_date BETWEEN ? AND ?
                           LEFT JOIN portfolios p ON c.client_id = p.client_id
                           GROUP BY b.broker_id
                           ORDER BY total_transaction_amount DESC";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->bind_param("ss", $start_date, $end_date);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($row = $result->fetch_assoc()) {
                        $avg_transaction = $row['transaction_count'] > 0 ? 
                            $row['total_transaction_amount'] / $row['transaction_count'] : 0;
                        
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['broker_name']) . "</td>";
                        echo "<td class='text-end'>" . number_format($row['client_count']) . "</td>";
                        echo "<td class='text-end'>" . number_format($row['transaction_count']) . "</td>";
                        echo "<td class='text-end'>" . number_format($row['total_transaction_amount'], 2) . "</td>";
                        echo "<td class='text-end'>" . number_format($avg_transaction, 2) . "</td>";
                        echo "<td class='text-end'>" . number_format($row['total_portfolio_value'], 2) . "</td>";
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
                        <th class="text-end">交易次數</th>
                        <th class="text-end">交易總額</th>
                        <th class="text-end">買入次數</th>
                        <th class="text-end">賣出次數</th>
                        <th class="text-end">持有人數</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "WITH trading_stats AS (
                        SELECT 
                            s.stock_id,
                            s.stock_code,
                            s.stock_name,
                            COUNT(t.transaction_id) AS total_transactions,
                            COALESCE(SUM(t.quantity * t.price), 0) AS total_amount,
                            SUM(CASE WHEN t.type = 'buy' THEN 1 ELSE 0 END) AS buy_count,
                            SUM(CASE WHEN t.type = 'sell' THEN 1 ELSE 0 END) AS sell_count
                        FROM stocks s
                        LEFT JOIN transactions t ON s.stock_id = t.stock_id 
                            AND t.transaction_date BETWEEN ? AND ?
                        GROUP BY s.stock_id
                    )
                    SELECT 
                        ts.*,
                        COUNT(DISTINCT p.client_id) AS holder_count
                    FROM trading_stats ts
                    LEFT JOIN portfolios p ON ts.stock_id = p.stock_id 
                        AND p.total_shares > 0
                    GROUP BY ts.stock_id
                    HAVING total_transactions > 0
                    ORDER BY total_transactions DESC";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->bind_param("ss", $start_date, $end_date);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['stock_code']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['stock_name']) . "</td>";
                        echo "<td class='text-end'>" . number_format($row['total_transactions']) . "</td>";
                        echo "<td class='text-end'>" . number_format($row['total_amount'], 2) . "</td>";
                        echo "<td class='text-end'>" . number_format($row['buy_count']) . "</td>";
                        echo "<td class='text-end'>" . number_format($row['sell_count']) . "</td>";
                        echo "<td class='text-end'>" . number_format($row['holder_count']) . "</td>";
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
                        <th>排名</th>
                        <th>客戶姓名</th>
                        <th>負責營業員</th>
                        <th class="text-end">總投資金額</th>
                        <th class="text-end">目前市值</th>
                        <th class="text-end">報酬金額</th>
                        <th class="text-end">報酬率</th>
                        <th class="text-end">持股數量</th>
                        <th class="text-end">交易次數</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "WITH client_investments AS (
                        SELECT 
                            c.client_id,
                            c.name AS client_name,
                            b.name AS broker_name,
                            SUM(p.total_shares * p.average_cost) AS total_investment,
                            SUM(p.market_value) AS total_market_value,
                            SUM(p.total_shares) AS total_shares,
                            COUNT(DISTINCT t.transaction_id) AS transaction_count
                        FROM 
                            clients c
                            LEFT JOIN brokers b ON c.broker_id = b.broker_id
                            LEFT JOIN portfolios p ON c.client_id = p.client_id
                            LEFT JOIN transactions t ON c.client_id = t.client_id 
                                AND t.transaction_date BETWEEN ? AND ?
                        GROUP BY 
                            c.client_id, c.name, b.name
                        HAVING 
                            total_investment > 0
                    )
                    SELECT 
                        *,
                        (total_market_value - total_investment) AS profit,
                        CASE 
                            WHEN total_investment > 0 
                            THEN ((total_market_value - total_investment) / total_investment * 100)
                            ELSE 0 
                        END AS return_rate,
                        RANK() OVER (ORDER BY ((total_market_value - total_investment) / total_investment * 100) DESC) AS rank
                    FROM 
                        client_investments
                    ORDER BY 
                        return_rate DESC";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->bind_param("ss", $start_date, $end_date);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['rank'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['client_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['broker_name']) . "</td>";
                        echo "<td class='text-end'>" . number_format($row['total_investment'], 2) . "</td>";
                        echo "<td class='text-end'>" . number_format($row['total_market_value'], 2) . "</td>";
                        echo "<td class='text-end " . ($row['profit'] >= 0 ? 'text-success' : 'text-danger') . "'>" 
                            . number_format($row['profit'], 2) . "</td>";
                        echo "<td class='text-end " . ($row['return_rate'] >= 0 ? 'text-success' : 'text-danger') . "'>" 
                            . number_format($row['return_rate'], 2) . "%</td>";
                        echo "<td class='text-end'>" . number_format($row['total_shares']) . "</td>";
                        echo "<td class='text-end'>" . number_format($row['transaction_count']) . "</td>";
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
    $('#brokerPerformanceTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Chinese-traditional.json"
        },
        "order": [[3, "desc"]]
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
        "order": [[6, "desc"]]
    });
});
</script>

<?php include 'includes/footer.php'; ?>