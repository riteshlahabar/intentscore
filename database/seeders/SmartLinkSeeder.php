<?php

namespace Database\Seeders;

use App\Models\SmartLink\IntentScoreRule;
use App\Models\SmartLink\Prospect;
use App\Models\SmartLink\SmartPageTemplate;
use App\Models\User;
use App\Services\SmartLink\IntentScoreService;
use App\Services\SmartLink\SmartLinkService;
use Illuminate\Database\Seeder;

class SmartLinkSeeder extends Seeder
{
    public function run(): void
    {
        foreach (IntentScoreRule::DEFAULTS as $rule) {
            IntentScoreRule::updateOrCreate(['event_key' => $rule['event_key']], $rule);
        }

        foreach (SmartPageTemplate::DEFAULTS as $template) {
            SmartPageTemplate::updateOrCreate(['slug' => $template['slug']], $template);
        }

        $admin = User::where('email', 'admin@example.com')->first();
        $template = SmartPageTemplate::where('slug', 'grooming-business')->first();

        if (! $admin || ! $template || Prospect::exists()) {
            return;
        }

        $prospect = Prospect::create([
            'business_name' => 'ABC Grooming',
            'contact_name' => 'Sample Contact',
            'website' => 'https://abcgrooming.example.com',
            'email' => 'hello@abcgrooming.example.com',
            'phone' => '919999999999',
            'industry' => 'Pet Grooming',
            'location' => 'Pune, Maharashtra',
            'offer' => 'Website + Google Business growth package',
            'salesperson_id' => $admin->id,
            'status' => 'new',
        ]);

        $page = app(SmartLinkService::class)->createForProspect($prospect, $template, [
            'personalized_message' => 'Hi, we looked at how ABC Grooming shows up online and put together a few specific ideas that should be quick wins.',
        ]);

        $content = [
            'intro' => ['content' => 'A few ideas we put together specifically for your business.'],
            'website_audit' => ['data' => [
                'observation' => 'The site loads in about 6 seconds on mobile and has no online booking.',
                'problem' => 'Most grooming enquiries happen on a phone, so slow pages lose bookings before the page is even read.',
                'recommendation' => 'A lightweight mobile-first site with booking on the first screen.',
            ]],
            'google_audit' => ['data' => [
                'rating' => '4.1',
                'reviews' => '38',
                'opportunity' => 'Competitors nearby average 4.7 with 150+ reviews.',
                'recommendation' => 'Automate a review request after every appointment.',
            ]],
            'instagram_audit' => ['data' => [
                'observation' => 'Posting roughly twice a month, mostly reposts.',
                'problem' => 'Before/after grooming content is what actually drives local discovery.',
                'recommendation' => 'A simple weekly before/after posting routine.',
            ]],
            'solution' => ['data' => [
                'what' => 'A mobile-first booking site, Google Business optimisation and a review automation flow.',
                'why' => 'These three fix the places where enquiries are being lost today.',
                'benefits' => "Fewer missed enquiries\nHigher local ranking\nMore repeat bookings",
            ]],
        ];

        foreach ($content as $type => $values) {
            $page->sections()->where('section_type', $type)->first()?->update($values);
        }

        app(IntentScoreService::class)->recalculate($prospect);
    }
}
