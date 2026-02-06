<?php
/**
 * Portal signup template.
 *
 * @package HabaqEvents
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

$status = isset( $habeq_status ) ? $habeq_status : '';
$error  = isset( $habeq_error ) ? $habeq_error : '';
$allow_signup = '1' === (string) get_option( 'habeq_allow_signup', '1' );

$messages = array(
	'signup_disabled' => __( 'Organizer signup is currently closed.', 'habeq' ),
	'invalid_email'   => __( 'Please provide a valid email address.', 'habeq' ),
	'signup_failed'   => __( 'Signup failed. Please try again.', 'habeq' ),
	'rate_limited'    => __( 'Please wait before attempting to sign up again.', 'habeq' ),
);

$error_message = isset( $messages[ $error ] ) ? $messages[ $error ] : __( 'Signup failed. Please try again.', 'habeq' );
?>
<section class="habeq-portal__panel habeq-portal__panel--signup">
	<h2><?php esc_html_e( 'Sign Up', 'habeq' ); ?></h2>
	<?php if ( ! $allow_signup ) : ?>
		<p class="habeq-portal__notice habeq-portal__notice--error"><?php esc_html_e( 'Organizer signup is currently closed.', 'habeq' ); ?></p>
	<?php elseif ( 'error' === $status && $error ) : ?>
		<p class="habeq-portal__notice habeq-portal__notice--error"><?php echo esc_html( $error_message ); ?></p>
	<?php endif; ?>
	<?php if ( $allow_signup ) : ?>
		<form class="habeq-portal__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'habeq_portal_signup', 'habeq_portal_signup_nonce' ); ?>
			<input type="hidden" name="action" value="habeq_signup" />
			<p>
				<label for="habeq-portal-signup-email"><?php esc_html_e( 'Email', 'habeq' ); ?></label>
				<input id="habeq-portal-signup-email" name="email" type="email" required />
			</p>
			<p>
				<label for="habeq-portal-signup-display-name"><?php esc_html_e( 'Display Name (optional)', 'habeq' ); ?></label>
				<input id="habeq-portal-signup-display-name" name="display_name" type="text" />
			</p>
			<p><?php esc_html_e( 'A password will be emailed to you after signup.', 'habeq' ); ?></p>
			<button type="submit"><?php esc_html_e( 'Create Account', 'habeq' ); ?></button>
		</form>
	<?php endif; ?>
</section>
