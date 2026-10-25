<?php
/**
 * Footer público / landing.
 *
 * @var array $landing
 * @var object|null $setting
 * @var bool $isPublicLanding
 */
$CI = &get_instance();
if (! isset($setting) || ! is_object($setting)) {
    $setting = method_exists($CI, 'getPlatformSettings') ? $CI->getPlatformSettings() : (object) [];
}
$L = (isset($landing) && is_array($landing)) ? $landing : [];
$t = static function (string $key, string $fallback = '') use ($L): string {
    $v = trim((string) ($L[$key] ?? ''));

    return $v !== '' ? $v : $fallback;
};
$isLanding = ! empty($isPublicLanding);
?>
<?php if ($isLanding) : ?>
<footer class="fv-footer">
  <div class="fv-footer__top">
    <div class="fv-wrap fv-footer__grid">
      <div class="fv-footer__brand">
        <img src="<?= base_url('frontend/img/landing/logo-on-dark.png') ?>"
             alt="Fullvendor"
             width="170"
             height="30"
             class="fv-footer__logo">
        <p><?= htmlspecialchars($t('footer_tagline'), ENT_QUOTES, 'UTF-8') ?></p>
        <ul class="fv-footer__social">
          <?php if (! empty($setting->facebook)) : ?>
            <li><a href="<?= htmlspecialchars((string) $setting->facebook, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="fa fa-facebook"></i></a></li>
          <?php endif; ?>
          <?php if (! empty($setting->twitter)) : ?>
            <li><a href="<?= htmlspecialchars((string) $setting->twitter, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="Twitter"><i class="fa fa-twitter"></i></a></li>
          <?php endif; ?>
          <?php if (! empty($setting->linkedin)) : ?>
            <li><a href="<?= htmlspecialchars((string) $setting->linkedin, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn"><i class="fa fa-linkedin"></i></a></li>
          <?php endif; ?>
          <?php if (! empty($setting->instagram)) : ?>
            <li><a href="<?= htmlspecialchars((string) $setting->instagram, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="fa fa-instagram"></i></a></li>
          <?php endif; ?>
        </ul>
      </div>
      <div class="fv-footer__col">
        <h5><?= htmlspecialchars($t('footer_contact'), ENT_QUOTES, 'UTF-8') ?></h5>
        <ul>
          <?php if (! empty($setting->address)) : ?>
            <li><span><?= htmlspecialchars($t('footer_visit'), ENT_QUOTES, 'UTF-8') ?>:</span> <?= htmlspecialchars((string) $setting->address, ENT_QUOTES, 'UTF-8') ?></li>
          <?php endif; ?>
          <?php if (! empty($setting->phone)) : ?>
            <li><span><?= htmlspecialchars($t('footer_call'), ENT_QUOTES, 'UTF-8') ?>:</span> <a href="tel:<?= htmlspecialchars((string) $setting->phone, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $setting->phone, ENT_QUOTES, 'UTF-8') ?></a></li>
          <?php endif; ?>
          <?php if (! empty($setting->email)) : ?>
            <li><span><?= htmlspecialchars($t('footer_email'), ENT_QUOTES, 'UTF-8') ?>:</span> <a href="mailto:<?= htmlspecialchars((string) $setting->email, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $setting->email, ENT_QUOTES, 'UTF-8') ?></a></li>
          <?php endif; ?>
        </ul>
      </div>
      <div class="fv-footer__col">
        <h5><?= htmlspecialchars($t('footer_company'), ENT_QUOTES, 'UTF-8') ?></h5>
        <ul>
          <li><a href="<?= base_url('terms-and-conditions') ?>"><?= htmlspecialchars($t('footer_terms'), ENT_QUOTES, 'UTF-8') ?></a></li>
          <li><a href="<?= htmlspecialchars(base_url('policy?lang=' . rawurlencode((string) ($landingLocale ?? 'es'))), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('footer_privacy'), ENT_QUOTES, 'UTF-8') ?></a></li>
          <li><a href="<?= base_url('copyright') ?>"><?= htmlspecialchars($t('footer_copyright'), ENT_QUOTES, 'UTF-8') ?></a></li>
        </ul>
      </div>
    </div>
  </div>
  <div class="fv-footer__bottom">
    <p>Copyright &copy; <?= date('Y') ?> <?= defined('SITENAME') ? htmlspecialchars((string) SITENAME, ENT_QUOTES, 'UTF-8') : 'FULLVENDOR' ?> — <?= htmlspecialchars($t('footer_rights'), ENT_QUOTES, 'UTF-8') ?></p>
    <p><?= htmlspecialchars($t('footer_version', 'Version'), ENT_QUOTES, 'UTF-8') ?> <?= defined('APP_VERSION') ? htmlspecialchars((string) APP_VERSION, ENT_QUOTES, 'UTF-8') : 'dev' ?></p>
  </div>
