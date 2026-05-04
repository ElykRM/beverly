# Beverly HOA System - Setup & Configuration Guide

## 📋 What Has Been Completed

### 1. **Database Migration Script** ✓
- **Location**: `migrations/add_membership_columns.php`
- **Purpose**: Safely adds membership columns to the `households` table if they don't exist
- **Safe to run**: Multiple times without errors (checks for existing columns first)
- **How to run**: Navigate to `http://localhost/beverly/migrations/add_membership_columns.php` in your browser

### 2. **Centralized Configuration File** ✓
- **Location**: `config.php` (in the root)
- **Purpose**: Single source of truth for system-wide settings
- **Contains**:
  - Year selection range (default: 2017 to current year + 5)
  - Global monthly dues amount (default: ₱100.00)
  - Per-year dues overrides
  - Per-year/month dues overrides
  - Promotional payment settings (default: ₱1,000.00/year)
  - Helper functions: `getDueAmount()`, `getYearRange()`, `getMonthNames()`, `getConfigSummary()`

**Usage Example**:
```php
include '../config.php';

// Get the due amount for June 2024 (applies overrides in order)
$amount = getDueAmount(2024, 6);  // Returns 100.00 if no override exists

// Get all year options for a dropdown
$years = getYearRange();

// Get formatted month names
$months = getMonthNames();
```

### 3. **Updated Pages to Use Centralized Config** ✓
- **pages/dues.php**: Now uses `config.php` for year range and dues calculations
- **pages/payment.php**: Now uses `config.php` for year range and promo settings
- **actions/save_payment.php**: Included `config.php` for consistency

**Benefits**:
- ✅ No more duplicate configuration across files
- ✅ Change settings once, apply system-wide
- ✅ Easier to maintain and debug

### 4. **Membership Fields** (Code Changes) ✓
All PHP code has been updated to support membership tracking:
- ✅ Database schema (db.sql) includes three new columns
- ✅ New household form (actions/add.php) includes membership inputs
- ✅ Edit household form (actions/edit.php) includes membership inputs
- ✅ Save handlers (save_household.php, update_household.php) save membership data
- ✅ View page (actions/view.php) displays membership info with safety guards

---

## 🚀 Next Steps

### Step 1: Apply Database Migration
Run the migration script to add membership columns to your database:

**Option A: Browser (Easiest)**
1. Start XAMPP (Apache + MySQL)
2. Open: `http://localhost/beverly/migrations/add_membership_columns.php`
3. You'll see a confirmation message when complete

**Option B: Command Line (Alternative)**
```bash
php "c:\Users\BSD-QA\Desktop\XAMPP\htdocs\beverly\migrations\add_membership_columns.php"
```

**Option C: Direct MySQL (If needed)**
If migration script doesn't work, run this manually in phpMyAdmin or MySQL CLI:
```sql
ALTER TABLE households 
  ADD COLUMN membership_date DATE NULL COMMENT 'Date of membership (optional)',
  ADD COLUMN membership_or_no VARCHAR(50) NULL COMMENT 'Membership OR/INV no. (optional)',
  ADD COLUMN membership_fee DECIMAL(10,2) NULL COMMENT 'Membership fee (optional)';
```

### Step 2: Customize Configuration (Optional)
Edit `config.php` to adjust system settings:

**Example: Add year-specific dues increase**
```php
$yearlyDefaultDues = [
    2024 => 120.00,  // 2024: ₱120.00/month
    2025 => 150.00,  // 2025: ₱150.00/month
];
```

**Example: Add month-specific overrides**
```php
$yearlyMonthlyOverrides = [
    2024 => [
        1 => 200.00,   // January 2024: ₱200.00
        12 => 250.00,  // December 2024: ₱250.00
    ],
    2025 => [
        6 => 300.00,   // June 2025: ₱300.00
    ],
];
```

**Example: Change promo amount**
```php
define('PROMO_AMOUNT', 1200.00);  // Change from 1000 to 1200
```

### Step 3: Test the System
1. **Dues Page**: Go to Dues tab, change year → verify amounts show correctly
2. **Payment Page**: Record a payment → check it appears on Dues page
3. **Membership**: Add new household → fill in membership fields → verify they're saved and visible

---

