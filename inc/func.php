<?php
/**
 * ETBS Shortcodes Compat の本体処理。
 *
 * 元の「Shortcodes by Angie Makes」（wc-shortcodes 3.46）の [wc_row]・[wc_column]・[wc_button] を
 * 元と同じ出力で再現する。属性の既定値と検査は元の public/class-vars.php・public/class-sanitize.php に合わせてある。
 *
 * @package etbs-shortcodes-compat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*-------------------------------------------*/
/*  安全装置（元のプラグインが有効な間は何もしない）
/*-------------------------------------------*/

/**
 * 元の wc-shortcodes が読み込み済みかを返す。
 *
 * 元は本体ファイルの読み込み時に WPC_Shortcodes_Vars::init_vars() で WC_SHORTCODES_VERSION を
 * 無条件に define する。プラグインの読み込み順に依存しないよう、呼び出しは plugins_loaded 以降で行う。
 *
 * @return bool 読み込み済みなら true。
 */
function escp_is_original_loaded() {
	return defined( 'WC_SHORTCODES_VERSION' );
}

/**
 * フックを掛ける。元のプラグインが読み込み済みなら何も掛けない。
 *
 * @return void
 */
function escp_init() {
	if ( escp_is_original_loaded() ) {
		return;
	}

	// row と column は wpautop（優先度 10）より前に展開する。元と同じ優先度 7。
	add_filter( 'the_content', 'escp_pre_process', 7 );
	add_filter( 'acf_the_content', 'escp_pre_process', 7 );

	// button は通常の登録（do_shortcode の優先度 11 で展開される）。
	add_shortcode( 'wc_button', 'escp_shortcode_button' );

	add_filter( 'body_class', 'escp_body_class' );
	add_action( 'wp_enqueue_scripts', 'escp_enqueue_scripts' );
	add_action( 'wp_enqueue_scripts', 'escp_override_font_awesome', 9999 );
}
add_action( 'plugins_loaded', 'escp_init' );

/*-------------------------------------------*/
/*  row・column の先行展開
/*-------------------------------------------*/

/**
 * 行と列だけを先に展開する。
 *
 * 登録済みのショートコードを退避して空にし、row と column だけを登録して展開する。
 * 途中で例外が出ても、退避したものを必ず元に戻す。
 *
 * @param string $content 本文。
 * @return string 行と列を展開した本文。
 */
function escp_pre_process( $content ) {
	global $shortcode_tags;

	// 登録済みのショートコードを退避して空にする。
	$original_shortcode_tags = $shortcode_tags;
	$shortcode_tags          = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- 元のプラグインと同じ退避・復元。

	try {
		// 前処理の間に登録されているのは row と column だけにする。
		add_shortcode( 'wc_column', 'escp_shortcode_column' );
		add_shortcode( 'wc_row', 'escp_shortcode_row' );

		$content = do_shortcode( $content );
	} finally {
		// 退避したショートコードを完全に元へ戻す。
		$shortcode_tags = $original_shortcode_tags; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- 元のプラグインと同じ退避・復元。
	}

	return $content;
}

/*-------------------------------------------*/
/*  属性の検査（元の WPC_Shortcodes_Sanitize と同じ結果）
/*-------------------------------------------*/

/**
 * 値が許可リストにあればその値、なければ既定値を返す。
 *
 * @param mixed    $value   検査する値。
 * @param string[] $allowed 許可する値の一覧。
 * @param string   $fallback 許可リストに無いときの値。
 * @return string 検査後の値。
 */
function escp_whitelist( $value, $allowed, $fallback ) {
	if ( in_array( $value, $allowed, true ) ) {
		return $value;
	}

	return $fallback;
}

/**
 * スペース区切りの class 名を1つずつ無害化する。
 *
 * @param string $value class 名（スペース区切り）。
 * @return string 無害化した class 名。
 */
function escp_html_classes( $value ) {
	if ( empty( $value ) ) {
		return '';
	}

	$classes = explode( ' ', $value );
	foreach ( $classes as $key => $class ) {
		$classes[ $key ] = sanitize_html_class( $class );
	}

	return trim( implode( ' ', $classes ) );
}

/**
 * 文字揃えの値を検査する。
 *
 * @param string $value 文字揃え。
 * @return string left・center・right のいずれか。それ以外は空文字。
 */
function escp_text_align( $value ) {
	return escp_whitelist( $value, array( '', 'left', 'center', 'right' ), '' );
}

/**
 * Font Awesome のアイコン名を検査する。先頭の fa- は取り除く。
 *
 * @param string $value アイコン名。
 * @return string 無害化したアイコン名。
 */
