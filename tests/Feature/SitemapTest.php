<?php

test('sitemap lists locales catalog and about', function () {
    seedCatalog();

    $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();

    expect($xml)->toContain(url('/about'))
        ->toContain(url('/privacy'))
        ->toContain(url('/terms'))
        ->toContain('/zh/about')
        ->toContain('/zh/privacy')
        ->toContain('/ru/terms')
        ->toContain('/catalog/neon-storefront-kit')
        ->toContain('/ru/about');
});

test('robots.txt points at the sitemap', function () {
    $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: https://demo-crypto-shop.dev4rweb.com/sitemap.xml', false);
});
