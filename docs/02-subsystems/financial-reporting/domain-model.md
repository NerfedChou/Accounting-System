# Financial Reporting - Domain Model

> **Master Architect Reference**: This document provides the complete domain specification for implementing the Financial Reporting bounded context.

## Aggregate: Report

**Aggregate Root:** Report entity (generated on demand)

### Entities

#### BalanceSheet
```php
class BalanceSheet {
    private ReportId $reportId;
    private CompanyId $companyId;
    private ReportPeriod $period;
    private DateTime $generatedAt;

    // Asset Section
    private array $currentAssets;        // AccountBalance[]
    private array $fixedAssets;          // AccountBalance[]
    private Money $totalAssets;

    // Liability Section
    private array $currentLiabilities;   // AccountBalance[]
    private array $longTermLiabilities;  // AccountBalance[]
    private Money $totalLiabilities;

    // Equity Section
    private array $equityAccounts;       // AccountBalance[]
    private Money $retainedEarnings;
    private Money $totalEquity;

    // Validation
    private bool $isBalanced;
    private Money $difference;           // Should be 0
}
```

#### IncomeStatement
```php
class IncomeStatement {
    private ReportId $reportId;
    private CompanyId $companyId;
    private ReportPeriod $period;
    private DateTime $generatedAt;

    // Revenue Section
    private array $revenueAccounts;      // AccountBalance[]
    private Money $totalRevenue;

    // Expense Section
    private array $expenseAccounts;      // AccountBalance[]
    private Money $totalExpenses;

    // Calculated
    private Money $grossProfit;
    private Money $operatingIncome;
    private Money $netIncome;
}
```

#### TrialBalance
```php
class TrialBalance {
    private ReportId $reportId;
    private CompanyId $companyId;
    private ReportPeriod $period;
    private DateTime $generatedAt;

    private array $accountBalances;      // TrialBalanceEntry[]
    private Money $totalDebits;
    private Money $totalCredits;
    private bool $isBalanced;
}
```

#### TrialBalanceEntry
```php
class TrialBalanceEntry {
    private AccountId $accountId;
    private string $accountCode;
    private string $accountName;
    private AccountType $accountType;
    private Money $debitBalance;
    private Money $creditBalance;
}
```

#### GeneralLedgerReport
```php
class GeneralLedgerReport {
    private ReportId $reportId;
    private CompanyId $companyId;
    private ReportPeriod $period;
    private DateTime $generatedAt;

    private array $accounts;             // GeneralLedgerAccount[]
}
```

#### GeneralLedgerAccount
```php
class GeneralLedgerAccount {
    private AccountId $accountId;
    private string $accountCode;
    private string $accountName;
    private Money $openingBalance;
    private array $transactions;         // LedgerTransaction[]
    private Money $closingBalance;
}
```

#### LedgerTransaction
```php
class LedgerTransaction {
    private TransactionId $transactionId;
    private DateTime $transactionDate;
    private string $description;
    private string $reference;
    private Money $debitAmount;
    private Money $creditAmount;
    private Money $runningBalance;
}
```

### Value Objects

#### ReportId
```php
final class ReportId {
    private string $value;  // UUID v4
}
```

#### ReportPeriod
```php
final class ReportPeriod {
    private DateTime $startDate;
    private DateTime $endDate;
    private PeriodType $type;

    public static function month(int $year, int $month): self;
    public static function quarter(int $year, int $quarter): self;
    public static function year(int $year): self;
    public static function custom(DateTime $start, DateTime $end): self;

    public function contains(DateTime $date): bool;
    public function overlaps(ReportPeriod $other): bool;
    public function getDays(): int;
}
```

#### PeriodType
```php
enum PeriodType: string {
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case YEARLY = 'yearly';
    case CUSTOM = 'custom';
}
```

#### ReportFormat
```php
enum ReportFormat: string {
    case JSON = 'json';
    case PDF = 'pdf';
    case EXCEL = 'excel';
    case CSV = 'csv';
    case HTML = 'html';
}
```

