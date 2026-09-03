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

// Public Legal and Content Pages
$router->get('/privacy', ['App\Controllers\PageController', 'privacy']);
$router->get('/privacy-policy', ['App\Controllers\PageController', 'privacy']);
$router->get('/privacy-policy.php', ['App\Controllers\PageController', 'privacy']);

$router->get('/terms', ['App\Controllers\PageController', 'terms']);
$router->get('/terms-of-service', ['App\Controllers\PageController', 'terms']);
$router->get('/term-services', ['App\Controllers\PageController', 'terms']);
$router->get('/term-services.php', ['App\Controllers\PageController', 'terms']);

$router->get('/faq', ['App\Controllers\PageController', 'faq']);
$router->get('/faq.php', ['App\Controllers\PageController', 'faq']);

$router->get('/about', ['App\Controllers\PageController', 'about']);
$router->get('/about-us', ['App\Controllers\PageController', 'about']);
$router->get('/about.php', ['App\Controllers\PageController', 'about']);

$router->get('/contact', ['App\Controllers\PageController', 'contact']);
$router->get('/contact-us', ['App\Controllers\PageController', 'contact']);
$router->get('/contact-us.php', ['App\Controllers\PageController', 'contact']);
$router->post('/contact', ['App\Controllers\PageController', 'submitContact']);

// Authentication and User Account System Endpoints
$router->get('/register', ['App\Controllers\AuthController', 'showRegister']);
$router->post('/register', ['App\Controllers\AuthController', 'register']);
$router->get('/verify-email', ['App\Controllers\AuthController', 'showVerifyEmail']);
$router->post('/verify-email', ['App\Controllers\AuthController', 'verifyEmail']);
$router->post('/verify-email/resend', ['App\Controllers\AuthController', 'resendVerifyEmail']);
$router->get('/login', ['App\Controllers\AuthController', 'showLogin']);
$router->post('/login', ['App\Controllers\AuthController', 'login']);
$router->post('/logout', ['App\Controllers\AuthController', 'logout']);
$router->get('/forgot-password', ['App\Controllers\AuthController', 'showForgot']);
$router->post('/forgot-password', ['App\Controllers\AuthController', 'forgot']);
$router->get('/reset-password', ['App\Controllers\AuthController', 'showReset']);
$router->post('/reset-password', ['App\Controllers\AuthController', 'reset']);

// Protected Dashboards Endpoints
$router->get('/dashboard', ['App\Controllers\DashboardController', 'index']);
$router->get('/admin', ['App\Controllers\AdminController', 'dashboard']);
$router->get('/employee', ['App\Controllers\DashboardController', 'employee']);

// Scholarship Management Endpoints
$router->get('/admin/scholarships', ['App\Controllers\ScholarshipController', 'index']);
$router->get('/admin/scholarships/create', ['App\Controllers\ScholarshipController', 'create']);
$router->post('/admin/scholarships', ['App\Controllers\ScholarshipController', 'store']);
$router->get('/admin/scholarships/{id}/edit', ['App\Controllers\ScholarshipController', 'edit']);
$router->post('/admin/scholarships/{id}/update', ['App\Controllers\ScholarshipController', 'update']);
$router->post('/admin/scholarships/{id}/delete', ['App\Controllers\ScholarshipController', 'delete']);
$router->post('/admin/scholarships/{id}/publish', ['App\Controllers\ScholarshipController', 'publish']);
$router->post('/admin/scholarships/{id}/unpublish', ['App\Controllers\ScholarshipController', 'unpublish']);
$router->post('/admin/scholarships/{id}/archive', ['App\Controllers\ScholarshipController', 'archive']);
$router->post('/admin/scholarships/{id}/duplicate', ['App\Controllers\ScholarshipController', 'duplicate']);


// Applicant Profile Management Endpoints
$router->get('/profile', ['App\Controllers\ProfileController', 'show']);
$router->get('/profile/complete', ['App\Controllers\ProfileController', 'complete']);
$router->post('/profile/complete', ['App\Controllers\ProfileController', 'completeWizard']);
$router->get('/profile/edit', ['App\Controllers\ProfileController', 'edit']);
$router->get('/notifications', ['App\Controllers\ProfileController', 'notifications']);
$router->get('/matches', ['App\Controllers\DashboardController', 'matches']);
$router->get('/settings', function() {
    header('Location: ' . url('/profile/edit#step-3'));
    exit;
});

$router->post('/profile/update', ['App\Controllers\ProfileController', 'update']);

$router->post('/profile/education/add', ['App\Controllers\ProfileController', 'addEducation']);
$router->post('/profile/education/update', ['App\Controllers\ProfileController', 'updateEducation']);
$router->post('/profile/education/delete', ['App\Controllers\ProfileController', 'deleteEducation']);

$router->post('/profile/preferences/update', ['App\Controllers\ProfileController', 'updatePreferences']);
$router->post('/profile/notifications/update', ['App\Controllers\ProfileController', 'updateNotificationSettings']);
$router->post('/profile/scholarships/reminder/toggle', ['App\Controllers\ProfileController', 'toggleScholarshipReminder']);

