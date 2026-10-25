<?php
/**
 * Plugin Name: ETBS Shortcodes Compat
 * Version: 1.0.0
 * Description: 公開が終了した「Shortcodes by Angie Makes」（wc-shortcodes 3.46）のうち、[wc_row]・[wc_column]・[wc_button] の3つを、元と同じ出力・同じ CSS で引き継ぎます。本文は書き換えません。元のプラグインが有効な間は何もしません。
 * Author: ETBS (DAI)
 * Author URI: https://etbs.jp
 * Plugin URI: https://etbs.jp/product-category/wordpress-tools/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: etbs-shortcodes-compat
 * @package etbs-shortcodes-compat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'ESCP_PLUGIN_FILE', __FILE__ );

// 版数はこの定数とヘッダの Version: の2か所。上げるときは両方そろえる。
define( 'ESCP_VERSION', '1.0.0' );

require_once __DIR__ . '/inc/func.php';

/*-------------------------------------------*/
/*  プラグインのアップデートチェック
/*-------------------------------------------*/
require __DIR__ . '/inc/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
$escp_update_checker = PucFactory::buildUpdateChecker(
	'https://github.com/etbsjp/etbs-shortcodes-compat/',
	__FILE__,
	'etbs-shortcodes-compat'
);
$escp_update_checker->setBranch( 'dist' );