---

## Domain Services

### BalanceSheetGenerator
```php
interface BalanceSheetGenerator {
    /**
     * Generate balance sheet for given period
     */
    public function generate(
        CompanyId $companyId,
        ReportPeriod $period
    ): BalanceSheet;

    /**
     * Compare two balance sheets
     */
    public function compare(
        BalanceSheet $current,
        BalanceSheet $previous
    ): BalanceSheetComparison;
}
```

### IncomeStatementGenerator
```php
interface IncomeStatementGenerator {
    /**
     * Generate income statement for given period
     */
    public function generate(
        CompanyId $companyId,
        ReportPeriod $period
    ): IncomeStatement;

    /**
     * Compare income statements
     */
    public function compare(
        IncomeStatement $current,
        IncomeStatement $previous
    ): IncomeStatementComparison;
}
```

### TrialBalanceGenerator
```php
interface TrialBalanceGenerator {
    /**
     * Generate trial balance as of date
     */
    public function generate(
        CompanyId $companyId,
        DateTime $asOfDate
    ): TrialBalance;
}
```

### ReportCacheService
```php
interface ReportCacheService {
    /**
     * Get cached report if still valid
     */
    public function get(
        string $reportType,
        CompanyId $companyId,
        ReportPeriod $period
    ): ?Report;

    /**
     * Cache generated report
     */
    public function cache(Report $report, int $ttlSeconds): void;

    /**
     * Invalidate cached reports for company
     */
    public function invalidate(CompanyId $companyId): void;

    /**
     * Invalidate specific report type
     */
    public function invalidateType(
        string $reportType,
        CompanyId $companyId
    ): void;
}
```

### ReportExporter
```php
interface ReportExporter {
    /**
     * Export report to specified format
     */
    public function export(
        Report $report,
        ReportFormat $format
    ): ExportResult;

    /**
     * Get supported formats
     */
    public function getSupportedFormats(): array;
}
```

---

## Repository Interface

```php
interface ReportRepositoryInterface {
    /**
     * Save generated report for history
     */
    public function save(Report $report): void;

    /**
     * Find report by ID
     */
    public function findById(ReportId $id): ?Report;

    /**
     * Find reports by company and type
     */
    public function findByCompanyAndType(
        CompanyId $companyId,
        string $reportType,
        int $limit = 10
    ): array;

    /**
     * Find reports for period
     */
    public function findByPeriod(
        CompanyId $companyId,
        ReportPeriod $period
    ): array;
}
```

---

## Domain Events

### ReportGenerated
```json
{
  "eventId": "uuid",
  "eventType": "ReportGenerated",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "reportId": "uuid",
    "reportType": "balance_sheet|income_statement|trial_balance",
    "companyId": "uuid",
    "periodStart": "2025-01-01",
    "periodEnd": "2025-12-31",
    "generatedBy": "uuid",
    "format": "json"
  }
}
```

### ReportCacheInvalidated
```json
{
  "eventId": "uuid",
  "eventType": "ReportCacheInvalidated",
  "occurredAt": "2025-12-12T10:30:00Z",
  "payload": {
    "companyId": "uuid",
    "reportTypes": ["balance_sheet", "income_statement"],
    "reason": "transaction_posted"
  }
}
```

### ReportExported
```json
{
  "eventId": "uuid",
  "eventType": "ReportExported",
  "occurredAt": "2025-12-12T10:30:00Z",
  "payload": {
    "reportId": "uuid",
    "format": "pdf",
    "exportedBy": "uuid",
    "fileSize": 12345,
    "filePath": "string"
  }
}
```

---

## Business Rules

### BR-FR-001: Balance Sheet Must Balance
- Assets MUST equal Liabilities + Equity
- If not balanced, report generation fails
- Unbalanced state indicates system error

### BR-FR-002: Period Boundaries
- Reports respect fiscal year boundaries
- Year-end reports include closing entries
- Comparative reports use same-length periods

