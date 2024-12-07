<!-- header.php -->
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>券商客戶管理系統</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">券商管理系統</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="brokers.php">營業員管理</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="clients.php">客戶管理</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="stocks.php">股票管理</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transactions.php">交易紀錄</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="portfolios.php">投資組合</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">績效報告</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
