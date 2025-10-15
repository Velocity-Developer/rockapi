<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Auth Menu API ===\n\n";

use App\Http\Controllers\Dash\ConfigController;
use Illuminate\Http\Request;

$controller = new ConfigController();

// Mock authenticated user
$user = \App\Models\User::first();
if (!$user) {
    echo "❌ No user found in database!\n";
    exit(1);
}

echo "Using user: {$user->id} - {$user->name}\n";
echo "User roles: " . $user->roles->pluck('name')->join(', ') . "\n\n";

// Create request with authenticated user
$request = Request::create('/api/dash/config', 'GET');
$request->setUserResolver(function() use ($user) {
    return $user;
});

// Mock Sanctum authentication
\Illuminate\Support\Facades\Auth::setUser($user);

try {
    $response = $controller->index($request);
    $data = $response->getData(true);

    echo "✅ Authenticated config API working!\n";
    echo "App Name: " . $data['app_name'] . "\n";
    echo "App Menus count: " . count($data['app_menus']) . "\n";

    if (isset($data['app_menus'])) {
        echo "Menu items:\n";
        foreach ($data['app_menus'] as $index => $menu) {
            $route = $menu['route'] ?? 'NO_ROUTE';
            $label = $menu['label'] ?? 'NO_LABEL';
            $key = $menu['key'] ?? 'NO_KEY';
            echo "[$index] $key - $label ($route)\n";

            // Check if this is TodoList menu
            if ($key === 'todos') {
                echo "  🎯 Found TodoList menu!\n";
                print_r($menu);
            }

            // Check if this menu has sub-items
            if (isset($menu['items']) && is_array($menu['items'])) {
                echo "  📁 Sub-items:\n";
                foreach ($menu['items'] as $subIndex => $subItem) {
                    $subRoute = $subItem['route'] ?? 'NO_ROUTE';
                    $subLabel = $subItem['label'] ?? 'NO_LABEL';
                    $subKey = $subItem['key'] ?? 'NO_KEY';
                    echo "    [$subIndex] $subKey - $subLabel ($subRoute)\n";
                }
            }
        }
    }

    echo "\n✅ TodoList should be visible in menu!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}