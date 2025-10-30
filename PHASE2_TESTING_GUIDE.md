# Phase 2 Testing Guide - LOS v3.0

**Version**: 3.0
**Date**: 2025-10-30
**Author**: Claude AI

## Overview

This guide provides comprehensive testing procedures for Phase 2 - Core Business Logic implementation. All tests use sample data created during Phase 1 migrations.

---

## Prerequisites

✅ **Phase 1 Completed**: All 13 migrations successfully applied
✅ **Sample Data Loaded**: Users, applications, facilities, collaterals, etc.
✅ **Test Users Available**:
- Admin (id=4, password: Admin@123)
- CVQHKH (id=1, password: RM@123456)
- CVTĐ (id=2, password: CA@123456)
- CPD (id=3, password: AP@123456)
- GDK (id=5, password: DIR@12345)

---

## Module Testing Checklist

### 1. Workflow Engine (`includes/workflow_engine.php`)

#### Test 1.1: Validate Workflow Step Retrieval
**Objective**: Verify workflow steps are correctly loaded for Credit Approval

```php
// Test in PHP console or temporary test file
require_once 'config/db.php';
require_once 'includes/workflow_engine.php';

$step = get_workflow_step($link, 'Credit_Approval', 'Khởi tạo');
print_r($step);

// Expected: Should return step with id=1, allowed_actions, allowed_roles
```

**Expected Output**:
```
Array (
    [id] => 1
    [step_name] => Khởi tạo
    [allowed_actions] => Next,Return
    [allowed_roles] => CVQHKH
    [sla_hours] => 24
)
```

#### Test 1.2: Check Permission to Perform Action
**Objective**: Verify role-based action validation

```php
// Test CVQHKH can perform 'Next' on new application
$can_perform = can_perform_action($link, 1, 'CVQHKH', 'Next', 'Khởi tạo');
print_r($can_perform);

// Expected: ['allowed' => true, 'message' => '']
```

**Expected**: CVQHKH can perform 'Next', but cannot perform 'Approve'

#### Test 1.3: Execute Transition (Send for Review)
**Manual Test via UI**:

1. Login as **CVQHKH** (RM) - user_id=1
2. Navigate to Application HSTD 1 (id=1, status="Bản nháp")
3. Click "Gửi thẩm định" button
4. Verify:
   - Status changes to "Chờ thẩm định"
   - Stage changes to "Thẩm định"
   - Assigned to CVTĐ (user_id=2)
   - History entry added
   - SLA updated

**Database Verification**:
```sql
SELECT id, hstd_code, status, stage, assigned_to_id, sla_status
FROM credit_applications
WHERE id = 1;

-- Expected: status='Chờ thẩm định', stage='Thẩm định', assigned_to_id=2

SELECT * FROM application_history
WHERE application_id = 1
ORDER BY timestamp DESC LIMIT 1;

-- Expected: action='Next', user_id=1, comment='Trình hồ sơ thẩm định'
```

---

### 2. Facility Management (`includes/facility_functions.php`)

#### Test 2.1: Create Facility
**Manual Test via UI** (if UI exists):

1. Login as **Admin** (id=4)
2. Go to Application HSTD 1 (id=1)
3. Create new facility:
   - Facility Type: "Revolving"
   - Product: Product 2 (id=2) - "Hạn mức tín dụng"
   - Amount: 1,000,000,000 VND
   - Collateral Required: Yes

**Database Verification**:
```sql
SELECT * FROM facilities WHERE application_id = 1 ORDER BY id DESC LIMIT 1;

-- Expected:
-- facility_code like 'FAC-2025-1-XX'
-- status='Pending'
-- amount=1000000000
-- available_amount=1000000000
-- disbursed_amount=0
```

#### Test 2.2: Activate Facility
**Precondition**: Collateral must be in warehouse with facility_activated=TRUE

**Test Steps**:
1. Verify collateral status for HSTD 1:
   ```sql
   SELECT warehouse_status, facility_activated
   FROM application_collaterals
   WHERE application_id = 1;

   -- Expected: warehouse_status='In Warehouse', facility_activated=1
   ```

