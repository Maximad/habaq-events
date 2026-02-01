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

## Data Integrity

### High: Capacity updates could drop below reserved count
- **File/Line:** `includes/class-habeq-db.php` (maybe_ensure_inventory_row).
- **Impact:** Updating capacity for an event could set capacity below current reserved, creating a negative availability scenario.
- **Fix Plan/Status:** Clamp capacity to at least the existing reserved count before updating. **Fixed.**

## Reliability

### Medium: No automated test harness for activation/booking/portal flows
- **File/Line:** N/A (project-level).
- **Impact:** Regressions in activation, schema, booking, and portal flows could go undetected.
- **Fix Plan/Status:** Added WP integration test harness, PHPUnit config, and critical-path tests. **Fixed.**

## Performance

### Low: None identified
- Current queries are bounded and indexed (inventory primary key, bookings indexes on event/user/status).
- No N+1 issues found in critical paths.
