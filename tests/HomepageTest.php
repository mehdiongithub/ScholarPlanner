<?php

use App\Controllers\HomeController;

class HomepageTest {
    /**
     * Run homepage asset structure and rendering checks
     */
    public function run(): void {
        echo "--- Running HomepageTest ---\n";
        
        // 1. Assert directories exist
        $requiredDirs = [
            ROOT_PATH . '/storage/logs',
            ROOT_PATH . '/storage/cache',
            ROOT_PATH . '/storage/uploads',
            ROOT_PATH . '/app/Controllers',
            ROOT_PATH . '/app/Views',
            ROOT_PATH . '/assets/css',
            ROOT_PATH . '/assets/js',
        ];
        
        foreach ($requiredDirs as $dir) {
            if (!file_exists($dir) || !is_dir($dir)) {
                throw new \Exception("Directory check failed: '$dir' does not exist.");
            }
            echo "✔ Directory exists: " . basename($dir) . "\n";
        }
        
        // 2. Assert assets exist
        $requiredAssets = [
            ROOT_PATH . '/assets/css/style.css',
            ROOT_PATH . '/assets/js/main.js',
        ];
        
        foreach ($requiredAssets as $asset) {
            if (!file_exists($asset) || filesize($asset) === 0) {
                throw new \Exception("Asset check failed: '$asset' is missing or empty.");
            }
            echo "✔ Asset file verified: " . basename($asset) . " (" . filesize($asset) . " bytes)\n";
        }
        
        // 3. Test HomeController dynamic page generation
        ob_start();
        try {
            $controller = new HomeController();
            $controller->index();
            $output = ob_get_clean();
            
            if (empty($output)) {
                throw new \Exception("Homepage compilation failed: output is empty.");
            }
            
            $indicators = [
                '<!DOCTYPE html>',
                'ScholarMatch',
                'Find Scholarships That Match You',
                'assets/css/style.css',
                'assets/js/main.js'
            ];
            
            foreach ($indicators as $indicator) {
                if (stripos($output, $indicator) === false) {
                    throw new \Exception("Homepage markup missing indicator: '$indicator'");
                }
            }
            echo "✔ Homepage compiles and renders key elements successfully.\n";
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }
        
        echo "HomepageTest PASSED.\n\n";
    }
}
