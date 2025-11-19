<?php
/**
 * Application Constants
 * Accounting System
 */

// Transaction Statuses (from database)
define('STATUS_PENDING', 1);
define('STATUS_POSTED', 2);
define('STATUS_VOIDED', 3);

// Account Types (from database)
define('ACCOUNT_TYPE_ASSET', 1);
define('ACCOUNT_TYPE_LIABILITY', 2);
define('ACCOUNT_TYPE_EQUITY', 3);
define('ACCOUNT_TYPE_REVENUE', 4);
define('ACCOUNT_TYPE_EXPENSE', 5);

// User Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_TENANT', 'tenant');

// Transaction Entry Modes
define('ENTRY_MODE_SINGLE', 'single');
define('ENTRY_MODE_DOUBLE', 'double');

// Transaction Types
define('TRANS_TYPE_ASSET', 'Asset');
define('TRANS_TYPE_LIABILITY', 'Liability');
define('TRANS_TYPE_EQUITY', 'Equity');
define('TRANS_TYPE_REVENUE', 'Revenue');
define('TRANS_TYPE_EXPENSE', 'Expense');

// Line Types
define('LINE_TYPE_DEBIT', 'debit');
define('LINE_TYPE_CREDIT', 'credit');

// Recurring Frequencies
define('FREQUENCY_DAILY', 'daily');
define('FREQUENCY_WEEKLY', 'weekly');
define('FREQUENCY_MONTHLY', 'monthly');
define('FREQUENCY_QUARTERLY', 'quarterly');
define('FREQUENCY_YEARLY', 'yearly');

// Recurring Schedule Types
define('SCHEDULE_TYPE_EXPENSE', 'expense');
define('SCHEDULE_TYPE_REVENUE', 'revenue');

// Pagination
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Date formats
define('DATE_FORMAT_SQL', 'Y-m-d');
define('DATE_FORMAT_DISPLAY', 'm/d/Y');
define('DATETIME_FORMAT_DISPLAY', 'm/d/Y H:i');

// Transaction number prefix
define('TRANSACTION_NUMBER_PREFIX', 'JE');

// Application settings
define('APP_NAME', 'Accounting System');
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'America/New_York');

// Error messages
define('MSG_UNAUTHORIZED', 'Unauthorized access');
define('MSG_FORBIDDEN', 'Access forbidden');
define('MSG_NOT_FOUND', 'Resource not found');
define('MSG_VALIDATION_ERROR', 'Validation error');
define('MSG_SERVER_ERROR', 'Internal server error');
define('MSG_TRANSACTION_NOT_BALANCED', 'Transaction debits must equal credits');
define('MSG_CANNOT_EDIT_POSTED', 'Cannot edit posted transaction');
define('MSG_CANNOT_DELETE_POSTED', 'Cannot delete posted transaction');

// Success messages
define('MSG_LOGIN_SUCCESS', 'Login successful');
define('MSG_LOGOUT_SUCCESS', 'Logout successful');
define('MSG_CREATED_SUCCESS', 'Created successfully');
define('MSG_UPDATED_SUCCESS', 'Updated successfully');
define('MSG_DELETED_SUCCESS', 'Deleted successfully');
define('MSG_POSTED_SUCCESS', 'Transaction posted successfully');
define('MSG_VOIDED_SUCCESS', 'Transaction voided successfully');

// HTTP Status codes
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_UNPROCESSABLE', 422);
define('HTTP_SERVER_ERROR', 500);

// Session keys
define('SESSION_USER_ID', 'user_id');
define('SESSION_USER_ROLE', 'role');
define('SESSION_COMPANY_ID', 'company_id');
define('SESSION_USERNAME', 'username');

// File upload
define('UPLOAD_MAX_SIZE', 5242880); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('UPLOAD_PATH', __DIR__ . '/../../uploads/');

// Report types
define('REPORT_BALANCE_SHEET', 'balance_sheet');
define('REPORT_INCOME_STATEMENT', 'income_statement');
define('REPORT_ACCOUNT_LEDGER', 'account_ledger');
define('REPORT_TRIAL_BALANCE', 'trial_balance');

