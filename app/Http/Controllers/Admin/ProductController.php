<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Products', [
            'seo' => page_seo(__('app.admin.products_title'), __('app.admin.meta_description')),
            'products' => Product::query()->with('category')->latest()->get(),
            'categories' => Category::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name'] ?? Str::random(8));
        unset($data['name']);

        Product::query()->create($data);

        return back()->with('success', __('app.common.saved'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);
        unset($data['name']);
        $product->update($data);

        return back()->with('success', __('app.common.saved'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return back()->with('success', __('app.common.saved'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:160'],
            'category_id' => ['required', 'exists:categories,id'],
            'type' => ['required', 'in:template,license,course,gift_card'],
            'price_usd_cents' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'is_digital' => ['boolean'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'bento_size' => ['nullable', 'in:sm,md,lg'],
            'image' => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_digital'] = $request->boolean('is_digital', true);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['bento_size'] = $data['bento_size'] ?? 'sm';

        if ($product === null && empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return $data;
    }
}
