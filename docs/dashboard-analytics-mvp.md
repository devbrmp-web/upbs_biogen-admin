# Dashboard Analytics MVP Documentation

## Overview
This document summarizes the implementation of the Admin Dashboard Analytics MVP for UPBS BRMP Biogen.

## Caching Strategy
- **Mechanism**: We use `Cache::remember('admin_dashboard_data', 300, ...)` to cache the entire dataset for **5 minutes**.
- **Performance**: This ensures sub-2-second load times even with complex aggregations.
- **Key**: `admin_dashboard_data`.
- **Invalidation**: Currently, the cache expires automatically after 5 minutes. Real-time invalidation (clearing cache on new order) was not implemented to keep it simple and performant (avoiding cache stampede), as per "not needing real-time polling" requirement.

## Fallback Handling
- **Implementation**: The controller logic is wrapped in a `try-catch` block.
- **Behavior**: If the database query fails or connection drops, the `catch` block catches the exception.
- **Result**: It returns the dashboard view with a `isFallback` flag set to `true`. The UI displays a warning alert ("Mode Offline/Fallback") and shows safe default values (0 or "-") instead of crashing.

## Adjusting Filter Periods
To change the reporting period (e.g., from 7 days to 30 days):
1. Open `app/Http/Controllers/Admin/DashboardController.php`.
2. Locate `$sevenDaysAgo = Carbon::today()->subDays(6);`.
3. Change `subDays(6)` to `subDays(29)` for a 30-day view.
4. Update the chart label in `resources/views/admin/dashboard.blade.php` (e.g., "Tren 30 Hari").

## UI Compliance
- **Template**: The view extends `layouts.vertical` from the Reback template.
- **Styling**: 
  - Uses standard Bootstrap 5 classes provided by the template.
  - Custom CSS is scoped within the `@section('css')` block in `dashboard.blade.php` to ensure no side effects on other pages.
  - High contrast colors and large fonts (3rem) are used for KPIs to meet "projector-friendly" requirements.
- **Charts**: Uses `ApexCharts` which is already integrated into the template. The initialization script is included in `@section('script-bottom')`.

## Testing
- **Feature Test**: `tests/Feature/Admin/DashboardTest.php` covers:
  - Successful page load.
  - Data accuracy (Orders Today, Critical Stock).
  - Fallback mechanism verification.
- **Run Tests**: `php artisan test tests/Feature/Admin/DashboardTest.php`
