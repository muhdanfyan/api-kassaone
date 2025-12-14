-- =========================================
-- Finance Dashboard - SQL Verification
-- =========================================
-- Use these queries to manually verify API calculations
-- Run directly in MySQL Workbench or phpMyAdmin

-- =========================================
-- 1. TOTAL KAS VERIFICATION
-- =========================================
SELECT 
    (SELECT COALESCE(SUM(amount), 0) 
     FROM savings_transactions 
     WHERE transaction_type = 'deposit' 
       AND status = 'approved') AS total_pemasukan,
    
    (SELECT COALESCE(SUM(amount), 0) 
     FROM expenses) AS total_pengeluaran,
    
    (SELECT COALESCE(SUM(amount), 0) 
     FROM savings_transactions 
     WHERE transaction_type = 'deposit' 
       AND status = 'approved') - 
    (SELECT COALESCE(SUM(amount), 0) 
     FROM expenses) AS total_kas;

-- Expected: total_kas should match API /finance/summary response


-- =========================================
-- 2. PEMASUKAN BULAN INI
-- =========================================
SELECT 
    COALESCE(SUM(amount), 0) AS pemasukan_bulan_ini
FROM savings_transactions
WHERE transaction_type = 'deposit'
  AND status = 'approved'
  AND MONTH(transaction_date) = MONTH(CURDATE())
  AND YEAR(transaction_date) = YEAR(CURDATE());

-- Expected: Should match API /finance/summary -> pemasukan_bulan_ini


-- =========================================
-- 3. PENGELUARAN BULAN INI
-- =========================================
SELECT 
    COALESCE(SUM(amount), 0) AS pengeluaran_bulan_ini
FROM expenses
WHERE MONTH(expense_date) = MONTH(CURDATE())
  AND YEAR(expense_date) = YEAR(CURDATE());

-- Expected: Should match API /finance/summary -> pengeluaran_bulan_ini


-- =========================================
-- 4. LABA/RUGI BULAN INI
-- =========================================
SELECT 
    (SELECT COALESCE(SUM(amount), 0)
     FROM savings_transactions
     WHERE transaction_type = 'deposit'
       AND status = 'approved'
       AND MONTH(transaction_date) = MONTH(CURDATE())
       AND YEAR(transaction_date) = YEAR(CURDATE())) AS pemasukan,
    
    (SELECT COALESCE(SUM(amount), 0)
     FROM expenses
     WHERE MONTH(expense_date) = MONTH(CURDATE())
       AND YEAR(expense_date) = YEAR(CURDATE())) AS pengeluaran,
    
    (SELECT COALESCE(SUM(amount), 0)
     FROM savings_transactions
     WHERE transaction_type = 'deposit'
       AND status = 'approved'
       AND MONTH(transaction_date) = MONTH(CURDATE())
       AND YEAR(transaction_date) = YEAR(CURDATE())) -
    (SELECT COALESCE(SUM(amount), 0)
     FROM expenses
     WHERE MONTH(expense_date) = MONTH(CURDATE())
       AND YEAR(expense_date) = YEAR(CURDATE())) AS laba_rugi_bulan_ini;

-- Expected: Should match API /finance/summary -> laba_rugi_bulan_ini


-- =========================================
-- 5. MONTHLY DATA (Last 6 Months)
-- =========================================
-- Note: Adjust dates based on current month
SELECT 
    MONTHNAME(transaction_date) AS month,
    YEAR(transaction_date) AS year,
    MONTH(transaction_date) AS month_num,
    
    (SELECT COALESCE(SUM(amount), 0)
     FROM savings_transactions
     WHERE transaction_type = 'deposit'
       AND status = 'approved'
       AND MONTH(transaction_date) = month_num
       AND YEAR(transaction_date) = year) AS pemasukan,
    
    (SELECT COALESCE(SUM(amount), 0)
     FROM expenses
     WHERE MONTH(expense_date) = month_num
       AND YEAR(expense_date) = year) AS pengeluaran
