# Approval Workflow - Domain Model

> **Master Architect Reference**: This document provides the complete domain specification for implementing the Approval Workflow bounded context.

## Aggregate: Approval

**Aggregate Root:** Approval entity

### Entities

#### Approval
```php
class Approval {
    private ApprovalId $approvalId;
    private CompanyId $companyId;
    private ApprovalType $approvalType;
    private string $entityType;           // "Transaction", etc.
    private string $entityId;             // UUID of entity needing approval
    private ApprovalStatus $status;
    private ApprovalReason $reason;
    private UserId $requestedBy;
    private DateTime $requestedAt;
    private ?UserId $reviewedBy;
    private ?DateTime $reviewedAt;
    private ?string $reviewNotes;
    private Money $amount;                // For threshold-based approvals
    private int $priority;                // 1-5, 1 = highest
    private ?DateTime $expiresAt;         // Auto-reject after this time
}
```

#### ApprovalHistory
```php
class ApprovalHistory {
    private ApprovalHistoryId $historyId;
    private ApprovalId $approvalId;
    private ApprovalStatus $previousStatus;
    private ApprovalStatus $newStatus;
    private UserId $changedBy;
    private string $changeReason;
    private DateTime $changedAt;
}
```

#### ApprovalRule
```php
class ApprovalRule {
    private ApprovalRuleId $ruleId;
    private CompanyId $companyId;
    private string $name;
    private ApprovalTrigger $trigger;
    private array $conditions;            // ApprovalCondition[]
    private array $approvers;             // UserId[] or Role[]
    private bool $requireAllApprovers;    // AND vs OR
    private int $priority;
    private bool $isActive;
}
```

#### ApprovalCondition
```php
class ApprovalCondition {
    private string $field;                // e.g., "amount", "accountType"
    private string $operator;             // "gt", "lt", "eq", "in"
    private mixed $value;
    private bool $isRequired;
}
```

### Value Objects

#### ApprovalId
```php
final class ApprovalId {
    private string $value;  // UUID v4
}
```

#### ApprovalType
```php
enum ApprovalType: string {
    case TRANSACTION = 'transaction';
    case NEGATIVE_EQUITY = 'negative_equity';
    case HIGH_VALUE = 'high_value';
    case USER_REGISTRATION = 'user_registration';
    case ACCOUNT_DEACTIVATION = 'account_deactivation';
    case VOID_TRANSACTION = 'void_transaction';
    case BACKDATED_TRANSACTION = 'backdated_transaction';

    public function getDefaultPriority(): int;
    public function getDefaultExpirationHours(): int;
}
```

#### ApprovalStatus
```php
enum ApprovalStatus: string {
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';

    public function isFinal(): bool;
    public function canTransitionTo(ApprovalStatus $newStatus): bool;
}
```

#### ApprovalReason
```php
final class ApprovalReason {
    private ApprovalType $type;
    private string $description;
    private array $details;

    public static function negativeEquity(
        string $accountName,
        Money $projectedBalance
    ): self;

    public static function highValue(
        Money $amount,
        Money $threshold
    ): self;

    public static function backdated(
        DateTime $transactionDate,
        int $daysBack
    ): self;
}
```

#### ApprovalTrigger
```php
enum ApprovalTrigger: string {
    case AMOUNT_EXCEEDS = 'amount_exceeds';
    case NEGATIVE_BALANCE = 'negative_balance';
    case BACKDATED = 'backdated';
    case ACCOUNT_TYPE = 'account_type';
    case USER_ROLE = 'user_role';
    case MANUAL = 'manual';
}
```

---

## Domain Services

### ApprovalRequestService
```php
interface ApprovalRequestService {
    /**
     * Create approval request
     */
    public function requestApproval(
        ApprovalType $type,
        string $entityType,
        string $entityId,
        ApprovalReason $reason,
        UserId $requestedBy,
        Money $amount
    ): Approval;

    /**
     * Check if entity requires approval
     */
    public function requiresApproval(
        string $entityType,
        string $entityId,
        array $context
    ): ApprovalRequirement;
}
```

