<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ThemeSwitchingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that login page loads successfully
     */
    public function test_login_page_loads_successfully(): void
    {
        $response = $this->get('/login');
        
        $response->assertStatus(200);
        $response->assertSee('Sign In');
    }

    /**
     * Test that theme initialization script is present in auth layout
     */
    public function test_theme_initialization_script_present(): void
    {
        $response = $this->get('/login');
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Check for FOUC prevention in auth layout
        $this->assertStringContainsString('no-js', $content);
        $this->assertStringContainsString('DOMContentLoaded', $content);
    }

    /**
     * Test that basic HTML structure is present
     */
    public function test_basic_html_structure_present(): void
    {
        $response = $this->get('/login');
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Check for basic HTML structure
        $this->assertStringContainsStringIgnoringCase('<!doctype html>', $content);
        $this->assertStringContainsString('<html', $content);
        $this->assertStringContainsString('authentication-bg', $content);
    }

    /**
     * Test that viewport meta tag is present for responsive design
     */
    public function test_viewport_meta_tag_present(): void
    {
        $response = $this->get('/login');
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Check for responsive design support
        $this->assertStringContainsString('viewport', $content);
        $this->assertStringContainsString('width=device-width', $content);
    }

    /**
     * Test that CSS and JS assets are loaded
     */
    public function test_assets_are_loaded(): void
    {
        $response = $this->get('/login');
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Check for CSS and JS assets
        $this->assertStringContainsString('css', $content);
        $this->assertStringContainsString('script', $content);
    }

    /**
     * Test that form elements are properly structured
     */
    public function test_form_elements_structured(): void
    {
        $response = $this->get('/login');
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Check for form structure
        $this->assertStringContainsString('form', $content);
        $this->assertStringContainsString('input', $content);
        $this->assertStringContainsString('button', $content);
    }

    /**
     * Test that CSRF protection is enabled
     */
    public function test_csrf_protection_enabled(): void
    {
        $response = $this->get('/login');
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Check for CSRF token
        $this->assertStringContainsString('_token', $content);
    }

    /**
     * Test that Bootstrap classes are used
     */
    public function test_bootstrap_classes_used(): void
    {
        $response = $this->get('/login');
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Check for Bootstrap classes
        $this->assertStringContainsString('btn', $content);
        $this->assertStringContainsString('form-', $content);
        $this->assertStringContainsString('mb-', $content);
    }

    /**
     * Test that page structure is complete
     */
    public function test_complete_page_structure(): void
    {
        $response = $this->get('/login');
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Check for complete page structure
        $this->assertStringContainsString('<html', $content);
        $this->assertStringContainsString('<head>', $content);
        $this->assertStringContainsString('</html>', $content);
        $this->assertStringContainsString('UPBS BRMP Biogen', $content);
    }
}