FROM (
    SELECT DISTINCT 
        DATE_FORMAT(transaction_date, '%Y-%m-01') AS transaction_date
    FROM (
        SELECT transaction_date FROM savings_transactions
        UNION
        SELECT expense_date AS transaction_date FROM expenses
    ) AS all_dates
    WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
) AS months
ORDER BY year DESC, month_num DESC
LIMIT 6;

-- Expected: Should match API /finance/monthly?months=6


-- =========================================
-- 6. RECENT TRANSACTIONS (Last 10)
-- =========================================
SELECT * FROM (
    -- Income transactions
    SELECT 
        st.id,
        st.transaction_date,
        'pemasukan' AS type,
        st.amount,
        CONCAT(sty.name, ' - ', m.full_name) AS description,
        m.full_name AS member_name,
        sty.name AS account_name,
        'savings' AS source,
        m.full_name AS created_by,
        st.created_at
    FROM savings_transactions st
    JOIN members m ON st.member_id = m.id
    JOIN savings_types sty ON st.savings_type_id = sty.id
    WHERE st.transaction_type = 'deposit'
      AND st.status = 'approved'
    
    UNION ALL
    
    -- Expense transactions
    SELECT 
        e.id,
        e.expense_date AS transaction_date,
        'pengeluaran' AS type,
        e.amount,
        e.description,
        NULL AS member_name,
        a.name AS account_name,
        'expense' AS source,
        COALESCE(u.full_name, 'System') AS created_by,
        e.created_at
    FROM expenses e
    JOIN accounts a ON e.account_id = a.id
    LEFT JOIN users u ON e.created_by = u.id
) AS combined_transactions
ORDER BY transaction_date DESC, created_at DESC
LIMIT 10;

-- Expected: Should match API /finance/transactions/recent?limit=10


-- =========================================
-- 7. BREAKDOWN - PEMASUKAN BY TYPE
-- =========================================
SELECT 
    sty.account_type,
    SUM(st.amount) AS total
FROM savings_transactions st
JOIN savings_types sty ON st.savings_type_id = sty.id
WHERE st.transaction_type = 'deposit'
  AND st.status = 'approved'
  AND st.transaction_date BETWEEN '2024-01-01' AND '2024-12-31'
GROUP BY sty.account_type
ORDER BY total DESC;

-- Expected: Should match API /finance/breakdown -> pemasukan.breakdown_by_type


-- =========================================
-- 8. BREAKDOWN - PENGELUARAN BY ACCOUNT
-- =========================================
SELECT 
    a.id AS account_id,
    a.name AS account_name,
    SUM(e.amount) AS total
FROM expenses e
JOIN accounts a ON e.account_id = a.id
WHERE e.expense_date BETWEEN '2024-01-01' AND '2024-12-31'
GROUP BY a.id, a.name
ORDER BY total DESC;

-- Expected: Should match API /finance/breakdown -> pengeluaran.breakdown_by_account


-- =========================================
-- 9. BREAKDOWN - TOTAL LABA/RUGI
-- =========================================
SELECT 
    (SELECT COALESCE(SUM(st.amount), 0)
     FROM savings_transactions st
     JOIN savings_types sty ON st.savings_type_id = sty.id
     WHERE st.transaction_type = 'deposit'
       AND st.status = 'approved'
       AND st.transaction_date BETWEEN '2024-01-01' AND '2024-12-31') AS total_pemasukan,
    
    (SELECT COALESCE(SUM(e.amount), 0)
     FROM expenses e
     WHERE e.expense_date BETWEEN '2024-01-01' AND '2024-12-31') AS total_pengeluaran,
    
    (SELECT COALESCE(SUM(st.amount), 0)
     FROM savings_transactions st
     WHERE st.transaction_type = 'deposit'
       AND st.status = 'approved'
       AND st.transaction_date BETWEEN '2024-01-01' AND '2024-12-31') -
    (SELECT COALESCE(SUM(e.amount), 0)
     FROM expenses e
     WHERE e.expense_date BETWEEN '2024-01-01' AND '2024-12-31') AS total_laba_rugi;

-- Expected: Should match API /finance/breakdown -> total_laba_rugi


-- =========================================
-- 10. DATA VALIDATION CHECKS
-- =========================================

-- Check for orphaned savings transactions (missing member or savings_type)
SELECT 
    COUNT(*) AS orphaned_savings