### ApprovalReviewService
```php
interface ApprovalReviewService {
    /**
     * Approve request
     */
    public function approve(
        ApprovalId $approvalId,
        UserId $approver,
        ?string $notes
    ): void;

    /**
     * Reject request
     */
    public function reject(
        ApprovalId $approvalId,
        UserId $reviewer,
        string $reason
    ): void;

    /**
     * Cancel request (by requester)
     */
    public function cancel(
        ApprovalId $approvalId,
        UserId $requester,
        string $reason
    ): void;
}
```

### ApprovalAuthorizationService
```php
interface ApprovalAuthorizationService {
    /**
     * Check if user can approve
     */
    public function canApprove(
        UserId $userId,
        Approval $approval
    ): bool;

    /**
     * Get eligible approvers
     */
    public function getEligibleApprovers(Approval $approval): array;
}
```

### ApprovalExpirationService
```php
interface ApprovalExpirationService {
    /**
     * Process expired approvals (scheduler)
     */
    public function processExpiredApprovals(): int;

    /**
     * Set expiration on approval
     */
    public function setExpiration(
        ApprovalId $approvalId,
        DateTime $expiresAt
    ): void;
}
```

---

## Repository Interface

```php
interface ApprovalRepositoryInterface {
    public function save(Approval $approval): void;

    public function findById(ApprovalId $id): ?Approval;

    public function findByEntity(
        string $entityType,
        string $entityId
    ): ?Approval;

    public function findPendingByCompany(CompanyId $companyId): array;

    public function findPendingForApprover(UserId $approverId): array;

    public function findByStatus(
        CompanyId $companyId,
        ApprovalStatus $status
    ): array;

    public function findExpired(): array;

    public function countPendingByCompany(CompanyId $companyId): int;
}

interface ApprovalRuleRepositoryInterface {
    public function save(ApprovalRule $rule): void;

    public function findById(ApprovalRuleId $id): ?ApprovalRule;

    public function findByCompany(CompanyId $companyId): array;

    public function findActiveByTrigger(
        CompanyId $companyId,
        ApprovalTrigger $trigger
    ): array;

    public function findMatchingRules(
        CompanyId $companyId,
        array $context
    ): array;
}

interface ApprovalHistoryRepositoryInterface {
    public function save(ApprovalHistory $history): void;

    public function findByApproval(ApprovalId $approvalId): array;
}
```

---

## Domain Events

### ApprovalRequested
```json
{
  "eventId": "uuid",
  "eventType": "ApprovalRequested",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "approvalId": "uuid",
    "companyId": "uuid",
    "approvalType": "negative_equity",
    "entityType": "Transaction",
    "entityId": "uuid",
    "reason": {
      "type": "negative_equity",
      "description": "Transaction would result in negative Owner's Capital balance",
      "details": {
        "accountName": "Owner's Capital",
        "currentBalance": "5000.00",
        "projectedBalance": "-500.00"
      }
    },
    "amount": "5500.00",
    "requestedBy": "uuid",
    "priority": 2,
    "expiresAt": "2025-12-14T10:30:00Z"
  }
}
```

### ApprovalGranted
```json
{
  "eventId": "uuid",
  "eventType": "ApprovalGranted",
  "occurredAt": "2025-12-12T11:00:00Z",
  "aggregateId": "uuid",
  "payload": {
    "approvalId": "uuid",
    "entityType": "Transaction",
    "entityId": "uuid",
    "approvedBy": "uuid",
    "notes": "Approved for end-of-year adjustment"
  }
}
```

### ApprovalDenied
```json
{
  "eventId": "uuid",
  "eventType": "ApprovalDenied",
  "occurredAt": "2025-12-12T11:00:00Z",
  "aggregateId": "uuid",
  "payload": {
    "approvalId": "uuid",
    "entityType": "Transaction",
    "entityId": "uuid",
    "deniedBy": "uuid",
    "reason": "Insufficient justification for negative equity"
  }
}
```

### ApprovalExpired
```json
{
  "eventId": "uuid",
  "eventType": "ApprovalExpired",
  "occurredAt": "2025-12-14T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "approvalId": "uuid",
    "entityType": "Transaction",
    "entityId": "uuid",
    "originalExpiresAt": "2025-12-14T10:30:00Z"
  }
}
```

