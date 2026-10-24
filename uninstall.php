<?php
/**
 * アンインストール処理。
 *
 * ETBS Shortcodes Compat は、DB に何も書かない。オプションも投稿メタも独自テーブルも cron も作らないため、
 * 案A（task-queue #108）の3分類で見ても、消すものが存在しない。
 *
 * - 利用者が作ったコンテンツ（投稿・投稿メタ）: 持たない。本文のショートコードは利用者のもので、触らない。
 * - 利用者が設定した値（オプション）: 持たない。CSS と Font Awesome の読み込みを切り替える
 *   `wc_shortcodes_enable_shortcode_css` と `wc_shortcodes_enable_font_awesome` は元のプラグイン
 *   （Shortcodes by Angie Makes）のオプションで、読むだけで書かない。消すと元のプラグインを戻したときに設定が失われる。
 * - 一時状態・自分が仕掛けた cron: 持たない。
 *
 * したがって、案Aに従うと「何もしない」が正しい実装になる。
 *
 * @package etbs-shortcodes-compat
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

// 意図的に何もしない。
