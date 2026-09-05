<?php

namespace Tests;

use App\Services\WhatsApp\ScholarshipMessageFormatter;
use App\Services\NotificationTypes;

class ScholarshipMessageFormatterTest {

    public function run(): void {
        echo "=================================================================\n";
        echo " RUNNING SCHOLARSHIP MESSAGE FORMATTER TEST SUITE (18 TESTS)     \n";
        echo "=================================================================\n\n";

        $this->test1_HeadingAppearsAtBeginning();
        $this->test2_TitleAppearsBelowHeading();
        $this->test3_ShortDescriptionSanitization();
        $this->test4_HtmlTagsRemovedAndEntitiesDecoded();
        $this->test5_WhitespaceNormalized();
        $this->test6_DeadlineFormattingStandardDate();
        $this->test7_DeadlineFormattingToday();
        $this->test8_DeadlineFormattingTomorrow();
        $this->test9_ProviderAndCountryIncludedWhenAvailable();
        $this->test10_FundingAndStudyLevelIncludedWhenAvailable();
        $this->test11_EmptyFieldsOmittedCleanly();
        $this->test12_ApplicationUrlFormatted();
        $this->test13_FooterAppearsAtBottom();
        $this->test14_DeadlineSoonHeadingAndCountdown();
        $this->test15_DeadlineTodayHeading();
        $this->test16_MultipleScholarshipsCombinedBatchFormat();
        $this->test17_TemplateParamsCountAndOrderNewMatch();
        $this->test18_TemplateParamsCountAndOrderDeadlineReminders();

        echo "\n=================================================================\n";
        echo " ✔ ALL 18 SCHOLARSHIP MESSAGE FORMATTER TESTS PASSED!            \n";
        echo "=================================================================\n\n";
    }

    private function assert(bool $cond, string $msg): void {
        if (!$cond) {
            throw new \RuntimeException("ASSERTION FAILED: " . $msg);
        }
    }

    private function test1_HeadingAppearsAtBeginning(): void {
        echo "[Test 1] Heading appears at the very beginning... ";
        $data = [
            'title' => 'Fulbright Scholarship 2026',
            'description' => 'Prestigious US scholarship program.',
            'provider_name' => 'USEFP',
            'application_deadline' => '2026-10-31'
        ];
        $msg = ScholarshipMessageFormatter::formatSingleMessage($data, 'NEW_MATCH');
        $this->assert(str_starts_with($msg, "🎓 SCHOLARSHIP OPPORTUNITY"), "Must start with 🎓 SCHOLARSHIP OPPORTUNITY");
        echo "PASS\n";
    }

    private function test2_TitleAppearsBelowHeading(): void {
        echo "[Test 2] Title appears immediately below heading... ";
        $data = [
            'title' => 'Chevening UK Scholarship',
            'description' => 'Fully funded UK government scholarship.'
        ];
        $msg = ScholarshipMessageFormatter::formatSingleMessage($data, 'NEW_MATCH');
        $lines = explode("\n", $msg);
        $this->assert($lines[0] === '🎓 SCHOLARSHIP OPPORTUNITY', "Line 0 is heading");
        $this->assert($lines[1] === 'Chevening UK Scholarship', "Line 1 is title");
        echo "PASS\n";
    }

    private function test3_ShortDescriptionSanitization(): void {
        echo "[Test 3] Short description is clean and concise... ";
        $longDesc = str_repeat("This is a great scholarship with many benefits for international students. ", 10);
        $data = [
            'title' => 'DAAD Germany',
            'description' => $longDesc
        ];
        $msg = ScholarshipMessageFormatter::formatSingleMessage($data, 'NEW_MATCH');
        $this->assert(strpos($msg, 'DAAD Germany') !== false, "Title present");
        $this->assert(strpos($msg, '...') !== false, "Truncated description contains ellipsis");
        $this->assert(strlen($msg) < 1500, "Message length kept within reasonable bounds");
        echo "PASS\n";
    }

