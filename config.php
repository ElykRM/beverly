<?php
/**
 * Beverly HOA - Central Configuration
 * 
 * This file contains all configurable settings for:
 * - Dues calculations (monthly amounts, overrides)
 * - Promotional payments (yearly promo pricing)
 * - Year selection (start year for dropdowns)
 * 
 * Update these values to adjust system-wide behavior without editing individual pages.
 */

// ============================================================================
// YEAR CONFIGURATION
// ============================================================================

/**
 * The earliest year shown in year dropdowns (e.g., dues page, payment page).
 * Users can select any year from this year through (current_year + 5).
 */
define('START_YEAR', 2017);

// ============================================================================
// DUES CONFIGURATION
// ============================================================================

/**
 * Global default monthly dues amount.
 * This is used if no per-year or per-month override exists.
 * Used in: pages/dues.php
 */
define('DEFAULT_MONTHLY_DUE', 100.00);

/**
 * Per-year default dues (overrides global default for entire year).
 * Format: [year => amount]
 * Example: [2024 => 120.00, 2023 => 110.00]
 * Leave empty array [] if no per-year defaults.
 */
$yearlyDefaultDues = [
    // 2024 => 120.00,
    // 2023 => 110.00,
];

/**
 * Per-year, per-month dues overrides (highest priority).
 * Format: [year => [month => amount, ...], ...]
 * Month is 1-12 (January=1, December=12)
 * Example: [2024 => [1 => 150.00, 12 => 200.00]]
 * Leave empty array [] if no month-specific overrides.
 */
$yearlyMonthlyOverrides = [
    // 2024 => [1 => 150.00, 12 => 200.00],
];

/**
 * Dues calculation resolution order (highest to lowest priority):
 * 1. $yearlyMonthlyOverrides[year][month]
 * 2. $yearlyDefaultDues[year]
 * 3. DEFAULT_MONTHLY_DUE
 * 
 * This function returns the applicable due amount for a given year/month.
 */
function getDueAmount($year, $month) {
    global $yearlyMonthlyOverrides, $yearlyDefaultDues;
    
    // Check month-specific override
    if (isset($yearlyMonthlyOverrides[$year][$month])) {
        return $yearlyMonthlyOverrides[$year][$month];
    }
    
    // Check year default
    if (isset($yearlyDefaultDues[$year])) {
        return $yearlyDefaultDues[$year];
    }
    
    // Fall back to global default
    return DEFAULT_MONTHLY_DUE;
}

// ============================================================================
// PROMOTIONAL PAYMENT CONFIGURATION
// ============================================================================

/**
 * Yearly promotional payment amount.
 * When a payment is marked as promo, the UI enforces full-year coverage (Jan-Dec)
 * and displays this amount to the user. The user can edit the amount, but the
 * server will still enforce Jan-Dec coverage when is_promo=1.
 * Used in: pages/payment.php, actions/save_payment.php
 */
define('PROMO_AMOUNT', 1000.00);

/**
 * Formatted label for promo amount (used in UI display).
 */
define('PROMO_AMOUNT_LABEL', number_format(PROMO_AMOUNT, 2));

/**
 * Promo behavior note: When is_promo=1, the system treats the payment as
 * covering January through December. For reporting purposes:
 * - Months 1-10 (Jan-Oct) are counted toward monthly dues totals
 * - Months 11-12 (Nov-Dec) are marked with promo tag but excluded from calculations
 * This gives a more accurate picture of which months are actually paid.
 */
define('PROMO_EFFECTIVE_MONTHS', 10);

// ============================================================================
// PAGINATION CONFIGURATION
// ============================================================================

/**
 * Number of records displayed per page in household lists and dues table.
 * Used in: pages/habitants.php, pages/dues.php
 */
define('PAGINATION_PER_PAGE', 10);

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Get all year and month configuration for display/export.
 * Useful for admin settings pages or detailed reports.
 */
function getConfigSummary() {
    global $yearlyDefaultDues, $yearlyMonthlyOverrides;
    
    return [
        'start_year' => START_YEAR,
        'default_monthly_due' => DEFAULT_MONTHLY_DUE,
        'yearly_defaults' => $yearlyDefaultDues,
        'yearly_monthly_overrides' => $yearlyMonthlyOverrides,
        'promo_amount' => PROMO_AMOUNT,
        'promo_effective_months' => PROMO_EFFECTIVE_MONTHS,
    ];
}

/**
 * Get the year range for dropdowns.
 */
function getYearRange() {
    $currentYear = (int)date('Y');
    return range(START_YEAR, $currentYear + 5);
}

/**
 * Get month names for display.
 */
function getMonthNames() {
    return [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
}

/**
 * Get short month names for display.
 */
function getShortMonthNames() {
    return [
        1=>'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'
    ];
}
