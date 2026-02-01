<?php
/**
 * Portal wrapper template.
 *
 * @package HabaqEvents
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

$portal_tabs = array(
	'login'     => __( 'Login', 'habeq' ),
	'signup'    => __( 'Sign Up', 'habeq' ),
	'dashboard' => __( 'Dashboard', 'habeq' ),
	'events'    => __( 'Events', 'habeq' ),
	'new-event' => __( 'New Event', 'habeq' ),
	'bookings'  => __( 'Bookings', 'habeq' ),
);

$active_tab     = isset( $portal_tab ) ? $portal_tab : 'login';
$portal_status  = isset( $portal_status ) ? $portal_status : 'guest';
$allow_signup   = '1' === (string) get_option( 'habeq_allow_signup', '1' );
$is_logged_in   = is_user_logged_in();
$is_approved    = class_exists( 'Habeq_Portal' ) ? Habeq_Portal::can_manage_portal_tabs() : false;
$restricted_tab = in_array( $active_tab, array( 'dashboard', 'events', 'new-event', 'bookings' ), true );

if ( ! $allow_signup ) {
	unset( $portal_tabs['signup'] );
}

if ( $is_logged_in && ! $is_approved ) {
	unset( $portal_tabs['events'], $portal_tabs['new-event'], $portal_tabs['bookings'] );
	if ( $restricted_tab ) {
		$active_tab = 'pending';
	}
}
?>
<div class="habeq-portal">
	<header class="habeq-portal__header">
		<h1 class="habeq-portal__title"><?php esc_html_e( 'Organizer Portal', 'habeq' ); ?></h1>
		<?php if ( $is_logged_in ) : ?>
			<form class="habeq-portal__logout" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'habeq_portal_logout', 'habeq_portal_logout_nonce' ); ?>
				<input type="hidden" name="action" value="habeq_logout" />
				<button type="submit" class="habeq-portal__logout-button"><?php esc_html_e( 'Log out', 'habeq' ); ?></button>
			</form>
		<?php endif; ?>
	</header>
	<nav class="habeq-portal__nav" aria-label="<?php esc_attr_e( 'Portal Navigation', 'habeq' ); ?>">
		<ul class="habeq-portal__nav-list">
			<?php foreach ( $portal_tabs as $slug => $label ) : ?>
				<?php
				$item_class = $slug === $active_tab ? 'is-active' : '';
				$url        = function_exists( 'habeq_portal_url' ) ? habeq_portal_url( $slug ) : '#';
				?>
				<li class="habeq-portal__nav-item <?php echo esc_attr( $item_class ); ?>">
					<a class="habeq-portal__nav-link" href="<?php echo esc_url( $url ); ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<div class="habeq-portal__content">
		<?php
		if ( ! empty( $portal_content_path ) && file_exists( $portal_content_path ) ) {
			require $portal_content_path;
		} else {
			echo '<p>' . esc_html__( 'Portal content is unavailable.', 'habeq' ) . '</p>';
		}
		?>
	</div>
</div>
