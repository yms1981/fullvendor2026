  <input type="hidden" id="baseUrl" value="<?= base_url() ?>">
  <input type="hidden" id="adminUrl" value="<?= admin_url() ?>">
          <footer class="footer">
            <div class="container">
              <div class="d-sm-flex justify-content-center justify-content-sm-between">
                <?php
                $footerShell = function_exists('admin_shell_lang_section') ? admin_shell_lang_section() : [];
                $footerRights = trim((string) ($footerShell['footer_copyright'] ?? 'All rights reserved.'));
                $footerDesigned = trim((string) ($footerShell['footer_designed_developed'] ?? 'Designed & Developed by'));
                ?>
                <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">Copyright &copy; <?=date('Y')?> <?= htmlspecialchars($footerRights, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center"><?= htmlspecialchars($footerDesigned, ENT_QUOTES, 'UTF-8') ?> <a href="https://www.goigi.com/" target="_blank">GOIGI</a></span>
              </div>
            </div>
          </footer>
        </div>
      </div>
    </div>
    <script src="<?= base_url('backend/vendors/js/vendor.bundle.base.js')?>"></script>
    <script src="<?= base_url('backend/vendors/summernote/dist/summernote-bs4.min.js')?>"></script>
    <script src="<?= base_url('backend/vendors/flot/jquery.flot.js')?>"></script>
    <script src="<?= base_url('backend/vendors/datatables.net/jquery.dataTables.js')?>"></script>
    <script src="<?= base_url('backend/vendors/datatables.net-bs4/dataTables.bootstrap4.js')?>"></script>
    <script src="<?= base_url('backend/js/off-canvas.js')?>"></script>
    <script src="<?= base_url('backend/js/hoverable-collapse.js')?>"></script>
    <script src="<?= base_url('backend/js/misc.js')?>"></script>
    <script src="<?= base_url('companyasset/js/bootstrap-datepicker.js')?>"></script>
    <script src="<?= base_url('backend/js/settings.js')?>"></script>
    <script src="<?= base_url('backend/js/todolist.js')?>"></script>
    <script src="<?= base_url('backend/js/dashboard.js')?>"></script>
    <script src="<?= base_url('backend/js/data-table.js')?>"></script>
    <script src="<?= base_url('companyasset/js/chart.min.js')?>"></script>
    <script src="<?= base_url('backend/js/bootstrap-tagsinput.js')?>"></script>
    <script src="<?= base_url('backend/vendors/simplemde/simplemde.min.js')?>"></script>
    <script src="<?=base_url('backend/js/jasny-bootstrap.js')?>"></script>
    <script src="<?= base_url('companyasset/js/select2.min.js')?>"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/sweetalert2@9.17.2/dist/sweetalert2.all.min.js"> </script>


<script>
     $(document).on('click', '.toggle-password', function() {
    $(this).toggleClass("fa-eye fa-eye-slash");
    var input = $("#password");
    input.attr('type') === 'password' ? input.attr('type','text') : input.attr('type','password')
  });
    /***Add Website links **/
    $('.add-web').on('click', function(e) {

        e.preventDefault();
        var id = $( this ).attr( 'href' ),
            clone = $( id ).find('.webfield-clone').html();
        $( id ).find('.weblink-list').append(clone);
    });
    $(".weblink-list").bind("DOMSubtreeModified", function() {
        $('.remove-weblink').on('click', function (e){
            e.preventDefault();
            $(this).parents( '.boxInput' ).remove();
        });
    });
  </script>


 <input type="hidden" id="baseUrl" value="<?= base_url() ?>">
 <input type="hidden" id="adminUrl" value="<?= admin_url() ?>">
 <script type="text/javascript">
    var adminUrl = $('#adminUrl').val();
    var baseUrl = $('#baseUrl').val();
    $(".alert").fadeTo(2000, 500).slideUp(500, function() {
        $(".alert").slideUp(500);
    });
    function alert_func(data) {
        swal.fire({title: data[0], icon: data[1], confirmButtonColor: data[2]});
    }
    var adminFooterFileInputLang = <?= json_encode([
        'selectImage' => trim((string) ($footerShell['footer_btn_select_image'] ?? 'Select image')),
        'changeImage' => trim((string) ($footerShell['footer_btn_change_image'] ?? 'Change')),
        'removeImage' => trim((string) ($footerShell['footer_btn_remove_image'] ?? 'Remove')),
    ], JSON_UNESCAPED_UNICODE) ?>;
      $('#add-more-image').on('click', function(event) {
    event.preventDefault();
    var fi = adminFooterFileInputLang;
    $('#image-group').append('<div class="fileinput fileinput-new" data-provides="fileinput"><div class="fileinput-new thumbnail" style="width: 200px; height: 150px;"><img src="'+baseUrl+'uploads/noimg.png" alt=""></div><div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 200px; max-height: 150px;"></div><div><span class="btn btn-default btn-file"><span class="fileinput-new">'+fi.selectImage+'</span><span class="fileinput-exists">'+fi.changeImage+'</span><input type="file" name="images[]" accept="images/*" required=""></span><a href="#" class="btn btn-danger fileinput-exists" data-dismiss="fileinput">'+fi.removeImage+'</a><span class="btn btn-danger delete-image-input">&times;</span></div></div>');
});
$(document).on('click', '.delete-image-input', function(event) {
    $(this).closest('.fileinput').remove();
});

