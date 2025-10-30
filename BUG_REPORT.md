# BUG REPORT - LOS v3.0

**Generated:** 2025-10-30
**Phase:** 2 - Core & Foundation Audit
**Status:** 🔴 CRITICAL ISSUES FOUND

---

## 🚨 CRITICAL BUGS (Blocker - Must Fix Immediately)

### BUG-001: Login accepts deactivated users
**File:** `login.php:64`
**Severity:** CRITICAL
**Impact:** Security vulnerability - deactivated users can still login

**Current code:**
```php
$sql = "SELECT id, username, password_hash, full_name, role, branch FROM users WHERE username = ?";
```

**Problem:** Query doesn't check `is_active = 1`

**Fix required:**
```php
$sql = "SELECT id, username, password_hash, full_name, role, branch FROM users WHERE username = ? AND is_active = 1";
```

---

### BUG-002: Functions query non-existent tables
**File:** `includes/functions.php`
**Severity:** CRITICAL
**Impact:** Runtime errors when calling these functions

**Affected functions:**
1. `get_credit_ratings_for_customer()` (line 57) - queries `customer_credit_ratings`
2. `get_related_parties_for_customer()` (line 68) - queries `customer_related_parties`
3. `get_application_history()` (line 134) - queries `application_history`
4. `add_history()` (line 152) - inserts to `application_history`
5. `get_repayment_sources_for_app()` (line 201) - queries `application_repayment_sources`

**Tables missing from database.sql:**
- customer_credit_ratings
- customer_related_parties
- application_history
- application_repayment_sources

**Fix options:**
- **Option A:** Create these tables in database.sql
- **Option B:** Remove these functions (if not used)
- **Option C:** Comment out functions and mark as "future feature"

**Recommendation:** Check if functions are actually called anywhere, if not - remove them.

---

### BUG-003: Collateral types column name mismatch
**File:** `includes/functions.php:180`
**Severity:** HIGH
**Impact:** Query will fail

**Current code:**
```php
$sql = "SELECT * FROM collateral_types ORDER BY name";
```

**Problem:** Table `collateral_types` has column `type_name`, not `name`

**Fix required:**
```php
$sql = "SELECT * FROM collateral_types ORDER BY type_name";
```

---

### BUG-004: Permission system queries non-existent tables
**File:** `includes/permission_functions.php`
**Severity:** CRITICAL
**Impact:** Entire permission system will fail

**Affected functions:** ALL permission functions query missing tables:
- `has_permission()` - queries `roles`, `permissions`, `role_permissions`
- `get_user_permissions()` - same tables
- `can_access_branch()` - queries `user_branch_access`
- `filter_by_branch_access()` - queries `user_branch_access`
- `can_perform_action_on_application()` - uses above functions

**Tables missing from database.sql:**
- roles
- permissions
- role_permissions
- user_branch_access

**Fix options:**
- **Option A:** Create these tables + seed data
- **Option B:** Simplify permission system to use role-based checks only (stored in users table)

**Recommendation:** Option B - Simplify to role-based (current system already has role in users table)

---

## ⚠️ HIGH PRIORITY BUGS

### BUG-005: Incorrect redirect path logic
**File:** `includes/security_init.php:12`
**Severity:** MEDIUM
**Impact:** May redirect to wrong path in some cases

**Current code:**
```php
header("location: " . (__DIR__ . "/../" ? "../" : "") . "login.php");
```

**Problem:** `__DIR__ . "/../"` will always be truthy, so always redirects to "../login.php"

**Fix required:**
```php
// For files in root directory
header("location: login.php");

// OR for files in subdirectories
header("location: ../login.php");

// OR dynamic detection
$is_in_subdirectory = (strpos(__DIR__, '/admin/') !== false || strpos(__DIR__, '/includes/') !== false);
$login_path = $is_in_subdirectory ? "../login.php" : "login.php";
header("location: $login_path");
```

---

## 📊 BUGS SUMMARY

| Severity | Count | Files Affected |
|----------|-------|----------------|
| CRITICAL | 4 | login.php, functions.php, permission_functions.php |
| HIGH | 1 | security_init.php |
| MEDIUM | 0 | - |
| LOW | 0 | - |

**Total Bugs:** 5 groups affecting 10+ specific issues

---

## 🔧 RECOMMENDED FIX ORDER

1. **BUG-001** (login.php) - Quick 1-line fix, critical security
2. **BUG-003** (collateral column) - Quick 1-line fix
3. **BUG-005** (redirect path) - Quick fix
4. **BUG-002** (functions.php) - Remove or comment unused functions
5. **BUG-004** (permission system) - Major refactor needed

---

## ✅ PHASE 2 COMPLETION STATUS

- [x] Phase 2.1: Database & Configuration audit
- [x] Phase 2.2: Authentication & Security audit
- [x] Phase 2.3: Core Functions & Permissions audit
- [ ] Phase 2: Bug fixes (in progress)

**Files Audited:** 9/9
**Bugs Found:** 10+
**Bugs Fixed:** 0

---

## 📝 NOTES

### Observations:
1. Code quality varies - some files very secure (login.php, logout.php), others have major issues
2. Permission system appears over-engineered for current requirements
3. Several functions query tables that don't exist - suggests incomplete migration or leftover code

### Recommendations:
1. **Immediate:** Fix critical security bugs (BUG-001)
2. **Short-term:** Clean up unused functions, simplify permission system
3. **Long-term:** Consider adding missing tables if features are needed

### Questions for User:
- Do you need the advanced permission system with role_permissions tables?
- Are credit ratings, related parties features required?
- Should I remove unused code or create missing tables?

---

**Next Step:** Begin fixing bugs starting with BUG-001 (most critical)
