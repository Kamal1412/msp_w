<?php

/**
 * Class LP_Email_Drip_Content
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

class LP_Email_Item_Available_User extends LP_Email {
	/**
	 * LP_Email_Drip_Content constructor.
	 */
	public function __construct() {
		$this->id    = 'content_drip_item_available';
		$this->title = __( 'Item Available', 'learnpress' );

		$this->template_base = dirname( LP_ADDON_CONTENT_DRIP_FILE ) . '/templates/';

		$this->template_html  = 'emails/item-available-user.php';
		$this->template_plain = 'emails/plain/item-available-user.php';

		$this->default_subject = __( '[{{site_title}}] Lesson is coming soon', 'learnpress' );
		$this->default_heading = __( 'Item Available', 'learnpress' );

		$this->template_path   = 'learnpress-content-drip';
		$this->support_variables = array(
			'{{site_url}}',
			'{{site_title}}',
			'{{admin_email}}',
			'{{lesson_id}}',
			'{{lesson_name}}',
			'{{lesson_url}}',
			'{{available_date}}',
			'{{user_id}}',
			'{{username}}',
			'{{login_url}}',
			'{{course_id}}',
			'{{course_name}}',
			'{{course_url}}',
			'{{header}}',
			'{{footer}}',
			'{{email_heading}}',
			'{{footer_text}}'
		);

		add_action( 'learn_press_user_content_drip_notification', array( $this, 'trigger' ), 99, 3 );
		add_filter( 'learn_press_section_emails_' . $this->id, array( $this, 'admin_options' ) );

		parent::__construct();
	}

	public function send_email() {

	}

	public function admin_options( $obj ) {
		$settings_class = LP_Settings_Emails::instance();
		$settings       = LP()->settings;
		$view           = learn_press_get_admin_view( '/settings/email-options.php', LP_ADDON_CONTENT_DRIP_FILE );
		include_once $view;
	}

	public function trigger( $user_id, $lesson_id, $available_date, $course_id ) {
		if ( !$this->enable ) {
			return;
		}
		$user = learn_press_get_user( $user_id );

		$this->recipient = $user->user_email;

		/*$this->find['site_title']     = '{site_title}';
		$this->find['lesson_name']    = '{lesson_name}';
		$this->find['available_date'] = '{available_date}';
		$this->find['username']       = '{username}';
		$this->find['lesson_url']     = '{lesson_url}';
		$this->find['login_url']      = '{login_url}';

		$this->replace['site_title']     = $this->get_blogname();
		$this->replace['lesson_name']    = get_the_title( $lesson_id );
		$this->replace['available_date'] = date( get_option( 'date_format' ), $available_date );
		$this->replace['username']       = learn_press_get_profile_display_name( $user );
		$this->replace['lesson_url']     = learn_press_get_course_item_url( $course_id, $lesson_id );
		$this->replace['login_url']      = learn_press_get_login_url();*/

		$this->object = $this->get_common_template_data(
			$this->email_format == 'plain_text' ? 'plain' : 'html',
			array(
				'user_id'        => $user->id,
				'user_name'      => learn_press_get_profile_display_name( $user ),
				'lesson_id'      => $lesson_id,
				'lesson_name'    => get_the_title( $lesson_id ),
				'lesson_url'     => learn_press_get_course_item_url( $course_id, $lesson_id ),
				'available_date' => date( get_option( 'date_format' ), $available_date ),
				'course_id'      => $course_id,
				'course_name'    => get_the_title( $course_id ),
				'course_url'     => get_the_permalink( $course_id )
			)
		);

		$this->variables = $this->data_to_variables( $this->object );

		$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		///return false;

		return $return;
	}

	/*
	public function get_content_html() {
		echo parent::get_content_html();
		ob_start();
		learn_press_get_template( $this->template_html, $this->get_template_data( 'html' ), '', dirname( LP_ADDON_CONTENT_DRIP_FILE ) . '/templates' );
		return ob_get_clean();
	}

	public function get_content_plain() {
		echo parent::get_content_plain();
		ob_start();
		learn_press_get_template( $this->template_plain, $this->get_template_data( 'plain' ), '', dirname( LP_ADDON_CONTENT_DRIP_FILE ) . '/templates' );
		return ob_get_clean();
	}*/

	public function get_template_data( $format = 'plain' ) {
		return $this->object;
	}
}

return new LP_Email_Item_Available_User();