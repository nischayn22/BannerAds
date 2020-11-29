<?php

class BannerAdsApi extends ApiBase {

	public function addResultValues($code, $value) {
		$result = $this->getResult();
		if ($code == 'success') {
			$result->addValue( 'result', $code, $value, ApiResult::OVERRIDE);
		} else if ($code == 'failed' && array_key_exists('failed', $this->getResult()->getData()['result'])) {
			return;
		} else if ( is_array($value) ) {
			$warnings = (array) $this->getResult()->getData()['result'][$code];
			$warnings = array_merge( $warnings, $value );
			$result->addValue( 'result', $code, $warnings, ApiResult::OVERRIDE);
		} else {
			$result->addValue( 'result', $code, $value);
		}
	}

	public function execute() {
		global $wgUser;

		if ( !$wgUser->isLoggedIn() ) {
			$this->getResult()->addValue( "result", "failed", "Not logged in." );
			return;
		}

		$userIsNotAdmin = !in_array( 'sysop', $wgUser->getEffectiveGroups());
		if ( $userIsNotAdmin ) {
			$this->getResult()->addValue( "result", "failed", "Access Denied" );
			return;
		}

		if ( $this->getMain()->getVal('ba_action') == "fetch_ad_display" ) {
			$this->fetchAdDisplay();
		} else if ( $this->getMain()->getVal('ba_action') == "create_camp" ) {
			$this->createCampaign();
		} else if ( $this->getMain()->getVal('ba_action') == "create_adset" ) {
			$this->createAdSet();
		} else if ( $this->getMain()->getVal('ba_action') == "create_ad" ) {
			$this->createAd();
		} else if ( $this->getMain()->getVal('ba_action') == "add_target" ) {
			$this->addTarget();
		}
	}


