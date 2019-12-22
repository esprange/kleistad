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

    $( document ).ready(
		function()  {
			onLoad();

			$( '#kleistad_gebruikers' ).on( 'changed.jstree',
				function() {
					var gebruikerIds = [],
						selectIndexes = $( '#kleistad_gebruikers' ).jstree( 'get_selected', true );
					$.each( selectIndexes, function() {
						var gebruikerId = this.li_attr.title;
						if ( undefined !== gebruikerId ) {
							gebruikerIds.push( gebruikerId );
						}
					});
					$( '#kleistad_selectie' ).val( gebruikerIds.join( ',' ) );
				}
			);
		}
    );

} )( jQuery );
