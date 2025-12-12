# API Error Codes Reference

> **Master Architect Reference**: Complete error code reference for the Accounting System API.

## Error Response Structure

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable error message",
    "details": {
      "field": "additional context"
    }
  },
  "meta": {
    "timestamp": "2025-12-13T10:30:00Z",
    "requestId": "uuid"
  }
}
```

---

## Authentication Errors (AUTH_*)

| Code | HTTP Status | Description |
|------|-------------|-------------|
| AUTH_INVALID_CREDENTIALS | 401 | Username or password is incorrect |
| AUTH_TOKEN_EXPIRED | 401 | JWT token has expired |
| AUTH_TOKEN_INVALID | 401 | JWT token is malformed or invalid |
| AUTH_TOKEN_MISSING | 401 | Authorization header is missing |
| AUTH_ACCOUNT_DEACTIVATED | 403 | User account has been deactivated |
| AUTH_ACCOUNT_PENDING | 403 | User registration is pending approval |
| AUTH_SESSION_EXPIRED | 401 | Session has expired, please login again |
| AUTH_PASSWORD_REQUIRED | 400 | Password is required |
| AUTH_USERNAME_REQUIRED | 400 | Username is required |

### Examples

**Invalid Credentials:**
```json
{
  "success": false,
  "error": {
    "code": "AUTH_INVALID_CREDENTIALS",
    "message": "Invalid username or password"
  }
}
```

**Token Expired:**
```json
{
  "success": false,
  "error": {
    "code": "AUTH_TOKEN_EXPIRED",
    "message": "Your session has expired. Please login again.",
    "details": {
      "expiredAt": "2025-12-12T10:30:00Z"
    }
  }
}
```

---

## Authorization Errors (AUTHZ_*)

| Code | HTTP Status | Description |
|------|-------------|-------------|
| AUTHZ_FORBIDDEN | 403 | User does not have permission for this action |
| AUTHZ_ADMIN_REQUIRED | 403 | This action requires admin privileges |
| AUTHZ_COMPANY_MISMATCH | 403 | User cannot access resources from another company |
| AUTHZ_SELF_ACTION_PROHIBITED | 403 | Cannot perform this action on yourself |
| AUTHZ_OWNER_REQUIRED | 403 | Only the owner can perform this action |

### Examples

**Admin Required:**
```json
{
  "success": false,
  "error": {
    "code": "AUTHZ_ADMIN_REQUIRED",
    "message": "This action requires administrator privileges",
    "details": {
      "requiredRole": "admin",
      "currentRole": "tenant"
    }
  }
}
```

---

## Validation Errors (VALIDATION_*)

| Code | HTTP Status | Description |
|------|-------------|-------------|
| VALIDATION_ERROR | 400 | One or more fields failed validation |
| VALIDATION_REQUIRED | 400 | Required field is missing |
| VALIDATION_INVALID_FORMAT | 400 | Field format is invalid |
| VALIDATION_MIN_LENGTH | 400 | Field is below minimum length |
| VALIDATION_MAX_LENGTH | 400 | Field exceeds maximum length |
| VALIDATION_MIN_VALUE | 400 | Value is below minimum |
| VALIDATION_MAX_VALUE | 400 | Value exceeds maximum |
| VALIDATION_INVALID_EMAIL | 400 | Invalid email format |
| VALIDATION_INVALID_DATE | 400 | Invalid date format |
| VALIDATION_INVALID_UUID | 400 | Invalid UUID format |
| VALIDATION_INVALID_ENUM | 400 | Value not in allowed enum |

### Examples

**Multiple Validation Errors:**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Request validation failed",
    "details": [
      {
        "field": "email",
        "code": "VALIDATION_INVALID_EMAIL",
        "message": "Invalid email format"
      },
      {
        "field": "password",
        "code": "VALIDATION_MIN_LENGTH",
        "message": "Password must be at least 8 characters",
        "constraints": {
          "minLength": 8
        }
      }
    ]
  }
}
```

---

## Resource Errors (RESOURCE_*)

| Code | HTTP Status | Description |
|------|-------------|-------------|
| RESOURCE_NOT_FOUND | 404 | Requested resource does not exist |
| RESOURCE_ALREADY_EXISTS | 409 | Resource with same identifier already exists |
| RESOURCE_DELETED | 410 | Resource has been deleted |
| RESOURCE_LOCKED | 423 | Resource is locked for editing |

### Examples

