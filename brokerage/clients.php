<?php
include 'includes/connect.php';
include 'includes/header.php';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        // 新增客戶
        if ($_POST['action'] == 'add') {
            $name = $db->real_escape_string($_POST['name']);
            $id_number = $db->real_escape_string($_POST['id_number']);
            $phone = $db->real_escape_string($_POST['phone']);
            $broker_id = $db->real_escape_string($_POST['broker_id']);
            $risk_level = $db->real_escape_string($_POST['risk_level']);
            
            $sql = "INSERT INTO clients (name, id_number, phone, broker_id, risk_level, registration_date) 
                    VALUES ('$name', '$id_number', '$phone', '$broker_id', '$risk_level', CURDATE())";
            if ($db->query($sql)) {
                echo "<div class='alert alert-success'>新增客戶成功！</div>";
            } else {
                echo "<div class='alert alert-danger'>新增失敗：" . $db->error . "</div>";
            }
        }
        
        // 刪除客戶 - 修改過的刪除功能
        if ($_POST['action'] == 'delete') {
            $client_id = $db->real_escape_string($_POST['client_id']);
            
            $db->begin_transaction();
            try {
                // 先刪除相關的交易紀錄
                $db->query("DELETE FROM transactions WHERE client_id = '$client_id'");
                
                // 再刪除相關的投資組合
                $db->query("DELETE FROM portfolios WHERE client_id = '$client_id'");
                
                // 最後刪除客戶資料
                $db->query("DELETE FROM clients WHERE client_id = '$client_id'");
                
                $db->commit();
                echo "<div class='alert alert-success'>刪除客戶成功！</div>";
            } catch (Exception $e) {
                $db->rollback();
                echo "<div class='alert alert-danger'>刪除失敗：" . $e->getMessage() . "</div>";
            }
        }
        
        // 更新客戶
        if ($_POST['action'] == 'edit') {
            $client_id = $db->real_escape_string($_POST['client_id']);
            $name = $db->real_escape_string($_POST['name']);
            $phone = $db->real_escape_string($_POST['phone']);
            $broker_id = $db->real_escape_string($_POST['broker_id']);
            $risk_level = $db->real_escape_string($_POST['risk_level']);
            
            $sql = "UPDATE clients SET 
                    name = '$name', 
                    phone = '$phone', 
                    broker_id = '$broker_id', 
                    risk_level = '$risk_level' 
                    WHERE client_id = '$client_id'";
            if ($db->query($sql)) {
                echo "<div class='alert alert-success'>更新客戶資料成功！</div>";
            } else {
                echo "<div class='alert alert-danger'>更新失敗：" . $db->error . "</div>";
            }
        }
    }
}
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <h2>客戶管理</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
                新增客戶
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped" id="clientsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>姓名</th>
                        <th>身分證號</th>
                        <th>電話</th>
                        <th>風險等級</th>
                        <th>所屬營業員</th>
                        <th>註冊日期</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $db->query("SELECT c.*, b.name as broker_name 
                                        FROM clients c 
                                        LEFT JOIN brokers b ON c.broker_id = b.broker_id 
                                        ORDER BY c.client_id");
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['client_id'] . "</td>";
                        echo "<td>" . $row['name'] . "</td>";
                        echo "<td>" . $row['id_number'] . "</td>";
                        echo "<td>" . $row['phone'] . "</td>";
                        echo "<td>" . $row['risk_level'] . "</td>";
                        echo "<td>" . $row['broker_name'] . "</td>";
                        echo "<td>" . $row['registration_date'] . "</td>";
                        echo "<td>
                                <button class='btn btn-sm btn-primary me-1' onclick='editClient(" . json_encode($row) . ")'>編輯</button>
                                <button class='btn btn-sm btn-danger' onclick='deleteClient(" . $row['client_id'] . ")'>刪除</button>
                              </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 新增客戶 Modal -->
<div class="modal fade" id="addClientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新增客戶</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">姓名</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">身分證號</label>
                        <input type="text" class="form-control" name="id_number" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">電話</label>
                        <input type="text" class="form-control" name="phone">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">營業員</label>
                        <select class="form-control" name="broker_id" required>
                            <?php
                            $brokers = $db->query("SELECT broker_id, name FROM brokers WHERE status = 'active'");
                            while ($broker = $brokers->fetch_assoc()) {
                                echo "<option value='" . $broker['broker_id'] . "'>" . $broker['name'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">風險等級</label>
                        <select class="form-control" name="risk_level" required>
                            <option value="low">低風險</option>
                            <option value="medium">中風險</option>
                            <option value="high">高風險</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">儲存</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 編輯客戶 Modal -->
<div class="modal fade" id="editClientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">編輯客戶</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="client_id" id="edit_client_id">
                    <div class="mb-3">
                        <label class="form-label">姓名</label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">電話</label>
                        <input type="text" class="form-control" name="phone" id="edit_phone">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">營業員</label>
                        <select class="form-control" name="broker_id" id="edit_broker_id" required>
                            <?php
                            $brokers = $db->query("SELECT broker_id, name FROM brokers WHERE status = 'active'");
                            while ($broker = $brokers->fetch_assoc()) {
                                echo "<option value='" . $broker['broker_id'] . "'>" . $broker['name'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">風險等級</label>
                        <select class="form-control" name="risk_level" id="edit_risk_level" required>
                            <option value="low">低風險</option>
                            <option value="medium">中風險</option>
                            <option value="high">高風險</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">更新</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 刪除確認 Modal -->
<div class="modal fade" id="deleteClientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">確認刪除</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>確定要刪除這位客戶嗎？</p>
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="client_id" id="delete_client_id">
                    <button type="submit" class="btn btn-danger">確定刪除</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#clientsTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Chinese-traditional.json"
        }
    });
});

function editClient(client) {
    $('#edit_client_id').val(client.client_id);
    $('#edit_name').val(client.name);
    $('#edit_phone').val(client.phone);
    $('#edit_broker_id').val(client.broker_id);
    $('#edit_risk_level').val(client.risk_level);
    $('#editClientModal').modal('show');
}

function deleteClient(clientId) {
    $('#delete_client_id').val(clientId);
    $('#deleteClientModal').modal('show');
}
</script>

<?php include 'includes/footer.php'; ?>