2. Login as **CPD** (id=3) or **Admin** (id=4)
3. Activate facility via action handler:
   ```
   POST to process_action.php:
   - action=activate_facility
   - facility_id=X (from step 2.1)
   - application_id=1
   ```

4. Verify activation:
   ```sql
   SELECT status, collateral_activated, activation_date
   FROM facilities WHERE id = X;

   -- Expected: status='Active', collateral_activated=1, activation_date=CURDATE()
   ```

#### Test 2.3: Check Facility Availability
**Objective**: Verify available_amount is correctly calculated

**Test in PHP**:
```php
$facility = get_facility_by_id($link, $facility_id);
echo "Amount: " . $facility['amount'] . "\n";
echo "Disbursed: " . $facility['disbursed_amount'] . "\n";
echo "Available: " . $facility['available_amount'] . "\n";

// Expected: available_amount = amount - disbursed_amount (auto-calculated by DB)
```

---

### 3. Disbursement Workflow (`includes/disbursement_functions.php`)

#### Test 3.1: Check Disbursement Preconditions
**Objective**: Verify all 8 precondition checks work correctly

**Test with HSTD 4** (id=4, already approved):

```php
$checks = check_disbursement_preconditions($link, 4, 3); // facility_id=3
print_r($checks);

// Expected Output:
// [
//   'allowed' => true,
//   'message' => 'Tất cả điều kiện đều đáp ứng. Có thể tiến hành giải ngân.',
//   'checks' => [
//     '✓ Hồ sơ đã hoàn tất thủ tục pháp lý',
//     '✓ Hồ sơ có ngày hiệu lực',
//     '✓ Hồ sơ đã được phê duyệt',
//     '✓ Hạn mức đã kích hoạt',
//     '✓ TSBĐ đã nhập kho và kích hoạt',
//     '✓ Hạn mức còn số dư khả dụng',
//     ...
//   ]
// ]
```

**Test with HSTD 1** (id=1, not yet approved):

```php
$checks = check_disbursement_preconditions($link, 1, 1);
print_r($checks);

// Expected: allowed=false, with specific reasons listed
```

#### Test 3.2: Create Disbursement Request
**Manual Test via UI**:

1. Login as **CVQHKH** (RM) - user_id=1
2. Navigate to Application HSTD 4 (id=4) - already approved and legal completed
3. Click "Tạo yêu cầu giải ngân"
4. Fill form:
   - Facility: Select facility for HSTD 4 (facility_id=3)
   - Amount: 300,000,000 VND
   - Disbursement Type: "Partial"
   - Beneficiary Account: "0123456789 - VCB"
   - Notes: "Giải ngân lần 1"
5. Submit form

**Expected Results**:
- Disbursement created with status="Draft"
- Disbursement code generated (e.g., "DISB-2025-4-01")
- Default conditions created automatically:
  - Hợp đồng tín dụng đã ký kết
  - Khách hàng đã mở tài khoản
  - TSBĐ đã đăng ký thế chấp (if collateral required)
  - Hợp đồng bảo hiểm (if collateral required)

**Database Verification**:
```sql
SELECT * FROM disbursements WHERE application_id = 4 ORDER BY id DESC LIMIT 1;

-- Expected: status='Draft', amount=300000000, facility_id=3

SELECT * FROM disbursement_conditions WHERE disbursement_id = X;

-- Expected: 4 default conditions created
```

#### Test 3.3: Complete Disbursement Workflow
**Objective**: Test full disbursement flow from Draft → Completed

**Step 1: RM Updates Conditions**
1. Login as CVQHKH (id=1)
2. For each condition, mark as "Met" with notes
3. Verify: is_met=1, met_date=CURDATE()

**Step 2: RM Submits for Approval**
1. Click "Trình giải ngân"
2. Expected: Status changes to "Awaiting Conditions Check"
3. Assigned to Kiểm soát role

**Step 3: Kiểm soát Checks Conditions**
1. Login as user with role "Kiểm soát" (or Admin)
2. Review all conditions
3. Click "Xác nhận điều kiện"
4. Expected: Status changes to "Awaiting Approval"
5. Assigned to CPD or GDK based on amount

**Step 4: CPD/GDK Approves**
1. Login as CPD (id=3) for amounts ≤5B
2. Or login as GDK (id=5) for amounts >5B
3. Click "Phê duyệt giải ngân"
4. Expected:
   - Status changes to "Approved"
   - approved_by_id set
   - approved_date set

