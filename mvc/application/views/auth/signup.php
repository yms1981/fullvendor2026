<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Sign Up | <?= SITENAME ?></title>
  <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('uploads/logos/isologo.png') ?>">
  <link rel="stylesheet" href="<?= base_url('backend/vendors/mdi/css/materialdesignicons.min.css')?>">
  <link rel="stylesheet" href="<?= base_url('backend/vendors/flag-icon-css/css/flag-icon.min.css')?>">
  <link rel="stylesheet" href="<?= base_url('backend/vendors/css/vendor.bundle.base.css')?>">
  <link rel="stylesheet" href="<?= base_url('backend/css/style.css')?>">
  <link rel="shortcut icon" href="<?= base_url('backend/images/favicon.png')?>" />
</head>
<body>
  <style>
    .login-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #0a0a0a;
      background-image: 
        linear-gradient(rgba(0, 0, 0, 0.75), rgba(0, 0, 0, 0.85)),
        url('https://images.unsplash.com/photo-1449824913935-59a10b8d2000?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      background-attachment: fixed;
      padding: 20px;
      position: relative;
      overflow: hidden;
    }
    .login-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-image: url('https://images.unsplash.com/photo-1449824913935-59a10b8d2000?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
      background-size: cover;
      background-position: center;
      filter: blur(8px) brightness(0.4);
      transform: scale(1.1);
      z-index: 0;
    }
    .login-container::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: 
        linear-gradient(135deg, rgba(0, 0, 0, 0.8) 0%, rgba(26, 0, 0, 0.9) 100%),
        radial-gradient(circle at 30% 40%, rgba(220, 53, 69, 0.2) 0%, transparent 60%);
      z-index: 0;
      pointer-events: none;
    }
    .login-card {
      width: 100%;
      max-width: 600px;
      background: rgba(255, 255, 255, 0.98);
      border-radius: 12px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(220, 53, 69, 0.1);
      padding: 40px;
      position: relative;
      z-index: 1;
      backdrop-filter: blur(10px);
    }
    .login-logo {
      display: flex;
      justify-content: center;
      align-items: center;
      margin-bottom: 30px;
    }
    .login-logo img {
      max-width: 200px;
      max-height: 120px;
      object-fit: contain;
    }
    .login-title {
      text-align: center;
      margin-bottom: 30px;
    }
    .login-title h4 {
      font-size: 24px;
      font-weight: 600;
      color: #333;
      margin-bottom: 8px;
    }
    .login-form .form-group {
      margin-bottom: 20px;
    }
    .login-form .form-control-lg {
      padding: 12px 16px;
      font-size: 16px;
      border-radius: 6px;
      border: 1px solid #ddd;
      transition: all 0.3s ease;
    }
    .login-form .form-control-lg:focus {
      border-color: #dc3545;
      box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
      outline: none;
    }
    .login-btn {
      width: 100%;
      padding: 14px;
      font-size: 16px;
      font-weight: 600;
      border-radius: 6px;
      margin-top: 10px;
      background: #dc3545;
      border: none;
      color: white;
      transition: background-color 0.2s ease, transform 0.2s ease;
    }
    .login-btn:hover {
      background: #c82333;
      transform: translateY(-1px);
      box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
    }
    .login-btn:active {
      transform: translateY(0);
    }
    .auth-link {
      color: #333;
      text-decoration: none;
      transition: color 0.2s ease;
    }
    .auth-link:hover {
      color: #dc3545;
      text-decoration: none;
    }
    .form-row {
      display: flex;
      gap: 15px;
    }
    .form-row .form-group {
      flex: 1;
    }
    @media (max-width: 576px) {
      .login-card {
        padding: 30px 20px;
      }
      .form-row {
        flex-direction: column;
        gap: 0;
      }
    }
  </style>
  <div class="login-container">
    <div class="login-card">
      <div class="login-logo">
        <a href="<?=base_url()?>">
          <img src="<?= base_url('uploads/logos/fullvendor-login-brand.png') ?>" alt="FULLVENDOR Logo" style="max-width: 250px; height: auto;">
        </a>
      </div>
      <div class="login-title">
        <h4>Create Account</h4>
        <p class="text-muted">Register your company</p>
      </div>
      <form class="login-form" action="<?= base_url('signup') ?>" method="post">
        <?php if (!empty($msg)): ?>
          <div class="mb-3">
            <?= $msg ?>
          </div>
        <?php endif ?>

        <h5 class="mb-3" style="color: #333; font-size: 18px; border-bottom: 2px solid #dc3545; padding-bottom: 10px;">Company Information</h5>
        
        <div class="form-group">
          <label>Company Name <span class="text-danger">*</span></label>
          <input type="text" name="company_name" class="form-control form-control-lg" required placeholder="Company Name">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Company Email <span class="text-danger">*</span></label>
            <input type="email" name="company_email" class="form-control form-control-lg" required placeholder="company@example.com">
          </div>
          <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="company_phone" class="form-control form-control-lg" placeholder="Phone Number">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Tax ID</label>
            <input type="text" name="company_tax_id" class="form-control form-control-lg" placeholder="Tax ID">
          </div>
          <div class="form-group">
            <label>Website</label>
            <input type="url" name="company_website" class="form-control form-control-lg" placeholder="https://example.com">
          </div>
        </div>

        <div class="form-group">
          <label>Language <span class="text-danger">*</span></label>
          <select name="language_id" class="form-control form-control-lg" required>
            <option value="1">Español</option>
            <option value="2">English</option>
          </select>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Password <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control form-control-lg" required placeholder="Password (min. 8 characters)" minlength="8">
          </div>
          <div class="form-group">
            <label>Confirm Password <span class="text-danger">*</span></label>
            <input type="password" name="password_confirmation" class="form-control form-control-lg" required placeholder="Confirm Password" minlength="8">
          </div>
        </div>

        <button type="submit" class="btn login-btn">SIGN UP</button>
      </form>
    </div>
  </div>
  <script src="<?= base_url('backend/vendors/js/vendor.bundle.base.js')?>"></script>
  <script src="<?= base_url('backend/js/off-canvas.js')?>"></script>
  <script src="<?= base_url('backend/js/hoverable-collapse.js')?>"></script>
  <script src="<?= base_url('backend/js/misc.js')?>"></script>
  <script src="<?= base_url('backend/js/settings.js')?>"></script>
  <script src="<?= base_url('backend/js/todolist.js')?>"></script>
</body>
</html>

