(function () {
    'use strict';
    
    $('#my-shared-workspaces-tool').on('click', '.rename-workspace-btn', function () {
        var sharedWorkspaceId = $(this).data('shared-workspace-id');
        
        
        window.Claroline.Modal.displayForm(
            Routing.generate(
                'formalibre_shared_workspace_name_edit_form',
                {'sharedWorkspace': sharedWorkspaceId}
            ),
            reloadPage,
            function() {}
        );
    });
    
    var reloadPage = function () {
        window.location.reload();
    };
})();