**Step 5: Thủ quỹ Executes**
1. Login as user with role "Thủ quỹ" (or Admin)
2. Enter transaction reference number
3. Click "Thực hiện giải ngân"
4. Expected:
   - Status changes to "Completed"
   - Facility disbursed_amount increased by disbursement amount
   - available_amount decreased automatically
   - disbursement_date set

**Complete Verification**:
```sql
-- Check disbursement
SELECT id, disbursement_code, status, amount, approved_by_id, executed_by_id
FROM disbursements WHERE id = X;

-- Expected: status='Completed', approved_by_id IS NOT NULL, executed_by_id IS NOT NULL

-- Check facility balance
SELECT amount, disbursed_amount, available_amount
FROM facilities WHERE id = 3;

-- Expected: disbursed_amount increased, available_amount decreased

-- Check history
SELECT * FROM disbursement_history WHERE disbursement_id = X ORDER BY timestamp;

-- Expected: Multiple entries for each workflow step
```

---

### 4. Exception & Escalation (`includes/exception_escalation_functions.php`)

#### Test 4.1: Request Exception for Approval Condition
**Objective**: Test exception request workflow

**Setup**:
1. Create an approval condition with allow_exception=TRUE
   ```sql
   INSERT INTO approval_conditions (application_id, condition_text, condition_type, mandatory, allow_exception)
   VALUES (1, 'Khách hàng cung cấp báo cáo tài chính 3 năm gần nhất', 'Financial', 1, 1);
   ```

**Test Steps**:
1. Login as CVQHKH (id=1)
2. Navigate to Application HSTD 1
3. For the condition above, click "Xin ngoại lệ"
4. Enter reason: "Khách hàng là doanh nghiệp mới thành lập, chỉ có BCTC 1 năm"
5. Submit

**Expected Results**:
```sql
SELECT is_exception_requested, exception_reason, exception_requested_by_id
FROM approval_conditions WHERE id = X;

-- Expected: is_exception_requested=1, exception_requested_by_id=1
```

#### Test 4.2: Approve Exception
**Test Steps**:
1. Login as CPD (id=3) or GDK (id=5)
2. Navigate to Application HSTD 1
3. Review exception request
4. Click "Chấp thuận ngoại lệ"
5. Comment: "Đồng ý ngoại lệ. Bù lại bằng tài sản đảm bảo"

**Expected Results**:
```sql
SELECT is_exception_approved, exception_approved_by_id, exception_approval_date
FROM approval_conditions WHERE id = X;

-- Expected: is_exception_approved=1, exception_approved_by_id=3
```

#### Test 4.3: Create Escalation (Khiếu nại)
**Scenario**: Application is rejected, RM wants to escalate to GDK

**Test Steps**:
1. Assume Application HSTD 1 is rejected by CPD
2. Login as CVQHKH (id=1)
3. Click "Khiếu nại quyết định từ chối"
4. Enter reason: "Khách hàng có tiềm năng tốt, đề nghị GĐK xem xét lại"
5. Submit

**Expected Results**:
```sql
SELECT * FROM escalations WHERE application_id = 1 ORDER BY id DESC LIMIT 1;

-- Expected:
-- escalation_type='Rejection Review'
-- status='Pending'
-- escalated_by_id=1
-- escalated_to_id=5 (GDK)
-- escalated_date=CURDATE()
```

#### Test 4.4: Resolve Escalation
**Test Steps**:
1. Login as GDK (id=5)
2. Navigate to escalation detail
3. Review and decide:
   - Option 1: Approve (override rejection)
   - Option 2: Maintain Rejection

**If Approve**:
```sql
SELECT status, resolution FROM escalations WHERE id = X;
-- Expected: status='Resolved', resolution='Approved'

SELECT status FROM credit_applications WHERE id = 1;
-- Expected: status changed back to appropriate approval stage
```

---

### 5. Permission System (`includes/permission_functions.php`)

#### Test 5.1: Check User Permissions
**Objective**: Verify permission checking works correctly

