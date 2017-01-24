<?php
/**
 * User Information
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}
global $wp_query;
$user = learn_press_get_current_user();

$user_info = get_userdata( $user->id );

$username             = $user_info->user_login;
$nick_name            = $user_info->nickname;
$first_name           = $user_info->first_name;
$last_name            = $user_info->last_name;
$profile_picture_type = $user->profile_picture_type;

if ( $user ) :
	?>
	<div class="user-profile-edit-form" id="learn-press-user-profile-edit-form">
		<form id="your-profile" action="" method="post" enctype="multipart/form-data" novalidate="novalidate">


			<h3 class="title"><?php esc_html_e( 'About Yourself', 'eduma' ); ?></h3>

			<div class="user-profile-picture info-field">
				<label><?php esc_html_e( 'Profile Picture', 'eduma' ); ?></label>
				<div class="profile-avatar-current <?php echo $profile_picture_type == 'gravatar' ? 'avatar-picture' : 'avatar-gravatar'; ?>">
					<?php echo $user->get_profile_picture( $profile_picture_type == 'gravatar' ? 'gravatar' : 'picture' ); ?>
				</div>
				<div class="profile-avatar-hidden hide-if-js <?php echo $profile_picture_type != 'gravatar' ? 'avatar-picture' : 'avatar-gravatar'; ?>">
					<?php echo $user->get_profile_picture( $profile_picture_type == 'gravatar' ? 'picture' : 'gravatar' ); ?>
				</div>
				<div class="change-picture">
					<select name="profile_picture_type">
						<option value="gravatar" <?php echo $profile_picture_type == 'gravatar' ? ' selected="selected"' : ''; ?>><?php esc_html_e( 'Gravatar', 'eduma' ); ?></option>
						<option value="picture" <?php echo $profile_picture_type == 'picture' ? ' selected="selected"' : ''; ?>><?php esc_html_e( 'Picture', 'eduma' ); ?></option>
					</select>
					<div id="profile-picture-gravatar" class="<?php echo $profile_picture_type != 'gravatar' ? 'hide-if-js' : ''; ?>">
						<p class="description"><?php esc_html_e( 'You can change your profile picture on', 'eduma' ); ?>
							<a href="https://en.gravatar.com/"><?php esc_html_e( 'Gravatar', 'eduma' ); ?></a></p>
					</div>
					<div id="profile-picture-picture" class="<?php echo $profile_picture_type == 'gravatar' ? 'hide-if-js' : ''; ?>">
						<!--<input type="file" name="profile_picture" />-->
						<input type="hidden" id="lp-user-profile-picture-data" name="profile_picture_data" />
						<a href="javascript:void(0);" class="button-primary change-profile-picutre-text" onupload="0"><?php _e('Change Profile Picture', 'learnpress');?></a>
						<div class="image-editor" style="display:none;">
							<input type="file" class="cropit-image-input" name="profile_picture">
							<div class="cropit-preview"></div>
							<div class="image-size-label">
								<?php _e( 'Resize image', 'learnpress' );?>
							</div>
							<input type="range" class="cropit-image-zoom-input">
							<span class="rotate-ccw dashicons dashicons-image-rotate-left"></span>
							<span class="rotate-cw dashicons dashicons-image-rotate-right"></span>
							<a class="export"><?php _e( 'Done', 'learnpress' );?></a>
						</div>
					</div>
				</div>
			</div>

			<div class="user-description-wrap info-field end-box">
				<label><?php esc_html_e( 'Biographical Info', 'eduma' ); ?></label>
				<textarea name="description" id="description" rows="5" cols="30"><?php echo esc_html( $user_info->description ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Share a little biographical information to fill out your profile. This may be shown publicly.', 'eduma' ); ?></p>
			</div>

			<h3 class="title"><?php esc_html_e( 'Name', 'eduma' ); ?></h3>

			<div class="user-user-login-wrap info-field">
				<label><?php esc_html_e( 'Username', 'eduma' ); ?></label>
				<input type="text" name="user_login" id="user_login" value="<?php echo esc_attr( $user->user->data->user_login ); ?>" disabled="disabled" class="regular-text">
				<p class="description"><?php esc_html_e( 'Username cannot be changed.', 'eduma' ) ?></p>
			</div>

			<div class="user-first-name-wrap info-field">
				<label><?php esc_html_e( 'First Name', 'eduma' ); ?></label>
				<input type="text" name="first_name" id="first_name" value="<?php echo esc_attr( $first_name ); ?>" class="regular-text">
			</div>

			<div class="user-last-name-wrap info-field">
				<label><?php esc_html_e( 'Last Name', 'eduma' ); ?></label>
				<input type="text" name="last_name" id="last_name" value="<?php echo esc_attr( $last_name ); ?>" class="regular-text">
			</div>

			<div class="user-nickname-wrap info-field">
				<label><?php esc_html_e( 'Nickname *', 'eduma' ); ?></label>
				<input type="text" name="nickname" id="nickname" value="<?php echo esc_attr( $user_info->nickname ) ?>" class="regular-text" />
			</div>
			<div class="user-last-name-wrap info-field end-box">
				<label><?php esc_html_e( 'Display name publicly as', 'eduma' ); ?></label>
				<select name="display_name" id="display_name">
					<?php
					$public_display                     = array();
					$public_display['display_nickname'] = $user_info->nickname;
					$public_display['display_username'] = $user_info->user_login;

					if ( !empty( $user_info->first_name ) )
						$public_display['display_firstname'] = $user_info->first_name;

					if ( !empty( $user_info->last_name ) )
						$public_display['display_lastname'] = $user_info->last_name;

					if ( !empty( $user_info->first_name ) && !empty( $user_info->last_name ) ) {
						$public_display['display_firstlast'] = $user_info->first_name . ' ' . $user_info->last_name;
						$public_display['display_lastfirst'] = $user_info->last_name . ' ' . $user_info->first_name;
					}

					if ( !in_array( $user_info->display_name, $public_display ) ) // Only add this if it isn't duplicated elsewhere
					{
						$public_display = array( 'display_displayname' => $user_info->display_name ) + $public_display;
					}

					$public_display = array_map( 'trim', $public_display );
					$public_display = array_unique( $public_display );

					foreach ( $public_display as $id => $item ) {
						?>
						<option <?php selected( $user_info->display_name, $item ); ?>><?php echo esc_html( $item ); ?></option>
						<?php
					}
					?>
				</select>
			</div>

			<h3 class="title"><?php esc_html_e( 'Contact Info', 'eduma' ); ?></h3>

			<div class="user-email-wrap info-field">
				<label><?php esc_html_e( 'Email *', 'eduma' ); ?></label>
				<input type="email" name="email" id="email" value="<?php echo esc_attr( $user_info->user_email ); ?>" class="regular-text ltr">
			</div>

			<div class="user-url-wrap info-field end-box">
				<label><?php esc_html_e( 'Website', 'eduma' ); ?></label>
				<input type="url" name="url" id="url" value="<?php echo esc_attr( $user_info->user_url ); ?>" class="regular-text code">
			</div>

			<h3 class="title"><?php esc_html_e( 'Account Management', 'eduma' ); ?></h3>
			<div class="change-password">
				<a href="javascript:jQuery('#user_profile_password_form').slideToggle();" class="link-change-password"><?php esc_html_e( 'Change Password', 'eduma' ); ?></a>
				<div id="user_profile_password_form" class="info-field end-box" style="display: none">
					<label><?php esc_html_e( 'Old Password', 'eduma' ); ?></label>
					<input type="password" id="pass0" name="pass0" autocomplete="off" class="regular-text" />

					<label><?php esc_html_e( 'New Password', 'eduma' ); ?></label>
					<input type="password" name="pass1" id="pass1" class="regular-text" value="" />

					<label><?php esc_html_e( 'Repeat New Password', 'eduma' ); ?></label>
					<input name="pass2" type="password" id="pass2" class="regular-text" value="" />
					<p class="description"><?php esc_html_e( 'Type your new password again.', 'eduma' ); ?></p>

				</div>
			</div>

			<input type="hidden" name="action" value="update" />
			<input type="hidden" name="user_id" id="user_id" value="<?php echo esc_attr( $user->id ); ?>" />
			<input type="hidden" name="profile-nonce" value="<?php echo esc_attr( wp_create_nonce( 'learn-press-user-profile-' . $user->id ) ); ?>" />
			<input type="hidden" name="from" value="profile">
			<input type="hidden" name="checkuser_id" value="2">
			<p class="submit update-profile">
				<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Update Profile', 'learnpress' ); ?>" />
			</p>
		</form>
	</div>
	<?php
endif;
?>
