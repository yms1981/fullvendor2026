 <?php
 $CI = &get_instance();
 $setting = method_exists($CI, 'getPlatformSettings') ? $CI->getPlatformSettings() : (object) [];
 ?>
 <!-- Footer Section Start -->
    <footer class="footer-area">
        <div class="footer-top-area">
            <div class="container">
                <div class="row">
                    <div class="col-lg-4 col-md-12 footer-link">
                         <img src="<?= base_url('frontend/logo.png') ?>"class="footerlogo">
                        <ul class="social_link">
					        <li><a href="<?=@$setting->facebook?>" target="_blank"><i class="fa fa-facebook"></i></a></li>
					        <li><a href="<?=@$setting->twitter?>" target="_blank"><i class="fa fa-twitter"></i></a></li>
					      
					        <li><a href="<?=@$setting->linkedin?>" target="_blank"><i class="fa fa-linkedin"></i></a></li>
					        <li><a href="<?=@$setting->instagram?>" target="_blank"><i class="fa fa-instagram" aria-hidden="true"></i></a></li>
					      </ul>
                    </div>
                    <div class="col-lg-4 col-md-12 footer-link">
                        <h5>Contact info</h5>
                        <div class="footer-payment">
                            <ul class="list-menu">
                            <li> <a href="javascript:void(0)"><span data-hover="Visit us :  <?=@$setting->address?>">Visit us :  <?=@$setting->address?></span></a>
                            </li>
                            <li> <a href="tel: <?=@$setting->phone?>"><span data-hover="Call us : <?=@$setting->phone?>">Call us : <?=@$setting->phone?></span></a>
                            </li>
                            <li> <a href="mailto: <?=@$setting->email?>"><span data-hover="Email us :  <?=@$setting->email?>">Email us :  <?=@$setting->email?></span></a>
                            </li>
                        </ul>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-12 footer-link">
                        <h5>Company </h5>
                        <div class="footer-payment">
                           <ul class="list-menu">
                            <li><a href="<?=base_url('terms-and-conditions')?>">Terms & Conditions</a></li>
                            <li><a href="<?=base_url('policy')?>">Privacy Policy</a></li>
                            <li><a href="<?=base_url('copyright')?>">Copyright</a></li>
                           </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <div class="row">
                	<div class="col-lg-12 col-md-12">
                        <div class="copyright-text text-center">
                            <p>Copyright &copy; <?= date('Y')?> <?= SITENAME ?> - All rights reserved.</p>
                        </div> 
                    </div>
                </div>
            </div>
        </div>
    </footer>
   
    <!--Jquery js-->
    <script src="<?=base_url('frontend/')?>js/jquery.min.js"></script>
    <script src="<?= base_url('companyasset/vendors/sweetalert/sweetalert.min.js')?>"></script>
    <script src="<?=base_url('frontend/')?>js/bootstrap.min.js"></script>
    <script src="<?=base_url('frontend/')?>js/easing.min.js"></script>
    <script src="<?=base_url('frontend/')?>js/owl.carousel.min.js"></script>
    <script src="<?=base_url('frontend/')?>js/magnific-popup.min.js"></script>
    <script src="<?=base_url('frontend/')?>js/jquery.waypoints.min.js"></script>
    <script src="<?=base_url('frontend/')?>js/jquery.counterup.js"></script>
    <script src="<?=base_url('frontend/')?>js/map.js"></script>
    <script src="<?=base_url('frontend/')?>js/slide-slider.js"></script>
    <script src="<?=base_url('frontend/')?>js/main.js"></script>
    <!-- <script>
      $('.add').click(function () {   
        var th = $(this).closest('.wrap').find('.count');     
        th.val(+th.val() + 1);
      });
      $('.sub').click(function () {
        var th = $(this).closest('.wrap').find('.count');     
            if (th.val() > 1) th.val(+th.val() - 1);
      });
    </script> -->
</body>
</html>