**Test with different roles**:
```php
// Test CVQHKH can input credit
$has_input = has_permission($link, 1, 'credit.input');
echo "CVQHKH can input credit: " . ($has_input ? 'YES' : 'NO') . "\n";
// Expected: YES

// Test CVQHKH cannot approve credit
$has_approve = has_permission($link, 1, 'credit.approve');
echo "CVQHKH can approve credit: " . ($has_approve ? 'YES' : 'NO') . "\n";
// Expected: NO

// Test CPD can approve credit
$has_approve = has_permission($link, 3, 'credit.approve');
echo "CPD can approve credit: " . ($has_approve ? 'YES' : 'NO') . "\n";
// Expected: YES

// Test Admin has all permissions
$has_system_config = has_permission($link, 4, 'admin.system_config');
echo "Admin can configure system: " . ($has_system_config ? 'YES' : 'NO') . "\n";
// Expected: YES
```

#### Test 5.2: Check Branch Access Control
**Objective**: Verify branch-based access control

**Test Setup**:
- User 1 (CVQHKH) belongs to "CN An Giang"
- User 5 (GDK) belongs to "Hội sở" but has cross-branch access

**Test**:
```php
// Test CVQHKH can access own branch
$can_access_ag = can_access_branch($link, 1, 'CN An Giang');
echo "CVQHKH can access CN An Giang: " . ($can_access_ag ? 'YES' : 'NO') . "\n";
// Expected: YES

// Test CVQHKH cannot access other branch
$can_access_hs = can_access_branch($link, 1, 'Hội sở');
echo "CVQHKH can access Hội sở: " . ($can_access_hs ? 'YES' : 'NO') . "\n";
// Expected: NO (unless granted in user_branch_access)

// Test GDK can access all branches
$can_access_ag = can_access_branch($link, 5, 'CN An Giang');
$can_access_hs = can_access_branch($link, 5, 'Hội sở');
echo "GDK can access CN An Giang: " . ($can_access_ag ? 'YES' : 'NO') . "\n";
echo "GDK can access Hội sở: " . ($can_access_hs ? 'YES' : 'NO') . "\n";
// Expected: YES for both
```

#### Test 5.3: Check Approval Limits
**Objective**: Verify approval limit enforcement

**Test**:
```php
// Test CPD with 3B application
$check = check_approval_limit($link, 3, 3000000000); // 3 billion
print_r($check);
// Expected: can_approve=true (CPD limit is 5B)

// Test CPD with 6B application
$check = check_approval_limit($link, 3, 6000000000); // 6 billion
print_r($check);
// Expected: can_approve=false, message about exceeding limit

// Test GDK with 6B application
$check = check_approval_limit($link, 5, 6000000000); // 6 billion
print_r($check);
// Expected: can_approve=true (GDK limit is 20B)

// Test Admin (no limit)
$check = check_approval_limit($link, 4, 30000000000); // 30 billion
print_r($check);
// Expected: can_approve=true (Admin has no limit)
```

#### Test 5.4: Permission Caching
**Objective**: Verify permission caching improves performance

**Test**:
```php
// Clear cache
clear_permission_cache();

// First call (should query database)
$start = microtime(true);
$has_perm = has_permission($link, 1, 'credit.input');
$time1 = microtime(true) - $start;
echo "First call: {$time1}s\n";

// Second call (should use cache)
$start = microtime(true);
$has_perm = has_permission($link, 1, 'credit.input');
$time2 = microtime(true) - $start;
echo "Second call (cached): {$time2}s\n";

// Expected: time2 should be significantly less than time1
```

---

## Integration Testing Scenarios

### Scenario 1: Complete Credit Application Workflow
**Objective**: Test full workflow from creation to approval

**Steps**:
1. **CVQHKH Creates Application**
   - Login as user_id=1
   - Create new application (amount: 4,000,000,000 VND)
   - Add collateral, repayment sources, documents
   - Submit for review (action="send_for_review")

2. **CVTĐ Reviews and Appraises**
   - Login as user_id=2
   - Review application
   - Option A: Return for info (action="return_for_info")
   - Option B: Submit for approval (action="submit_for_approval")

3. **CPD Approves** (amount ≤5B)
   - Login as user_id=3
   - Check approval limit (should pass for 4B)
   - Approve (action="approve")
   - System creates approval conditions automatically