**Not Found:**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "Transaction not found",
    "details": {
      "resourceType": "Transaction",
      "resourceId": "uuid-here"
    }
  }
}
```

---

## User Errors (USER_*)

| Code | HTTP Status | Description |
|------|-------------|-------------|
| USER_NOT_FOUND | 404 | User does not exist |
| USER_ALREADY_EXISTS | 409 | Username or email already registered |
| USER_DEACTIVATED | 403 | User account is deactivated |
| USER_PENDING_APPROVAL | 403 | User registration pending approval |
| USER_PASSWORD_WEAK | 400 | Password does not meet complexity requirements |
| USER_PASSWORD_MISMATCH | 400 | Current password is incorrect |
| USER_CANNOT_DEACTIVATE_SELF | 400 | Cannot deactivate your own account |

### Examples

**Username Exists:**
```json
{
  "success": false,
  "error": {
    "code": "USER_ALREADY_EXISTS",
    "message": "Username already taken",
    "details": {
      "field": "username"
    }
  }
}
```

**Weak Password:**
```json
{
  "success": false,
  "error": {
    "code": "USER_PASSWORD_WEAK",
    "message": "Password does not meet requirements",
    "details": {
      "requirements": [
        "At least 8 characters",
        "At least one uppercase letter",
        "At least one lowercase letter",
        "At least one digit"
      ],
      "missing": ["uppercase letter", "digit"]
    }
  }
}
```

---

## Company Errors (COMPANY_*)

| Code | HTTP Status | Description |
|------|-------------|-------------|
| COMPANY_NOT_FOUND | 404 | Company does not exist |
| COMPANY_ALREADY_EXISTS | 409 | Company with same name or tax ID exists |
| COMPANY_DEACTIVATED | 403 | Company is deactivated |
| COMPANY_NOT_ACTIVE | 422 | Company is not in active status |
| COMPANY_TAX_ID_EXISTS | 409 | Tax ID already registered |
| COMPANY_CANNOT_DEACTIVATE | 422 | Company has pending operations |

---

## Account Errors (ACCOUNT_*)

| Code | HTTP Status | Description |
|------|-------------|-------------|
| ACCOUNT_NOT_FOUND | 404 | Account does not exist |
| ACCOUNT_CODE_EXISTS | 409 | Account code already exists in company |
| ACCOUNT_DEACTIVATED | 422 | Account is deactivated |
| ACCOUNT_HAS_BALANCE | 422 | Cannot deactivate account with non-zero balance |
| ACCOUNT_HAS_CHILDREN | 422 | Cannot deactivate account with active child accounts |
| ACCOUNT_IS_SYSTEM | 422 | Cannot modify or delete system account |
| ACCOUNT_INVALID_CODE | 400 | Account code format is invalid |
| ACCOUNT_INVALID_PARENT | 400 | Invalid parent account |
| ACCOUNT_HAS_TRANSACTIONS | 422 | Cannot delete account with transaction history |

### Examples

**Has Balance:**
```json
{
  "success": false,
  "error": {
    "code": "ACCOUNT_HAS_BALANCE",
    "message": "Cannot deactivate account with non-zero balance",
    "details": {
      "accountId": "uuid",
      "accountCode": "1000",
      "accountName": "Cash",
      "currentBalance": 5000.00
    }
  }
}
```

---

## Transaction Errors (TRANSACTION_*)

| Code | HTTP Status | Description |
|------|-------------|-------------|
| TRANSACTION_NOT_FOUND | 404 | Transaction does not exist |
| TRANSACTION_UNBALANCED | 422 | Debits do not equal credits |
| TRANSACTION_MIN_LINES | 422 | Transaction requires at least 2 lines |
| TRANSACTION_INVALID_AMOUNT | 400 | Amount must be positive |
| TRANSACTION_ALREADY_POSTED | 422 | Posted transactions cannot be modified |
| TRANSACTION_ALREADY_VOIDED | 422 | Transaction has already been voided |
| TRANSACTION_NOT_POSTED | 422 | Transaction is not in posted status |
| TRANSACTION_REQUIRES_APPROVAL | 202 | Transaction requires approval before posting |
| TRANSACTION_BACKDATED_EXCEEDED | 422 | Transaction date exceeds allowed backdating |
| TRANSACTION_PENDING_APPROVAL | 409 | Transaction has pending approval request |

### Examples

**Unbalanced:**
```json
{
  "success": false,
  "error": {
    "code": "TRANSACTION_UNBALANCED",
    "message": "Transaction debits must equal credits",
    "details": {
      "totalDebits": 1500.00,
      "totalCredits": 1000.00,
      "difference": 500.00
    }
  }
}
```

**Requires Approval:**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "status": "pending",
    "requiresApproval": true,
    "approval": {
      "id": "uuid",
      "type": "negative_equity",
      "reason": "Transaction would result in negative Owner's Capital balance",
      "details": {
        "accountName": "Owner's Capital",
        "currentBalance": 5000.00,
        "projectedBalance": -500.00
      }
    }
  },
  "meta": {
    "httpStatus": 202
  }
}
```