</footer>
<script src="<?= base_url('frontend/') ?>js/jquery.min.js"></script>
<script src="<?= base_url('frontend/') ?>js/bootstrap.min.js"></script>
<?php else : ?>
<footer class="footer-area">
  <div class="footer-top-area">
    <div class="container">
      <div class="row">
        <div class="col-lg-4 col-md-12 footer-link">
          <img src="<?= base_url('frontend/logo.png') ?>" class="footerlogo" alt="Fullvendor">
          <ul class="social_link">
            <li><a href="<?= htmlspecialchars((string) ($setting->facebook ?? ''), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><i class="fa fa-facebook"></i></a></li>
            <li><a href="<?= htmlspecialchars((string) ($setting->twitter ?? ''), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><i class="fa fa-twitter"></i></a></li>
            <li><a href="<?= htmlspecialchars((string) ($setting->linkedin ?? ''), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><i class="fa fa-linkedin"></i></a></li>
            <li><a href="<?= htmlspecialchars((string) ($setting->instagram ?? ''), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><i class="fa fa-instagram"></i></a></li>
          </ul>
        </div>
        <div class="col-lg-4 col-md-12 footer-link">
          <h5><?= htmlspecialchars($t('footer_contact', 'Contact info'), ENT_QUOTES, 'UTF-8') ?></h5>
          <ul class="list-menu">
            <li><?= htmlspecialchars($t('footer_visit', 'Visit us'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($setting->address ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
            <li><?= htmlspecialchars($t('footer_call', 'Call us'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($setting->phone ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
            <li><?= htmlspecialchars($t('footer_email', 'Email us'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($setting->email ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
          </ul>
        </div>
        <div class="col-lg-4 col-md-12 footer-link">
          <h5><?= htmlspecialchars($t('footer_company', 'Company'), ENT_QUOTES, 'UTF-8') ?></h5>
          <ul class="list-menu">
            <li><a href="<?= base_url('terms-and-conditions') ?>"><?= htmlspecialchars($t('footer_terms', 'Terms & Conditions'), ENT_QUOTES, 'UTF-8') ?></a></li>
            <li><a href="<?= base_url('policy') ?>"><?= htmlspecialchars($t('footer_privacy', 'Privacy Policy'), ENT_QUOTES, 'UTF-8') ?></a></li>
            <li><a href="<?= base_url('copyright') ?>"><?= htmlspecialchars($t('footer_copyright', 'Copyright'), ENT_QUOTES, 'UTF-8') ?></a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="container">
      <div class="copyright-text text-center">
        <p>Copyright &copy; <?= date('Y') ?> <?= defined('SITENAME') ? SITENAME : 'FULLVENDOR' ?> - <?= htmlspecialchars($t('footer_rights', 'All rights reserved.'), ENT_QUOTES, 'UTF-8') ?></p>
        <p><?= htmlspecialchars($t('footer_version', 'Version'), ENT_QUOTES, 'UTF-8') ?> <?= defined('APP_VERSION') ? APP_VERSION : 'dev' ?></p>
      </div>
    </div>
  </div>
</footer>
<script src="<?= base_url('frontend/') ?>js/jquery.min.js"></script>
<script src="<?= base_url('frontend/') ?>js/bootstrap.min.js"></script>
<script src="<?= base_url('frontend/') ?>js/easing.min.js"></script>
<script src="<?= base_url('frontend/') ?>js/owl.carousel.min.js"></script>
<script src="<?= base_url('frontend/') ?>js/magnific-popup.min.js"></script>
<script src="<?= base_url('frontend/') ?>js/jquery.waypoints.min.js"></script>
<script src="<?= base_url('frontend/') ?>js/jquery.counterup.js"></script>
<script src="<?= base_url('frontend/') ?>js/map.js"></script>
<script src="<?= base_url('frontend/') ?>js/slide-slider.js"></script>
<script src="<?= base_url('frontend/') ?>js/main.js"></script>
<?php endif; ?>
