# API Specification

> **Master Architect Reference**: Complete REST API specification for the Accounting System.

## API Overview

### Base URL
```
Production:  https://api.accounting-system.com/v1
Development: http://localhost:8080/v1
```

### Authentication
All endpoints (except `/auth/*`) require Bearer token authentication:
```http
Authorization: Bearer <jwt_token>
```

### Content Type
```http
Content-Type: application/json
Accept: application/json
```

### Response Format
All responses follow this structure:
```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "timestamp": "2025-12-13T10:30:00Z",
    "requestId": "uuid"
  }
}
```

Error responses:
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Human-readable error message",
    "details": [ ... ]
  },
  "meta": {
    "timestamp": "2025-12-13T10:30:00Z",
    "requestId": "uuid"
  }
}
```

### HTTP Status Codes
| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 204 | No Content (successful deletion) |
| 400 | Bad Request (validation error) |
| 401 | Unauthorized (missing/invalid token) |
| 403 | Forbidden (insufficient permissions) |
| 404 | Not Found |
| 409 | Conflict (duplicate, concurrency error) |
| 422 | Unprocessable Entity (business rule violation) |
| 500 | Internal Server Error |

---

## Authentication Endpoints

### POST /auth/login
Authenticate user and receive JWT token.

**Request:**
```json
{
  "username": "string",
  "password": "string"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "expiresAt": "2025-12-14T10:30:00Z",
    "user": {
      "id": "uuid",
      "username": "string",
      "email": "string",
      "role": "admin|tenant",
      "companyId": "uuid|null"
    }
  }
}
```

**Errors:**
- 401: Invalid credentials
- 403: Account deactivated or pending approval

### POST /auth/logout
Invalidate current session.

**Response (204):** No content

### POST /auth/refresh
Refresh JWT token.

**Request:**
```json
{
  "token": "current_jwt_token"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "token": "new_jwt_token",
    "expiresAt": "2025-12-14T10:30:00Z"
  }
}
```

### POST /auth/register
Register new user (requires admin approval).

**Request:**
```json
{
  "username": "string",
  "email": "string",
  "password": "string",
  "companyId": "uuid"
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "username": "string",
    "email": "string",
    "status": "pending",
    "message": "Registration submitted for admin approval"
  }
}
```

---

## User Endpoints

### GET /users
List users (Admin only, scoped to company for Tenant admins).

**Query Parameters:**
- `status`: pending|active|deactivated
- `role`: admin|tenant
- `page`: int (default: 1)
- `limit`: int (default: 20, max: 100)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "username": "string",
      "email": "string",
      "role": "tenant",
      "status": "active",
      "companyId": "uuid",
      "companyName": "string",
      "lastLoginAt": "2025-12-12T10:30:00Z"
    }
  ],
  "meta": {
    "page": 1,
    "limit": 20,
    "total": 45,
    "totalPages": 3
  }
}
```

### GET /users/{id}
Get user details.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "username": "string",
    "email": "string",
    "role": "tenant",
    "status": "active",
    "companyId": "uuid",
    "companyName": "string",
    "createdAt": "2025-12-01T10:30:00Z",
    "lastLoginAt": "2025-12-12T10:30:00Z"
  }
}
```

### PUT /users/{id}
Update user (Admin only).

**Request:**
```json
{
  "email": "new_email@example.com",
  "role": "tenant"
}
```

### POST /users/{id}/approve
Approve pending user registration (Admin only).

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "status": "active",
    "approvedAt": "2025-12-13T10:30:00Z",
    "approvedBy": "uuid"
  }
}
```

### POST /users/{id}/deactivate
Deactivate user (Admin only).

**Request:**
```json
{
  "reason": "string"
}
```

---

## Company Endpoints

