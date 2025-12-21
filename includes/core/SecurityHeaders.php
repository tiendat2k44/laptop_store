<?php
/**
 * Security Headers Middleware
 * Thêm HTTP security headers vào tất cả responses
 * 
 * Bảo vệ chống:
 * - Clickjacking (X-Frame-Options)
 * - MIME type sniffing (X-Content-Type-Options)
 * - XSS attacks (X-XSS-Protection, CSP)
 * - Man-in-the-middle (HSTS, Referrer-Policy)
 */

class SecurityHeaders {
    /**
     * Apply security headers to response
     * 
     * @param bool $isHttps Whether connection is HTTPS
     * @return void
     */
    public static function apply($isHttps = false) {
        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enable XSS filter in older browsers
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Permissions policy (formerly Feature Policy)
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        
        // Content Security Policy - prevent XSS and injection attacks
        $csp = self::getContentSecurityPolicy();
        header("Content-Security-Policy: $csp");
        
        // For HTTPS, add HSTS header
        if ($isHttps) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        // Disable cache for sensitive pages
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }

    /**
     * Get Content Security Policy header value
     * 
     * Adjust directives based on your needs:
     * - 'self' = same domain only
     * - 'unsafe-inline' = allow inline scripts (not recommended)
     * - 'unsafe-eval' = allow eval() (dangerous!)
     * - Specific domains = trusted sources
     * 
     * @return string
     */
    private static function getContentSecurityPolicy() {
        return implode('; ', [
            // Default fallback
            "default-src 'self'",
            
            // Scripts - allow from self and CDNs
            "script-src 'self' 'unsafe-inline' cdn.jsdelivr.net ajax.googleapis.com",
            
            // Styles - allow from self and CDNs
            "style-src 'self' 'unsafe-inline' cdn.jsdelivr.net fonts.googleapis.com",
            
            // Fonts - allow from CDNs and self
            "font-src 'self' fonts.gstatic.com cdn.jsdelivr.net data:",
            
            // Images - allow from self and data URIs
            "img-src 'self' data: https:",
            
            // Media - allow data URIs (for notification sounds, etc)
            "media-src 'self' data:",
            
            // Connect - allow AJAX to self and CDN source maps
            "connect-src 'self' cdn.jsdelivr.net",
            
            // Forms - prevent form submission to unauthorized domains
            "form-action 'self'",
            
            // Frame ancestors - prevent embedding in iframes
            "frame-ancestors 'none'",
            
            // TẮT report-uri để tránh lỗi 404 (endpoint chưa tồn tại)
            // "report-uri /security/csp-report",
        ]);
    }

    /**
     * Apply strict security headers for sensitive pages (admin, payment)
     * 
     * @param bool $isHttps
     * @return void
     */
    public static function applyStrict($isHttps = false) {
        self::apply($isHttps);
        
        // Stricter CSP for admin/payment pages
        $strictCsp = "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self'; form-action 'self'; frame-ancestors 'none'; upgrade-insecure-requests";
        header("Content-Security-Policy: $strictCsp");
    }
}

?>
