/**
 * "Save PNG" buttons on a thumbnail page. Each size is rendered in a hidden
 * iframe of that exact width, so the CSS picks the right layout (16:9 from
 * 576px up, 4:5 below), and html-to-image draws the .mtg-thumb element of
 * that iframe at the wanted pixel ratio.
 */
( function () {
	'use strict';

	// Inside a capture or a miniature: no buttons.
	if ( window.top !== window ) {
		return;
	}

	var sizes = [
		{ label: '1280 × 720', width: 1280, height: 720, ratio: 1 },
		{ label: '1080 × 1350', width: 360, height: 450, ratio: 3 },
	];

	function loadFrame( size ) {
		return new Promise( function ( resolve, reject ) {
			var frame = document.createElement( 'iframe' );
			frame.setAttribute( 'aria-hidden', 'true' );
			frame.tabIndex = -1;
			frame.style.cssText = 'position:fixed;left:-20000px;top:0;border:0;width:' + size.width + 'px;height:' + size.height + 'px';
			frame.onload = function () {
				resolve( frame );
			};
			frame.onerror = reject;
			frame.src = window.location.href;
			document.body.appendChild( frame );
		} );
	}

	function imagesLoaded( doc ) {
		return Promise.all(
			Array.prototype.map.call( doc.images, function ( img ) {
				// Lazy images never load in an off-screen frame.
				img.loading = 'eager';
				return img.complete ? null : new Promise( function ( resolve ) {
					img.onload = img.onerror = resolve;
				} );
			} )
		);
	}

	function save( size, button ) {
		var text = button.textContent;
		button.disabled = true;
		button.textContent = '…';

		return loadFrame( size )
			.then( function ( frame ) {
				var win = frame.contentWindow;
				var doc = frame.contentDocument;
				return doc.fonts.ready
					.then( function () {
						return imagesLoaded( doc );
					} )
					.then( function () {
						return win.htmlToImage.toPng( doc.querySelector( '.mtg-thumb' ), {
							pixelRatio: size.ratio,
							width: size.width,
							height: size.height,
						} );
					} )
					.then( function ( url ) {
						var slug = window.location.pathname.split( '/' ).filter( Boolean ).pop() || 'thumbnail';
						var link = document.createElement( 'a' );
						link.href = url;
						link.download = slug + '-' + size.width * size.ratio + 'x' + size.height * size.ratio + '.png';
						document.body.appendChild( link );
						link.click();
						link.remove();
					} )
					.finally( function () {
						frame.remove();
					} );
			} )
			.catch( function ( error ) {
				window.console.error( error );
			} )
			.finally( function () {
				button.disabled = false;
				button.textContent = text;
			} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var bar = document.createElement( 'div' );
		bar.className = 'mtg-export';
		bar.setAttribute( 'role', 'group' );
		bar.setAttribute( 'aria-label', 'Save PNG' );

		sizes.forEach( function ( size ) {
			var button = document.createElement( 'button' );
			button.type = 'button';
			button.textContent = 'PNG ' + size.label;
			button.addEventListener( 'click', function () {
				save( size, button );
			} );
			bar.appendChild( button );
		} );

		document.body.appendChild( bar );
	} );
}() );
