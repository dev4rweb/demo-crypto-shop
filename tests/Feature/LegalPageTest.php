<?php

test('privacy and terms pages return 200', function () {
    $this->get('/privacy')->assertOk()->assertSee('Privacy Policy', false);
    $this->get('/terms')->assertOk()->assertSee('Terms of Service', false);
});

test('privacy and terms are available in every locale', function () {
    foreach (['en', 'zh', 'fr', 'es', 'ar', 'pt', 'ru'] as $locale) {
        $this->get('/'.$locale.'/privacy')->assertOk();
        $this->get('/'.$locale.'/terms')->assertOk();

        expect(trans('app.legal.privacy.title', [], $locale))->not->toBe('app.legal.privacy.title')
            ->and(trans('app.legal.terms.title', [], $locale))->not->toBe('app.legal.terms.title');
    }
});
