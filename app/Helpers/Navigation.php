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
     * Get admin / employee sidebar config.
     */
    private static function getAdminSidebar(): array {
        return [
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
                'active_prefix' => '/admin/users'
            ],
            [
                'type' => 'link',
                'label' => 'Staff & Employees',
                'icon' => 'shield-check',
                'url' => '/admin/employees',
                'active_prefix' => '/admin/employees',
                'exclude_prefix' => '/admin/employees/roles'
            ],
            [
                'type' => 'link',
                'label' => 'Roles & Permissions',
                'icon' => 'lock',
                'url' => '/admin/employees/roles',
                'active_prefix' => '/admin/employees/roles'
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
                'active_prefix' => '/admin/scholarships'
            ],
            [
                'type' => 'link',
                'label' => 'Institutions',
                'icon' => 'landmark',
                'url' => '/admin/institutions',
                'active_prefix' => '/admin/institutions'
            ],
            [
                'type' => 'link',
                'label' => 'Applications',
                'icon' => 'file-text',
                'url' => '/admin/applications',
                'active_prefix' => '/admin/applications'
            ],
            [
                'type' => 'link',
                'label' => 'Uploaded Documents',
                'icon' => 'files',
                'url' => '/admin/documents',
                'active_prefix' => '/admin/documents'
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
                'active_prefix' => '/admin/locations/countries'
            ],
            [
                'type' => 'link',
                'label' => 'States / Provinces',
                'icon' => 'map-pin',
                'url' => '/admin/locations/states',
                'active_prefix' => '/admin/locations/states'
            ],
            [
                'type' => 'link',
                'label' => 'Cities',
                'icon' => 'navigation',
                'url' => '/admin/locations/cities',
                'active_prefix' => '/admin/locations/cities'
            ],
            [
                'type' => 'link',
                'label' => 'Fields of Study',
                'icon' => 'book-open',
                'url' => '/admin/academic/fields',
                'active_prefix' => '/admin/academic/fields'
            ],
            [
                'type' => 'link',
                'label' => 'Degree Levels',
                'icon' => 'award',
                'url' => '/admin/academic/degrees',
                'active_prefix' => '/admin/academic/degrees'
            ],
            [
                'type' => 'link',
                'label' => 'Funding Types',
                'icon' => 'banknote',
                'url' => '/admin/academic/funding',
                'active_prefix' => '/admin/academic/funding'
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
                'active_prefix' => '/admin/matching/rules'
            ],
            [
                'type' => 'link',
                'label' => 'Matching Stats',
                'icon' => 'chart-bar',
                'url' => '/admin/matching/stats',
                'active_prefix' => '/admin/matching/stats'
            ],
            [
                'type' => 'link',
                'label' => 'Intelligence',
                'icon' => 'brain',
                'url' => '/admin/intelligence',
                'active_prefix' => '/admin/intelligence'
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
                'active_prefix' => '/admin/notifications'
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
                'active_prefix' => '/admin/subscriptions'
            ],
            [
                'type' => 'link',
                'label' => 'Payments',
                'icon' => 'dollar-sign',
                'url' => '/admin/payments',
                'active_prefix' => '/admin/payments'
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
                'active_prefix' => '/admin/settings'
            ],
            [
                'type' => 'link',
                'label' => 'Audit Logs',
                'icon' => 'history',
                'url' => '/admin/audit-logs',
                'active_prefix' => '/admin/audit-logs'
            ],
            [
                'type' => 'link',
                'label' => 'Admin Profile',
                'icon' => 'user',
                'url' => '/admin/profile',
                'active_prefix' => '/admin/profile'
            ]
        ];
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
            [
                'type' => 'link',
                'label' => 'Subscription',
                'icon' => 'credit-card',
                'url' => '/billing',
                'active_prefix' => '/billing'
            ],
            [
                'type' => 'link',
                'label' => 'Settings',
                'icon' => 'settings',
                'url' => '/profile/edit#step-1',
                'active_prefix' => '/profile/edit#step-1'
            ]
        ];
    }
}
