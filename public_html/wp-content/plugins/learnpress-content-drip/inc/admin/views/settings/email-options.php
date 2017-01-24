<?php
defined( 'ABSPATH' ) || exit();
?>
<table class="form-table">
	<tbody>
	<?php do_action( 'learn_press_before_' . $settings_class->id . '_' . $settings_class->section['id'] . '_settings_fields', $settings ); ?>
	<tr>
		<th scope="row">
			<label for="learn-press-emails-item-available-enable"><?php _e( 'Enable', 'learnpress' ); ?></label>
		</th>
		<td>
			<input type="hidden" name="<?php echo $settings_class->get_field_name( 'emails_content_drip_item_available[enable]' ); ?>" value="no" />
			<input id="learn-press-emails-item-available-enable" type="checkbox" name="<?php echo $settings_class->get_field_name( 'emails_content_drip_item_available[enable]' ); ?>" value="yes" <?php checked( $settings->get( 'emails_content_drip_item_available.enable' ) == 'yes' ); ?>" />
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="learn-press-emails-item-available-enable"><?php _e( 'Email event', 'learnpress' ); ?></label>
		</th>
		<td>
			<select onchange="jQuery('#emails_content_drip_item_available_ajax_schedule').toggleClass('hide-if-js', !this.value);" name="<?php echo $settings_class->get_field_name( 'emails_content_drip_item_available[email_event]' ); ?>">
				<option value="" <?php selected( $settings->get( 'emails_content_drip_item_available.email_event' ) == '' ); ?>><?php esc_html_e( 'Anyone access to site', 'learnpress' ); ?></option>
				<option value="ajax" <?php selected( $settings->get( 'emails_content_drip_item_available.email_event' ) == 'ajax' ); ?>><?php esc_html_e( 'Use Ajax to check every...', 'learnpress' ); ?></option>
			</select>
			<div id="emails_content_drip_item_available_ajax_schedule" class="<?php echo $settings->get( 'emails_content_drip_item_available.email_event' ) == '' ? 'hide-if-js' : ''; ?>">
				<?php
				$v = $settings->get( 'emails_content_drip_item_available.ajax_schedule.1', 'second' );
				?>
				<input name="<?php echo $settings_class->get_field_name( 'emails_content_drip_item_available[ajax_schedule][0]' ); ?>" type="number" min="1" max="" step="1" value="<?php echo $settings->get( 'emails_content_drip_item_available.ajax_schedule.0', 15 ); ?>" />
				<select name="<?php echo $settings_class->get_field_name( 'emails_content_drip_item_available[ajax_schedule][1]' ); ?>">
					<option value="second" <?php selected( $v == 'second' ); ?>><?php esc_html_e( 'Second(s)', 'learnpress' ); ?></option>
					<option value="minute" <?php selected( $v == 'minute' ); ?>><?php esc_html_e( 'Minute(s)', 'learnpress' ); ?></option>
					<option value="hour" <?php selected( $v == 'hour' ); ?>><?php esc_html_e( 'Hour(s)', 'learnpress' ); ?></option>
					<option value="day" <?php selected( $v == 'day' ); ?>><?php esc_html_e( 'Day(s)', 'learnpress' ); ?></option>
				</select>
			</div>
			<p class="description">
				<code><?php esc_html_e( 'Anyone access to site:', 'learnpress' ); ?></code>
				<?php esc_html_e( 'Check items is available each time anyone access to your site and send email to users', 'learnpress' ); ?>
			</p>
			<p class="description">
				<code><?php esc_html_e( 'Use Ajax to check every...:', 'learnpress' ); ?></code>
				<?php esc_html_e( 'Use Ajax runs in background to check by schedule you selected above', 'learnpress' ); ?>
			</p>
			<p class="description">
				<?php esc_html_e( 'Cron-job URL', 'learnpress' ); ?>
				<code><a href="<?php echo get_site_url(); ?>/?lp-ajax=drip-content-cron-job"><?php echo get_site_url(); ?>/?lp-ajax=drip-content-cron-job</a></code>
			</p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="learn-press-emails-item-available-subject"><?php _e( 'Subject', 'learnpress' ); ?></label>
		</th>
		<td>
			<input id="learn-press-emails-item-available-subject" class="regular-text" type="text" name="<?php echo $settings_class->get_field_name( 'emails_content_drip_item_available[subject]' ); ?>" value="<?php echo $settings->get( 'emails_content_drip_item_available.subject', $this->default_subject ); ?>" />

			<p class="description">
				<?php printf( __( 'Email subject, default: <code>%s</code>', 'learnpress' ), $this->default_subject ); ?>
			</p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="learn-press-emails-item-available-heading"><?php _e( 'Heading', 'learnpress' ); ?></label>
		</th>
		<td>
			<input id="learn-press-emails-item-available-heading" class="regular-text" type="text" name="<?php echo $settings_class->get_field_name( 'emails_content_drip_item_available[heading]' ); ?>" value="<?php echo $settings->get( 'emails_content_drip_item_available.heading', $this->default_heading ); ?>" />

			<p class="description">
				<?php printf( __( 'Email heading, default: <code>%s</code>', 'learnpress' ), $this->default_heading ); ?>
			</p>
		</td>
	</tr>
	<!--<tr>
		<th scope="row">
			<label for="learn-press-emails-item-available-email-format"><?php _e( 'Email format', 'learnpress' ); ?></label>
		</th>
		<td>
			<?php learn_press_email_formats_dropdown( array( 'name' => $settings_class->get_field_name( 'emails_content_drip_item_available[email_format]' ), 'id' => 'learn_press_email_formats', 'selected' => $settings->get( 'emails_content_drip_item_available.email_format' ) ) ); ?>
		</td>
	</tr>-->
	<?php
	$view = learn_press_get_admin_view( 'settings/emails/email-template.php' );
	include_once $view;
	?>
	<?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	</tbody>
</table>