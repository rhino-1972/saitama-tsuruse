# LocalのNextGENギャラリーで発生する502

## 確認した原因

2026-10-07 22:04の修練内容ページのアクセス時、Nginxは `upstream prematurely closed connection`、PHP-FPMは `SIGSEGV` を記録していました。macOSのクラッシュ記録には `zif_dgettext` → `libintl_dcigettext` → `_libintl_locale_name_default` → CoreFoundationの設定読み込みが記録されています。

NextGEN Gallery同梱のPEL画像情報ライブラリは、文字列を翻訳する際にPHPのネイティブ `dgettext()` を呼びます。通常のHTMLやショートコードの構文エラーではなく、Localのネイティブ翻訳処理によるPHPプロセスの異常終了です。

一時的な診断ページで、`dgettext()` だけを実行するとHTTP 502、先に `putenv('LC_ALL=C')` を実行するとHTTP 200となることを確認しました。診断ページは確認後に削除しています。

## 回避策

`wordpress/mu-plugins/skst-local-gettext-compat.php` が、macOSのPHP-FPM、Localホスト（`.local` / localhost / 127.0.0.1）、LC_ALL未設定の場合のみ、ネイティブgettextのロケールをCに設定します。

WordPress自身の日本語翻訳設定、固定ページ、ギャラリー設定、写真は変更しません。明示的なLC_ALL設定や本番環境は変更しません。プラグイン本体の編集やPHPバージョン変更は不要です。

この回避策は画像取り込みの経路にも適用されますが、画像取り込み自体の再実行は行っていません。
