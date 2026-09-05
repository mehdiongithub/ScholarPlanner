<?php

namespace App\Services\WhatsApp;

class ScholarshipMessageFormatter {
    public const SEPARATOR = '━━━━━━━━━━━━━━━━━━';

    /**
     * Sanitize and normalize string for WhatsApp message display.
     * Strips HTML tags, decodes HTML entities, normalizes whitespace, and truncates safely.
     */
    public static function sanitizeText(?string $text, int $maxLength = 250): string {
        if ($text === null || trim($text) === '') {
            return '';
        }

        // 1. Strip all HTML tags
        $clean = strip_tags($text);

        // 2. Decode HTML entities
        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 3. Remove non-printable control characters (except newline)
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $clean);

        // 4. Normalize spaces and linebreaks
        $clean = preg_replace('/[^\S\r\n]+/', ' ', $clean);
        $clean = preg_replace('/\n{3,}/', "\n\n", $clean);
        $clean = trim($clean);

        // 5. Safe truncation at word boundary
        if (mb_strlen($clean, 'UTF-8') > $maxLength) {
            $truncated = mb_substr($clean, 0, $maxLength, 'UTF-8');
            $lastSpace = mb_strrpos($truncated, ' ', 0, 'UTF-8');
            if ($lastSpace !== false && $lastSpace > ($maxLength * 0.7)) {
                $truncated = mb_substr($truncated, 0, $lastSpace, 'UTF-8');
            }
            $clean = rtrim($truncated, " \t\n\r\0\x0B.,;:-") . '...';
        }

