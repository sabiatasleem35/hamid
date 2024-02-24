<?php
if (!defined('ABSPATH')) {
	exit;//Exit if accessed directly
}

class AIOWPSecurity_General_Init_Tasks {
	public function __construct() {
		// Do init time tasks
		global $aio_wp_security;

		if ('1' == $aio_wp_security->configs->get_value('aiowps_disable_xmlrpc_pingback_methods')) {
			add_filter('xmlrpc_methods', array($this, 'aiowps_disable_xmlrpc_pingback_methods'));
			add_filter('wp_headers', array($this, 'aiowps_remove_x_pingback_header'));
		}

		if ('1' == $aio_wp_security->configs->get_value('aiowps_disable_rss_and_atom_feeds')) {
			add_action('do_feed', array($this, 'block_feed'), 1);
			add_action('do_feed_rdf', array($this, 'block_feed'), 1);
			add_action('do_feed_rss', array($this, 'block_feed'), 1);
			add_action('do_feed_rss2', array($this, 'block_feed'), 1);
			add_action('do_feed_atom', array($this, 'block_feed'), 1);
			add_action('do_feed_rss2_comments', array($this, 'block_feed'), 1);
			add_action('do_feed_atom_comments', array($this, 'block_feed'), 1);

			remove_action('wp_head', 'feed_links_extra', 3);
			remove_action('wp_head', 'feed_links', 2);
		}

		// Check permanent block list and block if applicable (ie, do PHP blocking)
		if (!is_user_logged_in()) {
			AIOWPSecurity_Blocking::check_visitor_ip_and_perform_blocking();
		}
		
		if ('1' == $aio_wp_security->configs->get_value('aiowps_enable_spambot_detecting') && '0' == $aio_wp_security->configs->get_value('aiowps_spam_comments_should')) {
			add_action('pre_comment_on_post', array($this, 'spam_detecting_and_process_comments_discard'), 10, 2); //this hook gets fired before comment is added
		}
		
		if ('1' == $aio_wp_security->configs->get_value('aiowps_enable_spambot_detecting') && '1' == $aio_wp_security->configs->get_value('aiowps_spam_comments_should')) {
			add_filter('pre_comment_approved', array($this, 'spam_detecting_and_process_comments_mark_spam'), 10, 2); //this hook filters a comments approval status before it is set
		}

		if ('1' == $aio_wp_security->configs->get_value('aiowps_enable_autoblock_spam_ip')) {
			add_action('comment_post', array($this, 'spam_detect_process_comment_post'), 10, 2); //this hook gets fired just after comment is saved to DB
			add_action('transition_comment_status', array($this, 'process_transition_comment_status'), 10, 3); //this hook gets fired when a comment's status changes
		}

		if ('1' == $aio_wp_security->configs->get_value('aiowps_enable_rename_login_page')) {
			add_action('widgets_init', array($this, 'remove_standard_wp_meta_widget'));
			add_filter('retrieve_password_message', array($this, 'decode_reset_pw_msg'), 10, 4); //Fix for non decoded html entities in password reset link
		}

		if (AIOWPSecurity_Utility_Permissions::has_manage_cap() && is_admin()) {
			if ('1' == $aio_wp_security->configs->get_value('aios_google_recaptcha_invalid_configuration')) {
				add_action('all_admin_notices', array($this, 'google_recaptcha_notice'));
			}

			if (is_main_site() && is_super_admin()) {
				add_action('all_admin_notices', array($this, 'do_firewall_notice'));
				add_action('admin_post_aiowps_firewall_setup', array(AIOWPSecurity_Firewall_Setup_Notice::get_instance(), 'handle_setup_form'));
				add_action('admin_post_aiowps_firewall_downgrade', array(AIOWPSecurity_Firewall_Setup_Notice::get_instance(), 'handle_downgrade_protection_form'));
				add_action('admin_post_aiowps_firewall_setup_dismiss', array(AIOWPSecurity_Firewall_Setup_Notice::get_instance(), 'handle_dismiss_form'));
			}

			$this->reapply_htaccess_rules();
			add_action('admin_notices', array($this,'reapply_htaccess_rules_notice'));
		}

		/**
		 * Send X-Frame-Options: SAMEORIGIN in HTTP header
		 */
		if ('1' == $aio_wp_security->configs->get_value('aiowps_prevent_site_display_inside_frame')) {
			add_action('template_redirect', 'send_frame_options_header');
		}

		if ('1' == $aio_wp_security->configs->get_value('aiowps_remove_wp_generator_meta_info')) {
			add_filter('the_generator', array($this,'remove_wp_generator_meta_info'));
			add_filter('style_loader_src', array($this,'remove_wp_css_js_meta_info'));
			add_filter('script_loader_src', array($this,'remove_wp_css_js_meta_info'));
		}

		// For the cookie based brute force prevention feature
		// Already logged in user should not redirected to brute_force_redirect_url in any case so added condition !is_user_logged_in()
		if ('1' == $aio_wp_security->configs->get_value('aiowps_enable_brute_force_attack_prevention')) {
			$bfcf_secret_word = $aio_wp_security->configs->get_value('aiowps_brute_force_secret_word');
			if (isset($_GET[$bfcf_secret_word])) {
				AIOWPSecurity_Utility_IP::check_login_whitelist_and_forbid();

				// If URL contains secret word in query param then set cookie and then redirect to the login page
				AIOWPSecurity_Utility::set_cookie_value(AIOWPSecurity_Utility::get_brute_force_secret_cookie_name(), wp_hash($bfcf_secret_word));
				if ('1' == $aio_wp_security->configs->get_value('aiowps_enable_rename_login_page') && !is_user_logged_in()) {
					$login_url = home_url((get_option('permalink_structure') ? '' : '?')  . $aio_wp_security->configs->get_value('aiowps_login_page_slug'));
					AIOWPSecurity_Utility::redirect_to_url($login_url);
				} elseif (!is_user_logged_in()) {
					AIOWPSecurity_Utility::redirect_to_url(AIOWPSEC_WP_URL.'/wp-admin');
				}
			}
		}
		// Stop users enumeration feature
		if (1 == $aio_wp_security->configs->get_value('aiowps_prevent_users_enumeration')) {
			include_once(AIO_WP_SECURITY_PATH.'/other-includes/wp-security-stop-users-enumeration.php');
			add_filter('rest_request_before_callbacks', array($this, 'rest_request_before_callbacks'), 10, 1);
			add_filter('oembed_response_data', array($this, 'oembed_response_data'), 10, 1);
		}

		// REST API security
		if ($aio_wp_security->configs->get_value('aiowps_disallow_unauthorized_rest_requests') == 1) {
			add_action('rest_api_init', array($this, 'check_rest_api_requests'), 10, 1);
		}

		// For user unlock request feature
		if (isset($_POST['aiowps_unlock_request']) || isset($_POST['aiowps_wp_submit_unlock_request'])) {
			nocache_headers();
			remove_action('wp_head', 'head_addons', 7);
			include_once(AIO_WP_SECURITY_PATH.'/other-includes/wp-security-unlock-request.php');
			exit();
		}

		if (isset($_GET['aiowps_auth_key'])) {
			//If URL contains unlock key in query param then process the request
			$unlock_key = sanitize_text_field($_GET['aiowps_auth_key']);
			AIOWPSecurity_User_Login::process_unlock_request($unlock_key);
		}

		// For honeypot feature
		if (isset($_POST['aio_special_field'])) {
			$special_field_value = sanitize_text_field($_POST['aio_special_field']);
			if (!empty($special_field_value)) {
				//This means a robot has submitted the login form!
				//Redirect back to its localhost
				AIOWPSecurity_Utility::redirect_to_url('http://127.0.0.1');
			}
		}

		// For 404 IP lockout feature
		if ($aio_wp_security->configs->get_value('aiowps_enable_404_IP_lockout') == '1') {
			if (!is_user_logged_in() || !current_user_can('administrator')) {
				$this->do_404_lockout_tasks();
			}
		}


		// For login CAPTCHA feature
		if ($aio_wp_security->configs->get_value('aiowps_enable_login_captcha') == '1') {
			if (!is_user_logged_in()) {
				add_action('login_form', array($aio_wp_security->captcha_obj, 'insert_captcha_question_form'));
			}
		}

		// For woo form CAPTCHA features
		if ($aio_wp_security->configs->get_value('aiowps_enable_woo_login_captcha') == '1') {
			if (!is_user_logged_in()) {
				add_action('woocommerce_login_form', array($aio_wp_security->captcha_obj, 'insert_captcha_question_form'));
			}
			if (isset($_POST['woocommerce-login-nonce'])) {
				add_filter('woocommerce_process_login_errors', array($this, 'aiowps_validate_woo_login_or_reg_captcha'), 10, 3);
			}
		}

		if ($aio_wp_security->configs->get_value('aiowps_enable_woo_register_captcha') == '1') {
			if (!is_user_logged_in()) {
				add_action('woocommerce_register_form', array($aio_wp_security->captcha_obj, 'insert_captcha_question_form'));
			}

			if (isset($_POST['woocommerce-register-nonce'])) {
				add_filter('woocommerce_process_registration_errors', array($this, 'aiowps_validate_woo_login_or_reg_captcha'), 10, 3);
			}
		}

		if ($aio_wp_security->configs->get_value('aiowps_enable_woo_lostpassword_captcha') == '1') {
			if (!is_user_logged_in()) {
				add_action('woocommerce_lostpassword_form', array($aio_wp_security->captcha_obj, 'insert_captcha_question_form'));
			}
			if (isset($_POST['woocommerce-lost-password-nonce'])) {
				add_action('lostpassword_post', array($this, 'process_woo_lost_password_form_post'));
			}
		}

		// For bbPress new topic form CAPTCHA
		if ($aio_wp_security->configs->get_value('aiowps_enable_bbp_new_topic_captcha') == '1') {
			if (!is_user_logged_in()) {
				add_action('bbp_theme_before_topic_form_submit_wrapper', array($aio_wp_security->captcha_obj, 'insert_captcha_question_form'));
			}
		}

		// For custom login form CAPTCHA feature, ie, when wp_login_form() function is used to generate login form
		if ('1' == $aio_wp_security->configs->get_value('aiowps_enable_custom_login_captcha') && !is_user_logged_in()) {
			add_filter('login_form_middle', array($aio_wp_security->captcha_obj, 'insert_captcha_custom_login'), 10, 2); //For cases where the WP wp_login_form() function is used
			add_filter('login_form_bottom', array($aio_wp_security->captcha_obj, 'add_captcha_script'), 20);
		}

		// For honeypot feature
		if ($aio_wp_security->configs->get_value('aiowps_enable_login_honeypot') == '1') {
			if (!is_user_logged_in()) {
				add_action('login_form', array($this, 'insert_honeypot_hidden_field'));
			}
		}

		// For registration honeypot feature
		if ($aio_wp_security->configs->get_value('aiowps_enable_registration_honeypot') == '1') {
			if (!is_user_logged_in()) {
				add_action('register_form', array($this, 'insert_honeypot_hidden_field'));
			}
		}

		// For disable application password feature hide generate password
		if ('1' == $aio_wp_security->configs->get_value('aiowps_disable_application_password')) {
			add_filter('wp_is_application_passwords_available', '__return_false');
			add_action('edit_user_profile', array($this, 'show_disabled_application_password_message'));
			add_action('show_user_profile', array($this, 'show_disabled_application_password_message'));

			// Override the wp_die handler for app passwords were disabled.
			if (!empty($_SERVER['SCRIPT_FILENAME']) && ABSPATH . 'wp-admin/authorize-application.php' == $_SERVER['SCRIPT_FILENAME']) {
				add_filter('wp_die_handler', function () {
					return function ($message, $title, $args) {
						if ('Application passwords are not available.' == $message) {
							$message = htmlspecialchars(__('Application passwords have been disabled by All In One WP Security & Firewall plugin.', 'all-in-one-wp-security-and-firewall'));
						}
						_default_wp_die_handler($message, $title, $args);
					};
				}, 10, 1);
			}
		}

		// For lost password CAPTCHA feature
		if ($aio_wp_security->configs->get_value('aiowps_enable_lost_password_captcha') == '1') {
			if (!is_user_logged_in()) {
				add_action('lostpassword_form', array($aio_wp_security->captcha_obj, 'insert_captcha_question_form'));
				add_action('lostpassword_post', array($this, 'process_lost_password_form_post'));
			}
		}

		// For registration manual approval feature
		if ($aio_wp_security->configs->get_value('aiowps_enable_manual_registration_approval') == '1') {
			add_filter('wp_login_errors', array($this, 'modify_registration_page_messages'), 10, 2);
		}

		// For registration page CAPTCHA feature
		if (is_multisite()) {
			$blog_id = get_current_blog_id();
			switch_to_blog($blog_id);
			if ($aio_wp_security->configs->get_value('aiowps_enable_registration_page_captcha') == '1') {
				if (!is_user_logged_in()) {
					add_action('signup_extra_fields', array($this, 'insert_captcha_question_form_multi'));
					//add_action('preprocess_signup_form', array($this, 'process_signup_form_multi'));
					add_filter('wpmu_validate_user_signup', array($this, 'process_signup_form_multi'));
				}
			}
			restore_current_blog();
		} else {
			if ($aio_wp_security->configs->get_value('aiowps_enable_registration_page_captcha') == '1') {
				if (!is_user_logged_in()) {
					add_action('register_form', array($aio_wp_security->captcha_obj, 'insert_captcha_question_form'));
				}
			}
		}

		// For comment CAPTCHA feature or custom login form CAPTCHA
		if (is_multisite()) {
			$blog_id = get_current_blog_id();
			switch_to_blog($blog_id);
			if ('1' == $aio_wp_security->configs->get_value('aiowps_enable_comment_captcha') && !is_user_logged_in()) {
				add_action('comment_form_after_fields', array($aio_wp_security->captcha_obj, 'insert_captcha_question_form'), 1);
				add_action('comment_form_after_fields', array($aio_wp_security->captcha_obj, 'add_captcha_script'), 10);
				add_filter('preprocess_comment', array($this, 'process_comment_post'));
			}
			restore_current_blog();
		} else {
			if ('1' == $aio_wp_security->configs->get_value('aiowps_enable_comment_captcha') && !is_user_logged_in()) {
				add_action('comment_form_after_fields', array($aio_wp_security->captcha_obj, 'insert_captcha_question_form'), 1);
				add_action('comment_form_after_fields', array($aio_wp_security->captcha_obj, 'add_captcha_script'), 10);
				add_filter('preprocess_comment', array($this, 'process_comment_post'));
			}
		}

		// For BuddyPress registration CAPTCHA feature
		if ($aio_wp_security->configs->get_value('aiowps_enable_bp_register_captcha') == '1') {
			add_action('bp_account_details_fields', array($aio_wp_security->captcha_obj, 'insert_captcha_question_form'));
			add_action('bp_signup_validate', array($this, 'buddy_press_signup_validate_captcha'));
		}


		// For block fake Googlebots feature
		if ($aio_wp_security->configs->get_value('aiowps_block_fake_googlebots') == '1') {
			include_once(AIO_WP_SECURITY_PATH.'/classes/wp-security-bot-protection.php');
			AIOWPSecurity_Fake_Bot_Protection::block_fake_googlebots();
		}

		// For 404 event logging
		if ($aio_wp_security->configs->get_value('aiowps_enable_404_logging') == '1') {
			add_action('wp_head', array($this, 'check_404_event'));
		}

		// For antibot post page set cookies.
		if ('1' == $aio_wp_security->configs->get_value('aiowps_enable_spambot_detecting')) {
			add_action('template_redirect', array($this, 'post_antibot_cookie'));
		}

		// For delete readme.html and wp-config-sample.php.
		if ('1' == $aio_wp_security->configs->get_value('aiowps_auto_delete_default_wp_files')) {
			add_action('upgrader_process_complete', array($this, 'delete_unneeded_files_after_upgrade'), 10, 2);
		}

		// Add more tasks that need to be executed at init time

	} // end _construct()

