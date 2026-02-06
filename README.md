# Habaq Events

Habaq Events is a WordPress plugin that will provide event creation, scheduling, and management tools for the Habaq platform.

## Development

### Run PHPCS locally

1. Install dependencies:
   ```bash
   composer install
   ```
2. Run PHPCS:
   ```bash
   vendor/bin/phpcs
   ```

## Releases

Release tags follow the format `vX.Y.Z` (for example, `v0.1.0`).

## Initial Setup

On activation, Habaq Events runs an idempotent setup that creates required pages and menus. You can also run it manually:

1. Go to **Events → Habaq Events Setup** in WP admin.
2. Click **Run Setup**.

The setup creates/stores these pages and menu IDs:

- Organizer Portal (`organizer`) with `[habeq_portal]`
- Booking Confirmed (`booking-confirmed`) with `[habeq_booking_confirmation]`
- Manage Booking (`manage-booking`) with `[habeq_manage_booking]` (draft unless published)
- Refunds & Cancellations (`refunds`) (draft)
- Privacy Policy (`privacy`) (draft, or reuses the WP privacy policy page if set)
- Terms (`terms`) (draft)
- Contact (`contact`) (draft)

Menus created:

- **Habaq Events – Main**: Events archive, Organizer Portal, optional Manage Booking/Contact.
- **Habaq Events – Footer**: Privacy, Refunds, Terms, Contact.

## Staging Mode

To prevent user-facing emails in staging, enable staging mode:

```php
define( 'HABEQ_STAGING', true );
```

When enabled, plugin-generated email subjects are prefixed with `[STAGING]` and user-facing booking emails are suppressed (admin notifications still send).
