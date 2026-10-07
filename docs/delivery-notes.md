# SKST 開発内容の保存

反映先: `/Users/toshiyuki/Library/CloudStorage/OneDrive-吉野敏之/_どりーむたいむす/Git-Hub/SKST-GiHub`

## 配置

- `wordpress/themes/doin`: 埼玉鶴瀬道院のテーマ、画像、表示処理
- `wordpress/themes/jsc`: 志木スポーツ少年団のテーマ
- `wordpress/themes/Saitama Tsuruse`: イベント写真館のテーマ
- `wordpress/mu-plugins`: アップロード上限、Event Organiserの互換処理、旧カレンダー追加日程の保存用コード
- `wordpress/tests`: イベントの日程・年間行事の検証
- `wordpress/scripts`: Local への同期などの開発スクリプト
- `docs/page-content/doin-site-snapshot.json`: お問い合わせ完了を含む固定ページの本文・親子関係と、日付形式・Lightning 設定の保存データ
- `database/doin-media`: Lightning スライドショーに設定した画像５件

`docs` 内の既存の設計書はそのまま保存する。WordPress 本体、認証情報、SKKP は含めない。

## 固定ページと設定

固定ページ本文と管理画面の設定はデータベースに保存されるため、テーマのコピーだけでは移行されない。`doin-site-snapshot.json` は復元用の参照データで、自動インポートは行わない。

移行先では固定ページを slug に対応させて本文を復元する。Lightning の画像 URL、メニュー ID、Contact Form 7 のフォーム ID は移行先に合わせて再設定する。スライドショー画像は `database/doin-media` からメディアへ登録する。

Local へのコード反映は `bash wordpress/scripts/sync-to-local.sh` で実行できる。

修練カレンダーと複数日程の操作手順は `docs/events-calendar.md` を参照する。

## 最新設計

2026年10月7日時点の確定仕様は `docs/04_SKST_実装反映設計書_Ver0.2.md` と同名のWord版にまとめた。Ver0.1の原設計書は保存し、追補に明記した変更はVer0.2を優先する。

お問い合わせ完了への移動、必要プラグイン導入案内、Localの502回避処理も含む。本番でのメール配送、画像取り込み、本番へのデータ移行は別途確認する。