### BR-FR-003: Real-Time vs Cached
- Trial Balance: Always real-time
- Balance Sheet: Cached with invalidation
- Income Statement: Cached with invalidation
- Cache invalidated on any balance change

### BR-FR-004: Retained Earnings Calculation
```
Retained Earnings =
    Opening Retained Earnings
    + Net Income (Revenue - Expenses)
    - Dividends/Drawings
```

### BR-FR-005: Account Classification
Balance Sheet classification:
- Current Assets: Accounts with code 1000-1499
- Fixed Assets: Accounts with code 1500-1999
- Current Liabilities: Accounts with code 2000-2499
- Long-term Liabilities: Accounts with code 2500-2999

### BR-FR-006: Net Income Calculation
```
Net Income = Total Revenue - Total Expenses
```

### BR-FR-007: Report Access Control
- Users can only view reports for their company
- Admins can view all company reports
- Reports contain sensitive financial data

---

## Report Templates

### Balance Sheet Template
```
                    COMPANY NAME
                    BALANCE SHEET
              As of [Report Date]

ASSETS
  Current Assets
    Cash                          $X,XXX.XX
    Accounts Receivable           $X,XXX.XX
    Inventory                     $X,XXX.XX
    Prepaid Expenses              $X,XXX.XX
    -----------------------------------
    Total Current Assets                      $XX,XXX.XX

  Fixed Assets
    Equipment                     $X,XXX.XX
    Less: Accumulated Depreciation $(X,XXX.XX)
    -----------------------------------
    Total Fixed Assets                        $XX,XXX.XX

TOTAL ASSETS                                  $XXX,XXX.XX
=========================================================

LIABILITIES
  Current Liabilities
    Accounts Payable              $X,XXX.XX
    Accrued Expenses              $X,XXX.XX
    -----------------------------------
    Total Current Liabilities                 $XX,XXX.XX

  Long-term Liabilities
    Notes Payable                 $X,XXX.XX
    -----------------------------------
    Total Long-term Liabilities               $XX,XXX.XX

TOTAL LIABILITIES                             $XX,XXX.XX

EQUITY
    Owner's Capital               $X,XXX.XX
    Retained Earnings             $X,XXX.XX
    -----------------------------------
TOTAL EQUITY                                  $XX,XXX.XX

TOTAL LIABILITIES AND EQUITY                  $XXX,XXX.XX
=========================================================
```

### Income Statement Template
```
                    COMPANY NAME
                  INCOME STATEMENT
        For the Period [Start Date] to [End Date]

REVENUE
  Sales Revenue                   $XX,XXX.XX
  Service Revenue                 $XX,XXX.XX
  Other Income                    $X,XXX.XX
  -------------------------------------------
TOTAL REVENUE                                 $XXX,XXX.XX

EXPENSES
  Cost of Goods Sold              $XX,XXX.XX
  Salaries Expense                $X,XXX.XX
  Rent Expense                    $X,XXX.XX
  Utilities Expense               $X,XXX.XX
  Depreciation Expense            $X,XXX.XX
  Other Expenses                  $X,XXX.XX
  -------------------------------------------
TOTAL EXPENSES                                $XX,XXX.XX

NET INCOME                                    $XX,XXX.XX
=========================================================
```

### Trial Balance Template
```
                    COMPANY NAME
                    TRIAL BALANCE
              As of [Report Date]

Account Code | Account Name          | Debit      | Credit
---------------------------------------------------------------------------
1000         | Cash                  | $X,XXX.XX  |
1100         | Accounts Receivable   | $X,XXX.XX  |
2000         | Accounts Payable      |            | $X,XXX.XX
3000         | Owner's Capital       |            | $X,XXX.XX
4000         | Sales Revenue         |            | $X,XXX.XX
5000         | Cost of Goods Sold    | $X,XXX.XX  |
---------------------------------------------------------------------------
             | TOTALS                | $XX,XXX.XX | $XX,XXX.XX
```

---

## Algorithms

