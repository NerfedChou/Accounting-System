# All Transaction Scenarios

This document outlines all possible transaction scenarios, categorized by their frequency and approval requirements.

## Normal Transactions (Common, No Approval Needed)

These are everyday transactions that form the bulk of business operations.

| Scenario | Debit | Credit | Description |
| :--- | :--- | :--- | :--- |
| **Cash Sale** | Cash (Asset) ↑ | Sales Revenue (Revenue) ↑ | Customer pays for goods/services with cash. |
| **Credit Sale** | Accounts Receivable (Asset) ↑ | Sales Revenue (Revenue) ↑ | Customer buys goods/services on credit. |
| **Receive Payment**| Cash (Asset) ↑ | Accounts Receivable (Asset) ↓ | Customer pays off their credit account. |
| **Pay Expense** | Rent Expense (Expense) ↑ | Cash (Asset) ↓ | Company pays rent for the month. |
| **Buy Asset** | Equipment (Asset) ↑ | Cash (Asset) ↓ | Company buys new equipment with cash. |
| **Borrow Money** | Cash (Asset) ↑ | Loan Payable (Liability) ↑ | Company takes out a loan from a bank. |
| **Pay Debt** | Loan Payable (Liability) ↓ | Cash (Asset) ↓ | Company makes a payment on a loan. |
| **Accrue Expense** | Utilities Expense (Expense) ↑ | Utilities Payable (Liability) ↑| Company receives a utility bill but hasn't paid it yet. |
| **Pay Accrued Expense**| Utilities Payable (Liability) ↓ | Cash (Asset) ↓ | Company pays the utility bill it previously recorded. |
| **Owner Investment**| Cash (Asset) ↑ | Owner's Capital (Equity) ↑ | The owner invests personal funds into the business. |

## Rare Transactions (Uncommon, No Approval Needed)

These transactions are less frequent but follow standard accounting rules.

| Scenario | Debit | Credit | Description |
| :--- | :--- | :--- | :--- |
| **Asset Exchange** | New Vehicle (Asset) ↑ | Old Vehicle (Asset) ↓ | Company trades one asset for another of equal value. |
| **Sales Return** | Sales Returns (Contra Revenue) ↑ | Accounts Receivable (Asset) ↓ | Customer returns goods purchased on credit. |
| **Write-off Bad Debt**| Bad Debt Expense (Expense) ↑ | Accounts Receivable (Asset) ↓ | Company determines a customer's account is uncollectible. |
| **Prepaid Expense** | Prepaid Insurance (Asset) ↑ | Cash (Asset) ↓ | Company pays for a full year of insurance upfront. |
| **Recognize Prepaid**| Insurance Expense (Expense) ↑ | Prepaid Insurance (Asset) ↓ | At month-end, recognize one month of the prepaid insurance. |
| **Unearned Revenue**| Cash (Asset) ↑ | Unearned Revenue (Liability) ↑ | Customer pays in advance for services not yet rendered. |
| **Earn Unearned** | Unearned Revenue (Liability) ↓ | Service Revenue (Revenue) ↑ | Company provides the service and earns the prepaid amount. |

## Transactions Requiring Admin Approval

These transactions are sensitive and require review by an administrator before they can be posted.

| Scenario | Debit | Credit | Description |
| :--- | :--- | :--- | :--- |
| **Period Closing** | Sales Revenue (Revenue) ↓<br>Retained Earnings (Equity) ↑ | Rent Expense (Expense) ↓ | **SYSTEM-GENERATED.** Closes all revenue and expense accounts to Retained Earnings at the end of an accounting period. |
| **Owner's Draw** | Owner's Draw (Equity) ↓ | Cash (Asset) ↓ | Owner takes cash from the business for personal use. This can make equity negative. |
| **Correcting Entry**| Varies | Varies | A manual journal entry to fix a mistake in a previous transaction. |
| **Voiding Transaction**| Varies (reverses original) | Varies (reverses original) | **SYSTEM-GENERATED.** A posted transaction is voided, creating a reversing entry. |

## Invalid Transactions (Blocked by System)

These transactions violate fundamental accounting principles and are blocked by the system.

| Scenario | Debit | Credit | Reason for Blocking |
| :--- | :--- | :--- | :--- |
| **Revenue & Expense**| Rent Expense (Expense) | Sales Revenue (Revenue) | **VIOLATION:** Revenue and Expense accounts can never be in the same transaction. They are temporary accounts that only move against balance sheet accounts. |
| **Direct Equity to Equity**| Owner's Capital (Equity) | Retained Earnings (Equity) | **VIOLATION:** Equity accounts do not transact with each other directly. Changes in equity come from net income (via closing) or owner contributions/draws (against assets). |
| **Direct Expense to Revenue**| Sales Revenue (Revenue) | Rent Expense (Expense) | **VIOLATION:** Same as the first rule. This is fundamentally incorrect. |
| **Unbalanced Entry** | Cash (Asset) $100 | Sales Revenue (Revenue) $90 | **VIOLATION:** Debits do not equal credits. The system will always enforce this balance. |
| **Negative Asset** | Varies | Cash (Asset) | **VIOLATION:** The system prevents asset accounts (like Cash) from having a negative balance. |

