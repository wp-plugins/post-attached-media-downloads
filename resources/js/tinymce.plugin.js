(function() {
	tinymce.PluginManager.add( 'pamd_mce_button', function( editor, url ) {
		editor.addButton( 'pamd_mce_button', {
			tooltip : 'Post Attached Media Downloads',
			icon    : 'pamd-icon',
			onclick : function() {
				editor.windowManager.open( {
					title : 'Post Attached Media Downloads',
					body  : [
						{
							type   : 'listbox',
							name   : 'pamdLinkTarget',
							label  : 'Download behavior',
							values : [
								{
									text  : 'Open in current window',
									value : '_self'
								},
								{
									text  : 'Open a new tab/window',
									value : '_blank'
								}
							]
						}
					],
					onsubmit: function ( e ) {
						editor.insertContent( '[pamd target="' + e.data.pamdLinkTarget + '"]' );
					}
				} );
			}
		});
	});
})();