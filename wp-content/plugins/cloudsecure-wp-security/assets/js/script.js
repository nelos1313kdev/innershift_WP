/**
 * REST API無効化ページのボタンの処理
 *
 * @package REST API無効化ページボタン
 */
document.addEventListener(
	'DOMContentLoaded',
	function()
	{
		let disableRestApiExclude = document.querySelector( '#disable_rest_api_exclude' );
		let activePlugins         = document.querySelector( '#active-plugins' );
		if ( activePlugins ) {
			let btnExcludeList = activePlugins.querySelectorAll( '.btn-exclude' );
			btnExcludeList.forEach(
				function(btnExclude)
				{
					btnExclude.addEventListener(
						'click',
						function()
						{
							let li = this.closest( 'li' );
							if ( disableRestApiExclude.value === '' ) {
								disableRestApiExclude.value += li.querySelector( 'span' ).textContent;
							} else {
								disableRestApiExclude.value += String.fromCharCode( 10 ) + li.querySelector( 'span' ).textContent;
							}
							li.parentNode.removeChild( li );
						}
					);
				}
			);
		}
	}
);