function escp_fa_icon( $value ) {
	$value = preg_replace( '/^fa\-/', '', $value );

	return sanitize_html_class( $value );
}

/**
 * column の属性を検査する。
 *
 * @param array $atts shortcode_atts() を通した属性。
 * @return array 検査後の属性。
 */
function escp_column_attr( $atts ) {
	$atts['size']       = escp_whitelist( $atts['size'], array( 'one-half', 'one-third', 'two-third', 'one-fourth', 'three-fourth' ), 'one-third' );
	$atts['position']   = escp_whitelist( $atts['position'], array( 'first', '', 'last' ), '' );
	$atts['class']      = escp_html_classes( $atts['class'] );
	$atts['text_align'] = escp_text_align( $atts['text_align'] );

	return $atts;
}

/**
 * button の属性を検査する。
 *
 * @param array $atts shortcode_atts() を通した属性。
 * @return array 検査後の属性。
 */
function escp_button_attr( $atts ) {
	$atts['type']  = escp_whitelist( $atts['type'], array( 'primary', 'secondary', 'inverse', 'success', 'warning', 'danger', 'info' ), 'primary' );
	$atts['url']   = esc_url_raw( $atts['url'] );
	$atts['title'] = sanitize_text_field( $atts['title'] );

	// target は先頭の _ を許す（_blank と blank を同じに扱う）。許可リストに無ければ self。
	$atts['target'] = escp_whitelist( ltrim( trim( $atts['target'] ), '_' ), array( '', 'blank' ), 'self' );

	$atts['rel']        = escp_whitelist( $atts['rel'], array( '', 'nofollow' ), '' );
	$atts['icon_left']  = escp_fa_icon( $atts['icon_left'] );
	$atts['icon_right'] = escp_fa_icon( $atts['icon_right'] );
	$atts['position']   = escp_text_align( $atts['position'] );
	$atts['class']      = escp_html_classes( $atts['class'] );

	return $atts;
}

/*-------------------------------------------*/
/*  ショートコード本体
/*-------------------------------------------*/

/**
 * [wc_row] の出力。
 *
 * @param array|string $atts    属性（使わない）。
 * @param string|null  $content 囲まれた本文。
 * @return string 行の HTML。
 */
function escp_shortcode_row( $atts, $content = null ) {
	return '<div class="wc-shortcodes-row wc-shortcodes-item wc-shortcodes-clearfix">' . do_shortcode( (string) $content ) . '</div>';
}

/**
 * [wc_column] の出力。
 *
 * class 属性の末尾の空白1つは元の出力そのままで、byte 一致のために残している。
 *
 * @param array|string $atts    属性（size・position・class・text_align）。
 * @param string|null  $content 囲まれた本文。
 * @return string 列の HTML。
 */
function escp_shortcode_column( $atts, $content = null ) {
	$atts = shortcode_atts(
		array(
			'size'       => 'one-third',
			'position'   => '',
			'class'      => '',
			'text_align' => '',
		),
		$atts
	);
	$atts = escp_column_attr( $atts );

	// 文字揃えは left・center・right のときだけ style を付ける。
	$style = '';
	if ( '' !== $atts['text_align'] ) {
		$style = ' style="text-align: ' . esc_attr( $atts['text_align'] ) . ';"';
	}

	return '<div' . $style . ' class="wc-shortcodes-column wc-shortcodes-content wc-shortcodes-' . esc_attr( $atts['size'] ) . ' wc-shortcodes-column-' . esc_attr( $atts['position'] ) . ' ' . esc_attr( $atts['class'] ) . '">' . do_shortcode( (string) $content ) . '</div>';
}

/**
 * [wc_button] の出力。
 *
 * @param array|string $atts    属性（type・url・title・target・rel・icon_left・icon_right・position・class）。
 * @param string|null  $content 囲まれた本文（ボタンの文言）。
 * @return string ボタンの HTML。
 */