4. **Legal Completion**
   - Login as Admin (user_id=4)
   - Mark legal completed (action="mark_legal_completed")
   - Enter effective_date and legal_notes

5. **Collateral Warehouse Management**
   - Update collateral status to "In Warehouse"
   - Activate collateral for facility

6. **Facility Activation**
   - Activate facility (action="activate_facility")

7. **Disbursement**
   - Create disbursement request
   - Follow disbursement workflow to completion

**Expected Timeline**:
- Draft → Review: 1 day SLA
- Review → Approval: 2 days SLA
- Approval → Legal: Manual
- Legal → Disbursement: Manual

### Scenario 2: High-Value Application (>5B) Requiring GDK
**Objective**: Test escalation to GDK for amounts exceeding CPD limit

**Steps**:
1. Create application with amount: 7,000,000,000 VND
2. CVQHKH submits
3. CVTĐ reviews and submits for approval
4. System should route to GDK (id=5) instead of CPD
5. GDK approves (check approval limit: should pass for 7B)

**Expected**:
- Application skips CPD, goes directly to GDK
- Stage: "Chờ phê duyệt cấp cao"

### Scenario 3: Rejection and Escalation
**Objective**: Test rejection workflow and escalation mechanism

**Steps**:
1. CPD rejects application with reason
2. CVQHKH creates escalation to GDK
3. GDK reviews escalation
4. Option A: GDK approves (override rejection)
5. Option B: GDK maintains rejection

**Expected**:
- Escalation tracked in escalations table
- History logged in application_history
- If GDK approves: status changes to approved
- If GDK maintains: status remains rejected

### Scenario 4: Partial Disbursement
**Objective**: Test multiple partial disbursements from same facility

**Steps**:
1. Create facility with amount: 1,000,000,000 VND
2. Activate facility
3. Create 1st disbursement: 300,000,000 VND → Execute
4. Verify: disbursed_amount=300M, available_amount=700M
5. Create 2nd disbursement: 500,000,000 VND → Execute
6. Verify: disbursed_amount=800M, available_amount=200M
7. Try 3rd disbursement: 300,000,000 VND
8. Expected: Should fail (exceeds available_amount)

**Database Checks**:
```sql
SELECT amount, disbursed_amount, available_amount FROM facilities WHERE id = X;

-- After 1st disbursement:
-- amount=1000000000, disbursed_amount=300000000, available_amount=700000000

-- After 2nd disbursement:
-- amount=1000000000, disbursed_amount=800000000, available_amount=200000000
```

---

## Error Handling Tests

### Test 1: Unauthorized Action Attempt
**Objective**: Verify permission checks prevent unauthorized actions

**Test**:
1. Login as CVQHKH (id=1)
2. Try to approve application directly (action="approve")
3. Expected: Error - "Bạn không có quyền Approve"

### Test 2: Insufficient Approval Limit
**Objective**: Verify approval limit enforcement

**Test**:
1. Login as CPD (id=3, limit=5B)
2. Try to approve 6B application
3. Expected: Error - "Số tiền vượt hạn mức phê duyệt. Cần phê duyệt cấp cao hơn."

### Test 3: Disbursement Without Legal Completion
**Objective**: Verify precondition checks

**Test**:
1. Try to create disbursement for application without legal_completed=TRUE
2. Expected: Error - "Hồ sơ chưa hoàn tất thủ tục pháp lý"

### Test 4: Facility Activation Without Collateral
**Objective**: Verify collateral prerequisite

**Test**:
1. Try to activate facility when collateral not in warehouse
2. Expected: Error - "TSBĐ chưa nhập kho hoặc chưa được kích hoạt"

### Test 5: Exception Request for Non-Exceptionable Condition
**Objective**: Verify allow_exception flag

**Test**:
1. Create condition with allow_exception=FALSE
2. Try to request exception
3. Expected: Error - "Điều kiện này không cho phép xin ngoại lệ"

---

## Performance Testing

### Test 1: Permission Cache Effectiveness
**Objective**: Measure cache performance improvement