### ApprovalCancelled
```json
{
  "eventId": "uuid",
  "eventType": "ApprovalCancelled",
  "occurredAt": "2025-12-12T10:45:00Z",
  "aggregateId": "uuid",
  "payload": {
    "approvalId": "uuid",
    "entityType": "Transaction",
    "entityId": "uuid",
    "cancelledBy": "uuid",
    "reason": "Transaction was edited, resubmitting"
  }
}
```

---

## Business Rules

### BR-AW-001: Only Admins Can Approve
- Only users with ADMIN role can approve/reject
- Tenant users cannot approve
- Self-approval is prohibited

### BR-AW-002: Self-Approval Prohibition
- User who requested approval cannot approve their own request
- System enforces different approver than requester

### BR-AW-003: Status Transitions
```
PENDING → APPROVED (admin approval)
PENDING → REJECTED (admin rejection)
PENDING → CANCELLED (requester cancellation)
PENDING → EXPIRED (time expiration)
APPROVED → (no transitions, final)
REJECTED → (no transitions, final)
EXPIRED → (no transitions, final)
CANCELLED → (no transitions, final)
```

### BR-AW-004: Rejection Requires Reason
- Rejections MUST include a reason
- Minimum 10 characters for reason
- Reason is stored and audited

### BR-AW-005: Approval Effects
- Approved transactions are automatically posted
- Approved user registrations are activated
- Denied requests remain in their pre-approval state

### BR-AW-006: Expiration Policy
- Default expiration: 48 hours
- High priority: 24 hours
- Can be configured per company
- Expired approvals are auto-rejected

### BR-AW-007: Priority Levels
```
1 = Critical (4 hours default expiration)
2 = High (24 hours)
3 = Normal (48 hours)
4 = Low (72 hours)
5 = Lowest (1 week)
```

### BR-AW-008: Notification Requirements
- Notify all eligible approvers on request
- Notify requester on approval/rejection
- Send reminders at 50% and 90% of expiration time

### BR-AW-009: One Active Approval Per Entity
- Only one pending approval per entity at a time
- Must cancel or complete existing before new request
- Prevents confusion and duplicate approvals

---

## Approval Triggers

### Trigger 1: Negative Equity Balance
**When:** Transaction would result in negative equity account balance
**Details:**
```php
if ($projectedBalance < 0 && $accountType === AccountType::EQUITY) {
    return ApprovalRequirement::required(
        ApprovalType::NEGATIVE_EQUITY,
        ApprovalReason::negativeEquity($accountName, $projectedBalance)
    );
}
```

### Trigger 2: High-Value Transaction
**When:** Transaction amount exceeds company threshold
**Details:**
```php
$threshold = $company->getSettings()->getTransactionApprovalThreshold();
if ($threshold > 0 && $transactionAmount > $threshold) {
    return ApprovalRequirement::required(
        ApprovalType::HIGH_VALUE,
        ApprovalReason::highValue($transactionAmount, $threshold)
    );
}
```

### Trigger 3: Backdated Transaction
**When:** Transaction date is in the past beyond allowed days
**Details:**
```php
$maxDays = $company->getSettings()->getMaxBackdateDays();
$daysBack = $today->diffInDays($transactionDate);
if ($daysBack > $maxDays) {
    return ApprovalRequirement::required(
        ApprovalType::BACKDATED_TRANSACTION,
        ApprovalReason::backdated($transactionDate, $daysBack)
    );
}
```

### Trigger 4: Void Transaction
**When:** Admin wants to void a posted transaction
**Details:**
- All void requests require approval
- Cannot self-approve voids
- Audit trail critical for voids

---

## Algorithms

