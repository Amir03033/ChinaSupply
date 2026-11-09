<?php

namespace Database\Seeders;

use App\Actions\Categories\SaveCategory;
use App\Enums\PublicationStatus;
use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Seeds the initial taxonomy only when the categories table is empty, so it
 * never overwrites changes made in Filament.
 */
class CategorySeeder extends Seeder
{
    /** Main categories shown as featured on the homepage initially. */
    private const FEATURED = [
        'Packaging & Printing', 'Home, Living & Interior', 'Clothing, Textiles & Fashion',
        'Electronics & Technology', 'Gifts, Promotional Products & Merchandise', 'Custom Manufacturing & OEM/ODM',
        'Beauty, Cosmetics & Personal Care', 'Sports, Fitness & Outdoor',
    ];

    public function run(SaveCategory $save): void
    {
        if (Category::exists()) {
            $this->command?->info('Categories already present; skipping taxonomy seed.');

            return;
        }

        foreach (require database_path('data/categories.php') as $i => $node) {
            $this->seedNode($save, $node, null, $i, true);
        }
    }

    /**
     * @param  array<int, mixed>  $node
     */
    private function seedNode(SaveCategory $save, array $node, ?int $parentId, int $sort, bool $isRoot): void
    {
        [$en, $nl] = $node;
        $icon = $isRoot ? ($node[2] ?? null) : null;
        $children = $isRoot ? ($node[3] ?? []) : ($node[2] ?? []);

        $category = $save->handle(null, [
            'parent_id' => $parentId,
            'status' => PublicationStatus::Active->value,
            'is_featured' => $isRoot && in_array($en, self::FEATURED, true),
            'icon' => $icon ? 'heroicon-o-'.$icon : null,
            'sort_order' => $sort,
        ], [
            'nl' => ['name' => $nl],
            'en' => ['name' => $en],
        ]);

        foreach ($children as $j => $child) {
            $this->seedNode($save, $child, $category->id, $j, false);
        }
    }
}
