# 道院のご案内：修練場所

固定ページ「道院のご案内」の「修練場所」に、本文、体育館外観写真、Googleマップを配置しています。本文と画像・地図のURLは固定ページのHTMLから編集できます。

- PCでは本文の左側に写真、両方の下にマップを表示します。
- モバイルでは本文、写真、マップの順に表示します。
- 項目は赤丸の矢印、項目名、説明文の3列で表示します。体育館名やバス情報の改行は説明文の列にそろいます。
- HTMLの保存先：`wordpress/themes/doin/page-content/about-location.html`
- 現在の固定ページ本文：`docs/page-content/doin-about-greeting.html`
- 表示用CSS：`wordpress/themes/doin/style.css` の `.doin-location` 関連。

写真は富士見市公式サイトの市民総合体育館ページで公開されている外観写真を外部参照しています。画像URLの変更・公開停止時には、固定ページの写真を差し替えてください。

写真出典：https://www.city.fujimi.saitama.jp/madoguchi_shisetsu/03sports/sports/2010-0506-1606-145/2010-0524-1506-142/index.html

Googleマップは既存道院サイトで利用している富士見市立市民総合体育館の埋め込みURLを使用しています。外部サービスへの通信が発生します。
