<?php

namespace App\Services\SmartLink;

use App\Models\SmartLink\Prospect;
use App\Models\SmartLink\SmartLinkModel;
use App\Models\SmartLink\SmartPage;
use App\Models\SmartLink\SmartPageSection;
use App\Models\SmartLink\SmartPageTemplate;
use Illuminate\Support\Facades\DB;

/**
 * Creates the Prospect -> Smart Page -> Smart Link -> Tracking ID chain
 * described in section 4 of the scope document.
 */
class SmartLinkService
{
    /** Unambiguous alphabet: no 0/O/1/l/I so slugs survive being read aloud. */
    private const ALPHABET = 'abcdefghijkmnopqrstuvwxyz23456789';

    public function createForProspect(
        Prospect $prospect,
        SmartPageTemplate $template,
        array $pageAttributes = []
    ): SmartPage {
        return DB::transaction(function () use ($prospect, $template, $pageAttributes) {
            $link = SmartLinkModel::create([
                'prospect_id' => $prospect->id,
                'slug' => $this->makeSlug(),
                'status' => 'active',
            ]);

            $page = SmartPage::create(array_merge([
                'prospect_id' => $prospect->id,
                'smart_link_id' => $link->id,
                'template_id' => $template->id,
                'heading' => $prospect->business_name,
                'subheading' => 'A few ideas we put together specifically for your business.',
                'cta_text' => 'Let us discuss this',
                'cta_type' => 'whatsapp',
                'cta_url' => $prospect->phone ? 'https://wa.me/'.preg_replace('/\D+/', '', $prospect->phone) : null,
                'status' => 'published',
            ], $pageAttributes));

            $this->applyTemplate($page, $template);

            return $page;
        });
    }

    /** Creates the section rows for a template, keeping any content already entered. */
    public function applyTemplate(SmartPage $page, SmartPageTemplate $template): void
    {
        $types = $template->sections ?: array_keys(SmartPageTemplate::SECTION_LABELS);

        foreach ($types as $index => $type) {
            SmartPageSection::firstOrCreate(
                ['smart_page_id' => $page->id, 'section_type' => $type],
                [
                    'title' => SmartPageTemplate::sectionLabel($type),
                    'display_order' => ($index + 1) * 10,
                    'enabled' => true,
                    'data' => $this->defaultData($type),
                ]
            );
        }

        // Sections outside the template stay in the database but are switched off,
        // so a salesperson can re-enable one without losing what they wrote.
        SmartPageSection::where('smart_page_id', $page->id)
            ->whereNotIn('section_type', $types)
            ->update(['enabled' => false]);
    }

    public function makeSlug(): string
    {
        do {
            $slug = '';
            for ($i = 0; $i < 6; $i++) {
                $slug .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while (SmartLinkModel::where('slug', $slug)->exists());

        return $slug;
    }

    private function defaultData(string $type): array
    {
        return match ($type) {
            'website_audit', 'instagram_audit' => ['observation' => '', 'problem' => '', 'recommendation' => ''],
            'google_audit' => ['rating' => '', 'reviews' => '', 'opportunity' => '', 'recommendation' => ''],
            'free_tools' => ['tools' => ['revenue', 'no_show', 'profit']],
            'solution' => ['what' => '', 'why' => '', 'benefits' => ''],
            'portfolio' => ['images' => []],
            default => [],
        };
    }
}
