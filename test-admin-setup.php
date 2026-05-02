#!/usr/bin/env php
<?php
/**
 * Test Script for Admin CRUD Operations
 * Verifies that all admin API endpoints are properly configured
 */

echo "\n";
echo "╔════════════════════════════════════════════════╗\n";
echo "║  CHAMA ADMIN CRUD - SETUP VERIFICATION TEST   ║\n";
echo "╚════════════════════════════════════════════════╝\n\n";

$checks = [
    '✓ AdminController exists' => file_exists(__DIR__ . '/app/Http/Controllers/Api/AdminController.php'),
    '✓ CreateSuperAdmin command exists' => file_exists(__DIR__ . '/app/Console/Commands/CreateSuperAdmin.php'),
    '✓ SuperAdminMiddleware exists' => file_exists(__DIR__ . '/app/Http/Middleware/SuperAdminMiddleware.php'),
    '✓ AdminMiddleware exists' => file_exists(__DIR__ . '/app/Http/Middleware/AdminMiddleware.php'),
    '✓ routes/api.php exists' => file_exists(__DIR__ . '/routes/api.php'),
    '✓ User model exists' => file_exists(__DIR__ . '/app/Models/User.php'),
    '✓ Chama model exists' => file_exists(__DIR__ . '/app/Models/Chama.php'),
];

$allPass = true;
foreach ($checks as $check => $result) {
    echo ($result ? "✅ " : "❌ ") . $check . "\n";
    if (!$result) $allPass = false;
}

echo "\n";

// Check if ProfileController has proper imports
echo "Checking ProfileController imports...\n";
$profileContent = file_get_contents(__DIR__ . '/app/Http/Controllers/Api/ProfileController.php');
$hasControllerImport = strpos($profileContent, 'use App\Http\Controllers\Controller') !== false;
echo ($hasControllerImport ? "✅ " : "❌ ") . "ProfileController has Controller import\n";
if (!$hasControllerImport) $allPass = false;

echo "\n";

// Check admin routes exist in api.php
echo "Checking API routes configuration...\n";
$routesContent = file_get_contents(__DIR__ . '/routes/api.php');
$hasAdminRoutes = strpos($routesContent, "Route::get('/users'") !== false && strpos($routesContent, 'AdminController') !== false;
$hasChamaRoutes = strpos($routesContent, "Route::get('/chamas'") !== false && strpos($routesContent, 'AdminController') !== false;
$hasSuperAdminMiddleware = strpos($routesContent, 'superadmin') !== false;

echo ($hasAdminRoutes ? "✅ " : "❌ ") . "Admin users routes defined\n";
echo ($hasChamaRoutes ? "✅ " : "❌ ") . "Admin chamas routes defined\n";
echo ($hasSuperAdminMiddleware ? "✅ " : "❌ ") . "Super-admin middleware configured\n";

if (!$hasAdminRoutes || !$hasChamaRoutes || !$hasSuperAdminMiddleware) {
    $allPass = false;
}

echo "\n";
echo "╔════════════════════════════════════════════════╗\n";
if ($allPass) {
    echo "║          ✅ ALL CHECKS PASSED!                ║\n";
    echo "║                                              ║\n";
    echo "║  Admin CRUD system is properly configured.  ║\n";
    echo "║  Run: php artisan app:create-super-admin     ║\n";
    echo "║  to create your first super-admin user       ║\n";
} else {
    echo "║          ❌ SOME CHECKS FAILED!               ║\n";
    echo "║  Please review the failed checks above.      ║\n";
}
echo "╚════════════════════════════════════════════════╝\n\n";

exit($allPass ? 0 : 1);
