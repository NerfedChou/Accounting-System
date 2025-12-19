-- ============================================
-- Seed Data for Development/Testing
-- Created: 2024-12-19
-- 
-- This file seeds the database with test data
-- matching the mock data structure in the frontend.
--
-- Run this after init.sql
-- ============================================

-- Generate UUIDs for entities (valid UUID v4 format)
SET @company_id = '550e8400-e29b-41d4-a716-446655440001';
SET @admin_user_id = '0462c96f-66d8-4b0e-ab7c-51251f745006'; -- Existing admin user

-- Accounts (valid UUID v4 format)
SET @cash_bdo_id = 'a0000001-0000-4000-8000-000000000001';
SET @tuition_revenue_id = 'a0000001-0000-4000-8000-000000000002';
SET @salaries_expense_id = 'a0000001-0000-4000-8000-000000000003';
SET @utilities_expense_id = 'a0000001-0000-4000-8000-000000000004';
SET @lab_equipment_id = 'a0000001-0000-4000-8000-000000000005';
SET @activity_fund_id = 'a0000001-0000-4000-8000-000000000006';
SET @general_fund_id = 'a0000001-0000-4000-8000-000000000007';

-- Transactions (valid UUID v4 format)
SET @txn1_id = 'b0000001-0000-4000-8000-000000000001';
SET @txn2_id = 'b0000001-0000-4000-8000-000000000002';
SET @txn3_id = 'b0000001-0000-4000-8000-000000000003';
SET @txn4_id = 'b0000001-0000-4000-8000-000000000004';
SET @txn5_id = 'b0000001-0000-4000-8000-000000000005';

-- Transaction Lines (valid UUID v4 format)
SET @line1a = 'c0000001-0000-4000-8000-000000000001';
SET @line1b = 'c0000001-0000-4000-8000-000000000002';
SET @line2a = 'c0000002-0000-4000-8000-000000000001';
SET @line2b = 'c0000002-0000-4000-8000-000000000002';
SET @line3a = 'c0000003-0000-4000-8000-000000000001';
SET @line3b = 'c0000003-0000-4000-8000-000000000002';
SET @line4a = 'c0000004-0000-4000-8000-000000000001';
SET @line4b = 'c0000004-0000-4000-8000-000000000002';
SET @line5a = 'c0000005-0000-4000-8000-000000000001';
SET @line5b = 'c0000005-0000-4000-8000-000000000002';

-- ============================================
-- 1. Insert Company: Metro Dumaguete College
-- ============================================
INSERT INTO companies (id, company_name, legal_name, tax_id, address_street, address_city, address_state, address_postal_code, address_country, currency, status)
VALUES (
    @company_id,
    'Metro Dumaguete College',
    'Metro Dumaguete College, Inc.',
    'PH-123-456-789-001',
    '123 Education Avenue',
    'Dumaguete City',
    'Negros Oriental',
    '6200',
    'Philippines',
    'PHP',
    'active'
) ON DUPLICATE KEY UPDATE company_name = VALUES(company_name);

-- ============================================
-- 2. Insert Admin User (if not exists)
-- ============================================
INSERT INTO users (id, username, email, password_hash, role, registration_status, company_id, is_active)
VALUES (
    @admin_user_id,
    'admin',
    'admin@metrodumaguete.edu.ph',
    '$2y$10$placeholder_hash_for_testing_only', -- Use proper hash in production
    'admin',
    'approved',
    @company_id,
    1
) ON DUPLICATE KEY UPDATE username = VALUES(username);

-- ============================================
-- 3. Insert Chart of Accounts
-- Account codes must be 1000-5999:
-- 1xxx = Assets, 2xxx = Liabilities, 3xxx = Equity, 4xxx = Revenue, 5xxx = Expenses
-- ============================================
INSERT INTO accounts (id, company_id, code, name, type, description, is_active, currency) VALUES
(@cash_bdo_id, @company_id, 1001, 'Cash in Bank - BDO', 'asset', 'BDO Savings Account', 1, 'PHP'),
(@tuition_revenue_id, @company_id, 4001, 'Tuition Revenue', 'revenue', 'Revenue from student tuition fees', 1, 'PHP'),
(@salaries_expense_id, @company_id, 5001, 'Salaries Expense - Faculty', 'expense', 'Faculty payroll expenses', 1, 'PHP'),
(@utilities_expense_id, @company_id, 5002, 'Utilities Expense', 'expense', 'Electricity, water, internet', 1, 'PHP'),
(@lab_equipment_id, @company_id, 1501, 'Laboratory Equipment', 'asset', 'Lab equipment and apparatus', 1, 'PHP'),
(@activity_fund_id, @company_id, 3001, 'Activity Fund', 'equity', 'Student activities fund', 1, 'PHP'),
(@general_fund_id, @company_id, 3002, 'General Fund', 'equity', 'General operating fund', 1, 'PHP')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ============================================
-- 4. Insert Transactions
-- ============================================

