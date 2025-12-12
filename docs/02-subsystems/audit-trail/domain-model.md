# Audit Trail - Domain Model

> **Master Architect Reference**: This document provides the complete domain specification for implementing the Audit Trail bounded context.

## Aggregate: ActivityLog

**Aggregate Root:** ActivityLog entity (append-only)

### Entities

#### ActivityLog
```php
class ActivityLog {
    private ActivityId $activityId;
    private CompanyId $companyId;
    private Actor $actor;
    private ActivityType $activityType;
    private string $entityType;          // e.g., "Transaction", "Account", "User"
    private string $entityId;            // UUID of affected entity
    private string $action;              // e.g., "created", "updated", "deleted"
    private array $previousState;        // JSON snapshot before change
    private array $newState;             // JSON snapshot after change
    private array $changes;              // Diff of changed fields
    private RequestContext $context;     // IP, user agent, etc.
    private DateTime $occurredAt;

    // Immutable - no setters
}
```

#### Actor
```php
class Actor {
    private ?UserId $userId;             // Null for system actions
    private string $actorType;           // "user", "system", "scheduler"
    private string $actorName;           // Display name for audit display
    private ?string $impersonatedBy;     // If admin acting as user
}
```

#### RequestContext
```php
class RequestContext {
    private ?string $ipAddress;
    private ?string $userAgent;
    private ?string $sessionId;
    private ?string $requestId;          // Correlation ID
    private ?string $endpoint;           // API endpoint called
    private ?string $httpMethod;
    private DateTime $timestamp;
}
```

#### AuditSnapshot
```php
class AuditSnapshot {
    private SnapshotId $snapshotId;
    private CompanyId $companyId;
    private string $snapshotType;        // "daily", "monthly", "year_end"
    private DateTime $snapshotDate;
    private array $balanceSummary;       // Account balances at snapshot time
    private string $checksum;            // Hash for integrity verification
    private DateTime $createdAt;
}
```

### Value Objects

#### ActivityId
```php
final class ActivityId {
    private string $value;  // UUID v4
}
```

#### ActivityType
```php
enum ActivityType: string {
    // Authentication
    case LOGIN = 'login';
    case LOGOUT = 'logout';
    case LOGIN_FAILED = 'login_failed';
    case PASSWORD_CHANGED = 'password_changed';

    // User Management
    case USER_CREATED = 'user_created';
    case USER_UPDATED = 'user_updated';
    case USER_DEACTIVATED = 'user_deactivated';
    case ROLE_CHANGED = 'role_changed';

    // Company Management
    case COMPANY_CREATED = 'company_created';
    case COMPANY_UPDATED = 'company_updated';
    case COMPANY_DEACTIVATED = 'company_deactivated';
    case SETTINGS_CHANGED = 'settings_changed';

    // Chart of Accounts
    case ACCOUNT_CREATED = 'account_created';
    case ACCOUNT_UPDATED = 'account_updated';
    case ACCOUNT_DEACTIVATED = 'account_deactivated';

    // Transactions
    case TRANSACTION_CREATED = 'transaction_created';
    case TRANSACTION_POSTED = 'transaction_posted';
    case TRANSACTION_VOIDED = 'transaction_voided';
    case TRANSACTION_EDITED = 'transaction_edited';

    // Approvals
    case APPROVAL_REQUESTED = 'approval_requested';
    case APPROVAL_GRANTED = 'approval_granted';
    case APPROVAL_DENIED = 'approval_denied';

    // Reports
    case REPORT_GENERATED = 'report_generated';
    case REPORT_EXPORTED = 'report_exported';

    // System
    case SYSTEM_ERROR = 'system_error';
    case DATA_EXPORTED = 'data_exported';
    case BACKUP_CREATED = 'backup_created';

    public function getCategory(): string;
    public function getSeverity(): AuditSeverity;
    public function requiresAdminNotification(): bool;
}
```

#### AuditSeverity
```php
enum AuditSeverity: string {
    case INFO = 'info';           // Normal operations
    case WARNING = 'warning';     // Unusual but allowed
    case CRITICAL = 'critical';   // Requires attention
    case SECURITY = 'security';   // Security-related
}
```

#### ChangeRecord
```php
final class ChangeRecord {
    private string $field;
    private mixed $previousValue;
    private mixed $newValue;
    private string $changeType;  // "added", "modified", "removed"
}
```