**Method**:
```php
// Clear cache
$_SESSION['user_permissions_cache'] = [];

// Time 100 permission checks without cache
$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    clear_permission_cache();
    has_permission($link, 1, 'credit.input');
}
$time_no_cache = microtime(true) - $start;

// Time 100 permission checks with cache
$start = microtime(true);
clear_permission_cache();
for ($i = 0; $i < 100; $i++) {
    has_permission($link, 1, 'credit.input');
}
$time_with_cache = microtime(true) - $start;

echo "Without cache: {$time_no_cache}s\n";
echo "With cache: {$time_with_cache}s\n";
echo "Improvement: " . round(($time_no_cache - $time_with_cache) / $time_no_cache * 100, 2) . "%\n";
```

**Expected**: At least 80% improvement with cache

### Test 2: Transaction Rollback
**Objective**: Verify transaction safety in disbursement

**Method**:
```php
// Attempt disbursement with invalid data to trigger rollback
$result = update_disbursed_amount($link, 999, 1000000000); // non-existent facility

// Verify: No changes to database, proper error message returned
```

---

## Troubleshooting Common Issues

### Issue 1: "Unknown action attempted"
**Cause**: Action name mismatch between UI and action handler

**Solution**:
- Check action name in form/button: `<input name="action" value="exact_action_name">`
- Verify action is in workflow_actions array or switch statement

### Issue 2: "Lỗi hệ thống" on transition
**Cause**: Usually database error or missing data

**Solution**:
- Check error_log for detailed error message
- Verify workflow_steps table has correct configuration
- Check application status and stage match expected values

### Issue 3: Permission cache not clearing
**Cause**: Session not updating

**Solution**:
```php
// Manually clear cache after role changes
clear_permission_cache($user_id);

// Or clear all
clear_permission_cache();
```

### Issue 4: Facility available_amount incorrect
**Cause**: Generated column not updating

**Solution**:
```sql
-- Check if column is generated correctly
SHOW CREATE TABLE facilities;

-- Should see: `available_amount` decimal(20,2) GENERATED ALWAYS AS (amount - disbursed_amount) STORED

-- If not, recreate column:
ALTER TABLE facilities
DROP COLUMN available_amount,
ADD COLUMN available_amount DECIMAL(20,2) GENERATED ALWAYS AS (amount - disbursed_amount) STORED;
```

---

## Test Data Cleanup

After testing, you may want to reset test data:

```sql
-- Delete test disbursements
DELETE FROM disbursement_conditions WHERE disbursement_id IN (SELECT id FROM disbursements WHERE created_by_id = 1);
DELETE FROM disbursement_history WHERE disbursement_id IN (SELECT id FROM disbursements WHERE created_by_id = 1);
DELETE FROM disbursements WHERE created_by_id = 1;

-- Reset facility balances
UPDATE facilities SET disbursed_amount = 0 WHERE application_id IN (1, 3, 4);

-- Delete test escalations
DELETE FROM escalations WHERE escalated_by_id = 1;

-- Reset application statuses
UPDATE credit_applications SET status = 'Bản nháp', stage = 'Khởi tạo' WHERE id IN (1, 2, 3);

-- Clear permission cache
-- (Done via PHP: clear_permission_cache())
```

---

## Success Criteria

Phase 2 is considered successfully tested when:

✅ All workflow transitions execute correctly
✅ Permission checks enforce role-based access control
✅ Approval limits are properly enforced
✅ Facility balances update correctly after disbursement
✅ Disbursement preconditions prevent invalid operations
✅ Exception and escalation workflows complete successfully
✅ Branch access control works as expected
✅ Transaction rollbacks prevent data corruption on errors
✅ Permission caching improves performance
✅ All error messages are clear and actionable

---

## Next Phase

After successful Phase 2 testing:

**Phase 3 - UI/UX Enhancement**:
- Update application_detail.php to show new actions
- Create disbursement_detail.php
- Add modals for exception requests, escalations
- Display approval conditions and disbursement conditions
- Show facility balance and utilization charts
- Add SLA status indicators
- Create dashboard widgets for pending tasks

---

## Support

For issues or questions about Phase 2 testing:
- Check error_log for detailed error messages
- Review application_history for workflow tracking
- Verify workflow_steps configuration
- Ensure all migrations were applied successfully
- Test with Admin role first (bypasses most permission checks)

**End of Phase 2 Testing Guide**
