(function( $ ) {

	function failed(){
		alert( "Failed doing last operation, check internet connection!" );
	};

	function success(data){
		if ( typeof( data.result ) != 'undefined' ) {
			if ( typeof( data.result.notification ) != 'undefined' ) {
				$.each( data.result.notification, function( k, v ) {
					mw.notify( v );
				} );
			}

			if (typeof(data.result.success) != 'undefined') {
				mw.notify(data.result.success);
				$('.temp-data').remove();
			} else if ( typeof( data.result.error ) != 'undefined' ) {
				mw.notify("Error: " + data.result.error.info );
			} else {
				alert("ERROR " + data.result.failed);
			}
		}

		if ( typeof ( data.error ) != 'undefined' ) {
			if ( typeof( data.error.info ) != 'undefined' ) {
				alert( "ERROR : " + data.error.info );
			}
		}
	};

	function showForm( form_id, html ) {
		$.confirm({
			theme: 'modern',
			columnClass: 'large',
			title: 'Create Campaign',
			content: function() {
				content = html;
				return content[0].outerHTML;
			},
			type: 'orange',
			typeAnimated: true,
			buttons: {
				confirm: {
					text: 'Create',
					btnClass: 'btn-orange',
					action: function () {
						mw.notify( "Processing..." );
						$.post(
							wgScriptPath + '/api.php',
							$( '#' + form_id ).serialize(),
							function(data) {
								success(data);
							}
						).fail(failed);
					}
				},
				cancel: function () {
				}
			}
		});
	};

	$(document).ready(function () { //jquery
		$('#tabs a').click(function (e) {
			e.preventDefault();
			$(this).tab('show');
			tab = $(this).attr('href');
		});

		$('#create_camp').click( function() {
			formContent = $.parseHTML( 
				'<form id="create_campaign">' +
					'<input name="action" value="banner_ads" type="hidden"/>' +
					'<input name="ba_action" value="create_camp" type="hidden"/>' +
					'<input name="format" value="json" type="hidden"/>' +
					'<div class="container-fluid">' +
						'<div style="text-align:left;">' +
							'<div>Campaign Name: <br><input type="text" name="name"></div>' +
							'<div>AdSet: <br><input type="text" name="adset_id"></div>' +
							'<div>End Date: <br><input type="text" name="end_date"></div>' +
						'</div>' +
					'</div>' +
				'</form>'
			);
			showForm( "create_campaign", formContent );
		});

		$('#create_adset').click( function() {
			formContent = $.parseHTML( 
				'<form id="create_adset">' +
					'<input name="action" value="banner_ads" type="hidden"/>' +
					'<input name="ba_action" value="create_adset" type="hidden"/>' +
					'<input name="format" value="json" type="hidden"/>' +
					'<div class="container-fluid">' +
						'<div style="text-align:left;">' +
							'<div>Ad Set Name: <br><input type="text" name="name"></div>' +
						'</div>' +
					'</div>' +
				'</form>'
			);
			showForm( "create_adset", formContent );
		});

		$('#create_ad').click( function() {
			formContent = $.parseHTML( 
				'<form id="create_ad">' +
					'<input name="action" value="banner_ads" type="hidden"/>' +
					'<input name="ba_action" value="create_ad" type="hidden"/>' +
					'<input name="format" value="json" type="hidden"/>' +
					'<div class="container-fluid">' +
						'<div style="text-align:left;">' +
							'<div>Ad Name: <br><input type="text" name="name"></div>' +
							'<div>AdSet: <br><input type="text" name="adset_id"></div>' +
							'<div>Ad Image URL: <br><input type="text" name="ad_img_url"></div>' +
							'<div>Ad Click URL: <br><input type="text" name="ad_url"></div>' +
						'</div>' +
					'</div>' +
				'</form>'
			);
			showForm( "create_ad", formContent );
		});

	});

} )( jQuery );
