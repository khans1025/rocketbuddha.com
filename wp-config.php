<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

//define('WP_CACHE', true); //Added by WP-Cache Manager
define( 'WPCACHEHOME', '/home/robcos22/rocketbuddha.com/wp-content/plugins/wp-super-cache/' ); //Added by WP-Cache Manager
define('WP_MEMORY_LIMIT', '256M');

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'rocketbuddha_com');

/** MySQL database username */
define('DB_USER', 'rocketbuddhacom');

/** MySQL database password */
define('DB_PASSWORD', '!!5C4Fvf');

/** MySQL hostname */
define('DB_HOST', 'mysql.rocketbuddha.com');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         's`n!DfnWNZ_cAjbSvmAS5TOaM((H@"~e0gGh9yalQnyf#^dFDbGp6Uph@n&`NtPK');
define('SECURE_AUTH_KEY',  'j:1RX"&`tmRy8Bc_&YgzIx@usokk6c3~yobRs`W@NBM7X*Ll:Uo2:A6kzhoE"W|2');
define('LOGGED_IN_KEY',    'w^CJ#W#sCJ!VimF0^Ldm)j::(b*`lYMU09LTb:lGO@RQy6fvln:%l:?TO@UJ+xx5');
define('NONCE_KEY',        'sEJc_I#~rFXw^hw6&)vdV:^y?o$l8pT7@q;u&QBBwE#:Z;Vi+k_uH4Y!(Mh/KNu5');
define('AUTH_SALT',        'n#j~%0VJ/&*ZHsizyT_*5E84g_|X$b4GC#jzp1?ftkrVakXQzD`r31h1md+I/J!K');
define('SECURE_AUTH_SALT', '(WV;@$i5+xRl5ejBt*JXBOGD@6VVR19O~OI^~c4vz;Si@s4*bM40muvYpU7qsQEm');
define('LOGGED_IN_SALT',   '0N$@#5yQBPBRnRPBZxSwr"_5j%;qPE;cc~8#x|CUkZ;)Ihdvkn~CQ*/WJMNBk3qY');
define('NONCE_SALT',       'j9OisBXM|_4YyKM0cQ:PNe@F~he6__vLBvXn@agdQM&NaX0a88Pa9f@YOfi6/HO?');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_n7ec67_';

/**
 * Limits total Post Revisions saved per Post/Page.
 * Change or comment this line out if you would like to increase or remove the limit.
 */
define('WP_POST_REVISIONS',  10);

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');