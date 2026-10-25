<div class="container-fluid page-body-wrapper pt-3">
  <div class="main-panel">
    <div class="container-fluid">
      <div class="page-header">
        <h3 class="page-title"> <?= @$title ?></h3>
      </div>
      <div class="card">
        <div class="card-body">
          <form id="changePasswordForm" method="post" action="<?= company_url('change_password/update') ?>" class="forms-sample">
            <div class="form-group">
              <h3 class="page-title">Settings</h3>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Current Password : <span class="color-red">*</span> </label>
                  <input type="password" class="form-control" name="c_password" id="c_password" autocomplete="off" required="">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>New Password : <span class="color-red">*</span> </label>
                  <input type="password" class="form-control" name="n_password_confirmation" id="n_password_confirmation" autocomplete="off" required="">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Repeat Password : <span class="color-red">*</span> </label>
                  <input type="password" class="form-control" name="n_password" id="n_password" data-validation="confirmation" autocomplete="off" required="">
                </div>
              </div>
            </div>
            <div class="form-group">
              <button type="submit" class="btn btn-success mr-2">Submit</button>
              <a class="btn btn-danger mr-2" href="<?= company_url('profile') ?>">
                Back
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Manejar el envío del formulario
    $('#changePasswordForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const formData = form.serialize();
        const formAction = form.attr('action');
        
        // Mostrar indicador de carga
        const submitButton = form.find('button[type="submit"]');
        const originalText = submitButton.html();
        submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> Processing...');
        
        $.ajax({
            url: formAction,
            type: 'POST',
            data: formData,
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                // Si la respuesta es exitosa, mostrar alerta y redirigir
                if (response && response.success) {
                    swal({
                        title: "Success!",
                        text: response.message || "Password changed successfully. You will be redirected to login.",
                        icon: "success",
                        button: {
                            text: "OK",
                            value: true,
                            visible: true,
                            className: "btn btn-primary"
                        }
                    }).then(function() {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            window.location.href = '<?= base_url('login') ?>';
                        }
                    });
                } else {
                    // Si hay un mensaje de error en la respuesta
                    const errorMsg = (response && response.message) ? response.message : 'Failed to change password';
                    swal({
                        title: "Error!",
                        text: errorMsg,
                        icon: "error",
                        button: {
                            text: "OK",
                            value: true,
                            visible: true,
                            className: "btn btn-primary"
                        }
                    });
                    submitButton.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr, status, error) {
                // Si el servidor devuelve HTML (redirect), significa que hubo un error y se redirigió
                if (xhr.responseText && xhr.responseText.indexOf('<!DOCTYPE') !== -1) {
                    // Recargar la página para mostrar el mensaje flash
                    window.location.reload();
                } else {
                    // Error de red o servidor
                    let errorMsg = 'An error occurred while changing the password.';
                    try {
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                    }
                    swal({
                        title: "Error!",
                        text: errorMsg,
                        icon: "error",
                        button: {
                            text: "OK",
                            value: true,
                            visible: true,
                            className: "btn btn-primary"
                        }
                    });
                    submitButton.prop('disabled', false).html(originalText);
                }
            }
        });
    });
});
</script>
