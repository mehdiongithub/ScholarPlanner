<?php

namespace App\Controllers;

use App\Services\Database;
use PDO;

class SEOController {
    /**
     * GET /robots.txt
     * Outputs robots rules blocking administrative and private user routes
     */
    public function robots(): void {
        header('Content-Type: text/plain; charset=utf-8');
        
        $appUrl = rtrim(url('/'), '/');
        
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Allow: /scholarships\n";
        echo "Allow: /scholarships/country/\n";
        echo "Allow: /scholarships/field/\n";
        echo "Allow: /scholarships/degree/\n";
        
        // Disallow private administrative and user-restricted segments
        echo "Disallow: /admin/\n";
        echo "Disallow: /dashboard/\n";
        echo "Disallow: /profile/\n";
        echo "Disallow: /documents/\n";
        echo "Disallow: /applications/\n";
        echo "Disallow: /api/\n";
        echo "Disallow: /login\n";
        echo "Disallow: /register\n";
        echo "Disallow: /forgot-password\n";
        echo "Disallow: /reset-password\n";
        echo "Disallow: /logout\n";
        
        // Disallow checkout/transaction URLs
        echo "Disallow: /payment/\n";
        echo "Disallow: /subscription/\n";
        
        echo "\nSitemap: {$appUrl}/sitemap.xml\n";
    }

    /**
     * GET /sitemap.xml
     * Outputs XML Sitemap containing dynamic links to published active opportunities
     */
    public function sitemap(): void {
        header('Content-Type: application/xml; charset=utf-8');
        
        $db = Database::connection();
        $appUrl = rtrim(url('/'), '/');
        
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Static core public landing pages
        $staticUrls = [
            '/',
            '/scholarships',
            '/how-it-works',
            '/pricing',
            '/faq',
            '/about',
            '/contact'
        ];
        
        foreach ($staticUrls as $relUrl) {
            $loc = htmlspecialchars($appUrl . $relUrl, ENT_XML1, 'UTF-8');
            echo "  <url>\n";
            echo "    <loc>{$loc}</loc>\n";
            echo "    <changefreq>daily</changefreq>\n";
            echo "    <priority>0.8</priority>\n";
            echo "  </url>\n";
        }

        // Stream dynamic listings sequentially to protect memory buffer limits
        // Exclude drafts, archived, and expired listings (deadline is in the past)
        $options = [];
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $options[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = false;
        }
        $stmt = $db->prepare("
            SELECT slug, updated_at 
            FROM scholarships 
            WHERE status = 'published' 
              AND (application_deadline IS NULL OR application_deadline >= CURDATE())
            ORDER BY id DESC
        ", $options);
        
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $loc = htmlspecialchars($appUrl . '/scholarships/' . $row['slug'], ENT_XML1, 'UTF-8');
            $lastmod = date('Y-m-d', strtotime($row['updated_at']));
            
            echo "  <url>\n";
            echo "    <loc>{$loc}</loc>\n";
            echo "    <lastmod>{$lastmod}</lastmod>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>0.6</priority>\n";
            echo "  </url>\n";
        }
        
        echo '</urlset>' . "\n";
    }
}
