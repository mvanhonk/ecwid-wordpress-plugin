jQuery(document).ready(function(){

	jQuery('.ec-create-store-button').click(function() {
		var hide_on_loading = '.ec-create-store-button',
			show_on_loading = '.ec-create-store-loading',
			show_on_success = '.ec-create-store-success-note';
			hide_on_success = '.ec-create-store-note';


	    if (ecwidParams.isWL) {
	        location.href = ecwidParams.registerLink;
	        return;
        }

        jQuery(hide_on_loading).hide();
        jQuery(show_on_loading).show();

        jQuery('.ec-connect-store').addClass('disabled');

		jQuery.ajax(ajaxurl + '?action=ecwid_create_store',
			{
				success: function(result) {
        			jQuery(hide_on_success).hide();
        			jQuery(show_on_success).show();
					
					setTimeout(function() {
						location.href="admin.php?page=ec-store";
					}, 1000);
				},
				error: function() {
					window.location.href = ecwidParams.registerLink;
				}
			}
		);
	});

});