### GET /companies
List companies (System Admin only).

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "companyName": "string",
      "legalName": "string",
      "status": "active",
      "currency": "PHP",
      "userCount": 5
    }
  ]
}
```

### POST /companies
Create company (System Admin only).

**Request:**
```json
{
  "companyName": "ABC Corporation",
  "legalName": "ABC Corporation Inc.",
  "taxId": "123-456-789",
  "currency": "PHP",
  "address": {
    "street1": "123 Main St",
    "street2": "Suite 100",
    "city": "Manila",
    "state": "Metro Manila",
    "postalCode": "1000",
    "country": "PH"
  },
  "fiscalYearStartMonth": 1,
  "fiscalYearStartDay": 1
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "companyName": "ABC Corporation",
    "status": "pending",
    "createdAt": "2025-12-13T10:30:00Z"
  }
}
```

### GET /companies/{id}
Get company details.

### PUT /companies/{id}
Update company.

### PUT /companies/{id}/settings
Update company settings.

**Request:**
```json
{
  "requireApprovalForNegativeEquity": true,
  "transactionApprovalThreshold": 10000.00,
  "allowBackdatedTransactions": true,
  "maxBackdateDays": 30,
  "autoPostTransactions": false
}
```

### POST /companies/{id}/activate
Activate company (System Admin only).

### POST /companies/{id}/deactivate
Deactivate company (System Admin only).

---

## Account Endpoints

### GET /accounts
List accounts for current company.

**Query Parameters:**
- `type`: asset|liability|equity|revenue|expense
- `status`: active|inactive
- `search`: string (searches code and name)
- `page`, `limit`

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "accountCode": "1000",
      "accountName": "Cash",
      "accountType": "asset",
      "normalBalance": "debit",
      "currentBalance": 15000.00,
      "isActive": true,
      "isSystemAccount": true,
      "parentAccountId": null
    }
  ]
}
```

### POST /accounts
Create account.

**Request:**
```json
{
  "accountCode": "1050",
  "accountName": "Petty Cash",
  "parentAccountId": "uuid|null",
  "openingBalance": 500.00
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "accountCode": "1050",
    "accountName": "Petty Cash",
    "accountType": "asset",
    "normalBalance": "debit",
    "currentBalance": 500.00,
    "isActive": true
  }
}
```

### GET /accounts/{id}
Get account details with balance history.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "accountCode": "1000",
    "accountName": "Cash",
    "accountType": "asset",
    "normalBalance": "debit",
    "currentBalance": 15000.00,
    "openingBalance": 10000.00,
    "totalDebits": 25000.00,
    "totalCredits": 20000.00,
    "transactionCount": 45,
    "lastTransactionAt": "2025-12-12T15:30:00Z",
    "isActive": true,
    "isSystemAccount": true,
    "children": []
  }
}
```

### PUT /accounts/{id}
Update account (name only, code cannot change).

**Request:**
```json
{
  "accountName": "Cash on Hand"
}
```

### POST /accounts/{id}/deactivate
Deactivate account (requires zero balance).

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "isActive": false,
    "deactivatedAt": "2025-12-13T10:30:00Z"
  }
}
```

**Error (422):**
```json
{
  "success": false,
  "error": {
    "code": "ACCOUNT_HAS_BALANCE",
    "message": "Cannot deactivate account with non-zero balance",
    "details": {
      "currentBalance": 5000.00
    }
  }
}
```

---

## Transaction Endpoints

### GET /transactions
List transactions.

