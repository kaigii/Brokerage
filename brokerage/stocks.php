<?php
include 'includes/connect.php';
include 'includes/header.php';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        // 新增股票
        if ($_POST['action'] == 'add') {
            $stock_code = $db->real_escape_string($_POST['stock_code']);
            $stock_name = $db->real_escape_string($_POST['stock_name']);
            $industry_type = $db->real_escape_string($_POST['industry_type']);
            $current_price = $db->real_escape_string($_POST['current_price']);
            
            $sql = "INSERT INTO stocks (stock_code, stock_name, industry_type, current_price) 
                    VALUES ('$stock_code', '$stock_name', '$industry_type', '$current_price')";
            if ($db->query($sql)) {
                echo "<div class='alert alert-success'>新增股票成功！</div>";
            } else {
                echo "<div class='alert alert-danger'>新增失敗：" . $db->error . "</div>";
            }
        }
        
        // 更新股票價格
        if ($_POST['action'] == 'update_price') {
            $stock_id = $db->real_escape_string($_POST['stock_id']);
            $current_price = $db->real_escape_string($_POST['current_price']);
            
            $db->begin_transaction();
            try {
                // 更新股票價格
                $sql = "UPDATE stocks SET current_price = '$current_price', 
                        last_update_time = CURRENT_TIMESTAMP 
                        WHERE stock_id = '$stock_id'";
                $db->query($sql);
                
                // 同時更新相關投資組合的市值
                $sql = "UPDATE portfolios 
                        SET market_value = total_shares * '$current_price'
                        WHERE stock_id = '$stock_id'";
                $db->query($sql);
                
                $db->commit();
                echo "<div class='alert alert-success'>更新價格成功！</div>";
            } catch (Exception $e) {
                $db->rollback();
                echo "<div class='alert alert-danger'>更新失敗：" . $e->error . "</div>";
            }
        }
        
        // 編輯股票資訊
        if ($_POST['action'] == 'edit') {
            $stock_id = $db->real_escape_string($_POST['stock_id']);
            $stock_name = $db->real_escape_string($_POST['stock_name']);
            $industry_type = $db->real_escape_string($_POST['industry_type']);
            
            $sql = "UPDATE stocks SET 
                    stock_name = '$stock_name', 
                    industry_type = '$industry_type' 
                    WHERE stock_id = '$stock_id'";
            if ($db->query($sql)) {
                echo "<div class='alert alert-success'>更新股票資訊成功！</div>";
            } else {
                echo "<div class='alert alert-danger'>更新失敗：" . $db->error . "</div>";
            }
        }
        
        // 刪除股票
        if ($_POST['action'] == 'delete') {
            $stock_id = $db->real_escape_string($_POST['stock_id']);
            
            // 檢查是否有關聯的交易紀錄
            $check = $db->query("SELECT COUNT(*) as count FROM transactions WHERE stock_id = '$stock_id'");
            $row = $check->fetch_assoc();
            if ($row['count'] > 0) {
                echo "<div class='alert alert-danger'>無法刪除：此股票有交易紀錄！</div>";
            } else {
                $sql = "DELETE FROM stocks WHERE stock_id = '$stock_id'";
                if ($db->query($sql)) {
                    echo "<div class='alert alert-success'>刪除股票成功！</div>";
                } else {
                    echo "<div class='alert alert-danger'>刪除失敗：" . $db->error . "</div>";
                }
            }
        }
    }
}
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <h2>股票管理</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStockModal">
                新增股票
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped" id="stocksTable">
                <thead>
                    <tr>
                        <th>代碼</th>
                        <th>名稱</th>
                        <th>產業類型</th>
                        <th>現價</th>
                        <th>最後更新時間</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $db->query("SELECT * FROM stocks ORDER BY stock_code");
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['stock_code'] . "</td>";
                        echo "<td>" . $row['stock_name'] . "</td>";
                        echo "<td>" . $row['industry_type'] . "</td>";
                        echo "<td>" . number_format($row['current_price'], 2) . "</td>";
                        echo "<td>" . $row['last_update_time'] . "</td>";
                        echo "<td>
                                <button class='btn btn-sm btn-success me-1' onclick='updatePrice(" . json_encode($row) . ")'>更新價格</button>
                                <button class='btn btn-sm btn-primary me-1' onclick='editStock(" . json_encode($row) . ")'>編輯</button>
                                <button class='btn btn-sm btn-danger' onclick='deleteStock(" . $row['stock_id'] . ")'>刪除</button>
                              </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 新增股票 Modal -->
<div class="modal fade" id="addStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新增股票</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">股票代碼</label>
                        <input type="text" class="form-control" name="stock_code" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">股票名稱</label>
                        <input type="text" class="form-control" name="stock_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">產業類型</label>
                        <select class="form-control" name="industry_type" required>
                            <option value="半導體">半導體</option>
                            <option value="電子">電子</option>
                            <option value="金融">金融</option>
                            <option value="傳產">傳產</option>
                            <option value="航運">航運</option>
                            <option value="其他">其他</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">現價</label>
                        <input type="number" class="form-control" name="current_price" step="0.01" required>
                    </div>
                    <button type="submit" class="btn btn-primary">儲存</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 更新價格 Modal -->
<div class="modal fade" id="updatePriceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">更新股價</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_price">
                    <input type="hidden" name="stock_id" id="update_stock_id">
                    <div class="mb-3">
                        <label class="form-label">股票代碼</label>
                        <input type="text" class="form-control" id="update_stock_code" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">股票名稱</label>
                        <input type="text" class="form-control" id="update_stock_name" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">現價</label>
                        <input type="number" class="form-control" name="current_price" step="0.01" required>
                    </div>
                    <button type="submit" class="btn btn-success">更新價格</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 編輯股票 Modal -->
<div class="modal fade" id="editStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">編輯股票資訊</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="stock_id" id="edit_stock_id">
                    <div class="mb-3">
                        <label class="form-label">股票代碼</label>
                        <input type="text" class="form-control" id="edit_stock_code" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">股票名稱</label>
                        <input type="text" class="form-control" name="stock_name" id="edit_stock_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">產業類型</label>
                        <select class="form-control" name="industry_type" id="edit_industry_type" required>
                            <option value="半導體">半導體</option>
                            <option value="電子">電子</option>
                            <option value="金融">金融</option>
                            <option value="傳產">傳產</option>
                            <option value="航運">航運</option>
                            <option value="其他">其他</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">更新</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 刪除確認 Modal -->
<div class="modal fade" id="deleteStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">確認刪除</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>確定要刪除這支股票嗎？</p>
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="stock_id" id="delete_stock_id">
                    <button type="submit" class="btn btn-danger">確定刪除</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#stocksTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Chinese-traditional.json"
        }
    });
});

function updatePrice(stock) {
    $('#update_stock_id').val(stock.stock_id);
    $('#update_stock_code').val(stock.stock_code);
    $('#update_stock_name').val(stock.stock_name);
    $('#updatePriceModal').modal('show');
}

function editStock(stock) {
    $('#edit_stock_id').val(stock.stock_id);
    $('#edit_stock_code').val(stock.stock_code);
    $('#edit_stock_name').val(stock.stock_name);
    $('#edit_industry_type').val(stock.industry_type);
    $('#editStockModal').modal('show');
}

function deleteStock(stockId) {
    $('#delete_stock_id').val(stockId);
    $('#deleteStockModal').modal('show');
}
</script>

<?php include 'includes/footer.php'; ?>
