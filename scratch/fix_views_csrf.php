<?php
define('ROOT_PATH', dirname(__DIR__));

function replaceInDir($dir) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $path = $file->getRealPath();
            $content = file_get_contents($path);
            
            // Replace short name Security::csrfToken() with fully qualified name
            // but make sure we don't duplicate it if it already starts with a backslash
            $newContent = preg_replace('/(?<!\\\\App\\\\Helpers\\\\)(?<!\\\\)Security::csrfToken\(\)/', '\\\App\\Helpers\\Security::csrfToken()', $content);
            
            if ($newContent !== $content) {
                file_put_contents($path, $newContent);
                echo "Updated: $path\n";
            }
        }
    }
}

replaceInDir(ROOT_PATH . '/app/Views');
echo "CSRF token namespace fixes complete.\n";