        return $clean;
    }

    /**
     * Format a deadline string into a user-friendly format.
     */
    public static function formatDeadline(?string $deadline): string {
        if (empty($deadline) || strtolower($deadline) === 'open/rolling' || strtolower($deadline) === 'rolling') {
            return 'Open / Rolling';
        }

        $timestamp = strtotime($deadline);
        if (!$timestamp) {
            return self::sanitizeText($deadline, 50);
        }

        $deadlineDate = date('Y-m-d', $timestamp);
        $todayDate = date('Y-m-d');
        $tomorrowDate = date('Y-m-d', strtotime('+1 day'));

        if ($deadlineDate === $todayDate) {
            return 'Today';
        }

        if ($deadlineDate === $tomorrowDate) {
            return 'Tomorrow';
        }

        return date('j F Y', $timestamp);
    }

    /**
     * Format a single scholarship notification into the professional layout.
     */
    public static function formatSingleMessage(array $data, string $type = 'NEW_MATCH'): string {
        $title = self::sanitizeText($data['title'] ?? ($data['scholarship_title'] ?? 'Scholarship Opportunity'), 120);
        $desc = self::sanitizeText($data['short_description'] ?? ($data['description'] ?? ($data['summary'] ?? '')), 250);
        if ($desc === '') {
            $desc = 'A verified scholarship opportunity matching your academic profile.';
        }

        $provider = self::sanitizeText($data['provider_name'] ?? ($data['provider'] ?? ''), 80);
        $studyLevel = self::sanitizeText($data['study_level'] ?? ($data['degree'] ?? ''), 60);
        $country = self::sanitizeText($data['country_name'] ?? ($data['country'] ?? ''), 60);
        $funding = self::sanitizeText($data['funding_type'] ?? ($data['funding'] ?? ''), 60);
        $rawDeadline = $data['application_deadline'] ?? ($data['deadline'] ?? '');
        $deadlineText = self::formatDeadline($rawDeadline);
        $daysLeft = $data['days_left'] ?? null;

        $applyUrl = trim((string)($data['official_application_url'] ?? ($data['official_apply_url'] ?? ($data['official_website'] ?? ($data['detail_url'] ?? '')))));
        if (!filter_var($applyUrl, FILTER_VALIDATE_URL)) {
            $slug = $data['slug'] ?? '';
            $applyUrl = !empty($slug) ? url('/scholarships/' . $slug) : url('/dashboard');
        }

        // Heading selection
        if ($type === 'SCHOLARSHIP_DEADLINE_TODAY') {
            $heading = "🚨 SCHOLARSHIP DEADLINE TODAY";
        } elseif ($type === 'SCHOLARSHIP_DEADLINE_SOON' || $type === 'DEADLINE_REMINDER') {
            $heading = "⏰ SCHOLARSHIP DEADLINE APPROACHING";
        } else {
            $heading = "🎓 SCHOLARSHIP OPPORTUNITY";
        }

        $lines = [];
        $lines[] = $heading;
        $lines[] = $title;
        $lines[] = $desc;
        $lines[] = self::SEPARATOR;

        if ($provider !== '') {
            $lines[] = "🏛 Provider:\n" . $provider;
        }
        if ($studyLevel !== '') {
            $lines[] = "📚 Study Level:\n" . $studyLevel;
        }
        if ($country !== '') {
            $lines[] = "🌍 Country:\n" . $country;
        }
        if ($funding !== '') {
            $lines[] = "💰 Funding:\n" . $funding;
        }

        if ($type === 'SCHOLARSHIP_DEADLINE_TODAY' || $deadlineText === 'Today') {
            $lines[] = "⚠️ Deadline:\nToday";
        } elseif ($type === 'SCHOLARSHIP_DEADLINE_SOON' || $type === 'DEADLINE_REMINDER') {
            $lines[] = "⚠️ Deadline:\n" . $deadlineText;
            if ($daysLeft !== null && (int)$daysLeft > 0) {
                $days = (int)$daysLeft;
                $lines[] = "⏳ Time remaining:\n" . $days . " day" . ($days === 1 ? '' : 's');
            }
        } elseif ($deadlineText !== '') {
            $lines[] = "📅 Deadline:\n" . $deadlineText;
        }

        if ($applyUrl !== '') {
            $lines[] = "🔗 Apply:\n" . $applyUrl;
        }

        $lines[] = self::SEPARATOR;
        $lines[] = "ScholarPlanner\nYour scholarship discovery assistant.";

        return implode("\n", $lines);
    }

    /**
     * Format multiple scholarships into one professional combined daily digest message.
     */
    public static function formatMultipleMessage(array $matches, int $count): string {
        $lines = [];
        $lines[] = "🎓 SCHOLARSHIP OPPORTUNITIES";
        $lines[] = "You have {$count} new scholarship opportunities matching your profile.";
        $lines[] = self::SEPARATOR;

        $maxMatches = min(count($matches), 5); // Display top matches up to safe length limit
        for ($i = 0; $i < $maxMatches; $i++) {
            $m = $matches[$i];
            $title = self::sanitizeText($m['title'] ?? 'Scholarship Opportunity', 100);
            $desc = self::sanitizeText($m['short_description'] ?? ($m['description'] ?? ($m['summary'] ?? '')), 180);
            if ($desc === '') {
                $desc = 'Matched opportunity based on your profile preferences.';
            }
            $deadline = self::formatDeadline($m['application_deadline'] ?? ($m['deadline'] ?? ''));
            $country = self::sanitizeText($m['country_name'] ?? ($m['country'] ?? ''), 50);
            $funding = self::sanitizeText($m['funding_type'] ?? ($m['funding'] ?? ''), 50);

            $applyUrl = trim((string)($m['official_application_url'] ?? ($m['official_apply_url'] ?? ($m['official_website'] ?? ($m['detail_url'] ?? '')))));
            if (!filter_var($applyUrl, FILTER_VALIDATE_URL)) {
                $slug = $m['slug'] ?? '';
                $applyUrl = !empty($slug) ? url('/scholarships/' . $slug) : url('/dashboard');
            }

            $lines[] = $title;
            $lines[] = $desc;
            if ($deadline !== '') {
                $lines[] = "📅 Deadline: " . $deadline;
            }
            if ($country !== '') {
                $lines[] = "🌍 Country: " . $country;
            }
            if ($funding !== '') {
                $lines[] = "💰 Funding: " . $funding;
            }
            if ($applyUrl !== '') {
                $lines[] = "🔗 Apply:\n" . $applyUrl;
            }
            $lines[] = self::SEPARATOR;
        }

        if ($count > $maxMatches) {
            $extra = $count - $maxMatches;
            $lines[] = "➕ And {$extra} more matching opportunities on your dashboard.";
            $lines[] = "🔗 View all: " . url('/dashboard');
            $lines[] = self::SEPARATOR;
        }

        $lines[] = "ScholarPlanner\nYour scholarship discovery assistant.";

        return implode("\n", $lines);
    }

    /**
     * Build template parameters for Meta WhatsApp Cloud API template mapping.
     */
    public static function buildTemplateParams(string $type, array $payload): array {
        if ($type === 'PAYMENT_CONFIRMATION' || $type === 'PAYMENT_SUCCESS' || $type === 'SUBSCRIPTION_CONFIRMATION') {
            return [
                self::sanitizeText($payload['user_name'] ?? 'Student', 60),
                self::sanitizeText($payload['plan_name'] ?? 'Premium Plan', 60),
                self::sanitizeText((string)($payload['amount'] ?? ''), 20),
                self::sanitizeText($payload['currency'] ?? 'PKR', 10),
                self::sanitizeText($payload['reference'] ?? '', 50)
            ];
        }

        // Multiple batched scholarship matches:
        if (!empty($payload['matches']) && is_array($payload['matches']) && count($payload['matches']) > 1) {
            $count = count($payload['matches']);
            $first = $payload['matches'][0];
            $title = self::sanitizeText($first['title'] ?? 'Scholarship Opportunities', 100);
            $desc = "You have {$count} new scholarship opportunities matching your profile.";
            $provider = self::sanitizeText($first['provider_name'] ?? ($first['provider'] ?? 'ScholarPlanner'), 80);
            $studyLevel = self::sanitizeText($first['study_level'] ?? ($first['degree'] ?? 'Various Levels'), 60);
            $country = self::sanitizeText($first['country_name'] ?? ($first['country'] ?? 'Multiple Countries'), 60);
            $funding = self::sanitizeText($first['funding_type'] ?? ($first['funding'] ?? 'Full/Partial'), 60);
            $deadline = self::formatDeadline($first['application_deadline'] ?? ($first['deadline'] ?? ''));
            $url = url('/dashboard');

            return [
                $title,
                $desc,
                $provider,
                $studyLevel,
                $country,
                $funding,
                $deadline,
                $url
            ];
        }

        // Single match (or match inside matches array)
        $m = (!empty($payload['matches']) && is_array($payload['matches']) && count($payload['matches']) === 1)
            ? $payload['matches'][0]
            : $payload;

        $title = self::sanitizeText($m['title'] ?? ($m['scholarship_title'] ?? 'Scholarship Opportunity'), 120);
        $desc = self::sanitizeText($m['short_description'] ?? ($m['description'] ?? ($m['summary'] ?? '')), 250);
        if ($desc === '') {
            $desc = 'A verified scholarship opportunity matching your academic profile.';
        }
        $provider = self::sanitizeText($m['provider_name'] ?? ($m['provider'] ?? 'Scholarship Provider'), 80);
        $studyLevel = self::sanitizeText($m['study_level'] ?? ($m['degree'] ?? 'Higher Education'), 60);
        $country = self::sanitizeText($m['country_name'] ?? ($m['country'] ?? 'International'), 60);
        $funding = self::sanitizeText($m['funding_type'] ?? ($m['funding'] ?? 'Fully Funded'), 60);
        $deadline = self::formatDeadline($m['application_deadline'] ?? ($m['deadline'] ?? ''));
        
        $applyUrl = trim((string)($m['official_application_url'] ?? ($m['official_apply_url'] ?? ($m['official_website'] ?? ($m['detail_url'] ?? '')))));
        if (!filter_var($applyUrl, FILTER_VALIDATE_URL)) {
            $slug = $m['slug'] ?? '';
            $applyUrl = !empty($slug) ? url('/scholarships/' . $slug) : url('/dashboard');
        }

        if ($type === 'SCHOLARSHIP_DEADLINE_TODAY') {
            return [
                $title,
                $desc,
                $provider,
                $deadline,
                $applyUrl
            ];
        }

        if ($type === 'SCHOLARSHIP_DEADLINE_SOON' || $type === 'DEADLINE_REMINDER') {
            $daysLeft = $m['days_left'] ?? '3';
            $daysLeftText = (string)$daysLeft . ' day' . ((int)$daysLeft === 1 ? '' : 's');
            return [
                $title,
                $desc,
                $provider,
                $daysLeftText,
                $deadline,
                $applyUrl
            ];
        }

        // Default new_match: 8 parameters
        return [
            $title,
            $desc,
            $provider,
            $studyLevel,
            $country,
            $funding,
            $deadline,
            $applyUrl
        ];
    }
}
