<?php

use MediaWiki\MediaWikiServices;

class BannerAdsApi extends ApiBase {

	public function addResultValues($code, $value) {
		$result = $this->getResult();
		if ($code == 'success') {
			$result->addValue( 'result', $code, $value, ApiResult::OVERRIDE);
		} else if ($code == 'failed' && array_key_exists('failed', $this->getResult()->getData()['result'])) {
			return;
		} else if ( is_array($value) ) {
			// $warnings = (array) $this->getResult()->getResultData()['result'][$code];
			// $warnings = array_merge( $warnings, $value );
			// dieq( $value, $this->getResult()->getResultData() );
			$result->addValue( 'result', $code, $value, ApiResult::OVERRIDE);
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
		} else if ( $this->getMain()->getVal('ba_action') == "fetch_stats_display" ) {
			$this->getStatsDisplay();
		} else if ( $this->getMain()->getVal('ba_action') == "get_campaigns" ) {
			$this->getCampaignList();
		} else if ( $this->getMain()->getVal('ba_action') == "get_adsets" ) {
			$this->getAdsetIds();
		} else if ( $this->getMain()->getVal('ba_action') == "delete_camp" ) {
			$this->deleteCampaign();
		} else if ( $this->getMain()->getVal('ba_action') == "create_camp" ) {
			$this->createCampaign();
		} else if ( $this->getMain()->getVal('ba_action') == "create_adset" ) {
			$this->createAdSet();
		} else if ( $this->getMain()->getVal('ba_action') == "create_ad" ) {
			$this->createAd();
		} else if ( $this->getMain()->getVal('ba_action') == "delete_ad" ) {
			$this->deleteAd();
		} else if ( $this->getMain()->getVal('ba_action') == "add_target" ) {
			$this->addTarget();
		} else if ( $this->getMain()->getVal('ba_action') == "delete_target" ) {
			$this->deleteTarget();
		}
	}

	/**
	 * Get BannerAds's custom upload directory (within $wgUploadDirectory).
	 * @return string Full filesystem path with no trailing slash.
	 */
	protected function getUploadDir() {
		$uploadDirectory = MediaWikiServices::getInstance()
			->getMainConfig()
			->get( 'UploadDirectory' );
		$uploadDir = $uploadDirectory . '/BannerAds';
		if ( !is_dir( $uploadDir ) ) {
			mkdir( $uploadDir );
		}
		$uploadDirFull = rtrim( realpath( $uploadDir ), DIRECTORY_SEPARATOR );
		if ( !is_dir( $uploadDirFull ) ) {
			throw new Exception( "Unable to create directory: $uploadDir" );
		}
		return $uploadDirFull;
	}

