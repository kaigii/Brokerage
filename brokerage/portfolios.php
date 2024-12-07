<?php
include 'includes/connect.php';
include 'includes/header.php';

// 更新所有投資組合的市值
if (isset($_POST['action']) && $_POST['action'] == 'update_all') {
    $sql = "UPDATE portfolios p 
            JOIN stocks s ON p.stock_id = s.stock_id 
            SET p.market_value = p.total_shares * s.current_price";
    if ($db->query($sql)) {
        echo "<div class='alert alert-success'>所有投資組合市值已更新！</div>";
    } else {
        echo "<div class='alert alert-danger'>更新失敗：" . $db->error . "</div>";
    }
}

// 獲取選定的客戶ID
$selected_client = isset($_GET['client_id']) ? $_GET['client_id'] : null;
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>投資組合管理</h2>
        </div>
        <div class="col-md-4 text-end">
            <form method="POST" class="d-inline">
                <input type="hidden" name="action" value="update_all">
                <button type="submit" class="btn btn-success">更新所有市值</button>
            </form>
        </div>
    </div>

    <!-- 客戶選擇 -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" id="clientSelectForm">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">選擇客戶</label>
                        <select class="form-control" name="client_id" onchange="this.form.submit()">
                            <option value="">-- 選擇客戶 --</option>
                            <?php
                            $clients = $db->query("SELECT client_id, name FROM clients ORDER BY name");
                            while ($client = $clients->fetch_assoc()) {
                                $selected = ($selected_client == $client['client_id']) ? 'selected' : '';
                                echo "<option value='" . $client['client_id'] . "' $selected>" 
                                    . $client['name'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($selected_client): ?>
        <?php
        // 獲取客戶資訊
        $client_info = $db->query("SELECT * FROM clients WHERE client_id = '$selected_client'")->fetch_assoc();
        $broker_info = $db->query("SELECT name FROM brokers WHERE broker_id = '{$client_info['broker_id']}'")->fetch_assoc();
        ?>
        
        <!-- 客戶資訊摘要 -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <h5>客戶姓名</h5>
                        <p><?php echo $client_info['name']; ?></p>
                    </div>
                    <div class="col-md-3">
                        <h5>風險等級</h5>
                        <p><?php echo $client_info['risk_level']; ?></p>
                    </div>
                    <div class="col-md-3">
                        <h5>負責營業員</h5>
                        <p><?php echo $broker_info['name']; ?></p>
                    </div>
                    <div class="col-md-3">
                        <h5>註冊日期</h5>
                        <p><?php echo $client_info['registration_date']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 投資組合明細 -->
        <div class="card mb-4">
            <div class="card-header">
                <h4>投資組合明細</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped" id="portfolioTable">
                    <thead>
                        <tr>
                            <th>股票代碼</th>
                            <th>股票名稱</th>
                            <th>持股數量</th>
                            <th>平均成本</th>
                            <th>現價</th>
                            <th>市值</th>
                            <th>損益金額</th>
                            <th>報酬率</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT p.*, s.stock_code, s.stock_name, s.current_price
                                FROM portfolios p
                                JOIN stocks s ON p.stock_id = s.stock_id
                                WHERE p.client_id = '$selected_client'
                                AND p.total_shares > 0";
                        $result = $db->query($sql);
                        $total_investment = 0;
                        $total_market_value = 0;
                        
                        while ($row = $result->fetch_assoc()) {
                            $market_value = $row['total_shares'] * $row['current_price'];
                            $cost_basis = $row['total_shares'] * $row['average_cost'];
                            $profit_loss = $market_value - $cost_basis;
                            $return_rate = ($market_value / $cost_basis - 1) * 100;
                            
                            $total_investment += $cost_basis;
                            $total_market_value += $market_value;
                            
                            echo "<tr>";
                            echo "<td>" . $row['stock_code'] . "</td>";
                            echo "<td>" . $row['stock_name'] . "</td>";
                            echo "<td>" . number_format($row['total_shares']) . "</td>";
                            echo "<td>" . number_format($row['average_cost'], 2) . "</td>";
                            echo "<td>" . number_format($row['current_price'], 2) . "</td>";
                            echo "<td>" . number_format($market_value, 2) . "</td>";
                            echo "<td class='" . ($profit_loss >= 0 ? 'text-success' : 'text-danger') . "'>" 
                                . number_format($profit_loss, 2) . "</td>";
                            echo "<td class='" . ($return_rate >= 0 ? 'text-success' : 'text-danger') . "'>" 
                                . number_format($return_rate, 2) . "%</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary fw-bold">
                            <td colspan="5">總計</td>
                            <td><?php echo number_format($total_market_value, 2); ?></td>
                            <td class="<?php echo ($total_market_value - $total_investment >= 0 ? 'text-success' : 'text-danger'); ?>">
                                <?php echo number_format($total_market_value - $total_investment, 2); ?>
                            </td>
                            <td class="<?php echo ($total_market_value/$total_investment - 1 >= 0 ? 'text-success' : 'text-danger'); ?>">
                                <?php echo number_format(($total_market_value/$total_investment - 1) * 100, 2); ?>%
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- 近期交易紀錄 -->
        <div class="card">
            <div class="card-header">
                <h4>近期交易紀錄</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped" id="recentTransactionsTable">
                    <thead>
                        <tr>
                            <th>交易日期</th>
                            <th>股票代碼</th>
                            <th>交易類型</th>
                            <th>數量</th>
                            <th>價格</th>
                            <th>總金額</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT t.*, s.stock_code
                                FROM transactions t
                                JOIN stocks s ON t.stock_id = s.stock_id
                                WHERE t.client_id = '$selected_client'
                                ORDER BY t.transaction_date DESC
                                LIMIT 10";
                        $result = $db->query($sql);
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row['transaction_date'] . "</td>";
                            echo "<td>" . $row['stock_code'] . "</td>";
                            echo "<td>" . ($row['type'] == 'buy' ? '買入' : '賣出') . "</td>";
                            echo "<td>" . number_format($row['quantity']) . "</td>";
                            echo "<td>" . number_format($row['price'], 2) . "</td>";
                            echo "<td>" . number_format($row['quantity'] * $row['price'], 2) . "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    $('#portfolioTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Chinese-traditional.json"
        },
        "order": [[6, "desc"]]
    });
    
    $('#recentTransactionsTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Chinese-traditional.json"
        },
        "order": [[0, "desc"]]
    });
});
</script>

<?php include 'includes/footer.php'; ?>
