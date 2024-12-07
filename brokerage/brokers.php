<?php
include 'includes/connect.php';
include 'includes/header.php';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        // 新增營業員
        if ($_POST['action'] == 'add') {
            $name = $db->real_escape_string($_POST['name']);
            $employee_number = $db->real_escape_string($_POST['employee_number']);
            $phone = $db->real_escape_string($_POST['phone']);
            $branch_office = $db->real_escape_string($_POST['branch_office']);
            
            $sql = "INSERT INTO brokers (name, employee_number, phone, branch_office) 
                    VALUES ('$name', '$employee_number', '$phone', '$branch_office')";
            if ($db->query($sql)) {
                echo "<div class='alert alert-success'>新增營業員成功！</div>";
            } else {
                echo "<div class='alert alert-danger'>新增失敗：" . $db->error . "</div>";
            }
        }
        
        // 刪除營業員 - 修改過的刪除功能
        if ($_POST['action'] == 'delete') {
            $broker_id = $db->real_escape_string($_POST['broker_id']);
            
            // 檢查是否有客戶關聯
            $check = $db->query("SELECT COUNT(*) as count FROM clients WHERE broker_id = '$broker_id'");
            $row = $check->fetch_assoc();
            if ($row['count'] > 0) {
                echo "<div class='alert alert-danger'>無法刪除：此營業員還有 " . $row['count'] . " 位客戶！請先將客戶轉移給其他營業員。</div>";
            } else {
                // 如果沒有客戶關聯，執行刪除
                $sql = "DELETE FROM brokers WHERE broker_id = '$broker_id'";
                if ($db->query($sql)) {
                    echo "<div class='alert alert-success'>刪除營業員成功！</div>";
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
            <h2>營業員管理</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBrokerModal">
                新增營業員
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped" id="brokersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>姓名</th>
                        <th>員工編號</th>
                        <th>電話</th>
                        <th>分公司</th>
                        <th>狀態</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $db->query("SELECT * FROM brokers ORDER BY broker_id");
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['broker_id'] . "</td>";
                        echo "<td>" . $row['name'] . "</td>";
                        echo "<td>" . $row['employee_number'] . "</td>";
                        echo "<td>" . $row['phone'] . "</td>";
                        echo "<td>" . $row['branch_office'] . "</td>";
                        echo "<td>" . ($row['status'] == 'active' ? '在職' : '離職') . "</td>";
                        echo "<td>
                                <button class='btn btn-sm btn-primary me-1' onclick='editBroker(" . json_encode($row) . ")'>編輯</button>
                                <button class='btn btn-sm btn-danger' onclick='deleteBroker(" . $row['broker_id'] . ")'>刪除</button>
                              </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 新增營業員 Modal -->
<div class="modal fade" id="addBrokerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新增營業員</h5>
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
                        <label class="form-label">員工編號</label>
                        <input type="text" class="form-control" name="employee_number" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">電話</label>
                        <input type="text" class="form-control" name="phone">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">分公司</label>
                        <input type="text" class="form-control" name="branch_office">
                    </div>
                    <button type="submit" class="btn btn-primary">儲存</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 編輯營業員 Modal -->
<div class="modal fade" id="editBrokerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">編輯營業員</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="broker_id" id="edit_broker_id">
                    <div class="mb-3">
                        <label class="form-label">姓名</label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">員工編號</label>
                        <input type="text" class="form-control" name="employee_number" id="edit_employee_number" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">電話</label>
                        <input type="text" class="form-control" name="phone" id="edit_phone">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">分公司</label>
                        <input type="text" class="form-control" name="branch_office" id="edit_branch_office">
                    </div>
                    <button type="submit" class="btn btn-primary">更新</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 刪除確認 Modal -->
<div class="modal fade" id="deleteBrokerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">確認刪除</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>確定要刪除這位營業員嗎？</p>
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="broker_id" id="delete_broker_id">
                    <button type="submit" class="btn btn-danger">確定刪除</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#brokersTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Chinese-traditional.json"
        }
    });
});

function editBroker(broker) {
    $('#edit_broker_id').val(broker.broker_id);
    $('#edit_name').val(broker.name);
    $('#edit_employee_number').val(broker.employee_number);
    $('#edit_phone').val(broker.phone);
    $('#edit_branch_office').val(broker.branch_office);
    $('#editBrokerModal').modal('show');
}

function deleteBroker(brokerId) {
    $('#delete_broker_id').val(brokerId);
    $('#deleteBrokerModal').modal('show');
}
</script>

<?php include 'includes/footer.php'; ?>
