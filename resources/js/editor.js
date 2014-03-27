jQuery(document).ready(function ($) {
	var pamd_file;

	function pamd_restyle_list() {
		$("#pamd-media-list tbody tr").removeClass('alternate');
		$("#pamd-media-list tbody tr:even").addClass('alternate');
	}

	$("#pamd-add-new-media").click(function (e) {
		e.preventDefault();

		if ( pamd_file ) {
			pamd_file.open();
			return;
		}

		pamd_file = wp.media.frames.file_frame = wp.media({
			title: 'Add downloadable media',
			button: {
				text: 'Add media'
			},
			multiple: true
		});

		pamd_file.on( 'select', function() {
			pamd_attachments = pamd_file.state().get('selection').toJSON();
			var pamd_html = "",
				pamd_ids = [];

			$.each( pamd_attachments, function( index, attachment ) {

				var media = {
					id    : attachment.id,
					label : attachment.title,
					url   : attachment.url
				};
				pamd_ids.push( media );
			});

			var ajax_call = {
				action   : 'pamd_append_list',
				post_id  : $("#post_ID").val(),
				pamd_ids : pamd_ids
			};
			$.post( ajaxurl, ajax_call, function( response ) {
				$.each( response, function( index, attachment ) {
					if ( attachment.label ) {
						pamd_html = pamd_html + '<tr>' +
							'<td data-pamd-media-id="' + attachment.id + '">' + attachment.label + '</td>' +
							'<td><a href="'+ attachment.url + '">' + attachment.url + '</a></td>' +
							'<td><a href="#" class="pamd-delete" data-pamd-remove="' + index + '">Remove</a></td>' +
							'</tr>';
					}
				});

				$("#pamd-media-list-body").html(pamd_html);
				pamd_restyle_list();

			}, 'json' );

		});

		pamd_file.open();
	});

	$("#pamd-media-list").on( 'click', '.pamd-delete', function (e) {
		e.preventDefault();

		var ajax_call = {
			action   : 'pamd_remove_entry',
			post_id  : $("#post_ID").val(),
			entry_id : $(this).data('pamd-remove')
		};

		var pamd_html = "";

		$.post( ajaxurl, ajax_call, function( response ) {
			$.each( response, function( index, attachment ) {
				pamd_html = pamd_html + '<tr>' +
					'<td data-pamd-media-id="' + attachment.id + '">' + attachment.label + '</td>' +
					'<td><a href="'+ attachment.url + '">' + attachment.url + '</a></td>' +
					'<td><a href="#" class="pamd-delete" data-pamd-remove="' + index + '">Remove</a></td>' +
					'</tr>';
			});

			$("#pamd-media-list-body").html(pamd_html);
			pamd_restyle_list();

		}, 'json' );
	});
});