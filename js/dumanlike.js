(function( $ ) {
	'use strict';
	$(document).on('click', '.dumanlike-button', function() {
		var button = $(this);
		var post_id = button.attr('data-post-id');
		var security = button.attr('data-nonce');
		var iscomment = button.attr('data-iscomment');
		var allbuttons;
		if ( iscomment === '1' ) { /* Comments can have same id */
			allbuttons = $('.dumanlike-comment-button-'+post_id);
		} else {
			allbuttons = $('.dumanlike-button-'+post_id);
		}
		var loader = allbuttons.next('#dumanlike-loader');
		if (post_id !== '') {
			$.ajax({
				type: 'POST',
				url: dumanlikeloc.ajaxurl,
				data : {
					action : 'process_dumanlike',
					post_id : post_id,
					nonce : security,
					is_comment : iscomment,
				},
				beforeSend:function(){
					loader.html('&nbsp;<div class="loader">Loading...</div>');
				},	
				success: function(response){
					var icon = response.icon;
					var count = response.count;
					allbuttons.html(icon+count);
					if(response.status === 'unliked') {
						var like_text = 'Like';
						allbuttons.prop('title', like_text);
						allbuttons.removeClass('liked');
					} else {
						var unlike_text = 'Unlike';
						allbuttons.prop('title', unlike_text);
						allbuttons.addClass('liked');
					}
					loader.empty();					
				},
				error: function(jqXHR, exception, textStatus, errorThrown){
					var msg = '';
					if (jqXHR.status === 0) {
						msg = 'Not connect.\n Verify Network.';
					} else if (jqXHR.status == 404) {
						msg = 'Requested page not found. [404]';
					} else if (jqXHR.status == 500) {
						msg = 'Internal Server Error [500].';
					} else if (exception === 'parsererror') {
						msg = 'Requested JSON parse failed.';
					} else if (exception === 'timeout') {
						msg = 'Time out error.';
					} else if (exception === 'abort') {
						msg = 'Ajax request aborted.';
					} else {
						msg = 'Uncaught Error.\n' + jqXHR.responseText;
					}
					$('#post').html(msg);
				}
			});
			
		}
		return false;
	});
})( jQuery );