// AJAX Cascading Select APIs
$router->get('/api/states', ['App\Controllers\ProfileController', 'getStates']);
$router->get('/api/cities', ['App\Controllers\ProfileController', 'getCities']);
$router->get('/api/institutions', ['App\Controllers\ProfileController', 'getInstitutions']);


// Matching Engine Routes
$router->get('/api/matches', ['App\Controllers\DashboardController', 'matchesApi']);
$router->get('/admin/matches/test', ['App\Controllers\DashboardController', 'matchesDiagnostic']);

// Admin Institution Management Routes
$router->get('/admin/institutions', ['App\Controllers\DashboardController', 'adminInstitutionsIndex']);
$router->get('/admin/institutions/create', ['App\Controllers\DashboardController', 'adminInstitutionsCreate']);
$router->post('/admin/institutions', ['App\Controllers\DashboardController', 'adminInstitutionsStore']);
$router->get('/admin/institutions/{id}/edit', ['App\Controllers\DashboardController', 'adminInstitutionsEdit']);
$router->post('/admin/institutions/{id}/update', ['App\Controllers\DashboardController', 'adminInstitutionsUpdate']);
$router->post('/admin/institutions/{id}/delete', ['App\Controllers\DashboardController', 'adminInstitutionsDelete']);

// Notification Management Routes
$router->get('/admin/notifications', ['App\Controllers\NotificationController', 'index']);
$router->get('/admin/notifications/{id}', ['App\Controllers\NotificationController', 'show']);
$router->post('/admin/notifications/{id}/retry', ['App\Controllers\NotificationController', 'retry']);
$router->post('/admin/notifications/providers/test', ['App\Controllers\NotificationController', 'testProvider']);

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
$router->get('/admin/exports/download', ['App\Controllers\IntelligenceController', 'downloadExport']);

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
$router->post('/api/payments/cashmaal/ipn', ['App\Controllers\BillingController', 'cashmaalIpn']);

// Complete Professional Admin Control Center Routes
$router->get('/admin/users', ['App\Controllers\AdminController', 'usersIndex']);
$router->get('/admin/users/{id}', ['App\Controllers\AdminController', 'usersShow']);
$router->get('/admin/users/{id}/edit', ['App\Controllers\AdminController', 'usersEdit']);
$router->post('/admin/users/{id}/update', ['App\Controllers\AdminController', 'usersUpdate']);
$router->post('/admin/users/{id}/suspend', ['App\Controllers\AdminController', 'usersSuspend']);
$router->post('/admin/users/{id}/activate', ['App\Controllers\AdminController', 'usersActivate']);
$router->post('/admin/users/{id}/password', ['App\Controllers\AdminController', 'usersResetPassword']);
$router->post('/admin/users/{id}/delete', ['App\Controllers\AdminController', 'usersDelete']);

$router->get('/admin/employees', ['App\Controllers\AdminController', 'employeesIndex']);
$router->get('/admin/employees/create', ['App\Controllers\AdminController', 'employeesCreate']);
$router->post('/admin/employees', ['App\Controllers\AdminController', 'employeesStore']);
$router->post('/admin/employees/{id}/update', ['App\Controllers\AdminController', 'employeesUpdate']);
$router->get('/admin/employees/roles', ['App\Controllers\AdminController', 'rolesIndex']);
$router->post('/admin/employees/roles', ['App\Controllers\AdminController', 'rolesUpdate']);

$router->get('/admin/locations/countries', ['App\Controllers\AdminController', 'countries']);
$router->post('/admin/locations/countries', ['App\Controllers\AdminController', 'countriesStore']);
$router->get('/admin/locations/countries/{id}/edit', ['App\Controllers\AdminController', 'countriesEdit']);
$router->post('/admin/locations/countries/{id}/update', ['App\Controllers\AdminController', 'countriesUpdate']);
$router->post('/admin/locations/countries/{id}/delete', ['App\Controllers\AdminController', 'countriesDelete']);

$router->get('/admin/locations/states', ['App\Controllers\AdminController', 'states']);
$router->post('/admin/locations/states', ['App\Controllers\AdminController', 'statesStore']);
$router->get('/admin/locations/states/{id}/edit', ['App\Controllers\AdminController', 'statesEdit']);
$router->post('/admin/locations/states/{id}/update', ['App\Controllers\AdminController', 'statesUpdate']);
$router->post('/admin/locations/states/{id}/delete', ['App\Controllers\AdminController', 'statesDelete']);

$router->get('/admin/locations/cities', ['App\Controllers\AdminController', 'cities']);
$router->post('/admin/locations/cities', ['App\Controllers\AdminController', 'citiesStore']);
$router->get('/admin/locations/cities/{id}/edit', ['App\Controllers\AdminController', 'citiesEdit']);
$router->post('/admin/locations/cities/{id}/update', ['App\Controllers\AdminController', 'citiesUpdate']);
$router->post('/admin/locations/cities/{id}/delete', ['App\Controllers\AdminController', 'citiesDelete']);

