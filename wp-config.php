<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'hamid' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         ']2WWig,2ob=m0m;VI}4y(~vrl[d-T6dC>y6pEt_wK!?$<H*%api:h*h{di<OISL&' );
define( 'SECURE_AUTH_KEY',  '[ID9D#=4qq=sXO,)[h%T(O.;hF3wdn=BZHAWT<:b`NlWW)_ZilLss9Be%+II9CA1' );
define( 'LOGGED_IN_KEY',    '7M1d}&8;XY3>Ny!9Fv-[&+|5478Zneak3mj![wkSiOPNf,7h4rB_@GAKKf>=.|,S' );
define( 'NONCE_KEY',        'h~g2A*8Q4 CJt%l8h[<Eo%JM)Vc#J5BJVnu&JwW2VM T9/ciJ,`@4Ll0FnX n_Sx' );
define( 'AUTH_SALT',        '&`%/4E8=`)MX0DM9b=Y]+5MVs?E.|O17 +:|)5_T>$7~X$i6 <Ft19~ERz#rNw$0' );
define( 'SECURE_AUTH_SALT', '>Y|1Qv[s(T{>XiK<Khey 8.BNd#Z#O7kAP$WBzRj>-:mx^cBh3uy!jI@(j[$08a*' );
define( 'LOGGED_IN_SALT',   '[W;-n1 5>B(2db|YyUf/(;f+HY0+%9/K)n?:{*ns/d::d`BL7jo9ny(FpDad>3WD' );
define( 'NONCE_SALT',       'qaVE}X^lO!>f#MM}4]/Lwn4r5x+C_{YHf6Dr6~/-ZLbC53!~?xJT10m}a=5^Xaw/' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
