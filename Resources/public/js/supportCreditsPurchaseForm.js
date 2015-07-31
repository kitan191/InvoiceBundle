(function () {
    'use strict';
    
    $('#support-credit-purchase-form').on('click', '.product-panel' , function () {
        var productId = $(this).data('product-id');
        
        $('.product-panel').removeClass('product-panel-selected');
        $(this).addClass('product-panel-selected');
        $('#buy-btn').removeClass('disabled');
        $('#support_credit_purchase_form_product').val(parseInt(productId));
    });
})();