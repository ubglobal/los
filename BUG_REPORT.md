# BUG REPORT - LOS v3.0

**Generated:** 2025-10-30
**Phase:** 2 - Core & Foundation Audit
**Status:** ✅ ALL BUGS FIXED

---

## 🚨 CRITICAL BUGS (Blocker - Must Fix Immediately)

### BUG-001: Login accepts deactivated users ✅ FIXED
**File:** `login.php:64`
**Severity:** CRITICAL
**Impact:** Security vulnerability - deactivated users can still login

**Status:** ✅ **FIXED**

**Fix applied:**
```php
$sql = "SELECT id, username, password_hash, full_name, role, branch FROM users WHERE username = ? AND is_active = 1";
```

Added `AND is_active = 1` check to login query. Deactivated users can no longer access the system.

---

### BUG-002: Functions query non-existent tables ✅ FIXED
**File:** `includes/functions.php`
**Severity:** CRITICAL
**Impact:** Runtime errors when calling these functions

**Status:** ✅ **FIXED**

**Investigation results:**
ALL functions were actively used in critical workflows:
- `add_history()` - Called in create_application.php and process_action.php (CRITICAL!)
- `get_application_history()` - Used in application_detail.php
- `get_credit_ratings_for_customer()` - Used in application_detail.php
- `get_related_parties_for_customer()` - Used in application_detail.php and admin/customer_detail.php
- `get_repayment_sources_for_app()` - Used in application_detail.php

**Fix applied:**
Created 4 missing tables in database.sql:
- ✅ `application_history` - Audit trail for application changes
- ✅ `customer_credit_ratings` - Customer credit score history
- ✅ `customer_related_parties` - Customer relationship mapping
- ✅ `application_repayment_sources` - Expected repayment sources

**Migration:**
- Added tables to database.sql (lines 460-542) for new installations
- Created `migrate_phase2.php` web tool for existing installations
- Tool adds retroactive history for existing applications

---

### BUG-003: Collateral types column name mismatch ✅ FIXED
**File:** `includes/functions.php:180`
**Severity:** HIGH
**Impact:** Query will fail

**Status:** ✅ **FIXED**

**Fix applied:**
```php
$sql = "SELECT * FROM collateral_types ORDER BY type_name";
```

Changed `ORDER BY name` to `ORDER BY type_name` to match actual database schema.

---

### BUG-004: Permission system queries non-existent tables ✅ FIXED
**File:** `includes/permission_functions.php`
**Severity:** CRITICAL
**Impact:** Entire permission system will fail

**Status:** ✅ **FIXED**

**Investigation results:**
- Only `has_permission()` is actively used (in disbursement_create.php and disbursement_action.php)
- Only 1 permission check: 'disbursement.input'
- Other complex permission functions not used in codebase

**Fix applied: Simplified to role-based permission mapping**
- Removed dependency on complex permission tables (roles, permissions, role_permissions)
- Implemented inline permission map inside `has_permission()` function
- Maps permissions to roles: e.g., 'disbursement.input' → ['Thủ quỹ', 'Admin']
- Covers all common permissions: credit, disbursement, customer, reports, exceptions
- Uses existing `users.role` column (no new tables needed)

**Benefits:**
- ✅ No database schema changes required
- ✅ Much simpler and easier to maintain
- ✅ Faster (no complex JOINs)
- ✅ Session caching for performance
- ✅ Easy to extend with new permissions

---

## ⚠️ HIGH PRIORITY BUGS

### BUG-005: Incorrect redirect path logic ✅ FIXED
**File:** `includes/security_init.php:12-17`
**Severity:** MEDIUM (HIGH in some configurations)
**Impact:** May redirect to wrong path in some cases

**Status:** ✅ **FIXED**

**Fix applied: Dynamic subdirectory detection**
```php
$is_in_subdirectory = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false ||
                       strpos($_SERVER['PHP_SELF'], '/includes/') !== false);
$login_path = $is_in_subdirectory ? "../login.php" : "login.php";
header("location: $login_path");
```

Now correctly detects if the file is in a subdirectory and redirects accordingly.

---

## 📊 BUGS SUMMARY

| Severity | Count | Status |
|----------|-------|---------|
| CRITICAL | 4 | ✅ ALL FIXED |
| HIGH | 1 | ✅ FIXED |
| MEDIUM | 0 | - |
| LOW | 0 | - |

**Total Bugs:** 5 groups (10+ specific issues) - **ALL FIXED ✅**

---

## ✅ FIX COMPLETION ORDER

1. ✅ **BUG-001** (login.php) - Added is_active check
2. ✅ **BUG-003** (collateral column) - Fixed column name
3. ✅ **BUG-005** (redirect path) - Implemented proper detection
4. ✅ **BUG-002** (functions.php) - Created 4 missing database tables
5. ✅ **BUG-004** (permission system) - Simplified to role-based mapping

---

## ✅ PHASE 2 COMPLETION STATUS

- [x] Phase 2.1: Database & Configuration audit
- [x] Phase 2.2: Authentication & Security audit
- [x] Phase 2.3: Core Functions & Permissions audit
- [x] Phase 2.4: Bug fixes **COMPLETED ✅**

**Files Audited:** 9/9
**Bugs Found:** 10+ (across 5 bug groups)
**Bugs Fixed:** 10+ (100% completion) ✅

---

## 📝 NOTES

### Observations:
1. Code quality varies - some files very secure (login.php, logout.php), others have major issues
2. Permission system appears over-engineered for current requirements
3. Several functions query tables that don't exist - suggests incomplete migration or leftover code

### Actions Taken:
1. ✅ **Security:** Fixed critical login vulnerability (BUG-001)
2. ✅ **Database:** Created 4 missing tables for core functionality (BUG-002)
3. ✅ **Permissions:** Simplified to role-based system - no complex tables needed (BUG-004)
4. ✅ **Queries:** Fixed column name mismatches (BUG-003)
5. ✅ **Redirects:** Implemented proper path detection (BUG-005)

### Files Modified:
- `login.php` - Added is_active check
- `includes/functions.php` - Fixed ORDER BY column name
- `includes/security_init.php` - Fixed redirect path logic
- `includes/permission_functions.php` - Simplified has_permission() to role-based
- `database.sql` - Added 4 new tables (application_history, customer_credit_ratings, customer_related_parties, application_repayment_sources)

### Files Created:
- `migrate_phase2.php` - Web-based migration tool for existing installations
- `database_fix_phase2.sql` - Standalone SQL migration script

---

**Next Step:** Phase 3 - Module-by-Module Audit (Application Management, Disbursement, etc.)
