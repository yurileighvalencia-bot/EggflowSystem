<?php

namespace Tests\Feature\Phase7;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Route verification tests - no database required
 */
class RouteVerificationTest extends TestCase
{
    #[Test]
    public function reports_routes_are_registered(): void
    {
        // Check via artisan route:list output
        $output = shell_exec('php artisan route:list --json');
        $routes = json_decode($output, true);
        
        $reportRoutes = array_filter($routes, function($route) {
            return str_starts_with($route['uri'] ?? '', 'reports/');
        });
        
        $this->assertNotEmpty($reportRoutes, 'Report routes should be registered');
    }

    #[Test]
    public function notifications_routes_are_registered(): void
    {
        $output = shell_exec('php artisan route:list --json');
        $routes = json_decode($output, true);
        
        $notificationRoutes = array_filter($routes, function($route) {
            $uri = $route['uri'] ?? '';
            return $uri === 'notifications' || str_starts_with($uri, 'notifications/');
        });
        
        $this->assertNotEmpty($notificationRoutes, 'Notification routes should be registered');
    }

    #[Test]
    public function settings_routes_are_registered(): void
    {
        $output = shell_exec('php artisan route:list --json');
        $routes = json_decode($output, true);
        
        $settingsRoutes = array_filter($routes, function($route) {
            $uri = $route['uri'] ?? '';
            return $uri === 'settings' || str_starts_with($uri, 'settings/');
        });
        
        $this->assertNotEmpty($settingsRoutes, 'Settings routes should be registered');
    }

    #[Test]
    public function inventory_report_route_has_correct_action(): void
    {
        $output = shell_exec('php artisan route:list --json');
        $routes = json_decode($output, true);
        
        $inventoryRoute = array_filter($routes, function($route) {
            return ($route['uri'] ?? '') === 'reports/inventory';
        });
        
        $this->assertNotEmpty($inventoryRoute, 'Inventory report route should exist');
        
        $route = reset($inventoryRoute);
        $this->assertStringContainsString('InventoryReport', $route['action'] ?? '');
    }

    #[Test]
    public function wastage_report_route_has_correct_action(): void
    {
        $output = shell_exec('php artisan route:list --json');
        $routes = json_decode($output, true);
        
        $wastageRoute = array_filter($routes, function($route) {
            return ($route['uri'] ?? '') === 'reports/wastage';
        });
        
        $this->assertNotEmpty($wastageRoute, 'Wastage report route should exist');
        
        $route = reset($wastageRoute);
        $this->assertStringContainsString('WastageReport', $route['action'] ?? '');
    }

    #[Test]
    public function collection_sales_comparison_route_has_correct_action(): void
    {
        $output = shell_exec('php artisan route:list --json');
        $routes = json_decode($output, true);
        
        $comparisonRoute = array_filter($routes, function($route) {
            return ($route['uri'] ?? '') === 'reports/collection-sales';
        });
        
        $this->assertNotEmpty($comparisonRoute, 'Collection-sales comparison route should exist');
        
        $route = reset($comparisonRoute);
        $this->assertStringContainsString('CollectionSalesComparison', $route['action'] ?? '');
    }
}
