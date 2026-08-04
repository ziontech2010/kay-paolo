<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class KayPaoloContent
{
    private const PATH = 'kay-paolo/content.json';

    public function all(): array
    {
        $stored = [];

        if (Storage::disk('local')->exists(self::PATH)) {
            $decoded = json_decode(Storage::disk('local')->get(self::PATH), true);
            $stored = is_array($decoded) ? $decoded : [];
        }

        return array_replace($this->defaults(), array_intersect_key($stored, $this->defaults()));
    }

    public function update(array $values): array
    {
        $content = array_replace($this->all(), array_intersect_key($values, $this->defaults()));

        Storage::disk('local')->put(self::PATH, json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $content;
    }

    public function defaults(): array
    {
        return [
            'meta_description' => 'Kay Paolo Shipping moves freight across 120+ countries with live quotes, shipment creation, documents, and tracking.',
            'who_headline' => "Any location, any time - we'll be there",
            'who_body' => 'Kay Paolo Shipping is a full-service logistics provider dedicated to streamlining your global supply chain. This Laravel Blade portal keeps the Kay Paolo experience separate while using a third-party shipping API for login, quotes, shipment creation, and tracking.',
            'who_image_primary' => 'kay-paolo/assets/images/about-warehouse.png',
            'who_image_secondary' => 'kay-paolo/assets/images/about-delivery.png',
            'process_step_1_title' => 'Consult & Quote',
            'process_step_1_body' => 'Share shipment details and generate live rate cards through the Kay Paolo shipping bridge.',
        ];
    }
}
