# PHASE 3.1 - APPLICATION MANAGEMENT MODULE AUDIT

**Date:** 2025-10-30
**Module:** Application Management (Core Business Logic)
**Files Audited:** 3/3
**Status:** ⚠️ BUGS FOUND

---

## FILES AUDITED

1. **create_application.php** (126 lines) - Create new application
2. **application_detail.php** (962 lines) - View and manage application ⭐ LARGEST FILE
3. **process_action.php** (416 lines) - Process all application actions

**Total lines audited:** 1,504 lines

---

## SECURITY ASSESSMENT

### Overall Security: ✅ EXCELLENT

All 3 files demonstrate **OUTSTANDING security practices:**

✅ **Authentication & Authorization:**
- Session validation on every file
- Role-based access control
- IDOR protection (application_detail.php lines 36-81)
- Approval limit checks

✅ **Input Validation:**
- CSRF tokens on all forms
- Type casting for all IDs: `(int)$_POST['id']`
- Input sanitization and length checks
- File upload validation (MIME type, size, extension)

✅ **SQL Injection Prevention:**
- 100% prepared statements usage
- No string concatenation in queries

✅ **XSS Protection:**
- `htmlspecialchars()` on all user output
- No raw `echo $_POST` anywhere

✅ **File Upload Security (process_action.php):**
- MIME type validation with `finfo`
- Extension whitelist
- File size limit (10MB)
- Random filename generation
- Path traversal protection with `realpath()` and `strpos()`
- Original filename never preserved

**Security Score: 9.5/10** - Professional-grade security implementation!

---

## BUGS FOUND

### 🐛 BUG-006: Application Code Generation - Potential Collision

**File:** `create_application.php:37`
**Severity:** MEDIUM (will become HIGH in production with high volume)
**Impact:** HSTD codes could collide, causing INSERT failures

**Current code:**
```php
$hstd_code = "APP." . date("Y") . "." . str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT);
```

**Problem:**
- Uses `random_int(1, 999999)` without checking for duplicates
- With 1 million possible values per year, birthday paradox applies
- At ~1,200 applications per year, collision probability becomes significant

**Recommended fix:**
```php
// Generate unique application code with database check
$hstd_code = null;
$max_attempts = 10;
$attempt = 0;

while ($hstd_code === null && $attempt < $max_attempts) {
    $candidate = "APP." . date("Y") . "." . str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT);

    // Check if code exists
    $check_sql = "SELECT COUNT(*) as cnt FROM credit_applications WHERE hstd_code = ?";
    if ($check_stmt = mysqli_prepare($link, $check_sql)) {
        mysqli_stmt_bind_param($check_stmt, "s", $candidate);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);
        $row = mysqli_fetch_assoc($result);

        if ($row['cnt'] == 0) {
            $hstd_code = $candidate; // Unique!
        }
        mysqli_stmt_close($check_stmt);
    }
    $attempt++;
}

if ($hstd_code === null) {
    error_log("Failed to generate unique HSTD code after {$max_attempts} attempts");
    $error = "Lỗi hệ thống. Vui lòng thử lại.";
}
```

**Alternative (better):** Use AUTO_INCREMENT sequence table:
```sql
CREATE TABLE application_code_sequence (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Then in PHP:
INSERT INTO application_code_sequence (year) VALUES (YEAR(CURDATE()));
$seq = mysqli_insert_id($link);
$hstd_code = "APP." . date("Y") . "." . str_pad($seq, 6, '0', STR_PAD_LEFT);
```

---

### 🐛 BUG-007: Collateral Type Column Name Mismatch

**File:** `application_detail.php:258`
**Severity:** HIGH (duplicate of BUG-003)
**Impact:** Dropdown will show empty values (broken UI)

**Current code:**
```php
<option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
```

**Problem:**
- `collateral_types` table uses column `type_name`, NOT `name`
- Same bug as BUG-003 (which we fixed in functions.php)
- Will show blank dropdown options

**Fix:**
```php
<option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['type_name']); ?></option>
```

**Location:** Line 258 in collateral section

---

### 🐛 BUG-008: Missing Implementation - Add Collateral/Repayment

**File:** `process_action.php:109-114`
**Severity:** HIGH
**Impact:** Critical features completely non-functional

**Current code:**
```php
if ($action === 'add_collateral' || $action === 'add_repayment') {
    // Logic for adding collateral or repayment
    // ... (Implementation omitted for brevity, but would insert into respective tables)
    header("location: application_detail.php?id=" . $application_id);
    exit;
}
```

**Problem:**
- Comment says "omitted for brevity" - this is PRODUCTION CODE!
- Users can click "Thêm TSBĐ" and "Thêm Nguồn" buttons in application_detail.php
- But process_action.php does NOTHING - just redirects back
- Data is never inserted into database

