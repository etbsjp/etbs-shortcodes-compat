# etbs-shortcodes-compat

etbs が配布する WordPress プラグイン。共通ルールの正本は `~/.claude/etbs-plugin-rules.md`。
**仕様の正本は、このリポジトリの issue #1。**

★ **公開リポジトリ。** コミット・PR・issue のコメント・テストの記録に、導入先の施設名・URL・ドメインを書かないこと。
検証サイトのタイトルや BASIC 認証の realm にも出さない（過去に curl のログから第三者の社名が PR に載った）。

## レビュー工程に大（シニアエンジニア）を追加する

このリポジトリでは、安藤（`vk-code-reviewer`）のレビューのあと、**PR を作成する前に**
大（`etbs-senior-wp`）の監査を必ず通すこと。大は etbs の申し送りと過去に踏んだ罠に照らして
「リリースできる形になっているか」を見る担当で、安藤の一般的なコード品質レビューとは層が違う。

- `Agent` ツールで `subagent_type: etbs-senior-wp`、`name: etbs-senior-wp`、
  **`run_in_background: false`** で起動する
- **`isolation: "worktree"` は使えるなら付ける**（付けないと起動応答は「成功」と返るのに
  一度も作業せず待機状態に入ることがある）。ただし ★★ **作業ディレクトリが git リポジトリでないと使えない。**
  その場合は **isolation なしで起動してよい**（2026-08-19 実績あり）。
  **見分け方は起動応答の形**——`output_file` 付きの正常形なら動いている
- prompt には対象リポジトリ・ブランチ・差分（または PR 番号）を渡す
- 大には **出力の末尾に `監査結果: PASS` または `監査結果: FAIL` を必ず書くよう指示する**
  （★ 大の定義ファイルには出力形式の指定が無いため、指示しないと合否を機械判定できない）
- `監査結果: PASS` を受け取るまで PR を作成しない。`FAIL` なら和田へ差し戻して再監査する
- 監査の出力本文は **PR のコメントとして残す**（「PASS」と書くだけでは記録にならない）

★ 大は vk-agents のメンバー表に登録されていないため、指示が無いと**永久に呼ばれない**。

## 検証環境

Local の `ai-wp-demo`（このプラグイン専用のサイトは無い）。`wp-content/plugins/` にシンボリックリンクで張る
（このプラグインは `dirname( __FILE__, N )` を使っていない）。worktree へ向けた場合は、終わったら本体クローンへ戻す。
比較用に元の 3.46 を入れるときは別ディレクトリからの一時設置にし、終わったら外す。

- 元の 3.46 は `svn export https://plugins.svn.wordpress.org/wc-shortcodes/tags/3.46/` で取る。**リポジトリには入れない**。
- 出力の比較は `apply_filters( 'the_content', … )` と `apply_filters( 'acf_the_content', … )` の結果を `diff -r` する。
  有効なプラグインの切り替えは DB を書き換えず、`pre_option_active_plugins` を wp-load の前に差し込むと確実。
- CLI では Local の php.ini を `-c` で渡すこと（`~/.claude/etbs-plugin-rules.md` の「ブラウザを使わない検証手法」）。

## 元のプラグインとの関係（このプラグインの存在理由）

- 出力 HTML は元の 3.46 と **byte 一致**が合格条件。特に `column` の `class` 属性の末尾の空白1つは意図して残している。
  元の `column()` が計算している `$append_clearfix` は元でも使われていないので、出力に足さない。
- `[wc_row]`・`[wc_column]` は `the_content`・`acf_the_content` の優先度 7 で先に展開する（wpautop より前。`<p>` を挟まないため）。
  `$shortcode_tags` の退避・復元は `try` / `finally`。元は例外が出ると戻らないが、こちらは戻す。
- 元が読み込み済み（`WC_SHORTCODES_VERSION` が定義済み）なら、ショートコード・CSS・body class のフックを1つも掛けない。
  プラグイン一覧の行リンク（`plugin_row_meta`）と PUC は、元の有無にかかわらず掛かる。
- CSS は元の `style.css` から3つのショートコードに当たる規則を**元の順序のまま**抜き出したもの
  （セレクタに他のショートコードの class が並ぶ規則は、規則ごと残す）。ハンドル名は元と同じ（`wc-shortcodes-style`・`wordpresscanvas-font-awesome`）。
- CSS の `ver` は `ESCP_VERSION`（元は `3.46`）。Font Awesome の `ver` は元と同じ `4.7.0`。

## アンインストール

`uninstall.php` の方針は**案A**（3分類）。このプラグインは DB に何も書かないので、**消すものが無い**（docblock に理由を書いてある）。
`wc_shortcodes_enable_shortcode_css`・`wc_shortcodes_enable_font_awesome` は元のプラグインのオプションで、読むだけ。

## 版数

版数は**ヘッダの `Version:` と定数 `ESCP_VERSION` の2か所**（`etbs-shortcodes-compat.php`）。上げるときは両方そろえる。
`readme.txt` は無く `README.md` のみ。変更履歴は README.md の「変更履歴」節に書く。

## CI

**共通ルールは `~/.claude/etbs-plugin-rules.md` の 2.7 節**。そちらの内容はここに転記しない。ここに置くのはこのリポジトリでしか決まらない値だけ。

- `.github/workflows/ci.yml`・`composer.json`・`composer.lock`・`.gitattributes` は `widget-shortcode-tools` の `dist`（原本）と byte 一致。
  `.phpcs.xml.dist` は原本と `ruleset name` / `description` だけが違う。
- **既存指摘の基準値: 0 ERROR / 0 WARNING**（`WordPress-Extra`・初版時点）。
  測り直すときは `vendor/bin/phpcs --standard=./.phpcs.xml.dist --report=summary $(git ls-files '*.php')` の形でのみ行う。
- **`Requires PHP` / `Requires at least` は無宣言。** 実在する下限が無いため（同梱 PUC の下限は WP 4.8）。
  CI の matrix は `['7.4','8.3']` なので、**CI が守るのは 7.4 まで。7.3 は守られていない**。
  アロー関数・型付きプロパティ・`??=`・`match`・`?->` を書くと 7.3 は白画面になる。
- `composer.json` の `name` は原本のまま（`etbsjp/widget-shortcode-tools`）。`composer.lock` とペアなので揃え直さない。
- 単体テスト（PHPUnit）は置いていない。比較の正解が元の 3.46 の出力そのものなので、上の「検証環境」の `diff -r` を回帰確認とする。
