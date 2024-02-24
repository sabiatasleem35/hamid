<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class AIOWPSecurity_Misc_Options_Menu extends AIOWPSecurity_Admin_Menu {

	/**
	 * Miscellaneous menu slug
	 *
	 * @var string
	 */
	protected $menu_page_slug = AIOWPSEC_MISC_MENU_SLUG;
	
	/**
	 * Constructor adds menu for Miscellaneous
	 */
	public function __construct() {
		parent::__construct(__('Miscellaneous', 'all-in-one-wp-security-and-firewall'));
	}

	/**
	 * This function will setup the menus tabs by setting the array $menu_tabs
	 *
	 * @return void
	 */
	protected function setup_menu_tabs() {
		$menu_tabs = array(
			'copy-protection' => array(
				'title' => __('Copy protection', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_copy_protection'),
			),
			'frames' => array(
				'title' => __('Frames', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_frames'),
			),
			'user-enumeration' => array(
				'title' => __('User enumeration', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_user_enumeration'),
			),
			'wp-rest-api' => array(
				'title' => __('WP REST API', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_wp_rest_api'),
			),
			'salt' => array(
				'title' => __('Salt', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_salt_tab'),
				'display_condition_callback' => array('AIOWPSecurity_Utility_Permissions', 'is_main_site_and_super_admin'),
			),
		);

		$this->menu_tabs = array_filter($menu_tabs, array($this, 'should_display_tab'));
	}

	/**
	 * Renders the submenu's copy protection tab
	 *
	 * @return Void
	 */
	protected function render_copy_protection() {
		global $aio_wp_security;
		$aio_wp_security->include_template('wp-admin/general/moved.php', false, array('key' => 'copy-protection'));
	}

	/**
	 * Renders the submenu's render frames tab
	 *
	 * @return Void
	 */
	protected function render_frames() {
		global $aio_wp_security;
		$aio_wp_security->include_template('wp-admin/general/moved.php', false, array('key' => 'frames'));
	}

	/**
	 * Renders the submenu's user enumeration tab
	 *
	 * @return Void
	 */
	protected function render_user_enumeration() {
		global $aio_wp_security;

		$aio_wp_security->include_template('wp-admin/general/moved.php', false, array('key' => AIOWPSEC_USER_SECURITY_MENU_SLUG));
	}

	/**
	 * Renders the submenu's WP REST API tab
	 *
	 * @return Void
	 */
	protected function render_wp_rest_api() {
		global $aio_wp_security;

		$aio_wp_security->include_template('wp-admin/general/moved.php', false, array('key' => AIOWPSEC_MISC_MENU_SLUG));
	}

	/**
	 * Renders the submenu's salt tab
	 *
	 * @return Void
	 */
	protected function render_salt_tab() {
		global $aio_wp_security;
		$aio_wp_security->include_template('wp-admin/general/moved.php', false, array('key' => 'salt'));
	}
}
