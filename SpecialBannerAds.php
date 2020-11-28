<?php

class SpecialBannerAds extends SpecialPage {

	public function __construct() {
		parent::__construct( 'BannerAds' );
	}

	function execute( $subpage ) {
		global $wgUser;
		if ( !$wgUser->isLoggedIn() ) {
			$this->getOutput()->redirect( SpecialPage::getTitleFor( 'Userlogin' )->getFullURL( 'returnto=Special:BannerAds' ) );
			return;
		}
		if ( !in_array( 'sysop', $wgUser->getEffectiveGroups() ) ) {
			$this->getOutput()->addHTML( 'You do not have the necessary permissions to view this page.' );
			return;
		}

		$html = '
<div class="" style="">
	<ul id="tabs" class="nav nav-tabs" role="tablist">
	  <li role="presentation" class="nav-item"><a class="nav-link active" aria-controls="campaigns" role="tab" href="#campaigns" data-toggle="tabs">Campaigns</a></li>
	  <li role="presentation" class="nav-item"><a class="nav-link" aria-controls="ad_sets" role="tab" href="#ad_sets" data-toggle="tabs">Ad Sets</a></li>
	  <li role="presentation" class="nav-item"><a class="nav-link" aria-controls="ads" role="tab" href="#ads" data-toggle="tabs">Ads</a></li>
	  <li role="presentation" class="nav-item"><a class="nav-link" aria-controls="stats" role="tab" href="#stats" data-toggle="tabs">Stats</a></li>
	</ul>
	<div class="tab-content card panel-default">
		<div role="tabpanel" class="tab-pane active card-body" id="campaigns">
			<button type="button" class="btn btn-primary" id="create_camp">Create Campaign</button>
			<div id="camp_list" style="margin-top:10px;"></div>
		</div>
		<div role="tabpanel" class="tab-pane card-body" id="ad_sets">
			<button type="button" class="btn btn-primary" id="create_adset">Create AdSet</button>
			<div id="ad_sets_list" style="margin-top:10px;"></div>
		</div>
		<div role="tabpanel" class="tab-pane card-body" id="ads">
			<button type="button" class="btn btn-primary" id="create_ad">Create Ad</button>
			<div id="ads_list" style="margin-top:10px;"></div>
		</div>
		<div role="tabpanel" class="tab-pane card-body" id="stats">
			<div id="stats_list" style="margin-top:10px;"></div>
		</div>
	</div>
</div>
		';

		$this->getOutput()->addHTML( $html );
		$this->getOutput()->addModules( 'ext.bootstrap' );
		$this->getOutput()->addModules( 'ext.jquery_confirm' );
		$this->getOutput()->addModules( 'ext.banner_ads.main' );
	}
}