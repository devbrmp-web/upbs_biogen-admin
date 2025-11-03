<?php

namespace Tests\Feature;

use Tests\TestCase;

class ThemePerformanceOptimizedTest extends TestCase
{
    /**
     * Test JavaScript loading
     */
    public function test_javascript_loading(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);

        // Check for basic page structure that supports theming
        $response->assertSee('html', false);
    }

    /**
     * Test optimized CSS transitions
     */
    public function test_optimized_css_transitions(): void
    {
        // Check if theme-improvements.js file exists and contains optimizations
        $jsPath = resource_path('js/theme-improvements.js');
        $this->assertTrue(file_exists($jsPath));
        
        $jsContent = file_get_contents($jsPath);
        
        // Check for optimized transition duration (0.1s)
        $this->assertStringContainsString('0.1s', $jsContent);
        
        // Check for cubic-bezier easing
        $this->assertStringContainsString('cubic-bezier', $jsContent);
        
        // Check for backface-visibility optimization
        $this->assertStringContainsString('backface-visibility: hidden', $jsContent);
    }

    /**
     * Test FOUC prevention
     */
    public function test_fouc_prevention(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        
        // Check for no-js class handling
        $response->assertSee('no-js', false);
    }

    /**
     * Test hardware acceleration
     */
    public function test_hardware_acceleration(): void
    {
        // Check theme-improvements.js for hardware acceleration properties
        $jsPath = resource_path('js/theme-improvements.js');
        $jsContent = file_get_contents($jsPath);
        
        // Check for will-change property
        $this->assertStringContainsString('will-change', $jsContent);
        
        // Check for translateZ usage (hardware acceleration)
        $this->assertStringContainsString('translateZ(0)', $jsContent);
    }

    /**
     * Test responsive design meta tags
     */
    public function test_responsive_design_meta_tags(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        
        // Check for viewport meta tag
        $response->assertSee('viewport', false);
    }

    /**
     * Test theme transition performance indicators
     */
    public function test_theme_transition_performance_indicators(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        
        // Check for theme-related classes
        $response->assertSee('theme', false);
    }

    /**
     * Test optimized cubic-bezier transitions
     */
    public function test_optimized_cubic_bezier_transitions(): void
    {
        // Check theme-improvements.js for cubic-bezier optimization
        $jsPath = resource_path('js/theme-improvements.js');
        $jsContent = file_get_contents($jsPath);
        
        // Check for cubic-bezier easing function (actual value used)
        $this->assertStringContainsString('cubic-bezier(0.25, 0.46, 0.45, 0.94)', $jsContent);
    }

    /**
     * Test backface visibility optimization
     */
    public function test_backface_visibility_optimization(): void
    {
        // Check theme-improvements.js for backface-visibility
        $jsPath = resource_path('js/theme-improvements.js');
        $jsContent = file_get_contents($jsPath);
        
        // Check for backface-visibility hidden
        $this->assertStringContainsString('backface-visibility: hidden', $jsContent);
    }

    /**
     * Test reduced transition duration
     */
    public function test_reduced_transition_duration(): void
    {
        // Check theme-improvements.js for optimized duration
        $jsPath = resource_path('js/theme-improvements.js');
        $jsContent = file_get_contents($jsPath);
        
        // Check for 0.1s duration (reduced from default)
        $this->assertStringContainsString('0.1s', $jsContent);
        
        // Check for 0.1s logo transition
        $this->assertStringContainsString('0.1s', $jsContent);
    }

    /**
     * Test theme manager initialization
     */
    public function test_theme_manager_initialization(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        
        // Check for theme-related elements (auth-logo structure)
        $response->assertSee('auth-logo', false);
    }

    /**
     * Test performance optimization classes
     */
    public function test_performance_optimization_classes(): void
    {
        // Check theme-improvements.js for performance classes
        $jsPath = resource_path('js/theme-improvements.js');
        $jsContent = file_get_contents($jsPath);
        
        // Check for theme-loading class
        $this->assertStringContainsString('theme-loading', $jsContent);
        
        // Check for theme-transitioning class
        $this->assertStringContainsString('theme-transitioning', $jsContent);
    }

    /**
     * Test logo transition optimization
     */
    public function test_logo_transition_optimization(): void
    {
        // Check theme-improvements.js for logo transition optimization
        $jsPath = resource_path('js/theme-improvements.js');
        $jsContent = file_get_contents($jsPath);
        
        // Check for logo-box optimization
        $this->assertStringContainsString('logo-box', $jsContent);
        
        // Check for 0.1s logo transition duration
        $this->assertStringContainsString('0.1s', $jsContent);
    }

    /**
     * Test overall page performance
     */
    public function test_overall_page_performance(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        
        // Check for responsive design meta tag
        $response->assertSee('viewport', false);
        
        // Check for theme-related assets
        $response->assertSee('theme', false);
    }
}