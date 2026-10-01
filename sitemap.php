<?php
declare(strict_types=1);

/**
 * ScholarPlanner Dynamic XML Sitemap
 *
 * URL:
 * https://scholarplanner.com/sitemap.xml
 */

header('Content-Type: application/xml; charset=UTF-8');

$baseUrl = 'https://scholarplanner.com';

/**
 * ---------------------------------------------------------
 * Load .env
 * ---------------------------------------------------------
 */

$envFile = __DIR__ . '/.env';

if (!file_exists($envFile)) {
    http_response_code(500);
    exit('Environment configuration not found.');
}

$env = [];

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {

    $line = trim($line);

    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }

    if (!str_contains($line, '=')) {
        continue;
    }

    [$key, $value] = explode('=', $line, 2);

    $key = trim($key);
    $value = trim($value);

    if (
        strlen($value) >= 2 &&
        (
            ($value[0] === '"' && $value[strlen($value) - 1] === '"') ||
            ($value[0] === "'" && $value[strlen($value) - 1] === "'")
        )
    ) {
        $value = substr($value, 1, -1);
    }

    $env[$key] = $value;
}


/**
 * ---------------------------------------------------------
 * Database configuration
 * ---------------------------------------------------------
 *
 * Supports common .env names.
 */

$dbHost = $env['DB_HOST'] ?? '';
$dbPort = $env['DB_PORT'] ?? '3306';
$dbName = $env['DB_DATABASE'] ?? ($env['DB_NAME'] ?? '');
$dbUser = $env['DB_USERNAME'] ?? ($env['DB_USER'] ?? '');
$dbPass = $env['DB_PASSWORD'] ?? '';


if (
    $dbHost === '' ||
    $dbName === '' ||
    $dbUser === ''
) {
    http_response_code(500);
    exit('Database configuration is incomplete.');
}


/**
 * ---------------------------------------------------------
 * Create PDO connection
 * ---------------------------------------------------------
 */

try {

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $dbHost,
        $dbPort,
        $dbName
    );

    $pdo = new PDO(
        $dsn,
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

} catch (Throwable $e) {

    error_log(
        'ScholarPlanner sitemap database error: ' . $e->getMessage()
    );

    http_response_code(500);
    exit('Sitemap database connection failed.');
}


/**
 * ---------------------------------------------------------
 * XML escaping
 * ---------------------------------------------------------
 */

function xmlEscape(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_XML1 | ENT_QUOTES,
        'UTF-8'
    );
}


/**
 * ---------------------------------------------------------
 * XML Header
 * ---------------------------------------------------------
 */

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <!-- Homepage -->
    <url>
        <loc><?= xmlEscape($baseUrl . '/') ?></loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- Scholarships -->
    <url>
        <loc><?= xmlEscape($baseUrl . '/scholarships') ?></loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>

    <!-- About -->
    <url>
        <loc><?= xmlEscape($baseUrl . '/about') ?></loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <!-- Contact -->
    <url>
        <loc><?= xmlEscape($baseUrl . '/contact') ?></loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <!-- Pricing -->
    <url>
        <loc><?= xmlEscape($baseUrl . '/pricing') ?></loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>

    <!-- FAQ -->
    <url>
        <loc><?= xmlEscape($baseUrl . '/faq') ?></loc>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>

<?php

/**
 * ---------------------------------------------------------
 * Dynamic Scholarship URLs
 * ---------------------------------------------------------
 *
 * The slug is taken directly from the scholarships table.
 *
 * Example:
 * commonwealth-phd-scholarships-2027-28
 *
 * becomes:
 * https://scholarplanner.com/scholarships/commonwealth-phd-scholarships-2027-28
 */

try {

    $sql = "
        SELECT
            slug,
            updated_at
        FROM scholarships
        WHERE slug IS NOT NULL
          AND slug != ''
        ORDER BY updated_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    while ($scholarship = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $slug = trim((string) ($scholarship['slug'] ?? ''));

        if ($slug === '') {
            continue;
        }

        $scholarshipUrl =
            $baseUrl . '/scholarships/' . rawurlencode($slug);

        echo "    <url>\n";

        echo "        <loc>"
            . xmlEscape($scholarshipUrl)
            . "</loc>\n";

        echo "        <changefreq>weekly</changefreq>\n";
        echo "        <priority>0.8</priority>\n";

        if (!empty($scholarship['updated_at'])) {

            $timestamp = strtotime(
                (string) $scholarship['updated_at']
            );

            if ($timestamp !== false) {

                echo "        <lastmod>"
                    . date('c', $timestamp)
                    . "</lastmod>\n";
            }
        }

        echo "    </url>\n";
    }

} catch (Throwable $e) {

    error_log(
        'ScholarPlanner sitemap query error: ' . $e->getMessage()
    );
}

?>

</urlset>