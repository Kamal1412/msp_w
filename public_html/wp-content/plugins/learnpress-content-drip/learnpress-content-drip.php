<?php
/*
Plugin Name: LearnPress - Content Drip
Plugin URI: http://thimpress.com/learnpress
Description: Decide when learners will be able to access the lesson content.
Author: ThimPress
Version: 2.0
Author URI: http://thimpress.com
Tags: learnpress
Text Domain: learnpress-content-drip
Domain Path: /languages/
*/
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'LP_ADDON_CONTENT_DRIP_FILE', __FILE__ );
define( 'LP_ADDON_CONTENT_DRIP_PATH', dirname( __FILE__ ) );
define( 'LP_ADDON_CONTENT_DRIP_VER', '2.0' );
define( 'LP_ADDON_CONTENT_DRIP_REQUIRE_VER', '2.0' );


/**
 * Class LP_Addon_Content_Drip
 */
class LP_Addon_Content_Drip {

	/**
	 * @var null
	 */
	protected static $_instance = null;

	/**
	 * @var null
	 */
	public $date_available = null;

	/**
	 * @var null
	 */
	private $_course_info = null;

	public $enable_email = false;

	/**
	 * LP_Addon_Content_Drip constructor.
	 */
	function __construct() {
		add_filter( 'learn_press_course_settings_meta_box_args', array( $this, 'course_settings' ) );

		add_action( 'learn_press_lp_lessonadd_meta_boxes', array( $this, 'lesson_settings' ), 10 );
		add_action( 'init', array( $this, 'load_text_domain' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		//add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp', array( $this, 'drip_content' ) );
		add_action( 'manage_lp_lesson_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_lp_lesson_posts_custom_column', array( $this, 'column_content' ), 99, 2 );
		add_action( 'learn_press_emails_init', array( $this, 'add_emails' ), 10 );
		add_action( 'save_post', array( $this, 'update_course_drip_items' ), 100000 );

		$this->enable_email = LP()->settings->get( 'emails_content_drip_item_available.enable' ) == 'yes';
		if ( $this->enable_email ) {
			if ( LP()->settings->get( 'emails_content_drip_item_available.email_event' ) == 'ajax' ) {
				LP_Request_Handler::register_ajax( 'drip-content-cron-job', array( $this, 'do_cron_job' ) );
			} else {
				add_action( 'init', array( $this, 'init' ) );
			}
		}
	}

	public function do_cron_job() {
		if ( !did_action( 'drip_content_schedule_events' ) ) {
			$this->schedule_events();
		}
		echo 'Drip Content schedule events completed';
		die();
	}

	/**
	 * Get courses which a lesson is contained
	 *
	 * @param $lesson_id
	 *
	 * @return array
	 */
	public function get_lesson_in_courses( $lesson_id ) {
		global $wpdb;
		$query   = $wpdb->prepare( "
			SELECT p.ID
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->prefix}learnpress_sections s ON s.section_course_id = p.ID
			INNER JOIN {$wpdb->prefix}learnpress_section_items si ON si.section_id = s.section_id
			WHERE si.item_id = %d
		", $lesson_id );
		$courses = $wpdb->get_col( $query );
		return $courses;
	}

	/**
	 * Update all lessons has drip content into course
	 *
	 * @param $post_id
	 */
	public function update_course_drip_items( $post_id ) {
		if ( get_post_type( $post_id ) === LP_LESSON_CPT ) {
			$courses = $this->get_lesson_in_courses( $post_id );
			if ( $courses ) foreach ( $courses as $course_id ) {
				$this->update_course_drip_items( $course_id );
			}
			return;
		}
		if ( get_post_type( $post_id ) !== LP_COURSE_CPT ) {
			return;
		}
		$has_drip_content = get_post_meta( $post_id, '_lp_content_drip_enable', true ) === 'yes';
		$drip_items       = array();

		if ( $has_drip_content ) {
			global $wpdb;
			$query_args = array(
				'_lp_drip_content_date_become_available',
				'interval',
				'specific_date',
				'_lp_drip_content_interval',
				'_lp_drip_content_specific_date',
				$post_id,
				'lp_lesson'
			);
			$query      = $wpdb->prepare( "
				SELECT si.item_id, im.meta_value as available_type, im2.meta_value as duration, im4.meta_value as date
				FROM {$wpdb->prefix}learnpress_sections s
				INNER JOIN {$wpdb->prefix}learnpress_section_items si ON si.section_id = s.section_id
				INNER JOIN {$wpdb->prefix}posts i ON i.ID = si.item_id
				INNER JOIN {$wpdb->prefix}postmeta im ON im.post_id = i.ID AND im.meta_key = %s AND im.meta_value IN(%s, %s)
				INNER JOIN {$wpdb->prefix}postmeta im2 ON im2.post_id = i.ID AND im2.meta_key = %s
				LEFT JOIN {$wpdb->prefix}postmeta im4 ON im4.post_id = i.ID AND im4.meta_key = %s
				WHERE s.section_course_id = %d
				AND i.post_type = %s
			", $query_args );
			$lessons    = $wpdb->get_results( $query );
			if ( $lessons ) {
				foreach ( $lessons as $lesson ) {
					$drip_items[$lesson->item_id] = array(
						'available_after' => $lesson->available_type == 'specific_date' ? $lesson->date : $lesson->duration
					);
				}
			}
		}
		if ( $drip_items ) {
			update_post_meta( $post_id, '_lp_drip_items', $drip_items );
		} else {
			delete_post_meta( $post_id, '_lp_drip_items' );
		}
	}

	public function init() {
		if ( version_compare( get_option( 'learn_press_content_drip_db_version' ), '2.0', '<' ) ) {
			$this->upgrade();
		}
		$this->schedule_events();
	}

	/**
	 * Upgrade old meta data to new format
	 */
	public function upgrade() {
		global $wpdb;
		/**
		 * Combines 2 meta values of '_lp_drip_content_interval' and '_lp_drip_content_interval_type' into one
		 * Only do this if the meta _lp_drip_content_interval is a numeric (not for 1 day, 3 week, etc...)
		 * E.g:
		 *        _lp_drip_content_interval = 1
		 *        _lp_drip_content_interval_type = day
		 * So result:
		 *        _lp_drip_content_interval = 1 day
		 */
		$query = $wpdb->prepare( "
			UPDATE {$wpdb->postmeta} a, (
				SELECT m2.post_id, m2.meta_key, CONCAT(m2.meta_value, ' ', m3.meta_value) AS duration
					FROM {$wpdb->postmeta} m2
					INNER JOIN {$wpdb->postmeta} m3 ON m2.post_id = m3.post_id
						AND m2.meta_key = %s
						AND m3.meta_key = %s
					WHERE m2.meta_value REGEXP %s
			) X
			SET a.meta_value = X.duration
			WHERE a.post_id = X.post_id
			AND a.meta_key = X.meta_key;
		", '_lp_drip_content_interval', '_lp_drip_content_interval_type', '^[0-9]+$' );
		$wpdb->query( $query );
		update_option( 'learn_press_content_drip_db_version', '2.0' );
	}

	public function schedule_events() {
		$user = learn_press_get_current_user();

		global $wpdb;
		/**
		 * Get all courses which is enabled Content Drip and it's is not already finished by users
		 */
		$query   = $wpdb->prepare( "
			SELECT * FROM(
				SELECT ui.user_item_id, ui.user_id, p.ID as course_id, ui.status
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s AND pm.meta_value = %s
				INNER JOIN {$wpdb->prefix}learnpress_user_items ui ON ui.item_id = p.ID
				ORDER BY ui.user_item_id DESC
			) as X
			GROUP BY user_id, course_id
			HAVING status <> %s
			ORDER BY course_id, user_item_id ASC
		", '_lp_content_drip_enable', 'yes', 'finished' );
		$courses = $wpdb->get_results( $query );
		if ( !$courses ) {
			return;
		}
		$course_users = array();
		foreach ( $courses as $course ) {
			if ( empty( $course_users[$course->course_id] ) ) {
				$course_users[$course->course_id] = array();
			}
			$course_users[$course->course_id][] = $course->user_id;
		}

		/*$course_ids = array_keys($course_users);
		$query = $wpdb->prepare("
			SELECT * FROM $wpdb->posts WHERE %d AND ID IN(".join(', ', $course_ids).")
		",1);*/

		foreach ( $course_users as $course_id => $users ) {
			$this->send_to_users_in_course( $course_id, $users );
		}
		do_action( 'drip_content_schedule_events' );
	}


	private function send_to_users_in_course( $course_id, $user_ids ) {
		global $wpdb;
		$drip_items = get_post_meta( $course_id, '_lp_drip_items', true );
		if ( !$drip_items ) {
			return;
		}
		$drip_item_ids = array_keys( $drip_items );
		$item_id_in    = array_fill( 0, sizeof( $drip_item_ids ), '%d' );
		$item_id_in    = $wpdb->prepare( 'IN(' . join( ',', $item_id_in ) . ')', $drip_item_ids );

		$user_id_in = array_fill( 0, sizeof( $user_ids ), '%d' );
		$user_id_in = $wpdb->prepare( 'IN(' . join( ',', $user_id_in ) . ')', $user_ids );

		$query            = $wpdb->prepare( "
			SELECT * FROM
				(SELECT user_id, item_id, status
				FROM {$wpdb->prefix}learnpress_user_items ui
				WHERE %d
					AND ui.item_id {$item_id_in}
					AND ui.user_id {$user_id_in}
				) AS X
			GROUP BY user_id, item_id
		", 1 );
		$items            = $wpdb->get_results( $query );
		$completed_items  = array();
		$maybe_send_items = array();
		if ( $items ) foreach ( $items as $item ) {
			if ( $item->status == 'completed' ) {
				if ( empty( $completed_items[$item->user_id] ) ) {
					$completed_items[$item->user_id] = array();
				}
				$completed_items[$item->user_id][] = $item->item_id;
			} else {
				if ( empty( $maybe_send_items[$item->user_id] ) ) {
					$maybe_send_items[$item->user_id] = array();
				}
				$maybe_send_items[$item->user_id][] = $item->item_id;
			}
		}
		foreach ( $drip_item_ids as $item_id ) {
			foreach ( $user_ids as $user_id ) {
				$maybe_send_item_id = 0;
				if ( empty( $completed_items[$user_id] ) ) {
					$maybe_send_item_id = $item_id;
				} else {
					if ( !in_array( $item_id, $completed_items[$user_id] ) ) {
						$maybe_send_item_id = $item_id;
					}
				}
				if ( $maybe_send_item_id ) {
					if ( empty( $maybe_send_items[$user_id] ) ) {
						$maybe_send_items[$user_id] = array();
					}
					if ( !in_array( $maybe_send_item_id, $maybe_send_items[$user_id] ) ) {
						$maybe_send_items[$user_id][] = $maybe_send_item_id;
					}
				}
			}
		}

		foreach ( $user_ids as $user_id ) {
			$user = learn_press_get_user( $user_id );
			if ( !$user->is_exists() ) {
				continue;
			}
			$this->maybe_send_to_user( $user, $course_id, $maybe_send_items[$user_id] );
		}
	}

	private function maybe_send_to_user( $user_id, $course_id, $items, $drip_items = false ) {
		if ( !$user_id instanceof LP_User ) {
			$user = learn_press_get_user( $user_id );
		} else {
			$user = $user_id;
		}
		$course_info = $user->get_course_info( $course_id );
		if ( !$drip_items ) {
			$drip_items = get_post_meta( $course_id, '_lp_drip_items', true );
		}
		foreach ( $items as $item_id ) {
			if ( empty( $drip_items[$item_id] ) ) {
				continue;
			}
			//$user_item_id = $this->_get_item_id( $user->id, $item_id );
			//$is_sent      = learn_press_get_user_item_meta( $course_info['history_id'], '_lp_drip_content_is_sent_mail', true ) == 'yes';
			$is_sent = $this->_has_sent_email_item( $course_info['history_id'], $item_id );
			if ( $is_sent ) {
				continue;
			}
			$available_date = $drip_items[$item_id]['available_after'];
			if ( preg_match( '/^[0-9]+\s([a-z]+)$/', $available_date ) ) {
				$available_date = strtotime( '+' . $available_date, strtotime( $course_info['start'] ) );//strtotime( $course_info['start'] ) +
			} else {
				$available_date = strtotime( $available_date );
			}

			$available_date = absint( $available_date / DAY_IN_SECONDS ) * DAY_IN_SECONDS;
			$current_time   = current_time( 'timestamp' );
			$current_time   = absint( $current_time / DAY_IN_SECONDS ) * DAY_IN_SECONDS;
			$time           = $available_date - $current_time;

			if ( !$is_sent && ( $time >= 0 ) && ( $time <= DAY_IN_SECONDS ) ) {

				$send = LP_Emails::instance()->emails['LP_Email_Drip_Content_User']->trigger( $user->id, $item_id, $available_date, $course_id );

				if ( $send ) {
					//$this->_update_item_is_sent( array( 'user_item_id' => $user_item_id ) );
					$this->_update_item_is_sent( $course_info['history_id'], $item_id );
				}
			}
		}
	}

	private function _has_sent_email_item( $course_item_id, $item_id ) {
		$data = learn_press_get_user_item_meta( $course_item_id, '_lp_drip_content_is_sent_mail', true );
		if ( empty( $data[$item_id] ) ) {
			return false;
		}
		return true;
	}

	private function _update_item_is_sent( $course_item_id, $item_id ) {
		$data = learn_press_get_user_item_meta( $course_item_id, '_lp_drip_content_is_sent_mail', true );
		if ( empty( $data[$item_id] ) ) {
			$data[$item_id] = current_time( 'Y-m-d H:i:s' );
		}
		learn_press_update_user_item_meta( $course_item_id, '_lp_drip_content_is_sent_mail', $data );
		return $data[$item_id];
		global $wpdb;
		if ( empty( $args['user_item_id'] ) ) {
			if ( !empty( $args['user_id'] ) && empty( $args['item_id'] ) ) {
				$args['user_item_id'] = $this->_get_item_id( $args['user_id'], $args['item_id'] );
			}
		}
		if ( empty( $args['user_item_id'] ) ) {

		} else {
			learn_press_update_user_item_meta( $args['user_item_id'], '_lp_drip_content_is_sent_mail', 'yes' );
		}
	}

	private function _get_item_id( $user_id, $item_id ) {
		global $wpdb;

		$query = $wpdb->prepare( "
			SELECT user_item_id
			FROM {$wpdb->prefix}learnpress_user_items
			WHERE user_id = %d
			AND item_id = %d
		", $user_id, $item_id );
		return $wpdb->get_var( $query );
	}

	public function add_emails( $obj ) {
		$obj->emails['LP_Email_Drip_Content_User'] = include( 'inc/class-lp-email-item-available-user.php' );
	}

	/**
	 * Enqueue scripts + styles
	 */
	function enqueue_scripts() {
		$settings   = LP()->settings;
		$plugin_url = plugins_url( '/', LP_ADDON_CONTENT_DRIP_FILE );
		if ( is_admin()/* && get_post_type() == 'lp_lesson'*/ ) {
			wp_register_style( 'content-drip-css', $plugin_url . 'css/admin.css' );
			wp_register_script( 'content-drip-js', $plugin_url . 'js/admin.js' );
			if ( $settings->get( 'emails_content_drip_item_available.enable' ) == 'yes' && $settings->get( 'emails_content_drip_item_available.email_event' ) == 'ajax' ) {
				LP_Assets::add_script_tag( $this->admin_js_code(), '__all' );
			}
		}
		wp_enqueue_style( 'content-drip-css' );
		wp_enqueue_script( 'content-drip-js' );
	}

	function _remove_content( $content ) {
		ob_start();
		$this->content_lesson();
		return ob_get_clean();
	}

	private function _get_ajax_schedule_duration() {
		$settings = LP()->settings;
		$a        = $settings->get( 'emails_content_drip_item_available.ajax_schedule.0', 15 );
		$b        = $settings->get( 'emails_content_drip_item_available.ajax_schedule.1', 'second' );
		$arr      = array( 'second' => 1, 'minute' => MINUTE_IN_SECONDS, 'hour' => HOUR_IN_SECONDS, 'day' => DAY_IN_SECONDS );
		return intval( $arr[$b] * $a );
	}

	public function admin_js_code() {
		ob_start();
		?>
		<script type="text/javascript">
			jQuery(function ($) {
				var _ajax_schedule_timer = null;

				function _ajax_schedule() {
					$.ajax({
						url    : '<?php echo admin_url( 'index.php' );?>?lp-ajax=drip-content-cron-job',
						success: function (response) {
							_ajax_schedule_timer && clearTimeout(_ajax_schedule_timer);
							_ajax_schedule_timer = setTimeout(function () {
								_ajax_schedule();
							}, <?php echo $this->_get_ajax_schedule_duration() * 1000;?>);
						}
					});
				}

				_ajax_schedule();
			});
		</script>
		<?php
		$code = ob_get_clean();
		return preg_replace( '!</?script>!', '', $code );
	}

	/**
	 * drip content if needed
	 */
	function drip_content() {
		// ensure that we are in a course
		$course_id = get_the_ID();
		if ( !$this->is_enable( $course_id ) ) {
			return;
		}
		$course = $this->need_to_drip( $course_id );
		if ( !$course ) {
			return;
		}
		$block_content      = false;
		$user               = LP()->user;
		$lesson_id          = $course->is_viewing_item();
		$this->_course_info = $user->get_course_info( $course_id );
		if ( !$this->_course_info ) {
			$block_content = true;
		}
		//print_r( $this->_course_info );
		// ensure the lesson is in a course as setting up
		if ( $lesson_id && $lesson = LP_Lesson::get_lesson( $lesson_id ) ) {
			$drip_type = $lesson->drip_content_date_become_available;
			if ( $drip_type == 'interval' ) {
				$interval = $lesson->drip_content_interval;
				//$type                 = $lesson->drip_content_interval_type;
				$this->date_available = strtotime( "+{$interval}", strtotime( $this->_course_info['start'] ) );
			} elseif ( $drip_type == 'specific_date' ) {
				$date = $lesson->drip_content_specific_date;
				if ( $date ) {
					if ( preg_match_all( '!^(\d{2}) (.*) (\d{4})!', $date, $matches ) ) {
						//$date = $matches[3][0] . '/' . $matches[2][0] . '/' . $matches[1][0];
					}
				}
				$this->date_available = strtotime( $date );
			}
			//echo $this->date_available ,',', current_time( 'timestamp' );die();
			if ( $this->date_available > current_time( 'timestamp' ) ) {
				$block_content = true;
			}
		}
		if ( $block_content ) {
			//add_filter( 'learn_press_course_lesson_content', array( $this, '_remove_content' ) );
			//add_filter( 'learn_press_user_view_lesson', '__return_false' );
			///add_filter( 'learn_press_content_item_protected_message', array( $this, 'protected_message' ), 10, 2 );
			//add_filter( 'learn_press_get_template', array( $this, 'show_message' ), 10, 5 );//'single-course/content-protected.php')
			add_filter( 'learn_press_locate_template', array( $this, 'show_message' ), 10, 3 );//'single-course/content-protected.php')
		}
	}

	public function show_message( $located, $template_name, $template_path ) {
		if ( $this->date_available && $template_name == 'single-course/content-item-lp_lesson.php' ) {
			$located = LP_ADDON_CONTENT_DRIP_PATH . '/templates/restrict-content.php';
		}
		return $located;
	}

	/**
	 * Display restrict content for lesson
	 */
	function content_lesson() {
		if ( $this->date_available ) {
			require LP_ADDON_CONTENT_DRIP_PATH . '/templates/restrict-content.php';
		}
	}

	/**
	 * Add new columns to lesson manage
	 *
	 * @param array
	 *
	 * @return array
	 */
	function add_columns( $columns ) {
		$new_column['drip_schedule'] = __( 'Drip Schedule', 'learnpress-content-drip' );
		$keys                        = array_keys( $columns );
		if ( false !== ( $pos = array_search( 'lp_course', $keys ) ) ) {
			$tmp     = array();
			$new_key = 'drip_schedule';
			array_splice( $keys, $pos + 1, 0, array( $new_key ) );
			foreach ( $keys as $key ) {
				if ( $key == $new_key ) {
					$tmp[$key] = __( 'Drip Schedule', 'learnpress-content-drip' );
				} else {
					$tmp[$key] = $columns[$key];
				}
			}
			$columns = $tmp;
		} else {
			$columns = array_merge( $columns, $new_column );
		}
		return $columns;
	}

	/**
	 * Displays content for our column
	 *
	 * @param string
	 * @param int
	 */
	function column_content( $column, $lesson_id ) {
		switch ( $column ) {
			case 'drip_schedule':
				$courses = learn_press_get_item_courses( $lesson_id );
				if ( $courses ) {
					$course = LP_Course::get_course( $courses[0]->ID );
					$lesson = LP_Lesson::get_lesson( $lesson_id );
					if ( $this->is_enable( $course->id ) ) {
						$drip_type = $lesson->drip_content_date_become_available;
						if ( $drip_type == 'interval' ) {
							$interval = $lesson->drip_content_interval;
							$segs     = explode( ' ', $interval );
							if ( sizeof( $segs ) > 1 ) {
								$a = absint( $segs[0] );
								$b = $segs[1];
							} else {
								$a = absint( $segs[0] );
								$b = 'day';
							}
							printf( _n( "After %s {$b}", "After %s {$b}s", $a, 'learnpress-content-drip' ), $a );
						} elseif ( $drip_type == 'specific_date' ) {
							$date = $lesson->drip_content_specific_date;
							printf( __( 'Available on </br> %s', 'learnpress-content-drip' ), date( get_option( 'date_format' ), strtotime( $date ) ) );
						} else {
							_e( 'Immediately', 'learnpress-content-drip' );
						}
					} else {
						echo '-';
					}
				} else {
					echo '-';
				}
		}
	}

	/**
	 * Check to see if Content Drip is enabled
	 *
	 * @param int
	 *
	 * @return bool
	 */
	function is_enable( $course_id = null ) {
		if ( !$course_id ) $course_id = get_the_ID();
		return 'yes' == get_post_meta( $course_id, '_lp_content_drip_enable', true );

	}

	/**
	 * @param int
	 *
	 * @return LP_Course | bool
	 */
	public function need_to_drip( $course_id ) {
		$need = is_single() && 'lp_course' == get_post_type() && $this->is_enable( $course_id );
		if ( $need ) {
			$need = LP_Course::get_course( $course_id );
			// ensure that the course is required enroll and Drip Content is enabled
			if ( !$need->is( 'require_enrollment' ) ) {
				return false;
			}
		}
		return $need;
	}

	/**
	 * Meta box to setting up Content Drip
	 */
	function lesson_settings() {
		if ( !class_exists( 'RW_Meta_Box' ) ) return;
		$post_id = 0;
		if ( empty( $post_id ) && !empty( $_REQUEST['post'] ) ) {
			$post_id = absint( $_REQUEST['post'] );
		}
		$prefix = '_lp_drip_content_';
		$type   = get_post_meta( $post_id, $prefix . 'date_become_available', true );
		new RW_Meta_Box(
			array(
				'id'     => 'content_drip',
				'title'  => __( 'Content drip', 'learnpress-content-drip' ),
				'pages'  => array( 'lp_lesson' ),
				'fields' => array(
					array(
						'name'    => __( 'When should this lesson become available?', 'learnpress-content-drip' ),
						'id'      => "{$prefix}date_become_available",
						'type'    => 'select',
						'std'     => 'immediately',
						'desc'    => __( 'The date/time this lesson become available for user view from the date they enrolled course', 'learnpress-content-drip' ),
						'options' => array(
							'immediately'   => __( 'Immediately', 'learnpress-content-drip' ),
							'interval'      => __( 'After...', 'learnpress-content-drip' ),
							'specific_date' => __( 'Specific date', 'learnpress-content-drip' )
						)
					),
					array(
						'name'  => __( 'Available after', 'learnpress-content-drip' ),
						'id'    => "{$prefix}interval",
						'type'  => 'duration',
						'desc'  => '',
						'std'   => '1',
						'min'   => 1,
						'max'   => 99999,
						'class' => 'content-drip-interval' . ( $type != 'interval' ? ' hide-if-js' : '' )
					),/*
					array(
						'name'  => __( 'Interval', 'learnpress-content-drip' ),
						'id'    => "{$prefix}interval",
						'type'  => 'number',
						'desc'  => '',
						'std'   => '1',
						'min'   => 1,
						'max'   => 99999,
						'class' => 'content-drip-interval' . ( $type != 'interval' ? ' hide-if-js' : '' )
					),
					array(
						'name'    => __( 'Interval Type', 'learnpress-content-drip' ),
						'id'      => "{$prefix}interval_type",
						'type'    => 'radio',
						'desc'    => '',
						'options' => array(
							'day'   => __( 'Day(s)', 'learnpress-content-drip' ),
							'week'  => __( 'Week(s)', 'learnpress-content-drip' ),
							'month' => __( 'Month(s)', 'learnpress-content-drip' )
						),
						'std'     => 'week',
						'class'   => 'content-drip-interval' . ( $type != 'interval' ? ' hide-if-js' : '' )
					),*/
					array(
						'name'   => __( 'Specific Date', 'learnpress-content-drip' ),
						'id'     => "{$prefix}specific_date",
						'type'   => 'date',
						'desc'   => sprintf( __( 'Date in format %s', 'learnpress-content-drip' ), date( 'd M Y' ) ),
						'std'    => '',
						'class'  => 'content-drip-specific-date' . ( $type != 'specific_date' ? ' hide-if-js' : '' ),
						'format' => 'dd M yy'
					)
				)
			)
		);
	}

	/**
	 * @param $meta_box
	 *
	 * @return mixed
	 */
	function course_settings( $meta_box ) {
		if ( $meta_box && $meta_box['id'] == 'course_settings' ) {
			$meta_box['fields'] = array_merge( $meta_box['fields'],
				array(
					array(
						'name'    => __( 'Enable Content Drip', 'learnpress-content-drip' ),
						'id'      => "_lp_content_drip_enable",
						'type'    => 'radio',
						'desc'    => __( 'Enable/Disable content drip for this course', 'learnpress-content-drip' ),
						'std'     => 'no',
						'options' => array(
							'no'  => __( 'No', 'learnpress-content-drip' ),
							'yes' => __( 'Yes', 'learnpress-content-drip' )
						)
					)
				)
			);
		}
		return $meta_box;
	}

	/**
	 * Load plugin text domain
	 */
	public static function load_text_domain() {
		if ( function_exists( 'learn_press_load_plugin_text_domain' ) ) {
			learn_press_load_plugin_text_domain( LP_ADDON_CONTENT_DRIP_PATH, true );
		}
	}

	public static function admin_notice() {
		?>
		<div class="error">
			<p><?php printf( __( '<strong>Content Drip</strong> addon version %s requires LearnPress version %s or higher', 'learnpress-content-drip' ), LP_ADDON_CONTENT_DRIP_VER, LP_ADDON_CONTENT_DRIP_REQUIRE_VER ); ?></p>
		</div>
		<?php
	}

	/**
	 * Return unique instance of LP_Addon_BuddyPress_Course_Profile
	 */
	static function instance() {
		if ( !defined( 'LEARNPRESS_VERSION' ) || ( version_compare( LEARNPRESS_VERSION, LP_ADDON_CONTENT_DRIP_REQUIRE_VER, '<' ) ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'admin_notice' ) );
			return false;
		}
		if ( !self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
}

add_action( 'learn_press_ready', array( 'LP_Addon_Content_Drip', 'instance' ) );
/**
 * Register add-on
 */

//function learn_press_register_content_drip() {
//	require_once( LP_ADDON_CONTENT_DRIP_PATH . '/includes/class-lpr-content-drip.php' );
//}
//
//add_action( 'plugins_loaded', 'learn_press_register_content_drip' );
//
//add_action( 'plugins_loaded', 'learnpress_content_drip_translations' );
//
//function learnpress_content_drip_translations() {
//	$textdomain    = 'learnpress_content_drip';
//	$locale        = apply_filters( "plugin_locale", get_locale(), $textdomain );
//	$lang_dir      = dirname( __FILE__ ) . '/lang/';
//	$mofile        = sprintf( '%s.mo', $locale );
//	$mofile_local  = $lang_dir . $mofile;
//	$mofile_global = WP_LANG_DIR . '/plugins/' . $mofile;
//	if ( file_exists( $mofile_global ) ) {
//		load_textdomain( $textdomain, $mofile_global );
//	} else {
//		load_textdomain( $textdomain, $mofile_local );
//	}
//}