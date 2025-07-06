<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($posts as $post): ?>
    <url>
        <loc><?= htmlspecialchars($url) ?>/<?= htmlspecialchars($post['url']) ?>.html</loc>
        <lastmod><?= htmlspecialchars($post['updated_at']) ?></lastmod>
        <changefreq><?= htmlspecialchars($changefreq_posts) ?></changefreq>
        <priority><?= htmlspecialchars($posts_priority) ?></priority>
    </url>
<?php endforeach ?>
<?php foreach ($pages as $page): ?>
    <url>
        <loc><?= htmlspecialchars($url) ?>/page/<?= htmlspecialchars($page['url']) ?>.html</loc>
        <lastmod><?= htmlspecialchars($page['updated_at']) ?></lastmod>
        <changefreq><?= htmlspecialchars($changefreq_pages) ?></changefreq>
        <priority><?= htmlspecialchars($pages_priority) ?></priority>
    </url>
<?php endforeach ?>
</urlset>