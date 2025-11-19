# Company-Tenant Cascade Deactivation Implementation

## Overview
When a company is deactivated by an admin, all tenants belonging to that company are automatically deactivated as well. This ensures data consistency and security.

## Implementation Details

### 1. Company Toggle API Enhancement
**File:** `/src/php/api/companies/toggle.php`

**Changes:**
- Added database transaction support for atomicity
- When deactivating a company (`is_active = 0`):
  - Automatically deactivates all active tenants in that company
  - Sets their `deactivation_reason` to "Company deactivated by administrator"
  - Logs the bulk deactivation in activity_logs
  - Records the number of affected tenants

**Logic:**
```sql
UPDATE users 
SET is_active = 0, 
    deactivation_reason = 'Company deactivated by administrator',
    updated_at = CURRENT_TIMESTAMP
WHERE company_id = :company_id 
AND role = 'tenant'
AND is_active = 1
```

### 2. Tenant API Protection
Added real-time status checks to all tenant-facing APIs to prevent deactivated users or users from deactivated companies from accessing data.

**Protected APIs:**
- `/src/php/api/dashboard/assets.php`
- `/src/php/api/dashboard/liabilities.php`
- `/src/php/api/dashboard/equity.php`
- `/src/php/api/dashboard/revenue-expenses.php`
- `/src/php/api/dashboard/recent-transactions.php`
- `/src/php/api/accounts/list.php`
- `/src/php/api/transactions/get.php`
- `/src/php/api/reports/balance-sheet.php`
- `/src/php/api/reports/income-statement.php`
- `/src/php/api/companies/get-own.php`

**Protection Logic:**
Each API now checks:
1. If the user's account is active
2. If the user's company is active

```php
// Check if user and company are active
$stmt = $db->prepare("
    SELECT u.is_active, u.deactivation_reason, c.is_active as company_is_active
    FROM users u
    LEFT JOIN companies c ON u.company_id = c.id
    WHERE u.id = :user_id
");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$status = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$status['is_active']) {
    respond(false, 'Account deactivated: ' . ($status['deactivation_reason'] ?? 'Contact administrator'), null, 403);
}

if (!$status['company_is_active']) {
    respond(false, 'Company has been deactivated', null, 403);
}
```

## User Flow

### Admin Deactivates Company:
1. Admin clicks "Deactivate" on a company in the companies management page
2. Backend updates company status to `is_active = 0`
3. Backend automatically deactivates all tenants in that company
4. Backend logs the action with the count of affected tenants
5. Success message returned to admin

### Tenant from Deactivated Company Tries to Access:
1. Tenant logs in (login still works)
2. Frontend redirects to dashboard
3. Dashboard attempts to load data from APIs
4. APIs check user and company status
5. API returns 403 error: "Company has been deactivated"
6. Frontend can display appropriate message to user

### When Company is Reactivated:
- Company status is set to `is_active = 1`
- **Tenants remain deactivated** (they must be manually reactivated by admin)
- This allows selective reactivation of tenants

## Testing

### Test Scenario 1: Deactivate Company
1. Login as admin
2. Navigate to Companies page
3. Deactivate a company that has active tenants
4. Check database: verify all tenants in that company are now deactivated
5. Check activity logs: verify the action was logged

### Test Scenario 2: Deactivated Tenant Access
1. Login as a tenant from the deactivated company
2. Try to access dashboard
3. Verify all API calls return 403 with appropriate error message
4. Verify no data is displayed

### Test Scenario 3: Reactivate Company
1. Login as admin
2. Reactivate the company
3. Verify company is active
4. Verify tenants remain deactivated
5. Manually activate a tenant
6. Login as that tenant and verify access is restored

## Database Schema Reference

### Users Table
- `is_active`: TINYINT(1) - User account status
- `deactivation_reason`: VARCHAR(255) - Reason for deactivation
- `company_id`: INT - Foreign key to companies table

### Companies Table
- `is_active`: TINYINT(1) - Company status

## Security Benefits
1. **Data Isolation**: Deactivated tenants cannot access company data
2. **Cascade Effect**: Ensures all company users are deactivated when company is deactivated
3. **Audit Trail**: All deactivations are logged in activity_logs
4. **Selective Reactivation**: Admin can choose which tenants to reactivate when company is restored

## Future Enhancements
1. Email notification to affected tenants when their company is deactivated
2. Bulk tenant reactivation option when company is reactivated
3. Scheduled deactivation for companies
4. Grace period before full deactivation

