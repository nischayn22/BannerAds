<?php

class BannerAdsProcessor {

	const AD_TYPE_MOBILE = 0;

	static $ad_types = [
		self::AD_TYPE_MOBILE => "Mobile",
	];

	public static function getMobileAd() {
		global $wgTitle;

		if ( $wgTitle->getNamespace() != NS_MAIN ) {
			return;
		}

		$page_id = WikiPage::factory( $wgTitle )->getId();

		$dbr = wfGetDB( DB_SLAVE );
		$ts_now = ( new DateTime('NOW') )->getTimestamp();

		// TODO: Filter by this page
		$campaigns = $dbr->select(
			["ba_campaign", "ba_campaign_pages" ],
			"*",
			[ "end_date > " . $ts_now, "page_id" => $page_id ],
			__METHOD__,
			array(),
			array( "ba_campaign_pages" => array( "JOIN", array( "ba_campaign.id=camp_id" ) ) )
		);

		foreach( $campaigns as $campaign ) {
			$ads = $dbr->select(
				"ba_ad",
				"*",
				[ "adset_id" => $campaign->adset_id ],
				__METHOD__
			);

			foreach( $ads as $ad ) {
				if ( $ad->ad_type == self::AD_TYPE_MOBILE ) {
					return [
						$campaign->id,
						$ad->id,
						$ad->ad_img_url,
						$ad->ad_url,
						$page_id
					];
				}
			}
		}
	}
}