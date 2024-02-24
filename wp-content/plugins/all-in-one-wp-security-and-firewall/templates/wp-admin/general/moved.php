<?php if (!defined('ABSPATH')) die('No direct access.'); ?>
<?php

$info = array(
	AIOWPSEC_BLACKLIST_MENU_SLUG => array(
		'title' => __('Blacklist Manager', 'all-in-one-wp-security-and-firewall'),
		'uri' => AIOWPSEC_FIREWALL_MENU_SLUG . '&tab=blacklist'
	),
	'404_detection' => array(
		'title' => '404 Detection',
		'uri' => AIOWPSEC_BRUTE_FORCE_MENU_SLUG . '&tab=404-detection'
	),
	'salt' => array(
		'title' => 'Salt',
		'uri' => AIOWPSEC_USER_SECURITY_MENU_SLUG . '&tab=salt'
	),
	'copy-protection' => array(
		'title' => __('Copy Protection', 'all-in-one-wp-security-and-firewall'),
		'uri' => AIOWPSEC_FILESYSTEM_MENU_SLUG.'&tab=copy-protection',
	),
	'frames' => array(
		'title' => __('Frames', 'all-in-one-wp-security-and-firewall'),
		'uri' => AIOWPSEC_FILESYSTEM_MENU_SLUG.'&tab=frames'
	),
	AIOWPSEC_MISC_MENU_SLUG => array(
		'title' => 'WP REST API',
		'uri' => AIOWPSEC_FIREWALL_MENU_SLUG . '&tab=wp-rest-api'
	),
	AIOWPSEC_USER_SECURITY_MENU_SLUG => array(
		'title' => 'User enumeration',
		'uri' => AIOWPSEC_USER_SECURITY_MENU_SLUG . '&tab=wp-user_accounts'
	)
);

if (empty($info)) return;
?>
<div class="postbox">
	<h3 class="hndle"><label for="title"><?php echo $info[$key]['title']; ?></label></h3>
	<div class="inside">
		<?php
			$new_location_link = '<a href="admin.php?page='.$info[$key]['uri'].'">' . __('here', 'all-in-one-wp-security-and-firewall') . '</a>';
			echo '<div class="aio_orange_box"><p>';
			echo sprintf(__('The %s feature is now located %s.', 'all-in-one-wp-security-and-firewall'), $info[$key]['title'], $new_location_link) . ' ' . __('This page will be removed in a future release.', 'all-in-one-wp-security-and-firewall');
			echo '</p></div>';
		?>
	</div>
</div>