## 📊 Configuration Resolution Order

### For Monthly Dues Amounts
When displaying/calculating dues for a specific month:
1. **Check**: Per-month override (`$yearlyMonthlyOverrides[year][month]`)
2. **Else check**: Per-year default (`$yearlyDefaultDues[year]`)
3. **Else use**: Global default (`DEFAULT_MONTHLY_DUE` = ₱100.00)

**Example Calculation**:
```
For June 2024:
- Check 2024 → month 6 override? → Not set
- Check 2024 → year default? → Yes → Use ₱120.00
- If neither exist, use global ₱100.00
```

---

## 🔄 How Promo Payments Work

When `is_promo = 1`:
- ✅ **Period**: Forced to January - December (regardless of UI selection)
- ✅ **Amount**: User can edit, but must stay Jan-Dec
- ✅ **Reporting**: 
  - Months 1-10 (Jan-Oct): Counted toward monthly totals
  - Months 11-12 (Nov-Dec): Tagged as "Promo" but not counted in calculations

**Example**:
- User records ₱1,000 promo for 2024
- System stores: `period_month=1, period_to_month=12, is_promo=1, amount=1000`
- Per-month displayed: ₱100 (1000 ÷ 10) for Jan-Oct, "Promo" label for Nov-Dec

---

## 📁 Project Structure (Updated)

```
beverly/
├── config.php                      ← NEW: Centralized configuration
├── db.php                          ← PDO connection (unchanged)
├── db.sql                          ← Database schema (updated with membership)
├── index.php                       ← Root redirect
│
├── pages/
│   ├── dues.php                    ← Updated: Uses config.php
│   ├── payment.php                 ← Updated: Uses config.php
│   ├── habitants.php
│   ├── login.php
│   ├── register.php
│   └── ...
│
├── actions/
│   ├── save_payment.php            ← Updated: Includes config.php
│   ├── save_household.php          ← Updated: Saves membership fields
│   ├── update_household.php        ← Updated: Updates membership fields
│   ├── add.php                     ← Updated: Membership form inputs
│   ├── edit.php                    ← Updated: Membership form inputs
│   ├── view.php                    ← Updated: Displays membership with guards
│   └── ...
│
├── includes/
│   ├── auth.php                    ← Session/role management
│   ├── header.php
│   ├── footer.php
│   └── ...
│
├── migrations/
│   └── add_membership_columns.php   ← NEW: Database migration script
│
└── ...
```

---

## ✅ Verification Checklist

After completing setup:

- [ ] Migration script runs without errors
- [ ] Three new membership columns visible in phpMyAdmin (households table)
- [ ] Can add new household and fill membership fields
- [ ] Can edit household and update membership fields
- [ ] Membership data appears in household view
- [ ] Dues page shows correct amounts (respects config)
- [ ] Payment page allows editing amounts
- [ ] Config changes take effect immediately (no restart needed)

---

## 🆘 Troubleshooting

### Issue: "Column 'membership_date' already exists" error
**Solution**: Run the migration script again — it checks for existing columns and skips them.

### Issue: Membership fields not showing in household view
**Solution**: 
1. Run migration script to add columns to DB
2. Clear browser cache (Ctrl+F5)
3. Refresh the household view page

### Issue: Year dropdown shows wrong range
**Solution**: Check `START_YEAR` in `config.php` — it's currently set to 2017

### Issue: Dues amounts not changing when I edit config.php
**Solution**: 
1. Clear browser cache
2. Reload the dues page
3. Verify you modified the correct variable in `config.php`

### Issue: Can't run migration script in browser
**Solution**: 
1. Ensure XAMPP Apache + MySQL are running
2. Try accessing: `http://localhost/beverly/` first (should work)
3. Try command line alternative: `php c:\Users\BSD-QA\Desktop\XAMPP\htdocs\beverly\migrations\add_membership_columns.php`

---

## 📝 Notes

- All configuration changes are **immediate** (no server restart needed)
- The system is **backward-compatible** — membership fields are optional
- Existing payments, dues, and exemptions work as before
- The `getDueAmount()` helper makes it easy to add membership-based pricing logic later if needed

---

**Last Updated**: May 4, 2026  
**System**: Beverly HOA Management  
**Database**: MySQL with PDO
