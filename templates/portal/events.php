<?php
/**
 * Portal events template.
 *
 * @package HabaqEvents
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

$is_approved = class_exists( 'Habeq_Portal' ) ? Habeq_Portal::is_current_user_approved_organizer() : false;
$status      = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
$error       = isset( $_GET['error'] ) ? sanitize_key( wp_unslash( $_GET['error'] ) ) : '';
$messages    = array(
	'event_created' => __( 'Event created and awaiting review.', 'habeq' ),
	'event_updated' => __( 'Event updated.', 'habeq' ),
);

$error_messages = array(
	'not_allowed'  => __( 'You do not have access to manage events.', 'habeq' ),
	'invalid_event' => __( 'Invalid event request.', 'habeq' ),
);
?>
<section class="habeq-portal__panel habeq-portal__panel--events">
	<h2><?php esc_html_e( 'Events', 'habeq' ); ?></h2>
	<?php if ( 'error' === $status && $error ) : ?>
		<p class="habeq-portal__notice habeq-portal__notice--error"><?php echo esc_html( $error_messages[ $error ] ?? __( 'Unable to manage events.', 'habeq' ) ); ?></p>
	<?php elseif ( $status && isset( $messages[ $status ] ) ) : ?>
		<p class="habeq-portal__notice habeq-portal__notice--success"><?php echo esc_html( $messages[ $status ] ); ?></p>
	<?php endif; ?>
	<?php if ( ! $is_approved ) : ?>
		<p><?php esc_html_e( 'You do not have access to manage events yet.', 'habeq' ); ?></p>
	<?php else : ?>
		<?php
		$events = get_posts(
			array(
				'post_type'      => 'habeq_event',
				'author'         => get_current_user_id(),
				'post_status'    => array( 'publish', 'pending', 'draft', 'future', 'private' ),
				'posts_per_page' => 20,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		?>
		<?php if ( empty( $events ) ) : ?>
			<p><?php esc_html_e( 'No events yet. Create your first event.', 'habeq' ); ?></p>
		<?php else : ?>
			<ul class="habeq-portal__list">
				<?php foreach ( $events as $event ) : ?>
					<?php $edit_url = function_exists( 'habeq_portal_url' ) ? habeq_portal_url( 'new-event', array( 'event_id' => $event->ID ) ) : '#'; ?>
					<li>
						<strong><?php echo esc_html( get_the_title( $event ) ); ?></strong>
						<span class="habeq-portal__status"><?php echo esc_html( ucfirst( $event->post_status ) ); ?></span>
						<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'habeq' ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	<?php endif; ?>
</section>