-- Transaction 1: Tuition Fee Collection (Pending)
INSERT INTO transactions (id, company_id, transaction_date, description, reference_number, status, created_by)
VALUES (
    @txn1_id,
    @company_id,
    '2024-12-18',
    'Tuition Fee Collection - 1st Semester',
    'JE-2024-001',
    'pending',
    @admin_user_id
) ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Transaction 2: Faculty Payroll (Pending)
INSERT INTO transactions (id, company_id, transaction_date, description, reference_number, status, created_by)
VALUES (
    @txn2_id,
    @company_id,
    '2024-12-15',
    'Faculty Payroll Disbursement - December',
    'PD-2024-001',
    'pending',
    @admin_user_id
) ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Transaction 3: Utility Payment (Pending)
INSERT INTO transactions (id, company_id, transaction_date, description, reference_number, status, created_by)
VALUES (
    @txn3_id,
    @company_id,
    '2024-12-10',
    'Utility Payment - DECECO Electric',
    'UP-2024-001',
    'pending',
    @admin_user_id
) ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Transaction 4: Lab Equipment Purchase (Posted)
INSERT INTO transactions (id, company_id, transaction_date, description, reference_number, status, created_by, posted_by, posted_at)
VALUES (
    @txn4_id,
    @company_id,
    '2024-12-08',
    'Laboratory Equipment Purchase',
    'JE-2024-002',
    'posted',
    @admin_user_id,
    @admin_user_id,
    '2024-12-08 14:00:00'
) ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Transaction 5: Fund Allocation (Posted)
INSERT INTO transactions (id, company_id, transaction_date, description, reference_number, status, created_by, posted_by, posted_at)
VALUES (
    @txn5_id,
    @company_id,
    '2024-12-05',
    'Fund Allocation',
    'JE-2024-003',
    'posted',
    @admin_user_id,
    @admin_user_id,
    '2024-12-05 10:00:00'
) ON DUPLICATE KEY UPDATE description = VALUES(description);

-- ============================================
-- 5. Insert Transaction Lines
-- ============================================

-- TXN1 Lines: Tuition Fee Collection (₱45,000)
INSERT INTO transaction_lines (id, transaction_id, account_id, line_type, amount_cents, currency, description, line_order) VALUES
(@line1a, @txn1_id, @cash_bdo_id, 'debit', 4500000, 'PHP', 'Cash received from tuition', 1),
(@line1b, @txn1_id, @tuition_revenue_id, 'credit', 4500000, 'PHP', 'Tuition revenue recognized', 2)
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- TXN2 Lines: Faculty Payroll (₱128,500)
INSERT INTO transaction_lines (id, transaction_id, account_id, line_type, amount_cents, currency, description, line_order) VALUES
(@line2a, @txn2_id, @salaries_expense_id, 'debit', 12850000, 'PHP', 'Faculty salaries for December', 1),
(@line2b, @txn2_id, @cash_bdo_id, 'credit', 12850000, 'PHP', 'Cash disbursement for payroll', 2)
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- TXN3 Lines: Utility Payment (₱8,750)
INSERT INTO transaction_lines (id, transaction_id, account_id, line_type, amount_cents, currency, description, line_order) VALUES
(@line3a, @txn3_id, @utilities_expense_id, 'debit', 875000, 'PHP', 'DECECO electric bill', 1),
(@line3b, @txn3_id, @cash_bdo_id, 'credit', 875000, 'PHP', 'Payment to DECECO', 2)
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- TXN4 Lines: Lab Equipment Purchase (₱75,000)
INSERT INTO transaction_lines (id, transaction_id, account_id, line_type, amount_cents, currency, description, line_order) VALUES
(@line4a, @txn4_id, @lab_equipment_id, 'debit', 7500000, 'PHP', 'Lab equipment acquisition', 1),
(@line4b, @txn4_id, @cash_bdo_id, 'credit', 7500000, 'PHP', 'Payment for equipment', 2)
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- TXN5 Lines: Fund Allocation (₱25,000)
INSERT INTO transaction_lines (id, transaction_id, account_id, line_type, amount_cents, currency, description, line_order) VALUES
(@line5a, @txn5_id, @activity_fund_id, 'debit', 2500000, 'PHP', 'Transfer to activity fund', 1),
(@line5b, @txn5_id, @general_fund_id, 'credit', 2500000, 'PHP', 'From general fund', 2)
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- ============================================
-- 6. Create Approval Records for Pending Transactions
-- ============================================
INSERT INTO approvals (id, company_id, entity_type, entity_id, approval_type, reason, status, amount_cents, priority, requested_by) VALUES
(UUID(), @company_id, 'transaction', @txn1_id, 'transaction_approval', 'Request approval for tuition collection entry', 'pending', 4500000, 1, @admin_user_id),
(UUID(), @company_id, 'transaction', @txn2_id, 'transaction_approval', 'Request approval for payroll disbursement', 'pending', 12850000, 2, @admin_user_id),
(UUID(), @company_id, 'transaction', @txn3_id, 'transaction_approval', 'Request approval for utility payment', 'pending', 875000, 0, @admin_user_id)
ON DUPLICATE KEY UPDATE reason = VALUES(reason);

-- ============================================
-- Summary
-- ============================================
-- Company: Metro Dumaguete College
-- User: admin (admin@metrodumaguete.edu.ph)
-- Accounts: 7 accounts created
-- Transactions: 5 transactions (3 pending, 2 posted)
-- Transaction Lines: 10 lines (2 per transaction)
-- Approvals: 3 pending approvals
