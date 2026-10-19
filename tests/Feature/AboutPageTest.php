<?php

test('about copy stays user-facing in every locale', function () {
    $banned = '/webhook|MAIL_MAILER|TELEGRAM_BOT_TOKEN|NOWPAYMENTS|\.env|\bcron\b/i';

    foreach (['en', 'zh', 'fr', 'es', 'ar', 'pt', 'ru'] as $locale) {
        $about = trans('app.about', [], $locale);
        $blob = json_encode($about, JSON_UNESCAPED_UNICODE);

        expect($blob)->not->toMatch($banned)
            ->and(trans('app.about.lead', [], $locale))->not->toBe('app.about.lead')
            ->and(trans('app.about.try_title', [], $locale))->not->toBe('app.about.try_title')
            ->and(trans('app.telegram.disabled', [], $locale))->not->toMatch($banned)
            ->and(trans('app.pay.simulate_hint', [], $locale))->not->toMatch($banned);
    }
});

test('about page renders for default locale', function () {
    $this->get('/about')->assertOk()->assertSee('Nexus', false);
});
