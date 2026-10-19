<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Product::query()->with(['category', 'variants'])->where('is_active', true);

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->string('category')));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('q')) {
            $q = mb_strtolower($request->string('q'));
            $matching = collect(trans('app.products'))
                ->filter(function ($entry) use ($q) {
                    $blob = mb_strtolower(($entry['name'] ?? '').' '.($entry['description'] ?? ''));

                    return str_contains($blob, $q);
                })
                ->keys();
            $query->where(function ($builder) use ($matching, $q) {
                $builder->whereIn('slug', $matching)->orWhere('slug', 'like', '%'.$q.'%');
            });
        }

        $sort = $request->string('sort', 'featured');
        match ((string) $sort) {
            'price_asc' => $query->orderBy('price_usd_cents'),
            'price_desc' => $query->orderByDesc('price_usd_cents'),
            default => $query->orderByDesc('is_featured')->orderBy('id'),
        };

        $products = $query->get()->map->toCardArray();

        return Inertia::render('Welcome', [
            'seo' => page_seo(__('app.welcome.meta_title'), __('app.welcome.meta_description')),
            'products' => $products,
            'categories' => Category::query()->orderBy('sort_order')->get()->map(fn (Category $c) => [
                'slug' => $c->slug,
                'name' => $c->localizedName(),
            ]),
            'filters' => [
                'category' => $request->string('category')->toString() ?: null,
                'type' => $request->string('type')->toString() ?: null,
                'q' => $request->string('q')->toString() ?: null,
                'sort' => (string) $sort,
            ],
        ]);
    }

    public function show(Request $request): Response
    {
        $slug = (string) $request->route('slug');
        $product = Product::query()->with(['category', 'variants'])->where('slug', $slug)->where('is_active', true)->firstOrFail();

        return Inertia::render('Catalog/Show', [
            'seo' => page_seo($product->localizedName(), $product->localizedDescription()),
            'product' => $product->toCardArray(),
        ]);
    }
}
