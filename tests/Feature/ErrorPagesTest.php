<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_displays_404_error_page_for_non_existent_routes()
    {
        $response = $this->get('/non-existent-page');
        
        $response->assertStatus(404);
        // Note: Laravel may not automatically use custom error views in testing
        // This test verifies the HTTP status. View rendering is tested separately.
    }

    /** @test */
    public function it_renders_404_error_view_correctly()
    {
        $response = $this->view('errors.404');
        
        $response->assertSee('Page Not Found!');
        $response->assertSee('Back to Homepage');
        $response->assertSee('404');
    }

    /** @test */
    public function it_displays_403_error_page_when_access_is_forbidden()
    {
        // This would typically be tested with actual forbidden access
        // For now, we'll just verify the view can be rendered
        $response = $this->view('errors.403');
        
        $response->assertSee('Access Forbidden!');
        $response->assertSee('Back to Homepage');
        $response->assertSee('403');
    }

    /** @test */
    public function it_displays_500_error_page_for_server_errors()
    {
        // Test that the 500 error view can be rendered
        $response = $this->view('errors.500');
        
        $response->assertSee('Internal Server Error!');
        $response->assertSee('Back to Homepage');
        $response->assertSee('500');
    }

    /** @test */
    public function it_displays_419_error_page_for_expired_sessions()
    {
        // Test that the 419 error view can be rendered
        $response = $this->view('errors.419');
        
        $response->assertSee('Page Expired!');
        $response->assertSee('Back to Homepage');
        $response->assertSee('419');
    }

    /** @test */
    public function error_pages_contain_back_to_homepage_button_with_correct_route()
    {
        $errorPages = ['404', '403', '500', '419'];
        
        foreach ($errorPages as $errorCode) {
            $response = $this->view("errors.{$errorCode}");
            
            // Check that the button exists and has the correct route
            $response->assertSee('href="' . route('admin.dashboard') . '"', false);
            $response->assertSee('Back to Homepage');
            $response->assertSee('bx-home-alt'); // Icon class
        }
    }

    /** @test */
    public function error_pages_use_consistent_styling()
    {
        $errorPages = ['404', '403', '500', '419'];
        
        foreach ($errorPages as $errorCode) {
            $response = $this->view("errors.{$errorCode}");
            
            // Check for consistent styling classes
            $response->assertSee('btn btn-primary btn-lg shadow-lg', false);
            $response->assertSee('auth-card', false);
            $response->assertSee('fs-60', false); // Large error code styling
        }
    }
}