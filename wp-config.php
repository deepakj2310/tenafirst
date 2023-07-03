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
define( 'DB_NAME', 'tenafirst_db' );

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
define( 'AUTH_KEY',         ',!{MYZ7)1*.+5KU)l<vdFqf:|jm78$qHIL/,iLx@;zQk1T-Pn397bo`zw*8A8Jl{' );
define( 'SECURE_AUTH_KEY',  'G6[:XA+ROmu}0ET~+$Mz5onw:JnUQrh|pCW}q:~^>}4C%$JoqxHzDv&fCE<t,L#:' );
define( 'LOGGED_IN_KEY',    'uB~I4fA,rz5a`~Xc-VtJ+sTU{YU/5z^!35p8-B#g2*T_2d9N%{ge$(g,x%YAU]^B' );
define( 'NONCE_KEY',        'Z&y<*ZikJ}&,<%aJNeEXzcLm{8UV*h}nA]AB2oAG7^(V8,3R4_b9Kxq(MnwNENvh' );
define( 'AUTH_SALT',        '},BHz>S6N&t5eDQ}0)tM$L<-E;:D.TOal~x;?tl0(A:asah2X6Z4Moe~B_*1P*Rd' );
define( 'SECURE_AUTH_SALT', 'Pd+O~)+UrX2L8YZ~etw$#L1Zazai=|CCtjeGI=ch%(VZH+iN!1#XLM92#~&Go(5C' );
define( 'LOGGED_IN_SALT',   'lfQxHr8?45f-#wtQRg5Ph0@^gD.-G>NW5pa7Ed};8k%-#8Qp-w~XhbovLQ};.])^' );
define( 'NONCE_SALT',       'RM 6J+qb>E.ekDG w<i1+e^YGD#$iO7FK)<<jVpakdW9t*^/D}b_s*,d]n.wzKf ' );

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
