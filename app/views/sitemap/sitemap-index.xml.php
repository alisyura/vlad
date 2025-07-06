<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php for ($i = 1; $i <= $chunks_posts; $i++): ?>
    <sitemap>
        <loc><?= htmlspecialchars($url) ?>/sitemap-posts-<?= htmlspecialchars($i) ?>.xml</loc>
        <lastmod><?= htmlspecialchars(date('Y-m-d')) ?></lastmod>
    </sitemap>
<?php endfor ?>
<?php for ($i = 1; $i <= $chunks_pages; $i++): ?>
    <sitemap>
        <loc><?= htmlspecialchars($url) ?>/sitemap-pages-<?= htmlspecialchars($i) ?>.xml</loc>
        <lastmod><?= htmlspecialchars(date('Y-m-d')) ?></lastmod>
    </sitemap>
<?php endfor ?>
</sitemapindex>