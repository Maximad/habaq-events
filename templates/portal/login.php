<?php
/**
 * Portal login template.
 *
 * @package HabaqEvents
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
$error  = isset( $_GET['error'] ) ? sanitize_key( wp_unslash( $_GET['error'] ) ) : '';

$messages = array(
	'signup_success'     => __( 'Signup complete. Please check your email for your password and log in.', 'habeq' ),
	'logged_out'         => __( 'You have been logged out.', 'habeq' ),
	'missing_credentials' => __( 'Please enter your email/username and password.', 'habeq' ),
	'invalid_email'      => __( 'Please provide a valid email address.', 'habeq' ),
	'login_failed'       => __( 'Login failed. Please try again.', 'habeq' ),
);

$error_message = isset( $messages[ $error ] ) ? $messages[ $error ] : __( 'Login failed. Please try again.', 'habeq' );
$status_message = isset( $messages[ $status ] ) ? $messages[ $status ] : '';
?>
<section class="habeq-portal__panel habeq-portal__panel--login">
	<h2><?php esc_html_e( 'Login', 'habeq' ); ?></h2>
	<?php if ( 'error' === $status && $error ) : ?>
		<p class="habeq-portal__notice habeq-portal__notice--error"><?php echo esc_html( $error_message ); ?></p>
	<?php elseif ( $status_message ) : ?>
		<p class="habeq-portal__notice habeq-portal__notice--success"><?php echo esc_html( $status_message ); ?></p>
	<?php endif; ?>
	<form class="habeq-portal__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'habeq_portal_login', 'habeq_portal_login_nonce' ); ?>
		<input type="hidden" name="action" value="habeq_login" />
		<p>
			<label for="habeq-portal-login"><?php esc_html_e( 'Email or Username', 'habeq' ); ?></label>
			<input id="habeq-portal-login" name="login" type="text" required />
		</p>
		<p>
			<label for="habeq-portal-password"><?php esc_html_e( 'Password', 'habeq' ); ?></label>
			<input id="habeq-portal-password" name="password" type="password" required />
		</p>
		<button type="submit"><?php esc_html_e( 'Log In', 'habeq' ); ?></button>
	</form>
</section>