### Algorithm: Check Approval Requirement
```
FUNCTION requiresApproval(entityType, entityId, context):
    company = getCompany(context.companyId)
    settings = company.getSettings()

    # Check negative equity
    IF entityType == "Transaction":
        transaction = getTransaction(entityId)
        FOR EACH line IN transaction.lines:
            account = getAccount(line.accountId)
            IF account.type == EQUITY:
                projected = calculateProjectedBalance(account, line)
                IF projected < 0 AND settings.requireApprovalForNegativeEquity:
                    RETURN ApprovalRequirement(
                        required: TRUE,
                        type: NEGATIVE_EQUITY,
                        reason: "Would result in negative {account.name}"
                    )

    # Check high value threshold
    IF settings.transactionApprovalThreshold > 0:
        IF context.amount > settings.transactionApprovalThreshold:
            RETURN ApprovalRequirement(
                required: TRUE,
                type: HIGH_VALUE,
                reason: "Amount exceeds approval threshold"
            )

    # Check backdating
    IF context.transactionDate < TODAY():
        daysBack = daysBetween(context.transactionDate, TODAY())
        IF daysBack > settings.maxBackdateDays:
            RETURN ApprovalRequirement(
                required: TRUE,
                type: BACKDATED_TRANSACTION,
                reason: "Transaction backdated {daysBack} days"
            )

    # Check custom rules
    rules = ruleRepository.findMatchingRules(company.id, context)
    IF rules.isNotEmpty():
        RETURN ApprovalRequirement(
            required: TRUE,
            type: rules.first().type,
            reason: rules.first().description
        )

    RETURN ApprovalRequirement(required: FALSE)
END FUNCTION
```

### Algorithm: Process Approval
```
FUNCTION approve(approvalId, approverId, notes):
    approval = repository.findById(approvalId)

    IF approval IS NULL:
        THROW "Approval not found"

    IF approval.status != PENDING:
        THROW "Approval is not pending"

    IF approval.requestedBy == approverId:
        THROW "Cannot approve your own request"

    IF NOT authService.canApprove(approverId, approval):
        THROW "User not authorized to approve"

    # Update approval
    approval.setStatus(APPROVED)
    approval.setReviewedBy(approverId)
    approval.setReviewedAt(NOW())
    approval.setReviewNotes(notes)

    # Save
    repository.save(approval)

    # Record history
    history = new ApprovalHistory(
        approvalId: approval.id,
        previousStatus: PENDING,
        newStatus: APPROVED,
        changedBy: approverId,
        changeReason: notes ?? "Approved"
    )
    historyRepository.save(history)

    # Publish event
    publishEvent(new ApprovalGranted(approval))

    # Trigger downstream action
    IF approval.entityType == "Transaction":
        # This event will be consumed by Transaction Processing
        # to actually post the transaction
        publishEvent(new TransactionApprovalGranted(approval.entityId))

END FUNCTION
```

### Algorithm: Process Expiration
```
FUNCTION processExpiredApprovals():
    expiredApprovals = repository.findExpired()
    processedCount = 0

    FOR EACH approval IN expiredApprovals:
        IF approval.status == PENDING:
            approval.setStatus(EXPIRED)
            repository.save(approval)

            # Record history
            history = new ApprovalHistory(
                approvalId: approval.id,
                previousStatus: PENDING,
                newStatus: EXPIRED,
                changedBy: SYSTEM,
                changeReason: "Expired after {expirationHours} hours"
            )
            historyRepository.save(history)

            # Publish event
            publishEvent(new ApprovalExpired(approval))

            # Notify requester
            notificationService.notify(
                approval.requestedBy,
                "Your approval request has expired"
            )

            processedCount++

    RETURN processedCount
END FUNCTION
```

---

## Use Cases

### UC-AW-001: Request Approval
**Actor:** System (triggered by domain event)
**Trigger:** TransactionApprovalRequired or NegativeBalanceDetected
**Flow:**
1. Receive triggering event
2. Determine approval type and reason
3. Calculate priority and expiration
4. Create Approval entity
5. Save to repository
6. Notify eligible approvers
7. Publish ApprovalRequested event

### UC-AW-002: Approve Request
**Actor:** Admin
**Preconditions:** Admin authenticated, approval pending
**Flow:**
1. Verify admin can approve
2. Verify not self-approval
3. Update approval status to APPROVED
4. Record in history
5. Publish ApprovalGranted event
6. Notify requester

### UC-AW-003: Reject Request
**Actor:** Admin
**Preconditions:** Admin authenticated, approval pending
**Flow:**
1. Verify admin can reject
2. Require rejection reason
3. Update approval status to REJECTED
4. Record in history
5. Publish ApprovalDenied event
6. Notify requester

### UC-AW-004: View Pending Approvals
**Actor:** Admin
**Flow:**
1. Query pending approvals for company
2. Sort by priority (highest first)
3. Include entity details
4. Return list

### UC-AW-005: Cancel Request
**Actor:** Requester
**Preconditions:** Approval still pending
**Flow:**
1. Verify requester owns approval
2. Update status to CANCELLED
3. Record in history
4. Publish ApprovalCancelled event

