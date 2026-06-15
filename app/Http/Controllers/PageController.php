<?php

namespace App\Http\Controllers;

use App\Mail\ContactFormMail;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class PageController extends Controller
{
    /**
     * Show the privacy policy page.
     */
    public function privacy()
    {
        return view('pages.privacy');
    }

    /**
     * Show the terms of service page.
     */
    public function terms()
    {
        return view('pages.terms');
    }

    /**
     * Show the contact page.
     */
    public function contact()
    {
        return view('pages.contact');
    }

    /**
     * Submit a contact form message.
     */
    public function submitContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10|max:2000',
        ]);

        try {
            // Store the contact message in database
            Contact::create($validated + ['status' => 'new']);

            // Try to send email (graceful failure if mail not configured)
            try {
                Mail::to(config('mail.from.address', 'noreply@aems.app'))
                    ->send(new ContactFormMail($validated));
            } catch (\Exception $e) {
                // Email sending failed, but contact was saved
                // Log the error for debugging
                logger()->warning('Failed to send contact form email: ' . $e->getMessage());
            }

            return redirect(route('contact'))->with('success', 'Thank you for your message! We will get back to you soon.');
        } catch (\Exception $e) {
            return redirect(route('contact'))->with('error', 'Failed to send message. Please try again.');
        }
    }
}

