<?php

namespace App\Controllers;

class PlaceholderController {
    /**
     * Placeholder for future scholarship search directory page
     */
    public function scholarships() {
        return view('placeholder', [
            'title' => 'Scholarship Directory',
            'message' => 'The searchable scholarship directory is currently under development. Soon, you will be able to search and filter through hundreds of international scholarship opportunities.',
            'step' => 'Step 5: Scholarship Database & Matching Engine'
        ]);
    }

    /**
     * Placeholder for future student login page
     */
    public function login() {
        return view('placeholder', [
            'title' => 'Student Login',
            'message' => 'The user login and authorization system will be implemented shortly. It will allow you to sign in to access your dashboard settings, profile details, and matched scholarship tracking.',
            'step' => 'Step 3: User Authentication & Onboarding'
        ]);
    }

    /**
     * Placeholder for future registration flow
     */
    public function register() {
        return view('placeholder', [
            'title' => 'Student Registration',
            'message' => 'The onboarding registration flow is coming soon. Soon, you will be able to build your academic profile and enable WhatsApp/email alert channels.',
            'step' => 'Step 3: User Authentication & Onboarding'
        ]);
    }

    /**
     * Placeholder for support contact forms
     */
    public function contact() {
        return view('placeholder', [
            'title' => 'Contact Us',
            'message' => 'The support query submission form will be integrated in a later update. For now, feel free to review our FAQ sections.',
            'step' => 'Future Updates'
        ]);
    }

    /**
     * Redirects to the homepage "How It Works" section
     */
    public function howItWorks() {
        header('Location: ' . url('/#how-it-works'));
        exit;
    }

    /**
     * Redirects to the homepage "Features" section
     */
    public function features() {
        header('Location: ' . url('/#features'));
        exit;
    }

    /**
     * Redirects to the homepage "Pricing" section
     */
    public function pricing() {
        header('Location: ' . url('/#pricing'));
        exit;
    }

    /**
     * Redirects to the homepage "Why ScholarMatch" section
     */
    public function about() {
        header('Location: ' . url('/#why'));
        exit;
    }

    /**
     * Redirects to the homepage "FAQ" section
     */
    public function faq() {
        header('Location: ' . url('/#faq'));
        exit;
    }
}
