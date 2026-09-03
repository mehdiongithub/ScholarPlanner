<?php

namespace App\Controllers;

use App\Services\Database;
use App\Helpers\Security;

class PageController {
    /**
     * Display the Privacy Policy page
     */
    public function privacy() {
        return view('pages.privacy', [
            'title' => 'Privacy Policy — ScholarPlanner',
            'description' => 'Privacy Policy for ScholarPlanner. Learn how we collect, use, store, and protect your personal information.'
        ]);
    }

    /**
     * Display the Terms of Service page
     */
    public function terms() {
        return view('pages.terms', [
            'title' => 'Terms of Service — ScholarPlanner',
            'description' => 'Terms of Service for ScholarPlanner. Read the terms governing your use of our scholarship discovery and notification platform.'
        ]);
    }

    /**
     * Display the FAQ page
     */
    public function faq() {
        return view('pages.faq', [
            'title' => 'Frequently Asked Questions — ScholarPlanner',
            'description' => 'Find answers to common questions about ScholarPlanner — how matching works, WhatsApp alerts, pricing, subscriptions, and privacy.'
        ]);
    }

    /**
     * Display the About Us page
     */
    public function about() {
        return view('pages.about', [
            'title' => 'About Us — ScholarPlanner',
            'description' => 'Learn about ScholarPlanner — a scholarship discovery platform matching opportunities to student profiles and sending personalized WhatsApp alerts.'
        ]);
    }

    /**
     * Display the Contact Us page
     */
    public function contact() {
        return view('pages.contact', [
            'title' => 'Contact Us — ScholarPlanner',
            'description' => 'Get in touch with ScholarPlanner. Send us a message about scholarships, your account, partnerships, or support.'
        ]);
    }

    /**
     * Handle Contact Us form submission
     */
    public function submitContact() {
        if (!Security::validateCsrf($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Invalid form session. Please try again.';
            header('Location: ' . url('/contact'));
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? 'General Inquiry');
        $message = trim($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($message)) {
            $_SESSION['flash_error'] = 'Please complete all required fields.';
            header('Location: ' . url('/contact'));
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Please provide a valid email address.';
            header('Location: ' . url('/contact'));
            exit;
        }

        $_SESSION['flash_success'] = 'Thank you for reaching out! Your message has been received and our team will get back to you shortly.';
        header('Location: ' . url('/contact'));
        exit;
    }
}
