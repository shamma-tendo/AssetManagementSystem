<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy - {{ config('app.name', 'AEMS') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
        .page-container { max-width: 900px; margin: 0 auto; padding: 2rem; }
        h1 { font-size: 2.25rem; font-weight: bold; margin-bottom: 1rem; color: #1b1b18; }
        h2 { font-size: 1.5rem; font-weight: 600; margin-top: 2rem; margin-bottom: 1rem; color: #1b1b18; }
        p { margin-bottom: 1rem; line-height: 1.6; color: #4b5563; }
        ul { margin-left: 1.5rem; margin-bottom: 1rem; }
        li { margin-bottom: 0.5rem; color: #4b5563; }
        .back-link { margin-bottom: 2rem; }
        .back-link a { color: #f53003; text-decoration: none; font-weight: 500; }
        .back-link a:hover { text-decoration: underline; }
        .dark-mode { background-color: #0a0a0a; color: #ededec; }
        .dark-mode h1, .dark-mode h2 { color: #ededec; }
        .dark-mode p, .dark-mode li { color: #a1a09a; }
    </style>
</head>
<body class="bg-white dark:bg-gray-950">
    <div class="page-container dark:text-white">
        <div class="back-link">
            <a href="/">← Back to Home</a>
        </div>
        
        <h1>Privacy Policy</h1>
        <p><strong>Last Updated: May 2026</strong></p>

        <h2>Introduction</h2>
        <p>Asset Management System (AEMS) ("we", "us", "our", or "Company") operates the AEMS website and mobile application. This page informs you of our policies regarding the collection, use, and disclosure of personal data when you use our Service and the choices you have associated with that data.</p>

        <h2>Information Collection and Use</h2>
        <p>We collect several different types of information for various purposes to provide and improve our Service to you.</p>
        
        <h3 style="font-size: 1.25rem; font-weight: 500; margin-top: 1.5rem; margin-bottom: 0.75rem;">Types of Data Collected:</h3>
        <ul>
            <li><strong>Personal Data:</strong> While using our Service, we may ask you to provide us with certain personally identifiable information that can be used to contact or identify you ("Personal Data"). This may include:
                <ul style="margin-top: 0.5rem;">
                    <li>Email address</li>
                    <li>First name and last name</li>
                    <li>Phone number</li>
                    <li>Address, State, Province, ZIP/Postal code, City</li>
                    <li>Cookies and Usage Data</li>
                </ul>
            </li>
            <li><strong>Usage Data:</strong> We may also collect information how the Service is accessed and used ("Usage Data"). This may include information such as your computer's Internet Protocol address (e.g. IP address), browser type, browser version, the pages you visit, the time and date of your visit, and other diagnostic data.</li>
        </ul>

        <h2>Use of Data</h2>
        <p>AEMS uses the collected data for various purposes:</p>
        <ul>
            <li>To provide and maintain the Service</li>
            <li>To notify you about changes to our Service</li>
            <li>To allow you to participate in interactive features of our Service when you choose to do so</li>
            <li>To provide customer support</li>
            <li>To gather analysis or valuable information to improve our Service</li>
            <li>To monitor the usage of our Service</li>
            <li>To detect, prevent and address technical issues</li>
        </ul>

        <h2>Security of Data</h2>
        <p>The security of your data is important to us but remember that no method of transmission over the Internet or method of electronic storage is 100% secure. While we strive to use commercially acceptable means to protect your Personal Data, we cannot guarantee its absolute security.</p>

        <h2>Changes to This Privacy Policy</h2>
        <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "effective date" at the top of this Privacy Policy.</p>

        <h2>Contact Us</h2>
        <p>If you have any questions about this Privacy Policy, please contact us at:</p>
        <ul>
            <li>Email: <a href="mailto:privacy@aems.app" style="color: #f53003;">privacy@aems.app</a></li>
            <li><a href="/contact" style="color: #f53003;">Contact Form</a></li>
        </ul>
    </div>
</body>
</html>
