# Identity & Access Management - Domain Model

> **Master Architect Reference**: This document provides the complete domain specification for implementing the Identity & Access Management bounded context.

## Aggregate: User

**Aggregate Root:** User entity

### Entities

#### User
```php
class User {
    private UserId $userId;
    private ?CompanyId $companyId;         // Null for system admins
    private string $username;
    private Email $email;
    private string $passwordHash;          // bcrypt, cost 12
    private Role $role;
    private RegistrationStatus $registrationStatus;
    private bool $isActive;
    private ?DateTime $lastLoginAt;
    private ?string $lastLoginIp;
    private ?DateTime $passwordChangedAt;
    private DateTime $createdAt;
    private ?DateTime $deactivatedAt;

    public function authenticate(string $password): bool;
    public function changePassword(string $newPassword): void;
    public function approve(UserId $approvedBy): void;
    public function decline(UserId $declinedBy, string $reason): void;
    public function deactivate(UserId $deactivatedBy, string $reason): void;
    public function recordLogin(string $ipAddress): void;
}
```

#### Session
```php
class Session {
    private SessionId $sessionId;
    private UserId $userId;
    private string $tokenHash;
    private string $ipAddress;
    private ?string $userAgent;
    private DateTime $lastActivityAt;
    private DateTime $expiresAt;
    private DateTime $createdAt;

    public function isValid(): bool;
    public function refresh(): void;
    public function invalidate(): void;
}
```

### Value Objects

#### UserId
```php
final class UserId {
    private string $value;  // UUID v4

    public static function generate(): self;
    public static function fromString(string $value): self;
    public function value(): string;
    public function equals(UserId $other): bool;
}
```

#### Email
```php
final class Email {
    private string $value;

    public static function fromString(string $email): self;
    public function value(): string;
    public function equals(Email $other): bool;
    public function getDomain(): string;

    // Validation: RFC 5322 compliant
    // Normalization: lowercase
}
```

#### Role
```php
enum Role: string {
    case ADMIN = 'admin';
    case TENANT = 'tenant';

    public function canApproveUsers(): bool;
    public function canApproveTransactions(): bool;
    public function canAccessAllCompanies(): bool;
    public function canManageSettings(): bool;
}
```

#### RegistrationStatus
```php
enum RegistrationStatus: string {
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case DECLINED = 'declined';

    public function canAuthenticate(): bool;
    public function canTransitionTo(RegistrationStatus $newStatus): bool;
}
```

#### SessionId
```php
final class SessionId {
    private string $value;  // UUID v4
}
```

---

## Domain Services

### AuthenticationService
```php
interface AuthenticationService {
    /**
     * Authenticate user with credentials
     */
    public function authenticate(
        string $username,
        string $password,
        string $ipAddress
    ): AuthenticationResult;

    /**
     * Validate session token
     */
    public function validateToken(string $token): ?User;

    /**
     * Logout user (invalidate session)
     */
    public function logout(SessionId $sessionId): void;

    /**
     * Logout all sessions for user
     */
    public function logoutAllSessions(UserId $userId): void;
}
```

### PasswordService
```php
interface PasswordService {
    /**
     * Hash password with bcrypt
     */
    public function hash(string $plainPassword): string;

    /**
     * Verify password against hash
     */
    public function verify(string $plainPassword, string $hash): bool;

    /**
     * Validate password strength
     */
    public function validateStrength(string $password): ValidationResult;

    /**
     * Generate secure random password
     */
    public function generateSecurePassword(): string;
}
```

### AuthorizationService
```php
interface AuthorizationService {
    /**
     * Check if user can access resource
     */
    public function canAccess(User $user, string $resource, string $action): bool;

    /**
     * Check if user has specific role
     */
    public function hasRole(User $user, Role $role): bool;

    /**
     * Check if user belongs to company
     */
    public function belongsToCompany(User $user, CompanyId $companyId): bool;
}
```

### RegistrationService
```php
interface RegistrationService {
    /**
     * Register new user
     */
    public function register(RegistrationRequest $request): User;

    /**
     * Approve pending registration
     */
    public function approve(UserId $userId, UserId $approvedBy): void;

    /**
     * Decline pending registration
     */
    public function decline(
        UserId $userId,
        UserId $declinedBy,
        string $reason
    ): void;
}
```

---

## Repository Interface

