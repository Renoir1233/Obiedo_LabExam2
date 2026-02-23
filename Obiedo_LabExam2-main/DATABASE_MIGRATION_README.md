# Database Migration Guide

## 🎯 Overview
This migration updates your database to fix all security and structure issues while preserving existing data.

## ✅ What Gets Fixed

### 1. **Users Table**
- ✅ Password column expanded from 100 to 255 characters (for bcrypt hashes)
- ✅ Email column added (VARCHAR 100, UNIQUE)
- ✅ Role column added (ENUM: 'admin', 'user')
- ✅ Admin password hashed (plaintext 'admin123' → bcrypt hash)
- ✅ Timestamps added (created_at, updated_at)

### 2. **Courses Table** (NEW)
- ✅ Normalized course data (eliminates redundancy)
- ✅ Contains: course_code, course_description
- ✅ Pre-populated with sample courses (BSIT, BSCS, BSIS, BSCE, BSEE)

### 3. **Students Table**
- ✅ course_id foreign key added (references courses.id)
- ✅ created_by foreign key added (references users.id)
- ✅ Timestamps added (created_at, updated_at)
- ✅ Existing course data migrated automatically

### 4. **Login Attempts Table** (NEW)
- ✅ Audit logging for all login attempts
- ✅ Tracks: username, IP address, timestamp, success/failure
- ✅ Indexed for fast queries

### 5. **Foreign Keys**
- ✅ Referential integrity enforced
- ✅ Cascade rules: ON DELETE SET NULL

## 🚀 How to Run Migration

### Method 1: Using Web Interface (Easiest)
1. Open your browser
2. Go to: `http://localhost/Obiedo_LabExam2-main/run_migration.php`
3. Click "Run Migration Now"
4. Wait for completion
5. **Delete `run_migration.php` after success** (security)

### Method 2: Using phpMyAdmin
1. Open phpMyAdmin
2. Select `infosec_lab` database
3. Click "Import" tab
4. Choose `database_migration.sql`
5. Click "Go"

### Method 3: Using MySQL Command Line
```bash
mysql -u root -p infosec_lab < database_migration.sql
```

## 📝 Updated Credentials

After migration, use these credentials:

**Admin Account:**
- Username: `admin`
- Password: `admin123`
- Email: `admin@example.com`
- Role: `admin`

## 🔐 Password Requirements (Updated)

The registration page now enforces strong passwords:

**Requirements:**
- ✅ Minimum 8 characters
- ✅ At least one uppercase letter (A-Z)
- ✅ At least one lowercase letter (a-z)
- ✅ At least one number (0-9)
- ✅ At least one special character (!@#$%^&*(),.?":{}|<>)

**Examples of Valid Passwords:**
- `SecureP@ss123`
- `MyP@ssw0rd!`
- `Admin#2026`

**Examples of Invalid Passwords:**
- `password` ❌ (no uppercase, number, or special char)
- `PASSWORD123` ❌ (no lowercase or special char)
- `Pass@123` ❌ (less than 8 characters)

## 📊 Database Structure (After Migration)

### Users Table
```sql
CREATE TABLE users (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Courses Table
```sql
CREATE TABLE courses (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(50) UNIQUE NOT NULL,
    course_description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Students Table
```sql
CREATE TABLE students (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    course_id INT(11),
    created_by INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

### Login Attempts Table
```sql
CREATE TABLE login_attempts (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) DEFAULT 0
);
```

## ⚠️ Important Notes

1. **Safe Migration:** The script checks if columns/tables exist before creating them
2. **Data Preservation:** All existing data is preserved during migration
3. **Automatic Migration:** Old course data is automatically moved to the new courses table
4. **No Downtime:** Migration runs quickly (usually under 5 seconds)
5. **Rollback:** Keep a backup before migration (optional, but recommended)

## 🔍 Verification

After migration, verify the changes:

```sql
-- Check users table structure
DESCRIBE users;

-- Check if admin password is hashed
SELECT username, password, email, role FROM users WHERE username = 'admin';

-- Check courses table
SELECT * FROM courses;

-- Check students with course relationships
SELECT s.*, c.course_code FROM students s 
LEFT JOIN courses c ON s.course_id = c.id;

-- Check login attempts table
DESCRIBE login_attempts;
```

## 🛡️ Security Improvements

| Before | After |
|--------|-------|
| Password VARCHAR(100) | Password VARCHAR(255) ✅ |
| Admin password: 'admin123' | Admin password: bcrypt hash ✅ |
| No email column | Email with UNIQUE constraint ✅ |
| No role column | Role-based access control ✅ |
| No normalization | Courses table normalized ✅ |
| No foreign keys | Foreign keys enforced ✅ |
| No audit logging | Login attempts tracked ✅ |
| Weak password rules | Strong password requirements ✅ |

## 📞 Troubleshooting

**Issue:** Migration fails with "Table already exists"
- **Solution:** This is normal. The script skips existing items.

**Issue:** Foreign key constraint fails
- **Solution:** Ensure courses table is created first (script handles this).

**Issue:** Can't login after migration
- **Solution:** Use password `admin123` for admin account.

**Issue:** Old students don't have course_id
- **Solution:** The migration automatically assigns course_id based on course name.

## ✅ Post-Migration Checklist

- [ ] Migration completed without errors
- [ ] Can login with admin/admin123
- [ ] Can register new user (test password requirements)
- [ ] Can add new student (course dropdown works)
- [ ] Students table shows courses correctly
- [ ] Delete `run_migration.php` file (security)
- [ ] Create database backup

## 🔄 Rollback (If Needed)

If you need to rollback:

1. Restore from backup:
```bash
mysql -u root -p infosec_lab < backup_before_migration.sql
```

2. Or manually drop new columns:
```sql
ALTER TABLE users DROP COLUMN email;
ALTER TABLE users DROP COLUMN role;
-- etc.
```

---

**Migration File:** `database_migration.sql`
**Web Tool:** `run_migration.php` (delete after use)
**Documentation:** This file (README)

Good luck! 🚀
