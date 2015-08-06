(function () {
    'use strict';
    
    $('#shared-workspace-create-form').on('change', '#shared_workspace_form_product', function () {
        var productId = $(this).val();
        
        $.ajax({
            url: Routing.generate('product_infos', {'product': productId}),
            type: 'GET',
            success: function (datas) {
                $('#shared_workspace_form_maxSize').val(datas['details']['max_storage']);
                $('#shared_workspace_form_maxUser').val(datas['details']['max_users']);
                $('#shared_workspace_form_maxRes').val(datas['details']['max_resources']);
                
//                var priceSolutions = datas['priceSolutions'];
//                $('#shared_workspace_form_price').empty();
//                
//                for (var i = 0; i < priceSolutions.length; i++) {
//                    var option = '<option value="' + priceSolutions[i]['id'] + '">' +
//                        priceSolutions[i]['duration'] + ' ' + Translator.trans('months', {}, 'invoice') +
//                        ' (' + priceSolutions[i]['price'] + ' €)</option>';
//                    $('#shared_workspace_form_price').append(option);
//                }
            }
        });
    });
})();