<?php

namespace Tests\Unit\Phase7;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Simple verification tests for Phase 3-6 - no database required
 */
class ComponentVerificationTest extends TestCase
{
    // ==========================================
    // Phase 3 - Reports Module
    // ==========================================
    
    #[Test]
    public function inventory_report_component_class_exists(): void
    {
        $this->assertTrue(class_exists(\App\Livewire\Reports\InventoryReport::class));
    }

    #[Test]
    public function wastage_report_component_class_exists(): void
    {
        $this->assertTrue(class_exists(\App\Livewire\Reports\WastageReport::class));
    }

    #[Test]
    public function collection_sales_comparison_component_class_exists(): void
    {
        $this->assertTrue(class_exists(\App\Livewire\Reports\CollectionSalesComparison::class));
    }

    #[Test]
    public function inventory_report_view_file_exists(): void
    {
        $viewPath = realpath(__DIR__ . '/../../../resources/views/livewire/reports/inventory-report.blade.php');
        $this->assertNotFalse($viewPath);
        $this->assertFileExists($viewPath);
    }

    #[Test]
    public function wastage_report_view_file_exists(): void
    {
        $viewPath = realpath(__DIR__ . '/../../../resources/views/livewire/reports/wastage-report.blade.php');
        $this->assertNotFalse($viewPath);
        $this->assertFileExists($viewPath);
    }

    #[Test]
    public function collection_sales_comparison_view_file_exists(): void
    {
        $viewPath = realpath(__DIR__ . '/../../../resources/views/livewire/reports/collection-sales-comparison.blade.php');
        $this->assertNotFalse($viewPath);
        $this->assertFileExists($viewPath);
    }

    // ==========================================
    // Phase 4 - Notifications Module
    // ==========================================

    #[Test]
    public function notification_center_component_class_exists(): void
    {
        $this->assertTrue(class_exists(\App\Livewire\Dashboard\NotificationCenter::class));
    }

    #[Test]
    public function notification_dropdown_component_class_exists(): void
    {
        $this->assertTrue(class_exists(\App\Livewire\Components\NotificationDropdown::class));
    }

    #[Test]
    public function notification_preferences_component_class_exists(): void
    {
        $this->assertTrue(class_exists(\App\Livewire\Settings\NotificationPreferences::class));
    }

    #[Test]
    public function notification_center_view_file_exists(): void
    {
        $viewPath = realpath(__DIR__ . '/../../../resources/views/livewire/dashboard/notification-center.blade.php');
        $this->assertNotFalse($viewPath);
        $this->assertFileExists($viewPath);
    }

    #[Test]
    public function notification_dropdown_view_file_exists(): void
    {
        $viewPath = realpath(__DIR__ . '/../../../resources/views/livewire/components/notification-dropdown.blade.php');
        $this->assertNotFalse($viewPath);
        $this->assertFileExists($viewPath);
    }

    #[Test]
    public function notification_preferences_view_file_exists(): void
    {
        $viewPath = realpath(__DIR__ . '/../../../resources/views/livewire/settings/notification-preferences.blade.php');
        $this->assertNotFalse($viewPath);
        $this->assertFileExists($viewPath);
    }

    // ==========================================
    // Phase 5 - Settings & Admin Module
    // ==========================================

    #[Test]
    public function settings_page_component_class_exists(): void
    {
        $this->assertTrue(class_exists(\App\Livewire\Settings\SettingsPage::class));
    }

    #[Test]
    public function user_management_component_class_exists(): void
    {
        $this->assertTrue(class_exists(\App\Livewire\Settings\UserManagement::class));
    }

    #[Test]
    public function category_management_component_class_exists(): void
    {
        $this->assertTrue(class_exists(\App\Livewire\Settings\CategoryManagement::class));
    }

    // ==========================================
    // Phase 6 - UI Components
    // ==========================================

    #[Test]
    public function loading_spinner_component_exists(): void
    {
        $viewPath = realpath(__DIR__ . '/../../../resources/views/components/loading-spinner.blade.php');
        $this->assertNotFalse($viewPath);
        $this->assertFileExists($viewPath);
    }

    #[Test]
    public function loading_state_component_exists(): void
    {
        $viewPath = realpath(__DIR__ . '/../../../resources/views/components/loading-state.blade.php');
        $this->assertNotFalse($viewPath);
        $this->assertFileExists($viewPath);
    }

