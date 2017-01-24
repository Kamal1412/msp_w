<?php
/**
 * Restrict lesson content template
 *
 * @author ThimPress
 */

$date_format    = apply_filters( 'learn_press_restrict_content_date_format', get_option( 'date_format' ) );
$date_available = date( $date_format, LP_Addon_Content_Drip::instance()->date_available );
$message        = sprintf( __( 'Sorry! You can not view this lesson right now. It will become available on %s', 'learnpress' ), $date_available );
?>
<div class="learn-press-restrict-lesson-content">
	<?php learn_press_display_message( sprintf( '<p>%s</p>', $message ), 'error' ); ?>
</div>