### UC-AW-006: Process Expirations
**Actor:** Scheduler (cron job)
**Trigger:** Every 15 minutes
**Flow:**
1. Query expired pending approvals
2. Update each to EXPIRED
3. Record in history
4. Publish ApprovalExpired events
5. Notify requesters

---

## Integration Points

### Consumes Events:
- `TransactionApprovalRequired` → Create approval request
- `NegativeBalanceDetected` → Create approval request
- `TransactionEdited` → Cancel existing approval if pending

### Publishes Events:
- `ApprovalRequested` → Notify approvers
- `ApprovalGranted` → Triggers entity action (e.g., post transaction)
- `ApprovalDenied` → Notify requester
- `ApprovalExpired` → Notify requester
- `ApprovalCancelled` → Clean up

### Dependencies:
- Identity & Access (for approver authorization)
- Transaction Processing (for transaction posting on approval)

---

## Database Schema (Reference)

```sql
-- Main approvals table
CREATE TABLE approvals (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id),
    approval_type VARCHAR(50) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id UUID NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    reason_type VARCHAR(50) NOT NULL,
    reason_description TEXT NOT NULL,
    reason_details JSONB,
    amount DECIMAL(15,2),
    priority INT NOT NULL DEFAULT 3,
    requested_by UUID NOT NULL REFERENCES users(id),
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_by UUID REFERENCES users(id),
    reviewed_at TIMESTAMP,
    review_notes TEXT,
    expires_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT valid_status CHECK (
        status IN ('pending', 'approved', 'rejected', 'expired', 'cancelled')
    ),
    CONSTRAINT valid_priority CHECK (priority BETWEEN 1 AND 5),
    CONSTRAINT different_reviewer CHECK (
        reviewed_by IS NULL OR reviewed_by != requested_by
    )
);

-- Approval history
CREATE TABLE approval_history (
    id UUID PRIMARY KEY,
    approval_id UUID NOT NULL REFERENCES approvals(id),
    previous_status VARCHAR(20) NOT NULL,
    new_status VARCHAR(20) NOT NULL,
    changed_by UUID NOT NULL,
    change_reason TEXT,
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Approval rules
CREATE TABLE approval_rules (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id),
    name VARCHAR(255) NOT NULL,
    trigger_type VARCHAR(50) NOT NULL,
    conditions JSONB NOT NULL,
    approvers JSONB NOT NULL,  -- User IDs or roles
    require_all_approvers BOOLEAN DEFAULT FALSE,
    priority INT NOT NULL DEFAULT 100,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP
);

-- Indexes
CREATE INDEX idx_approvals_company ON approvals(company_id);
CREATE INDEX idx_approvals_status ON approvals(status);
CREATE INDEX idx_approvals_entity ON approvals(entity_type, entity_id);
CREATE INDEX idx_approvals_pending ON approvals(company_id, status) WHERE status = 'pending';
CREATE INDEX idx_approvals_expires ON approvals(expires_at) WHERE status = 'pending';
CREATE INDEX idx_approval_history ON approval_history(approval_id);
CREATE INDEX idx_approval_rules_company ON approval_rules(company_id);
CREATE INDEX idx_approval_rules_trigger ON approval_rules(trigger_type);
```

---

## Notifications

### Email Templates

#### Approval Requested
```
Subject: [ACTION REQUIRED] Approval needed for {entityType}

Hi {approverName},

A new approval request requires your attention:

Type: {approvalType}
Requested by: {requesterName}
Amount: {amount}
Reason: {reasonDescription}

Details:
{reasonDetails}

This request expires in {hoursRemaining} hours.

[Approve] [Reject] [View Details]
```

#### Approval Granted
```
Subject: Your approval request was APPROVED

Hi {requesterName},

Your approval request has been approved by {approverName}.

Type: {approvalType}
Entity: {entityType} {entityId}

Notes: {reviewNotes}

The action will be processed automatically.
```

#### Approval Denied
```
Subject: Your approval request was DENIED

Hi {requesterName},

Your approval request has been denied by {reviewerName}.

Type: {approvalType}
Entity: {entityType} {entityId}

Reason for denial:
{denialReason}

You may create a new request after addressing the concerns.
```
