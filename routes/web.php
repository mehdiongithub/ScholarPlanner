<?php

use App\Controllers\HomeController;
use App\Controllers\PlaceholderController;

/** @var \App\Services\Router $router */

$router->get('/', [HomeController::class, 'index']);

// Robots.txt & Sitemap XML Routes
$router->get('/robots.txt', ['App\Controllers\SEOController', 'robots']);
$router->get('/sitemap.xml', ['App\Controllers\SEOController', 'sitemap']);

// Step 1 placeholders and redirect routes
$router->get('/scholarships', ['App\Controllers\ScholarshipController', 'publicList']);
$router->get('/scholarships/country/{slug}', ['App\Controllers\ScholarshipController', 'publicListByCountry']);
$router->get('/scholarships/field/{slug}', ['App\Controllers\ScholarshipController', 'publicListByField']);
$router->get('/scholarships/degree/{slug}', ['App\Controllers\ScholarshipController', 'publicListByDegree']);
$router->get('/scholarships/{slug}', ['App\Controllers\ScholarshipController', 'publicDetail']);

$router->get('/how-it-works', [PlaceholderController::class, 'howItWorks']);
$router->get('/features', [PlaceholderController::class, 'features']);
$router->get('/pricing', [PlaceholderController::class, 'pricing']);
$router->get('/about', [PlaceholderController::class, 'about']);
$router->get('/faq', [PlaceholderController::class, 'faq']);
$router->get('/contact', [PlaceholderController::class, 'contact']);
// Authentication and User Account System Endpoints
$router->get('/register', ['App\Controllers\AuthController', 'showRegister']);
$router->post('/register', ['App\Controllers\AuthController', 'register']);
$router->get('/login', ['App\Controllers\AuthController', 'showLogin']);
$router->post('/login', ['App\Controllers\AuthController', 'login']);
$router->post('/logout', ['App\Controllers\AuthController', 'logout']);
$router->get('/forgot-password', ['App\Controllers\AuthController', 'showForgot']);
$router->post('/forgot-password', ['App\Controllers\AuthController', 'forgot']);
$router->get('/reset-password', ['App\Controllers\AuthController', 'showReset']);
$router->post('/reset-password', ['App\Controllers\AuthController', 'reset']);

// Protected Dashboards Endpoints
$router->get('/dashboard', ['App\Controllers\DashboardController', 'index']);
$router->get('/admin', ['App\Controllers\DashboardController', 'admin']);
$router->get('/employee', ['App\Controllers\DashboardController', 'employee']);

// Scholarship Management Endpoints
$router->get('/admin/scholarships', ['App\Controllers\ScholarshipController', 'index']);
$router->get('/admin/scholarships/create', ['App\Controllers\ScholarshipController', 'create']);
$router->post('/admin/scholarships', ['App\Controllers\ScholarshipController', 'store']);
$router->get('/admin/scholarships/{id}/edit', ['App\Controllers\ScholarshipController', 'edit']);
$router->post('/admin/scholarships/{id}/update', ['App\Controllers\ScholarshipController', 'update']);
$router->post('/admin/scholarships/{id}/delete', ['App\Controllers\ScholarshipController', 'delete']);
$router->post('/admin/scholarships/{id}/publish', ['App\Controllers\ScholarshipController', 'publish']);
$router->post('/admin/scholarships/{id}/archive', ['App\Controllers\ScholarshipController', 'archive']);

// Applicant Profile Management Endpoints
$router->get('/profile', ['App\Controllers\ProfileController', 'show']);
$router->get('/profile/edit', ['App\Controllers\ProfileController', 'edit']);
$router->post('/profile/update', ['App\Controllers\ProfileController', 'update']);

$router->post('/profile/education/add', ['App\Controllers\ProfileController', 'addEducation']);
$router->post('/profile/education/update', ['App\Controllers\ProfileController', 'updateEducation']);
$router->post('/profile/education/delete', ['App\Controllers\ProfileController', 'deleteEducation']);

$router->post('/profile/preferences/update', ['App\Controllers\ProfileController', 'updatePreferences']);

// AJAX Cascading Select APIs
$router->get('/api/states', ['App\Controllers\ProfileController', 'getStates']);
$router->get('/api/cities', ['App\Controllers\ProfileController', 'getCities']);

