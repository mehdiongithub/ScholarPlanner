<?php

class ConfigTest {
    /**
     * Run configuration loading smoke tests
     */
    public function run(): void {
        echo "--- Running ConfigTest ---\n";
        
        // Assert app configuration loads
        $appName = config('app.name');
        if (empty($appName)) {
            throw new \Exception("Config Failure: 'app.name' returned empty.");
        }
        echo "✔ Central configuration app.name resolved: '$appName'\n";
        
        // Assert database config keys mapping
        $dbHost = config('database.host');
        if (empty($dbHost)) {
            throw new \Exception("Config Failure: 'database.host' is empty.");
        }
        echo "✔ Central configuration database.host resolved: '$dbHost'\n";
        
        // Assert default currencies
        $currency = config('payment.currency');
        if ($currency !== 'PKR') {
             throw new \Exception("Config Failure: 'payment.currency' is '$currency' instead of 'PKR'.");
        }
        echo "✔ Central configuration payment.currency resolved: '$currency'\n";
        
        echo "ConfigTest PASSED.\n\n";
    }
}