FROM savings_transactions st
LEFT JOIN members m ON st.member_id = m.id
LEFT JOIN savings_types sty ON st.savings_type_id = sty.id
WHERE m.id IS NULL OR sty.id IS NULL;
-- Expected: 0 (no orphaned records)


-- Check for orphaned expenses (missing account)
SELECT 
    COUNT(*) AS orphaned_expenses
FROM expenses e
LEFT JOIN accounts a ON e.account_id = a.id
WHERE a.id IS NULL;
-- Expected: 0 (no orphaned records)


-- Check for negative amounts
SELECT 
    'savings_transactions' AS table_name,
    COUNT(*) AS negative_count
FROM savings_transactions
WHERE amount < 0

UNION ALL

SELECT 
    'expenses' AS table_name,
    COUNT(*) AS negative_count
FROM expenses
WHERE amount < 0;
-- Expected: 0 for both (no negative amounts)


-- Check for NULL amounts
SELECT 
    'savings_transactions' AS table_name,
    COUNT(*) AS null_count
FROM savings_transactions
WHERE amount IS NULL

UNION ALL

SELECT 
    'expenses' AS table_name,
    COUNT(*) AS null_count
FROM expenses
WHERE amount IS NULL;
-- Expected: 0 for both (no NULL amounts)


-- =========================================
-- 11. SAMPLE DATA SUMMARY
-- =========================================

-- Count transactions by type
SELECT 
    'Savings Transactions' AS type,
    COUNT(*) AS total_count,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count,
    COALESCE(SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END), 0) AS total_amount
FROM savings_transactions
WHERE transaction_type = 'deposit'

UNION ALL

SELECT 
    'Expenses' AS type,
    COUNT(*) AS total_count,
    COUNT(*) AS approved_count,  -- Expenses don't have status
    0 AS pending_count,
    0 AS rejected_count,
    COALESCE(SUM(amount), 0) AS total_amount
FROM expenses;


-- Count savings types
SELECT 
    sty.account_type,
    COUNT(DISTINCT st.id) AS transaction_count,
    COUNT(DISTINCT st.member_id) AS unique_members,
    COALESCE(SUM(st.amount), 0) AS total_amount
FROM savings_types sty
LEFT JOIN savings_transactions st ON sty.id = st.savings_type_id 
    AND st.transaction_type = 'deposit'
    AND st.status = 'approved'
GROUP BY sty.account_type;


-- Count expense accounts
SELECT 
    a.name AS account_name,
    COUNT(e.id) AS transaction_count,
    COALESCE(SUM(e.amount), 0) AS total_amount
FROM accounts a
LEFT JOIN expenses e ON a.id = e.account_id
GROUP BY a.id, a.name
ORDER BY total_amount DESC;


-- =========================================
-- 12. PERFORMANCE CHECK
-- =========================================

-- Check table sizes
SELECT 
    'savings_transactions' AS table_name,
    COUNT(*) AS row_count
FROM savings_transactions

UNION ALL

SELECT 
    'expenses' AS table_name,
    COUNT(*) AS row_count
FROM expenses

UNION ALL

SELECT 
    'members' AS table_name,
    COUNT(*) AS row_count
FROM members

UNION ALL

SELECT 
    'savings_types' AS table_name,
    COUNT(*) AS row_count
FROM savings_types

UNION ALL

SELECT 
    'accounts' AS table_name,
    COUNT(*) AS row_count
FROM accounts;


-- Check for missing indexes (if slow)
-- EXPLAIN SELECT ... (run EXPLAIN before slow queries)


-- =========================================
-- NOTES:
-- =========================================
-- 1. All pemasukan queries filter by:
--    - transaction_type = 'deposit'
--    - status = 'approved'
--
-- 2. Expenses do NOT have status field
--    (all expenses are considered final)
--
-- 3. COALESCE ensures 0 instead of NULL
--    when there's no data
--
-- 4. Indonesian month names:
--    Januari, Februari, Maret, April, Mei, Juni,
--    Juli, Agustus, September, Oktober, November, Desember
--
-- 5. Date format in API: YYYY-MM-DD
--
-- =========================================
