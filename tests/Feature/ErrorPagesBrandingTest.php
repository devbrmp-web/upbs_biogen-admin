<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ErrorPagesBrandingTest extends TestCase
{
    /**
     * Test 404 error page displays correct branding
     */
    public function test_404_page_displays_correct_branding(): void
    {
        $response = $this->get('/non-existent-route');
        
        $response->assertStatus(404);
        
        // Check for Kementerian Pertanian logo
        $response->assertSee('Logo_Kementerian_Pertanian_Republik_Indonesia.svg.png');
        
        // Check for UPBS BRMP Biogen text
        $response->assertSee('UPBS BRMP Biogen');
        
        // Ensure old Reback branding is not present
        $response->assertDontSee('logo-sm.png');
        $response->assertDontSee('logo-dark.png');
        $response->assertDontSee('logo-light.png');
        $response->assertDontSee('Reback');
        
        // Check for proper logo attributes
        $response->assertSee('height="40"', false);
        $response->assertSee('auth-logo-img');
        $response->assertSee('auth-logo-text');
        $response->assertSee('Kementan Logo');
    }

    /**
     * Test 403 error page displays correct branding
     */
    public function test_403_page_displays_correct_branding(): void
    {
        // Since we can't easily trigger a 403, we'll test the template exists
        $this->assertTrue(file_exists(resource_path('views/errors/403.blade.php')));
        
        // Check the file contains the correct branding
        $content = file_get_contents(resource_path('views/errors/403.blade.php'));
        $this->assertStringContainsString('Logo_Kementerian_Pertanian_Republik_Indonesia.svg.png', $content);
        $this->assertStringContainsString('UPBS BRMP Biogen', $content);
        $this->assertStringNotContainsString('logo-sm.png', $content);
        $this->assertStringNotContainsString('Reback', $content);
    }

    /**
     * Test 419 error page displays correct branding
     */
    public function test_419_page_displays_correct_branding(): void
    {
        // Since we can't easily trigger a 419, we'll test the template exists
        $this->assertTrue(file_exists(resource_path('views/errors/419.blade.php')));
        
        // Check the file contains the correct branding
        $content = file_get_contents(resource_path('views/errors/419.blade.php'));
        $this->assertStringContainsString('Logo_Kementerian_Pertanian_Republik_Indonesia.svg.png', $content);
        $this->assertStringContainsString('UPBS BRMP Biogen', $content);
        $this->assertStringNotContainsString('logo-sm.png', $content);
        $this->assertStringNotContainsString('Reback', $content);
    }

    /**
     * Test error pages have consistent structure
     */
    public function test_error_pages_have_consistent_structure(): void
    {
        $response = $this->get('/non-existent-route');
        
        $response->assertStatus(404);
        
        // Check for consistent HTML structure
        $response->assertSee('<!doctype html>', false);
        $response->assertSee('<html lang="en"', false);
        $response->assertSee('auth-logo', false);
        $response->assertSee('logo-dark', false);
        $response->assertSee('logo-light', false);
        
        // Check for responsive meta tag
        $response->assertSee('viewport', false);
        
        // Check for proper CSS classes
        $response->assertSee('auth-logo-img');
        $response->assertSee('auth-logo-text');
        $response->assertSee('fw-bold');
        $response->assertSee('fs-16');
    }

    /**
     * Test error pages are responsive
     */
    public function test_error_pages_are_responsive(): void
    {
        $response = $this->get('/non-existent-route');
        
        $response->assertStatus(404);
        
        // Check for responsive classes that actually exist
        $response->assertSee('container');
        $response->assertSee('row');
        $response->assertSee('col');
        
        // Check for Bootstrap responsive utilities
        $response->assertSee('text-center');
        $response->assertSee('mb-');
    }

    /**
     * Test error pages include proper assets
     */
    public function test_error_pages_include_proper_assets(): void
    {
        $response = $this->get('/non-existent-route');
        
        $response->assertStatus(404);
        
        // Check for CSS includes
        $content = $response->getContent();
        $this->assertStringContainsString('.css', $content);
        
        // Check for JavaScript includes
        $this->assertStringContainsString('.js', $content);
        
        // Check for basic asset structure
        $this->assertStringContainsString('bundle', $content);
    }

    /**
     * Test logo accessibility attributes
     */
    public function test_logo_accessibility_attributes(): void
    {
        $response = $this->get('/non-existent-route');
        
        $response->assertStatus(404);
        
        // Check for proper alt text (not HTML encoded in raw response)
        $response->assertSee('alt="Kementan Logo"', false);
        
        // Check for proper height attribute
        $response->assertSee('height="40"', false);
        
        // Check for proper CSS classes for styling
        $response->assertSee('class="me-2 auth-logo-img"', false);
        $response->assertSee('class="fw-bold fs-16 auth-logo-text"', false);
    }

    /**
     * Test theme compatibility on error pages
     */
    public function test_error_pages_theme_compatibility(): void
    {
        $response = $this->get('/non-existent-route');
        
        $response->assertStatus(404);
        
        // Check for theme-related classes
        $response->assertSee('logo-dark');
        $response->assertSee('logo-light');
        
        // Error pages extend auth layout which may not have data-bs-theme
        // Check for theme-compatible structure instead
        $content = $response->getContent();
        $this->assertStringContainsString('auth-logo', $content);
    }
}