### Algorithm: Generate Balance Sheet
```
FUNCTION generateBalanceSheet(companyId, period):
    # Get all account balances
    balances = ledgerRepository.getAllBalances(companyId)

    # Classify accounts
    currentAssets = []
    fixedAssets = []
    currentLiabilities = []
    longTermLiabilities = []
    equityAccounts = []

    FOR EACH balance IN balances:
        account = getAccount(balance.accountId)

        SWITCH account.type:
            CASE ASSET:
                IF account.code < 1500:
                    currentAssets.append(balance)
                ELSE:
                    fixedAssets.append(balance)

            CASE LIABILITY:
                IF account.code < 2500:
                    currentLiabilities.append(balance)
                ELSE:
                    longTermLiabilities.append(balance)

            CASE EQUITY:
                equityAccounts.append(balance)

    # Calculate totals
    totalCurrentAssets = SUM(currentAssets.balance)
    totalFixedAssets = SUM(fixedAssets.balance)
    totalAssets = totalCurrentAssets + totalFixedAssets

    totalCurrentLiabilities = SUM(currentLiabilities.balance)
    totalLongTermLiabilities = SUM(longTermLiabilities.balance)
    totalLiabilities = totalCurrentLiabilities + totalLongTermLiabilities

    # Calculate retained earnings
    incomeStatement = generateIncomeStatement(companyId, period)
    retainedEarnings = calculateRetainedEarnings(companyId, incomeStatement.netIncome)

    totalEquity = SUM(equityAccounts.balance) + retainedEarnings

    # Validate balance
    isBalanced = abs(totalAssets - (totalLiabilities + totalEquity)) < 0.01
    difference = totalAssets - (totalLiabilities + totalEquity)

    IF NOT isBalanced:
        LOG.error("Balance sheet not balanced: difference = {difference}")

    RETURN new BalanceSheet(
        companyId: companyId,
        period: period,
        currentAssets: currentAssets,
        fixedAssets: fixedAssets,
        totalAssets: totalAssets,
        currentLiabilities: currentLiabilities,
        longTermLiabilities: longTermLiabilities,
        totalLiabilities: totalLiabilities,
        equityAccounts: equityAccounts,
        retainedEarnings: retainedEarnings,
        totalEquity: totalEquity,
        isBalanced: isBalanced,
        difference: difference
    )
END FUNCTION
```

### Algorithm: Generate Income Statement
```
FUNCTION generateIncomeStatement(companyId, period):
    # Get revenue accounts
    revenueBalances = ledgerRepository.getBalancesByType(companyId, REVENUE)
    totalRevenue = SUM(revenueBalances.balance)

    # Get expense accounts
    expenseBalances = ledgerRepository.getBalancesByType(companyId, EXPENSE)
    totalExpenses = SUM(expenseBalances.balance)

    # Calculate net income
    netIncome = totalRevenue - totalExpenses

    RETURN new IncomeStatement(
        companyId: companyId,
        period: period,
        revenueAccounts: revenueBalances,
        totalRevenue: totalRevenue,
        expenseAccounts: expenseBalances,
        totalExpenses: totalExpenses,
        netIncome: netIncome
    )
END FUNCTION
```

### Algorithm: Generate Trial Balance
```
FUNCTION generateTrialBalance(companyId, asOfDate):
    balances = ledgerRepository.getAllBalances(companyId)
    entries = []
    totalDebits = 0
    totalCredits = 0

    FOR EACH balance IN balances:
        account = getAccount(balance.accountId)

        # Determine debit/credit column based on normal balance
        IF account.normalBalance == DEBIT:
            debitBalance = balance.currentBalance
            creditBalance = 0
            totalDebits += debitBalance
        ELSE:
            debitBalance = 0
            creditBalance = balance.currentBalance
            totalCredits += creditBalance

        entries.append(new TrialBalanceEntry(
            accountId: balance.accountId,
            accountCode: account.code,
            accountName: account.name,
            accountType: account.type,
            debitBalance: debitBalance,
            creditBalance: creditBalance
        ))

    # Sort by account code
    entries = SORT(entries, BY: accountCode)

    isBalanced = abs(totalDebits - totalCredits) < 0.01

    RETURN new TrialBalance(
        companyId: companyId,
        asOfDate: asOfDate,
        entries: entries,
        totalDebits: totalDebits,
        totalCredits: totalCredits,
        isBalanced: isBalanced
    )
END FUNCTION
```