$router->get('/admin/academic/fields', ['App\Controllers\AdminController', 'fields']);
$router->post('/admin/academic/fields', ['App\Controllers\AdminController', 'fieldsStore']);
$router->get('/admin/academic/fields/{id}/edit', ['App\Controllers\AdminController', 'fieldsEdit']);
$router->post('/admin/academic/fields/{id}/update', ['App\Controllers\AdminController', 'fieldsUpdate']);
$router->post('/admin/academic/fields/{id}/delete', ['App\Controllers\AdminController', 'fieldsDelete']);

$router->get('/admin/academic/degrees', ['App\Controllers\AdminController', 'degrees']);
$router->post('/admin/academic/degrees', ['App\Controllers\AdminController', 'degreesStore']);
$router->get('/admin/academic/degrees/{id}/edit', ['App\Controllers\AdminController', 'degreesEdit']);
$router->post('/admin/academic/degrees/{id}/update', ['App\Controllers\AdminController', 'degreesUpdate']);
$router->post('/admin/academic/degrees/{id}/delete', ['App\Controllers\AdminController', 'degreesDelete']);

$router->get('/admin/academic/funding', ['App\Controllers\AdminController', 'funding']);
$router->post('/admin/academic/funding', ['App\Controllers\AdminController', 'fundingStore']);
$router->get('/admin/academic/funding/{id}/edit', ['App\Controllers\AdminController', 'fundingEdit']);
$router->post('/admin/academic/funding/{id}/update', ['App\Controllers\AdminController', 'fundingUpdate']);
$router->post('/admin/academic/funding/{id}/delete', ['App\Controllers\AdminController', 'fundingDelete']);

$router->get('/admin/matching/rules', ['App\Controllers\AdminController', 'matchingRules']);
$router->get('/admin/matching/stats', ['App\Controllers\AdminController', 'matchingStats']);

$router->get('/admin/settings', ['App\Controllers\AdminController', 'settings']);
$router->post('/admin/settings', ['App\Controllers\AdminController', 'settingsUpdate']);
$router->post('/admin/settings/wacrm/test-connection', ['App\Controllers\AdminController', 'testWacrmConnection']);

// Admin Referral Management Routes
$router->get('/admin/referrals', ['App\Controllers\AdminController', 'referralsIndex']);
$router->post('/admin/referrals', ['App\Controllers\AdminController', 'referralsStore']);
$router->post('/admin/referrals/{id}/delete', ['App\Controllers\AdminController', 'referralsDelete']);
$router->get('/admin/referrals/data', ['App\Controllers\AdminController', 'referralsData']);

// Partner Dashboard Routes
$router->get('/referral-partner', ['App\Controllers\ReferralPartnerController', 'index']);
$router->get('/referral-partner/students', ['App\Controllers\ReferralPartnerController', 'students']);
$router->get('/referral-partner/profile', ['App\Controllers\ReferralPartnerController', 'profile']);
$router->post('/referral-partner/profile', ['App\Controllers\ReferralPartnerController', 'profileUpdate']);

$router->get('/admin/audit-logs', ['App\Controllers\AdminController', 'auditLogs']);
$router->get('/admin/profile', ['App\Controllers\AdminController', 'profile']);
$router->post('/admin/profile', ['App\Controllers\AdminController', 'profileUpdate']);

$router->get('/admin/subscriptions', ['App\Controllers\AdminController', 'subscriptionsIndex']);
$router->get('/admin/payments', ['App\Controllers\AdminController', 'paymentsIndex']);

// Server-Side DataTables JSON endpoints
$router->get('/admin/users/data', ['App\Controllers\AdminController', 'usersData']);
$router->get('/admin/employees/data', ['App\Controllers\AdminController', 'employeesData']);
$router->get('/admin/scholarships/data', ['App\Controllers\ScholarshipController', 'scholarshipsData']);
$router->get('/admin/institutions/data', ['App\Controllers\DashboardController', 'institutionsData']);
$router->get('/admin/applications/data', ['App\Controllers\ApplicationController', 'applicationsData']);
$router->get('/admin/documents/data', ['App\Controllers\DocumentController', 'documentsData']);
$router->get('/admin/locations/countries/data', ['App\Controllers\AdminController', 'countriesData']);
$router->get('/admin/locations/states/data', ['App\Controllers\AdminController', 'statesData']);
$router->get('/admin/locations/cities/data', ['App\Controllers\AdminController', 'citiesData']);
$router->get('/admin/academic/fields/data', ['App\Controllers\AdminController', 'fieldsData']);
$router->get('/admin/academic/degrees/data', ['App\Controllers\AdminController', 'degreesData']);
$router->get('/admin/academic/funding/data', ['App\Controllers\AdminController', 'fundingData']);
$router->get('/admin/payments/data', ['App\Controllers\AdminController', 'paymentsData']);
$router->get('/admin/subscriptions/data', ['App\Controllers\AdminController', 'subscriptionsData']);
$router->get('/admin/audit-logs/data', ['App\Controllers\AdminController', 'auditLogsData']);

