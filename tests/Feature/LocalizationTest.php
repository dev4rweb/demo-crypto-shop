<?php

test('russian is reachable only by a direct prefix and is hidden from the selector', function () {
    $this->get('/ru')->assertOk();
    $this->get('/ru/about')->assertOk()->assertSee('Nexus', false);

    $html = $this->get('/')->assertOk()->getContent();
    expect($html)->not->toContain('value="ru"');
});

test('arabic pages are marked rtl', function () {
    $this->get('/ar')->assertOk()->assertSee('dir="rtl"', false);
});

test('locale prefix keeps catalog and about working', function () {
    seedCatalog();

    foreach (['en', 'zh', 'fr', 'es', 'ar', 'pt'] as $locale) {
        $this->get('/'.$locale)->assertOk();
        $this->get('/'.$locale.'/about')->assertOk();
        $this->get('/'.$locale.'/catalog/neon-storefront-kit')->assertOk();
    }
});
