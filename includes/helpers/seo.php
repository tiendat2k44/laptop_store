<?php
/**
 * SEO Helper Functions
 */

function seo_meta_tags($title, $description = '', $image = '', $url = '') {
    global $product; // For product pages
    
    $siteName = SITE_NAME;
    $fullTitle = $title ? ($title . ' - ' . $siteName) : $siteName;
    $desc = $description ?: 'Mua sắm laptop chính hãng, giá tốt nhất thị trường. Đa dạng mẫu mã từ các thương hiệu hàng đầu.';
    $img = $image ?: (SITE_URL . '/assets/images/logo.png');
    $currentUrl = $url ?: (SITE_URL . $_SERVER['REQUEST_URI']);
    
    // Canonical URL cleanup
    $canonical = strtok($currentUrl, '?'); // Remove query string
    
    echo '<!-- SEO Meta Tags -->' . "\n";
    echo '<title>' . escape($fullTitle) . '</title>' . "\n";
    echo '<meta name="description" content="' . escape($desc) . '">' . "\n";
    echo '<link rel="canonical" href="' . escape($canonical) . '">' . "\n";
    
    // Open Graph
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:site_name" content="' . escape($siteName) . '">' . "\n";
    echo '<meta property="og:title" content="' . escape($fullTitle) . '">' . "\n";
    echo '<meta property="og:description" content="' . escape($desc) . '">' . "\n";
    echo '<meta property="og:image" content="' . escape($img) . '">' . "\n";
    echo '<meta property="og:url" content="' . escape($currentUrl) . '">' . "\n";
    
    // Twitter Card
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . escape($fullTitle) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . escape($desc) . '">' . "\n";
    echo '<meta name="twitter:image" content="' . escape($img) . '">' . "\n";
    
    // Structured Data (JSON-LD)
    if (!empty($product) && isset($product['id'])) {
        // Product schema
        $schema = [
            "@context" => "https://schema.org/",
            "@type" => "Product",
            "name" => $product['name'],
            "description" => $product['description'] ?? $desc,
            "image" => image_url($product['thumbnail'] ?? ''),
            "offers" => [
                "@type" => "Offer",
                "url" => SITE_URL . '/product-detail.php?id=' . $product['id'],
                "priceCurrency" => "VND",
                "price" => getDisplayPrice($product['price'], $product['sale_price']),
                "availability" => (int)$product['stock_quantity'] > 0 ? "https://schema.org/InStock" : "https://schema.org/OutOfStock"
            ]
        ];
        if (($product['rating_average'] ?? 0) > 0) {
            $schema['aggregateRating'] = [
                "@type" => "AggregateRating",
                "ratingValue" => ($product['rating_average'] ?? 0),
                "reviewCount" => ($product['review_count'] ?? 0)
            ];
        }
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
    }
}
