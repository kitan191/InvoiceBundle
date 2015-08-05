(function () {
    'use strict';
    
    var sharedWorkspaceId = 0;
    
    $('#shared-workspaces-admin-tool').on('click', '.rename-workspace-btn', function () {
        sharedWorkspaceId = $(this).data('shared-workspace-id');

        window.Claroline.Modal.displayForm(
            Routing.generate(
                'formalibre_shared_workspace_name_edit_form',
                {'sharedWorkspace': sharedWorkspaceId}
            ),
            reloadPage,
            function() {}
        );
    });
    
    $('#shared-workspaces-admin-tool').on('click', '.change-owner-btn', function () {
        var ownerId = $(this).data('owner-id');
        sharedWorkspaceId = $(this).data('shared-workspace-id');

        var pickerOptions = {
            picker_name: 'workspace-owner-' + sharedWorkspaceId,
            multiple: false,
            selected_users: [ownerId]
        };
        var userPicker = new UserPicker();
        userPicker.configure(pickerOptions, changeOwner);
        userPicker.open();
//        window.Claroline.Modal.displayForm(
//            Routing.generate(
//                'formalibre_shared_workspace_name_edit_form',
//                {'sharedWorkspace': sharedWorkspaceId}
//            ),
//            reloadPage,
//            function() {}
//        );
    });
    
    var changeOwner = function (userId) {
        $.ajax({
            url: Routing.generate(
                'formalibre_admin_shared_workspace_owner_edit',
                {'sharedWorkspace': sharedWorkspaceId, 'user': userId}
            ),
            type: 'POST',
            success: function () {
                window.location.reload();
            }
        });
    };
    
    var reloadPage = function () {
        window.location.reload();
    };
})();