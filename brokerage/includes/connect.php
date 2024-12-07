<?php
$db_server = "localhost";
$db_name = "brokerage_system";
$db_user = "root";
$db_passwd = "";

// 連接資料庫
$db = mysqli_connect($db_server, $db_user, $db_passwd, $db_name);
if(mysqli_connect_errno()) {
    echo "無法對資料庫連線！" . mysqli_connect_error();
}

// 設定資料庫編碼
mysqli_set_charset($db, 'utf8mb4');
?>