if ($("#summernoteExample").length) {
    $('#summernoteExample').summernote({
      height: 250,
      tabsize: 1
    });
  }
 </script>

<?php
// Un solo read: evita condiciones incoherentes y, tras mostrar, se borra la clave para que no reaparezca al navegar.
$flash_msg = $this->session->flashdata('msg');
$adminShellFooter = function_exists('admin_shell_lang_section') ? admin_shell_lang_section() : [];
$flashGenericError = trim((string) ($adminShellFooter['flash_generic_error'] ?? ''));
if ($flashGenericError === '') {
    $flashGenericError = 'An error occurred. Please try again.';
}
?>
<?php if (! empty($flash_msg)): ?>
    <?php if ($flash_msg === 'error') { ?>
        <script type="text/javascript">
            alert_func([<?= json_encode($flashGenericError, JSON_UNESCAPED_UNICODE) ?>, "error", "#DD6B55"]);
        </script>
    <?php } else { ?>
        <script type="text/javascript">
            alert_func(<?= $flash_msg ?>);
        </script>
    <?php } ?>
<?php endif; ?>
<?php
$this->session->unset_userdata('msg');
$this->session->unmark_flash('msg');
?>
<script type="text/javascript">
    $(".taginput").tagsinput('items');

 $(function () {
        $("#fromdate").datepicker({
              autoclose: true,
              format:'yyyy-mm-dd',
              todayHighlight: true
        }).datepicker('update', new Date());
        $("#todate").datepicker({
              autoclose: true,
              format:'yyyy-mm-dd',
              todayHighlight: true
        }).datepicker('update', new Date());
      });
 <?php

   $adminStats = get_instance()->getObjectFromApi('dashboard/admin-stats');
   $salesByMonth = is_object($adminStats) && isset($adminStats->sales_by_month) ? $adminStats->sales_by_month : null;

   $monthsortfullname = [];
   $totsalemonth = [];
   if (is_object($salesByMonth) && isset($salesByMonth->labels, $salesByMonth->qty)) {
       $monthsortfullname = (array) $salesByMonth->labels;
       $totsalemonth = array_map('intval', (array) $salesByMonth->qty);
   } else {
       for ($i = 0; $i < 12; $i++) {
           $qtymonths[] = date('m', strtotime("-$i month"));
           sort($qtymonths);
       }
       foreach ($qtymonths as $m) {
           $monthsortfullname[] = date('F', strtotime('01-' . $m . '-' . date('Y')));
           $totsalemonth[] = 0;
       }
   }

$jsonordermonth=json_encode($totsalemonth);
$monthfulljson=json_encode($monthsortfullname);
   ?>
  // === include 'setup' then 'config' above ===
const labels = <?php echo $monthfulljson; ?>;
const data = {
  labels: labels,
  datasets: [{
    label: <?= json_encode(trim((string) ($footerShell['dashboard_sales_graph_label'] ?? 'Companies sales graph')), JSON_UNESCAPED_UNICODE) ?>,
    borderColor: 'rgb(0, 210, 130)',
    data: <?= $jsonordermonth ?>,
  }]
};

const config = {
  type: 'line',
  data: data,
  options: {}
};
  const myChart = new Chart(
    document.getElementById('myChart'),
    config
  );

 </script>

<?php if (! empty($loadCompanyFormScripts)) : ?>
<?php $this->load->view('admin/company_admin_phone_script'); ?>
<?php $this->load->view('admin/company_currency_picker_script'); ?>
<?php endif; ?>

  </body>
</html>