---

## Ledger Errors (LEDGER_*)

| Code | HTTP Status | Description |
|------|-------------|-------------|
| LEDGER_NEGATIVE_BALANCE | 422 | Operation would result in prohibited negative balance |
| LEDGER_EQUATION_VIOLATED | 500 | Accounting equation violated (system error) |
| LEDGER_CONCURRENT_MODIFICATION | 409 | Concurrent modification detected, retry |

### Examples

**Negative Balance:**
```json
{
  "success": false,
  "error": {
    "code": "LEDGER_NEGATIVE_BALANCE",
    "message": "Asset accounts cannot have negative balance",
    "details": {
      "accountId": "uuid",
      "accountCode": "1000",
      "accountName": "Cash",
      "accountType": "asset",
      "currentBalance": 500.00,
      "projectedBalance": -100.00,
      "change": -600.00
    }
  }
}
```

---

## Approval Errors (APPROVAL_*)

| Code | HTTP Status | Description |
|------|-------------|-------------|
| APPROVAL_NOT_FOUND | 404 | Approval request does not exist |
| APPROVAL_NOT_PENDING | 422 | Approval is not in pending status |
| APPROVAL_EXPIRED | 422 | Approval request has expired |
| APPROVAL_SELF_APPROVAL | 403 | Cannot approve your own request |
| APPROVAL_ALREADY_PROCESSED | 409 | Approval has already been processed |
| APPROVAL_REASON_REQUIRED | 400 | Rejection reason is required |
| APPROVAL_NOT_AUTHORIZED | 403 | User not authorized to approve this request |

### Examples

**Self Approval:**
```json
{
  "success": false,
  "error": {
    "code": "APPROVAL_SELF_APPROVAL",
    "message": "You cannot approve your own request",
    "details": {
      "requestedBy": "uuid",
      "attemptedApprover": "uuid"
    }
  }
}
```

---

## Report Errors (REPORT_*)

| Code | HTTP Status | Description |
|------|-------------|-------------|
| REPORT_GENERATION_FAILED | 500 | Failed to generate report |
| REPORT_INVALID_PERIOD | 400 | Invalid report period specified |
| REPORT_EXPORT_FAILED | 500 | Failed to export report |
| REPORT_FORMAT_UNSUPPORTED | 400 | Report format not supported |

---

## Rate Limiting Errors

| Code | HTTP Status | Description |
|------|-------------|-------------|
| RATE_LIMIT_EXCEEDED | 429 | Too many requests, try again later |

### Example

```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Rate limit exceeded. Try again in 45 seconds.",
    "details": {
      "limit": 100,
      "window": "1 minute",
      "retryAfter": 45
    }
  }
}
```

Headers:
```http
Retry-After: 45
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1702468845
```

---

## System Errors

| Code | HTTP Status | Description |
|------|-------------|-------------|
| INTERNAL_ERROR | 500 | Unexpected internal server error |
| SERVICE_UNAVAILABLE | 503 | Service temporarily unavailable |
| DATABASE_ERROR | 500 | Database operation failed |
| EXTERNAL_SERVICE_ERROR | 502 | External service request failed |

### Example

```json
{
  "success": false,
  "error": {
    "code": "INTERNAL_ERROR",
    "message": "An unexpected error occurred. Please try again.",
    "details": {
      "requestId": "uuid-for-debugging"
    }
  }
}
```

---

## Error Handling Best Practices

### Client-Side Handling

```javascript
async function handleApiResponse(response) {
  const data = await response.json();

  if (!data.success) {
    const error = data.error;

    switch (error.code) {
      case 'AUTH_TOKEN_EXPIRED':
        // Redirect to login
        await refreshToken();
        break;

      case 'VALIDATION_ERROR':
        // Show field-specific errors
        showValidationErrors(error.details);
        break;

      case 'TRANSACTION_REQUIRES_APPROVAL':
        // Show approval pending message
        showApprovalPending(data.data.approval);
        break;

      case 'RATE_LIMIT_EXCEEDED':
        // Wait and retry
        const retryAfter = error.details.retryAfter;
        await sleep(retryAfter * 1000);
        return retry();

      default:
        showGenericError(error.message);
    }
  }

  return data;
}
```

### Logging and Monitoring

Always include `requestId` when reporting errors to support:

```
Error occurred:
  Request ID: abc123-uuid
  Error Code: TRANSACTION_UNBALANCED
  User: johndoe
  Timestamp: 2025-12-13T10:30:00Z
```
