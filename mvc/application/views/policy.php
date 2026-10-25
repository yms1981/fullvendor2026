<?php
/**
 * Política de privacidad (landing).
 *
 * @var array $landing
 * @var string|null $landingLocale
 */
$L = (isset($landing) && is_array($landing)) ? $landing : [];
$t = static function (string $key, string $fallback = '') use ($L): string {
    $v = trim((string) ($L[$key] ?? ''));

    return $v !== '' ? $v : $fallback;
};
$bodyHtml = (string) ($L['privacy_body_html'] ?? '');

$toc = [];
if ($bodyHtml !== '' && preg_match_all('/<h2[^>]*>(.*?)<\/h2>/is', $bodyHtml, $matches)) {
    foreach ($matches[1] as $i => $label) {
        $normalized = str_replace(['</span><span', '</span> <span'], ['</span> <span', '</span> <span'], (string) $label);
        $plain = trim(html_entity_decode(strip_tags($normalized), ENT_QUOTES, 'UTF-8'));
        $plain = preg_replace('/^\d+\s*/', '', $plain) ?? $plain;
        $plain = trim((string) $plain);
        if ($plain === '') {
            continue;
        }
        $toc[] = [
            'id' => 'fv-legal-s' . ($i + 1),
            'label' => $plain,
        ];
    }
    $n = 0;
    $bodyHtml = preg_replace_callback(
        '/<h2([^>]*)>/i',
        static function ($m) use (&$n) {
            $n++;

            return '<h2' . $m[1] . ' id="fv-legal-s' . $n . '">';
        },
        $bodyHtml
    );
}
?>
<main class="fv-legal">
  <header class="fv-legal__hero">
    <div class="fv-wrap">
      <p class="fv-legal__eyebrow"><?= htmlspecialchars($t('privacy_title', 'Privacy Policy'), ENT_QUOTES, 'UTF-8') ?></p>
      <h1><?= htmlspecialchars($t('privacy_subtitle', 'Our Policy'), ENT_QUOTES, 'UTF-8') ?></h1>
      <p class="fv-legal__hero-lead"><?= htmlspecialchars($t('privacy_hero_lead', ''), ENT_QUOTES, 'UTF-8') ?></p>
      <div class="fv-legal__hero-meta">
        <span><?= htmlspecialchars($t('privacy_updated_label', ''), ENT_QUOTES, 'UTF-8') ?></span>
        <span class="fv-legal__dot" aria-hidden="true"></span>
        <span><?= htmlspecialchars($t('privacy_reading_label', ''), ENT_QUOTES, 'UTF-8') ?></span>
      </div>
    </div>
  </header>

  <div class="fv-legal__body">
    <div class="fv-wrap fv-legal__layout">
      <?php if ($toc !== []) : ?>
        <aside class="fv-legal__toc" aria-label="<?= htmlspecialchars($t('privacy_toc_label', 'Contents'), ENT_QUOTES, 'UTF-8') ?>">
          <p class="fv-legal__toc-title"><?= htmlspecialchars($t('privacy_toc_label', 'Contents'), ENT_QUOTES, 'UTF-8') ?></p>
          <ol class="fv-legal__toc-list">
            <?php foreach ($toc as $item) : ?>
              <li>
                <a href="#<?= htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') ?>">
                  <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ol>
        </aside>
      <?php endif; ?>

      <div class="fv-legal__panel">
        <article class="fv-legal__content">
          <?php if ($bodyHtml !== '') : ?>
            <?= $bodyHtml ?>
          <?php else : ?>
            <div class="fv-legal__intro">
              <p><?= htmlspecialchars($t('privacy_body_missing', 'Privacy policy content is temporarily unavailable. Please try again later.'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
          <?php endif; ?>
        </article>
      </div>
    </div>
  </div>
</main>
