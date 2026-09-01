<?php

namespace App\Services\Presentation;

use App\Models\Presentation\Presentation;
use Illuminate\Support\Str;

class PresentationBuilderService
{
    public const SECTIONS = [
        'overview' => 'Overview',
        'requirements' => 'Your Requirement',
        'solution' => 'Recommended Solution',
        'features' => 'Key Features',
        'media' => 'Product Experience',
        'demo' => 'Live Demo',
        'pricing' => 'Investment',
        'timeline' => 'Implementation',
        'support' => 'Support',
        'company' => 'About Us',
        'terms' => 'Terms',
        'contact' => 'Next Step',
    ];

    public function createDefaults(Presentation $presentation): void
    {
        foreach (array_keys(self::SECTIONS) as $index => $key) {
            $presentation->sections()->firstOrCreate(
                ['section_key' => $key],
                [
                    'sort_order' => ($index + 1) * 10,
                    'is_enabled' => true,
                ]
            );
        }
    }

    public function makeReference(): string
    {
        do {
            $reference = 'SP-'.now()->format('ymd').'-'.strtoupper(Str::random(5));
        } while (Presentation::withTrashed()->where('reference_no', $reference)->exists());

        return $reference;
    }

    public function makeToken(): string
    {
        do {
            $token = Str::random(40);
        } while (Presentation::withTrashed()->where('public_token', $token)->exists());

        return $token;
    }
}
