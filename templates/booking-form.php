<?php
/**
 * Booking form template.
 *
 * @package HabaqEvents
 *
 * @var int    $event_id
 * @var int    $capacity
 * @var int    $reserved
 * @var int    $remaining
 * @var string $booking_error
 * @var string $booking_success
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}
?>
<section class="habeq-booking-box">
	<h2><?php esc_html_e( 'Book Your Spot', 'habeq' ); ?></h2>
	<p class="habeq-booking-remaining">
		<?php
		echo esc_html(
			sprintf(
				/* translators: %d: remaining spots */
				__( '%d spots remaining', 'habeq' ),
				$remaining
			)
		);
		?>
	</p>

	<?php if ( $booking_success ) : ?>
		<?php require HABEQ_PATH . 'templates/booking-success.php'; ?>
	<?php elseif ( $booking_error ) : ?>
		<div class="habeq-booking-message habeq-booking-error">
			<?php echo esc_html( $booking_error ); ?>
		</div>
	<?php endif; ?>

	<form method="post" class="habeq-booking-form">
		<?php wp_nonce_field( 'habeq_booking', 'habeq_booking_nonce' ); ?>
		<input type="hidden" name="habeq_booking_action" value="submit_booking" />

		<p class="habeq-field">
			<label for="habeq_name"><?php esc_html_e( 'Name', 'habeq' ); ?></label>
			<input type="text" name="habeq_name" id="habeq_name" required />
		</p>
		<p class="habeq-field">
			<label for="habeq_email"><?php esc_html_e( 'Email', 'habeq' ); ?></label>
			<input type="email" name="habeq_email" id="habeq_email" required />
		</p>
		<p class="habeq-field">
			<label for="habeq_qty"><?php esc_html_e( 'Quantity', 'habeq' ); ?></label>
			<input type="number" name="habeq_qty" id="habeq_qty" min="1" value="1" required />
		</p>

		<p class="habeq-field habeq-honeypot">
			<label for="habeq_company"><?php esc_html_e( 'Company', 'habeq' ); ?></label>
			<input type="text" name="habeq_company" id="habeq_company" autocomplete="off" />
		</p>

		<button type="submit" class="button button-primary">
			<?php esc_html_e( 'Request Booking', 'habeq' ); ?>
		</button>
	</form>
</section>
