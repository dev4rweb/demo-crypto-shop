<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function show(Request $request, string $slug): Response
    {
        $this->assertCanDownload($request, $slug);

        $product = Product::query()->where('slug', $slug)->first();

        return Inertia::render('Downloads/Show', [
            'seo' => page_seo(__('app.downloads.meta_title'), __('app.downloads.body')),
            'slug' => $slug,
            'name' => $product?->localizedName() ?? $slug,
            'fileName' => $slug.'.txt',
            'downloadUrl' => \Illuminate\Support\Facades\URL::signedRoute('downloads.file', ['slug' => $slug]),
        ]);
    }

    public function file(Request $request, string $slug): StreamedResponse
    {
        $this->assertCanDownload($request, $slug);

        $name = $slug.'.txt';
        $body = "Nexus demo download\nProduct: {$slug}\nThis is a demonstration file from the Nexus crypto shop.\n";

        return response()->streamDownload(static function () use ($body): void {
            echo $body;
        }, $name, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function assertCanDownload(Request $request, string $slug): void
    {
        if ($request->hasValidSignature()) {
            return;
        }

        $user = $request->user();
        abort_unless($user, 403);

        if ($user->isStaff()) {
            return;
        }

        $allowed = Order::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['paid', 'overpaid'])
            ->whereHas('items', function ($query) use ($slug): void {
                $query->where(function ($inner) use ($slug): void {
                    $inner->where('delivery_payload->url', 'like', '%/downloads/'.$slug.'%')
                        ->orWhereHas('product', fn ($product) => $product->where('slug', $slug));
                });
            })
            ->exists();

        abort_unless($allowed, 403);
    }
}
