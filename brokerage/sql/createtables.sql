-- 創建資料庫
CREATE DATABASE IF NOT EXISTS brokerage_system;
USE brokerage_system;

-- 建立資料表
-- 營業員資料表
CREATE TABLE brokers (
    broker_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    employee_number VARCHAR(20) UNIQUE NOT NULL,
    phone VARCHAR(20),
    branch_office VARCHAR(50),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 客戶資料表
CREATE TABLE clients (
    client_id INT PRIMARY KEY AUTO_INCREMENT,
    broker_id INT,
    name VARCHAR(50) NOT NULL,
    id_number VARCHAR(20) UNIQUE NOT NULL,
    phone VARCHAR(20),
    risk_level ENUM('low', 'medium', 'high') DEFAULT 'medium',
    registration_date DATE,
    FOREIGN KEY (broker_id) REFERENCES brokers(broker_id)
);

-- 股票基本資料表
CREATE TABLE stocks (
    stock_id INT PRIMARY KEY AUTO_INCREMENT,
    stock_code VARCHAR(10) UNIQUE NOT NULL,
    stock_name VARCHAR(50) NOT NULL,
    industry_type VARCHAR(30),
    current_price DECIMAL(10,2),
    last_update_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 交易紀錄表
CREATE TABLE transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT,
    stock_id INT,
    type ENUM('buy', 'sell') NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    settlement_date DATE,
    FOREIGN KEY (client_id) REFERENCES clients(client_id),
    FOREIGN KEY (stock_id) REFERENCES stocks(stock_id)
);

-- 投資組合現況表
CREATE TABLE portfolios (
    portfolio_id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT,
    stock_id INT,
    total_shares INT DEFAULT 0,
    average_cost DECIMAL(10,2),
    market_value DECIMAL(10,2),
    last_update_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(client_id),
    FOREIGN KEY (stock_id) REFERENCES stocks(stock_id)
);

-- 客戶績效表
-- 重新定義為每筆交易的獲利分析表
CREATE TABLE client_returns (
    return_id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT,
    transaction_id INT,  -- 關聯到具體交易
    invested_amount DECIMAL(15,2),  -- 投資金額
    selling_amount DECIMAL(15,2),   -- 賣出金額
    profit_loss DECIMAL(15,2),      -- 單筆損益
    return_rate DECIMAL(10,4),      -- 單筆報酬率
    holding_period INT,             -- 持有期間(天)
    settlement_date DATE,           -- 結算日期
    FOREIGN KEY (client_id) REFERENCES clients(client_id),
    FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id)
);
DELIMITER $$

CREATE TRIGGER after_transaction_insert
AFTER INSERT ON transactions
FOR EACH ROW
BEGIN
    DECLARE invested_amount DECIMAL(15,2);
    DECLARE selling_amount DECIMAL(15,2);
    DECLARE profit_loss DECIMAL(15,2);
    DECLARE return_rate DECIMAL(10,4);
    DECLARE holding_period INT;

    -- 計算投資金額與賣出金額
    SET invested_amount = CASE 
        WHEN NEW.type = 'buy' THEN NEW.quantity * NEW.price 
        ELSE 0 
    END;

    SET selling_amount = CASE 
        WHEN NEW.type = 'sell' THEN NEW.quantity * NEW.price 
        ELSE 0 
    END;

    -- 計算損益與報酬率（僅對賣出操作）
    IF NEW.type = 'sell' THEN
        SELECT AVG(p.average_cost) INTO profit_loss
        FROM portfolios p
        WHERE p.client_id = NEW.client_id AND p.stock_id = NEW.stock_id;

        SET profit_loss = selling_amount - (NEW.quantity * profit_loss);
        SET return_rate = (profit_loss / (NEW.quantity * profit_loss)) * 100;
    ELSE
        SET profit_loss = 0;
        SET return_rate = 0;
    END IF;

    -- 計算持有天數（僅當交易結算日期存在時）
    IF NEW.settlement_date IS NOT NULL THEN
        SET holding_period = DATEDIFF(NEW.settlement_date, CURRENT_DATE());
    ELSE
        SET holding_period = 0;
    END IF;

    -- 插入到client_returns表
    INSERT INTO client_returns (
        client_id, transaction_id, invested_amount, selling_amount, profit_loss, return_rate, holding_period, settlement_date
    ) VALUES (
        NEW.client_id, NEW.transaction_id, invested_amount, selling_amount, profit_loss, return_rate, holding_period, NEW.settlement_date
    );
