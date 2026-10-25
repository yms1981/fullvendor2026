$(document).ready(function() {

    $("#companySelect").on('change',function(){
        $("#companyData").val(JSON.stringify($(this).children('option:selected').data('company')));
    })

    $("#companyForm").submit(function() {
       $("#userId").val(userId);
       return true;
    });

    if ($('#companySelect option').length > 1) {
        $('#companyModal').modal('show');
    }

    if ($('#applicationSelect option').length > 1) {
        $('#applicationModal').modal('show');
    }
});