---

## Domain Services

### AuditLogService
```php
interface AuditLogService {
    /**
     * Log an activity (primary entry point)
     */
    public function log(
        ActivityType $type,
        string $entityType,
        string $entityId,
        string $action,
        array $previousState,
        array $newState,
        Actor $actor,
        RequestContext $context
    ): ActivityLog;

    /**
     * Log from domain event
     */
    public function logFromEvent(DomainEvent $event): ActivityLog;

    /**
     * Calculate field-level changes
     */
    public function calculateChanges(
        array $previousState,
        array $newState
    ): array;
}
```

### AuditQueryService
```php
interface AuditQueryService {
    /**
     * Search audit logs with filters
     */
    public function search(AuditSearchCriteria $criteria): AuditSearchResult;

    /**
     * Get activity history for entity
     */
    public function getEntityHistory(
        string $entityType,
        string $entityId
    ): array;

    /**
     * Get user activity
     */
    public function getUserActivity(
        UserId $userId,
        DateTime $from,
        DateTime $to
    ): array;
}
```

### AuditSnapshotService
```php
interface AuditSnapshotService {
    /**
     * Create point-in-time snapshot
     */
    public function createSnapshot(
        CompanyId $companyId,
        string $snapshotType
    ): AuditSnapshot;

    /**
     * Verify snapshot integrity
     */
    public function verifySnapshot(SnapshotId $snapshotId): VerificationResult;
}
```

### AuditExportService
```php
interface AuditExportService {
    /**
     * Export audit logs for compliance
     */
    public function export(
        CompanyId $companyId,
        DateTime $from,
        DateTime $to,
        ExportFormat $format
    ): ExportResult;
}
```

---

## Repository Interface

```php
interface ActivityLogRepositoryInterface {
    /**
     * Save activity log (append-only)
     */
    public function save(ActivityLog $log): void;

    /**
     * Find by ID
     */
    public function findById(ActivityId $id): ?ActivityLog;

    /**
     * Search with criteria
     */
    public function search(AuditSearchCriteria $criteria): array;

    /**
     * Get by entity
     */
    public function findByEntity(
        string $entityType,
        string $entityId
    ): array;

    /**
     * Get by user
     */
    public function findByUser(
        UserId $userId,
        DateTime $from,
        DateTime $to
    ): array;

    /**
     * Get by company and date range
     */
    public function findByCompanyAndDateRange(
        CompanyId $companyId,
        DateTime $from,
        DateTime $to
    ): array;

    /**
     * Get recent activities
     */
    public function getRecent(
        CompanyId $companyId,
        int $limit = 100
    ): array;

    /**
     * Count by type for analytics
     */
    public function countByType(
        CompanyId $companyId,
        DateTime $from,
        DateTime $to
    ): array;

    // NOTE: No update or delete methods - audit logs are immutable
}

interface AuditSnapshotRepositoryInterface {
    public function save(AuditSnapshot $snapshot): void;

    public function findById(SnapshotId $id): ?AuditSnapshot;

    public function findByCompanyAndType(
        CompanyId $companyId,
        string $snapshotType
    ): array;

    public function getLatest(
        CompanyId $companyId,
        string $snapshotType
    ): ?AuditSnapshot;
}
```

---

## Domain Events

**Note:** Audit Trail is primarily an event CONSUMER. It listens to all domain events from other contexts and logs them.

### Events Consumed (from other contexts):

```php
// All events from all other bounded contexts
- UserRegistered
- UserAuthenticated
- UserDeactivated
- CompanyCreated
- CompanyActivated
- CompanyDeactivated
- AccountCreated
- AccountDeactivated
- TransactionCreated
- TransactionPosted
- TransactionVoided
- ApprovalRequested
- ApprovalGranted
- ApprovalDenied
- AccountBalanceChanged
- ReportGenerated
// ... ALL domain events
```

### Events Published (by Audit Trail):

### AuditLogCreated
```json
{
  "eventId": "uuid",
  "eventType": "AuditLogCreated",
  "occurredAt": "2025-12-12T10:30:00Z",
  "payload": {
    "activityId": "uuid",
    "companyId": "uuid",
    "activityType": "transaction_created",
    "entityType": "Transaction",
    "entityId": "uuid",
    "actorId": "uuid",
    "severity": "info"
  }
}
```

