(function () {
    'use strict';
    
    $('.invoice-topbar-navbar-center-dropdown-menu').hover(
        function () {},
        function (event) {
            var parent = $(this).parent('.invoice-topbar-navbar-center-title-dropdown');
            var underline = parent.children('.invoice-topbar-navbar-center-title-underline');
            var parentName = parent.data('name');
            
            var x = event.clientX; 
            var y = event.clientY;
            var pointedElement = document.elementFromPoint(x, y);
            var pointedParent = $(pointedElement).parent('.invoice-topbar-navbar-center-title-dropdown');
            
            if ($(pointedElement).data('name') !== parentName &&
                (pointedParent.length < 1 || $(pointedParent[0]).data('name') !== parentName)) {
            
                underline.removeClass('invoice-topbar-navbar-center-selected');
                parent.removeClass('open');
            }
        }
    );
    
    $('.invoice-topbar-navbar-center-title-link').hover(
        function () {
            var underline = $(this).children('.invoice-topbar-navbar-center-title-underline');
            $('.invoice-topbar-navbar-center-title-underline').removeClass('invoice-topbar-navbar-center-selected');
            $('.invoice-topbar-navbar-center-title').removeClass('open');
            underline.addClass('invoice-topbar-navbar-center-selected');
            $(this).addClass('open');
        },
        function () {
            var underline = $(this).children('.invoice-topbar-navbar-center-title-underline');
            underline.removeClass('invoice-topbar-navbar-center-selected');
            $(this).removeClass('open');
        }
    );
    
    $('.invoice-topbar-navbar-center-title-dropdown').hover(
        function () {
            var underline = $(this).children('.invoice-topbar-navbar-center-title-underline');
            $('.invoice-topbar-navbar-center-title-underline').removeClass('invoice-topbar-navbar-center-selected');
            $('.invoice-topbar-navbar-center-title').removeClass('open');
            underline.addClass('invoice-topbar-navbar-center-selected');
            $(this).addClass('open');
        },
        function () {}
    );
    
    $('.invoice-topbar-navbar-center-title-dropdown').on('click', function (event) {
        event.preventDefault();
        var underline = $(this).children('.invoice-topbar-navbar-center-title-underline');
        
        if ($(this).hasClass('open')) {
            $(this).removeClass('open');
            underline.removeClass('invoice-topbar-navbar-center-selected');
        } else {
            $(this).addClass('open');
            underline.addClass('invoice-topbar-navbar-center-selected');
        }
    });
    
    $('.invoice-topbar-navbar-center-dropdown-item').on('click', function (event) {
        var link = $(this).children('a');
        var route = link.attr('href');
        window.location = route;
    });
    
    $('#invoice-topbar-login-btn').on('click', function () {
        var url = $(this).data('url');
        window.location = url;
    });
    
    $('#invoice-topbar-register-btn').on('click', function () {
        var url = $(this).data('url');
        window.location = url;
    });
})();