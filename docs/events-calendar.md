# 修練カレンダーと複数日程

## 使用プラグイン

Event Organiser 3.12.10 を埼玉鶴瀬道院サイトだけで有効化。The Events Calendar は同サイトで停止し、元のイベントデータは削除していない。既存5イベントと5カテゴリを移行済み。同名の既存イベントも勝手に統合せず維持する。

## 月表示と年間行事

固定ページの `[doin_training_schedule]` はそのまま使用する。

- 修練日程メモ: 通常投稿の「修練日程のお知らせ」カテゴリを投稿日降順で10件。
- 年間行事: Event Organiser の「年間行事」カテゴリを開催日時降順で10日程。過去の行事も表示し、日付形式はWordPressの表示設定に従う。
- 修練カレンダー: Event Organiser 標準の月カレンダー。公開イベントを表示し、前月・翌月・今日の操作に対応。モバイルでも月表示を保つ。

## 管理画面での日程設定

1. 「イベント」からイベントを作成・編集する。
2. 「イベント詳細」で開始日時・終了日時を設定する。
3. 毎週などの定期開催は繰り返し規則と終了日を指定する。
4. 任意の複数日程は繰り返しを「カスタム」にし、「日付を表示」からカレンダーの日付をクリックして追加・除外する。
5. 修練には「修練」、年間行事には「年間行事」のカテゴリを選び、公開・更新する。

同じイベントの日程は開始時刻・所要時間・会場を共有する。曜日により時刻や会場が違う修練は別イベントとして登録する。イベント詳細ページには開催日程を表示する。

プラグインの共通設定は「設定 ＞ Event Organiser」。既存のLightningレイアウトを保つため、プラグイン独自のテンプレートは無効にしている。期限切れイベントの自動削除も無効。

## 配置と移行

- `wordpress/themes/doin/functions.php`: 月表示・年間行事・詳細の日程表示。
- `wordpress/mu-plugins/skst-event-organiser-compat.php`: WordPressの新しい翻訳読み込み仕様への互換処理。プラグイン本体は編集しない。
- `wordpress/tests/event-organiser-integration.php`: 年間行事のカテゴリ・降順・10件制限、複数日程の追加・削除の検証。一時イベントは終了時に削除。
- `docs/page-content/tec-before-event-organiser.json`: 旧イベントの移行前保存データ。
- `docs/page-content/event-organiser-migration.json`: 移行元・移行先IDと設定。
- `docs/page-content/event-organiser-snapshot.json`: 移行後のイベント・日程・カテゴリ・設定の参照データ。

旧 `skst-event-dates.php` は後戻り用に残すが、Event Organiser有効時は動作しない。新しい日程は標準機能で登録し、旧追加日程欄は使わない。

一般配布プラグインの本体はリポジトリに含めない。移行先では公式配布のEvent Organiserを `wp-content/plugins` にインストール・有効化し、テーマとmu-pluginsを配置する。JSONは復元の参照データで、自動インポートは行わない。

## 公式資料

- https://wordpress.org/plugins/event-organiser/
- https://docs.wp-event-organiser.com/shortcodes/calendar/