	public function aiowps_disable_xmlrpc_pingback_methods($methods) {
		unset($methods['pingback.ping']);
		unset($methods['pingback.extensions.getPingbacks']);
		return $methods;
	}

	public function aiowps_remove_x_pingback_header($headers) {
		unset($headers['X-Pingback']);
		return $headers;
	}

	/**
	 * Blocks feed by redirecting user to home url.
	 *
	 * @return Void
	 */
	public function block_feed() {
		wp_redirect(home_url());
	}
	
	/**
	 * Spam detection and discard comment.
	 *
	 * @param int $comment_post_id
	 *
	 * @return void
	 */
	public function spam_detecting_and_process_comments_discard($comment_post_id) {
		$is_comment_should_not_allowed = AIOWPSecurity_Comment::is_comment_spam_detected();
		if ($is_comment_should_not_allowed) {
			$this->spam_discard_auto_block_ip();
			$comments = get_comments(array('number' => '1', 'post_id' => $comment_post_id));
			if ($comments) {
				$loc = get_comment_link($comments[0]->comment_ID);
			} else {
				$loc = get_permalink($comment_post_id) . '#spam-comment-msg';
			}
			wp_safe_redirect($loc);
			exit;
		}
	}

	/**
	 * Block IP for spam discard after minimum comments reached.
	 *
	 * @return void
	 */
	public function spam_discard_auto_block_ip() {
		global $aio_wp_security, $wpdb;
		
		if ('1' != $aio_wp_security->configs->get_value('aiowps_enable_autoblock_spam_ip')) return;
		
		AIOWPSecurity_Utility::event_logger('spam_discard');
		$comment_ip = AIOWPSecurity_Utility_IP::get_user_ip_address();
		$spam_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM ".AIOWPSEC_TBL_EVENTS." WHERE ip_or_host = %s AND event_type=%s", $comment_ip, 'spam_discard'));
		$min_comment_before_block = $aio_wp_security->configs->get_value('aiowps_spam_ip_min_comments_block');
		if (!empty($min_comment_before_block) && $spam_count > $min_comment_before_block) {
			AIOWPSecurity_Blocking::add_ip_to_block_list($comment_ip, 'spam_discard');
		}
	}

