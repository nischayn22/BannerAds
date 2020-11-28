<?php

class BannerAdsHooks {

	public static function onRecordClick( $params ) {
	}

	/**
	 * Add banner to skins which output banners into the site notice area.
	 * @param string|bool &$siteNotice of the page.
	 * @param Skin $skin being used.
	 */
	public static function onSiteNoticeAfter( &$siteNotice, Skin $skin ) {
		global $wgScriptPath;

		$campaign_id = 1;
		$ad_id = 1;
		$external_url = "";

		$siteNotice = '
			<div>
				<a href="'. $wgScriptPath .'/api.php?action=track_clicks&track_app=banner_ads&campaign_id='. $campaign_id .'&ad_id='. $ad_id .'&external_url='. $external_url .'">
					<img src="https://tpc.googlesyndication.com/simgad/6306868953523201331" border="0" width="970" height="90" alt="" class="img_ad">
				</a>
			</div>
		';
	}

	function onLoadExtensionSchemaUpdate( $updater ) {
		$updater->addExtensionTable( 'ba_campaign',
			__DIR__ . '/bannerads.sql', true );
		$updater->addExtensionTable( 'ba_campaign_pages',
			__DIR__ . '/bannerads.sql', true );
		$updater->addExtensionTable( 'ba_adset',
			__DIR__ . '/bannerads.sql', true );
		$updater->addExtensionTable( 'ba_ad',
			__DIR__ . '/bannerads.sql', true );
		$updater->addExtensionTable( 'ba_ad_stats',
			__DIR__ . '/bannerads.sql', true );
		return true;
	}
}