jQuery(function ($) {
    const wpcsProductCheckbox = $('#is_wpcs_product');
    const wpcsProductOptionsTab = $('.wpcs_options');

    function manageProductOptionsTab() {
        if (wpcsProductCheckbox.is(':checked')) {
            wpcsProductOptionsTab.show();
        } else {
            wpcsProductOptionsTab.hide();
        }
    }

    manageProductOptionsTab();

    wpcsProductCheckbox.change(function () {
        manageProductOptionsTab();
    });
});

jQuery(function ($) {
    const wpcsAddonCheckbox = $('#wpcs_product_type');
    const wpcsGroupNameSelectContainer = $('.WPCS_PRODUCT_GROUPNAME_META_field');
    const wpcsGroupNameSelect = $('#WPCS_PRODUCT_GROUPNAME_META');

    function manageGroupNameSelect() {
        if (wpcsAddonCheckbox.is(':checked')) {
            wpcsGroupNameSelectContainer.hide();
        } else {
            wpcsGroupNameSelectContainer.show();
            wpcsGroupNameSelect.val("");
        }
    }

    manageGroupNameSelect();

    wpcsAddonCheckbox.change(function () {
        manageGroupNameSelect();
    });
});