<?php
if (!defined('ABSPATH')) {
	exit;//Exit if accessed directly
}

/**
 * AIOWPSecurity_Blacklist_Menu class for banning ips and user agents.
 *
 * @access public
 */
class AIOWPSecurity_Blacklist_Menu extends AIOWPSecurity_Admin_Menu {

	/**
	 * Blacklist menu slug
	 *
	 * @var string
	 */
	protected $menu_page_slug = AIOWPSEC_BLACKLIST_MENU_SLUG;
	
	/**
	 * Constructor adds menu for Blacklist manager
	 */
	public function __construct() {
		parent::__construct(__('Blacklist manager', 'all-in-one-wp-security-and-firewall'));
	}

	/**
	 * This function will setup the menus tabs by setting the array $menu_tabs
	 *
	 * @return void
	 */
	protected function setup_menu_tabs() {
		$menu_tabs = array(
			'ban-users' => array(
				'title' => __('Ban users', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_ban_users'),
			),
		);

		$this->menu_tabs = array_filter($menu_tabs, array($this, 'should_display_tab'));
	}

	/**
	 * Renders ban user tab for blacklist IPs and user agents
	 *
	 * @global $aio_wp_security
	 */
	protected function render_ban_users() {
		global $aio_wp_security;

		$aio_wp_security->include_template('wp-admin/general/moved.php', false, array('key' => AIOWPSEC_BLACKLIST_MENU_SLUG));
	}

}
