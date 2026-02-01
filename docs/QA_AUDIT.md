# QA Audit Report

## Overview
Audit of the Habaq Events plugin focused on security, data integrity, reliability, and performance. Items below list severity, location, impact, and fix plan/status.

## Security

### Medium: Un-sanitized action keys in admin and public handlers
- **File/Line:** `includes/class-habeq-cpt-event.php` (render_tools_page, render_organizer_dashboard, render_bookings_page, handle_booking_submission).
- **Impact:** Raw `$_POST` values were compared without sanitization, which violates the input-sanitization requirement and can lead to unexpected behavior if malformed input is supplied.
- **Fix Plan/Status:** Sanitize action values via `sanitize_key( wp_unslash( ... ) )` before comparisons. **Fixed.**

### Medium: Unescaped output for organizer dashboard event links
- **File/Line:** `includes/class-habeq-cpt-event.php` (render_organizer_dashboard).
- **Impact:** Event titles/permalinks were output without explicit escaping; a malicious title could introduce XSS.
- **Fix Plan/Status:** Use `esc_url( get_permalink() )` and `esc_html( get_the_title() )` for output. **Fixed.**

### Medium: Missing service-layer booking rate limit
- **File/Line:** `includes/class-habeq-bookings.php` (create_booking).
- **Impact:** Automated submissions could bypass the controller-level rate limit by calling the service directly.
- **Fix Plan/Status:** Add transient-based rate limit keyed by event+email for 2 minutes at the service layer. **Fixed.**

### Medium: Organizer signup lacked rate limit
- **File/Line:** `includes/class-habeq-portal.php` (handle_signup).
- **Impact:** Repeated signup attempts could be abused to spam admin notifications.
- **Fix Plan/Status:** Add transient-based rate limit keyed by email and IP for 10 minutes. **Fixed.**

## Data Integrity

### High: Capacity updates could drop below reserved count
- **File/Line:** `includes/class-habeq-db.php` (maybe_ensure_inventory_row).
- **Impact:** Updating capacity for an event could set capacity below current reserved, creating a negative availability scenario.
- **Fix Plan/Status:** Clamp capacity to at least the existing reserved count before updating. **Fixed.**

### High: Duplicate booking could consume capacity twice
- **File/Line:** `includes/class-habeq-bookings.php` (create_booking).
- **Impact:** Multiple active bookings with the same email could reserve capacity multiple times.
- **Fix Plan/Status:** Check for active booking by event+email before reserving and return `already_booked`. **Fixed.**

### High: Insert failures could leave ghost reservations
- **File/Line:** `includes/class-habeq-bookings.php` (create_booking).
- **Impact:** If booking insert failed, reserved capacity could remain incremented.
- **Fix Plan/Status:** Roll back reserved capacity with `GREATEST` and return `already_booked` or `db_insert_failed`. **Fixed.**

## Reliability

### Medium: No automated test harness for activation/booking/portal flows
- **File/Line:** N/A (project-level).
- **Impact:** Regressions in activation, schema, booking, and portal flows could go undetected.
- **Fix Plan/Status:** Added WP integration test harness, PHPUnit config, and critical-path tests. **Fixed.**

## Performance

### Low: None identified
- Current queries are bounded and indexed (inventory primary key, bookings indexes on event/user/status).
- No N+1 issues found in critical paths.
