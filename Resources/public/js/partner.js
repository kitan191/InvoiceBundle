(function () {
    'use strict';
    var modal = window.Claroline.Modal;
    var translator = window.Translator;
    
    $('body').on('change', '.chk-activation', function(event) {
        var isChecked = $(event.currentTarget).prop('checked');
        var checkedString = isChecked ? 'true': 'false';
        var partnerId = $(event.currentTarget).attr('data-partner-id');;
        var url = Routing.generate(
            'formalibre_activate_partner',
            {'isActivated': checkedString, 'partner': partnerId}
        );

        $.ajax({
            'url': url
        });
    });

    $('body').on('click', '#add-partner-btn', function(event) {
        event.preventDefault();
        modal.displayForm($(event.currentTarget).attr('href'), addProduct, function() {}, 'form_add_partner');
    });

    $('body').on('click', '.remove-partner', function(event) {
        event.preventDefault();
        var removeUrl = $(event.currentTarget).attr('href');
        var productId = $(event.currentTarget).attr('data-partner-id');
        modal.confirmRequest(
            removeUrl,
            removePartner,
            partnerId,
            translator.trans('remove_partner_confirm', {'id': productId}, 'invoice'),
            translator.trans('delete', {}, 'platform')
        );
    });

    var addProduct = function(data, textStatus, jqXHR) {
        $('#table-partner-body').append(Twig.render(PartnerTableRow, {'partner': data}));
    }

    var removePartner = function(event, partnerId) {
        $('#row-partner-' + partnerId).remove();
    }
}());