**Expected behavior:**
1. User fills form in application_detail.php (lines 253-267 for collateral)
2. Submits with `action=add_collateral`
3. process_action.php should INSERT into `application_collaterals` table

**Fix for add_collateral:**
```php
if ($action === 'add_collateral') {
    $collateral_type_id = (int)($_POST['collateral_type_id'] ?? 0);
    $description = trim($_POST['collateral_description'] ?? '');
    $value = $_POST['collateral_value'] ?? '';

    // Validation
    if ($collateral_type_id <= 0 || empty($description) || empty($value)) {
        header("location: application_detail.php?id=" . $application_id . "&error=collateral_required");
        exit;
    }

    if (!is_numeric($value) || $value <= 0) {
        header("location: application_detail.php?id=" . $application_id . "&error=collateral_value_invalid");
        exit;
    }

    $sql = "INSERT INTO application_collaterals (application_id, collateral_type_id, description, value, added_by_id)
            VALUES (?, ?, ?, ?, ?)";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "iisdi", $application_id, $collateral_type_id, $description, $value, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            error_log("Collateral added: app_id={$application_id}, type={$collateral_type_id}, user={$user_id}");
        } else {
            error_log("Collateral insert failed: " . mysqli_error($link));
        }
        mysqli_stmt_close($stmt);
    }

    header("location: application_detail.php?id=" . $application_id);
    exit;
}
```

**Fix for add_repayment:**
```php
if ($action === 'add_repayment') {
    $source_type = trim($_POST['repayment_source_type'] ?? '');
    $description = trim($_POST['repayment_description'] ?? '');
    $monthly_income = $_POST['repayment_monthly_income'] ?? '';

    // Validation
    if (empty($source_type) || empty($description) || empty($monthly_income)) {
        header("location: application_detail.php?id=" . $application_id . "&error=repayment_required");
        exit;
    }

    if (!is_numeric($monthly_income) || $monthly_income <= 0) {
        header("location: application_detail.php?id=" . $application_id . "&error=repayment_income_invalid");
        exit;
    }

    $sql = "INSERT INTO application_repayment_sources
            (application_id, source_type, source_description, estimated_monthly_amount, verified_by_id)
            VALUES (?, ?, ?, ?, ?)";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "issdi", $application_id, $source_type, $description, $monthly_income, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            error_log("Repayment source added: app_id={$application_id}, type={$source_type}, user={$user_id}");
        } else {
            error_log("Repayment insert failed: " . mysqli_error($link));
        }
        mysqli_stmt_close($stmt);
    }

    header("location: application_detail.php?id=" . $application_id);
    exit;
}
```

---

### ⚠️ SECURITY-001: Direct File Access (MINOR)

**File:** `application_detail.php:307`
**Severity:** MEDIUM
**Impact:** Users can potentially access files directly if they know the path

**Current code:**
```php
<a href="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="text-blue-600 hover:underline text-sm">Xem</a>
```

**Problem:**
- Direct link to `uploads/` directory
- If directory listing is enabled OR user guesses filenames, could access other users' documents
- No access control check before serving file

**Recommended fix:**
Create a `download_document.php` file:
```php
<?php
require_once "config/session.php";
init_secure_session();
require_once "config/db.php";
require_once "includes/functions.php";

if (!isset($_SESSION["loggedin"])) {
    http_response_code(403);
    die("Access denied");
}

$doc_id = (int)($_GET['id'] ?? 0);
if ($doc_id <= 0) {
    http_response_code(400);
    die("Invalid document ID");
}

// Get document info
$sql = "SELECT ad.*, ca.created_by_id, ca.assigned_to_id
        FROM application_documents ad
        JOIN credit_applications ca ON ad.application_id = ca.id
        WHERE ad.id = ?";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $doc_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $doc = mysqli_fetch_assoc($result);

    if (!$doc) {
        http_response_code(404);
        die("Document not found");
    }

    // Check access rights (same as application_detail.php)
    $user_id = $_SESSION['id'];
    $user_role = $_SESSION['role'];

    if ($user_role !== 'Admin' &&
        $doc['created_by_id'] != $user_id &&
        $doc['assigned_to_id'] != $user_id) {
        http_response_code(403);
        die("Access denied");
    }

    // Serve file
    $file_path = 'uploads/' . basename($doc['file_path']);
    if (file_exists($file_path)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $doc['document_name'] . '"');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    } else {
        http_response_code(404);
        die("File not found on server");
    }

    mysqli_stmt_close($stmt);
}
?>
```

