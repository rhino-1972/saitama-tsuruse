<?php
$_SERVER['HTTP_HOST'] = 'wordpress.local';
$_SERVER['REQUEST_URI'] = '/saitamatsuruse-douin/';
require '/Users/toshiyuki/Local Sites/shorinjikempo/app/public/wp-load.php';
function doin_gallery_assert($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
$original = get_current_blog_id();
$items = doin_get_gallery_whatsnew();
doin_gallery_assert(get_current_blog_id() === $original, 'Blog was not restored');
doin_gallery_assert(count($items) === 5, 'Expected five gallery items');
$dates = array_column($items, 'datetime');
$sorted = $dates;
rsort($sorted);
doin_gallery_assert($dates === $sorted, 'Dates are not descending');
foreach ($items as $item) {
    doin_gallery_assert(strpos($item['url'], 'http://wordpress.local/tsuruse-shiki/') === 0, 'Wrong local gallery URL');
}
$zone = wp_timezone();
$post = new WP_Post((object) array('ID' => 0, 'filter' => 'raw', 'post_date' => '2026-10-01 10:00:00', 'post_date_gmt' => '2026-10-01 01:00:00'));
foreach (array('2026-10-01 09:59:59' => false, '2026-10-01 10:00:00' => true, '2026-10-14 23:59:59' => true, '2026-10-15 00:00:00' => false) as $now => $expected) {
    doin_gallery_assert(doin_gallery_post_is_new($post, new DateTimeImmutable($now, $zone)) === $expected, 'New boundary failed: ' . $now);
}
$production = function () { return 'https://shorinjikempo.dream-times.net/tsuruse-shiki/'; };
add_filter('doin_gallery_base_url', $production);
try {
    foreach (doin_get_gallery_whatsnew() as $item) {
        doin_gallery_assert(strpos($item['url'], 'https://shorinjikempo.dream-times.net/tsuruse-shiki/') === 0, 'Wrong production URL');
    }
} finally { remove_filter('doin_gallery_base_url', $production); }
$html = do_shortcode('[doin_training_schedule]');
$note = strpos($html, '各行事後に順次');
doin_gallery_assert($note !== false && $note < strpos($html, '<h3>年間行事</h3>'), 'Request must precede annual title');
doin_gallery_assert(strpos($html, '>写真館</a>') !== false && strpos($html, '>ご提供</a>') !== false, 'Missing request links');
echo "PASS: five items, descending, blog restoration, Local/production links, New expiry, annual request links\n";
