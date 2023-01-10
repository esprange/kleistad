/**
 * Galerie Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  7.9.0
 * @package Kleistad
 */

/* global kleistadData */

( function( $ ) {
	/**
	 * Type definitions
	 *
	 * @typedef Showcase
	 * @type {object}
	 * @property {number} id            - the ID.
	 * @property {object} titel         - the title.
	 * @property {string} beschrijving  - the description.
	 * @property {string} prijs         - the price.
	 * @property {string} status        = status of the showcase.
	 * @property {string} foto_small    - url for the small picture.
	 * @property {string} foto_large    - url for the large picture.
	 * @property {string} link          - url of displayed page.
	 * @property {number} keramist_id   - the ID of the keramist.
	 *
	 * @typedef Keramist
	 * @type {object}
	 * @property {number} id            - the ID.
	 * @property {string} naam          - name of the author.
	 * @property {string} bio           - bio of the author.
	 * @property {string} foto          - url of the authors' picture.
	 * @property {string} website       - url of the authors' website.
	 */

	/**
	 * The showcase store
	 *
	 * @type {Showcase[]} showcases     - Array of showcases.
	 */
	let showcases = [];

	/**
	 * The keramist store
	 *
	 * @type {Keramist[]} keramisten    - Array of keramisten.
	 */
	let keramisten = [];

	/**
	 * The current showcase
	 *
	 * @type {number} currentShowcaseID - Currently displayed showcase ID.
	 */
	let currentShowcaseID = 0;

	/**
	 * The current keramist
	 *
	 * @type {number} currentKeramistID - Currently displayed keramist ID.
	 */
	let currentKeramistID = 0;

	/**
	 * Retrieve all showcase and keramist data and store it.
	 *
	 * @return {object}
	 */
	function getDataFromServer() {
		$.ajax(
			{
				type: 'GET',
				url:  kleistadData.base_url + '/showcases/',
				beforeSend: function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
				},
				success:
					/**
					 * REST call
					 *
					 * @typedef Response
					 * @property {Showcase[]} showcases
					 * @property {Keramist[]} keramisten
					 *
					 * @param {Response} response
					 */
					function( response ) {
						showcases  = response.showcases;
						keramisten = response.keramisten;
					},
				async: false
			}
		)
	}

	/**
	 * Update the field
	 *
	 * @param {string} fieldId            - field id.
	 * @param {string} text          - unformatted text.
	 * @param {string} formattedText - formatted text (optional}.
	 */
	function updateField( fieldId, text, formattedText = `${text}` ) {
		if ( text.length ) {
			$( fieldId ).html( formattedText );
			$( fieldId + '_label' ).show();
			return;
		}
		$( fieldId ).html( '' );
		$( fieldId + '_label' ).hide();
	}

	/**
	 * Find the showcase by id and update prev and next links. Return the index or undefined if not found.
	 *
	 * @param {number}     id        - ID of the showcase.
	 * @return {number|undefined}
	 */
	function findShowcase( id ) {
		return showcases.findIndex(
			function( showcase ) {
				return id === showcase.id;
			}
		);
	}

	/**
	 * Find the keramist. Return the index or undefined if not found.
	 *
	 * @param {number}     index         - Index of the showcase in store.
	 * @return {number|undefined}
	 */
	function findKeramist( index ) {
		return keramisten.findIndex(
			function( keramist ) {
				return keramist.id === showcases[index].keramist_id;
			}
		);
	}

	/**
	 * Update prev and next links if needed.
	 *
	 * @param {number} index - Index of the showcase in store.
	 */
	function updateShowcaseLinks( index ) {
		let $arrows = $( '#next, #prev' );
		if ( undefined !== index && 1 < showcases.length ) {
			$arrows.show();
			$( '#next' ).data( 'id', showcases[ ( index + 1 === showcases.length ) ? 0 : index + 1 ].id );
			$( '#prev' ).data( 'id', showcases[ ( 0 === index ) ? showcases.length - 1 : index - 1 ].id );
			return;
		}
		$arrows.hide();
	}

	/**
	 * Render the showcase information
	 *
	 * @param {number} index - The index of the showcase in store.
	 */
	function updateShowcase( index ) {
		if ( currentShowcaseID !== showcases[index].id ) {
			currentShowcaseID = showcases[index].id;
			document.title    = showcases[index].titel;
			updateField( '#titel', showcases[index].titel );
			updateField( '#beschrijving', showcases[index].beschrijving );
			updateField( '#prijs', '&euro; ' + showcases[index].prijs );
			updateField( '#foto', showcases[index].foto_large, `<img src="${showcases[index].foto_large}" alt="foto van werkstuk" class="kleistad-showcase">` );
			updateField( '#status', showcases[index].status );
			window.history.pushState( {}, '', showcases[index].link );
		}
	}

	/**
	 * Render the showcase information
	 *
	 * @param {number} index - The index of the keramist in store.
	 */
	function updateKeramist( index ) {
		if ( currentKeramistID !== keramisten[index].id ) {
			currentKeramistID = keramisten[index].id;
			updateField( '#bio', keramisten[index].bio );
			updateField( '#keramist', keramisten[index].naam );
			updateField( '#website', keramisten[index].website, `<a href="${keramisten[index].website}">${keramisten[index].website}</a>` );
			updateField( '#keramist_foto', keramisten[index].foto, `<img src="${keramisten[index].foto}" alt="foto van keramist">` );
		}
	}

	/**
	 * Find other showcases of the keramist and render these.
	 *
	 * @param {number}     index - The index of the keramist in store.
	 */
	function updateKeramistGallery( index ) {
		let keramistShowcases = showcases.filter(
			/**
			 * Find the keramist
			 *
			 * @param {Showcase} showcase
			 * @return {boolean}
			 */
			function( showcase ) {
				return ( keramisten[index].id === showcase.keramist_id );
			}
		)
		if ( keramistShowcases.length ) {
			$( '#meer_panel' ).html(
				keramistShowcases.map(
					function( showcase ) {
						if ( showcase.foto_small && showcase.id !== currentShowcaseID ) {
							return `<div class="kleistad-gallerij-item" style="margin:10px;width: 100px;">
								<a class="showcase-link" data-id="${showcase.id}">
									<img src="${showcase.foto_small}" class="kleistad-gallerij-keramist" alt="werkstuk van keramist">
								</a></div>`;
						}
						return '';
					}
				).join()
			).show();
		} else {
			$( '#meer_panel' ).hide();
		}
	}

	/**
	 * Render the dynamic parts of the page
	 *
	 * @param {number} showcase_id The ID of the showcase.
	 * @return {boolean} If the update was successfull
	 */
	function update( showcase_id ) {
		let showcaseIndex = findShowcase( showcase_id );
		if ( undefined !== showcaseIndex ) {
			let keramistIndex = findKeramist( showcaseIndex );
			updateShowcase( showcaseIndex );
			updateShowcaseLinks( showcaseIndex );
			updateKeramist( keramistIndex );
			updateKeramistGallery( keramistIndex );
			$( '#showcase' ).get(0).scrollIntoView();
			return true;
		}
		return false;
	}

	/**
	 * Document ready.
	 */
	$(
		function()
		{
			getDataFromServer();

			let $container = $( '#showcase' );
			if ( ! update( $container.data( 'id' ) ) ) {
				$container.html( 'Het werkstuk is helaas niet meer beschikbaar' );
			}

			$container.on(
				'click',
				'.showcase-link',
				function() {
					update( $( this ).data( 'id' ) );
				}
			)
		}
	);

} )( jQuery );