### SecurityAlertTriggered
```json
{
  "eventId": "uuid",
  "eventType": "SecurityAlertTriggered",
  "occurredAt": "2025-12-12T10:30:00Z",
  "payload": {
    "alertType": "multiple_failed_logins",
    "companyId": "uuid",
    "userId": "uuid",
    "ipAddress": "192.168.1.1",
    "details": "5 failed login attempts in 5 minutes"
  }
}
```

### AuditSnapshotCreated
```json
{
  "eventId": "uuid",
  "eventType": "AuditSnapshotCreated",
  "occurredAt": "2025-12-12T10:30:00Z",
  "payload": {
    "snapshotId": "uuid",
    "companyId": "uuid",
    "snapshotType": "monthly",
    "snapshotDate": "2025-12-01",
    "checksum": "sha256:abc123..."
  }
}
```

---

## Business Rules

### BR-AT-001: Immutability
- Audit logs MUST be append-only
- No update or delete operations allowed
- Cannot modify existing log entries
- Physical deletion only via data retention policy

### BR-AT-002: Complete Capture
- ALL domain events MUST be logged
- ALL user actions MUST be logged
- ALL system operations MUST be logged
- No gaps in audit trail

### BR-AT-003: Actor Identification
- Every log entry MUST have an actor
- System actions use "SYSTEM" as actor
- Impersonation MUST be recorded

### BR-AT-004: Context Capture
- IP address captured for all user actions
- Session ID linked for correlation
- Request ID for distributed tracing
- Timestamp with timezone

### BR-AT-005: State Snapshots
- Previous and new state captured for changes
- Field-level changes computed and stored
- Sensitive data redacted (passwords, etc.)

### BR-AT-006: Retention Policy
- Minimum retention: 7 years (configurable)
- Year-end snapshots: Permanent
- Export before deletion

### BR-AT-007: Access Control
- Only admins can view full audit logs
- Users can view their own activity
- Sensitive fields may be masked for non-admins

### BR-AT-008: Security Events
- Failed logins: Alert after 3 attempts
- Multiple sessions: Alert on concurrent logins
- Admin actions: Always logged with detail
- Data exports: Require explicit logging

### BR-AT-009: Checksum Verification
- Snapshots include checksums
- Periodic verification of integrity
- Alert on checksum mismatch

---

## Event Mapping

Map domain events to audit activity types:

| Domain Event | Activity Type | Severity |
|--------------|---------------|----------|
| UserRegistered | user_created | info |
| UserAuthenticated | login | info |
| UserDeactivated | user_deactivated | warning |
| CompanyCreated | company_created | info |
| TransactionCreated | transaction_created | info |
| TransactionPosted | transaction_posted | info |
| TransactionVoided | transaction_voided | warning |
| ApprovalDenied | approval_denied | warning |
| NegativeBalanceDetected | transaction_created | warning |
| LoginFailed (3+) | login_failed | security |

---

## Algorithms

### Algorithm: Log Domain Event
```
FUNCTION logDomainEvent(event):
    # Extract common fields
    eventType = event.getType()
    aggregateId = event.getAggregateId()
    occurredAt = event.getOccurredAt()
    payload = event.getPayload()

    # Determine activity type
    activityType = mapEventToActivityType(eventType)

    # Determine actor
    actor = extractActor(payload)

    # Get request context (from context holder)
    context = RequestContextHolder.getCurrent()

    # Get entity states
    previousState = getPreviousState(aggregateId) OR {}
    newState = payload

    # Calculate changes
    changes = calculateChanges(previousState, newState)

    # Create log entry
    log = new ActivityLog(
        activityId: ActivityId.generate(),
        companyId: extractCompanyId(payload),
        actor: actor,
        activityType: activityType,
        entityType: getEntityType(eventType),
        entityId: aggregateId,
        action: getAction(eventType),
        previousState: redactSensitive(previousState),
        newState: redactSensitive(newState),
        changes: changes,
        context: context,
        occurredAt: occurredAt
    )

    # Save (append-only)
    repository.save(log)

    # Check for security alerts
    checkSecurityAlerts(log)

    # Publish audit event
    publishEvent(new AuditLogCreated(log))
END FUNCTION
```

