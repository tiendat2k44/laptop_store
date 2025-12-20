<?php
require_once 'includes/init.php';

header('Content-Type: application/xml; charset=utf-8');

$db = Database::getInstance();

// Get all active products
$products = $db->query(
    "SELECT id, updated_at FROM products WHERE status = 'active' ORDER BY updated_at DESC LIMIT 5000"
);

// Get all categories
$categories = $db->query(
    "SELECT id, updated_at FROM categories WHERE status = 'active' ORDER BY id"
);

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Homepage
echo "  <url>\n";
echo "    <loc>" . SITE_URL . "/</loc>\n";
echo "    <changefreq>daily</changefreq>\n";
echo "    <priority>1.0</priority>\n";
echo "  </url>\n";

// Products page
echo "  <url>\n";
echo "    <loc>" . SITE_URL . "/products.php</loc>\n";
echo "    <changefreq>hourly</changefreq>\n";
echo "    <priority>0.9</priority>\n";
echo "  </url>\n";

// All products
foreach ($products as $product) {
    echo "  <url>\n";
    echo "    <loc>" . SITE_URL . "/product-detail.php?id=" . $product['id'] . "</loc>\n";
    if ($product['updated_at']) {
        echo "    <lastmod>" . date('Y-m-d', strtotime($product['updated_at'])) . "</lastmod>\n";
    }
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>0.8</priority>\n";
    echo "  </url>\n";
}

// All categories
foreach ($categories as $category) {
    echo "  <url>\n";
    echo "    <loc>" . SITE_URL . "/products.php?category_id=" . $category['id'] . "</loc>\n";
    if ($category['updated_at']) {
        echo "    <lastmod>" . date('Y-m-d', strtotime($category['updated_at'])) . "</lastmod>\n";
    }
    echo "    <changefreq>daily</changefreq>\n";
    echo "    <priority>0.7</priority>\n";
    echo "  </url>\n";
}

// Static pages
$staticPages = [
    ['url' => '/login.php', 'priority' => '0.5', 'changefreq' => 'monthly'],
    ['url' => '/register.php', 'priority' => '0.5', 'changefreq' => 'monthly'],
    ['url' => '/cart.php', 'priority' => '0.6', 'changefreq' => 'always'],
    ['url' => '/wishlist.php', 'priority' => '0.6', 'changefreq' => 'always']
];

foreach ($staticPages as $page) {
    echo "  <url>\n";
    echo "    <loc>" . SITE_URL . $page['url'] . "</loc>\n";
    echo "    <changefreq>" . $page['changefreq'] . "</changefreq>\n";
    echo "    <priority>" . $page['priority'] . "</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>';