---

## Use Cases

### UC-FR-001: Generate Balance Sheet
**Actor:** User, System
**Preconditions:** Company exists, user has access
**Flow:**
1. Check cache for existing report
2. If cached and valid, return cached
3. Fetch all account balances
4. Classify accounts by type
5. Calculate totals
6. Validate balance equation
7. Generate report
8. Cache report
9. Publish ReportGenerated event
10. Return report

### UC-FR-002: Generate Income Statement
**Actor:** User, System
**Preconditions:** Company exists, period specified
**Flow:**
1. Check cache for existing report
2. Fetch revenue and expense balances
3. Calculate net income
4. Generate report
5. Cache report
6. Return report

### UC-FR-003: Export Report
**Actor:** User
**Preconditions:** Report generated
**Flow:**
1. Receive export request with format
2. Generate report (or use cached)
3. Convert to requested format
4. Return file download

### UC-FR-004: Invalidate Cache
**Actor:** System (triggered by AccountBalanceChanged)
**Preconditions:** Balance changed
**Flow:**
1. Receive AccountBalanceChanged event
2. Invalidate all cached reports for company
3. Publish ReportCacheInvalidated event

---

## Integration Points

### Consumes Events:
- `AccountBalanceChanged` → Invalidate report cache
- `TransactionPosted` → Invalidate report cache
- `TransactionVoided` → Invalidate report cache

### Publishes Events:
- `ReportGenerated` → Audit trail
- `ReportCacheInvalidated` → System monitoring
- `ReportExported` → Audit trail

### Dependencies:
- Ledger & Posting (for account balances)
- Chart of Accounts (for account metadata)
- Company Management (for company details)

---

## Database Schema (Reference)

```sql
-- Report history for audit
CREATE TABLE generated_reports (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id),
    report_type VARCHAR(50) NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    report_data JSONB NOT NULL,
    is_balanced BOOLEAN,
    generated_by UUID REFERENCES users(id),
    generated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT valid_report_type CHECK (
        report_type IN ('balance_sheet', 'income_statement', 'trial_balance', 'general_ledger')
    )
);

-- Report cache
CREATE TABLE report_cache (
    cache_key VARCHAR(255) PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id),
    report_type VARCHAR(50) NOT NULL,
    period_start DATE,
    period_end DATE,
    cached_data JSONB NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_generated_reports_company ON generated_reports(company_id);
CREATE INDEX idx_generated_reports_type ON generated_reports(company_id, report_type);
CREATE INDEX idx_generated_reports_period ON generated_reports(period_start, period_end);
CREATE INDEX idx_report_cache_expires ON report_cache(expires_at);
```

---

## Export Formats

### JSON Format
```json
{
  "reportType": "balance_sheet",
  "companyName": "ABC Company",
  "period": {
    "start": "2025-01-01",
    "end": "2025-12-31"
  },
  "generatedAt": "2025-12-13T10:30:00Z",
  "data": {
    "assets": {
      "current": [...],
      "fixed": [...],
      "total": 100000.00
    },
    "liabilities": {...},
    "equity": {...}
  },
  "isBalanced": true
}
```

### CSV Format
For trial balance:
```csv
Account Code,Account Name,Account Type,Debit,Credit
1000,Cash,Asset,5000.00,
1100,Accounts Receivable,Asset,3000.00,
2000,Accounts Payable,Liability,,2000.00
...
,TOTALS,,8000.00,8000.00
```

### PDF Format
- Uses report templates above
- Company logo if configured
- Page numbers and timestamps
- Signature lines for approvals
