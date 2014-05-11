jQuery(document).ready(function ($) {
	var pamd_file;

	function pamd_update_entry_list( entries ) {
		var pamd_html = "";

		$.each( entries, function( index, attachment ) {
			if ( attachment.label ) {
				pamd_html = pamd_html + '<tr data-pamd-entry-num="' + index + '">' +
					'<td data-pamd-media-id="' + attachment.id + '">' + attachment.label + '</td>' +
					'<td><a href="'+ attachment.url + '">' + attachment.url + '</a></td>' +
					'<td>' +
					'<a href="#" class="pamd-edit">' + pamd.edit_link + '</a>' +
					' | <a href="#" class="pamd-delete" data-pamd-remove="' + index + '">' + pamd.delete_link + '</a>' +
					'</td>' +
					'</tr>';
			}
		});

		$("#pamd-media-list-body").html(pamd_html);
		pamd_restyle_list();
	}

	function pamd_sortable_helper( e, ui ) {
		ui.children().each(function() {
			$(this).width($(this).width());
		});
		return ui;
	}
	function pamd_sortable_update( e, ui ) {
		pamd_restyle_list();
		var new_order = "";
		$("#pamd-media-list tbody tr").each(function( index ) {
			new_order = new_order + $(this).data('pamd-entry-num') + ',';
		});
		var ajax_call = {
			action   : 'pamd_update_list_order',
			post_id  : $("#post_ID").val(),
			pamd_ids : new_order
		};

		$.post( ajaxurl, ajax_call, function ( response ) {
			pamd_update_entry_list( response );
		}, 'json' );
	}
	function pamd_make_sortable() {
		$("#pamd-media-list tbody").sortable({
			helper: pamd_sortable_helper,
			update: pamd_sortable_update
		});
	}

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
			var pamd_ids = [];

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
				pamd_update_entry_list( response );
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

		$.post( ajaxurl, ajax_call, function( response ) {
			pamd_update_entry_list( response );
		}, 'json' );
	})
	.on( 'click', '.pamd-edit', function (e) {
		e.preventDefault();

		var $parent = $(this).closest('tr'),
			newtitle = prompt( pamd.edit_help_text );

		if ( newtitle && newtitle != "" ) {
			var ajax_call = {
				'action'    : 'pamd_update_label',
				'post_id'   : $("#post_ID").val(),
				'pamd_id'   : $parent.data('pamd-entry-num'),
				'new_label' : newtitle
			};

			$.post( ajaxurl, ajax_call );

			$("td:first-child", $parent).text( newtitle );
		}
	});

	pamd_make_sortable();
});