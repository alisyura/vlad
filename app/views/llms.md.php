<?php
    $url = htmlspecialchars($data['base_url'] ?? '');
    $posts = $data['post'] ?? [];
    $pages = $data['page']['pages'] ?? [];
?>
# <?= htmlspecialchars($data['title'] ?? '') ?>


<?php if (!empty($data['last-modified'])): ?>
> Last-Modified: <?= htmlspecialchars($data['last-modified']) ?>
<?= "\n" ?>
>
<?php endif; ?>
> URL: <?= $url ?>
<?= "\n" ?>
> <?= htmlspecialchars($data['description'] ?? '') ?>


## Рубрики сайта

- [Главная](<?= $url ?>) — Главная страница Смехбука
<?php foreach ($posts as $cat_url => $cat_val): ?>
<?php
$cat_descr = ''; 
if (!empty($cat_val['description'])) {
    $cat_descr = htmlspecialchars($cat_val['description'] ?? '');
}
else {
    $cat_descr = htmlspecialchars($cat_val['name'] ?? '');
}
?>
- [<?= htmlspecialchars($cat_val['name'] ?? '') ?>](<?= $url ?>/cat/<?= htmlspecialchars($cat_val['url'] ?? '') ?>) — <?= $cat_descr ?>
<?= "\n" ?>
<?php endforeach; ?>

<?php foreach ($posts as $cat_url => $cat_val): ?>
## <?= htmlspecialchars($cat_val['name'] ?? '') ?>
<?= "\n" ?>
<?php foreach ($cat_val['posts'] as $post_id => $post_val): ?>
<?php
$post_descr = ''; 
if (!empty($post_val['description'])) {
    $post_descr = $post_val['description'] ?? '';
}
else {
    $post_descr = htmlspecialchars($post_val['title'] ?? '');
}
?>
- [<?= htmlspecialchars($post_val['title'] ?? '') ?>](<?= $url ?>/<?= htmlspecialchars($post_val['url'] ?? '') ?>.html) — <?= $post_descr ?>

<?php endforeach; ?>
<?= "\n" ?>
<?php endforeach; ?>

## Страницы сайта

<?php foreach ($pages as $page_id => $page_val): ?>
<?php
$page_descr = ''; 
if (!empty($page_val['description'])) {
    $page_descr = $page_val['description'] ?? '';
}
else {
    $page_descr = htmlspecialchars($page_val['title'] ?? '');
}
?>
- [<?= htmlspecialchars($page_val['title'] ?? '') ?>](<?= $url ?>/page/<?= htmlspecialchars($page_val['url'] ?? '') ?>.html) — <?= $page_descr ?>
<?= "\n" ?>
<?php endforeach; ?>