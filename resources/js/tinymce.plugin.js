(function() {
	tinymce.PluginManager.add( 'pamd_mce_button', function( editor, url ) {
		editor.addButton( 'pamd_mce_button', {
			tooltip : editor.getLang( 'pamd.plugin' ),
			icon    : 'pamd-icon',
			onclick : function() {
				editor.windowManager.open( {
					title : editor.getLang( 'pamd.plugin' ),
					body  : [
						{
							type   : 'listbox',
							name   : 'pamdLinkTarget',
							label  : editor.getLang( 'pamd.linktarget' ),
							values : [
								{
									text  : editor.getLang( 'pamd.linktargetself' ),
									value : '_self'
								},
								{
									text  : editor.getLang( 'pamd.linktargetblank' ),
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