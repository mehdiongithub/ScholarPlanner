<?php

use App\Controllers\PageController;

class PublicPagesTest {
    public function run(): void {
        echo "--- Running PublicPagesTest ---\n";

        $controller = new PageController();

        // 1. Test Privacy Page rendering
        ob_start();
        $controller->privacy();
        $privacyHtml = ob_get_clean();
        if (strpos($privacyHtml, 'Privacy Policy') === false || strpos($privacyHtml, 'Information We Collect') === false) {
            throw new Exception("PublicPagesTest Failure: Privacy Policy page did not render expected content.");
        }
        echo "✔ Privacy Policy page renders successfully.\n";

        // 2. Test Terms of Service Page rendering
        ob_start();
        $controller->terms();
        $termsHtml = ob_get_clean();
        if (strpos($termsHtml, 'Terms of Service') === false || strpos($termsHtml, 'Acceptance of Terms') === false) {
            throw new Exception("PublicPagesTest Failure: Terms of Service page did not render expected content.");
        }
        echo "✔ Terms of Service page renders successfully.\n";

        // 3. Test FAQ Page rendering
        ob_start();
        $controller->faq();
        $faqHtml = ob_get_clean();
        if (strpos($faqHtml, 'Frequently Asked Questions') === false || strpos($faqHtml, 'How Matching Works') === false) {
            throw new Exception("PublicPagesTest Failure: FAQ page did not render expected content.");
        }
        echo "✔ FAQ page renders successfully.\n";

        // 4. Test About Us Page rendering
        ob_start();
        $controller->about();
        $aboutHtml = ob_get_clean();
        if (strpos($aboutHtml, 'About ScholarPlanner') === false || strpos($aboutHtml, 'Why We Built') === false) {
            throw new Exception("PublicPagesTest Failure: About Us page did not render expected content.");
        }
        echo "✔ About Us page renders successfully.\n";

        // 5. Test Contact Us Page rendering
        ob_start();
        $controller->contact();
        $contactHtml = ob_get_clean();
        if (strpos($contactHtml, 'Get in Touch') === false || strpos($contactHtml, 'Send Us a Message') === false) {
            throw new Exception("PublicPagesTest Failure: Contact Us page did not render expected content.");
        }
        echo "✔ Contact Us page renders successfully.\n";

        echo "PublicPagesTest PASSED.\n\n";
    }
}
