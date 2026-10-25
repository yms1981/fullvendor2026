<div class="float-right">
    <div class="form-group d-flex align-items-center pt-2">
        <div class="pr-2">
            <label for="show-customers"><?= $lang_dashboard['show'] ?></label>
        </div>
        <select id="<?= isset($select_show)?$select_show:'' ?>" 
                name="<?= isset($select_show)?$select_show:'' ?>" 
                class="form-control selectpicker select-show" data-width = "70">
            <option value="5">5</option>
            <option value="all"><?= $lang_dashboard['all'] ?></option>
        </select>
    </div>
</div>