	public function deleteTarget() {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete(
			'ba_campaign_pages',
			[ 
				"id" => $this->getMain()->getVal( "target_id" ),
			],
			__METHOD__,
			array( 'IGNORE' )
		);
		$dbw->commit();
		$this->getResult()->addValue( "result", "success", "Success!" );
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

	public function deleteAd() {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete(
			'ba_ad',
			[ 
				"id" => $this->getMain()->getVal( "ad_id" ),
			],
			__METHOD__,
			array( 'IGNORE' )
		);
		$dbw->commit();
		$this->getResult()->addValue( "result", "success", "Success!" );
	}

	public function createAd() {
		global $wgServer, $wgScriptPath;

		$uploadDir = $this->getUploadDir();
		$fileTmpPath = $_FILES['ad_img']['tmp_name'];
		$fileName = $_FILES['ad_img']['name'];
		$fileSize = $_FILES['ad_img']['size'];
		$fileType = $_FILES['ad_img']['type'];
		$fileNameCmps = explode(".", $fileName);
		$fileExtension = strtolower(end($fileNameCmps));
		$newFileName = md5(time() . $fileName) . '.' . $fileExtension;
		$dest_path = $uploadDir . "/" . $newFileName;

 		if( move_uploaded_file( $fileTmpPath, $dest_path ) ) {

			$dbw = wfGetDB( DB_MASTER );
			$dbw->insert(
				'ba_ad',
				[ 
					"name" => $this->getMain()->getVal( "name" ),
					"adset_id" => $this->getMain()->getVal( "adset_id" ),
					"ad_type" => $this->getMain()->getVal( "ad_type" ),
					"ad_img_url" => $wgServer . $wgScriptPath . '/images/BannerAds/' . basename( $dest_path ),
					"ad_url" => $this->getMain()->getVal( "ad_url" )
				],
				__METHOD__,
				array( 'IGNORE' )
			);
			$dbw->commit();
		} else {
			$this->getResult()->addValue( "result", "failed", "Could not upload file" );
			return;
		}
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

	public function deleteCampaign() {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete(
			'ba_campaign',
			[ 
				"id" => $this->getMain()->getVal( "camp_id" ),
			],
			__METHOD__,
			array( 'IGNORE' )
		);
		$dbw->commit();
		$this->getResult()->addValue( "result", "success", "Success!" );
	}

	public function createCampaign() {
		$dbw = wfGetDB( DB_MASTER );

		$start_date = $this->getMain()->getVal( "start_date" );
		if ( empty( $start_date ) ) {
			$start_ts = (new DateTime())->modify()->getTimestamp();
		} else {
			$start_ts = DateTime::createFromFormat("d M y", $start_date )->getTimestamp();
		}

		$end_date = $this->getMain()->getVal( "end_date" );
		if ( empty( $end_date ) ) {
			$end_ts = (new DateTime())->modify( '+365 days' )->getTimestamp();
		} else {
			$end_ts = DateTime::createFromFormat("d M y", $end_date )->getTimestamp();
		}

		$dbw->insert(
			'ba_campaign',
			[ 
				"name" => $this->getMain()->getVal( "name" ),
				"start_date" => $start_ts,
				"end_date" => $end_ts
			],
			__METHOD__,
			array( 'IGNORE' )
		);
		$dbw->commit();
		$adset_id = $dbw->insertId();
		$dbw->insert(
			'ba_adset',
			[ 
				'id' => $adset_id,
				"name" => $this->getMain()->getVal( "name" ),
			],
			__METHOD__,
			array( 'IGNORE' )
		);
		$dbw->update(
			'ba_campaign',
			[ 'adset_id' => $adset_id ],
			[ 'id' => $adset_id ],
			__METHOD__,
			array( 'IGNORE' )
		);
		$dbw->commit();
		$this->getResult()->addValue( "result", "success", "Success!" );
	}

	public function getAdsetIds() {
		$dbr = wfGetDB( DB_SLAVE );
		$adsets = $dbr->select(
			"ba_adset",
			"*",
			"true",
			__METHOD__
		);
		$results = [];
		foreach( $adsets as $adset ) {
			$results[$adset->id] = $adset->name;
		}
		$this->addResultValues( "adsets", $results );
		$this->getResult()->addValue( "result", "success", "success" );
	}

	public function getCampaignList() {
		$dbr = wfGetDB( DB_SLAVE );
		$campaigns = $dbr->select(
			"ba_campaign",
			"*",
			"true",
			__METHOD__
		);
		$results = [];
		foreach( $campaigns as $campaign ) {
			$results[$campaign->id] = $campaign->name;
		}
		$this->addResultValues( "campaigns", $results );
		$this->getResult()->addValue( "result", "success", "success" );
	}

	public function getStatsDisplay() {
		$dbr = wfGetDB( DB_SLAVE );
		$stats = $dbr->select(
			"ba_ad_stats",
			"*",
			[ 'camp_id' => $this->getMain()->getVal( 'camp_id' ) ],
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
		$this->getResult()->addValue( "result", "success", "success" );
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
					<th>Start Date</th>
					<th>End Date</th>
					<th>Action</th>
				</tr>
		';

		foreach( $campaigns as $campaign ) {
			$campaign_html .= '
				<tr>
					<td>'. $campaign->id .'</td>
					<td>'. $campaign->name .'</td>
					<td>'. (new DateTime())->setTimestamp( $campaign->start_date )->format("d M y") .'</td>
					<td>'. (new DateTime())->setTimestamp( $campaign->end_date )->format("d M y") .'</td>
					<td><button type="button" class="btn btn-danger api_action" data-camp_id="'. $campaign->id .'" data-ba_action="delete_camp" data-action="banner_ads" data-format="json">Delete</button></td>
				</tr>
			';
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
					<th>Ad Name</th>
					<th>Ad Type</th>
					<th>Ad Img</th>
					<th>Ad URL</th>
					<th>Action</th>
				</tr>
		';

		foreach( $ads as $ad ) {
			$ads_html .= '
				<tr>
					<td>'. $ad->id .'</td>
					<td>'. $ad->name .'</td>
					<td>'. BannerAdsProcessor::$ad_types[$ad->ad_type] .'</td>
					<td>'. $ad->ad_img_url .'</td>
					<td>'. $ad->ad_url .'</td>
					<td><button type="button" class="btn btn-danger api_action" data-ad_id="'. $ad->id .'" data-ba_action="delete_ad" data-action="banner_ads" data-format="json">Delete</button></td>
				</tr>
			';
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
					<th>Action</th>
				</tr>
		';

		foreach( $targetings as $targeting ) {
			$wikipage = WikiPage::newFromID( $targeting->page_id );
			if ( empty( $wikipage ) ) {
				continue;
			}
			$targeting_html .= '
				<tr>
					<td>'. $targeting->camp_id .'</td>
					<td>'. $wikipage->getTitle()->getText() .'</td>
					<td><button type="button" class="btn btn-danger api_action" data-target_id="'. $targeting->id .'" data-ba_action="delete_target" data-action="banner_ads" data-format="json">Delete</button></td>
				</tr>
			';
		}

		$targeting_html .= "</table>";
		$this->addResultValues( "targeting_html", $targeting_html );

		$this->getResult()->addValue( "result", "success", "Refreshed!" );
	}

}