	/**
	 * Spam detection and mark as spam comment.
	 *
	 * @param string $approved
	 *
	 * @return string status
	 */
	public function spam_detecting_and_process_comments_mark_spam($approved) {
		return AIOWPSecurity_Comment::is_comment_spam_detected() ? 'spam' : $approved;
	}

	public function spam_detect_process_comment_post($comment_id, $comment_approved) {
		if ("spam" === $comment_approved) {
			$this->block_comment_ip($comment_id);
		}

	}

	public function process_transition_comment_status($new_status, $old_status, $comment) {
		if ('spam' == $new_status) {
			$this->block_comment_ip($comment->comment_ID);
		}

	}

	/**
	 * Will check auto-spam blocking settings and will add IP to blocked table accordingly
	 *
	 * @param int $comment_id
	 */
	public function block_comment_ip($comment_id) {
		global $aio_wp_security, $wpdb;
		$comment_obj = get_comment($comment_id);
		$comment_ip = $comment_obj->comment_author_IP;
		//Get number of spam comments from this IP
		$sql = $wpdb->prepare("SELECT * FROM $wpdb->comments
				WHERE comment_approved = 'spam'
				AND comment_author_IP = %s
				", $comment_ip);
		$comment_data = $wpdb->get_results($sql, ARRAY_A);
		$spam_count = count($comment_data);
		$min_comment_before_block = $aio_wp_security->configs->get_value('aiowps_spam_ip_min_comments_block');
		if (!empty($min_comment_before_block) && $spam_count >= ($min_comment_before_block - 1)) {
			AIOWPSecurity_Blocking::add_ip_to_block_list($comment_ip, 'spam');
		}
	}

	public function remove_standard_wp_meta_widget() {
		unregister_widget('WP_Widget_Meta');
	}

	public function remove_wp_generator_meta_info() {
		return '';
	}

	/**
	 * This function removes wp meta info from style and script src
	 *
	 * @param string|null $src - the src for the style or script
	 * @return string
	 */
	public function remove_wp_css_js_meta_info($src) {
		global $wp_version;
		static $wp_version_hash = null; // Cache hash value for all function calls

		if (empty($src)) return '';

		// Replace only version number of assets with WP version
		if (strpos($src, 'ver=' . $wp_version) !== false) {
			if (!$wp_version_hash) {
				$wp_version_hash = wp_hash($wp_version);
			}
			// Replace version number with computed hash
			$src = add_query_arg('ver', $wp_version_hash, $src);
		}
		return $src;
	}

	public function do_404_lockout_tasks() {
		global $aio_wp_security;
		$redirect_url = $aio_wp_security->configs->get_value('aiowps_404_lock_redirect_url'); //This is the redirect URL for blocked users

		$visitor_ip = AIOWPSecurity_Utility_IP::get_user_ip_address();

		$is_locked = AIOWPSecurity_Utility::check_locked_ip($visitor_ip, '404');

		if ($is_locked) {
			//redirect blocked user to configured URL
			AIOWPSecurity_Utility::redirect_to_url($redirect_url);
		} else {
			//allow through
		}
	}

	public function insert_captcha_question_form_multi() {
		global $aio_wp_security;
		$default_captcha = $aio_wp_security->configs->get_value('aiowps_default_captcha');
		$aio_wp_security->captcha_obj->display_captcha_form($default_captcha);
	}

	public function process_signup_form_multi($result) {
		global $aio_wp_security;
		// Check if CAPTCHA enabled
		$verify_captcha = $aio_wp_security->captcha_obj->verify_captcha_submit();
		if (false === $verify_captcha) {
			// wrong answer was entered
			$result['errors']->add('generic', __('<strong>ERROR</strong>: Your answer was incorrect - please try again.', 'all-in-one-wp-security-and-firewall'));
		}
		return $result;
	}

	/**
	 * This function echos a honeypot hidden field into login or register form
	 *
	 * @return void
	 */
	public function insert_honeypot_hidden_field() {
		$honey_input = '<p style="display: none;"><label>'.__('Enter something special:', 'all-in-one-wp-security-and-firewall').'</label>';
		$honey_input .= '<input name="aio_special_field" type="text" class="aio_special_field" value="" /></p>';
		echo $honey_input;
	}

	/**
	 * Shows application password disabled message on user edit profile page.
	 * If logged user is admin showing the Change Setting option.
	 *
	 * @return void
	 */
	public function show_disabled_application_password_message() {
		if (is_user_logged_in() && is_admin()) {
			$disabled_message =	'<h2>'.__('Application passwords', 'all-in-one-wp-security-and-firewall').'</h2>';
			$disabled_message .= '<table class="form-table" role="presentation">';
			$disabled_message .= '<tbody>';
			$disabled_message .= '<tr id="disable-password">';
			$disabled_message .= '<th>'.__('Disabled').'</th>';
			$disabled_message .= '<td>'.htmlspecialchars(__('Application passwords have been disabled by All In One WP Security & Firewall plugin.', 'all-in-one-wp-security-and-firewall'));
			if (AIOWPSecurity_Utility_Permissions::has_manage_cap()) {
				$aiowps_additional_setting_url = 'admin.php?page=aiowpsec_userlogin&tab=additional';
				$change_setting_url = is_multisite() ? network_admin_url($aiowps_additional_setting_url) : admin_url($aiowps_additional_setting_url);
				$disabled_message .= '<p><a href="'.$change_setting_url.'"  class="button">'.__('Change setting', 'all-in-one-wp-security-and-firewall').'</a></p>';
			} else {
				$disabled_message .= ' '.__('Site admin can only change this setting.', 'all-in-one-wp-security-and-firewall');
			}
			$disabled_message .= '</td>';
			$disabled_message .= '</tr>';
			$disabled_message .= '<tbody>';
			$disabled_message .= '</table>';
			echo $disabled_message;
		}
	}

	public function process_comment_post($comment) {
		global $aio_wp_security;
		if (is_user_logged_in()) {
				return $comment;
		}

		// Don't process CAPTCHA for comment replies inside admin menu
		if (isset($_REQUEST['action']) && 'replyto-comment' == $_REQUEST['action'] && (check_ajax_referer('replyto-comment', '_ajax_nonce', false) || check_ajax_referer('replyto-comment', '_ajax_nonce-replyto-comment', false))) {
			return $comment;
		}

		// Don't do CAPTCHA for pingback/trackback
		if ('' != $comment['comment_type'] && 'comment' != $comment['comment_type'] && 'review' != $comment['comment_type']) {
			return $comment;
		}

		$verify_captcha = $aio_wp_security->captcha_obj->verify_captcha_submit();
		if (false === $verify_captcha) {
			//Wrong answer
			wp_die(__('Error: You entered an incorrect CAPTCHA answer, please go back and try again.', 'all-in-one-wp-security-and-firewall'));
		} else {
			return($comment);
		}
	}

	/**
	 * Process the main Wordpress account lost password login form post
	 * Called by wp hook "lostpassword_post"
	 */
	public function process_lost_password_form_post() {
		global $aio_wp_security;

		// Workaround - the WooCommerce lost password form also uses the same "lostpassword_post" hook.
		// We don't want to process woo forms here so ignore if this is a woo lost password $_POST
		if (!array_key_exists('woocommerce-lost-password-nonce', $_POST)) {
			$verify_captcha = $aio_wp_security->captcha_obj->verify_captcha_submit();
			if (false === $verify_captcha) {
				add_filter('allow_password_reset', array($this, 'add_lostpassword_captcha_error_msg'));
			}
		}
	}

	public function add_lostpassword_captcha_error_msg() {
		//Insert an error just before the password reset process kicks in
		return new WP_Error('aiowps_captcha_error', __('<strong>ERROR</strong>: Your answer was incorrect - please try again.', 'all-in-one-wp-security-and-firewall'));
	}

	public function check_404_event() {
		if (is_404()) {
			//This means a 404 event has occurred - let's log it!
			AIOWPSecurity_Utility::event_logger('404');
		}

	}

	/**
	 * Deletes unneeded default WP files if they're regenerated after core upgrade.
	 *
	 * @param WP_Upgrader $upgrader
	 * @param array       $hook_extra
	 *
	 * @return void
	 */
	public function delete_unneeded_files_after_upgrade($upgrader, $hook_extra) {
		if (empty($hook_extra)) {
			return;
		}

		if (isset($hook_extra['action']) && 'update' == $hook_extra['action'] && isset($hook_extra['type']) && 'core' == $hook_extra['type']) {
			AIOWPSecurity_Utility::delete_unneeded_default_files();
		}
	}

	public function buddy_press_signup_validate_captcha() {
		global $bp, $aio_wp_security;
		// Check CAPTCHA if required
		$verify_captcha = $aio_wp_security->captcha_obj->verify_captcha_submit();
		if (false === $verify_captcha) {
			// wrong answer was entered
			$bp->signup->errors['aiowps-captcha-answer'] = __('Your CAPTCHA answer was incorrect - please try again.', 'all-in-one-wp-security-and-firewall');
		}
		return;
	}

	public function aiowps_validate_woo_login_or_reg_captcha($errors) {
		global $aio_wp_security;
		$locked = $aio_wp_security->user_login_obj->check_locked_user();
		if (!empty($locked)) {
			$errors->add('authentication_failed', __('<strong>ERROR</strong>: Your IP address is currently locked please contact the administrator!', 'all-in-one-wp-security-and-firewall'));
			return $errors;
		}

		$verify_captcha = $aio_wp_security->captcha_obj->verify_captcha_submit();
		if (false === $verify_captcha) {
			// wrong answer was entered
			$errors->add('authentication_failed', __('<strong>ERROR</strong>: Your answer was incorrect - please try again.', 'all-in-one-wp-security-and-firewall'));
		}
		return $errors;

	}

	/**
	 * Process the WooCommerce lost password login form post
	 * Called by wp hook "lostpassword_post"
	 */
	public function process_woo_lost_password_form_post() {
		global $aio_wp_security;

		if (isset($_POST['woocommerce-lost-password-nonce'])) {
			$verify_captcha = $aio_wp_security->captcha_obj->verify_captcha_submit();
			if (false === $verify_captcha) {
				add_filter('allow_password_reset', array($this, 'add_lostpassword_captcha_error_msg'));
			}
		}
	}

	/**
	 * Reapply htaccess rule or dismiss the related notice.
	 *
	 * @return void
	 */
	public function reapply_htaccess_rules() {
		if (isset($_REQUEST['aiowps_reapply_htaccess'])) {
			global $aio_wp_security;

			if (strip_tags($_REQUEST['aiowps_reapply_htaccess']) == 1) {
				$result = AIOWPSecurity_Utility_Permissions::check_nonce_and_user_cap($_GET['_wpnonce'], 'aiowps-reapply-htaccess-yes');
				if (is_wp_error($result)) {
					$aio_wp_security->debug_logger->log_debug($result->get_error_message(), 4);
					die($result->get_error_message());
				}
				include_once('wp-security-installer.php');
				if (AIOWPSecurity_Installer::reactivation_tasks()) {
					$aio_wp_security->debug_logger->log_debug('The AIOS .htaccess rules were successfully re-inserted.');
					$_SESSION['reapply_htaccess_rules_action_result'] = '1';//Success indicator.
					// Can't echo to the screen here. It will create an header already sent error.
				} else {
					$aio_wp_security->debug_logger->log_debug('AIOS encountered an error when trying to write to your .htaccess file. Please check the logs.', 5);
					$_SESSION['reapply_htaccess_rules_action_result'] = '2';//fail indicator.
					// Can't echo to the screen here. It will create an header already sent error.
				}
			} elseif (strip_tags($_REQUEST['aiowps_reapply_htaccess']) == 2) {
				$result = AIOWPSecurity_Utility_Permissions::check_nonce_and_user_cap($_GET['_wpnonce'], 'aiowps-reapply-htaccess-no');
				if (is_wp_error($result)) {
					$aio_wp_security->debug_logger->log_debug($result->get_error_message(), 4);
					return;
				}
				// Don't re-write the rules and just delete the temp config item
				delete_option('aiowps_temp_configs');
			}
		}
	}

	/**
	 * Displays a notice message if the entered recatcha site key is wrong.
	 */
	public function google_recaptcha_notice() {
		global $aio_wp_security;

		if (($aio_wp_security->is_admin_dashboard_page() || $aio_wp_security->is_plugin_admin_page() || $aio_wp_security->is_aiowps_admin_page()) && !$aio_wp_security->is_aiowps_google_recaptcha_tab_page()) {
			$recaptcha_tab_url = 'admin.php?page='.AIOWPSEC_BRUTE_FORCE_MENU_SLUG.'&tab=login-captcha';
			echo '<div class="notice notice-warning"><p>';
			/* translators: %s: Admin Dashboard > WP Security > Brute Force > Login CAPTCHA Tab Link */
			printf(__('Your Google reCAPTCHA configuration is invalid.', 'all-in-one-wp-security-and-firewall').' '.__('Please enter the correct reCAPTCHA keys %s to use the Google reCAPTCHA feature.', 'all-in-one-wp-security-and-firewall'), '<a href="'.esc_url($recaptcha_tab_url).'">'.__('here', 'all-in-one-wp-security-and-firewall').'</a>');
			echo '</p></div>';
		}
	}

	/**
	 * Displays a notice message if the plugin is reactivated which gives users the option of re-applying the AIOS rules which were deleted from the .htaccess file at the last deactivation.
	 *
	 * @return Void
	 */
	public function reapply_htaccess_rules_notice() {
		if (false !== get_option('aiowps_temp_configs')) {
			$reapply_htaccess_yes_url = wp_nonce_url('admin.php?page='.AIOWPSEC_MENU_SLUG_PREFIX.'&aiowps_reapply_htaccess=1', 'aiowps-reapply-htaccess-yes');
			$reapply_htaccess_no_url  = wp_nonce_url('admin.php?page='.AIOWPSEC_MENU_SLUG_PREFIX.'&aiowps_reapply_htaccess=2', 'aiowps-reapply-htaccess-no');

			if (is_main_site() && is_super_admin()) {
				echo '<div class="updated"><p>'.htmlspecialchars(__('Would you like All In One WP Security & Firewall to restore the config settings and re-insert the security rules in your .htaccess file which were cleared when you deactivated the plugin?', 'all-in-one-wp-security-and-firewall')).'&nbsp;&nbsp;<a href="'.esc_url($reapply_htaccess_yes_url).'" class="button-primary">'.__('Yes', 'all-in-one-wp-security-and-firewall').'</a>&nbsp;&nbsp;<a href="'.esc_url($reapply_htaccess_no_url).'" class="button-primary">'.__('No', 'all-in-one-wp-security-and-firewall').'</a></p></div>';
			} elseif (!is_main_site()) {
				echo '<div class="updated"><p>'.htmlspecialchars(__('Would you like All In One WP Security & Firewall to restore the config settings which were cleared when you deactivated the plugin?', 'all-in-one-wp-security-and-firewall')).'&nbsp;&nbsp;<a href="'.esc_url($reapply_htaccess_yes_url).'" class="button-primary">'.__('Yes', 'all-in-one-wp-security-and-firewall').'</a>&nbsp;&nbsp;<a href="'.esc_url($reapply_htaccess_no_url).'" class="button-primary">'.__('No', 'all-in-one-wp-security-and-firewall').'</a></p></div>';
			}
		}
	}

	/**
	 * This is a fix for cases when the password reset URL in the email was not decoding all html entities properly
	 *
	 * @param string $message
	 * @return string
	 */
	public function decode_reset_pw_msg($message) {
		$message = html_entity_decode($message);
		return $message;
	}

	public function modify_registration_page_messages($errors) {
		if (isset($_GET['checkemail']) && 'registered' == $_GET['checkemail']) {
			if (is_wp_error($errors)) {
				$errors->remove('registered');
				$pending_approval_msg = __('Your registration is pending approval.', 'all-in-one-wp-security-and-firewall');
				$pending_approval_msg = apply_filters('aiowps_pending_registration_message', $pending_approval_msg);
				$errors->add('registered', $pending_approval_msg, array('registered' => 'message'));
			}
		}
		return $errors;
	}

	/**
	 * Re-wrote code which checks for REST API requests
	 * Below uses the "rest_api_init" action hook to check for REST requests.
	 * The code will block "unauthorized" requests whilst allowing genuine requests.
	 * (P. Petreski June 2018)
	 *
	 * @return void
	 */
	public function check_rest_api_requests() {
		$rest_user = wp_get_current_user();
		if (empty($rest_user->ID)) {
			$error_message = apply_filters('aiowps_rest_api_error_message', __('You are not authorized to perform this action.', 'disable-wp-rest-api'));
			wp_die($error_message);
		}
	}

	/**
	 * Shows the firewall notice
	 *
	 * @return void
	 */
	public function do_firewall_notice() {
		
		$firewall_setup = AIOWPSecurity_Firewall_Setup_Notice::get_instance();
		$firewall_setup->start_firewall_setup();

	}
	
	/**
	 * Called by the WP filter rest_request_before_callbacks
	 *
	 * @param WP_REST_Response $response
	 *
	 * @return WP_REST_Response|WP_Error $response
	 */
	public function rest_request_before_callbacks($response) {
		$rest_route = !empty($_GET['rest_route']) ? $_GET['rest_route'] : (empty($_SERVER['REQUEST_URI']) ? '' : (string) parse_url(urldecode($_SERVER['REQUEST_URI']), PHP_URL_PATH));
		$rest_route = trim($rest_route, '/');
		if ('' != $rest_route && !current_user_can('edit_others_posts')) {
			if (preg_match('/wp\/v2\/users$/i', $rest_route)) {
				$error = new WP_Error('aios_user_lists_forbidden', __('Listing users is forbidden.', 'all-in-one-wp-security-and-firewall'));
				$response = rest_ensure_response($error);
			} elseif (preg_match('/wp\/v2\/users\/+(\d+)$/i', $rest_route, $matches)) {
				$id = empty($matches) ? 0 : (int) $matches[1];
				if (get_current_user_id() !== $id) {
					$error = new WP_Error('aios_user_details_forbidden', __('Accessing user details is forbidden.', 'all-in-one-wp-security-and-firewall'), array('status' => 403));
					$response = rest_ensure_response($error);
				}
			}
		}
		return $response;
	}
	
	/**
	 * Called by the WP filter oembed_response_data
	 *
	 * @param Array $data
	 *
	 * @return Array $data
	 */
	public function oembed_response_data($data) {
		unset($data['author_name']);
		unset($data['author_url']);
		return $data;
	}
	
	/**
	 * Sets the antibot cookie for post page comment form
	 *
	 * @return void
	 */
	public function post_antibot_cookie() {
		if (is_singular() || is_archive()) {
			AIOWPSecurity_Comment::insert_antibot_keys_in_cookie();
		}
	}
}
