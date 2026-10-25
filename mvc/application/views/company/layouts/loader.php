
<?php if (!empty($this->session->flashdata('loader'))): ?>
<?php if ($this->session->flashdata('loader')): ?>
    <div class="preloader">
        <div class="loading-center d-flex align-items-center justify-content-center">
            <div class="d-flex flex-column justify-content-center align-items-center">
                <img  width="100px" src="<?= base_url('images/logos/logo.png') ?>" alt="">
                <div>
                    <div class="object object_one"></div>
                    <div class="object object_two"></div>
                    <div class="object object_three"></div>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>
<?php endif ?>
