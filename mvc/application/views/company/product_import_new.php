<?php
  if (!isset($sep)) $sep=1;
?>
<style>
.app_progbar{
  position: absolute;
  width: 5rem; height: 5rem;
  left: 45%; top: 32%;
  opacity: 0.56;
  background: url("<?= base_url('images/logos/isologo.png'); ?>");
  background-size: 5px 5px;
  background-position: center;
  background-repeat: no-repeat;
  background-size: contain;
  z-index: 21001;
}
.app_progbargif{
  margin-left: -1.2em; margin-top: -0.85em;
  width: 7.4rem; height: 6rem;
  opacity: 0.4;
}
.app_progbarbk{
  position: absolute;
  width: 100%; height: 100%;
  border-radius: 9%;
  opacity: 0.225;
  background-color: red;
}
.iinfotable_row{
  color: red;
  cursor: pointer;
}
.iinfodlgback{
  color: white;
  background: rgb(79,10,10);
  background: linear-gradient(90deg, rgba(79,10,10,1) 0%, rgba(207,33,23,1) 41%, rgba(252,8,38,1) 98%);
}
.iinfosum_grayline{
  color: black;
}
.iinfosum_redline{
  color: red;
}
.iinfosum_greenline{
  color: darkgreen;
}
.error_bg{
  color: white;
  /* border-radius: 9px; */
  background: rgb(255,2,2);
  background: linear-gradient(90deg, rgba(255,2,2,1) 0%, rgba(247,113,113,1) 41%, rgba(255,220,209,1) 98%);
}
</style>

<?php if (isset($errflag) && ($errflag==true)): ?>
  <div class="alert alert-danger" role="alert">
    <div><?= $errmsg ?> </div>
  </div>
<?php endif; ?>


<div id="appprogbar" class="app_progbar">
  <div class="app_progbarbk"></div>
  <img class="app_progbargif" src="<?= base_url('images/loading.gif'); ?>" />
</div>


<script type="text/javascript">
var tbl;
  var fldsep = ';';
  var selerrcolss = ''; //selected error columns ss

  $(document).ready(function (){
    showpbar(true);

    setTimeout(() => {
      showpbar(false);
    }, 3000);
  });

  function ifilepickerChanged(any){
    let finput = document.getElementById('ifilepicker');
    if (any.length < 1) return;
    let fpath = finput.value;
    var okfiletypes = /(.csv|.CSV|.png|.PNG)$/i;
    if (!okfiletypes.exec(fpath)){
      finput.value = "";
      alert("Debe elegir un archivo CSV !");
      return false;
    }
    else{
      if (finput.files && finput.files[0]){
        let frm = document.getElementById('frmcsvfile');
        frm.submit();
        showpbar();
        setTimeout(() => {
            frm.submit();
        }, 3000);
      }
    }
    return true;
  }

  function importit(){
    let tdata = tbl.rows().data();
    var temparray = [];
    for (let rw = 0; rw < tdata.length; rw++) {
        let row = tdata.rows(rw).data()[0];
        if (row[15] == '1'){
          temparray.push(row);
        }
    }

    let frm = document.getElementById('iformimport');
    var input = document.createElement("input");
    input.setAttribute("type", "hidden");
    input.setAttribute("name", "rawdata");
    input.setAttribute("value", JSON.stringify(temparray));
    frm.appendChild(input);
    frm.submit();
  }

  function pickFileClick(){
    document.getElementById('ifilepicker').click();
  }

  function showpbar(bval){
    document.getElementById('appprogbar').style.visibility = bval?'visible':'hidden';
  }

  function ifg_reset_css(){
    $('#ifg_product_name').removeClass('error_bg');
    $('#ifg_categ_list').removeClass('error_bg');
    $('#ifg_sku').removeClass('error_bg');
    $('#ifg_barcode').removeClass('error_bg');
    $('#ifg_unit_type').removeClass('error_bg');
    $('#ifg_sale_price').removeClass('error_bg');
    $('#ifg_fob_price').removeClass('error_bg');
    $('#ifg_purchase_price').removeClass('error_bg');
    $('#ifg_stock').removeClass('error_bg');
    $('#ifg_notimoq').removeClass('error_bg');
    $('#ifg_tags').removeClass('error_bg');
    $('#ifg_location').removeClass('error_bg');
    $('#ifg_descrips').removeClass('error_bg');
    $('#ifg_status').removeClass('error_bg');
  }

  function itRowClick(tblrowidx, rowinfo){
    // console.log(rowinfo);
    if (rowinfo[15] == '1') return;
    ifg_reset_css();
    selerrcolss = rowinfo[17];
    let errcols = rowinfo[17].split(';');
    for(let i=0; i<errcols.length; i++){
      let colidx = errcols[i];
      switch(colidx){
        case '0':
          $('#ifg_product_name').addClass('error_bg');
          break;
        case '1':
          $('#ifg_categ_list').addClass('error_bg');
          break;
        case '2':
          $('#ifg_sku').addClass('error_bg');
          break;
        case '3':
          $('#ifg_barcode').addClass('error_bg');
          break;
        case '4':
          $('#ifg_unit_type').addClass('error_bg');
          break;
        case '5':
          $('#ifg_sale_price').addClass('error_bg');
          break;
        case '6':
          $('#ifg_fob_price').addClass('error_bg');
          break;
        case '7':
          $('#ifg_purchase_price').addClass('error_bg');
          break;
        case '8':
          $('#ifg_stock').addClass('error_bg');
          break;
        case '9':
          $('#ifg_moq').addClass('error_bg');
          break;
        case '10':
          $('#ifg_notimoq').addClass('error_bg');
          break;
        case '11':
          $('#ifg_tags').addClass('error_bg');
          break;
        case '12':
          $('#ifg_location').addClass('error_bg');
          break;
        case '13':
          $('#ifg_descrips').addClass('error_bg');
          break;
        case '14':
          $('#ifg_status').addClass('error_bg');
          break;
      }
    }
    $('#ic_product_name').val(rowinfo[0]);
    $('#ic_categ_list').val(rowinfo[1]);
    $('#ic_sku').val(rowinfo[2]);
    $('#ic_barcode').val(rowinfo[3]);
    $('#ic_unit_type').val(rowinfo[4]);
    $('#ic_sale_price').val(rowinfo[5]);
    $('#ic_fob_price').val(rowinfo[6]);
    $('#ic_purchase_price').val(rowinfo[7]);
    $('#ic_stock').val(rowinfo[8]);
    $('#ic_moq').val(rowinfo[9]);
    $('#ic_notimoq').val(rowinfo[10]);
    $('#ic_tags').val(rowinfo[11]);
    $('#ic_location').val(rowinfo[12]);
    $('#ic_descrips').val(rowinfo[13]);
    $('#ic_status').val(rowinfo[14]);

    $("#iformDlgEdit").modal('show');
  }

  function isNumeric(str) {
    if (typeof str != "string") return false;
    return !isNaN(str) && !isNaN(parseFloat(str));
  }

  function iformdlgSaveChanges(){
    let errcheck = false;
    let errmsg = "";
    let errcols = selerrcolss.split(';');
    for(let i=0; i<errcols.length; i++){
      let colidx = errcols[i];

    }

  }

</script>