	public function addTarget() {
		$title = Title::newFromText( $this->getMain()->getVal( "title" ) );
		$pageObj = WikiPage::factory( $title );
		if ( empty( $pageObj->getId() ) ) {
			$this->getResult()->addValue( "result", "failed", "Invalid Page" );
			return;
		}

		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
			'ba_campaign_pages',
			[ 
				"camp_id" => $this->getMain()->getVal( "camp_id" ),
				"page_id" => $pageObj->getId()
			],
			__METHOD__,
			array( 'IGNORE' )
		);
		$dbw->commit();
		$this->getResult()->addValue( "result", "success", "Success!" );
	}

	public function createAd() {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
			'ba_ad',
			[ 
				"name" => $this->getMain()->getVal( "name" ),
				"adset_id" => $this->getMain()->getVal( "adset_id" ),
				"ad_type" => $this->getMain()->getVal( "ad_type" ),
				"ad_img_url" => $this->getMain()->getVal( "ad_img_url" ),
				"ad_url" => $this->getMain()->getVal( "ad_url" )
			],
			__METHOD__,
			array( 'IGNORE' )
		);
		$dbw->commit();
		$this->getResult()->addValue( "result", "success", "Success!" );
	}

	public function createAdSet() {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
			'ba_adset',
			[ 
				"name" => $this->getMain()->getVal( "name" ),
			],
			__METHOD__,
			array( 'IGNORE' )
		);
		$dbw->commit();
		$this->getResult()->addValue( "result", "success", "Success!" );
	}

	public function createCampaign() {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
			'ba_campaign',
			[ 
				"name" => $this->getMain()->getVal( "name" ),
				"adset_id" => $this->getMain()->getVal( "adset_id" ),
				"end_date" => DateTime::createFromFormat("d M y", $this->getMain()->getVal( "end_date" ) )->getTimestamp()
			],
			__METHOD__,
			array( 'IGNORE' )
		);
		$dbw->commit();
		$this->getResult()->addValue( "result", "success", "Success!" );
	}

	public function fetchAdDisplay() {
		$dbr = wfGetDB( DB_SLAVE );
		$campaigns = $dbr->select(
			"ba_campaign",
			"*",
			"true",
			__METHOD__
		);

		$campaign_html = '
			<table class="wikitable">
				<tr>
					<th>Campaign ID</th>
					<th>Campaign Name</th>
					<th>End Date</th>
				</tr>
		';

		foreach( $campaigns as $campaign ) {
			$campaign_html .= "
				<tr>
					<td>". $campaign->id ."</td>
					<td>". $campaign->name ."</td>
					<td>". (new DateTime())->setTimestamp( $campaign->end_date )->format("d M y") ."</td>
				</tr>
			";
		}

		$campaign_html .= "</table>";
		$this->addResultValues( "campaign_html", $campaign_html );

		$adsets = $dbr->select(
			"ba_adset",
			"*",
			"true",
			__METHOD__
		);

		$adsets_html = '
			<table class="wikitable">
				<tr>
					<th>AdSet ID</th>
					<th>AdSet Name</th>
				</tr>
		';

		foreach( $adsets as $adset ) {
			$adsets_html .= "
				<tr>
					<td>". $adset->id ."</td>
					<td>". $adset->name ."</td>
				</tr>
			";
		}

		$adsets_html .= "</table>";
		$this->addResultValues( "adsets_html", $adsets_html );

		$ads = $dbr->select(
			"ba_ad",
			"*",
			"true",
			__METHOD__
		);

		$ads_html = '
			<table class="wikitable">
				<tr>
					<th>Ad ID</th>
					<th>AdSet ID</th>
					<th>Ad Name</th>
					<th>Ad Type</th>
					<th>Ad Img</th>
					<th>Ad URL</th>
				</tr>
		';

		foreach( $ads as $ad ) {
			$ads_html .= "
				<tr>
					<td>". $ad->id ."</td>
					<td>". $ad->adset_id ."</td>
					<td>". $ad->name ."</td>
					<td>". BannerAdsProcessor::$ad_types[$ad->ad_type] ."</td>
					<td>". $ad->ad_img_url ."</td>
					<td>". $ad->ad_url ."</td>
				</tr>
			";
		}

		$ads_html .= "</table>";
		$this->addResultValues( "ads_html", $ads_html );

		$targetings = $dbr->select(
			"ba_campaign_pages",
			"*",
			"true",
			__METHOD__
		);

		$targeting_html = '
			<table class="wikitable">
				<tr>
					<th>Campaign ID</th>
					<th>Page</th>
				</tr>
		';

		foreach( $targetings as $targeting ) {
			$wikipage = WikiPage::newFromID( $targeting->page_id );
			if ( empty( $wikipage ) ) {
				continue;
			}
			$targeting_html .= "
				<tr>
					<td>". $targeting->camp_id ."</td>
					<td>". $wikipage->getTitle()->getText() ."</td>
				</tr>
			";
		}

		$targeting_html .= "</table>";
		$this->addResultValues( "targeting_html", $targeting_html );

		$stats = $dbr->select(
			"ba_ad_stats",
			"*",
			"true",
			__METHOD__
		);

		$stats_html = '
			<table class="wikitable sortable">
				<tr>
					<th>Campaign ID</th>
					<th>Ad ID</th>
					<th>Page</th>
					<th>Counter</th>
				</tr>
		';

		foreach( $stats as $stat ) {
			$wikipage = WikiPage::newFromID( $stat->page_id );
			if ( empty( $wikipage ) ) {
				continue;
			}
			$stats_html .= "
				<tr>
					<td>". $stat->camp_id ."</td>
					<td>". $stat->ad_id ."</td>
					<td>". $wikipage->getTitle()->getText() ."</td>
					<td>". $stat->counter ."</td>
				</tr>
			";
		}

		$targeting_html .= "</table>";
		$this->addResultValues( "stats_html", $stats_html );
		$this->getResult()->addValue( "result", "success", "Refreshed!" );
	}

}