function escp_shortcode_button( $atts, $content = null ) {
	$atts = shortcode_atts(
		array(
			'type'       => 'primary',
			'url'        => '',
			'title'      => 'Visit Site',
			'target'     => 'self',
			'rel'        => '',
			'icon_left'  => '',
			'icon_right' => '',
			'position'   => 'float',
			'class'      => '',
		),
		$atts
	);
	$atts = escp_button_attr( $atts );

	$url_rel    = ! empty( $atts['rel'] ) ? ' rel="' . esc_attr( $atts['rel'] ) . '"' : '';
	$url_target = ! empty( $atts['target'] ) ? ' target="_' . esc_attr( $atts['target'] ) . '"' : '';

	// a 要素の class を組み立てる。
	$classes   = array();
	$classes[] = 'wc-shortcodes-button';
	$classes[] = 'wc-shortcodes-button-' . $atts['type'];
	$classes[] = 'wc-shortcodes-button-position-' . $atts['position'];
	if ( ! empty( $atts['class'] ) ) {
		$classes[] = $atts['class'];
	}

	// 左右のアイコンと文言を a 要素に入れる。
	$button  = '<a href="' . esc_url( $atts['url'] ) . '" class="' . esc_attr( implode( ' ', $classes ) ) . '"' . $url_rel . $url_target . ' title="' . esc_attr( $atts['title'] ) . '">';
	$button .= '<span class="wc-shortcodes-button-inner">';
	if ( $atts['icon_left'] ) {
		$button .= '<span class="wc-shortcodes-button-icon-left fa fa-' . esc_attr( $atts['icon_left'] ) . '"></span>';
	}
	$button .= $content;
	if ( $atts['icon_right'] ) {
		$button .= '<span class="wc-shortcodes-button-icon-right fa fa-' . esc_attr( $atts['icon_right'] ) . '"></span>';
	}
	$button .= '</span>';
	$button .= '</a>';

	// 位置の指定があるときだけ div で包む。
	if ( ! empty( $atts['position'] ) ) {
		$button = '<div class="wc-shortcodes-item wc-shortcodes-button-' . $atts['position'] . '">' . $button . '</div>';
	}

	return $button;
}

/*-------------------------------------------*/
/*  CSS・Font Awesome・body class
/*  読み込みの切り替えは元のプラグインのオプションを読む（既定 true）。設定画面は持たない。
/*-------------------------------------------*/

/**
 * CSS と jQuery を読み込む。ハンドル名は元と同じ。
 *
 * @return void
 */
function escp_enqueue_scripts() {
	if ( get_option( 'wc_shortcodes_enable_shortcode_css', true ) ) {
		wp_enqueue_style( 'wc-shortcodes-style', plugins_url( 'assets/css/style.css', ESCP_PLUGIN_FILE ), array(), ESCP_VERSION );
	}

	wp_enqueue_script( 'jquery' );
}

/**
 * Font Awesome を読み込む。
 *
 * 元と同じく、同名のハンドルをいったん解除して登録し直す（優先度 9999）。
 * テーマや他プラグインが同じハンドル名で登録していても、この版で上書きされる。
 *
 * @return void
 */
function escp_override_font_awesome() {
	if ( get_option( 'wc_shortcodes_enable_font_awesome', true ) ) {
		wp_deregister_style( 'wordpresscanvas-font-awesome' );
		wp_register_style( 'wordpresscanvas-font-awesome', plugins_url( 'assets/css/font-awesome.min.css', ESCP_PLUGIN_FILE ), array(), '4.7.0' );
		wp_enqueue_style( 'wordpresscanvas-font-awesome' );
	}
}

/**
 * Font Awesome が有効なとき、body に元と同じ class を足す。
 *
 * @param string[] $classes body の class 一覧。
 * @return string[] class 一覧。
 */
function escp_body_class( $classes ) {
	if ( get_option( 'wc_shortcodes_enable_font_awesome', true ) ) {
		$classes[] = 'wc-shortcodes-font-awesome-enabled';
	}

	return $classes;
}

/*-------------------------------------------*/
/*  サポート導線（プラグイン一覧の行）
/*  ダッシュボードとフッターは置かない（画面を開くのが導入先の施設の方のため）。
/*-------------------------------------------*/

/**
 * プラグイン一覧の自分の行に、支援と依頼のリンクを足す。
 *
 * @param string[] $links プラグインの行に並ぶリンク。
 * @param string   $file  行のプラグインファイル（plugin_basename）。
 * @return string[] リンク一覧。
 */
function escp_plugin_row_meta( $links, $file ) {
	if ( plugin_basename( ESCP_PLUGIN_FILE ) !== $file ) {
		return $links;
	}

	$links[] = '<a href="https://etbs.jp/product/donate/?utm_source=etbs-shortcodes-compat&utm_medium=plugin" target="_blank" rel="noopener noreferrer">'
		. esc_html__( '開発を支援', 'etbs-shortcodes-compat' ) . '</a>';
	$links[] = '<a href="https://etbs.jp/product-category/wordpress-tools/?utm_source=etbs-shortcodes-compat&utm_medium=plugin" target="_blank" rel="noopener noreferrer">'
		. esc_html__( '開発のご依頼', 'etbs-shortcodes-compat' ) . '</a>';

	return $links;
}
add_filter( 'plugin_row_meta', 'escp_plugin_row_meta', 10, 2 );
