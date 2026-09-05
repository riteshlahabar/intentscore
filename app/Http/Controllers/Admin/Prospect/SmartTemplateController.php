<?php

namespace App\Http\Controllers\Admin\Prospect;

use App\Http\Controllers\Controller;
use App\Models\SmartLink\Prospect;
use App\Models\SmartLink\SmartPage;
use App\Models\SmartLink\SmartPageSection;
use App\Models\SmartLink\SmartPageTemplate;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/** Gallery of the ported page designs, with a live preview rendered from sample data. */
class SmartTemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.templates.index', [
            'templates' => SmartPageTemplate::orderBy('name')->get(),
        ]);
    }

    public function preview(SmartPageTemplate $template): View
    {
        $design = in_array($template->design, SmartPageTemplate::DESIGNS, true) ? $template->design : null;

        abort_unless($design, 404);

        $prospect = new Prospect([
            'business_name' => 'Sample Business',
            'industry' => $template->industry,
            'location' => 'Pune, Maharashtra',
            'website' => 'samplebusiness.example.com',
            'offer' => 'Website + Google Business growth package',
        ]);

        $page = new SmartPage([
            'subheading' => 'A few ideas we put together specifically for your business.',
            'personalized_message' => 'Hi, we looked at how your business shows up online and put together a few specific ideas that should be quick wins.',
            'cta_text' => "Let's talk",
            'cta_url' => '#',
            'cta_type' => 'whatsapp',
        ]);
        $page->setRelation('prospect', $prospect);

        return view("frontend.smart-page.designs.{$design}", [
            'page' => $page,
            'prospect' => $prospect,
            'sections' => $this->sampleSections($template),
            'settings' => [
                'company_name' => 'Your Agency',
                'company_about' => 'We help local businesses grow with better websites, Google presence and social media.',
                'company_phone' => '+91 90000 00000',
                'company_email' => 'hello@youragency.example.com',
                'privacy_notice' => 'This page records which sections you view and which tools you use, so we can follow up with what is actually relevant to you.',
            ],
            'preview' => true,
        ]);
    }

    private function sampleSections(SmartPageTemplate $template): Collection
    {
        $content = [
            'intro' => ['content' => 'A few ideas we put together specifically for your business.'],
            'website_audit' => ['data' => [
                'observation' => 'The site loads in about 6 seconds on mobile and has no online booking.',
                'problem' => 'Most enquiries happen on a phone, so slow pages lose leads before the page is even read.',
                'recommendation' => 'A lightweight mobile-first site with booking on the first screen.',
            ]],
            'google_audit' => ['data' => [
                'rating' => '4.1',
                'reviews' => '38',
                'opportunity' => 'Nearby competitors average 4.7 with 150+ reviews.',
                'recommendation' => 'Automate a review request after every visit.',
            ]],
            'instagram_audit' => ['data' => [
                'observation' => 'Posting roughly twice a month, mostly reposts.',
                'problem' => 'Original before/after content is what actually drives local discovery.',
                'recommendation' => 'A simple weekly posting routine.',
            ]],
            'free_tools' => ['data' => ['tools' => ['revenue', 'no_show', 'profit']]],
            'solution' => ['data' => [
                'what' => 'A mobile-first booking site, Google Business optimisation and a review automation flow.',
                'why' => 'These three fix the places where enquiries are being lost today.',
                'benefits' => "Fewer missed enquiries\nHigher local ranking\nMore repeat bookings",
            ]],
            'portfolio' => ['data' => ['images' => []]],
            'cta' => ['title' => 'Ready when you are', 'content' => 'Happy to walk through any of this in a short call.'],
        ];

        return collect($template->sections)
            ->values()
            ->map(function (string $type, int $i) use ($content) {
                return new SmartPageSection(array_merge([
                    'section_type' => $type,
                    'display_order' => $i,
                    'enabled' => true,
                ], $content[$type] ?? []));
            });
    }
}
