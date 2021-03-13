( function( $ ) {
	'use strict';

	function onLoad() {
		$( '#kleistad_gebruikers' ).jstree(
			{
				'plugins': [ 'checkbox' ]
			}
		);
	}

	$( document ).ajaxComplete(
        function() {
			onLoad();
		}
	);

    $( function()
		{
			onLoad();

			$( '#kleistad_gebruikers' ).on( 'changed.jstree',
				function() {
					var gebruikerIds = [],
						selectIndexes = $( this ).jstree( 'get_selected', true );
					$.each( selectIndexes, function() {
						var gebruikerId = this.li_attr.gebruikerid;
						if ( undefined !== gebruikerId ) {
							gebruikerIds.push( gebruikerId );
						}
					});
					$( '#kleistad_gebruikerids' ).val( gebruikerIds.join( ',' ) );
				}
			);
		}
    );

} )( jQuery );
