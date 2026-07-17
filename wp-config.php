<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

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
define( 'AUTH_KEY',         'y/m.EOf+;b5(1,E^gv63!U<RsOX-Ob3+0HE4`]D5(&+kj fwXG,5qEq,McFX;w6&' );
define( 'SECURE_AUTH_KEY',  'cI969ez+rgILi&y=]F;XG8{31}$k2^2-Sx|Ki?nm^#iRR^N!DKw!WzMEUC(!7:Sh' );
define( 'LOGGED_IN_KEY',    'AQ6<0wM,^W6iX>`3)hDV14^MHUof;bU;t#!4Z!*82O9p&w{]yUQgSnX^n1Y}VyS`' );
define( 'NONCE_KEY',        'wq[:T4ZM(2<NCN;c}+-NH]6`=fZqCxa;X9gz<;lXe&Xdgy1UbHUz29Na)1oad(jH' );
define( 'AUTH_SALT',        'KgB-aQ50l}`{uHe(UP~a}xn,b.]j=V!u$lT%+j.^lolQ{)W~_BR9>,h`UC;U%[=w' );
define( 'SECURE_AUTH_SALT', '(Nh}g.3q6?Mv/UeIe_o+#lOg1%)RG:-0o1WZ62L@LS}^H&e6vs4fx?2WEM/?RsPg' );
define( 'LOGGED_IN_SALT',   'tp>3RLq1aKCwO[NAWp=FHf.)7E~|V9H`XHicEG*TA2)[IBo4UZkV18L_%0>_@xT#' );
define( 'NONCE_SALT',       'H&yzV:kv~`.`uf$&3dInfk9`|W%E`z6~:as_qT_*IjqKBR$:O1,zFpI!df&1FMmV' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
// Enable WP_DEBUG mode
define( 'WP_DEBUG', true );

// Enable Debug logging to the /wp-content/debug.log file
define( 'WP_DEBUG_LOG', true );

// Disable display of errors and warnings on the screen
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', 0 );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
