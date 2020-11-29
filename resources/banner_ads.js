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

			if ( typeof( data.result.campaign_html ) != "undefined" ) {
				$( '#camp_list' ).html( data.result.campaign_html );
			} else {
				refreshPage();
			}
			if ( typeof( data.result.adsets_html ) != "undefined" ) {
				$( '#ad_sets_list' ).html( data.result.adsets_html );
			}
			if ( typeof( data.result.ads_html ) != "undefined" ) {
				$( '#ads_list' ).html( data.result.ads_html );
			}
			if ( typeof( data.result.targeting_html ) != "undefined" ) {
				$( '#ad_target_list' ).html( data.result.targeting_html );
			}
			if ( typeof( data.result.stats_html ) != "undefined" ) {
				$( '#stats_list' ).html( data.result.stats_html );
			}
			$( 'sortable' ).tablesorter();

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

	function refreshPage() {
		$.post(
			wgScriptPath + '/api.php',
			{ 'action': 'banner_ads', 'ba_action': 'fetch_ad_display', 'format': 'json' },
			function(data) {
				success(data);
			}
		).fail(failed);
	};

	function showForm( form_name, form_id, html ) {
		$.confirm({
			theme: 'modern',
			columnClass: 'large',
			title: form_name,
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
							'<div>AdSet ID: (Adset needs to be already created)<br><input type="text" name="adset_id"></div>' +
							'<div>End Date: (Format 23 May 20)<br><input type="text" name="end_date"></div>' +
						'</div>' +
					'</div>' +
				'</form>'
			);
			showForm( "Create Campaign", "create_campaign", formContent );
		});

		$('#create_adset').click( function() {
			formContent = $.parseHTML( 
				'<form id="create_adset_form">' +
					'<input name="action" value="banner_ads" type="hidden"/>' +
					'<input name="ba_action" value="create_adset" type="hidden"/>' +
					'<input name="format" value="json" type="hidden"/>' +
					'<div class="container-fluid">' +
						'<div style="text-align:left;">' +
							'<div>Name: <br><input type="text" name="name"></div>' +
						'</div>' +
					'</div>' +
				'</form>'
			);
			showForm( "Create Adset", "create_adset_form", formContent );
		});

		$('#create_ad').click( function() {
			formContent = $.parseHTML( 
				'<form id="create_ad_form">' +
					'<input name="action" value="banner_ads" type="hidden"/>' +
					'<input name="ba_action" value="create_ad" type="hidden"/>' +
					'<input name="format" value="json" type="hidden"/>' +
					'<div class="container-fluid">' +
						'<div style="text-align:left;">' +
							'<div>Ad Name: <br><input type="text" name="name"></div>' +
							'<div>AdSet ID: <br><input type="text" name="adset_id"></div>' +
							'<div>Ad Type: <select name="ad_type"><option value="0">Mobile</option></select></div>' +
							'<div>Ad Image URL: <br><input type="text" name="ad_img_url"></div>' +
							'<div>Ad Click URL: <br><input type="text" name="ad_url"></div>' +
						'</div>' +
					'</div>' +
				'</form>'
			);
			showForm( "Create Ad", "create_ad_form", formContent );
		});

		$('#add_target').click( function() {
			formContent = $.parseHTML( 
				'<form id="add_new_target">' +
					'<input name="action" value="banner_ads" type="hidden"/>' +
					'<input name="ba_action" value="add_target" type="hidden"/>' +
					'<input name="format" value="json" type="hidden"/>' +
					'<div class="container-fluid">' +
						'<div style="text-align:left;">' +
							'<div>Campaign ID: <br><input type="text" name="camp_id"></div>' +
							'<div>Page Name: <br><input type="text" name="title"></div>' +
						'</div>' +
					'</div>' +
				'</form>'
			);
			showForm( "Add Target", "add_new_target", formContent );
		});

		refreshPage();

	});

} )( jQuery );
