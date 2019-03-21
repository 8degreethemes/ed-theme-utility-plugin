jQuery(document).ready(function ($) {
	/** Demo Import **/
	$('.demo_import_btn').on('click', function (e) {
		e.preventDefault();

		$import_true = confirm(IDMObject.demo_confirm);
		if($import_true == false) return;
		var site_url = $('#site_url').val();
		var el = $(this);
		var ajaxurl = IDMObject.ajaxurl; 
		if(site_url==undefined){
		    var site_url = ajaxurl.replace("wp-admin/admin-ajax.php","");
		}
		var folder = el.attr('data-folder');
		var menu = el.attr('data-menu');
		var homepage = el.attr('data-homepage');

		el.addClass('installing');
		el.html(IDMObject.demo_installing);

		$.ajax({
			method: "POST",
            url: ajaxurl,
            data: ({
            'action': 'demo_import_action',
            'folder': folder,
            'menu': menu,
            'homepage': homepage,
            }),
            success: function(response){ 
                if(confirm("\t Demo has been Imported. \n Press 'Ok' to Visit Homepage \n or \n Cancel to stay here. \t")){
                    document.location = site_url;
                }
                el.removeClass('installing');
				el.html(IDMObject.demo_installed);
		    	el.attr('disabled','disabled');
                el.parents('.all-demo-wrapper').prepend("<div class='warning-msg'>"+response+"</div>");
            }
        });
	});
}); // Document Load Complete