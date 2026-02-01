<?php
/**
 * Portal bookings template.
 *
 * @package HabaqEvents
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}
?>
<section class="habeq-portal__panel habeq-portal__panel--bookings">
	<h2><?php esc_html_e( 'Bookings', 'habeq' ); ?></h2>
	<?php if ( ! class_exists( 'Habeq_Portal' ) ) : ?>
		<p><?php esc_html_e( 'Booking management is unavailable.', 'habeq' ); ?></p>
		<?php return; ?>
	<?php endif; ?>

	<?php
	$can_manage   = Habeq_Portal::can_manage_portal_tabs();
	$events       = Habeq_Portal::get_accessible_events();
	$selected_id  = isset( $_GET['event_id'] ) ? absint( wp_unslash( $_GET['event_id'] ) ) : 0;
	$selected_id  = $selected_id ? $selected_id : ( ! empty( $events ) ? (int) $events[0]->ID : 0 );
	$has_events   = ! empty( $events );
	$export_nonce = wp_create_nonce( 'habeq_bookings_export' );
	$event_ids    = wp_list_pluck( $events, 'ID' );
	?>

	<?php if ( ! $can_manage ) : ?>
		<p><?php esc_html_e( 'You do not have access to view bookings yet.', 'habeq' ); ?></p>
	<?php elseif ( ! $has_events ) : ?>
		<p><?php esc_html_e( 'No events found to view bookings.', 'habeq' ); ?></p>
	<?php else : ?>
		<form class="habeq-portal__form" method="get" action="">
			<input type="hidden" name="tab" value="bookings" />
			<label for="habeq-portal-bookings-event"><?php esc_html_e( 'Select Event', 'habeq' ); ?></label>
			<select id="habeq-portal-bookings-event" name="event_id">
				<?php foreach ( $events as $event ) : ?>
					<option value="<?php echo esc_attr( $event->ID ); ?>" <?php selected( $selected_id, $event->ID ); ?>>
						<?php echo esc_html( get_the_title( $event ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<button type="submit"><?php esc_html_e( 'View Bookings', 'habeq' ); ?></button>
		</form>

		<?php
		$has_access = $selected_id && in_array( $selected_id, $event_ids, true );
		$bookings   = $has_access ? Habeq_Portal::get_bookings_for_event( $selected_id ) : array();
		?>

		<?php if ( $selected_id && $has_access ) : ?>
			<p>
				<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'habeq_bookings_export', 'event_id' => $selected_id, 'habeq_bookings_export_nonce' => $export_nonce ), admin_url( 'admin-post.php' ) ) ); ?>">
					<?php esc_html_e( 'Download CSV', 'habeq' ); ?>
				</a>
			</p>
		<?php endif; ?>

		<?php if ( $selected_id && ! $has_access ) : ?>
			<p class="habeq-portal__notice habeq-portal__notice--error"><?php esc_html_e( 'You do not have access to view bookings for this event.', 'habeq' ); ?></p>
		<?php elseif ( empty( $bookings ) ) : ?>
			<p><?php esc_html_e( 'No bookings found for this event.', 'habeq' ); ?></p>
		<?php else : ?>
			<table class="habeq-portal__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Booking ID', 'habeq' ); ?></th>
						<th><?php esc_html_e( 'Name', 'habeq' ); ?></th>
						<th><?php esc_html_e( 'Email', 'habeq' ); ?></th>
						<th><?php esc_html_e( 'Qty', 'habeq' ); ?></th>
						<th><?php esc_html_e( 'Status', 'habeq' ); ?></th>
						<th><?php esc_html_e( 'Created', 'habeq' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $bookings as $booking ) : ?>
						<tr>
							<td><?php echo esc_html( $booking['id'] ); ?></td>
							<td><?php echo esc_html( $booking['name'] ); ?></td>
							<td><?php echo esc_html( $booking['email'] ); ?></td>
							<td><?php echo esc_html( $booking['qty'] ); ?></td>
							<td><?php echo esc_html( $booking['status'] ); ?></td>
							<td><?php echo esc_html( $booking['created_at'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	<?php endif; ?>
</section>
