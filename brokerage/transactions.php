<?php
include 'includes/connect.php';
include 'includes/header.php';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        // 新增交易
        if ($_POST['action'] == 'add') {
            $client_id = $db->real_escape_string($_POST['client_id']);
            $stock_id = $db->real_escape_string($_POST['stock_id']);
            $type = $db->real_escape_string($_POST['type']);
            $quantity = $db->real_escape_string($_POST['quantity']);
            $price = $db->real_escape_string($_POST['price']);
            $settlement_date = $db->real_escape_string($_POST['settlement_date']);
            
            // 開始交易
            $db->begin_transaction();
            
            try {
                // 插入交易記錄
                $sql = "INSERT INTO transactions (client_id, stock_id, type, quantity, price, settlement_date) 
                        VALUES ('$client_id', '$stock_id', '$type', '$quantity', '$price', '$settlement_date')";
                $db->query($sql);
                
                // 更新投資組合
                $market_value = $quantity * $price;
                $sql = "INSERT INTO portfolios (client_id, stock_id, total_shares, average_cost, market_value) 
                        VALUES ('$client_id', '$stock_id', " . 
                        ($type == 'buy' ? $quantity : -$quantity) . ", '$price', '$market_value')
                        ON DUPLICATE KEY UPDATE 
                        total_shares = total_shares " . ($type == 'buy' ? '+' : '-') . " $quantity,
                        market_value = total_shares * '$price'";
                $db->query($sql);
                
                $db->commit();
                echo "<div class='alert alert-success'>新增交易成功！</div>";
            } catch (Exception $e) {
                $db->rollback();
                echo "<div class='alert alert-danger'>交易失敗：" . $e->getMessage() . "</div>";
            }
        }
        
        // 刪除交易
        if ($_POST['action'] == 'delete') {
            $transaction_id = $db->real_escape_string($_POST['transaction_id']);
            
            $db->begin_transaction();
            
            try {
                // 先取得交易資訊
                $sql = "SELECT * FROM transactions WHERE transaction_id = '$transaction_id'";
                $result = $db->query($sql);
                $transaction = $result->fetch_assoc();
                
                // 更新投資組合
                $quantity = $transaction['quantity'];
                $sql = "UPDATE portfolios SET 
                        total_shares = total_shares " . 
                        ($transaction['type'] == 'buy' ? '-' : '+') . " $quantity
                        WHERE client_id = '{$transaction['client_id']}' 
                        AND stock_id = '{$transaction['stock_id']}'";
                $db->query($sql);
                
                // 刪除交易記錄
                $sql = "DELETE FROM transactions WHERE transaction_id = '$transaction_id'";
                $db->query($sql);
                
                $db->commit();
                echo "<div class='alert alert-success'>刪除交易成功！</div>";
            } catch (Exception $e) {
                $db->rollback();
                echo "<div class='alert alert-danger'>刪除失敗：" . $e->getMessage() . "</div>";
            }
        }
    }
}
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <h2>交易紀錄管理</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                新增交易
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped" id="transactionsTable">
                <thead>
                    <tr>
                        <th>交易ID</th>
                        <th>客戶姓名</th>
                        <th>股票代碼</th>
                        <th>交易類型</th>
                        <th>數量</th>
                        <th>價格</th>
                        <th>總金額</th>
                        <th>交易日期</th>
                        <th>結算日期</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $db->query("SELECT t.*, c.name as client_name, s.stock_code 
                                        FROM transactions t 
                                        JOIN clients c ON t.client_id = c.client_id 
                                        JOIN stocks s ON t.stock_id = s.stock_id 
                                        ORDER BY t.transaction_date DESC");
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['transaction_id'] . "</td>";
                        echo "<td>" . $row['client_name'] . "</td>";
                        echo "<td>" . $row['stock_code'] . "</td>";
                        echo "<td>" . ($row['type'] == 'buy' ? '買入' : '賣出') . "</td>";
                        echo "<td>" . number_format($row['quantity']) . "</td>";
                        echo "<td>" . number_format($row['price'], 2) . "</td>";
                        echo "<td>" . number_format($row['quantity'] * $row['price'], 2) . "</td>";
                        echo "<td>" . $row['transaction_date'] . "</td>";
                        echo "<td>" . $row['settlement_date'] . "</td>";
                        echo "<td>
                                <button class='btn btn-sm btn-danger' onclick='deleteTransaction(" . $row['transaction_id'] . ")'>刪除</button>
                              </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 新增交易 Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新增交易</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">客戶</label>
                        <select class="form-control" name="client_id" required>
                            <?php
                            $clients = $db->query("SELECT client_id, name FROM clients");
                            while ($client = $clients->fetch_assoc()) {
                                echo "<option value='" . $client['client_id'] . "'>" . $client['name'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">股票</label>
                        <select class="form-control" name="stock_id" required>
                            <?php
                            $stocks = $db->query("SELECT stock_id, stock_code, stock_name, current_price FROM stocks");
                            while ($stock = $stocks->fetch_assoc()) {
                                echo "<option value='" . $stock['stock_id'] . "' data-price='" . $stock['current_price'] . "'>" 
                                    . $stock['stock_code'] . " - " . $stock['stock_name'] 
                                    . " (現價: " . number_format($stock['current_price'], 2) . ")" 
                                    . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">交易類型</label>
                        <select class="form-control" name="type" required>
                            <option value="buy">買入</option>
                            <option value="sell">賣出</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">數量</label>
                        <input type="number" class="form-control" name="quantity" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">價格</label>
                        <input type="number" class="form-control" name="price" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">結算日期</label>
                        <input type="date" class="form-control" name="settlement_date" required>
                    </div>
                    <button type="submit" class="btn btn-primary">儲存</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 刪除確認 Modal -->
<div class="modal fade" id="deleteTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">確認刪除</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>確定要刪除這筆交易紀錄嗎？這將同時更新客戶的投資組合。</p>
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="transaction_id" id="delete_transaction_id">
                    <button type="submit" class="btn btn-danger">確定刪除</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#transactionsTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Chinese-traditional.json"
        },
        "order": [[7, "desc"]]
    });

    // 自動填充股票現價
    $('select[name="stock_id"]').change(function() {
        var currentPrice = $(this).find(':selected').data('price');
        $('input[name="price"]').val(currentPrice);
    });

    // 設置預設結算日期為三天後
    var today = new Date();
    today.setDate(today.getDate() + 3);
    var defaultSettlementDate = today.toISOString().split('T')[0];
    $('input[name="settlement_date"]').val(defaultSettlementDate);
});

function deleteTransaction(transactionId) {
    $('#delete_transaction_id').val(transactionId);
    $('#deleteTransactionModal').modal('show');
}
</script>

<?php include 'includes/footer.php'; ?>