### Algorithm: Calculate Changes
```
FUNCTION calculateChanges(previousState, newState):
    changes = []

    # Get all unique keys
    allKeys = UNION(previousState.keys(), newState.keys())

    FOR EACH key IN allKeys:
        prevValue = previousState.get(key)
        newValue = newState.get(key)

        IF prevValue IS NULL AND newValue IS NOT NULL:
            changes.append(new ChangeRecord(
                field: key,
                previousValue: null,
                newValue: newValue,
                changeType: "added"
            ))

        ELSE IF prevValue IS NOT NULL AND newValue IS NULL:
            changes.append(new ChangeRecord(
                field: key,
                previousValue: prevValue,
                newValue: null,
                changeType: "removed"
            ))

        ELSE IF prevValue != newValue:
            changes.append(new ChangeRecord(
                field: key,
                previousValue: prevValue,
                newValue: newValue,
                changeType: "modified"
            ))

    RETURN changes
END FUNCTION
```

### Algorithm: Security Alert Detection
```
FUNCTION checkSecurityAlerts(log):
    IF log.activityType == LOGIN_FAILED:
        # Count recent failures
        recentFailures = countRecentFailures(
            log.actor.userId,
            last5Minutes()
        )

        IF recentFailures >= 3:
            publishEvent(new SecurityAlertTriggered(
                alertType: "multiple_failed_logins",
                userId: log.actor.userId,
                ipAddress: log.context.ipAddress,
                details: "{recentFailures} failed attempts in 5 minutes"
            ))

    IF log.activityType == LOGIN:
        # Check for concurrent sessions
        activeSessions = getActiveSessions(log.actor.userId)

        IF activeSessions.count() > 1:
            publishEvent(new SecurityAlertTriggered(
                alertType: "concurrent_sessions",
                userId: log.actor.userId,
                details: "User logged in from multiple locations"
            ))
END FUNCTION
```

### Algorithm: Create Snapshot
```
FUNCTION createSnapshot(companyId, snapshotType):
    # Get current balances
    balances = ledgerRepository.getAllBalances(companyId)

    balanceSummary = {}
    FOR EACH balance IN balances:
        balanceSummary[balance.accountId] = {
            accountCode: balance.accountCode,
            accountName: balance.accountName,
            balance: balance.currentBalance
        }

    # Calculate checksum
    dataString = JSON.stringify(balanceSummary, sorted=true)
    checksum = SHA256(dataString)

    snapshot = new AuditSnapshot(
        snapshotId: SnapshotId.generate(),
        companyId: companyId,
        snapshotType: snapshotType,
        snapshotDate: TODAY(),
        balanceSummary: balanceSummary,
        checksum: "sha256:" + checksum,
        createdAt: NOW()
    )

    repository.save(snapshot)
    publishEvent(new AuditSnapshotCreated(snapshot))

    RETURN snapshot
END FUNCTION
```

---

## Use Cases

### UC-AT-001: Log Activity (Automatic)
**Actor:** System (event listener)
**Flow:**
1. Receive domain event
2. Map to activity type
3. Extract actor and context
4. Calculate state changes
5. Create log entry
6. Save to repository
7. Check for alerts

### UC-AT-002: Search Audit Logs
**Actor:** Admin
**Preconditions:** Admin authenticated
**Flow:**
1. Build search criteria
2. Execute search query
3. Apply access controls
4. Return filtered results
5. Log the search itself

### UC-AT-003: View Entity History
**Actor:** User, Admin
**Preconditions:** User has access to entity
**Flow:**
1. Receive entity type and ID
2. Query all logs for entity
3. Sort chronologically
4. Return timeline view

### UC-AT-004: Create Periodic Snapshot
**Actor:** Scheduler
**Trigger:** End of day/month/year
**Flow:**
1. Trigger snapshot creation
2. Collect all balance data
3. Calculate checksum
4. Store snapshot
5. Publish event

### UC-AT-005: Export Audit Logs
**Actor:** Admin
**Preconditions:** Compliance export required
**Flow:**
1. Specify date range
2. Select export format
3. Generate export file
4. Log the export action
5. Return download link

---

## Integration Points

### Consumes Events:
- **ALL** domain events from **ALL** bounded contexts
- Acts as universal event sink

