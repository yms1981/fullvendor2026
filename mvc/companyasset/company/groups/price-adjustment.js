$(document).ready(function(e){
    const select = $("select[name='percentage_on_price']");
    const container = $("#price-adjustment-field");
    const label = $("#price-adjustment-label");

    function togglePriceAdjustmentField(value) {
        if (value === "Increase on Price List %") {
            container.show();
            label.text("% to increase price:");
        } else if (value === "Decrease on Price List %") {
            container.show();
            label.text("% to decrease price:");
        } else {
            container.hide();
            label.text("");
        }
    }

    select.on("change", function () {
        togglePriceAdjustmentField($(this).val());
    });

    togglePriceAdjustmentField(select.val());
});