    private function test4_HtmlTagsRemovedAndEntitiesDecoded(): void {
        echo "[Test 4] HTML tags removed and HTML entities decoded... ";
        $htmlDesc = "<p>This is <strong>bold</strong> &amp; <em>italic</em> with <a href='https://example.com'>links</a> &quot;quoted&quot;.</p>";
        $data = [
            'title' => 'Turkiye Burslari',
            'description' => $htmlDesc
        ];
        $msg = ScholarshipMessageFormatter::formatSingleMessage($data, 'NEW_MATCH');
        $this->assert(strpos($msg, '<p>') === false, "No <p> tags");
        $this->assert(strpos($msg, '<strong>') === false, "No <strong> tags");
        $this->assert(strpos($msg, '&amp;') === false, "No &amp; entity");
        $this->assert(strpos($msg, '&quot;') === false, "No &quot; entity");
        $this->assert(strpos($msg, 'bold & italic with links "quoted".') !== false, "Decoded clean text present");
        echo "PASS\n";
    }

    private function test5_WhitespaceNormalized(): void {
        echo "[Test 5] Excessive whitespace normalized... ";
        $messy = "Line 1   with    extra    spaces.\n\n\n\n\nLine 2.";
        $cleaned = ScholarshipMessageFormatter::sanitizeText($messy);
        $this->assert(strpos($cleaned, '   ') === false, "No triple spaces");
        $this->assert(strpos($cleaned, "\n\n\n") === false, "No triple newlines");
        echo "PASS\n";
    }

    private function test6_DeadlineFormattingStandardDate(): void {
        echo "[Test 6] Standard date formatted cleanly (e.g. 31 October 2026)... ";
        $formatted = ScholarshipMessageFormatter::formatDeadline('2026-10-31');
        $this->assert($formatted === '31 October 2026', "Standard date formatted as '31 October 2026'");
        echo "PASS\n";
    }

    private function test7_DeadlineFormattingToday(): void {
        echo "[Test 7] Today's deadline formatted as 'Today'... ";
        $today = date('Y-m-d');
        $formatted = ScholarshipMessageFormatter::formatDeadline($today);
        $this->assert($formatted === 'Today', "Today's deadline formatted as 'Today'");
        echo "PASS\n";
    }

    private function test8_DeadlineFormattingTomorrow(): void {
        echo "[Test 8] Tomorrow's deadline formatted as 'Tomorrow'... ";
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $formatted = ScholarshipMessageFormatter::formatDeadline($tomorrow);
        $this->assert($formatted === 'Tomorrow', "Tomorrow's deadline formatted as 'Tomorrow'");
        echo "PASS\n";
    }

    private function test9_ProviderAndCountryIncludedWhenAvailable(): void {
        echo "[Test 9] Provider and Country included when available... ";
        $data = [
            'title' => 'HEC Overseas Scholarship',
            'provider_name' => 'Higher Education Commission',
            'country_name' => 'Pakistan',
            'application_deadline' => '2026-12-31'
        ];
        $msg = ScholarshipMessageFormatter::formatSingleMessage($data, 'NEW_MATCH');
        $this->assert(strpos($msg, "🏛 Provider:\nHigher Education Commission") !== false, "Provider present");
        $this->assert(strpos($msg, "🌍 Country:\nPakistan") !== false, "Country present");
        echo "PASS\n";
    }

    private function test10_FundingAndStudyLevelIncludedWhenAvailable(): void {
        echo "[Test 10] Funding and Study Level included when available... ";
        $data = [
            'title' => 'Commonwealth PhD Scholarship',
            'study_level' => "Doctorate / PhD",
            'funding_type' => 'Fully Funded',
            'application_deadline' => '2026-12-31'
        ];
        $msg = ScholarshipMessageFormatter::formatSingleMessage($data, 'NEW_MATCH');
        $this->assert(strpos($msg, "📚 Study Level:\nDoctorate / PhD") !== false, "Study Level present");
        $this->assert(strpos($msg, "💰 Funding:\nFully Funded") !== false, "Funding present");
        echo "PASS\n";
    }

    private function test11_EmptyFieldsOmittedCleanly(): void {
        echo "[Test 11] Empty fields omitted cleanly without N/A... ";
        $data = [
            'title' => 'Minimal Scholarship',
            'provider_name' => null,
            'study_level' => '',
            'funding_type' => null,
            'country_name' => ''
        ];
        $msg = ScholarshipMessageFormatter::formatSingleMessage($data, 'NEW_MATCH');
        $this->assert(strpos($msg, '🏛 Provider:') === false, "No Provider row when empty");
        $this->assert(strpos($msg, '📚 Study Level:') === false, "No Study Level row when empty");
        $this->assert(strpos($msg, '💰 Funding:') === false, "No Funding row when empty");
        $this->assert(strpos($msg, '🌍 Country:') === false, "No Country row when empty");
        $this->assert(strpos($msg, 'N/A') === false, "No N/A literals");
        echo "PASS\n";
    }

