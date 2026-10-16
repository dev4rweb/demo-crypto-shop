<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:80', 'unique:categories,slug'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        $data['slug'] = Str::slug($data['slug']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        Category::query()->create($data);

        return back()->with('success', __('app.common.saved'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:80', 'unique:categories,slug,'.$category->id],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        $category->update($data);

        return back()->with('success', __('app.common.saved'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return back()->with('success', __('app.common.saved'));
    }
}
