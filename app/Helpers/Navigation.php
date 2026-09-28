<?php

namespace App\Helpers;

use App\Services\Auth;

class Navigation {
    /**
     * Check if a navigation item is active based on request URI.
     */
    public static function isActive(array $item): bool {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $basePath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = ($basePath === '/' || $basePath === '\\') ? '' : rtrim($basePath, '/');
        
        $activePrefix = $item['active_prefix'] ?? ($item['url'] ?? '');
        $prefix = $basePath . $activePrefix;
        $exclude = isset($item['exclude_prefix']) ? $basePath . $item['exclude_prefix'] : null;
        
        if ($exclude !== null && strpos($uri, $exclude) === 0) {
            return false;
        }
        
        if (!empty($item['exact'])) {
            return $uri === $prefix || $uri === $prefix . '/';
        }
        
        return strpos($uri, $prefix) === 0;
    }

    /**
     * Get the sidebar menu structure for a given role/context.
     */
    public static function getSidebarMenu(string $role): array {
        switch ($role) {
            case 'admin':
            case 'employee':
                return self::getAdminSidebar();
            case 'partner':
                return self::getPartnerSidebar();
            case 'visitor': // Visitor is the student role in Auth session
            default:
                return self::getStudentSidebar();
        }
    }

    /**
     * Get admin / employee sidebar config filtered by user permissions.
     */
    private static function getAdminSidebar(): array {
        $rawItems = [
            [
                'type' => 'link',
                'label' => 'Dashboard',
                'icon' => 'layout-dashboard',
                'url' => '/admin',
                'active_prefix' => '/admin',
                'exact' => true
            ],
            [
                'type' => 'section',
                'label' => 'USER MANAGEMENT'
            ],
            [
                'type' => 'link',
                'label' => 'Students / Visitors',
                'icon' => 'users',
                'url' => '/admin/users',
                'active_prefix' => '/admin/users',
                'permission' => 'users.view'
            ],
            [
                'type' => 'link',
                'label' => 'Active User Plans',
                'icon' => 'sparkles',
                'url' => '/admin/manual-subscriptions',
                'active_prefix' => '/admin/manual-subscriptions',
                'permission' => 'users.view'
            ],
            [
                'type' => 'link',
                'label' => 'Alert Timers',
                'icon' => 'timer',
                'url' => '/admin/alert-timers',
                'active_prefix' => '/admin/alert-timers',
                'permission' => 'settings.view'
            ],
            [
                'type' => 'link',
                'label' => 'Staff & Employees',
                'icon' => 'shield-check',
                'url' => '/admin/employees',
                'active_prefix' => '/admin/employees',
                'exclude_prefix' => '/admin/employees/roles',
                'permission' => 'employees.manage'
            ],
            [
                'type' => 'link',
                'label' => 'Roles & Permissions',
                'icon' => 'lock',
                'url' => '/admin/employees/roles',
                'active_prefix' => '/admin/employees/roles',
                'permission' => 'roles.manage'
            ],
            [
                'type' => 'section',
                'label' => 'SCHOLARSHIPS & PLATFORM'
            ],
            [
                'type' => 'link',
                'label' => 'Scholarships',
                'icon' => 'award',
                'url' => '/admin/scholarships',
                'active_prefix' => '/admin/scholarships',
                'permission' => 'scholarships.view'
            ],
            [
                'type' => 'link',
                'label' => 'Institutions',
                'icon' => 'landmark',
                'url' => '/admin/institutions',
                'active_prefix' => '/admin/institutions',
                'role' => 'admin'
            ],
            [
                'type' => 'link',
                'label' => 'Applications',
                'icon' => 'file-text',
                'url' => '/admin/applications',
                'active_prefix' => '/admin/applications',
                'permission' => 'applications.view'
            ],
            [
                'type' => 'link',
                'label' => 'Uploaded Documents',
                'icon' => 'files',
                'url' => '/admin/documents',
                'active_prefix' => '/admin/documents',
                'permission' => 'documents.view'
            ],
            [
                'type' => 'section',
                'label' => 'ACADEMIC & LOCATIONS'
            ],
            [
                'type' => 'link',
                'label' => 'Countries',
                'icon' => 'globe',
                'url' => '/admin/locations/countries',
                'active_prefix' => '/admin/locations/countries',
                'permission' => 'settings.view'
            ],
            [
                'type' => 'link',
                'label' => 'States / Provinces',
                'icon' => 'map-pin',
                'url' => '/admin/locations/states',
                'active_prefix' => '/admin/locations/states',
                'permission' => 'settings.view'
            ],
            [
                'type' => 'link',
                'label' => 'Cities',
                'icon' => 'navigation',
                'url' => '/admin/locations/cities',
                'active_prefix' => '/admin/locations/cities',
                'permission' => 'settings.view'
            ],
            [
                'type' => 'link',
                'label' => 'Fields of Study',
                'icon' => 'book-open',
                'url' => '/admin/academic/fields',
                'active_prefix' => '/admin/academic/fields',
                'permission' => 'settings.view'
            ],
            [
                'type' => 'link',
                'label' => 'Degree Levels',
                'icon' => 'award',
                'url' => '/admin/academic/degrees',
                'active_prefix' => '/admin/academic/degrees',
                'permission' => 'settings.view'
            ],
            [
                'type' => 'link',
                'label' => 'Funding Types',
                'icon' => 'banknote',
                'url' => '/admin/academic/funding',
                'active_prefix' => '/admin/academic/funding',
                'permission' => 'settings.view'
            ],
            [
                'type' => 'section',
                'label' => 'MATCHING'
            ],
            [
                'type' => 'link',
                'label' => 'Matching Rules',
                'icon' => 'git-branch',
                'url' => '/admin/matching/rules',
                'active_prefix' => '/admin/matching/rules',
                'permission' => 'reports.view'
            ],
            [
                'type' => 'link',
                'label' => 'Matching Stats',
                'icon' => 'chart-bar',
                'url' => '/admin/matching/stats',
                'active_prefix' => '/admin/matching/stats',
                'permission' => 'reports.view'
            ],
            [
                'type' => 'link',
                'label' => 'Intelligence',
                'icon' => 'brain',
                'url' => '/admin/intelligence',
                'active_prefix' => '/admin/intelligence',
                'permission' => ['reports.view', 'scholarships.verify']
            ],
            [
                'type' => 'section',
                'label' => 'COMMUNICATION'
            ],
            [
                'type' => 'link',
                'label' => 'Notifications',
                'icon' => 'bell',
                'url' => '/admin/notifications',
                'active_prefix' => '/admin/notifications',
                'permission' => 'notifications.view'
            ],
            [
                'type' => 'section',
                'label' => 'BILLING'
            ],
            [
                'type' => 'link',
                'label' => 'Subscriptions',
                'icon' => 'refresh-cw',
                'url' => '/admin/subscriptions',
                'active_prefix' => '/admin/subscriptions',
                'permission' => 'subscriptions.view'
            ],
            [
                'type' => 'link',
                'label' => 'Subscription Plans',
                'icon' => 'package',
                'url' => '/admin/plans',
                'active_prefix' => '/admin/plans',
                'permission' => 'subscriptions.view'
            ],
            [
                'type' => 'link',
                'label' => 'Payments',
                'icon' => 'dollar-sign',
                'url' => '/admin/payments',
                'active_prefix' => '/admin/payments',
                'permission' => 'payments.view'
            ],
            [
                'type' => 'section',
                'label' => 'REFERRALS'
            ],
            [
                'type' => 'link',
                'label' => 'Referrals',
                'icon' => 'share-2',
                'url' => '/admin/referrals',
                'active_prefix' => '/admin/referrals',
                'permission' => 'referrals.view'
            ],
            [
                'type' => 'section',
                'label' => 'SYSTEM'
            ],
            [
                'type' => 'link',
                'label' => 'Settings',
                'icon' => 'settings',
                'url' => '/admin/settings',
                'active_prefix' => '/admin/settings',
                'permission' => 'settings.view'
            ],
            [
                'type' => 'link',
                'label' => 'Audit Logs',
                'icon' => 'history',
                'url' => '/admin/audit-logs',
                'active_prefix' => '/admin/audit-logs',
                'permission' => 'audit_logs.view'
            ],
            [
                'type' => 'link',
                'label' => 'Staff Profile',
                'icon' => 'user',
                'url' => '/admin/profile',
                'active_prefix' => '/admin/profile'
            ]
        ];

        return self::filterMenuForUser($rawItems);
    }