    private function test12_ApplicationUrlFormatted(): void {
        echo "[Test 12] Application URL formatted and included... ";
        $data = [
            'title' => 'Australia Awards',
            'official_application_url' => 'https://australiaawards.gov.au/apply'
        ];
        $msg = ScholarshipMessageFormatter::formatSingleMessage($data, 'NEW_MATCH');
        $this->assert(strpos($msg, "🔗 Apply:\nhttps://australiaawards.gov.au/apply") !== false, "Apply URL present");
        echo "PASS\n";
    }

    private function test13_FooterAppearsAtBottom(): void {
        echo "[Test 13] Footer appears at bottom... ";
        $data = ['title' => 'Test Scholarship'];
        $msg = ScholarshipMessageFormatter::formatSingleMessage($data, 'NEW_MATCH');
        $this->assert(str_ends_with($msg, "ScholarPlanner\nYour scholarship discovery assistant."), "Footer present at bottom");
        echo "PASS\n";
    }

    private function test14_DeadlineSoonHeadingAndCountdown(): void {
        echo "[Test 14] Deadline soon heading and time remaining... ";
        $data = [
            'title' => 'Urgent Fellowship',
            'application_deadline' => '2026-11-15',
            'days_left' => 3
        ];
        $msg = ScholarshipMessageFormatter::formatSingleMessage($data, 'SCHOLARSHIP_DEADLINE_SOON');
        $this->assert(str_starts_with($msg, "⏰ SCHOLARSHIP DEADLINE APPROACHING"), "Heading is ⏰ SCHOLARSHIP DEADLINE APPROACHING");
        $this->assert(strpos($msg, "⚠️ Deadline:\n15 November 2026") !== false, "Deadline formatted");
        $this->assert(strpos($msg, "⏳ Time remaining:\n3 days") !== false, "Time remaining countdown present");
        echo "PASS\n";
    }

    private function test15_DeadlineTodayHeading(): void {
        echo "[Test 15] Deadline today heading... ";
        $data = [
            'title' => 'Closing Today Scholarship',
            'application_deadline' => date('Y-m-d')
        ];
        $msg = ScholarshipMessageFormatter::formatSingleMessage($data, 'SCHOLARSHIP_DEADLINE_TODAY');
        $this->assert(str_starts_with($msg, "🚨 SCHOLARSHIP DEADLINE TODAY"), "Heading is 🚨 SCHOLARSHIP DEADLINE TODAY");
        $this->assert(strpos($msg, "⚠️ Deadline:\nToday") !== false, "Deadline is Today");
        echo "PASS\n";
    }

    private function test16_MultipleScholarshipsCombinedBatchFormat(): void {
        echo "[Test 16] Multiple scholarships combined batch format... ";
        $matches = [
            [
                'title' => 'Scholarship Alpha',
                'description' => 'First scholarship match.',
                'deadline' => '2026-10-15',
                'country' => 'UK',
                'funding' => 'Fully Funded',
                'official_application_url' => 'https://example.com/alpha'
            ],
            [
                'title' => 'Scholarship Beta',
                'description' => 'Second scholarship match.',
                'deadline' => '2026-11-01',
                'country' => 'USA',
                'funding' => 'Partial Tuition',
                'official_application_url' => 'https://example.com/beta'
            ]
        ];
        $msg = ScholarshipMessageFormatter::formatMultipleMessage($matches, 2);
        $this->assert(str_starts_with($msg, "🎓 SCHOLARSHIP OPPORTUNITIES"), "Heading is 🎓 SCHOLARSHIP OPPORTUNITIES");
        $this->assert(strpos($msg, "You have 2 new scholarship opportunities matching your profile.") !== false, "Count line present");
        $this->assert(strpos($msg, "Scholarship Alpha") !== false, "First scholarship present");
        $this->assert(strpos($msg, "Scholarship Beta") !== false, "Second scholarship present");
        $this->assert(str_ends_with($msg, "ScholarPlanner\nYour scholarship discovery assistant."), "Footer present");
        echo "PASS\n";
    }

