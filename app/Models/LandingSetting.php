<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingSetting extends Model
{
    protected $table = 'landing_settings';

    protected $fillable = [
        'landing_edisi_lomba_id',
        'hero_badge',
        'hero_title',
        'hero_subtitle',
        'about_text',
        'cta_badge',
        'cta_label',
        'cta_url',
        'faq_items',
    ];

    protected function casts(): array
    {
        return [
            'faq_items' => 'array',
        ];
    }
}
