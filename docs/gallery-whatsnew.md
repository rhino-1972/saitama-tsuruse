# イベント写真館のWhat’sNew表示

埼玉鶴瀬道院の「イベント写真館」固定ページへ `[doin_gallery_whatsnew]` を追加。説明本文は維持し、写真館へのリンクは `[doin_gallery_link]` で出力する。

同一WordPressマルチサイト内の `/tsuruse-shiki/` サイトから公開済み通常投稿を取得する。写真館のWhat’sNewと同じく固定表示投稿を含む5件を選び、こちらでは投稿日降順に並べる。写真館側の表示順やNew設定は変更しない。写真・動画本文、非公開投稿・下書きは取り込まない。閲覧時は写真館の権限制御に従う。

タイトル直後の `New` は、公開日時以降から投稿日14日後の午前0時まで。日付計算は写真館のWordPressタイムゾーンを使う。毎回判定するため、期限後の手動削除や定期処理は不要。日付の書式も写真館の表示設定に従う。

## リンク先

- Local: `http://wordpress.local/tsuruse-shiki/`
- 本番: `https://shorinjikempo.dream-times.net/tsuruse-shiki/`

鶴瀬道院のサイトURLが `.local` または `localhost` の場合はLocal、それ以外は本番URLを使う。各記事のパス・クエリを保ち、取得元の古いドメインは引き継がない。必要な場合は `doin_gallery_base_url` フィルタで変更できる。

修練日程の「年間行事」見出し直前に写真・動画の提供依頼2行を追加。「写真館」「ご提供」のリンクはユーザー指定のURLをそのまま使用し、Localでも自動変更しない。

## 保存と検証

- `wordpress/themes/doin/functions.php`: 記事取得・リンク・New・提供依頼文。
- `wordpress/themes/doin/style.css`: 新着マーク・提供依頼文の表示。
- `wordpress/tests/gallery-whatsnew-integration.php`: 5件・降順・サイト復元・URL切り替え・Newの境界・提供依頼リンクの検証。
- `docs/page-content/doin-event-gallery.html`: 更新済み固定ページの本文。
- `docs/page-content/event-gallery-before-whatsnew.html`: 更新前の本文。
- `docs/page-content/doin-site-snapshot.json`: 更新済み固定ページ参照データ。

移行先は同じネットワーク内に写真館サイトが必要。固定ページ本文はデータベースへ保存されるため、テーマのコピーだけではショートコード追加は反映されない。