    private function test17_TemplateParamsCountAndOrderNewMatch(): void {
        echo "[Test 17] Template parameters count and order for new_match (8 params)... ";
        $payload = [
            'title' => 'Erasmus Mundus',
            'description' => 'European joint masters degree.',
            'provider' => 'European Commission',
            'study_level' => 'Masters',
            'country' => 'Europe',
            'funding' => 'Fully Funded',
            'deadline' => '2026-12-15',
            'official_application_url' => 'https://erasmus-plus.ec.europa.eu'
        ];
        $params = ScholarshipMessageFormatter::buildTemplateParams('NEW_MATCH', $payload);
        $this->assert(count($params) === 8, "new_match must have exactly 8 parameters");
        $this->assert($params[0] === 'Erasmus Mundus', "Param 1 is Title");
        $this->assert($params[1] === 'European joint masters degree.', "Param 2 is Short Description");
        $this->assert($params[2] === 'European Commission', "Param 3 is Provider");
        $this->assert($params[3] === 'Masters', "Param 4 is Study Level");
        $this->assert($params[4] === 'Europe', "Param 5 is Country");
        $this->assert($params[5] === 'Fully Funded', "Param 6 is Funding");
        $this->assert($params[6] === '15 December 2026', "Param 7 is Deadline");
        $this->assert($params[7] === 'https://erasmus-plus.ec.europa.eu', "Param 8 is Application URL");
        echo "PASS\n";
    }

    private function test18_TemplateParamsCountAndOrderDeadlineReminders(): void {
        echo "[Test 18] Template parameters count and order for deadline reminders... ";
        $payloadSoon = [
            'title' => 'Gates Cambridge',
            'description' => 'Prestigious Cambridge scholarship.',
            'provider' => 'University of Cambridge',
            'deadline' => '2026-11-20',
            'days_left' => 5,
            'official_application_url' => 'https://gatescambridge.org'
        ];
        $paramsSoon = ScholarshipMessageFormatter::buildTemplateParams('SCHOLARSHIP_DEADLINE_SOON', $payloadSoon);
        $this->assert(count($paramsSoon) === 6, "deadline_soon must have exactly 6 parameters");
        $this->assert($paramsSoon[0] === 'Gates Cambridge', "Param 1 is Title");
        $this->assert($paramsSoon[1] === 'Prestigious Cambridge scholarship.', "Param 2 is Short Description");
        $this->assert($paramsSoon[2] === 'University of Cambridge', "Param 3 is Provider");
        $this->assert($paramsSoon[3] === '5 days', "Param 4 is Time Remaining");
        $this->assert($paramsSoon[4] === '20 November 2026', "Param 5 is Deadline");
        $this->assert($paramsSoon[5] === 'https://gatescambridge.org', "Param 6 is Apply URL");

        $payloadToday = [
            'title' => 'Rhodes Scholarship',
            'description' => 'Oxford university scholarship.',
            'provider' => 'University of Oxford',
            'deadline' => '2026-10-31',
            'official_application_url' => 'https://rhodeshouse.ox.ac.uk'
        ];
        $paramsToday = ScholarshipMessageFormatter::buildTemplateParams('SCHOLARSHIP_DEADLINE_TODAY', $payloadToday);
        $this->assert(count($paramsToday) === 5, "deadline_today must have exactly 5 parameters");
        $this->assert($paramsToday[0] === 'Rhodes Scholarship', "Param 1 is Title");
        $this->assert($paramsToday[1] === 'Oxford university scholarship.', "Param 2 is Short Description");
        $this->assert($paramsToday[2] === 'University of Oxford', "Param 3 is Provider");
        $this->assert($paramsToday[3] === '31 October 2026', "Param 4 is Deadline");
        $this->assert($paramsToday[4] === 'https://rhodeshouse.ox.ac.uk', "Param 5 is Apply URL");
        echo "PASS\n";
    }
}

if (!defined('TESTING_MODE')) {
    define('TESTING_MODE', true);
}
require_once 'c:/laragon/www/scholarship/tests/bootstrap.php';
(new ScholarshipMessageFormatterTest())->run();