END$$

DELIMITER ;
-- 插入測試資料
-- 新增營業員資料
INSERT INTO brokers (name, employee_number, phone, branch_office, status) VALUES
('張志明', 'B001', '0912345678', '台北總部', 'active'),
('李曉華', 'B002', '0923456789', '台中分公司', 'active'),
('王大同', 'B003', '0934567890', '高雄分公司', 'active');

-- 新增股票資料
INSERT INTO stocks (stock_code, stock_name, industry_type, current_price) VALUES
('2330', '台積電', '半導體', 580.00),
('2317', '鴻海', '電子', 104.50),
('2412', '中華電', '通訊網路', 125.50),
('2882', '國泰金', '金融', 42.80),
('2303', '聯電', '半導體', 48.50),
('2881', '富邦金', '金融', 62.30),
('2454', '聯發科', '半導體', 820.00),
('1301', '台塑', '塑膠', 78.90),
('2308', '台達電', '電子', 288.50),
('2002', '中鋼', '鋼鐵', 28.75);

-- 新增客戶資料
INSERT INTO clients (broker_id, name, id_number, phone, risk_level, registration_date) VALUES
(1, '林小明', 'A123456789', '0912111222', 'medium', '2023-01-15'),
(1, '陳大寶', 'B123456789', '0923333444', 'high', '2023-02-20'),
(2, '黃美麗', 'C123456789', '0934444555', 'low', '2023-03-10'),
(2, '劉志豪', 'D123456789', '0945555666', 'high', '2023-04-05'),
(3, '張小芬', 'E123456789', '0956666777', 'medium', '2023-05-12'),
(3, '王建國', 'F123456789', '0967777888', 'low', '2023-06-18');

-- 新增交易紀錄
INSERT INTO transactions (client_id, stock_id, type, quantity, price, transaction_date, settlement_date) VALUES
(1, 1, 'buy', 1000, 550.00, '2023-07-01', '2023-07-04'),
(2, 2, 'buy', 2000, 100.50, '2023-07-02', '2023-07-05'),
(3, 3, 'buy', 1500, 120.00, '2023-07-03', '2023-07-06'),
(4, 4, 'buy', 3000, 40.50, '2023-07-04', '2023-07-07'),
(5, 5, 'buy', 2000, 45.00, '2023-07-05', '2023-07-08'),
(6, 6, 'buy', 1800, 60.00, '2023-07-06', '2023-07-09'),
(1, 7, 'buy', 500, 800.00, '2023-07-07', '2023-07-10'),
(2, 8, 'buy', 2500, 75.00, '2023-07-08', '2023-07-11'),
(3, 9, 'buy', 1000, 280.00, '2023-07-09', '2023-07-12'),
(4, 10, 'buy', 5000, 27.50, '2023-07-10', '2023-07-13');

-- 更新投資組合
INSERT INTO portfolios (client_id, stock_id, total_shares, average_cost, market_value)
SELECT 
    t.client_id,
    t.stock_id,
    SUM(CASE WHEN t.type = 'buy' THEN t.quantity ELSE -t.quantity END) as total_shares,
    AVG(t.price) as average_cost,
    s.current_price * SUM(CASE WHEN t.type = 'buy' THEN t.quantity ELSE -t.quantity END) as market_value
FROM transactions t
JOIN stocks s ON t.stock_id = s.stock_id
GROUP BY t.client_id, t.stock_id;