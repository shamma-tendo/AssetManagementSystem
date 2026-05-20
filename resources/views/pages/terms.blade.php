<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terms of Service - {{ config('app.name', 'AEMS') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
        .page-container { max-width: 900px; margin: 0 auto; padding: 2rem; }
        h1 { font-size: 2.25rem; font-weight: bold; margin-bottom: 1rem; color: #1b1b18; }
        h2 { font-size: 1.5rem; font-weight: 600; margin-top: 2rem; margin-bottom: 1rem; color: #1b1b18; }
        p { margin-bottom: 1rem; line-height: 1.6; color: #4b5563; }
        ul, ol { margin-left: 1.5rem; margin-bottom: 1rem; }
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
        
        <h1>Terms of Service</h1>
        <p><strong>Last Updated: May 2026</strong></p>

        <h2>1. Agreement to Terms</h2>
        <p>By accessing and using the Asset Management System (AEMS), you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.</p>

        <h2>2. Use License</h2>
        <p>Permission is granted to temporarily download one copy of the materials (information or software) on Asset Management System for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, and under this license you may not:</p>
        <ul>
            <li>Modifying or copying the materials</li>
            <li>Using the materials for any commercial purpose or for any public display</li>
            <li>Attempting to decompile or reverse engineer any software contained on AEMS</li>
            <li>Removing any copyright or other proprietary notations from the materials</li>
            <li>Transferring the materials to another person or "mirror" the materials on any other server</li>
        </ul>

        <h2>3. Disclaimer</h2>
        <p>The materials on AEMS are provided on an 'as is' basis. AEMS makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</p>

        <h2>4. Limitations</h2>
        <p>In no event shall AEMS or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the materials on AEMS, even if AEMS or an authorized representative has been notified orally or in writing of the possibility of such damage.</p>

        <h2>5. Accuracy of Materials</h2>
        <p>The materials appearing on AEMS could include technical, typographical, or photographic errors. AEMS does not warrant that any of the materials on its website are accurate, complete, or current. AEMS may make changes to the materials contained on its website at any time without notice.</p>

        <h2>6. Materials Link</h2>
        <p>AEMS has not reviewed all of the sites linked to its website and is not responsible for the contents of any such linked site. The inclusion of any link does not imply endorsement by AEMS of the site. Use of any such linked website is at the user's own risk.</p>

        <h2>7. Modifications</h2>
        <p>AEMS may revise these terms of service for its website at any time without notice. By using this website, you are agreeing to be bound by the then current version of these terms of service.</p>

        <h2>8. Governing Law</h2>
        <p>These terms and conditions are governed by and construed in accordance with the laws of the jurisdiction in which AEMS operates, and you irrevocably submit to the exclusive jurisdiction of the courts located in that location.</p>

        <h2>9. Contact Information</h2>
        <p>If you have any questions about these Terms of Service, please contact us at:</p>
        <ul>
            <li>Email: <a href="mailto:legal@aems.app" style="color: #f53003;">legal@aems.app</a></li>
            <li><a href="/contact" style="color: #f53003;">Contact Form</a></li>
        </ul>
    </div>
</body>
</html>
