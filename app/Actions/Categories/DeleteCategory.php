<?php

namespace App\Actions\Categories;

use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DeleteCategory
{
    public function handle(Category $category): void
    {
        if ($category->children()->exists()) {
            throw ValidationException::withMessages([
                'category' => __('admin.categories.errors.has_children'),
            ]);
        }

        // Phase 2 adds: refuse when suppliers or products are attached.

        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }

        $category->delete();
    }
}
