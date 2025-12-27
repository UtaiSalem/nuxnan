<?php

namespace Database\Seeders;

use App\Models\PostBackground;
use Illuminate\Database\Seeder;

class PostBackgroundSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $backgrounds = [
            // Solid Colors
            [
                'name' => 'Ocean Blue',
                'type' => 'color',
                'background_color' => '#1E88E5',
                'text_color' => '#FFFFFF',
                'category' => 'colors',
                'sort_order' => 1,
            ],
            [
                'name' => 'Forest Green',
                'type' => 'color',
                'background_color' => '#43A047',
                'text_color' => '#FFFFFF',
                'category' => 'colors',
                'sort_order' => 2,
            ],
            [
                'name' => 'Sunset Orange',
                'type' => 'color',
                'background_color' => '#FB8C00',
                'text_color' => '#FFFFFF',
                'category' => 'colors',
                'sort_order' => 3,
            ],
            [
                'name' => 'Royal Purple',
                'type' => 'color',
                'background_color' => '#7B1FA2',
                'text_color' => '#FFFFFF',
                'category' => 'colors',
                'sort_order' => 4,
            ],
            [
                'name' => 'Cherry Red',
                'type' => 'color',
                'background_color' => '#E53935',
                'text_color' => '#FFFFFF',
                'category' => 'colors',
                'sort_order' => 5,
            ],
            [
                'name' => 'Night Black',
                'type' => 'color',
                'background_color' => '#212121',
                'text_color' => '#FFFFFF',
                'category' => 'colors',
                'sort_order' => 6,
            ],
            [
                'name' => 'Soft Pink',
                'type' => 'color',
                'background_color' => '#F48FB1',
                'text_color' => '#FFFFFF',
                'category' => 'colors',
                'sort_order' => 7,
            ],
            [
                'name' => 'Teal',
                'type' => 'color',
                'background_color' => '#00897B',
                'text_color' => '#FFFFFF',
                'category' => 'colors',
                'sort_order' => 8,
            ],
            
            // Gradients
            [
                'name' => 'Sunset Gradient',
                'type' => 'gradient',
                'background_gradient' => 'linear-gradient(135deg, #FF6B6B 0%, #FFC371 100%)',
                'text_color' => '#FFFFFF',
                'category' => 'gradients',
                'sort_order' => 20,
            ],
            [
                'name' => 'Ocean Gradient',
                'type' => 'gradient',
                'background_gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'text_color' => '#FFFFFF',
                'category' => 'gradients',
                'sort_order' => 21,
            ],
            [
                'name' => 'Forest Gradient',
                'type' => 'gradient',
                'background_gradient' => 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)',
                'text_color' => '#FFFFFF',
                'category' => 'gradients',
                'sort_order' => 22,
            ],
            [
                'name' => 'Pink Dream',
                'type' => 'gradient',
                'background_gradient' => 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
                'text_color' => '#333333',
                'category' => 'gradients',
                'sort_order' => 23,
            ],
            [
                'name' => 'Night Sky',
                'type' => 'gradient',
                'background_gradient' => 'linear-gradient(135deg, #0F2027 0%, #203A43 50%, #2C5364 100%)',
                'text_color' => '#FFFFFF',
                'category' => 'gradients',
                'sort_order' => 24,
            ],
            [
                'name' => 'Rainbow',
                'type' => 'gradient',
                'background_gradient' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                'text_color' => '#FFFFFF',
                'category' => 'gradients',
                'sort_order' => 25,
            ],
            [
                'name' => 'Golden Hour',
                'type' => 'gradient',
                'background_gradient' => 'linear-gradient(135deg, #F37335 0%, #FDC830 100%)',
                'text_color' => '#FFFFFF',
                'category' => 'gradients',
                'sort_order' => 26,
            ],
            [
                'name' => 'Cool Blues',
                'type' => 'gradient',
                'background_gradient' => 'linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%)',
                'text_color' => '#FFFFFF',
                'category' => 'gradients',
                'sort_order' => 27,
            ],
            
            // Celebration
            [
                'name' => 'Birthday',
                'type' => 'gradient',
                'background_gradient' => 'linear-gradient(135deg, #FF61D2 0%, #FE9090 100%)',
                'text_color' => '#FFFFFF',
                'category' => 'celebration',
                'sort_order' => 40,
            ],
            [
                'name' => 'New Year',
                'type' => 'gradient',
                'background_gradient' => 'linear-gradient(135deg, #FFD700 0%, #FF8C00 100%)',
                'text_color' => '#333333',
                'category' => 'celebration',
                'sort_order' => 41,
            ],
            [
                'name' => 'Valentine',
                'type' => 'gradient',
                'background_gradient' => 'linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%)',
                'text_color' => '#FFFFFF',
                'category' => 'celebration',
                'sort_order' => 42,
            ],
            [
                'name' => 'Christmas',
                'type' => 'gradient',
                'background_gradient' => 'linear-gradient(135deg, #1D976C 0%, #93F9B9 100%)',
                'text_color' => '#FFFFFF',
                'category' => 'celebration',
                'sort_order' => 43,
            ],
        ];

        foreach ($backgrounds as $bg) {
            PostBackground::updateOrCreate(
                ['name' => $bg['name']],
                array_merge($bg, [
                    'is_active' => true,
                    'is_premium' => false,
                    'text_alignment' => 'center',
                ])
            );
        }
    }
}