**Query Parameters:**
- `status`: pending|posted|voided
- `dateFrom`: YYYY-MM-DD
- `dateTo`: YYYY-MM-DD
- `accountId`: uuid (filter by account)
- `minAmount`: decimal
- `maxAmount`: decimal
- `search`: string (description search)
- `page`, `limit`

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "transactionNumber": "TXN-000123",
      "transactionDate": "2025-12-12",
      "description": "Office supplies purchase",
      "totalAmount": 500.00,
      "status": "posted",
      "lineCount": 2,
      "createdBy": {
        "id": "uuid",
        "username": "johndoe"
      },
      "postedAt": "2025-12-12T10:45:00Z"
    }
  ]
}
```

### POST /transactions
Create transaction.

**Request:**
```json
{
  "transactionDate": "2025-12-12",
  "description": "Office supplies purchase",
  "lines": [
    {
      "accountId": "uuid",
      "lineType": "debit",
      "amount": 500.00
    },
    {
      "accountId": "uuid",
      "lineType": "credit",
      "amount": 500.00
    }
  ]
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "transactionNumber": "TXN-000124",
    "transactionDate": "2025-12-12",
    "description": "Office supplies purchase",
    "totalAmount": 500.00,
    "status": "pending",
    "requiresApproval": false,
    "lines": [
      {
        "id": "uuid",
        "accountId": "uuid",
        "accountCode": "5200",
        "accountName": "Office Supplies",
        "lineType": "debit",
        "amount": 500.00
      },
      {
        "id": "uuid",
        "accountId": "uuid",
        "accountCode": "1000",
        "accountName": "Cash",
        "lineType": "credit",
        "amount": 500.00
      }
    ]
  }
}
```

**Error (422) - Unbalanced:**
```json
{
  "success": false,
  "error": {
    "code": "TRANSACTION_UNBALANCED",
    "message": "Transaction debits must equal credits",
    "details": {
      "totalDebits": 500.00,
      "totalCredits": 400.00,
      "difference": 100.00
    }
  }
}
```

### GET /transactions/{id}
Get transaction details.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "transactionNumber": "TXN-000124",
    "transactionDate": "2025-12-12",
    "description": "Office supplies purchase",
    "totalAmount": 500.00,
    "status": "posted",
    "requiresApproval": false,
    "lines": [ ... ],
    "createdBy": {
      "id": "uuid",
      "username": "johndoe"
    },
    "createdAt": "2025-12-12T10:30:00Z",
    "postedBy": {
      "id": "uuid",
      "username": "johndoe"
    },
    "postedAt": "2025-12-12T10:45:00Z",
    "balanceChanges": [
      {
        "accountId": "uuid",
        "accountName": "Office Supplies",
        "previousBalance": 1000.00,
        "newBalance": 1500.00,
        "change": 500.00
      },
      {
        "accountId": "uuid",
        "accountName": "Cash",
        "previousBalance": 15500.00,
        "newBalance": 15000.00,
        "change": -500.00
      }
    ]
  }
}
```

### PUT /transactions/{id}
Update pending transaction.

**Error (422) - Already Posted:**
```json
{
  "success": false,
  "error": {
    "code": "TRANSACTION_ALREADY_POSTED",
    "message": "Posted transactions cannot be modified"
  }
}
```

### POST /transactions/{id}/post
Post pending transaction.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "status": "posted",
    "postedAt": "2025-12-13T10:30:00Z",
    "balanceChanges": [ ... ]
  }
}
```

**Response (202) - Requires Approval:**
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
      "status": "pending"
    }
  }
}
```

### POST /transactions/{id}/void
Void posted transaction (Admin only).

**Request:**
```json
{
  "reason": "Duplicate entry"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "status": "voided",
    "voidedAt": "2025-12-13T10:30:00Z",
    "voidReason": "Duplicate entry",
    "balanceRestorations": [ ... ]
  }
}
```

### DELETE /transactions/{id}
Delete pending transaction.

**Response (204):** No content

---

## Approval Endpoints

### GET /approvals
List pending approvals (Admin only).

