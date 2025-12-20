-- ============================================
-- Seeding for "All Cases" and functionality verification
-- ============================================

-- Use existing Admin User
SET @admin_user_id = (SELECT id FROM users WHERE username = 'admin' LIMIT 1);
SET @admin_username = 'admin';

-- Create a new Company for clean testing
SET @company_id = '450e8400-e29b-41d4-a716-446655440022';
INSERT INTO companies (id, company_name, legal_name, tax_id, address_street, address_city, address_state, address_postal_code, address_country, currency, status)
VALUES (
    @company_id,
    'Omni Solutions Inc.',
    'Omni Solutions Incorporated',
    'TX-999-888-777',
    '99 Innovation Drive',
    'Manila',
    'NCR',
    '1000',
    'Philippines',
    'PHP',
    'active'
) ON DUPLICATE KEY UPDATE company_name = VALUES(company_name);

-- Update admin user to belong to this company (for testing convenience)
-- Wait, summary says admins have no company. 
-- "BR-IAM-006: Admins have no company"
-- I'll create a tenant user instead.
SET @tenant_user_id = '450e8400-e29b-41d4-a716-446655440033';
INSERT INTO users (id, username, email, password_hash, role, registration_status, company_id, is_active)
VALUES (
    @tenant_user_id,
    'omni_tenant',
    'tenant@omnisolutions.co',
    '$2y$12$J6/7NvIW/kLXVRCe4RQRJuD.e8hd6bdAdluKL0jXamMT1uHLuhpDi', -- Angulo12345
    'tenant',
    'approved',
    @company_id,
    1
) ON DUPLICATE KEY UPDATE username = VALUES(username);

-- ============================================
-- Accounts
-- ============================================
SET @cash_id = UUID();
SET @ar_id = UUID();
SET @revenue_id = UUID();
SET @expense_id = UUID();

INSERT INTO accounts (id, company_id, code, name, type, description, is_active, currency) VALUES
(@cash_id, @company_id, 1001, 'Petty Cash', 'asset', 'Petty Cash Fund', 1, 'PHP'),
(@ar_id, @company_id, 1201, 'Accounts Receivable', 'asset', 'Customer receivables', 1, 'PHP'),
(@revenue_id, @company_id, 4001, 'Service Revenue', 'revenue', 'Revenue from services', 1, 'PHP'),
(@expense_id, @company_id, 5001, 'Office Supplies', 'expense', 'Monthly office supplies', 1, 'PHP');

-- ============================================
-- Transactions
-- ============================================

-- 1. DRAFT Transaction
SET @txn_draft = UUID();
INSERT INTO transactions (id, company_id, transaction_date, description, reference_number, status, created_by)
VALUES (@txn_draft, @company_id, '2025-12-20', 'Draft Transaction Example', 'REF-001', 'draft', @tenant_user_id);

INSERT INTO transaction_lines (id, transaction_id, account_id, line_type, amount_cents, currency, description, line_order) VALUES
(UUID(), @txn_draft, @cash_id, 'debit', 100000, 'PHP', 'Draft Debit', 1),
(UUID(), @txn_draft, @revenue_id, 'credit', 100000, 'PHP', 'Draft Credit', 2);

-- 2. PENDING Transaction
SET @txn_pending = UUID();
INSERT INTO transactions (id, company_id, transaction_date, description, reference_number, status, created_by)
VALUES (@txn_pending, @company_id, '2025-12-20', 'Pending Transaction Example', 'REF-002', 'pending', @tenant_user_id);

INSERT INTO transaction_lines (id, transaction_id, account_id, line_type, amount_cents, currency, description, line_order) VALUES
(UUID(), @txn_pending, @cash_id, 'debit', 250000, 'PHP', 'Pending Debit', 1),
(UUID(), @txn_pending, @revenue_id, 'credit', 250000, 'PHP', 'Pending Credit', 2);

INSERT INTO approvals (id, company_id, entity_type, entity_id, approval_type, reason, status, amount_cents, priority, requested_by)
VALUES (UUID(), @company_id, 'transaction', @txn_pending, 'transaction_approval', 'Monthly service invoice', 'pending', 250000, 1, @tenant_user_id);

-- 3. POSTED Transaction
SET @txn_posted = UUID();
INSERT INTO transactions (id, company_id, transaction_date, description, reference_number, status, created_by, posted_by, posted_at)
VALUES (@txn_posted, @company_id, '2025-12-15', 'Posted Transaction Example', 'REF-003', 'posted', @tenant_user_id, @admin_user_id, NOW());

INSERT INTO transaction_lines (id, transaction_id, account_id, line_type, amount_cents, currency, description, line_order) VALUES
(UUID(), @txn_posted, @cash_id, 'debit', 500000, 'PHP', 'Posted Debit', 1),
(UUID(), @txn_posted, @revenue_id, 'credit', 500000, 'PHP', 'Posted Credit', 2);

-- 4. VOIDED Transaction
SET @txn_voided = UUID();
INSERT INTO transactions (id, company_id, transaction_date, description, reference_number, status, created_by, voided_by, voided_at, void_reason)
VALUES (@txn_voided, @company_id, '2025-12-10', 'Voided Transaction Example', 'REF-004', 'voided', @tenant_user_id, @admin_user_id, NOW(), 'Incorrect amount entered');

INSERT INTO transaction_lines (id, transaction_id, account_id, line_type, amount_cents, currency, description, line_order) VALUES
(UUID(), @txn_voided, @cash_id, 'debit', 75000, 'PHP', 'Voided Debit', 1),
(UUID(), @txn_voided, @expense_id, 'credit', 75000, 'PHP', 'Voided Credit', 2);

-- 5. REJECTED Transaction (in approvals table)
SET @txn_rejected = UUID();
INSERT INTO transactions (id, company_id, transaction_date, description, reference_number, status, created_by)
VALUES (@txn_rejected, @company_id, '2025-12-18', 'Rejected Transaction Example', 'REF-005', 'pending', @tenant_user_id);

INSERT INTO transaction_lines (id, transaction_id, account_id, line_type, amount_cents, currency, description, line_order) VALUES
(UUID(), @txn_rejected, @cash_id, 'debit', 10000, 'PHP', 'Rejected Debit', 1),
(UUID(), @txn_rejected, @revenue_id, 'credit', 10000, 'PHP', 'Rejected Credit', 2);

INSERT INTO approvals (id, company_id, entity_type, entity_id, approval_type, reason, status, amount_cents, priority, requested_by, reviewed_by, reviewed_at, review_notes)
VALUES (UUID(), @company_id, 'transaction', @txn_rejected, 'transaction_approval', 'Weekly maintenance', 'rejected', 10000, 0, @tenant_user_id, @admin_user_id, NOW(), 'Insufficient documentation provided');
