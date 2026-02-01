<?php
/**
 * Portal new event template.
 *
 * @package HabaqEvents
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

$is_approved = class_exists( 'Habeq_Portal' ) ? Habeq_Portal::is_current_user_approved_organizer() : false;
$status      = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
$error       = isset( $_GET['error'] ) ? sanitize_key( wp_unslash( $_GET['error'] ) ) : '';
$event_id    = isset( $_GET['event_id'] ) ? absint( wp_unslash( $_GET['event_id'] ) ) : 0;

$error_messages = array(
	'not_allowed'   => __( 'You do not have access to manage events.', 'habeq' ),
	'invalid_event' => __( 'Invalid event request.', 'habeq' ),
	'missing_title' => __( 'Please provide an event title.', 'habeq' ),
	'create_failed' => __( 'Unable to create the event.', 'habeq' ),
	'update_failed' => __( 'Unable to update the event.', 'habeq' ),
);

$messages = array(
	'event_created' => __( 'Event created and awaiting review.', 'habeq' ),
	'event_updated' => __( 'Event updated.', 'habeq' ),
);

$event_post = null;
if ( $event_id ) {
	$event_post = get_post( $event_id );
	if ( ! $event_post || 'habeq_event' !== $event_post->post_type || (int) $event_post->post_author !== get_current_user_id() ) {
		$event_post = null;
		$event_id   = 0;
		$status     = 'error';
		$error      = 'not_allowed';
	}
}

$title       = $event_post ? $event_post->post_title : '';
$description = $event_post ? $event_post->post_content : '';
$start       = $event_post ? get_post_meta( $event_post->ID, '_habeq_start', true ) : '';
$end         = $event_post ? get_post_meta( $event_post->ID, '_habeq_end', true ) : '';
$venue_name  = $event_post ? get_post_meta( $event_post->ID, '_habeq_venue_name', true ) : '';
$venue_addr  = $event_post ? get_post_meta( $event_post->ID, '_habeq_venue_address', true ) : '';
$capacity    = $event_post ? get_post_meta( $event_post->ID, '_habeq_capacity', true ) : '';

$action = $event_post ? 'habeq_event_update' : 'habeq_event_create';
$nonce_action = $event_post ? 'habeq_portal_event_update' : 'habeq_portal_event_create';
?>
<section class="habeq-portal__panel habeq-portal__panel--new-event">
	<h2><?php echo esc_html( $event_post ? __( 'Edit Event', 'habeq' ) : __( 'New Event', 'habeq' ) ); ?></h2>
	<?php if ( 'error' === $status && $error ) : ?>
		<p class="habeq-portal__notice habeq-portal__notice--error"><?php echo esc_html( $error_messages[ $error ] ?? __( 'Unable to save event.', 'habeq' ) ); ?></p>
	<?php elseif ( $status && isset( $messages[ $status ] ) ) : ?>
		<p class="habeq-portal__notice habeq-portal__notice--success"><?php echo esc_html( $messages[ $status ] ); ?></p>
	<?php endif; ?>
	<?php if ( ! $is_approved ) : ?>
		<p><?php esc_html_e( 'You do not have access to create events yet.', 'habeq' ); ?></p>
	<?php else : ?>
		<form class="habeq-portal__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( $nonce_action, 'habeq_portal_event_nonce' ); ?>
			<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>" />
			<?php if ( $event_post ) : ?>
				<input type="hidden" name="event_id" value="<?php echo esc_attr( $event_post->ID ); ?>" />
			<?php endif; ?>
			<p>
				<label for="habeq-portal-event-title"><?php esc_html_e( 'Title', 'habeq' ); ?></label>
				<input id="habeq-portal-event-title" name="title" type="text" value="<?php echo esc_attr( $title ); ?>" required />
			</p>
			<p>
				<label for="habeq-portal-event-description"><?php esc_html_e( 'Description', 'habeq' ); ?></label>
				<textarea id="habeq-portal-event-description" name="description" rows="5"><?php echo esc_textarea( $description ); ?></textarea>
			</p>
			<p>
				<label for="habeq-portal-event-start"><?php esc_html_e( 'Start Date/Time', 'habeq' ); ?></label>
				<input id="habeq-portal-event-start" name="start_datetime" type="text" value="<?php echo esc_attr( $start ); ?>" />
			</p>
			<p>
				<label for="habeq-portal-event-end"><?php esc_html_e( 'End Date/Time', 'habeq' ); ?></label>
				<input id="habeq-portal-event-end" name="end_datetime" type="text" value="<?php echo esc_attr( $end ); ?>" />
			</p>
			<p>
				<label for="habeq-portal-event-venue-name"><?php esc_html_e( 'Venue Name', 'habeq' ); ?></label>
				<input id="habeq-portal-event-venue-name" name="venue_name" type="text" value="<?php echo esc_attr( $venue_name ); ?>" />
			</p>
			<p>
				<label for="habeq-portal-event-venue-address"><?php esc_html_e( 'Venue Address', 'habeq' ); ?></label>
				<input id="habeq-portal-event-venue-address" name="venue_address" type="text" value="<?php echo esc_attr( $venue_addr ); ?>" />
			</p>
			<p>
				<label for="habeq-portal-event-capacity"><?php esc_html_e( 'Capacity', 'habeq' ); ?></label>
				<input id="habeq-portal-event-capacity" name="capacity" type="number" min="0" value="<?php echo esc_attr( $capacity ); ?>" />
			</p>
			<button type="submit"><?php echo esc_html( $event_post ? __( 'Save Changes', 'habeq' ) : __( 'Create Event', 'habeq' ) ); ?></button>
		</form>
	<?php endif; ?>
</section>