    #[Test]
    public function empty_state_component_exists(): void
    {
        $viewPath = realpath(__DIR__ . '/../../../resources/views/components/empty-state.blade.php');
        $this->assertNotFalse($viewPath);
        $this->assertFileExists($viewPath);
    }

    #[Test]
    public function toast_notifications_component_exists(): void
    {
        $viewPath = realpath(__DIR__ . '/../../../resources/views/components/toast-notifications.blade.php');
        $this->assertNotFalse($viewPath);
        $this->assertFileExists($viewPath);
    }

    #[Test]
    public function skeleton_loader_component_exists(): void
    {
        $viewPath = realpath(__DIR__ . '/../../../resources/views/components/skeleton-loader.blade.php');
        $this->assertNotFalse($viewPath);
        $this->assertFileExists($viewPath);
    }

    #[Test]
    public function page_header_component_exists(): void
    {
        $viewPath = realpath(__DIR__ . '/../../../resources/views/components/page-header.blade.php');
        $this->assertNotFalse($viewPath);
        $this->assertFileExists($viewPath);
    }

    #[Test]
    public function alert_component_exists(): void
    {
        $viewPath = realpath(__DIR__ . '/../../../resources/views/components/alert.blade.php');
        $this->assertNotFalse($viewPath);
        $this->assertFileExists($viewPath);
    }

    #[Test]
    public function confirmation_modal_component_exists(): void
    {
        $viewPath = realpath(__DIR__ . '/../../../resources/views/components/confirmation-modal.blade.php');
        $this->assertNotFalse($viewPath);
        $this->assertFileExists($viewPath);
    }

    #[Test]
    public function button_component_exists(): void
    {
        $viewPath = realpath(__DIR__ . '/../../../resources/views/components/button.blade.php');
        $this->assertNotFalse($viewPath);
        $this->assertFileExists($viewPath);
    }

    #[Test]
    public function badge_component_exists(): void
    {
        $viewPath = realpath(__DIR__ . '/../../../resources/views/components/badge.blade.php');
        $this->assertNotFalse($viewPath);
        $this->assertFileExists($viewPath);
    }

    #[Test]
    public function stat_card_component_exists(): void
    {
        $viewPath = realpath(__DIR__ . '/../../../resources/views/components/stat-card.blade.php');
        $this->assertNotFalse($viewPath);
        $this->assertFileExists($viewPath);
    }

    // ==========================================
    // CSS Animations Verification
    // ==========================================

    #[Test]
    public function app_css_has_fade_in_animation(): void
    {
        $cssPath = realpath(__DIR__ . '/../../../resources/css/app.css');
        $this->assertNotFalse($cssPath);
        $css = file_get_contents($cssPath);
        $this->assertStringContainsString('@keyframes fade-in', $css);
    }

    #[Test]
    public function app_css_has_fade_in_up_animation(): void
    {
        $cssPath = realpath(__DIR__ . '/../../../resources/css/app.css');
        $css = file_get_contents($cssPath);
        $this->assertStringContainsString('@keyframes fade-in-up', $css);
    }

    #[Test]
    public function app_css_has_slide_in_right_animation(): void
    {
        $cssPath = realpath(__DIR__ . '/../../../resources/css/app.css');
        $css = file_get_contents($cssPath);
        $this->assertStringContainsString('@keyframes slide-in-right', $css);
    }

    #[Test]
    public function app_css_has_scale_in_animation(): void
    {
        $cssPath = realpath(__DIR__ . '/../../../resources/css/app.css');
        $css = file_get_contents($cssPath);
        $this->assertStringContainsString('@keyframes scale-in', $css);
    }

    #[Test]
    public function app_css_has_shimmer_animation(): void
    {
        $cssPath = realpath(__DIR__ . '/../../../resources/css/app.css');
        $css = file_get_contents($cssPath);
        $this->assertStringContainsString('@keyframes shimmer', $css);
    }

    #[Test]
    public function app_css_has_animation_utility_classes(): void
    {
        $cssPath = realpath(__DIR__ . '/../../../resources/css/app.css');
        $css = file_get_contents($cssPath);
        
        $this->assertStringContainsString('.animate-fade-in', $css);
        $this->assertStringContainsString('.animate-fade-in-up', $css);
        $this->assertStringContainsString('.animate-slide-in-right', $css);
        $this->assertStringContainsString('.animate-scale-in', $css);
    }
}
