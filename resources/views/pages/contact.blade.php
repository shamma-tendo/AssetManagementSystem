<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact Us - {{ config('app.name', 'AEMS') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; background-color: #f9f9f8; }
        .dark-mode { background-color: #0a0a0a; }
        .page-container { max-width: 800px; margin: 0 auto; padding: 2rem; }
        h1 { font-size: 2.25rem; font-weight: bold; margin-bottom: 1rem; color: #1b1b18; }
        .dark-mode h1 { color: #ededec; }
        p { color: #4b5563; margin-bottom: 1rem; }
        .dark-mode p { color: #a1a09a; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #1b1b18; }
        .dark-mode label { color: #ededec; }
        input, textarea { width: 100%; padding: 0.75rem; border: 1px solid #e3e3e0; border-radius: 0.375rem; font-family: inherit; font-size: 1rem; background-color: white; color: #1b1b18; }
        .dark-mode input, .dark-mode textarea { background-color: #161615; border-color: #3e3e3a; color: #ededec; }
        input:focus, textarea:focus { outline: none; border-color: #f53003; box-shadow: 0 0 0 3px rgba(245, 48, 3, 0.1); }
        .dark-mode input:focus, .dark-mode textarea:focus { box-shadow: 0 0 0 3px rgba(245, 48, 3, 0.2); }
        button { background-color: #1b1b18; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 0.375rem; font-weight: 500; cursor: pointer; font-size: 1rem; }
        .dark-mode button { background-color: #ededec; color: #1b1b18; }
        button:hover { background-color: #333; }
        .dark-mode button:hover { background-color: #fff; }
        .back-link { margin-bottom: 2rem; }
        .back-link a { color: #f53003; text-decoration: none; font-weight: 500; }
        .back-link a:hover { text-decoration: underline; }
        .alert { padding: 1rem; border-radius: 0.375rem; margin-bottom: 1rem; }
        .alert-success { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .dark-mode .alert-success { background-color: #064e3b; color: #d1fae5; border-color: #047857; }
        .dark-mode .alert-error { background-color: #7f1d1d; color: #fecaca; border-color: #dc2626; }
        .contact-options { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 3rem; padding-top: 2rem; border-top: 1px solid #e3e3e0; }
        .dark-mode .contact-options { border-top-color: #3e3e3a; }
        .contact-option h3 { font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem; }
        .contact-option a { color: #f53003; text-decoration: none; }
        .contact-option a:hover { text-decoration: underline; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-950">
    <div class="page-container dark:text-white">
        <div class="back-link">
            <a href="/">← Back to Home</a>
        </div>
        
        <h1>Contact Us</h1>
        <p>Have questions about AEMS? We'd love to hear from you. Fill out the form below and we'll get back to you as soon as possible.</p>

        @if ($errors->any())
            <div class="alert alert-error">
                <strong>Please fix the following errors:</strong>
                <ul style="margin-top: 0.5rem; margin-left: 1rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('contact.submit') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" required value="{{ old('name') }}" placeholder="Your name">
            </div>

            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required value="{{ old('email') }}" placeholder="your@email.com">
            </div>

            <div class="form-group">
                <label for="subject">Subject *</label>
                <input type="text" id="subject" name="subject" required value="{{ old('subject') }}" placeholder="How can we help?">
            </div>

            <div class="form-group">
                <label for="message">Message *</label>
                <textarea id="message" name="message" required rows="6" placeholder="Please tell us more details about your inquiry...">{{ old('message') }}</textarea>
            </div>

            <button type="submit">Send Message</button>
        </form>

        <div class="contact-options">
            <div class="contact-option">
                <h3>Email</h3>
                <p>For general inquiries:</p>
                <a href="mailto:support@aems.app">support@aems.app</a>
                <p style="margin-top: 1rem;">For sales inquiries:</p>
                <a href="mailto:sales@aems.app">sales@aems.app</a>
            </div>

            <div class="contact-option">
                <h3>Sales Team</h3>
                <p>Want to discuss pricing or schedule a demo? Our sales team is ready to help.</p>
                <a href="tel:+1-800-AEMS-123" style="display: inline-block; margin-top: 0.5rem;">+1 (800) 234-2367</a>
                <p style="margin-top: 0.5rem; font-size: 0.875rem; color: #706f6c;">
                    <span class="dark:text-gray-400">Mon-Fri, 9AM-6PM EST</span>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
