# Manual QA Checklist

## Activation & Schema
1. Activate the plugin in WP admin.
2. Confirm no fatal errors.
3. Verify DB tables exist:
   - `{prefix}habeq_inventory`
   - `{prefix}habeq_bookings`

## Event Creation & Inventory
1. Create an Event (Events → Add New).
2. Set Capacity and save.
3. Verify event meta saved and inventory row created/updated.

## Frontend Booking
1. Open a published event on the frontend.
2. Submit booking form with Name, Email, Quantity.
3. Confirm reserved count increments in `habeq_inventory`.
4. Confirm confirmation/admin emails are triggered (email logs acceptable).
5. Cancel a booking via DB or admin tools and confirm reserved count decrements.

## Organizer Portal
1. Visit Organizer Portal page.
2. Sign up as organizer and verify pending approval state (if required).
3. Log in and confirm dashboard loads.
4. As pending organizer, verify event creation tabs are blocked.
5. As approved organizer, create an event and confirm status (pending or published per settings).
6. As approved organizer, confirm bookings view is restricted to own events.

## Organizer/Admin Workflow
1. Admin approves organizer (role/meta updates).
2. Organizer creates event; admin publishes if pending.
3. Verify organizer can view bookings for their events only.
