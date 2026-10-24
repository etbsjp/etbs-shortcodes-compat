# ETBS Shortcodes Compat

公開の配布が終了した「Shortcodes by Angie Makes」（wc-shortcodes 3.46）のうち、
**行・列・ボタン**の3つのショートコード（`[wc_row]`・`[wc_column]`・`[wc_button]`）だけを、
同じ HTML・同じ CSS で引き継ぐ WordPress プラグインです。本文を書き換えずに、元のプラグインを削除できるようにします。

- 元のプラグインが有効な間は何もしません（入れ替えの途中で表示が途切れないように）
- 3つ以外のショートコードは引き継ぎません

## 引き継ぐもの

| 引き継ぐ | 引き継がない |
|---|---|
| `[wc_row]`・`[wc_column]`・`[wc_button]`（出力は元と同じ HTML） | ほかのショートコードすべて |
| 上の3つに当たる CSS | ほかのショートコード用の CSS |
| Font Awesome 4.7.0 の CSS と fonts | 管理画面（設定画面・エディタのボタン・ウィジェット） |
| `body` の class `wc-shortcodes-font-awesome-enabled` | 投稿タイプ・AJAX・共有ボタン |

CSS と Font Awesome の読み込みは、元のプラグインと同じオプション（`wc_shortcodes_enable_shortcode_css`・`wc_shortcodes_enable_font_awesome`）に従います。
このプラグインに設定画面はありません。

## 入れ替えの手順

1. このプラグインを**有効化**します（この時点では何も変わりません）。
2. 元の「Shortcodes by Angie Makes」を**停止**します。次のリクエストから、このプラグインが働きます。
3. 表示を確かめてから、元のプラグインを**削除**します。

## アンインストール

プラグインを削除しても、データは何も消しません。このプラグインは設定も投稿データも作らず、
上の2つのオプションは元のプラグインのものだからです。

## 変更履歴

### 1.0.0

- 初版。`[wc_row]`・`[wc_column]`・`[wc_button]` を、wc-shortcodes 3.46 と同じ出力で引き継ぎ。

## サポート

有償サポートやカスタマイズは[こちらのページ](https://etbs.jp/product-category/wordpress-tools/?utm_source=etbs-shortcodes-compat&utm_medium=plugin)からお問い合わせください。
開発の継続は[ご支援](https://etbs.jp/product/donate/?utm_source=etbs-shortcodes-compat&utm_medium=plugin)で応援いただけます。
