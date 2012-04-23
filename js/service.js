jQuery(document).ready(function($){

	

	$('#fakeaboutme_btn_settings').click(function(e){

		e.preventDefault();

		$.get(ajax_object.ajaxurl, {
			action: 'fakeaboutme_service'
			,fakeaboutme_language: $('#fakeaboutme_table_settings select#fakeaboutme_language' ).attr('value')
			,fakeaboutme_genre: $('#fakeaboutme_table_settings select#fakeaboutme_genre').attr('value') 						
		}, function(data) {
			$('#fab_form_identity').replaceWith(data);
		});
		 
	});	

});