Then change application_detail.php line 307:
```php
<a href="download_document.php?id=<?php echo $doc['id']; ?>" target="_blank" class="text-blue-600 hover:underline text-sm">Xem</a>
```

**Also add to .htaccess:**
```apache
# Deny direct access to uploads directory
<Directory "uploads">
    Order Deny,Allow
    Deny from all
</Directory>
```

---

### ⚠️ PERFORMANCE-001: Multiple Database Queries on Page Load

**File:** `application_detail.php:84-109`
**Severity:** LOW
**Impact:** Slow page load with 12+ separate queries

**Current approach:**
```php
$customer = get_customer_by_id($link, $app['customer_id']);  // Query 1
$credit_ratings = get_credit_ratings_for_customer($link, $app['customer_id']);  // Query 2
$related_parties = get_related_parties_for_customer($link, $app['customer_id']);  // Query 3
// ... 9 more queries
```

**Impact:**
- Each query = 1 round trip to database
- On slow networks or remote DB, this adds significant latency
- 12 queries × 10ms avg = 120ms just for queries

**Recommended optimization:**
Use JOINs to fetch related data in fewer queries:

```php
// Single query to get app + customer + product
$sql = "SELECT ca.*,
               c.*,
               p.name as product_name
        FROM credit_applications ca
        LEFT JOIN customers c ON ca.customer_id = c.id
        LEFT JOIN products p ON ca.product_id = p.id
        WHERE ca.id = ?";

// Then separate queries only for arrays (collaterals, documents, etc.)
```

**Note:** This is LOW priority - current implementation works fine for typical load. Only optimize if experiencing performance issues.

---

## SUMMARY TABLE

| Bug ID | File | Severity | Issue | Status |
|--------|------|----------|-------|--------|
| BUG-006 | create_application.php:37 | MEDIUM | Code collision risk | ⏳ Pending |
| BUG-007 | application_detail.php:258 | HIGH | Column name mismatch | ⏳ Pending |
| BUG-008 | process_action.php:109-114 | HIGH | Missing implementation | ⏳ Pending |
| SECURITY-001 | application_detail.php:307 | MEDIUM | Direct file access | ⏳ Pending |
| PERFORMANCE-001 | application_detail.php:84-109 | LOW | Multiple queries | ⏳ Pending |

**Total Bugs:** 5 (2 HIGH, 1 MEDIUM, 2 LOW)

---

## POSITIVE FINDINGS ✨

**Outstanding code quality in many areas:**

1. **File Upload Security (process_action.php:117-208)**
   - MIME type validation with `finfo`
   - Extension whitelist
   - File size limits
   - Random filename generation
   - Path traversal protection
   - **Grade: A+**

2. **IDOR Protection (application_detail.php:36-81)**
   - Checks assigned_to_id
   - Checks created_by_id
   - Checks application_history for involvement
   - Comprehensive access control
   - **Grade: A+**

3. **CSRF Protection**
   - Tokens on ALL forms
   - Verification before processing
   - **Grade: A**

4. **SQL Injection Prevention**
   - 100% prepared statements
   - No string concatenation
   - **Grade: A+**

5. **Input Validation**
   - Type casting for all IDs
   - Length limits on text fields
   - Numeric validation
   - **Grade: A**

---

## RECOMMENDATIONS

### Critical (Fix before production):
1. ✅ Fix BUG-008 - Implement add_collateral and add_repayment
2. ✅ Fix BUG-007 - Use correct column name `type_name`

### Important (Fix soon):
3. ✅ Fix BUG-006 - Add unique code generation with database check
4. ✅ Fix SECURITY-001 - Implement download_document.php with access control

### Optional (Nice to have):
5. ⚪ PERFORMANCE-001 - Optimize queries only if needed

---

## CODE QUALITY RATING

| Aspect | Rating | Notes |
|--------|--------|-------|
| Security | 9.5/10 | Professional-grade, few improvements possible |
| Code Organization | 8/10 | Well-structured, good separation of concerns |
| Error Handling | 7/10 | Good logging, could use more user-friendly messages |
| Performance | 7/10 | Acceptable, room for optimization |
| Completeness | 6/10 | Missing add_collateral/repayment implementation |
| Documentation | 6/10 | Some comments, could use more inline docs |

**Overall Module Health: B+ (85/100)**

Main issues are missing implementations (BUG-008) and a few minor bugs. Once these are fixed, this module will be production-ready.

---

## NEXT STEPS

1. Fix BUG-006, BUG-007, BUG-008
2. Implement SECURITY-001 (download_document.php)
3. Continue Phase 3.2: Customer Management Module audit

---

**Audited by:** Claude Code - Phase 3.1
**Date:** 2025-10-30
**Lines audited:** 1,504 lines
**Time:** ~45 minutes
