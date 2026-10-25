<script src="<?= base_url('companyasset/vendors/sweetalert/sweetalert.min.js')?>"></script>
<!-- <script type="text/javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script> -->
<script src="<?= base_url('companyasset/vendors/jquery-validation/jquery.validate.min.js')?>"></script>
<script src="<?= base_url('companyasset/vendors/jquery-validation/jquery.validate.min.js')?>"></script>
<script src="<?= base_url('companyasset/js/form-validation.js')?>"></script>
<script src="<?= base_url('companyasset/vendors/js/vendor.bundle.base.js')?>"></script>
<script src="<?= base_url('companyasset/js/off-canvas.js')?>"></script>
<script src="<?= base_url('companyasset/js/hoverable-collapse.js')?>"></script>
<script src="<?= base_url('companyasset/js/misc.js')?>"></script>
<script src="<?= base_url('companyasset/js/settings.js')?>"></script>
<script src="<?= base_url('companyasset/js/todolist.js')?>"></script>
<script src="<?= base_url('companyasset/js/bootstrap-select.min.js?v='.$v_imagen)?>"></script>



<script>
    
function alert_func(data) {
    swal({
        title: data[0],
        text: '',
        icon: data[1],
        button: {
            text: "OK",
            value: true,
            visible: true,
            className: "btn btn-primary"
        }
    });
}

</script>

<?php if (!empty($this->session->flashdata('msg'))): ?>
<?php if ($this->session->flashdata('msg') == 'error') { ?>
<script>
alert_func(["Some error occured, Please try again!", "error", "#DD6B55"]);
</script>
<?php } else { ?>
<script>
alert_func(<?= $this->session->flashdata('msg') ?>);
</script>
<?php } ?>
<?php endif ?>