# Identity & Access Management Subsystem

## Overview

The Identity & Access Management (IAM) bounded context handles all user authentication, authorization, and access control for the Accounting System.

## Purpose

This subsystem is responsible for:

- **User Registration**: Creating new user accounts with approval workflow
- **Authentication**: Verifying user credentials and issuing JWT tokens
- **Authorization**: Controlling access to resources based on roles
- **Session Management**: Tracking active user sessions
- **Password Management**: Secure password hashing and validation
- **Role-Based Access Control (RBAC)**: Admin vs Tenant permissions

## Core Entities

### User
The main entity representing a system user with:
- Unique username and email
- Secure password (bcrypt hashed)
- Role (admin or tenant)
- Company association (null for admins)
- Registration status (pending, approved, declined)
- Activity tracking (last login, IP address)

### Session
Represents an authenticated user session with:
- JWT token reference
- IP address and user agent
- Expiration tracking
- Last activity timestamp

## Key Value Objects

- **UserId**: UUID-based user identifier
- **Email**: RFC 5322 compliant email address
- **Role**: Enum (admin, tenant) with permission checks
- **RegistrationStatus**: Enum tracking approval state
- **SessionId**: UUID-based session identifier

## Business Rules

1. **BR-IAM-001**: Username must be unique (case-insensitive)
2. **BR-IAM-002**: Email must be unique (normalized to lowercase)
3. **BR-IAM-003**: Password requires 8+ chars, uppercase, lowercase, digit
4. **BR-IAM-004**: Passwords hashed with bcrypt (cost 12)
5. **BR-IAM-005**: New users require admin approval
6. **BR-IAM-006**: Admins have no company association
7. **BR-IAM-007**: Tenants must belong to a company
8. **BR-IAM-008**: Sessions expire after 24 hours of inactivity
9. **BR-IAM-009**: Deactivated users cannot authenticate
10. **BR-IAM-010**: All authentication attempts are logged

## Use Cases

### Primary Use Cases

1. **UC-IAM-001: Register User**
   - Actor: Public/Admin
   - Creates pending user account
   - Notifies admins for approval

2. **UC-IAM-002: Login**
   - Actor: User
   - Validates credentials
   - Creates session and returns JWT

3. **UC-IAM-003: Approve Registration**
   - Actor: Admin
   - Approves pending user
   - User can now authenticate

4. **UC-IAM-004: Change Password**
   - Actor: User
   - Validates old password
   - Updates to new password

5. **UC-IAM-005: Logout**
   - Actor: User
   - Invalidates session
   - Clears authentication state

## Domain Events

- **UserRegistered**: New user account created
- **UserAuthenticated**: Successful login
- **UserDeactivated**: User account disabled
- **RegistrationApproved**: Admin approved user
- **RegistrationDeclined**: Admin declined user
- **PasswordChanged**: User changed password
- **LoginFailed**: Failed authentication attempt

## Integration Points

### Consumes Events
- `CompanyDeactivated` → Deactivate all company users

### Publishes Events
- All IAM events → Audit Trail subsystem
- User authentication events → Activity logging

### Dependencies
- **Company Management**: For validating company associations
- **Audit Trail**: For logging all security events

## API Endpoints

- `POST /api/v1/auth/register` - Register new user
- `POST /api/v1/auth/login` - Authenticate user
- `POST /api/v1/auth/logout` - End session
- `POST /api/v1/auth/change-password` - Update password
- `GET /api/v1/users` - List users (admin only)
- `POST /api/v1/users/:id/approve` - Approve user (admin)
- `POST /api/v1/users/:id/decline` - Decline user (admin)

## Security Considerations

### Password Security
- Never log plain passwords
- Use timing-safe comparison
- Rate limit login attempts
- Lock account after 5 failed attempts

### Token Security
- JWT with short expiration (1 hour)
- Secure token storage required
- Token rotation on sensitive operations
- Bind sessions to IP (optional)

### Session Security
- Track session activity
- Detect session hijacking
- Automatic logout on password change
- Clean up expired sessions

## Implementation Status

- [ ] Domain Model: Documented
- [ ] Value Objects: Not implemented
- [ ] Entities: Not implemented
- [ ] Services: Not implemented
- [ ] Repositories: Not implemented
- [ ] Use Cases: Not implemented
- [ ] API Endpoints: Not implemented
- [ ] Tests: Not implemented

## Documentation

- **Domain Model**: [domain-model.md](./domain-model.md)
- **Architecture**: [../../01-architecture/architecture-overview.md](../../01-architecture/architecture-overview.md)
- **API Specification**: [../../04-api/api-specification.md](../../04-api/api-specification.md)

## Related Subsystems

- [Company Management](../company-management/) - Company associations
- [Audit Trail](../audit-trail/) - Security event logging
- [Approval Workflow](../approval-workflow/) - Registration approvals

---

**Next Steps**: Begin TDD implementation following [Implementation Plan](../../plans/implementation-plan.md)