**Query Parameters:**
- `status`: pending|approved|rejected|expired
- `type`: negative_equity|high_value|backdated
- `priority`: 1-5
- `page`, `limit`

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "type": "negative_equity",
      "entityType": "Transaction",
      "entityId": "uuid",
      "reason": {
        "type": "negative_equity",
        "description": "Transaction would result in negative Owner's Capital",
        "details": {
          "accountName": "Owner's Capital",
          "projectedBalance": -500.00
        }
      },
      "amount": 5500.00,
      "priority": 2,
      "status": "pending",
      "requestedBy": {
        "id": "uuid",
        "username": "johndoe"
      },
      "requestedAt": "2025-12-12T10:30:00Z",
      "expiresAt": "2025-12-14T10:30:00Z"
    }
  ]
}
```

### GET /approvals/{id}
Get approval details.

### POST /approvals/{id}/approve
Approve request (Admin only).

**Request:**
```json
{
  "notes": "Approved for year-end adjustment"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "status": "approved",
    "reviewedBy": {
      "id": "uuid",
      "username": "admin"
    },
    "reviewedAt": "2025-12-13T10:30:00Z",
    "notes": "Approved for year-end adjustment"
  }
}
```

### POST /approvals/{id}/reject
Reject request (Admin only).

**Request:**
```json
{
  "reason": "Insufficient justification"
}
```

### POST /approvals/{id}/cancel
Cancel own approval request.

---

## Report Endpoints

### GET /reports/balance-sheet
Generate balance sheet.

**Query Parameters:**
- `asOfDate`: YYYY-MM-DD (default: today)
- `format`: json|pdf|excel|csv (default: json)

**Response (200):**
```json
{
  "success": true,
  "data": {
    "reportType": "balance_sheet",
    "asOfDate": "2025-12-31",
    "generatedAt": "2025-12-13T10:30:00Z",
    "company": {
      "id": "uuid",
      "name": "ABC Corporation"
    },
    "assets": {
      "current": [
        {
          "accountCode": "1000",
          "accountName": "Cash",
          "balance": 15000.00
        }
      ],
      "fixed": [ ... ],
      "totalCurrent": 50000.00,
      "totalFixed": 25000.00,
      "total": 75000.00
    },
    "liabilities": {
      "current": [ ... ],
      "longTerm": [ ... ],
      "totalCurrent": 10000.00,
      "totalLongTerm": 5000.00,
      "total": 15000.00
    },
    "equity": {
      "accounts": [ ... ],
      "retainedEarnings": 10000.00,
      "total": 60000.00
    },
    "isBalanced": true
  }
}
```

### GET /reports/income-statement
Generate income statement.

**Query Parameters:**
- `periodStart`: YYYY-MM-DD
- `periodEnd`: YYYY-MM-DD
- `format`: json|pdf|excel|csv

### GET /reports/trial-balance
Generate trial balance.

**Query Parameters:**
- `asOfDate`: YYYY-MM-DD
- `format`: json|pdf|excel|csv

### GET /reports/general-ledger
Generate general ledger report.

**Query Parameters:**
- `accountId`: uuid (optional, specific account)
- `periodStart`: YYYY-MM-DD
- `periodEnd`: YYYY-MM-DD
- `format`: json|pdf|excel|csv

---

## Audit Trail Endpoints

### GET /audit-logs
Search audit logs (Admin only).

**Query Parameters:**
- `entityType`: User|Company|Account|Transaction
- `entityId`: uuid
- `activityType`: string
- `userId`: uuid (actor filter)
- `dateFrom`: YYYY-MM-DD
- `dateTo`: YYYY-MM-DD
- `severity`: info|warning|critical|security
- `page`, `limit`

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "activityType": "transaction_posted",
      "entityType": "Transaction",
      "entityId": "uuid",
      "action": "posted",
      "actor": {
        "id": "uuid",
        "username": "johndoe",
        "type": "user"
      },
      "changes": [
        {
          "field": "status",
          "previousValue": "pending",
          "newValue": "posted"
        }
      ],
      "context": {
        "ipAddress": "192.168.1.1",
        "userAgent": "Mozilla/5.0..."
      },
      "occurredAt": "2025-12-12T10:45:00Z"
    }
  ]
}
```

### GET /audit-logs/entity/{type}/{id}
Get audit history for specific entity.

### GET /audit-logs/export
Export audit logs.

**Query Parameters:**
- `dateFrom`: YYYY-MM-DD (required)
- `dateTo`: YYYY-MM-DD (required)
- `format`: csv|json

---

## Dashboard Endpoints

### GET /dashboard/summary
Get dashboard summary for current company.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "balanceSummary": {
      "totalAssets": 75000.00,
      "totalLiabilities": 15000.00,
      "totalEquity": 60000.00,
      "netIncome": 5000.00
    },
    "recentTransactions": [ ... ],
    "pendingApprovals": 3,
    "alerts": [
      {
        "type": "low_cash",
        "message": "Cash balance below threshold"
      }
    ]
  }
}
```

### GET /dashboard/account-balances
Get all account balances.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "assets": [ ... ],
    "liabilities": [ ... ],
    "equity": [ ... ],
    "revenue": [ ... ],
    "expenses": [ ... ]
  }
}
```

---

## Pagination

All list endpoints support pagination:

**Request:**
```
GET /transactions?page=2&limit=20
```

**Response Meta:**
```json
{
  "meta": {
    "page": 2,
    "limit": 20,
    "total": 245,
    "totalPages": 13,
    "hasNext": true,
    "hasPrevious": true
  }
}
```

---

## Rate Limiting

| Endpoint Category | Limit |
|-------------------|-------|
| Authentication | 10/min |
| Read endpoints | 100/min |
| Write endpoints | 30/min |
| Report generation | 10/min |
| Export endpoints | 5/min |

Headers:
```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1702468800
```

---

## Versioning

API version in URL: `/v1/`, `/v2/`

Deprecation header for old versions:
```http
X-API-Deprecated: true
X-API-Sunset: 2026-01-01
```
