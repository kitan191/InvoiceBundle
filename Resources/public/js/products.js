(function () {
    'use strict';
    var modal = window.Claroline.Modal;
    var translator = window.Translator;

    $('body').on('change', '.chk-activation', function(event) {
        var isChecked = $(event.currentTarget).prop('checked');
        var checkedString = isChecked ? 'true': 'false';
        var productId = $(event.currentTarget).attr('data-product-id');;
        var url = Routing.generate(
            'formalibre_activate_products',
            {'isActivated': checkedString, 'product': productId}
        );

        $.ajax({
            'url': url
        });
    });

    $('body').on('click', '.add-price-solution', function(event) {
        var formUrl = Routing.generate('formalibre_price_solution_form', {'product': $(event.currentTarget).attr('data-product-id')});
        modal.displayForm(formUrl, addPrice, function() {}, 'form_price_solution_creation');
    });

    $('body').on('click', '.edit-product', function(event) {
        var formUrl = Routing.generate('formalibre_product_edit_form', {'product': $(event.currentTarget).attr('data-product-id')});
        modal.displayForm(formUrl, editProduct, function() {}, 'formalibre_product_edit');
    });

    $('body').on('click', '#add-product-btn', function(event) {
        event.preventDefault();
        modal.displayForm($(event.currentTarget).attr('href'), addProduct, function() {}, 'form_add_product');
    });

    $('body').on('click', '.remove-price-solution', function(event) {
        event.preventDefault();
        var removeUrl = $(event.currentTarget).attr('href');
        var priceSolutionId = $(event.currentTarget).attr('data-price-solution-id');
        modal.confirmRequest(
            removeUrl,
            removePrice,
            priceSolutionId,
            translator.trans('remove_price_confirm', {'id': priceSolutionId}, 'invoice'),
            translator.trans('delete', {}, 'platform')
        );
    });

    $('body').on('click', '.remove-product', function(event) {
        event.preventDefault();
        var removeUrl = $(event.currentTarget).attr('href');
        var productId = $(event.currentTarget).attr('data-product-id');
        modal.confirmRequest(
            removeUrl,
            removeProduct,
            productId,
            translator.trans('remove_product_confirm', {'id': productId}, 'invoice'),
            translator.trans('delete', {}, 'platform')
        );
    });

    var addPrice = function(data, textStatus, jqXHR) {
        $('#price-list-' + data.product_id).append(Twig.render(PriceElement, {'priceSolution': data}));
    }

    var editProduct = function(data, $textStatus, jqXHR) {
        window.location.reload(); 
    }

    var addProduct = function(data, textStatus, jqXHR) {
        $('#table-product-body').append(Twig.render(ProductTableRow, {'product': data}));
    }

    var removePrice = function(event, priceSolutionId) {
        $('#price-el-' + priceSolutionId).remove();
    }

    var removeProduct = function(event, productId) {
        $('#row-product-' + productId).remove();
    }
}());