```php
interface UserRepositoryInterface {
    public function save(User $user): void;

    public function findById(UserId $id): ?User;

    public function findByUsername(string $username): ?User;

    public function findByEmail(Email $email): ?User;

    public function findByCompany(CompanyId $companyId): array;

    public function findPendingRegistrations(): array;

    public function existsByUsername(string $username): bool;

    public function existsByEmail(Email $email): bool;

    public function delete(UserId $id): void;
}

interface SessionRepositoryInterface {
    public function save(Session $session): void;

    public function findById(SessionId $id): ?Session;

    public function findByToken(string $tokenHash): ?Session;

    public function findActiveByUser(UserId $userId): array;

    public function deleteExpired(): int;

    public function deleteByUser(UserId $userId): void;
}
```

---

## Domain Events

### UserRegistered
```json
{
  "eventId": "uuid",
  "eventType": "UserRegistered",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "userId": "uuid",
    "username": "string",
    "email": "string",
    "role": "admin|tenant",
    "companyId": "uuid|null",
    "registrationStatus": "pending"
  }
}
```

### UserAuthenticated
```json
{
  "eventId": "uuid",
  "eventType": "UserAuthenticated",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "userId": "uuid",
    "ipAddress": "string",
    "sessionId": "uuid",
    "userAgent": "string"
  }
}
```

### UserDeactivated
```json
{
  "eventId": "uuid",
  "eventType": "UserDeactivated",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "userId": "uuid",
    "reason": "string",
    "deactivatedBy": "uuid"
  }
}
```

### RegistrationApproved
```json
{
  "eventId": "uuid",
  "eventType": "RegistrationApproved",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "userId": "uuid",
    "approvedBy": "uuid"
  }
}
```

### RegistrationDeclined
```json
{
  "eventId": "uuid",
  "eventType": "RegistrationDeclined",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "userId": "uuid",
    "declinedBy": "uuid",
    "reason": "string"
  }
}
```

### PasswordChanged
```json
{
  "eventId": "uuid",
  "eventType": "PasswordChanged",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "userId": "uuid",
    "changedBy": "uuid"
  }
}
```

### LoginFailed
```json
{
  "eventId": "uuid",
  "eventType": "LoginFailed",
  "occurredAt": "2025-12-12T10:30:00Z",
  "payload": {
    "username": "string",
    "ipAddress": "string",
    "reason": "invalid_password|user_not_found|user_inactive|user_pending"
  }
}
```

---

## Business Rules

### BR-IAM-001: Username Uniqueness
- Username MUST be unique across entire system
- Case-insensitive comparison

### BR-IAM-002: Email Uniqueness
- Email MUST be unique across entire system
- Normalized to lowercase before storage

### BR-IAM-003: Password Requirements
- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one digit
- Special characters recommended but not required

### BR-IAM-004: Password Storage
- Passwords MUST be hashed with bcrypt
- Cost factor: 12
- Never store or log plain passwords

### BR-IAM-005: Registration Flow
- New users start with PENDING status
- Admin must approve before user can authenticate
- Declined users cannot re-register with same email/username

### BR-IAM-006: Admin Users
- Admin users have no company association (companyId = null)
- Admins can access all companies
- Admins can approve/decline registrations

### BR-IAM-007: Tenant Users
- Tenant users MUST have company association
- Tenants can only access their own company data
- Tenants cannot approve other users

### BR-IAM-008: Session Management
- Sessions expire after 24 hours of inactivity
- Users can have multiple active sessions
- Login from new device notifies user (future)

### BR-IAM-009: Deactivation
- Deactivated users cannot authenticate
- Deactivation cascades from company deactivation
- Admin can reactivate users

### BR-IAM-010: Security Events
- Log all authentication attempts
- Alert on 3+ failed login attempts
- Log password changes

---

## Algorithms

### Algorithm: Password Hashing
```
FUNCTION hashPassword(plainPassword):
    # Validate strength
    IF length(plainPassword) < 8:
        THROW "Password must be at least 8 characters"

    IF NOT containsUppercase(plainPassword):
        THROW "Password must contain uppercase letter"

    IF NOT containsLowercase(plainPassword):
        THROW "Password must contain lowercase letter"

    IF NOT containsDigit(plainPassword):
        THROW "Password must contain digit"

    # Hash with bcrypt, cost 12
    hash = bcrypt(plainPassword, cost=12)
    RETURN hash
END FUNCTION
```

