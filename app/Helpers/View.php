<?php

namespace App\Helpers;

class View {
    /**
     * Render a view file by loading it and extracting template data.
     */
    public static function render(string $name, array $data = []): void {
        extract($data);
        
        $path = ROOT_PATH . '/app/Views/' . str_replace('.', '/', $name) . '.php';
        if (file_exists($path)) {
            require $path;
        } else {
            throw new \Exception("View template '$name' not found at '$path'");
        }
    }
}
