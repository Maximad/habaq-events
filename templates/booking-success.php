<?php
/**
 * Booking success message.
 *
 * @package HabaqEvents
 *
 * @var string $booking_success
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}
?>
<div class="habeq-booking-message habeq-booking-success">
	<?php echo esc_html( $booking_success ); ?>
</div>
