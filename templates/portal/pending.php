<?php
/**
 * Portal pending approval template.
 *
 * @package HabaqEvents
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}
?>
<section class="habeq-portal__panel habeq-portal__panel--pending">
	<h2><?php esc_html_e( 'Approval Pending', 'habeq' ); ?></h2>
	<p><?php esc_html_e( 'Your organizer account is pending admin approval. You will be able to manage events once approved.', 'habeq' ); ?></p>
</section>