    /**
     * Filter menu items according to role and permissions.
     * Automatically prunes empty sections where user lacks permissions for all contained links.
     */
    public static function filterMenuForUser(array $items): array {
        $filtered = [];
        $currentSection = null;
        $sectionItems = [];

        foreach ($items as $item) {
            if ($item['type'] === 'section') {
                if ($currentSection !== null && !empty($sectionItems)) {
                    $filtered[] = $currentSection;
                    foreach ($sectionItems as $si) {
                        $filtered[] = $si;
                    }
                }
                $currentSection = $item;
                $sectionItems = [];
                continue;
            }

            if (self::canAccessItem($item)) {
                if ($currentSection !== null) {
                    $sectionItems[] = $item;
                } else {
                    $filtered[] = $item;
                }
            }
        }

        if ($currentSection !== null && !empty($sectionItems)) {
            $filtered[] = $currentSection;
            foreach ($sectionItems as $si) {
                $filtered[] = $si;
            }
        }

        return $filtered;
    }

    /**
     * Determine if current authenticated user has access to a navigation item.
     */
    public static function canAccessItem(array $item): bool {
        // Enforce role constraints if defined
        if (!empty($item['role'])) {
            $roles = is_array($item['role']) ? $item['role'] : [$item['role']];
            if (!Auth::hasRole($roles)) {
                return false;
            }
        }

        // Enforce permission constraints if defined
        if (!empty($item['permission'])) {
            $user = Auth::currentUser();
            // System admin role always has access
            if ($user && ($user['role_name'] ?? '') === 'admin') {
                return true;
            }

            $permissions = is_array($item['permission']) ? $item['permission'] : [$item['permission']];
            $hasAny = false;
            foreach ($permissions as $perm) {
                if (Auth::hasPermission($perm)) {
                    $hasAny = true;
                    break;
                }
            }
            if (!$hasAny) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get referral partner sidebar config.
     */
    private static function getPartnerSidebar(): array {
        return [
            [
                'type' => 'link',
                'label' => 'Dashboard',
                'icon' => 'layout-dashboard',
                'url' => '/referral-partner',
                'active_prefix' => '/referral-partner',
                'exact' => true
            ],
            [
                'type' => 'link',
                'label' => 'Referred Students',
                'icon' => 'users',
                'url' => '/referral-partner/students',
                'active_prefix' => '/referral-partner/students'
            ],
            [
                'type' => 'link',
                'label' => 'My Referral Link',
                'icon' => 'share-2',
                'url' => '/referral-partner#sharing-link',
                'active_prefix' => '/referral-partner#sharing-link'
            ],
            [
                'type' => 'link',
                'label' => 'Profile / Password',
                'icon' => 'user',
                'url' => '/referral-partner/profile',
                'active_prefix' => '/referral-partner/profile'
            ]
        ];
    }

    /**
     * Get student sidebar config.
     */
    private static function getStudentSidebar(): array {
        return [
            [
                'type' => 'section',
                'label' => 'Main Menu'
            ],
            [
                'type' => 'link',
                'label' => 'Dashboard',
                'icon' => 'layout-dashboard',
                'url' => '/dashboard',
                'active_prefix' => '/dashboard',
                'exact' => true
            ],
            [
                'type' => 'link',
                'label' => 'My Profile',
                'icon' => 'user',
                'url' => '/profile',
                'active_prefix' => '/profile',
                'exact' => true
            ],
            [
                'type' => 'link',
                'label' => 'Scholarship Matches',
                'icon' => 'target',
                'url' => '/matches',
                'active_prefix' => '/matches'
            ],
            [
                'type' => 'link',
                'label' => 'Saved Scholarships',
                'icon' => 'bookmark',
                'url' => '/saved-scholarships',
                'active_prefix' => '/saved-scholarships'
            ],
            [
                'type' => 'link',
                'label' => 'Notifications',
                'icon' => 'bell',
                'url' => '/notifications',
                'active_prefix' => '/notifications'
            ],
            /* Subscription tab commented out / hidden for now
            [
                'type' => 'link',
                'label' => 'Subscription',
                'icon' => 'credit-card',
                'url' => '/billing',
                'active_prefix' => '/billing'
            ],
            */
            [
                'type' => 'link',
                'label' => 'Settings',
                'icon' => 'settings',
                'url' => '/settings',
                'active_prefix' => '/settings'
            ]
        ];
    }
}
