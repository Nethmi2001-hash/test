<?php

/**
 * Application Routes
 * Define all application routes here
 */

use App\Core\Application;

$router = Application::getInstance()->getRouter();

// ============================================
// PUBLIC ROUTES
// ============================================

$router->get('/', 'App\Controllers\HomeController@index');
$router->get('/login', 'App\Controllers\AuthController@showLogin');
$router->post('/login', 'App\Controllers\AuthController@login');
$router->get('/register', 'App\Controllers\AuthController@showRegister');
$router->post('/register', 'App\Controllers\AuthController@register');
$router->post('/logout', 'App\Controllers\AuthController@logout');

// Public donation pages
$router->get('/donate', 'App\Controllers\PublicController@showDonationForm');
$router->post('/donate', 'App\Controllers\PublicController@processDonation');
$router->get('/transparency', 'App\Controllers\PublicController@showTransparency');

// ============================================
// AUTHENTICATED ROUTES
// ============================================

// Dashboard routes (role-based)
$router->get('/dashboard', 'App\Controllers\DashboardController@index', ['App\Middleware\AuthMiddleware']);

// Profile management
$router->get('/profile', 'App\Controllers\ProfileController@show', ['App\Middleware\AuthMiddleware']);
$router->post('/profile', 'App\Controllers\ProfileController@update', ['App\Middleware\AuthMiddleware']);

// ============================================
// HEALTHCARE MODULE ROUTES
// ============================================

// Monks management
$router->get('/healthcare/monks', 'App\Controllers\Healthcare\MonkController@index', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin,doctor,helper']);
$router->get('/healthcare/monks/create', 'App\Controllers\Healthcare\MonkController@create', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin,helper']);
$router->post('/healthcare/monks', 'App\Controllers\Healthcare\MonkController@store', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin,helper']);
$router->get('/healthcare/monks/{id}', 'App\Controllers\Healthcare\MonkController@show', 
    ['App\Middleware\AuthMiddleware']);
$router->get('/healthcare/monks/{id}/edit', 'App\Controllers\Healthcare\MonkController@edit', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin,helper']);
$router->put('/healthcare/monks/{id}', 'App\Controllers\Healthcare\MonkController@update', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin,helper']);

// Healthcare providers
$router->get('/healthcare/providers', 'App\Controllers\Healthcare\ProviderController@index', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin']);
$router->get('/healthcare/providers/create', 'App\Controllers\Healthcare\ProviderController@create', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin']);
$router->post('/healthcare/providers', 'App\Controllers\Healthcare\ProviderController@store', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin']);

// Appointments
$router->get('/healthcare/appointments', 'App\Controllers\Healthcare\AppointmentController@index', 
    ['App\Middleware\AuthMiddleware']);
$router->get('/healthcare/appointments/create', 'App\Controllers\Healthcare\AppointmentController@create', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin,doctor,helper']);
$router->post('/healthcare/appointments', 'App\Controllers\Healthcare\AppointmentController@store', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin,doctor,helper']);
$router->get('/healthcare/appointments/{id}', 'App\Controllers\Healthcare\AppointmentController@show', 
    ['App\Middleware\AuthMiddleware']);
$router->put('/healthcare/appointments/{id}', 'App\Controllers\Healthcare\AppointmentController@update', 
    ['App\Middleware\AuthMiddleware']);

// Medical records
$router->get('/healthcare/records', 'App\Controllers\Healthcare\MedicalRecordController@index', 
    ['App\Middleware\AuthMiddleware']);
$router->get('/healthcare/records/create', 'App\Controllers\Healthcare\MedicalRecordController@create', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:doctor']);
$router->post('/healthcare/records', 'App\Controllers\Healthcare\MedicalRecordController@store', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:doctor']);
$router->get('/healthcare/records/{id}', 'App\Controllers\Healthcare\MedicalRecordController@show', 
    ['App\Middleware\AuthMiddleware']);

// ============================================
// DONATIONS MODULE ROUTES
// ============================================

// Donation management
$router->get('/donations', 'App\Controllers\Donations\DonationController@index', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin,helper']);
$router->get('/donations/{id}', 'App\Controllers\Donations\DonationController@show', 
    ['App\Middleware\AuthMiddleware']);
$router->put('/donations/{id}/verify', 'App\Controllers\Donations\DonationController@verify', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin,helper']);

// Campaigns
$router->get('/donations/campaigns', 'App\Controllers\Donations\CampaignController@index', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin']);
$router->get('/donations/campaigns/create', 'App\Controllers\Donations\CampaignController@create', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin']);
$router->post('/donations/campaigns', 'App\Controllers\Donations\CampaignController@store', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin']);

// ============================================
// REPORTS MODULE ROUTES
// ============================================

$router->get('/reports', 'App\Controllers\Reports\ReportController@index', 
    ['App\Middleware\AuthMiddleware']);
$router->get('/reports/healthcare', 'App\Controllers\Reports\HealthcareReportController@index', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin,doctor']);
$router->get('/reports/donations', 'App\Controllers\Reports\DonationReportController@index', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin,helper']);
$router->get('/reports/financial', 'App\Controllers\Reports\FinancialReportController@index', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin']);

// ============================================
// API ROUTES (v1)
// ============================================

// Authentication API
$router->post('/api/v1/auth/login', 'App\Controllers\Api\V1\AuthController@login');
$router->post('/api/v1/auth/logout', 'App\Controllers\Api\V1\AuthController@logout', 
    ['App\Middleware\ApiAuthMiddleware']);
$router->get('/api/v1/auth/user', 'App\Controllers\Api\V1\AuthController@user', 
    ['App\Middleware\ApiAuthMiddleware']);

// Healthcare API
$router->get('/api/v1/healthcare/appointments', 'App\Controllers\Api\V1\Healthcare\AppointmentController@index', 
    ['App\Middleware\ApiAuthMiddleware']);
$router->post('/api/v1/healthcare/appointments', 'App\Controllers\Api\V1\Healthcare\AppointmentController@store', 
    ['App\Middleware\ApiAuthMiddleware']);
$router->get('/api/v1/healthcare/monks', 'App\Controllers\Api\V1\Healthcare\MonkController@index', 
    ['App\Middleware\ApiAuthMiddleware']);

// Donations API
$router->get('/api/v1/donations', 'App\Controllers\Api\V1\Donations\DonationController@index', 
    ['App\Middleware\ApiAuthMiddleware']);
$router->post('/api/v1/donations', 'App\Controllers\Api\V1\Donations\DonationController@store');

// Reports API
$router->get('/api/v1/reports/dashboard-stats', 'App\Controllers\Api\V1\Reports\DashboardController@stats', 
    ['App\Middleware\ApiAuthMiddleware']);
$router->get('/api/v1/reports/charts/{type}', 'App\Controllers\Api\V1\Reports\ChartController@generate', 
    ['App\Middleware\ApiAuthMiddleware']);

// ============================================
// ADMIN ROUTES
// ============================================

$router->get('/admin', 'App\Controllers\Admin\AdminController@index', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin']);
$router->get('/admin/users', 'App\Controllers\Admin\UserController@index', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin']);
$router->get('/admin/settings', 'App\Controllers\Admin\SettingsController@index', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin']);
$router->post('/admin/settings', 'App\Controllers\Admin\SettingsController@update', 
    ['App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin']);