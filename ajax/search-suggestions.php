<?php
require_once __DIR__ . '/../includes/init.php';

$q = trim($_GET['q'] ?? '');
if ($q === '' || mb_strlen($q) < 2) {
    jsonResponse(['success' => true, 'products' => []]);
}

try {
    $db = Database::getInstance();
    $rows = $db->query(
        "SELECT p.id, p.name, p.price, p.sale_price,
                COALESCE(p.thumbnail, (SELECT pi.image_url FROM product_images pi WHERE pi.product_id=p.id ORDER BY pi.display_order, pi.id LIMIT 1)) AS img
         FROM products p
         WHERE p.status = 'active' AND (p.name ILIKE :kw OR p.slug ILIKE :kw)
         ORDER BY p.featured DESC, p.sold_count DESC, p.views DESC
         LIMIT 10",
        ['kw' => '%' . $q . '%']
    );

    $products = [];
    foreach ($rows as $r) {
        $displayPrice = getDisplayPrice((float)$r['price'], $r['sale_price'] !== null ? (float)$r['sale_price'] : null);
        $products[] = [
            'id' => (int)$r['id'],
            'name' => $r['name'],
            'price' => formatPrice($displayPrice),
            'image' => image_url($r['img'] ?? ''),
        ];
    }

    jsonResponse(['success' => true, 'products' => $products]);
} catch (Exception $e) {
    error_log('search-suggestions error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'products' => []], 500);
}