### Publishes Events:
- `AuditLogCreated` → For external monitoring
- `SecurityAlertTriggered` → For security response
- `AuditSnapshotCreated` → For compliance verification

### Dependencies:
- None (independent sink context)
- Read-only access to other contexts for state retrieval

---

## Database Schema (Reference)

```sql
-- Main audit log table (append-only)
CREATE TABLE activity_logs (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL,
    actor_user_id UUID,
    actor_type VARCHAR(20) NOT NULL,
    actor_name VARCHAR(255) NOT NULL,
    impersonated_by UUID,
    activity_type VARCHAR(50) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id VARCHAR(100) NOT NULL,
    action VARCHAR(50) NOT NULL,
    previous_state JSONB,
    new_state JSONB,
    changes JSONB,
    ip_address INET,
    user_agent TEXT,
    session_id VARCHAR(100),
    request_id VARCHAR(100),
    endpoint VARCHAR(255),
    http_method VARCHAR(10),
    severity VARCHAR(20) NOT NULL DEFAULT 'info',
    occurred_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- No UPDATE or DELETE constraints
    -- This is enforced at application level
    CONSTRAINT valid_severity CHECK (
        severity IN ('info', 'warning', 'critical', 'security')
    )
);

-- Indexes for common queries
CREATE INDEX idx_activity_logs_company ON activity_logs(company_id);
CREATE INDEX idx_activity_logs_actor ON activity_logs(actor_user_id);
CREATE INDEX idx_activity_logs_entity ON activity_logs(entity_type, entity_id);
CREATE INDEX idx_activity_logs_type ON activity_logs(activity_type);
CREATE INDEX idx_activity_logs_occurred ON activity_logs(occurred_at);
CREATE INDEX idx_activity_logs_severity ON activity_logs(severity);
CREATE INDEX idx_activity_logs_session ON activity_logs(session_id);

-- Audit snapshots table
CREATE TABLE audit_snapshots (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL,
    snapshot_type VARCHAR(20) NOT NULL,
    snapshot_date DATE NOT NULL,
    balance_summary JSONB NOT NULL,
    checksum VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT valid_snapshot_type CHECK (
        snapshot_type IN ('daily', 'monthly', 'quarterly', 'yearly')
    )
);

CREATE INDEX idx_audit_snapshots_company ON audit_snapshots(company_id);
CREATE INDEX idx_audit_snapshots_type ON audit_snapshots(snapshot_type);
CREATE INDEX idx_audit_snapshots_date ON audit_snapshots(snapshot_date);

-- Security alerts table
CREATE TABLE security_alerts (
    id UUID PRIMARY KEY,
    company_id UUID,
    user_id UUID,
    alert_type VARCHAR(50) NOT NULL,
    ip_address INET,
    details TEXT,
    acknowledged_at TIMESTAMP,
    acknowledged_by UUID,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_security_alerts_company ON security_alerts(company_id);
CREATE INDEX idx_security_alerts_user ON security_alerts(user_id);
CREATE INDEX idx_security_alerts_unack ON security_alerts(acknowledged_at) WHERE acknowledged_at IS NULL;
```

---

## Sensitive Data Handling

### Fields to Redact
- `password` → "[REDACTED]"
- `passwordHash` → "[REDACTED]"
- `token` → "[REDACTED]"
- `secret` → "[REDACTED]"
- `creditCard` → "****1234" (last 4 only)
- `ssn` → "***-**-1234" (last 4 only)

### Redaction Function
```php
function redactSensitive(array $data): array {
    $sensitiveFields = ['password', 'passwordHash', 'token', 'secret'];

    foreach ($data as $key => $value) {
        if (in_array(strtolower($key), $sensitiveFields)) {
            $data[$key] = '[REDACTED]';
        } elseif (is_array($value)) {
            $data[$key] = redactSensitive($value);
        }
    }

    return $data;
}
```

---

## Compliance Considerations

### SOX Compliance (Sarbanes-Oxley)
- Complete audit trail of financial transactions
- Immutable records
- Access controls on audit logs
- Regular snapshots

### GDPR Compliance
- Track data access (who viewed what)
- Support right to access (export user's data)
- Retention limits (but minimum 7 years for financial)

### General Best Practices
- Timestamp with timezone
- Actor identification
- IP address logging
- Request correlation
- Secure storage
- Regular integrity verification