### Algorithm: Authentication
```
FUNCTION authenticate(username, password, ipAddress):
    user = userRepository.findByUsername(username)

    IF user IS NULL:
        logEvent(LoginFailed(username, ipAddress, "user_not_found"))
        RETURN AuthenticationResult.failure("Invalid credentials")

    IF NOT user.isActive:
        logEvent(LoginFailed(username, ipAddress, "user_inactive"))
        RETURN AuthenticationResult.failure("Account is inactive")

    IF user.registrationStatus != APPROVED:
        logEvent(LoginFailed(username, ipAddress, "user_pending"))
        RETURN AuthenticationResult.failure("Account pending approval")

    IF NOT passwordService.verify(password, user.passwordHash):
        logEvent(LoginFailed(username, ipAddress, "invalid_password"))
        checkSecurityAlerts(username, ipAddress)
        RETURN AuthenticationResult.failure("Invalid credentials")

    # Success
    user.recordLogin(ipAddress)
    userRepository.save(user)

    session = createSession(user, ipAddress)
    sessionRepository.save(session)

    token = generateJWT(user, session)

    publishEvent(UserAuthenticated(user.id, ipAddress, session.id))

    RETURN AuthenticationResult.success(token, user)
END FUNCTION
```

### Algorithm: Authorization Check
```
FUNCTION canAccess(user, resource, action):
    # Admins have full access
    IF user.role == ADMIN:
        RETURN TRUE

    # Tenants can only access their company
    IF user.role == TENANT:
        resourceCompanyId = extractCompanyId(resource)

        IF resourceCompanyId IS NULL:
            RETURN FALSE  # Resource not company-scoped

        RETURN user.companyId.equals(resourceCompanyId)

    RETURN FALSE
END FUNCTION
```

---

## Use Cases

### UC-IAM-001: Register User
**Actor:** Public / Admin
**Preconditions:** None for self-registration, Admin for admin-created users
**Flow:**
1. Validate username uniqueness
2. Validate email uniqueness
3. Validate password strength
4. Hash password
5. Create User entity with PENDING status
6. Save user
7. Publish UserRegistered event
8. Notify admins of pending registration

### UC-IAM-002: Login
**Actor:** User
**Preconditions:** User approved and active
**Flow:**
1. Find user by username
2. Verify user is active and approved
3. Verify password
4. Create session
5. Generate JWT token
6. Record login time and IP
7. Publish UserAuthenticated event
8. Return token

### UC-IAM-003: Approve Registration
**Actor:** Admin
**Preconditions:** Admin authenticated, user pending
**Flow:**
1. Verify admin has approval rights
2. Find pending user
3. Update registration status to APPROVED
4. Publish RegistrationApproved event
5. Notify user

### UC-IAM-004: Change Password
**Actor:** User
**Preconditions:** User authenticated
**Flow:**
1. Verify current password
2. Validate new password strength
3. Hash new password
4. Update password hash
5. Publish PasswordChanged event
6. Optionally invalidate other sessions

### UC-IAM-005: Logout
**Actor:** User
**Preconditions:** User authenticated
**Flow:**
1. Find session by token
2. Invalidate session
3. Delete session from repository
4. Return success

---

## Integration Points

### Consumes Events:
- `CompanyDeactivated` → Deactivate all company users

### Publishes Events:
- `UserRegistered` → Company Management, Audit Trail
- `UserAuthenticated` → Audit Trail
- `UserDeactivated` → Audit Trail
- `RegistrationApproved` → Audit Trail
- `RegistrationDeclined` → Audit Trail
- `PasswordChanged` → Audit Trail
- `LoginFailed` → Audit Trail, Security Monitoring

### Dependencies:
- Company Management (for company validation)

---

## Database Schema (Reference)

```sql
CREATE TABLE users (
    id CHAR(36) PRIMARY KEY,
    company_id CHAR(36) REFERENCES companies(id),
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'tenant') NOT NULL DEFAULT 'tenant',
    registration_status ENUM('pending', 'approved', 'declined') NOT NULL DEFAULT 'pending',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45) NULL,
    password_changed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deactivated_at TIMESTAMP NULL,

    UNIQUE KEY uk_users_username (username),
    UNIQUE KEY uk_users_email (email),
    INDEX idx_users_company (company_id),
    INDEX idx_users_status (registration_status),
    INDEX idx_users_active (is_active)
);

CREATE TABLE sessions (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    last_activity_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_sessions_user (user_id),
    INDEX idx_sessions_token (token_hash),
    INDEX idx_sessions_expires (expires_at),

    CONSTRAINT fk_sessions_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
);
```

---

## Security Considerations

### Password Security
- Never log passwords
- Use timing-safe comparison
- Rate limit login attempts
- Lock account after repeated failures

### Token Security
- JWT with short expiration (1 hour)
- Refresh tokens for extended sessions
- Token rotation on sensitive operations
- Secure storage on client side

### Session Security
- Bind session to IP address (optional)
- Detect session hijacking attempts
- Automatic logout on password change
- Session listing for users
