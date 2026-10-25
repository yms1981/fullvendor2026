
<!-- Modal Exportacion -->
<div class="modal fade" id="modal_export" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">EXPORTACIÓN DE CLIENTES</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>CSV file: <span class="color-red">*</span></label><br>
          <div class="fileinput fileinput-new" data-provides="fileinput"></div>
          <div class="clearfix margin-top-10 m-b-20 f12" style="display: block;">
            <span class="label label-danger">Formato</span>&nbsp;csv
          </div>

          <fieldset style="display: flex; column-gap: 15px; margin-bottom: 15px;">
            <legend style="font-size: 15px;">Separador de campo:</legend>
            <div style="display: flex; width: fit-content;">
              <div class="fieldsep_op"><input type="radio" id="sepComma" name="fsep" value="0"><div style="margin-left: 5px;" for="sepComma">Coma</div></div>
              <div style="width: 7px;"></div>
              <div class="fieldsep_op"><input type="radio" id="sepSemicolon" name="fsep" value="1" checked=""><div style="margin-left: 5px;" for="sepSemicolon">Punto y coma</div></div>
            </div>
          </fieldset>
          <div class="previewbox">
            <div id="appprogbar1" class="pbarbox" style="visibility: hidden;">
              <div class="pbar"></div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <a href="javascript:void(0)" onclick="exportar()" download id="btn-export" class="btn btn-success mt-2 mt-sm-0 btn-icon-text"><i class="mdi mdi-download"></i> Export </a>
      </div>
    </div>
  </div>
</div>