(function () {
    'use strict';

    $('.change-partner-btn').on('click', function () {
        var userId = $(this).data('user-id');
        var currentPartnerId = $(this).data('partner-id');
        
        window.Claroline.Modal.displayForm(
            Routing.generate(
                'admin_invoice_user_partner_select_form',
                {'user': userId, 'currentPartnerId': currentPartnerId}
            ),
            refreshPage,
            function() {}
        );
    });

    $('#search-user-btn').on('click', function () {
        var search = $('#search-user-input').val();
        var max = $(this).data('max');
        var route = Routing.generate(
            'admin_invoice_users',
            {
                'max': max,
                'search': search
            }
        );

        window.location.href = route;
    });

    $('#search-user-input').keypress(function(e) {
        if (e.keyCode === 13) {
            var search = $(this).val();
            var max = $(this).data('max');
            var route = Routing.generate(
                'admin_invoice_users',
                {
                    'max': max,
                    'search': search
                }
            );

            window.location.href = route;
        }
    });
    
    var refreshPage = function () {
        window.location.reload();
    };
})();