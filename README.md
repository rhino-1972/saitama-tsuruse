# saitama-tsuruse

SKST（埼玉鶴瀬道院・志木スポーツ少年団・イベント写真館）の独自WordPressコードと設計書を管理します。SKKP（少林寺拳法大会）は別リポジトリです。

## 構成

- `wordpress/themes/doin`: 埼玉鶴瀬道院のLightning子テーマ
- `wordpress/themes/jsc`: 志木スポーツ少年団の子テーマ
- `wordpress/themes/TuruseShiki_Etents`: イベント写真館の子テーマ
- `wordpress/mu-plugins`: アップロード上限・イベント互換処理・Local環境の互換処理
- `wordpress/tests`: 独自処理の検証スクリプト
- `docs`: 原設計書、実装反映設計書、運用・移行手順
- `database/doin-media`: 移行用スライドショー画像

`wordpress` の内容は実際のWordPressの `wp-content` に対応します。配布フォルダ内に `wordpress/wp-content` は作りません。WordPress本体、一般配布プラグイン、認証情報は含みません。

## 設計と反映

最新仕様は [実装反映設計書 Ver0.2](docs/04_SKST_実装反映設計書_Ver0.2.md) と同名Word版を参照してください。原設計書は保存しています。移行手順は [配布手順](docs/delivery-notes.md)、必要プラグインは [導入案内](docs/required-plugins.md) に記載しています。

固定ページ・フォーム・イベント・メニュー・Lightning設定はデータベースに保存されます。テーマファイルだけでは移行できません。`docs/page-content` のJSONは参照用であり、自動インポートは行いません。

Localでの埼玉鶴瀬道院の確認URLは `http://wordpress.local/saitamatsuruse-douin/` です。コードの反映は次のコマンドで行います。

```sh
bash wordpress/scripts/sync-to-local.sh
```

本番メール配送、データ移行、画像取り込みの再確認および他サイトの全機能検証は別途必要です。