// Matching Engine Routes
$router->get('/api/matches', ['App\Controllers\DashboardController', 'matchesApi']);
$router->get('/admin/matches/test', ['App\Controllers\DashboardController', 'matchesDiagnostic']);

// Notification Management Routes
$router->get('/admin/notifications', ['App\Controllers\NotificationController', 'index']);
$router->get('/admin/notifications/{id}', ['App\Controllers\NotificationController', 'show']);
$router->post('/admin/notifications/{id}/retry', ['App\Controllers\NotificationController', 'retry']);

// Document Management Routes
$router->get('/documents', ['App\Controllers\DocumentController', 'index']);
$router->post('/documents/upload', ['App\Controllers\DocumentController', 'upload']);
$router->post('/documents/{id}/delete', ['App\Controllers\DocumentController', 'delete']);
$router->get('/documents/{id}/download', ['App\Controllers\DocumentController', 'download']);

// Admin/Employee Document Review Routes
$router->get('/admin/documents', ['App\Controllers\DocumentController', 'adminIndex']);
$router->get('/admin/documents/{id}', ['App\Controllers\DocumentController', 'adminShow']);
$router->post('/admin/documents/{id}/approve', ['App\Controllers\DocumentController', 'approve']);
$router->post('/admin/documents/{id}/reject', ['App\Controllers\DocumentController', 'reject']);
$router->get('/admin/documents/{id}/download', ['App\Controllers\DocumentController', 'adminDownload']);

// Application Tracker Routes
$router->get('/applications', ['App\Controllers\ApplicationController', 'index']);
$router->post('/applications', ['App\Controllers\ApplicationController', 'store']);
$router->get('/applications/{id}', ['App\Controllers\ApplicationController', 'show']);
$router->post('/applications/{id}/update', ['App\Controllers\ApplicationController', 'update']);
$router->post('/applications/{id}/delete', ['App\Controllers\ApplicationController', 'delete']);

// Admin/Employee Application Review Routes
$router->get('/admin/applications', ['App\Controllers\ApplicationController', 'adminIndex']);
$router->get('/admin/applications/{id}', ['App\Controllers\ApplicationController', 'adminShow']);
$router->post('/admin/applications/{id}/status', ['App\Controllers\ApplicationController', 'adminUpdateStatus']);

// Saved Scholarships Routes
$router->post('/scholarships/{id}/save', ['App\Controllers\ScholarshipController', 'save']);
$router->post('/scholarships/{id}/unsave', ['App\Controllers\ScholarshipController', 'unsave']);
$router->get('/saved-scholarships', ['App\Controllers\ScholarshipController', 'savedList']);

// Scholarship Comparison Routes
$router->post('/scholarships/{id}/compare/add', ['App\Controllers\ScholarshipController', 'addToCompare']);
$router->post('/scholarships/{id}/compare/remove', ['App\Controllers\ScholarshipController', 'removeFromCompare']);
$router->get('/scholarships/compare', ['App\Controllers\ScholarshipController', 'compare']);

// Step 12 Admin Intelligence & Platform Operations Routes
$router->get('/admin/intelligence', ['App\Controllers\IntelligenceController', 'index']);
$router->post('/admin/intelligence/quality/bulk', ['App\Controllers\IntelligenceController', 'bulkAction']);

// Step 14 Billing, Subscription & Monetization Routes
$router->get('/pricing', ['App\Controllers\BillingController', 'pricing']);
$router->get('/billing', ['App\Controllers\BillingController', 'billing']);
$router->get('/checkout', ['App\Controllers\BillingController', 'checkout']);
$router->post('/checkout', ['App\Controllers\BillingController', 'processCheckout']);
$router->get('/checkout/callback', ['App\Controllers\BillingController', 'callback']);
$router->post('/checkout/callback', ['App\Controllers\BillingController', 'callback']); // support POST callbacks from gateway redirects
$router->post('/checkout/cancel', ['App\Controllers\BillingController', 'cancel']);
$router->post('/admin/billing/refund', ['App\Controllers\BillingController', 'refund']);
$router->get('/checkout/mock-screen', ['App\Controllers\BillingController', 'mockScreen']);
$router->get('/checkout/redirect', ['App\Controllers\BillingController', 'redirectRedirect']);
$router->post('/api/payments/webhook', ['App\Controllers\BillingController', 'webhook']);
