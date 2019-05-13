<?php
/**
 *
 */

// no direct call
if(!defined('GRAPE')) die('Direct access not permitted');
/**
 *
 */
function canvassing_run(){
	global $grape;
	switch ($_REQUEST["job"]){
		case "statistics":
			canvassing_statistics();
			break;
		case "map_region":
			canvassing_map_region();
			break;
		case "map_region_ajax":
			canvassing_map_region_ajax();
			break;
		case "regional_ranking":
			canvassing_regional_ranking();
			break;
		case "abandoned_streets":
			canvassing_abandoned_streets();
			break;
		case "search_street":
			canvassing_search_street_ui();
			break;
		case "selectStreet":
			canvassing_select_street();
			break;
		case "ringThatBell":
			canvassing_ring_that_bell();
			break;
		case "map_electoral_ward":
			canvassing_map_electoral_ward();
			break;
		case "map_electoral_ward_ajax":
			canvassing_map_electoral_ward_ajax();
			break;
		case "takeStreet":
			canvassing_take_street();
			canvassing_ring_that_bell();
			break;
		case "saveContacts":
			canvassing_save_contacts();
			break;
		case "streetComment":
			canvassing_street_comment();
			break;
		case "streetCommentSave":
			canvassing_street_comment_save();
			canvassing_start();
			break;
		case "finishStreet":
			canvassing_finish_street();
			break;
		case "mail_street":
			canvassing_mail_street();
			break;
		case "dump_contact_data":
			canvassing_dump_contact_data();
			break;
		case "dump_street_data":
			canvassing_dump_street_data();
			break;
		case "delete_data":
			canvassing_delete_data();
			break;
		default:
			canvassing_start();
			break;
	}
}
/**
 * Enough data for module in ou?
 */
function canvassing_check_preconditions(){
	global $grape;
	// are there 
}
/**
 *
 */
function canvassing_build_menu(){
	global $grape;
	$campaign_id = intval($_REQUEST["campaign_id"]);
	$grape->output->add_menu_item(array("url"=>URL."?module=canvassing&campaign_id=$campaign_id&job=statistics","name"=>"Statistik"));
	$grape->output->add_menu_item(array("url"=>URL."?module=canvassing&campaign_id=$campaign_id&job=map_region","name"=>"Karte"));
	$grape->output->add_menu_item(array("url"=>URL."?module=canvassing&campaign_id=$campaign_id&job=regional_ranking","name"=>"Rangliste"));
	$grape->output->add_menu_item(array("url"=>URL."?module=canvassing&campaign_id=$campaign_id&job=abandoned_streets","name"=>"Vergessenes"));
	//$grape->output->content->html.= $grape->output->dump_var(grape_get_capability_of_current_user_for_ou($grape->user->ou_id));
	$grape->output->add_menu_item(array("url"=>URL."?module=canvassing&campaign_id=$campaign_id&job=search_street","name"=>"Straße suchen"));
	if(grape_user_has_capability(grape_get_capability_of_current_user_for_ou($grape->user->ou_id),"admin")){
		$grape->output->add_menu_item(array("url"=>URL."?module=canvassing&campaign_id=$campaign_id&job=dump_contact_data","name"=>"Kontaktdaten herunterladen"));
		$grape->output->add_menu_item(array("url"=>URL."?module=canvassing&campaign_id=$campaign_id&job=delete_data","name"=>"Daten löschen"));
		//$grape->output->add_menu_item(array("url"=>URL."?module=canvassing&campaign_id=$campaign_id&job=dump_street_data","name"=>"Straßendaten herunterladen"));
	}
}
/**
 *
 */
function canvassing_statistics(){
	global $grape;
	//$grape->output->content->html.= "<pre>Welcome at module ".$_REQUEST["module"]."</pre>";
	canvassing_build_menu();
	$campaign_id = intval($_REQUEST["campaign_id"]);
	$campaign = grape_campaign_by_campaign_id($campaign_id);
	$cache_data = "";
	//$cache_data.= $grape->output->dump_var($campaign);
	/*
	$cache_filename = "cache/statistics_election".$user->election_id.".html";
	//echo "<p>$cache_filename</p>";
	//print_r($user);
	$filemtime = @filemtime($cache_filename);
	$cache_life = 360;
	if (!$filemtime or (time() - $filemtime >= $cache_life)){*/
		$global_statistics = canvassing_get_global_statistics($campaign_id);
		if($global_statistics->trials > 20){
			$html = "<h2>Das geht ".($global_statistics->trials>100?"nur":"")." gemeinsam".($global_statistics->trials>100?"":" besser")."!</h2>";
			$html.=  '<p>'.grape_number($global_statistics->trials).' gedrückte Klingelknöpfe und '.grape_number($global_statistics->contacts).' Kontakte im Rahmen der Kampagne "'.$campaign->name.'"</p>';
			$html.=  '<p>Und nun? <a href="#" onclick="load_content(\'module=canvassing&campaign_id='.$campaign_id.'\');">Weiter klingeln!</a></p>';
			$cache_data.= $grape->output->wrap_div($html);
			
			$html = "<h2>Unsere Fleißigsten</h2>";
			$html.=  '<div class="table-responsive">
						<table cellspacing=\"0\" width="100%"><tr><th align="left">Grüne*r</th><th>Versuche</th><th>davon Kontakte</th><th>davon persönlich</th><tr>';
			$statistics = canvassing_get_statistics($campaign_id);
			//$cache_data.= $grape->output->dump_var($statistics);
			foreach($statistics as $statistic){
				if($statistic->trial_count > 0) $html.= '<tr><td>'.$statistic->name.' '.substr($statistic->last_name,0,1).' ('.$statistic->ou_name.')</td><td align="center">'.grape_number($statistic->trial_count).'</td><td align="center">'.grape_number($statistic->contact_count).'</td><td align="center">'.grape_number($statistic->face2face_count).'</td></tr>';
			}
			$html.=  '</table></div>';
			$cache_data.= $grape->output->wrap_div($html);
		}
		
		$html = "<h2>Wann lohnt sich Dein Einsatz?</h2>";
		$html.= "<p>Hier werden jegliche Kontaktversuche aller Kampagnen betrachtet</p>";
		$weekday_statistics = canvassing_get_statistics_success_timebased(0,4);
		//$html.= serialize($weekday_statistics);
		$html.= "<h3>Montag bis Freitag</h3>";
		$html.=  '<p>Unter der Woche triffst Du zu diesen Zeiten Menschen an<!-- (gelber Balken = Stimmung)-->:</p>';
		$html.= canvassing_timebased_statistics_2($weekday_statistics);
		$html.= "<h3>Samstag</h3>";
		$html.=  '<p>Samstags triffst Du zu diesen Zeiten Menschen an<!-- (gelber Balken = Stimmung)-->:</p>';
		$saturday_statistics = canvassing_get_statistics_success_timebased(5);
		$html.= canvassing_timebased_statistics_2($saturday_statistics);
		$html.= "<h3>Sonntag</h3>";
		$html.=  '<p>Sonntags triffst Du zu diesen Zeiten Menschen an<!-- (gelber Balken = Stimmung)-->:</p>';
		$sunday_statistics = canvassing_get_statistics_success_timebased(6);
		$html.= canvassing_timebased_statistics_2($sunday_statistics);
		$cache_data.= $grape->output->wrap_div($html);
		$grape->output->content->html.= $cache_data;
		/*
		
		//print_r($weekday_statistics);
		*/
		$weekly_statistics = canvassing_get_weekly_statistics($campaign_id);
		$html = "<table>
					<tr>
						<th>Wochenstart</th>
						<th>Aktive</th>
						<th>Versuche</th>
						<th>Kontakte</th>
						<th>persönlich</th>
					</tr>";
		foreach($weekly_statistics as $week_data){
			$html.= "<tr>
						<td>".$week_data->start."</td>
						<td>".$week_data->results->users."</td>
						<td>".$week_data->results->trials."</td>
						<td>".$week_data->results->contacts."</td>
						<td>".$week_data->results->face2face."</td>
					</tr>";
		}
		$grape->output->content->html.= $grape->output->wrap_div($html);
		$grape->output->help->html = "Die Statistik wird alle 24 Stunden neu generiert.";
		/*file_put_contents($cache_filename,$cache_data);
	}
	else{
		echo file_get_contents($cache_filename);
	}*/
	
}
/**
 *
 */
function canvassing_get_statistics($campaign_id){
	global $grape;
	$campaign_id = intval($campaign_id);
	$cache_max_age = 60; // in minutes
	$cache_key = "campaign_user_statistic_".$campaign_id;
	$cache_content = grape_cache_get($cache_key,$cache_max_age);
	if($cache_content !== false){
		return $cache_content;
	}
	else{
		$sql = "SELECT 
					`grape_users`.*,
					`grape_organization_units`.`name` AS `ou_name`, 
					(
						SELECT COUNT(`canvassing_contacts`.`contact_id`) 
						FROM `canvassing_contacts` 
						LEFT JOIN `grape_x_wards` ON `grape_x_wards`.`x_ward_id` = `canvassing_contacts`.`x_ward_id`
						LEFT JOIN `grape_x_elections_electoral_districts` ON `grape_x_elections_electoral_districts`.`x_election_district_id` = `grape_x_wards`.`x_election_district_id`
						LEFT JOIN `grape_campaigns` ON `grape_campaigns`.`election_id` = `grape_x_elections_electoral_districts`.`election_id`
						WHERE (
							`canvassing_contacts`.`user_id` = `grape_users`.`user_id` 
							OR 
							`canvassing_contacts`.`partner_user_id` = `grape_users`.`user_id`
						)
						AND `grape_campaigns`.`campaign_id` = $campaign_id
					) AS `trial_count`,
					(
						SELECT SUM(`canvassing_contacts`.`contact`) 
						FROM `canvassing_contacts` 
						LEFT JOIN `grape_x_wards` ON `grape_x_wards`.`x_ward_id` = `canvassing_contacts`.`x_ward_id`
						LEFT JOIN `grape_x_elections_electoral_districts` ON `grape_x_elections_electoral_districts`.`x_election_district_id` = `grape_x_wards`.`x_election_district_id`
						LEFT JOIN `grape_campaigns` ON `grape_campaigns`.`election_id` = `grape_x_elections_electoral_districts`.`election_id`
						WHERE (
							`canvassing_contacts`.`user_id` = `grape_users`.`user_id` 
							OR 
							`canvassing_contacts`.`partner_user_id` = `grape_users`.`user_id`
						)
						AND `grape_campaigns`.`campaign_id` = $campaign_id
					) AS `contact_count`,
					(
						SELECT SUM(`canvassing_contacts`.`face2face`) 
						FROM `canvassing_contacts` 
						LEFT JOIN `grape_x_wards` ON `grape_x_wards`.`x_ward_id` = `canvassing_contacts`.`x_ward_id`
						LEFT JOIN `grape_x_elections_electoral_districts` ON `grape_x_elections_electoral_districts`.`x_election_district_id` = `grape_x_wards`.`x_election_district_id`
						LEFT JOIN `grape_campaigns` ON `grape_campaigns`.`election_id` = `grape_x_elections_electoral_districts`.`election_id`
						WHERE (
							`canvassing_contacts`.`user_id` = `grape_users`.`user_id` 
							OR 
							`canvassing_contacts`.`partner_user_id` = `grape_users`.`user_id`
						)
						AND `grape_campaigns`.`campaign_id` = $campaign_id
					) AS `face2face_count`
				FROM `grape_users` 
				LEFT JOIN `grape_organization_units` ON `grape_organization_units`.`ou_id` = `grape_users`.`ou_id` 
				ORDER BY `trial_count` DESC, `grape_users`.`name`";
		$grape->db->query($sql);
		$results = $grape->db->get_results();
		grape_cache_set($cache_key,$results);
		return $results;
	}
}
/**
 *
 */
function canvassing_get_weekly_statistics($campaign_id){
	global $grape;
	$campaign_id = intval($campaign_id);
	$cache_max_age = 0; // in minutes
	$cache_key = "campaign_weekly_statistic_".$campaign_id;
	$cache_content = grape_cache_get($cache_key,$cache_max_age);
	if($cache_content !== false){
		return $cache_content;
	}
	else{
		$all_results = [];
		$monday = date('Y-m-d', strtotime('Monday this week'));
		$sql = 'SELECT
					COUNT(DISTINCT `user_id`) AS users,
					COUNT(`contact_id`) AS trials,
					SUM(`contact`) AS contacts,
					SUM(`face2face`) AS face2face
				FROM `canvassing_contacts`
				WHERE `timestamp` >= "'.$monday.' 00:00:00"';
		$grape->db->query($sql);
		$results = $grape->db->get_results();
		$data = new stdClass();
		$data->start = $monday;
		$data->results = $results[0];
		$data->query = $sql;
		array_push($all_results,$data);
		$monday = date('Y-m-d', strtotime('Monday this week'));
		$last_monday = date('Y-m-d', strtotime('Monday -1 week'));
		$sql = 'SELECT
					COUNT(DISTINCT `user_id`) AS users,
					COUNT(`contact_id`) AS trials,
					SUM(`contact`) AS contacts,
					SUM(`face2face`) AS face2face
				FROM `canvassing_contacts`
				WHERE `timestamp` >= "'.$last_monday.' 00:00:00"
				AND `timestamp` < "'.$monday.' 00:00:00"';
		$grape->db->query($sql);
		$results = $grape->db->get_results();
		$data = new stdClass();
		$data->start = $monday;
		$data->results = $results[0];
		$data->query = $sql;
		array_push($all_results,$data);
		for($i=-1;$i>-15;$i--){
			$monday = date('Y-m-d', strtotime('Monday '.$i.' week'));
			$last_monday = date('Y-m-d', strtotime('Monday '.($i-1).' week'));
			$sql = 'SELECT
						COUNT(DISTINCT `user_id`) AS users,
						COUNT(`contact_id`) AS trials,
						SUM(`contact`) AS contacts,
						SUM(`face2face`) AS face2face
					FROM `canvassing_contacts`
					WHERE `timestamp` >= "'.$last_monday.' 00:00:00"
					AND `timestamp` < "'.$monday.' 00:00:00"';
			$grape->db->query($sql);
			$results = $grape->db->get_results();
			$data = new stdClass();
			$data->start = $monday;
			$data->results = $results[0];
			$data->query = $sql;
			array_push($all_results,$data);
		}
		grape_cache_set($cache_key,$all_results);
		return $all_results;
	}
}
/**
 * @param int $campaign_id
 * @return object
 */
function canvassing_get_global_statistics($campaign_id){
	global $grape;
	$election_id = intval($election_id);
	$sql = "SELECT COUNT(`contact_id`) AS trials, SUM(`contact`) AS contacts
			FROM `canvassing_contacts`
			LEFT JOIN `grape_x_wards` ON `grape_x_wards`.`x_ward_id` = `canvassing_contacts`.`x_ward_id`
			LEFT JOIN `grape_x_elections_electoral_districts` ON `grape_x_elections_electoral_districts`.`x_election_district_id` = `grape_x_wards`.`x_election_district_id`
			LEFT JOIN `grape_campaigns` ON `grape_campaigns`.`election_id` = `grape_x_elections_electoral_districts`.`election_id`
			WHERE `grape_campaigns`.`campaign_id` = $campaign_id";
	//$grape->output->content->html.= $grape->output->dump_var($sql);
	$grape->db->query($sql);
	if($grape->db->num_rows > 0){
		$results = $grape->db->get_results();
		return $results[0];
	}
	else{
		return false;
	}
}
/**
 *
 */
function canvassing_get_statistics_success_timebased($weekday_start,$weekday_stop=false){
	global $grape;
	$threshold = 30;
	$cache_max_age = 60*24; // in minutes
	$weekday_condition = false;
	$weekday_start = intval($weekday_start);
	if($weekday_stop!==false){
		$weekday_stop = intval($weekday_stop);
		if($weekday_stop>$weekday_start&&$weekday_start>-1&&$weekday_start<24&&$weekday_stop>-1&&$weekday_stop<24){
			$weekday_condition = "BETWEEN $weekday_start AND $weekday_stop";
		}
	}
	else{
		if($weekday_start>-1&&$weekday_start<24){
			$weekday_condition = "= $weekday_start";
		}
	}
	$cache_key = "canvassing_get_statistics_success_timebased_".$weekday_condition;
	$cache_content = grape_cache_get($cache_key,$cache_max_age);

	if($cache_content !== false){
		return $cache_content;
	}
	else{
		if($weekday_condition){
			$tmp = array();
			for($i=0;$i<24;$i++){
				$hour = ($i<10)?"0".$i:$i;
				$hourPlusOne = (($i+1)<10)?"0".($i+1):$i+1;
				array_push($tmp,'(	SELECT
										CASE WHEN COUNT(`contact_id`) >= '.$threshold.'
											THEN
												SUM(`contact`)/COUNT(`contact_id`)
											ELSE
												NULL
										END
										FROM `canvassing_contacts`
										WHERE TIME(`timestamp`) BETWEEN "'.$hour.':00:00" AND "'.$hour.':30:00"
										AND WEEKDAY(`timestamp`) '.$weekday_condition.'
								) AS success_'.$hour.'00');
				array_push($tmp,'(	SELECT
										CASE WHEN COUNT(`contact_id`) >= '.$threshold.'
											THEN
												SUM(`contact`)/COUNT(`contact_id`)
											ELSE
												NULL
										END
										FROM `canvassing_contacts`
										WHERE TIME(`timestamp`) BETWEEN "'.$hour.':30:00" AND "'.$hourPlusOne.':00:00"
										AND WEEKDAY(`timestamp`) '.$weekday_condition.'
								) AS success_'.$hour.'30');
				array_push($tmp,'(	SELECT
										CASE WHEN COUNT(`contact_id`) >= '.$threshold.'
											THEN
												(AVG(`mood`)-1)*25
											ELSE
												NULL
										END
										FROM `canvassing_contacts`
										WHERE TIME(`timestamp`) BETWEEN "'.$hour.':00:00" AND "'.$hour.':30:00"
										AND WEEKDAY(`timestamp`) '.$weekday_condition.'
								) AS mood_'.$hour.'00');
				array_push($tmp,'(	SELECT
										CASE WHEN COUNT(`contact_id`) >= '.$threshold.'
											THEN
												(AVG(`mood`)-1)*25
											ELSE
												NULL
										END
										FROM `canvassing_contacts`
										WHERE TIME(`timestamp`) BETWEEN "'.$hour.':30:00" AND "'.$hourPlusOne.':00:00"
										AND WEEKDAY(`timestamp`) '.$weekday_condition.'
								) AS mood_'.$hour.'30');
			}
			$sql = "SELECT ".join(",\n",$tmp);
			//echo $sql;
			$grape->db->query($sql);
			if($grape->db->num_rows > 0){
				$results = $grape->db->get_results();
				$results = $results[0];
				grape_cache_set($cache_key,$results);
				return $results;
			}
			else{
				return false;
			}
		}
		else return false;
	}
}
/**
 * @param array $data
 * @return string diagram as SVG
 */
function canvassing_timebased_statistics_2($data){
	$start = 6;
	$stop = 22;
	$out = '
<svg version="1.1" class="highcharts-root " xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 220" style=" width: 100% !important; height: auto;">
	<defs>
		<clipPath id="highcharts-m8tzqcl-5">
			<rect x="0" y="0" width="511" height="322"></rect>
		</clipPath>
		<filter id="drop-shadow-1" opacity="0.5">
			<feGaussianBlur in="SourceAlpha" stdDeviation="1"></feGaussianBlur>
			<feOffset dx="1" dy="1"></feOffset>
			<feComponentTransfer>
				<feFuncA type="linear" slope="0.3"></feFuncA>
			</feComponentTransfer>
			<feMerge>
				<feMergeNode></feMergeNode>
				<feMergeNode in="SourceGraphic"></feMergeNode>
			</feMerge>
		</filter>
		<style>
			.highcharts-tooltip-1{filter:url(#drop-shadow-1)}
			.highcharts-axis{stroke: #000000; stroke-width: 0.5px;}
			.highcharts-axis-labels{font-family: sans-serif; font-size: 12px;}
			.highcharts-grid{stroke: #ddd; stroke-width: 0.5px;}
			.highcharts-color-0{ fill: #343a40;}
		</style>
	</defs>
	<!--<rect class="highcharts-background" x="0.5" y="0.5" width="598" height="498" rx="0" ry="0"></rect>
	<rect class="highcharts-plot-background" x="69" y="71" width="511" height="322"></rect>-->
	<!--<g class="highcharts-grid highcharts-xaxis-grid ">
		<path class="highcharts-grid-line" d="M 87.5 71 L 87.5 393" opacity="1"></path>
		<path class="highcharts-grid-line" d="M 115.5 71 L 115.5 393" opacity="1"></path>
		<path class="highcharts-grid-line" d="M 142.5 71 L 142.5 393" opacity="1"></path>
		<path class="highcharts-grid-line" d="M 170.5 71 L 170.5 393" opacity="1"></path>
		<path class="highcharts-grid-line" d="M 198.5 71 L 198.5 393" opacity="1"></path>
		<path class="highcharts-grid-line" d="M 226.5 71 L 226.5 393" opacity="1"></path>
		<path class="highcharts-grid-line" d="M 254.5 71 L 254.5 393" opacity="1"></path>
		<path class="highcharts-grid-line" d="M 282.5 71 L 282.5 393" opacity="1"></path>
		<path class="highcharts-grid-line" d="M 310.5 71 L 310.5 393" opacity="1"></path>
		<path class="highcharts-grid-line" d="M 337.5 71 L 337.5 393" opacity="1"></path>
		<path class="highcharts-grid-line" d="M 365.5 71 L 365.5 393" opacity="1"></path>
		<path class="highcharts-grid-line" d="M 393.5 71 L 393.5 393" opacity="1"></path>
		<path class="highcharts-grid-line" d="M 421.5 71 L 421.5 393" opacity="1"></path>
		<path class="highcharts-grid-line" d="M 449.5 71 L 449.5 393" opacity="1"></path>
		<path class="highcharts-grid-line" d="M 477.5 71 L 477.5 393" opacity="1"></path>
		<path class="highcharts-grid-line" d="M 505.5 71 L 505.5 393" opacity="1"></path>
		<path class="highcharts-grid-line" d="M 532.5 71 L 532.5 393" opacity="1"></path>
		<path class="highcharts-grid-line" d="M 560.5 71 L 560.5 393" opacity="1"></path>
	</g>-->
	<g class="highcharts-grid highcharts-yaxis-grid ">
		<path class="highcharts-grid-line" d="M 37 20 L 580 20" opacity="1"></path>
		<path class="highcharts-grid-line" d="M 37 57.5 L 580 57.5" opacity="1"></path>
		<path class="highcharts-grid-line" d="M 37 95 L 580 95" opacity="1"></path>
		<path class="highcharts-grid-line" d="M 37 132.5 L 580 132.5" opacity="1"></path>
	</g>
	<!--<rect class="highcharts-plot-border" x="68.5" y="70.5" width="512" height="323"></rect>-->
	<g class="highcharts-axis highcharts-xaxis ">
		<path class="highcharts-axis-line" d="M 37 170 L 580 170"></path>';

	$x_start = 40;
	$x_stop = 560.5;
	$x_step = ($x_stop-$x_start)/($stop-$start);
	for($i=$start;$i<$stop;$i++){
		$out.= '<path class="highcharts-tick" d="M '.($x_step*($i-$start)+$x_start).' 170 L '.($x_step*($i-$start)+$x_start).' 173" opacity="1"></path>';
	}
	$out.= '
	</g>
	<!--<g class="highcharts-axis highcharts-yaxis ">
		<path class="highcharts-axis-line" d="M 40 20 L 40 170"></path>
	</g>-->
	<g class="highcharts-series-group">
		<g class="highcharts-series highcharts-series-0 highcharts-column-series highcharts-color-0 highcharts-tracker " transform="translate(0,100) scale(1 1)">';

	$width_factor = 150;
	$y_pos = 70;
	//echo '<rect x="'.($x_start).'" y="'.($y_pos-1*$width_factor).'" width="7" height="'.(1*$width_factor).'" class="highcharts-point highcharts-color-0 "></rect>';
	for($i=$start;$i<$stop;$i++){
		$hour = ($i<10)?"0".$i:$i;
		$hourPlusOne = ($i+1<10)?"0".($i+1):$i+1;
		$out.= '
		<rect x="'.($x_step*($i-$start)+$x_start).'" y="'.($y_pos-$data->{"success_".$hour."00"}*$width_factor).'" width="'.($x_step/2-1).'" height="'.($data->{"success_".$hour."00"}*$width_factor).'" class="highcharts-point highcharts-color-0 "></rect>
		<rect x="'.($x_step*($i-$start)+$x_start+($x_step/2)).'" y="'.($y_pos-$data->{"success_".$hour."30"}*$width_factor).'" width="'.($x_step/2-1).'" height="'.($data->{"success_".$hour."30"}*$width_factor).'" class="highcharts-point highcharts-color-0 "></rect>';
	}
	$out.= '
		</g>
		<!--<g class="highcharts-markers highcharts-series-0 highcharts-column-series highcharts-color-0 " transform="translate(69,71) scale(1 1)" clip-path="none">
			
		</g>-->
		<!--<g class="highcharts-series highcharts-series-1 highcharts-column-series highcharts-color-1 highcharts-tracker " transform="translate(69,71) scale(1 1)" clip-path="url(#highcharts-m8tzqcl-5)">
			<rect x="39.5" y="107.5" width="14" height="2" class="highcharts-point highcharts-color-1"></rect>
			<rect x="67.5" y="105.5" width="14" height="2" class="highcharts-point highcharts-color-1"></rect><rect x="95.5" y="98.5" width="14" height="7" class="highcharts-point highcharts-color-1"></rect><rect x="122.5" y="90.5" width="14" height="8" class="highcharts-point highcharts-color-1"></rect><rect x="150.5" y="84.5" width="14" height="6" class="highcharts-point highcharts-color-1"></rect><rect x="178.5" y="79.5" width="14" height="5" class="highcharts-point highcharts-color-1"></rect><rect x="206.5" y="72.5" width="14" height="7" class="highcharts-point highcharts-color-1"></rect><rect x="234.5" y="71.5" width="14" height="1" class="highcharts-point highcharts-color-1"></rect><rect x="262.5" y="258.5" width="14" height="2" class="highcharts-point highcharts-negative highcharts-color-1"></rect><rect x="290.5" y="63.5" width="14" height="10" class="highcharts-point highcharts-color-1"></rect><rect x="317.5" y="57.5" width="14" height="6" class="highcharts-point highcharts-color-1"></rect><rect x="345.5" y="55.5" width="14" height="2" class="highcharts-point highcharts-color-1"></rect><rect x="373.5" y="52.5" width="14" height="3" class="highcharts-point highcharts-color-1"></rect><rect x="401.5" y="51.5" width="14" height="1" class="highcharts-point highcharts-color-1"></rect><rect x="429.5" y="258.5" width="14" height="0" class="highcharts-point highcharts-negative highcharts-color-1"></rect><rect x="457.5" y="258.5" width="14" height="0" class="highcharts-point highcharts-negative highcharts-color-1"></rect>
		</g>-->
<g class="highcharts-markers highcharts-series-1 highcharts-column-series highcharts-color-1 " transform="translate(69,71) scale(1 1)" clip-path="none">
</g>
<!--<g class="highcharts-series highcharts-series-2 highcharts-column-series highcharts-color-7 highcharts-tracker " transform="translate(69,71) scale(1 1)" clip-path="url(#highcharts-m8tzqcl-5)"><rect x="485.5" y="48.5" width="14" height="3" class="highcharts-point highcharts-color-7"></rect>
</g>-->
<g class="highcharts-markers highcharts-series-2 highcharts-column-series highcharts-color-7 " transform="translate(69,71) scale(1 1)" clip-path="none">
</g>
	</g>
<g class="highcharts-legend" transform="translate(58,442)">
	<!--<rect class="highcharts-legend-box" rx="0" ry="0" x="0" y="0" width="474" height="28" visibility="visible"></rect>-->
	<g><g><g class="highcharts-legend-item highcharts-column-series highcharts-color-0 highcharts-series-0" transform="translate(8,3)"><text x="21" text-anchor="start" y="19"><tspan>CO2 Emissions</tspan></text><rect x="0" y="4" width="16" height="16" rx="8" ry="8" class="highcharts-point"></rect>
</g>
<g class="highcharts-legend-item highcharts-column-series highcharts-color-1 highcharts-series-1" transform="translate(136.0500030517578,3)"><text x="21" y="19" text-anchor="start"><tspan>Yearly Increase 2000-2016</tspan></text><rect x="0" y="4" width="16" height="16" rx="8" ry="8" class="highcharts-point"></rect>
</g>
<g class="highcharts-legend-item highcharts-column-series highcharts-color-7 highcharts-series-2" transform="translate(332.18333435058594,3)"><text x="21" y="19" text-anchor="start"><tspan>Increase 2016-2017</tspan></text><rect x="0" y="4" width="16" height="16" rx="8" ry="8" class="highcharts-point"></rect>
</g>
</g>
</g>
</g>
<g class="highcharts-axis-labels highcharts-xaxis-labels ">
	<!--<text x="90.02496153525475" text-anchor="end" transform="translate(0,0) rotate(-45 90.02496153525475 408)" y="408" opacity="1">
		<tspan>8-8:30</tspan>
	</text>
	<text x="117.88755695510186" text-anchor="end" transform="translate(0,0) rotate(-45 117.88755695510186 408)" y="408" opacity="1">
		<tspan>17-20:30</tspan>
	</text>
	<text x="145.75015237494895" text-anchor="end" transform="translate(0,0) rotate(-45 145.75015237494895 408)" y="408" opacity="1">
		<tspan>2002</tspan>
	</text>-->';

	$x_text_offset = 2;
	for($i=$start;$i<$stop;$i++){
		$out.= '<text x="'.($x_step*($i-$start)+$x_start+$x_text_offset).'" text-anchor="end" transform="translate(0,0) rotate(-45 '.($x_step*($i-$start)+$x_start+$x_text_offset).' 178)" y="178" opacity="1">
				<tspan>'.($i).' Uhr</tspan>
			  </text>';
	}
$out.= '
</g>
<g class="highcharts-axis-labels highcharts-yaxis-labels ">
	<text x="32" text-anchor="end" transform="translate(0,0)" y="173" opacity="1">
		<tspan>0%</tspan>
	</text>
	<text x="32" text-anchor="end" transform="translate(0,0)" y="135.5" opacity="1">
		<tspan>25%</tspan>
	</text>
	<text x="32" text-anchor="end" transform="translate(0,0)" y="98" opacity="1">
		<tspan>50%</tspan>
	</text>
	<text x="32" text-anchor="end" transform="translate(0,0)" y="60.5" opacity="1">
		<tspan>75%</tspan>
	</text>
	<text x="32" text-anchor="end" transform="translate(0,0)" y="23" opacity="1">
		<tspan>100%</tspan>
	</text>
</g>
<!--<text x="590" class="highcharts-credits" text-anchor="end" y="495">
	<tspan>OECD/IEA</tspan>
</text>-->
<!--<g class="highcharts-label highcharts-tooltip highcharts-tooltip-1 highcharts-color-0" transform="translate(492,-9999)" opacity="0" visibility="visible"><path class="highcharts-label-box highcharts-tooltip-box" d="M 3.5 0.5 L 81.5 0.5 C 84.5 0.5 84.5 0.5 84.5 3.5 L 84.5 43.5 C 84.5 46.5 84.5 46.5 81.5 46.5 L 47.5 46.5 41.5 52.5 35.5 46.5 3.5 46.5 C 0.5 46.5 0.5 46.5 0.5 43.5 L 0.5 3.5 C 0.5 0.5 0.5 0.5 3.5 0.5"></path><text x="8" y="20"><tspan class="highcharts-header">2016</tspan><tspan x="8" dy="15">32.1 Gt CO2</tspan></text>
</g>--></svg>';
	return $out;
}
/**
 * @param array $data
 * @return string HTML
 * @deprecated
 */
function canvassing_timebased_statistics($data){
	$out = "";
	$width_factor = 80;
	$first = 24;
	$last = -1;
	for($i=0;$i<24;$i++){
		$hour = ($i<10)?"0".$i:$i;
		if(isset($data->{"success_".$hour."00"})||isset($data->{"success_".$hour."30"})){
			$first = $i;
			break;
		}
	}
	for($i=23;$i>=0;$i--){
		$hour = ($i<10)?"0".$i:$i;
		if(isset($data->{"success_".$hour."00"})||isset($data->{"success_".$hour."30"})){
			$last = $i;
			break;
		}
	}
	if($first < 24){
		$out.= '<table cellspacing="0" width="100%" class="table-diagram">';
		$out.= '<tr>';
		for($i=$first;$i<=$last;$i++){
			$hour = ($i<10)?"0".$i:$i;
			$hourPlusOne = ($i+1<10)?"0".($i+1):$i+1;
			$out.= '<td><div class="vertical_legend">';
			$out.= (($data->{"success_".$hour."00"})?round($data->{"success_".$hour."00"}*100,0)."%":"");
			$out.= '</div></td>';
			$out.= '<td><div class="vertical_legend">';
			$out.= (($data->{"success_".$hour."30"})?round($data->{"success_".$hour."30"}*100,0)."%":"");
			$out.= '</div></td>';
		}
		$out.= '</tr>';
		$out.= '<tr>';
		for($i=$first;$i<=$last;$i++){
			$hour = ($i<10)?"0".$i:$i;
			$hourPlusOne = ($i+1<10)?"0".($i+1):$i+1;
			$out.= '<td width="2%"><div class="vertical_legend">';
			$out.= $hour.'-'.$hour.':30';
			$out.= '</div></td>';
			$out.= '<td width="2%"><div class="vertical_legend">';
			$out.= $hour.':30-'.$hourPlusOne;
			$out.= '</div></td>';
		}
		$out.= '</tr>';
		$out.= '</table>';
		$out.= '<table cellspacing="0" width="100%" class="nobackground">';
		for($i=$first;$i<=$last;$i++){
			$hour = ($i<10)?"0".$i:$i;
			$hourPlusOne = ($i+1<10)?"0".($i+1):$i+1;
			$out.= '<tr>
					<td width="100">'.$hour.'-'.$hour.':30</td>
					<td>
						<div class="statistic_bar" style="width:'.($data->{"success_".$hour."00"}*$width_factor).'%">
							<!--<div class="statistic_bar" style="width:'.($data->{"mood_".$hour."00"}).'%;background-color:#ffee00;"></div>-->
						</div>'.(($data->{"success_".$hour."00"})?round($data->{"success_".$hour."00"}*100,0)."%":"").'
						<!--<br/><div class="statistic_bar" style="width:'.($data->{"mood_".$hour."00"}*$width_factor/100).'%;background-color:#ffee00;"></div>-->
					</td>
				  </tr>';
			$out.= '<tr>
					<td width="100">'.$hour.':30-'.$hourPlusOne.'</td>
					<td>
						<div class="statistic_bar" style="width:'.($data->{"success_".$hour."30"}*$width_factor).'%">
							<!--<div class="statistic_bar" style="width:'.($data->{"mood_".$hour."30"}).'%;background-color:#ffee00;"></div>-->
						</div>'.(($data->{"success_".$hour."30"})?round($data->{"success_".$hour."30"}*100,0)."%":"").'
						<!--<br/><div class="statistic_bar" style="width:'.($data->{"mood_".$hour."30"}*$width_factor/100).'%;background-color:#ffee00;"></div>-->
					</td>
				  </tr>';
		}
		$out.= '</table>';
	}
	else $out.= "Keine oder nicht genügend Daten";
	return $out;
}
/**
 *
 */
function canvassing_map_region(){
	global $grape;
	$grape->output->result = "";
	$grape->output->content_type = "embedded_map";
	$grape->output->add_javascript(URL."grape-modules/canvassing/mapping.js","construct_map('".URL."ajax.php?module=canvassing&job=map_region_ajax&campaign_id=".$_REQUEST["campaign_id"]."','map_region','#grape_content');");
	$grape->output->content->html.= "<div id='map3' style='width: 100%; position: absolute; left: 0px; top: 54px; bottom:0px;'></div>";
	canvassing_build_menu();
}
/**
 *
 */
function canvassing_map_region_ajax(){
	global $grape;
	$campaign_id = intval($_REQUEST["campaign_id"]);
	$ou_id = $grape->user->ou_id;
	$datasets = canvassing_get_data_of_ou($ou_id,$campaign_id,true);
	//print_r($datasets);
	//exit;
	echo'
{
	"type":"FeatureCollection",
	"crs":{
		"type":"name",
		"properties":{
			"name":"urn:ogc:def:crs:OGC:1.3:CRS84"
		}
	},
	"features":[';
	$data = array();
	foreach($datasets as $dataset){
		array_push($data,'{
								"type":"Feature",
								"properties":{
											"campaign_id": '.$campaign_id.',
											"code": "'.str_replace('"',"'",$dataset->code).'",
											"potential": '.floatval($dataset->potential).',
											"x_ward_id": '.$dataset->x_ward_id.',
											"electoral_district_name": "'.str_replace('"',"'",$dataset->electoral_district_name).'",
											"electoral_ward_name": "'.str_replace('"',"'",$dataset->electoral_ward_name).'",
											"visible": '.((isset($dataset->visible)&&$dataset->visible==0)?0:1).',
											"done_rate": '.(($dataset->done_rate===false||$dataset->done_rate===''||!isset($dataset->done_rate))?0:$dataset->done_rate).',
											"attribution": "'.$dataset->attribution.'"
								},
								"geometry":'.$dataset->geodata.'
								}');
	}
	$data = join(",\n",$data);
	echo $data;
	echo'
	]
}';
	exit;
}
/**
 *
 */
function canvassing_get_data_of_ou($ou_id,$campaign_id,$geodata=false){
	global $grape;
	$ou_id = intval($ou_id);
	$campaign_id = intval($campaign_id);
	//$grape->output->content->html.= "$ou_id $campaign_id";
	$sql = "SELECT 
				`canvassing_electoral_ward_data`.`potential`,
				`canvassing_electoral_ward_data`.`visible`,
				`grape_electoral_wards`.`name` AS electoral_ward_name,
				`grape_electoral_wards`.`code`,
				`grape_x_wards`.`x_ward_id`,
				`grape_geodata_attributions`.`text` AS attribution,
				".($geodata?"`grape_electoral_wards`.`geodata`,":"")."
				`grape_electoral_districts`.`name` AS electoral_district_name,
						(SELECT COUNT(DISTINCT `grape_x_streets`.`street_id`)
						FROM `canvassing_street_data`
						LEFT JOIN `grape_x_streets` ON `grape_x_streets`.`x_street_id` = `canvassing_street_data`.`x_street_id`
						WHERE `done` IS NOT NULL 
						AND `grape_x_streets`.`x_ward_id` = `grape_x_wards`.`x_ward_id`)/
						(SELECT COUNT(`grape_x_streets`.`street_id`)
						FROM `grape_x_streets`
						WHERE `grape_x_streets`.`x_ward_id` = `grape_x_wards`.`x_ward_id`)
						*100 AS `done_rate`
			FROM `grape_x_wards`
			LEFT JOIN `grape_x_elections_electoral_districts` ON `grape_x_elections_electoral_districts`.`x_election_district_id` = `grape_x_wards`.`x_election_district_id`
			LEFT JOIN `grape_electoral_districts` ON `grape_electoral_districts`.`electoral_district_id` = `grape_x_elections_electoral_districts`.`electoral_district_id`
			LEFT JOIN `grape_campaigns` ON `grape_campaigns`.`election_id` = `grape_x_elections_electoral_districts`.`election_id`
			LEFT JOIN `canvassing_electoral_ward_data` ON `canvassing_electoral_ward_data`.`x_ward_id` = `grape_x_wards`.`x_ward_id`
			LEFT JOIN `grape_electoral_wards` ON `grape_electoral_wards`.`electoral_ward_id` = `grape_x_wards`.`electoral_ward_id`
			LEFT JOIN `grape_geodata_attributions` ON `grape_geodata_attributions`.`attribution_id` = `grape_electoral_wards`.`attribution_id`
			LEFT JOIN `grape_x_eed_ou` ON `grape_x_eed_ou`.`x_election_district_id` = `grape_x_elections_electoral_districts`.`x_election_district_id`
			WHERE `grape_x_eed_ou`.`ou_id` = $ou_id
			AND `grape_campaigns`.`campaign_id` = $campaign_id
			ORDER BY `potential` DESC";
	$grape->db->query($sql);
	$results = $grape->db->get_results();
	return $results;
}
/**
 *
 */
function canvassing_regional_ranking(){
	global $grape;
	$campaign_id = intval($_REQUEST["campaign_id"]);
	$ou_id = $grape->user->ou_id;
	$html = "";
	//if(count(get_admins_of_region(get_region_by_wahlkreis($user->wahlkreis_id)))==0){
	$datasets = canvassing_get_data_of_ou($ou_id,$campaign_id,false);
	//$html = $grape->output->dump_var($datasets);
	$html.= "<h2>Rangliste der Wahlbezirke</h2>";
		if(count($datasets) == 0) $html.= "<p>Es sind keine Wahlbezirke vorhanden.</p>";
		else{
			$html.= "<p>Hier lohnt sich Dein Einsatz besonders:</p>";
			$html.= "<table cellspacing=\"0\">";
			$html.= "<tr><th>Name</th><th>Potential</th><th>Status</th><!--<th>Lezte Änderung</th><th>Letzter Kontakt</th>--></tr>";
			//print_r($voting_districts);
			foreach($datasets as $ward){
				$html.= "<tr>
						<td><a href=\"#\" onclick=\"load_content('module=canvassing&campaign_id=".$campaign_id."&x_ward_id=".$ward->x_ward_id."&job=selectStreet');\">".$ward->code." ".$ward->electoral_ward_name."</a></td>
						<td>".$ward->potential."</td>
						<td>".round($ward->done_rate,0)."%</td>
					</tr>";
			}
			$html.= "</table>";
		}
	//}
	$grape->output->content->html.= $grape->output->wrap_div($html);
	canvassing_build_menu();

}
/**
 *
 */
function canvassing_abandoned_streets(){
	global $grape;
	$campaign_id = intval($_REQUEST["campaign_id"]);
	$ou_id = $grape->user->ou_id;
	$html = "";
				$street_data = canvassing_get_abandoned_streets($ou_id,$campaign_id);
				//$html.= $grape->output->dump_var($street_data);
				$html.= "<h2>Vergessene/verlassene Straßenzüge</h2>";
				if(count($street_data) == 0) $html.= "<p>Es sind keine verlassenen/vergessenen Straßenzüge vorhanden.</p>";
				else {
					$html.= "<p>In einigen Straßenzügen tut sich schon seit mindestens einer Woche nichts mehr.</p>";
					$html.= "<table cellspacing=\"0\">";
					$html.= "<tr><th>Name</th><th>Grüne*r</th><th>Kommentar</th><th>Lezte Änderung</th><!--<th>Letzter Kontakt</th>--></tr>";
					foreach($street_data as $street){
						$html.= "<tr>
								<td>".$street->street_name."<!--</a>--><br/>
								<!--zum <a href=\"#\" onclick=\"module=canvassing&district=".$street->stadtbezirk_id."&votingDistrict=".$street->wahlbezirk_id."&job=selectStreet\">-->Wahlbezirk ".$street->code." ".$street->ward_name."<!--</a>--> in ".$street->district_name."</td>
								<td>".$street->user_name."<!-- <a href=\"?job=mail_street&return=".urlencode(json_encode($_REQUEST))."&street_data_id=".$street->street_data_id."\">anmailen</a>--></td>
								<td>".$street->comment."</td>
								<td>".$street->last_changes."</td>
							</tr>";
					}
					$html.= "</table>";
				}
	$grape->output->content->html.= $grape->output->wrap_div($html);
	canvassing_build_menu();
}
/**
 * search for strings in street names
 * @param string $search_string
 * @param int $ou_id
 * @param int $campaign_id
 * @param int $limit
 * @return object Contains string search_string_escaped and array results
 */
function canvassing_search_street($search_string,$ou_id,$campaign_id,$limit=50){
	global $grape;
	$ou_id = intval($ou_id);
	$campaign_id = intval($campaign_id);
	$limit = intval($limit);
	$result = new stdClass();
	$result->search_string = $search_string;
	$result->search_string_escaped = grape_escape($search_string);
	$sql = "SELECT `grape_streets`.*,
			`grape_electoral_wards`.`code`,
			`grape_electoral_wards`.`name` AS ward_name,
			`grape_electoral_districts`.`name` AS district_name,
			`canvassing_street_data`.`street_data_id`,
			`grape_x_streets`.`x_street_id`,
			`grape_x_wards`.`x_ward_id`,
			CONCAT(`grape_users`.`name`,' ',SUBSTRING(`grape_users`.`last_name`,1,1),'.') AS user,
				`grape_users`.`user_id`,
				(CASE 
					WHEN `done` IS NOT NULL 
					THEN 'Straßenzug erledigt'
					ELSE 
						CASE 
							WHEN (`comment` IS NOT NULL AND `comment` <> '')
							THEN `comment`
							ELSE 'Straßenzug reserviert'
						END
					END) AS my_comment
			FROM `grape_streets`
			LEFT JOIN `grape_x_streets` ON `grape_x_streets`.`street_id` = `grape_streets`.`street_id`
			LEFT JOIN `grape_x_wards` ON `grape_x_wards`.`x_ward_id` = `grape_x_streets`.`x_ward_id`
			LEFT JOIN `canvassing_street_data` ON `canvassing_street_data`.`x_street_id` = `grape_x_streets`.`x_street_id`
			LEFT JOIN `grape_users` ON `grape_users`.`user_id` = `canvassing_street_data`.`locked_by`
			LEFT JOIN `grape_electoral_wards` ON `grape_electoral_wards`.`electoral_ward_id` = `grape_x_wards`.`electoral_ward_id`
			LEFT JOIN `grape_x_elections_electoral_districts` ON `grape_x_elections_electoral_districts`.`x_election_district_id` = `grape_x_wards`.`x_election_district_id`
			LEFT JOIN `grape_electoral_districts` ON `grape_electoral_districts`.`electoral_district_id` = `grape_x_elections_electoral_districts`.`electoral_district_id`
			LEFT JOIN `grape_campaigns` ON `grape_campaigns`.`election_id` = `grape_x_elections_electoral_districts`.`election_id`
			LEFT JOIN `grape_x_eed_ou` ON `grape_x_eed_ou`.`x_election_district_id` = `grape_x_elections_electoral_districts`.`x_election_district_id`
			WHERE `grape_x_eed_ou`.`ou_id` = $ou_id
			AND `grape_campaigns`.`campaign_id` = $campaign_id
			AND `grape_streets`.`name` LIKE '%".$result->search_string_escaped."%'
			ORDER BY `grape_streets`.`name`
			LIMIT 50";
	$grape->db->query($sql);
	$result->results = $grape->db->get_results();
	return $result;
}
/**
 * UI for search for strings in street names
 */
function canvassing_search_street_ui(){
	global $grape;
	$campaign_id = intval($_REQUEST["campaign_id"]);
	$ou_id = $grape->user->ou_id;
	//$grape->output->content->html.= "<pre>Welcome at module ".$_REQUEST["module"]."</pre>";
	canvassing_build_menu();
	if(isset($_REQUEST["search_string"])){
		$result = canvassing_search_street($_REQUEST["search_string"],$ou_id,$campaign_id);
		$search_string = $result->search_string_escaped;
		$results = $result->results;
		if(count($results)>0){
			// found something
			$tmp_html.=  "<h2>Suchergebnis</h2>
							<p>Ich habe nach \"$search_string\" in Deiner Kommune gesucht. Hier meine Ergebnisse:</p>
							<table>
							  <thead>
								<tr>
								  <th>Straße</th>
								  <th>Status</th>
								</tr>
							  </thead>";
			foreach($results as $entry){
				$tmp_html.= "<tr><td>".$entry->name." (".$entry->ward_name.")<br/><!--".print_r($entry,true)."--></td><td>";
				if(strlen($entry->user)>0){
					// already taken
					if($grape->user->user_id != $entry->user_id) {
						// taken by someone else
						$tmp_html.= $entry->my_comment." ".$entry->user;
						$tmp_html.= " <a href=\"#\" onclick=\"load_content_return('module=canvassing&campaign_id=".$campaign_id."&job=mail_street&street_data_id=".$entry->street_data_id."');\">anmailen</a>";
					}
					else{
						// own
						$tmp_html.= "ist schon Dein Straßenabschnitt...";
						$tmp_html.= " <a href='#' onclick='load_content(\"module=canvassing&job=ringThatBell&campaign_id=$campaign_id&x_street_id=".$entry->x_street_id."\");'>Weiter bearbeiten</a>";
					}
				}
				else{
					// free
					$tmp_html.= " <a href='#' onclick='load_content(\"module=canvassing&job=takeStreet&campaign_id=$campaign_id&x_street_id=".$entry->x_street_id."&x_ward_id=".$entry->x_ward_id."\");'>Straßenabschnitt bearbeiten</a>";
				}
				$tmp_html.= "</td></tr>";
			}
			$tmp_html.=  "</table>";
			$grape->output->content->html.= $grape->output->wrap_div($tmp_html);
		}
		else{
			// nothing found
			$tmp_html.=  "<h2>Suchergebnis</h2>
						  <p>Die Suche nach \"$search_string\" in Deiner Kommune führte leider zu keinen Ergebnissen.</p>";
			$grape->output->content->html.= $grape->output->wrap_div($tmp_html);
		}
	}
	else{
		$html = '
		<h2>Straße suchen</h2>
		<p>Hier kannst Du nach einem Teil eines Straßennamens suchen. Dabei werden nur Straßen in Deiner Kommune berücksichtig.</p>
		<form method="post" action="index.php">
			<input type="hidden" name="campaign_id" value="'.$campaign_id.'"/>
			<input type="hidden" name="module" value="canvassing"/>
			<input type="hidden" name="job" value="search_street"/>
			<div class="form-group">
				<label for="search_string">Suchtext</label><br/>
				<input class="form-control" type="text" name="search_string" value=""/>
			</div>
			<a class="btn btn-secondary" href="#" role="button" onclick="load_content(\''.urldecode($_REQUEST["return"]).'\');">Abbrechen</a>
			<button type="submit" class="btn btn-primary">Straße suchen</button>
		</form>';
		$grape->output->content->html.= $grape->output->wrap_div($html);
	}
	
}
/**
 * @param int $quantity
 */
function canvassing_get_best_wards($quantity=2){
	global $grape;
	$quantity = intval($quantity);
	$ou_id = $grape->user->ou_id;
	$campaign_id = intval($_REQUEST["campaign_id"]);
	$sql = "SELECT 
			`grape_electoral_wards`.*,
			`canvassing_electoral_ward_data`.*
			FROM `canvassing_electoral_ward_data`
			LEFT JOIN `grape_x_wards` ON `grape_x_wards`.`x_ward_id` = `canvassing_electoral_ward_data`.`x_ward_id`
			LEFT JOIN `grape_electoral_wards` ON `grape_electoral_wards`.`electoral_ward_id` = `grape_x_wards`.`electoral_ward_id`
			LEFT JOIN `grape_x_elections_electoral_districts` ON `grape_x_elections_electoral_districts`.`x_election_district_id` = `grape_x_wards`.`x_election_district_id`
			LEFT JOIN `grape_campaigns` ON `grape_campaigns`.`election_id` = `grape_x_elections_electoral_districts`.`election_id`
			LEFT JOIN `grape_x_eed_ou` ON `grape_x_eed_ou`.`x_election_district_id` = `grape_x_elections_electoral_districts`.`x_election_district_id`
			WHERE `canvassing_electoral_ward_data`.`visible` = 1
			AND `grape_x_eed_ou`.`ou_id` = $ou_id
			AND `grape_campaigns`.`campaign_id` = $campaign_id
			AND (SELECT COUNT(DISTINCT `canvassing_street_data`.`x_street_id`) 
			 FROM `canvassing_street_data` 
			 LEFT JOIN `grape_x_streets` ON `grape_x_streets`.`x_street_id` = `canvassing_street_data`.`x_street_id`
			 WHERE `grape_x_streets`.`x_ward_id` = `grape_x_wards`.`x_ward_id`)
			/
			(SELECT COUNT(`grape_x_streets`.`street_id`) 
			 FROM `grape_x_streets` 
			 WHERE `grape_x_streets`.`x_ward_id` = `grape_x_wards`.`x_ward_id`) < 0.9
			ORDER BY `canvassing_electoral_ward_data`.`potential` DESC
			LIMIT $quantity";
	$grape->db->query($sql);
	$results = $grape->db->get_results();
	return $results;
}
/**
 *
 */
function canvassing_dump_contact_data(){
	global $grape;
	if(grape_user_has_capability(grape_get_capability_of_current_user_for_ou($grape->user->ou_id),"admin")){
		$campaign_id = intval($_REQUEST["campaign_id"]);
		$ou_id = $grape->user->ou_id;
		$sql = "SELECT `canvassing_contacts`.*,
				users1.`gender` AS user1_gender,
				users1.`user_id` AS user1_id,
				users1.`year_of_brith` AS user1_year_of_brith,
				(
					CASE  
						WHEN users2.`user_id` = -3 THEN 'divers'
						WHEN users2.`user_id` = -2 THEN 'female'
						WHEN users2.`user_id` = -1 THEN 'male' 
						WHEN users2.`user_id` = 0 THEN '' 
						ELSE users2.`gender`
					END
				) AS user2_gender,
				users2.`user_id` AS user2_id,
				users2.`year_of_brith` AS user2_year_of_brith,
				`grape_electoral_wards`.`code`,
				`grape_electoral_wards`.`name` AS ward_name,
				`grape_electoral_districts`.`name` AS district_name
				FROM `canvassing_contacts`
				LEFT JOIN `grape_users` AS users1 ON users1.`user_id` = `canvassing_contacts`.`user_id`
				LEFT JOIN `grape_users` AS users2 ON users2.`user_id` = `canvassing_contacts`.`partner_user_id`
				LEFT JOIN `grape_x_wards` ON `grape_x_wards`.`x_ward_id` = `canvassing_contacts`.`x_ward_id`
				LEFT JOIN `grape_electoral_wards` ON `grape_electoral_wards`.`electoral_ward_id` = `grape_x_wards`.`electoral_ward_id`
				LEFT JOIN `grape_x_elections_electoral_districts` ON `grape_x_elections_electoral_districts`.`x_election_district_id` = `grape_x_wards`.`x_election_district_id`
				LEFT JOIN `grape_electoral_districts` ON `grape_electoral_districts`.`electoral_district_id` = `grape_x_elections_electoral_districts`.`electoral_district_id`
				LEFT JOIN `grape_campaigns` ON `grape_campaigns`.`election_id` = `grape_x_elections_electoral_districts`.`election_id`
				LEFT JOIN `grape_x_eed_ou` ON `grape_x_eed_ou`.`x_election_district_id` = `grape_x_elections_electoral_districts`.`x_election_district_id`
				WHERE `grape_x_eed_ou`.`ou_id` = $ou_id
				AND `grape_campaigns`.`campaign_id` = $campaign_id
				ORDER BY `canvassing_contacts`.`timestamp`";
		$grape->db->query($sql);
		//echo $sql;
		//exit;
		$results = $grape->db->get_results();
		grape_csv_dump($results,"tzt_contacts");
	}
	exit;
}
/**
 *
 */
function canvassing_dump_street_data(){
	global $grape;
	if(grape_user_has_capability(grape_get_capability_of_current_user_for_ou($grape->user->ou_id),"admin")){
		$campaign_id = intval($_REQUEST["campaign_id"]);
		$ou_id = $grape->user->ou_id;
		$sql = "";
		$grape->db->query($sql);
		$results = $grape->db->get_results();
		grape_csv_dump($results,"tzt_street_data");
	}
	exit;
}
/**
 * Deletion of contact and street data
 * Includes confirmation dialogue
 */
function canvassing_delete_data(){
	global $grape;
	if(grape_user_has_capability(grape_get_capability_of_current_user_for_ou($grape->user->ou_id),"admin")){
		$campaign_id = intval($_REQUEST["campaign_id"]);
		if(isset($_REQUEST["confirmed"])){
			if($_REQUEST["confirmed"]=="yes"){
				$sql = "DELETE
						FROM `canvassing_contacts`
						WHERE `x_ward_id` IN
							(
								SELECT `x_ward_id`
								FROM `grape_x_wards`
								LEFT JOIN `grape_x_elections_electoral_districts` ON `grape_x_elections_electoral_districts`.`x_election_district_id` = `grape_x_wards`.`x_election_district_id`
								LEFT JOIN `grape_campaigns` ON `grape_campaigns`.`election_id` = `grape_x_elections_electoral_districts`.`election_id`
								LEFT JOIN `grape_x_eed_ou` ON `grape_x_eed_ou`.`x_election_district_id` = `grape_x_elections_electoral_districts`.`x_election_district_id`
								WHERE `grape_campaigns`.`campaign_id` = $campaign_id
								AND `grape_x_eed_ou`.`ou_id` = ".$grape->user->ou_id."
							)
						;";
				$grape->db->query($sql);
				$sql = "DELETE
						FROM `canvassing_street_data`
						WHERE `x_street_id` IN
							(
								SELECT `x_street_id`
								FROM `grape_x_streets`
								LEFT JOIN `grape_x_wards` ON `grape_x_wards`.`x_ward_id` = `grape_x_streets`.`x_ward_id`
								LEFT JOIN `grape_x_elections_electoral_districts` ON `grape_x_elections_electoral_districts`.`x_election_district_id` = `grape_x_wards`.`x_election_district_id`
								LEFT JOIN `grape_campaigns` ON `grape_campaigns`.`election_id` = `grape_x_elections_electoral_districts`.`election_id`
								LEFT JOIN `grape_x_eed_ou` ON `grape_x_eed_ou`.`x_election_district_id` = `grape_x_elections_electoral_districts`.`x_election_district_id`
								WHERE `grape_campaigns`.`campaign_id` = $campaign_id
								AND `grape_x_eed_ou`.`ou_id` = ".$grape->user->ou_id."
							)
						;";
				$grape->db->query($sql);
				$grape->output->message = '<strong>Ich habe alle Daten gelöscht</strong> (und hoffe, Du bereust es nicht später...). ';
			}
			else{
				$grape->output->message = 'Ich habe die Löschung der Daten auf Deinen Wunsch abgebrochen.';
			}
			$grape->output->result = "success";
			canvassing_start();
		}
		else{
			$html = "<p>Willst Du wirklich alle Straßen- und Kontaktdaten in ".$grape->user->ou_name." löschen?</p>";
			$html.= "<a class=\"btn btn-primary\" href=\"#\" role=\"button\" onclick=\"load_content('module=canvassing&job=delete_data&confirmed=no&campaign_id=$campaign_id');\">Nein, ich will die Daten behalten</a> ";
			$html.= "<a class=\"btn btn-primary\" href=\"#\" role=\"button\" onclick=\"load_content('module=canvassing&job=delete_data&confirmed=yes&campaign_id=$campaign_id');\">Ja, ich will die Daten löschen</a>";
			$grape->output->content->html.= $grape->output->wrap_div($html);
		}
	}
	else{
		$grape->output->content->html.= $grape->output->wrap_div("Sorry, Deine Rechte reichen nicht aus.");
	}
}
/**
 *
 */
function canvassing_start(){
	global $grape;
	$campaign_id = intval($_REQUEST["campaign_id"]);
	$campaign = grape_campaign_by_campaign_id($campaign_id);
	//$grape->output->content->html.= "<pre>Welcome at module ".$_REQUEST["module"]."</pre>";
	canvassing_build_menu();
	//$grape->output->content->html.= $grape->output->wrap_div("<pre>".print_r($grape->user,true)."</pre>");
	// Was steht für den User an?
	$openJobs = canvassing_openJobs($grape->user->user_id,$campaign->election_id);
	
	// Was hat er schon erledigt?
	$closedJobs = canvassing_closedJobs($grape->user->user_id,$campaign->election_id);
	
	// User statistics
	$user_statistics = canvassing_user_statistics();
	
	// Greeting
	if($user_statistics->contacts > 5) {
		$tmp_html =  "<h2>Hallo ".$grape->user->name.",</h2>
						<p>Du hast bisher ".grape_number($user_statistics->trials)." Mal geklingelt und dabei ".grape_number($user_statistics->contacts)." Mal jemanden erreicht!".(($grape->output->users_online>1)?"<br/>Momentan sind ".$grape->output->users_online." online.":"")."</p>
						<p><a href='#' onclick='load_content(\"module=canvassing&job=statistics&campaign_id=$campaign_id\");'>Mehr Statistik</a></p>";
		$grape->output->content->html.= $grape->output->wrap_div($tmp_html);
	}
	//else $tmp_html.=  "<p>Hallo ".$grape->user->name."!</p>";
	
	//$grape->output->content->html.= "<pre>".print_r($openJobs,true)."</pre>";
	$tmp_html = "";
	if($openJobs){
		$tmp_html.=  "<h2>Hier geht's weiter</h2>";
		foreach($openJobs as $openJob){
			$tmp_html.=  '
				<form>
					<input type="hidden" name="module" value="canvassing"/>
					<input type="hidden" name="campaign_id" value="'.$_REQUEST["campaign_id"].'"/>
					<input type="hidden" name="x_street_id" value="'.$openJob->x_street_id.'"/>
					<input type="hidden" name="job" value="ringThatBell"/>
					<button class="btn btn-primary btn-block">'.$openJob->name.' ('.$openJob->district_name.')</button>
				</form>';
		}
		$grape->output->content->html.= $grape->output->wrap_div($tmp_html);
	}
	
	$tmp_html = "";
		$tmp_html.=  '
		<form>
			<h2>Neue Aufgabe</h2>
			<p>Wähle einen Wahlbezirk in/bei der '.$campaign->name.':</p>
			<input type="hidden" name="module" value="canvassing"/>
			<input type="hidden" name="campaign_id" value="'.$campaign_id.'"/>
			<input type="hidden" name="job" value="selectStreet"/>
			<select name="x_ward_id" id="x_ward_id" data-live-search="true">';
		$tmp_html.= canvassing_html_ward_select_by_ou($grape->user->ou_id,$campaign->election_id);
		$tmp_html.=  '
			
			';
				$tmp_html.=  '
			</select>
			<button class="btn btn-primary btn-block" name="job" value="selectStreet">Weiter</button>
		</form>
		
		';
	$grape->output->content->html.= $grape->output->wrap_div($tmp_html);

	
	$tmp_html = "";
	if($closedJobs){
		$tmp_html.=  "<h2>Dein Werk bisher</h2>
						<table>
									  <thead>
										<tr>
										  <th>Datum</th>
										  <th>Erledigter Straßenzug</th>
										</tr>
									  </thead>";
		foreach($closedJobs as $closedJob){
			$tmp_html.=  "<tr><td>".date("d.m.Y H:i",strtotime($closedJob->done))."</td><td>".$closedJob->name.' ('.$closedJob->district_name.')</td></tr>';
		}
		$tmp_html.=  "</table>";
		$grape->output->content->html.= $grape->output->wrap_div($tmp_html);
	}

				//$logbook = get_logbook(get_region_by_wahlkreis($user->wahlkreis_id),10);
				$logbook = canvassing_get_logbook_by_ou($grape->user->ou_id,$campaign->election_id,10);
				$tmp_html = "";
				if($logbook){
					$tmp_html.=  "<h2>Im Kreisverband</h2>
									<table>
									  <thead>
										<tr>
										  <th>Datum</th>
										  <th>Ereignis</th>
										</tr>
									  </thead>";
					foreach($logbook as $entry){
						$tmp_html.=  "<tr><td>".date("d.m.Y H:i",strtotime($entry->last_changes))."</td><td>".trim($entry->street).": ".$entry->my_comment." (".$entry->user;
						if($grape->user->user_id != $entry->user_id) {
							$tmp_html.= " <a href=\"#\" onclick=\"load_content_return('module=canvassing&campaign_id=".$campaign_id."&job=mail_street&street_data_id=".$entry->street_data_id."');\">anmailen</a>";
						}
						$tmp_html.= ")</td></tr>";
					}
					$tmp_html.=  "</table>";
					$grape->output->content->html.= $grape->output->wrap_div($tmp_html);
				}
				/*
				$records = get_records($user->election_id,10);
				if($records){
					$grape->output->content->html.=  "<p>Rekorde bei der ".$user->election_name.":</p><table cellspacing=\"0\">";
					foreach($records as $entry){
						$grape->output->content->html.=  "<tr><td>".date("d.m.Y H:i",strtotime($entry->timestamp))."</td><td>";
						if($entry->record_context=="personal"){
							if($entry->record_type=="trial"){
								$grape->output->content->html.=  $entry->user." hat ".($entry->gender=="female"?"ihren":"seinen")." ".grape_number($entry->record_value)."sten Klingelknopf gedrückt!";
							}
							else{
								$grape->output->content->html.=  $entry->user." hatte ".($entry->gender=="female"?"ihren":"seinen")." ".grape_number($entry->record_value)."sten Kontakt!";
							}
						}
						else{
							if($entry->record_type=="trial"){
								$grape->output->content->html.=  $entry->user." hat den ".grape_number($entry->record_value)."sten Klingelknopf ";
								if($entry->record_context=="regional")
									echo "in ".$entry->wahlkreis_name." ";
								$grape->output->content->html.=  "gedrückt!";
							}
							else{
								$grape->output->content->html.=  $entry->user." hatte den ".grape_number($entry->record_value)."sten Kontakt ";
								if($entry->record_context=="regional")
									echo "in ".$entry->wahlkreis_name;
								$grape->output->content->html.=  "!";
							}
						}
						$grape->output->content->html.=  "</td></tr>";
					}
					$grape->output->content->html.=  "</table>";
				}

				$grape->output->content->html.=  "<p>Neu hier? Vielleicht hilft Dir unsere <a href=\"https://gruene.fritzmielert.de/Video_TZT.mp4\">Videoanleitung</a> weiter. Achtung! Nicht für Mobilgeräte geeignet, da sie 360MB auf die Waage bringt.</p>";*/
	$grape->output->help->html = "Hier beginnt Dein Tür-zu-Tür-Wahlkampf. Du kannst einen Wahlbezirk auswählen oder Du suchst über das Menü links oben direkt einen Straßenzug.";
}
/**
 *
 */
function canvassing_user_statistics(){
	global $grape;
	$user_id = $grape->user->user_id;
	$sql = "SELECT COUNT(`canvassing_contacts`.`contact_id`) AS trials, SUM(`canvassing_contacts`.`contact`) AS contacts
			FROM `canvassing_contacts` 
			WHERE 
				`canvassing_contacts`.`user_id` = $user_id
				OR 
				`canvassing_contacts`.`partner_user_id` = $user_id";
	$grape->db->query($sql);
	$results = $grape->db->get_results();
	return $results[0];
}
/**
 *
 */
function canvassing_take_street(){
	global $grape;
	$grape->result = "error";
	//$grape->output->content->html.= "<pre>".print_r($_REQUEST,true)."</pre>";
	$result = canvassing_lock_street();
	if($result === true) {
		$grape->result = "success";
		$message = "Straßenzug reserviert";
		//$job = "ringThatBell_".$_REQUEST["street"];
		//$_REQUEST["job"] = $job;
	}
	return $message;
}
/**
 *
 */
function canvassing_lock_street(){
	global $grape;
	$user_id = $grape->user->user_id;
	$x_street_id = intval($_REQUEST["x_street_id"]);
	$sql = "INSERT INTO `canvassing_street_data` (`locked_by`,`locked_at`,`x_street_id`) VALUES ($user_id, CURRENT_TIMESTAMP, $x_street_id);";
	$grape->db->query($sql);
	return true;
}
/**
 *
 */
function canvassing_select_street(){
	global $grape;
	$html = "";
	$x_ward_id = intval($_REQUEST["x_ward_id"]);
	$campaign_id = intval($_REQUEST["campaign_id"]);
	//$grape->output->content->html.= "<pre>".print_r($_REQUEST,true)."</pre>";
	$ward_data = canvassing_get_electoral_ward($x_ward_id);
	//$grape->output->content->html.= "<pre>".print_r($ward_data,true)."</pre>";
	//$grape->output->content->html.= "<p>Admin?</p>";
	$capability = grape_get_capability_of_current_user_for_ou($ward_data->ou_id);
	//$grape->output->content->html.= "<pre>".print_r($capability,true)."</pre>";
	if($capability=="admin"){
		$html.= '<form id="toggle_voting_district">
				<input type="hidden" name="campaign" value="'.$campaign_id.'"/>
				<input type="hidden" name="module" value="canvassing"/>
				<input type="hidden" name="job" value="toggle_voting_district"/>
				<input type="hidden" name="x_ward_id" value="'.$x_ward_id.'"/>
			</form>';
		if($ward_data->visible == "0"){
			$html.= '<p>Du bist Admin. Willst Du den <a href="javascript:document.getElementById(\'toggle_voting_district\').submit();">Wahlbezirk '.$ward_data->code.' sichtbar machen</a>?</p>';
		}
		else {
			$html.= '<p>Du bist Admin. Willst Du den <a href="javascript:document.getElementById(\'toggle_voting_district\').submit();">Wahlbezirk '.$ward_data->code.' verstecken</a>?</p>';
		}
	}
	if($ward_data->visible == "0"){
		echo "Der Wahlbezirk ist leider gesperrt. Du kannst ihn nicht bearbeiten. Bitte wende Dich an die Koordinator*innen in Deiner Region.";
	}
	else{
		$grape->output->add_javascript(URL."grape-modules/canvassing/mapping.js?v=4");
		$overlay_params = "'".URL."ajax.php?module=canvassing&job=map_electoral_ward_ajax&x_ward_id=".$x_ward_id."','map_electoral_ward','#grape_overlay2'";
		$streets = canvassing_get_streets($x_ward_id);
		//$grape->output->content->html.= "<pre>".print_r($streets,true)."</pre>";
		if($streets && count($streets)>0){
			$html.= '
		<form>
			<input type="hidden" name="module" value="canvassing"/>
			<input type="hidden" name="campaign_id" value="'.$campaign_id.'"/>
			<input type="hidden" name="x_ward_id" value="'.$ward_data->x_ward_id.'"/>
			<input type="hidden" name="job" value="takeStreet"/>
			<h2>Dein Straßenabschnitt im Wahlbezirk '.$ward_data->electoral_ward_name.'</h2>
			<select name="x_street_id" id="x_street_id" data-iconpos="left" onchange="update_street_lock_button(this.value);">';
				$js_first_enabled_street_id = -1;
				$js_locked_streets = array();
				foreach($streets as $street){
					$street->locked_by = explode(",",$street->locked_by);
					$html.= '<option value="'.$street->x_street_id.'" '.(($street->done!=""||in_array($grape->user->user_id,$street->locked_by))?'disabled="disabled"':'').'>'.$street->name.(($street->comment!="")?' ('.$street->comment.')':'').'</option>
		';
					if($street->comment!=""&&!in_array($grape->user->user_id,$street->locked_by)) array_push($js_locked_streets,'"'.$street->x_street_id.'"');
					if(($street->done==""||!in_array($grape->user->user_id,$street->locked_by)) && $js_first_enabled_street_id == -1){
						$js_first_enabled_street_id = $street->x_street_id;
					}
				}
			$html.= '</select>';
			if($ward_data->geodata != "") {
				$html.=  '<p>Du hast Zweifel, ob Du im richtigen Wahlbezirk bist? <a href="#" onclick="construct_map('.$overlay_params.');$(\'#grape_overlay2\').show();$(\'#main-content\').hide();">Schau auf der Karte nach!</a></p>';
			}
			$html.= '
			<div class="btn-group btn-block special" role="group">
				<a href="#" class="btn btn-secondary" onclick="load_content(\'module=canvassing&job=selectVotingDistrict&campaign_id='.$campaign_id.'\');">Zurück</a>
				<button class="btn btn-primary" name="job" value="takeStreet" id="street_lock_button">Straßenzug (auch) bearbeiten</button>
			</div>
			<div id="street_lock_button_comment"></div>
		</form>
		<script language="javascript">
			var locked_streets = ['.join(",",$js_locked_streets).'];
			function update_street_lock_button(street_id){
				console.log(street_id);
				console.log(locked_streets);
				var result = locked_streets.indexOf(street_id);
				console.log(result);
				if(result !== -1){
					document.getElementById("street_lock_button").innerHTML = "Bei der Bearbeitung des Straßenzugs helfen";
					document.getElementById("street_lock_button_comment").innerHTML = "Bitte sprecht Euch ab!";
				}
				else{
					document.getElementById("street_lock_button").innerHTML = "Straßenzug bearbeiten";
					document.getElementById("street_lock_button_comment").innerHTML = "";
				}
			}
			update_street_lock_button("'.$js_first_enabled_street_id.'");
		</script>';
		}
		else{
			$html.= "<p>Keine Straßen zu finden...</p>";
		}
		$grape->output->content->html.= $grape->output->wrap_div($html);
	}
	$logbook = canvassing_get_logbook_electoral_ward($ward_data->x_ward_id,10);
	if($logbook){
		$html = "<h2>Das tut sich in diesem Wahlbezirk:</h2><table>";
		foreach($logbook as $entry){
			$html.= "<tr><td>".date("d.m.Y H:i",strtotime($entry->last_changes))."</td><td>".trim($entry->name).": ".$entry->my_comment." (".$entry->user;
			if($grape->user->user_id != $entry->user_id){
				$html.= " <a href=\"?job=mail_street&return=".urlencode(json_encode($_REQUEST))."&street_data_id=".$entry->street_data_id."\">anmailen</a>";
			}
			$html.= ")</td></tr>";
		}
		$html.= "</table>";
		$grape->output->content->html.= $grape->output->wrap_div($html);
		
	}
	if($capability=="admin"){
		$html= '<h2>Geodaten des Wahlbezirks</h2><form>
				<input type="hidden" name="module" value="canvassing"/>
				<input type="hidden" name="campaign_id" value="'.$campaign_id.'"/>
				<input type="hidden" name="x_ward_id" value="'.$ward_data->x_ward_id.'"/>
				<textarea name="geojson" class="form-control" id="geojson" rows="10" cols="30">'.json_encode(canvassing_multipolygon2polygons(json_decode($ward_data->geodata))).'</textarea><br/>
		<div class="btn-group btn-block special" role="group">
			<a target="_blank" href="http://geojson.io/#data=data:application/json,'.urlencode(json_encode(canvassing_multipolygon2polygons(json_decode($ward_data->geodata)))).'" class="btn btn-secondary">Daten in geojson.io bearbeiten</a>
			<button class="btn btn-primary" type="submit" name="job" value="save_geodata">Geodaten aktualisieren</button>
		</div>
		</form><br/>';
		$grape->output->content->html.= $grape->output->wrap_div($html);
		$html = "<h2>Straßenabschnitte im Wahlbezirk</h2><p>Diese Liste hilft Dir, wenn Du die Geodaten in json.io bearbeitest.</p>";
		foreach($streets as $street){
			$html.= $street->name.'<br/>';
		}
		$grape->output->content->html.= $grape->output->wrap_div($html);
	}
	//$grape->output->content->html.= $grape->output->wrap_div($html);
	canvassing_build_menu();
}
/**
 *
 */
function canvassing_ring_that_bell(){
	global $grape;
	$x_street_id = intval($_REQUEST["x_street_id"]);
	$html = "";
	if(canvassing_get_street_data_of_user($x_street_id)!==false){
		$grape->output->result = "";
		$grape->output->content_type = "focus";
		//$grape->output->content->html.= "<pre>".print_r($_REQUEST,true)."</pre>";
		$campaign_id = intval($_REQUEST["campaign_id"]);
		if(isset($_REQUEST["partner_id"])){
			$partner_id = intval($_REQUEST["partner_id"]);
		}
		else{
			$partner_id = false;
		}
		$x_ward_id = canvassing_get_x_ward_id_by_x_street_id($x_street_id);
		//$campaign = grape_campaign_by_campaign_id($campaign_id);
		$street_data = canvassing_get_street($x_street_id);
		//$grape->output->content->html.= "<pre>".print_r($street_data,true)."</pre>";
		$overlay_params = "'".URL."/ajax.php?module=canvassing&job=map_electoral_ward_ajax&x_ward_id=".$x_ward_id."','map_electoral_ward','#grape_overlay'";
		if($partner_id === false){
			$html.= "<h2>Gleich geht's los...</h2>";
			$html.= "<p><!--".$street_data->code." ".$street_data->electoral_ward_name." / --> ...in der/dem ".$street_data->street_name.' <!--<a href="?module=canvassing&job=map_electoral_ward&campaign_id='.$campaign_id.'&x_ward_id='.$street_data->x_ward_id.'" target="_blank">Kartenansicht</a>--><!--<br/>';
			$html.= $street_data->electoral_district_name."--></p>";
			if($street_data->comment) $html.= "<p>Kommentare:<br/>".str_replace("<a href=\"","<a href=\"?module=canvassing&campaign_id='.$campaign_id.'&job=mail_street&return=".urlencode(json_encode($_REQUEST)),$street_data->comment)."</p>";
			$users = grape_get_users_by_ou($grape->user->ou_id);
			$html.= '<form>
				<input type="hidden" name="module" value="canvassing"/>
				<input type="hidden" name="campaign_id" value="'.$campaign_id.'"/>
				<input type="hidden" name="x_street_id" value="'.$x_street_id.'"/>
				<input type="hidden" name="job" value="ringThatBell"/>
				<label for="partner_id">Bist Du alleine unterwegs oder in Begleitung?</label>
				<select name="partner_id" id="partner_id" data-iconpos="left" class="form-control">
					<option value="0">alleine</option>
					<option value="-1">mit einem Mann ohne Account auf dieser Seite</option>
					<option value="-2">mit einer Frau ohne Account auf dieser Seite</option>';
					foreach($users as $user){
						if($user->user_id != $grape->user->user_id){
							$html.= '<option value="'.$user->user_id.'">mit '.$user->name.' '.$user->last_name.'</option>';
						}
					}
			$html.= '
				</select>
				<div class="btn-group btn-block special" role="group">
					<a class="btn btn-secondary" href="#" onclick="load_content(\'module=canvassing&campaign_id='.$campaign_id.'\');" role="button">Zurück</a>
					<button class="btn btn-primary">Weiter</button>
				</div>
			</form>';
		}
		else{
			$grape->output->add_javascript(URL."grape-modules/canvassing/mapping.js?v=4");
			$html.= "<p><!--".$street_data->code." ".$street_data->electoral_ward_name." / -->".$street_data->street_name.'<!--<br/>';
			$html.= $street_data->electoral_district_name."--></p>";
			if($street_data->comment) $html.= "<p>Kommentare:<br/>".substr($street_data->comment,strpos($street_data->comment,">")+1,10000)."</p>";
			$grape->output->content_type = "focus";
			$server_time = microtime(true);
			$grape->output->add_javascript(URL."grape-modules/canvassing/canvassing.js?v=4","startup();");
			//$overlay_params = "{module:'canvassing',job:'map_electoral_ward',x_ward_id:".$street_data->x_ward_id.",mode:'overlay'}";
			$html.= '
				<div id="actions" class="btn-group btn-block special" role="group" aria-label="">
					<button id="button-undo" class="btn btn-standard fa fa-undo" onclick="undo();" disabled></button>
					<button id="button-map" class="btn btn-standard fa fa-map" onclick="construct_map('.$overlay_params.');$(\'#grape_overlay\').show();"></button>
					<button id="button-help" class="btn btn-standard fa fa-question" onclick="help();"></button>
				</div>
				<script>
					var url = "'.URL.'ajax.php?module=canvassing&campaign_id='.$campaign_id.'";
				</script>
				<div id="hidden-data">
					<div id="data_x_street_id">'.$x_street_id.'</div>
					<div id="data_partner_id">'.$partner_id.'</div>
				</div>
				<div class="box">
					<div id="unsuccessful" class="reaction-class filled-box">
						<div class="box-header">nicht angetroffen</div>
						<div class="reaction">
							<div class="smiley">
								<button class="reaction-unsuccessful btn btn-primary"></button>
								<div class="counter unsuccessful">0</div>
							</div>
						</div>
					</div>
					<div id="personal" class="reaction-class filled-box">
						<div class="box-header">persönlich angetroffen</div>
						<div class="reaction">
							<div class="smiley">
								<button class="reaction-negative btn btn-primary" title="negativ"></button>
								<div class="counter negative">0</div>
							</div>
							<div class="smiley">
								<button class="reaction-neutral btn btn-primary" title="neutral"></button>
								<div class="counter neutral">0</div>
							</div>
							<div class="smiley">
								<button class="reaction-positive btn btn-primary" title="positiv"></button>
								<div class="counter positive">0</div>
							</div>
						</div>
					</div>
					<div id="intercom" class="reaction-class filled-box">
						<div class="box-header">über Sprechanlage gesprochen</div>
						<div class="reaction">
							<div class="smiley">
								<button class="reaction-negative btn btn-primary" title="negativ"></button>
								<div class="counter negative">0</div>
							</div>
							<div class="smiley">
								<button class="reaction-neutral btn btn-primary" title="neutral"></button>
								<div class="counter neutral">0</div>
							</div>
							<div class="smiley">
								<button class="reaction-positive btn btn-primary" title="positiv"></button>
								<div class="counter positive">0</div>
							</div>
						</div>
					</div>
				</div>
				<div id="actions" class="btn-group btn-block special" role="group" aria-label="">
					<button id="button-abort" class="btn btn-standard" onclick="submit(false);">speichern & unterbrechen</button><br/>
					<button id="button-finished" class="btn btn-standard" onclick="submit(true);">speichern & beenden</button>
				</div>
				<div id="statusbar">
					<div style="display: none;"><br/> Serverzeit: <span id="server-time">'.$server_time.'</span>
					<br/> Lokale Zeit: <span id="local-time"></span></div>
				</div>
				<form id="street-comment-form" method="post" action="index.php">
					<input type="hidden" name="x_street_id" value="'.$x_street_id.'"/>
					<input type="hidden" name="module" value="canvassing"/>
					<input type="hidden" name="job" value="streetComment"/>
					<input type="hidden" name="partner_id" value="'.$partner_id.'"/>
					<input type="hidden" name="campaign_id" id="campaign_id" value="'.$campaign_id.'"/>
				</form>
				<form id="street-finish-form" method="post" action="index.php">
					<input type="hidden" name="x_street_id" value="'.$x_street_id.'"/>
					<input type="hidden" name="module" value="canvassing"/>
					<input type="hidden" name="job" value="finishStreet"/>
					<input type="hidden" name="partner_id" value="'.$partner_id.'"/>
					<input type="hidden" name="campaign_id" value="'.$campaign_id.'"/>
				</form>';
		}
	}
	$grape->output->content->html.= $grape->output->wrap_div($html);
	//$grape->output->content->html.= $html;
}
/**
 *
 */
function canvassing_save_contacts(){
	global $grape;
	$x_street_id = intval($_REQUEST["x_street_id"]);
	if(canvassing_get_street_data_of_user($x_street_id)!==false){
		error_reporting(E_ALL);
	
		$contacts = json_decode($_REQUEST["contacts"]);
		$x_ward_id = grape_get_x_ward_by_x_street($x_street_id);
		$partner_id = intval($_REQUEST["partner_id"]);
		$time_difference_in_milliseconds = $_REQUEST["time_difference_in_milliseconds"];
		$street_is_complete = $_REQUEST["street_is_complete"];
	
		//print_r($_REQUEST);
	
		foreach ($contacts as $contact) {
			$timestamp = $contact->timestamp;
			$contact_type = $contact->contact_type;
			$reaction = $contact->reaction;
	
			// contact is 1 if the bell was answered and 0 otherwise.
			$db_contact = ($contact_type=="unsuccessful") ? 0 : 1;
			// face2face is 1 if the contact was personal, 0 if it was over the intercom
			$db_face2face = ($contact_type=="personal") ? 1 : 0;
	
			switch ($reaction) {
				case "reaction-very-negative":
					$db_mood = 1;
					break;
				case "reaction-negative":
					$db_mood = 2;
					break;
				case "reaction-neutral":
					$db_mood = 3;
					break;
				case "reaction-positive":
					$db_mood = 4;
					break;
				case "reaction-very-positive":
					$db_mood = 5;
					break;
				default:
					$db_mood = 0;
			}
			canvassing_save_door_contact($x_ward_id,$db_contact,$db_face2face,$db_mood,$partner_id,($timestamp*1.0-$time_difference_in_milliseconds)/1000);
		}
		$grape->output->result = "success";
		$grape->output->message = '<strong>Vielen Dank für deine Hilfe!</strong> Deine '.count($contacts).' Kontakte habe ich gespeichert. ';
		if($_REQUEST["street_is_complete"]=="false"){
			canvassing_street_comment();
		}
		else{
			canvassing_finish_street();
			canvassing_start();
		}
	}
}
/**
 * @param int $x_ward_id
 * @param int $contact (0,1)
 * @param int $face2face (0,1)
 * @param int $mood
 * @param int $partner_id
 * @param string $timestamp
 * @return string Message
 */
function canvassing_save_door_contact($x_ward_id,$contact,$face2face,$mood,$partner_id,$timestamp = false){
	global $grape;
	if(!$timestamp) $timestamp = "NOW()";
	else {
		// manipulation needed for creation of date object from possible int timestamp
		if(intval($timestamp) == $timestamp) $timestamp+= 0.001;
		$date = DateTime::createFromFormat('U.u', $timestamp);
		$date->setTimezone(new DateTimeZone("Europe/Berlin"));
		$timestamp = "'".$date->format('Y-m-d H:i:s.u')."'";
	}
	//echo "\n$timestamp\n";
	$user_id = $grape->user->user_id;
	$x_ward_id = intval($x_ward_id);
	$contact = intval($contact);
	$face2face = intval($face2face);
	$mood = intval($mood);
	$partner_id = intval($partner_id);
	$sql = "INSERT INTO `canvassing_contacts`
			(
				`user_id`,
				`x_ward_id`,
				`contact`,
				`face2face`,
				`mood`,
				`partner_user_id`,
				`timestamp`
			)
			VALUES
			(
				$user_id,
				$x_ward_id,
				$contact,
				$face2face,
				$mood,
				$partner_id,
				$timestamp
			)";
	//echo $sql;
	$grape->db->query($sql);
	return true;
}
/**
 *
 */
function canvassing_street_comment(){
	global $grape;
	$html = "";
	//$html.= $grape->output->dump_var($_REQUEST);
	$x_street_id = intval($_REQUEST["x_street_id"]);
	$campaign_id = intval($_REQUEST["campaign_id"]);
	$street_data = canvassing_get_street($x_street_id);
	$street_data_of_user = canvassing_get_street_data_of_user($x_street_id);
	$html.= '<p>Du hast den Straßenzug "'.$street_data->street_name.'" abgebrochen.</p><p>Beschreibe doch kurz, welchen Teil (Anfang und Ende) Du erledigt hast. Das erleichtert Dir und anderen die weitere Bearbeitung.</p>
	<p>Bisherige Kommentare:<br/>'.$street_data->comment.'</p>
	<form method="post" action="index.php">
		<input type="hidden" name="module" value="canvassing"/>
		<input type="hidden" name="x_street_id" value="'.$x_street_id.'"/>
		<input type="hidden" name="campaign_id" value="'.$campaign_id.'"/>
		<input type="hidden" name="job" value="streetCommentSave"/>
		<div class="form-group">
			<label for="comment">Dein Kommentar:</label>
			<textarea class="form-control" id="comment" name="comment" rows="10">'.(($street_data_of_user!==false)?$street_data_of_user->comment:"").'</textarea>
		</div>
		<button class="btn btn-primary" name="job" value="streetCommentSave">Kommentar speichern</button>
	</form>';
	$grape->output->content->html = $grape->output->wrap_div($html);
}
/**
 *
 */
function canvassing_get_x_ward_id_by_x_street_id($x_street_id){
	global $grape;
	$x_street_id = intval($x_street_id);
	$sql = "SELECT `x_ward_id` FROM `grape_x_streets` WHERE `x_street_id` = $x_street_id";
	$grape->db->query($sql);
	if($grape->db->num_rows > 0){
		$results = $grape->db->get_results();
		return $results[0]->x_ward_id;
	}
	else{
		return false;
	}
}
/**
 * 
 */
function canvassing_get_street_data_of_user($x_street_id){
	global $grape;
	$x_street_id = intval($x_street_id);
	$user_id = $grape->user->user_id;
	$sql = "SELECT * FROM `canvassing_street_data` WHERE `x_street_id` = $x_street_id AND `locked_by` = $user_id";
	$grape->db->query($sql);
	if($grape->db->num_rows > 0){
		$results = $grape->db->get_results();
		return $results[0];
	}
	else{
		return false;
	}
}
/**
 *
 */
function canvassing_street_comment_save(){
	global $grape;
	$x_street_id = intval($_REQUEST["x_street_id"]);
	//$grape->output->content->html.= '<pre>'.print_r($_REQUEST,true).'</pre>';
	$user_id = $grape->user->user_id;
	$comment = preg_replace('/[^a-zA-Z0-9\Ä\Ö\Ü\ä\ö\ü\ß\ \/\-\.\_\:\;\?\#\+\&\,]+/', "", $_REQUEST["comment"]);
	$sql = "UPDATE `canvassing_street_data` SET `comment` = '$comment' WHERE `x_street_id` = $x_street_id AND `locked_by` = $user_id";
	$grape->db->query($sql);
	$grape->output->result = "success";
	if($comment!=""){
		$grape->output->message = "Deinen Kommentar habe ich gespeichert.";
	}
	else{
		$grape->output->message = "Ich habe mir erlaubt, Deinen leeren Kommentar den Hasen zu geben.";
	}
}
/**
 * @version RemovedLockCheck
 */
function canvassing_finish_street(){
	global $grape;
	$x_street_id = intval($_REQUEST["x_street_id"]);
	$sql = "UPDATE `canvassing_street_data` SET `done` = CURRENT_TIMESTAMP WHERE `x_street_id` = $x_street_id;";
	$grape->db->query($sql);
	$grape->output->message.= " Den Straßenzug habe ich als erledigt markiert.";
	return true;
}
/**
 *
 */
function canvassing_get_street($x_street_id){
	global $grape;
	$x_street_id = intval($x_street_id);
	$sql = "SELECT
					
						(SELECT GROUP_CONCAT(CONCAT('<a href=\"',`canvassing_street_data`.`street_data_id`,'\">',`grape_users`.`name`,'</a>',': ',`canvassing_street_data`.`comment`) SEPARATOR '<br/>')
						FROM `canvassing_street_data`
						LEFT JOIN `grape_users` ON `grape_users`.`user_id` = `canvassing_street_data`.`locked_by`
						WHERE `canvassing_street_data`.`x_street_id` = $x_street_id
						AND `comment` IS NOT NULL
						AND `comment` <> '')
					AS `comment`,
					`grape_x_streets`.`x_ward_id`,
					`grape_electoral_wards`.`electoral_ward_id`,
					`grape_electoral_wards`.`code`,
					`grape_electoral_wards`.`name` AS electoral_ward_name,
					`grape_streets`.`name` AS street_name,
					`grape_electoral_districts`.`name` as electoral_district_name
			FROM `grape_x_streets`
            LEFT JOIN `grape_streets` ON `grape_streets`.`street_id` = `grape_x_streets`.`street_id`
            LEFT JOIN `grape_x_wards` ON `grape_x_wards`.`x_ward_id` = `grape_x_streets`.`x_ward_id`
			LEFT JOIN `grape_electoral_wards` ON `grape_electoral_wards`.`electoral_ward_id` = `grape_x_wards`.`electoral_ward_id`
            LEFT JOIN `grape_x_elections_electoral_districts` ON `grape_x_elections_electoral_districts`.`x_election_district_id` = `grape_x_wards`.`x_election_district_id`
			LEFT JOIN `grape_electoral_districts` ON `grape_electoral_districts`.`electoral_district_id` = `grape_x_elections_electoral_districts`.`electoral_district_id`
			WHERE `grape_x_streets`.`x_street_id` = $x_street_id";
	//$grape->output->content->html.= "<pre>".print_r($sql,true)."</pre>";
	$grape->db->query($sql);
	$results = $grape->db->get_results();
	return $results[0];
}
/**
 *
 */
function canvassing_get_abandoned_streets($ou_id,$campaign_id){
	global $grape;
	$ou_id = intval($ou_id);
	$campaign_id = intval($campaign_id);
	$sql = "SELECT `canvassing_street_data`.*,
`grape_users`.`name` AS user_name,
`grape_users`.`user_id`,
`grape_streets`.`name` AS street_name,
`grape_electoral_wards`.`code`,
`grape_electoral_wards`.`name` AS ward_name,
`grape_electoral_districts`.`name` AS district_name
FROM `canvassing_street_data`
LEFT JOIN `grape_x_streets` ON `grape_x_streets`.`street_id` = `canvassing_street_data`.`x_street_id`
LEFT JOIN `grape_streets` ON `grape_streets`.`street_id` = `grape_x_streets`.`street_id`
LEFT JOIN `grape_users` ON `grape_users`.`user_id` = `canvassing_street_data`.`locked_by`
LEFT JOIN `grape_x_wards` ON `grape_x_wards`.`x_ward_id` = `grape_x_streets`.`x_ward_id`
LEFT JOIN `grape_electoral_wards` ON `grape_electoral_wards`.`electoral_ward_id` = `grape_x_wards`.`electoral_ward_id`
LEFT JOIN `grape_x_elections_electoral_districts` ON `grape_x_elections_electoral_districts`.`x_election_district_id` = `grape_x_wards`.`x_election_district_id`
LEFT JOIN `grape_electoral_districts` ON `grape_electoral_districts`.`electoral_district_id` = `grape_x_elections_electoral_districts`.`electoral_district_id`
LEFT JOIN `grape_campaigns` ON `grape_campaigns`.`election_id` = `grape_x_elections_electoral_districts`.`election_id`
LEFT JOIN `grape_x_eed_ou` ON `grape_x_eed_ou`.`x_election_district_id` = `grape_x_elections_electoral_districts`.`x_election_district_id`
WHERE `grape_x_eed_ou`.`ou_id` = $ou_id
AND `grape_campaigns`.`campaign_id` = $campaign_id
AND `canvassing_street_data`.`done` IS NULL
AND `canvassing_street_data`.`last_changes` < NOW() - INTERVAL 1 WEEK";
	//$grape->output->content->html.= $sql;
	$grape->db->query($sql);
	$results = $grape->db->get_results();
	return $results;
}
/**
 *
 */
function canvassing_map_electoral_ward(){
	global $grape;
	//$grape->output->content->html.= "<div id='map' style='width: 100%; position: fixed; left: 0px; top: 50px; bottom:23px;'></div>";
	//$grape->output->content->html.= "<pre>".print_r($_SERVER,true)."</pre>";
	
	$grape->output->result = "";
	$grape->output->content_type = "embedded_map";
	//$grape->output->add_javascript(URL."external-js-libraries/leaflet/leaflet.js");
	//$grape->output->add_javascript(URL."external-js-libraries/leaflet/L.Control.Locate.js");
	//$grape->output->add_css(URL."external-js-libraries/leaflet/leaflet.css");
	$grape->output->add_javascript(URL."grape-modules/canvassing/mapping.js","construct_map('".URL."ajax.php?module=canvassing&job=map_electoral_ward_ajax&x_ward_id=".$_REQUEST["x_ward_id"]."','map_electoral_ward');");
	$grape->output->content->html.= "<div id='map' style='width: 100%; position: absolute; left: 0px; top: 0px; bottom:0px; height: 100%;'></div>";

}
/**
 *
 */
function canvassing_map_electoral_ward_ajax(){
	global $grape;
	$x_ward_id = intval($_REQUEST["x_ward_id"]);
	echo'
{
	"type":"FeatureCollection",
	"crs":{
		"type":"name",
		"properties":{
			"name":"urn:ogc:def:crs:OGC:1.3:CRS84"
		}
	},
	"features":[';
	$dataset = canvassing_get_electoral_ward($x_ward_id);

	echo '{
							"type":"Feature",
							"properties":{
										"code": "'.str_replace('"',"'",$dataset->code).'",
										"potential": '.floatval($dataset->potential).',
										"electoral_ward_id": '.$dataset->electoral_ward_id.',
										"x_ward_id": '.$dataset->x_ward_id.',
										"electoral_district_name": "'.str_replace('"',"'",$dataset->electoral_district_name).'",
										"electoral_ward_name": "'.str_replace('"',"'",$dataset->electoral_ward_name).'",
										"visible": '.((isset($dataset->visible)&&$dataset->visible==0)?0:1).',
										"done_rate": '.(($dataset->done_rate===false||$dataset->done_rate===''||!isset($dataset->done_rate))?0:$dataset->done_rate).',
										"attribution": "'.$dataset->attribution.'"
							},
							"geometry":'.$dataset->geodata.'
							}';

	echo'
	]
}';
	exit;
	
}
/**
 *
 */
function canvassing_get_electoral_ward($x_ward_id){
	global $grape;
	$x_ward_id = intval($x_ward_id);
	$sql = "SELECT
				`grape_electoral_wards`.`electoral_ward_id`,
				`grape_x_wards`.`x_ward_id`,
				`grape_electoral_wards`.`code`,
				`grape_electoral_wards`.`geodata`,
				`grape_electoral_wards`.`name` AS electoral_ward_name,
				`canvassing_electoral_ward_data`.`potential`,
				`canvassing_electoral_ward_data`.`visible`,
				`grape_x_eed_ou`.`ou_id`,
				`grape_x_elections_electoral_districts`.`electoral_district_id`,
				`grape_x_elections_electoral_districts`.`x_election_district_id`,
				`grape_electoral_districts`.`name` AS electoral_district_name,
				`grape_geodata_attributions`.`text` AS attribution,
				(SELECT
					COUNT(`canvassing_street_data`.`street_data_id`)
					FROM `canvassing_street_data`
					LEFT JOIN `grape_x_streets` ON `grape_x_streets`.`x_street_id` = `canvassing_street_data`.`x_street_id`
				WHERE `grape_x_streets`.`x_ward_id` = `grape_x_wards`.`x_ward_id`)
				/
				(SELECT
					COUNT(`grape_x_streets`.`street_id`)
					FROM `grape_x_streets`
					WHERE `grape_x_streets`.`x_ward_id` = `grape_x_wards`.`x_ward_id`
				) * 100 AS done_rate
				FROM `grape_x_wards`
				LEFT JOIN `grape_electoral_wards` ON `grape_electoral_wards`.`electoral_ward_id` = `grape_x_wards`.`electoral_ward_id`
				LEFT JOIN `canvassing_electoral_ward_data` ON `canvassing_electoral_ward_data`.`x_ward_id` = `grape_x_wards`.`x_ward_id`
				LEFT JOIN `grape_x_elections_electoral_districts` ON `grape_x_elections_electoral_districts`.`x_election_district_id` = `grape_x_wards`.`x_election_district_id`
				LEFT JOIN `grape_x_eed_ou` ON `grape_x_eed_ou`.`x_election_district_id` = `grape_x_elections_electoral_districts`.`x_election_district_id`
				LEFT JOIN `grape_electoral_districts` ON `grape_electoral_districts`.`electoral_district_id` = `grape_x_elections_electoral_districts`.`electoral_district_id`
				LEFT JOIN `grape_geodata_attributions` ON `grape_geodata_attributions`.`attribution_id` = `grape_electoral_wards`.`attribution_id`
				WHERE `grape_x_wards`.`x_ward_id` = $x_ward_id";
	$grape->db->query($sql);
	$results = $grape->db->get_results();
	$result = $results[0];
	
	return $result;
}
/**
 * @param int $user_id ID of user
 * @return object Result of query containing open jobs
 */
function canvassing_openJobs($user_id,$election_id){
	global $grape;
	$user_id = intval($user_id);
	$election_id = intval($election_id);
	$sql = "SELECT `canvassing_street_data`.*,
			`grape_streets`.*,
			`grape_electoral_districts`.`name` AS district_name
			FROM `canvassing_street_data`
			LEFT JOIN `grape_x_streets` ON `grape_x_streets`.`x_street_id` = `canvassing_street_data`.`x_street_id`
			LEFT JOIN `grape_streets` ON `grape_streets`.`street_id` = `grape_x_streets`.`street_id`
			LEFT JOIN `grape_x_wards` ON `grape_x_wards`.`x_ward_id` = `grape_x_streets`.`x_ward_id`
			LEFT JOIN `grape_x_elections_electoral_districts` ON `grape_x_elections_electoral_districts`.`x_election_district_id` = `grape_x_wards`.`x_election_district_id`
			LEFT JOIN `grape_electoral_districts` ON `grape_electoral_districts`.`electoral_district_id` = `grape_x_elections_electoral_districts`.`electoral_district_id`
			WHERE `canvassing_street_data`.`locked_by` = $user_id
			AND `grape_x_elections_electoral_districts`.`election_id` = $election_id
			AND `done` IS NULL";
	//$grape->output->content->html.= "<pre>".print_r($sql,true)."</pre>";
	$grape->db->query($sql);
	return $grape->db->get_results();
}
/**
 * @param int $user_id ID of user
 * @return object Result of query containing closed jobs
 */
function canvassing_closedJobs($user_id,$election_id){
	global $grape;
	$user_id = intval($user_id);
	$election_id = intval($election_id);
	$sql = "SELECT `canvassing_street_data`.*,
			`grape_streets`.*,
			`grape_electoral_districts`.`name` AS district_name
			FROM `canvassing_street_data` 
			LEFT JOIN `grape_x_streets` ON `grape_x_streets`.`x_street_id` = `canvassing_street_data`.`x_street_id`
			LEFT JOIN `grape_streets` ON `grape_streets`.`street_id` = `grape_x_streets`.`street_id`
			LEFT JOIN `grape_x_wards` ON `grape_x_wards`.`x_ward_id` = `grape_x_streets`.`x_ward_id`
			LEFT JOIN `grape_x_elections_electoral_districts` ON `grape_x_elections_electoral_districts`.`x_election_district_id` = `grape_x_wards`.`x_election_district_id`
			LEFT JOIN `grape_electoral_districts` ON `grape_electoral_districts`.`electoral_district_id` = `grape_x_elections_electoral_districts`.`electoral_district_id`
			WHERE `canvassing_street_data`.`locked_by` = $user_id
			AND `grape_x_elections_electoral_districts`.`election_id` = $election_id
			AND `done` IS NOT NULL
			ORDER BY `last_changes` DESC";
	//$grape->output->content->html.= "<pre>".print_r($sql,true)."</pre>";
	$grape->db->query($sql,10);
	return $grape->db->get_results();
}
/**
 *
 */
function canvassing_get_streets($x_ward_id){
	global $grape;
	$x_ward_id = intval($x_ward_id);
	$sql = "SELECT `grape_x_streets`.`x_street_id`, `grape_streets`.`name`, `canvassing_street_data`.`done`,
			(CASE WHEN `canvassing_street_data`.`done` IS NOT NULL
									THEN
										CONCAT('erledigt von ',(SELECT group_concat(`grape_users`.`name` SEPARATOR ', ') FROM `grape_users` WHERE `grape_users`.`user_id` = `canvassing_street_data`.`locked_by`))
									ELSE
										CASE WHEN `canvassing_street_data`.`locked_at` IS NOT NULL
											THEN
												CONCAT('in Bearbeitung durch ',(SELECT group_concat(`grape_users`.`name` SEPARATOR ', ') FROM `grape_users` WHERE `grape_users`.`user_id` = `canvassing_street_data`.`locked_by`))
											ELSE ''
										END
									END) AS `comment`,
								 (SELECT group_concat(`canvassing_street_data`.`locked_by` SEPARATOR ',') FROM `canvassing_street_data` WHERE `canvassing_street_data`.`x_street_id` = `grape_x_streets`.`x_street_id`) AS `locked_by`
			FROM `grape_x_streets`
			LEFT JOIN `grape_streets` ON `grape_streets`.`street_id` = `grape_x_streets`.`street_id`
			LEFT JOIN `canvassing_street_data` ON `canvassing_street_data`.`x_street_id` = `grape_x_streets`.`x_street_id`
			WHERE `grape_x_streets`.`x_ward_id` = $x_ward_id
			ORDER BY `grape_streets`.`name`";
	$grape->db->query($sql);
	return $grape->db->get_results();
}
/**
 *
 */
function canvassing_get_logbook_by_ou($ou_id,$election_id,$limit=false){
	global $grape;
	$ou_id = intval($ou_id);
	$election_id = intval($election_id);
	$sql = "SELECT 	`canvassing_street_data`.`last_changes`,
					`grape_electoral_districts`.`name` AS electoral_district,
					`grape_streets`.`name` AS street,
					`grape_streets`.`street_id`,
					`canvassing_street_data`.`street_data_id`,
					`grape_electoral_wards`.`code`,
					`grape_electoral_districts`.`electoral_district_id`,
					CONCAT(`grape_users`.`name`,' ',SUBSTRING(`grape_users`.`last_name`,1,1),'.') AS user,
					`grape_users`.`user_id`,
					`grape_users`.`email` AS email,
					(CASE 
						WHEN `canvassing_street_data`.`done` IS NOT NULL 
						THEN 'Straßenzug erledigt'
						ELSE 
							CASE 
								WHEN (`comment` IS NOT NULL AND `comment` <> '')
								THEN `comment`
								ELSE 'Straßenzug reserviert'
							END
						END) AS my_comment 
			FROM `canvassing_street_data` 
			LEFT JOIN `grape_users` ON `grape_users`.`user_id` = `canvassing_street_data`.`locked_by` 
			LEFT JOIN `grape_x_streets` ON `grape_x_streets`.`x_street_id` = `canvassing_street_data`.`x_street_id` 
			LEFT JOIN `grape_streets` ON `grape_streets`.`street_id` = `grape_x_streets`.`street_id` 
			LEFT JOIN `grape_x_wards` ON `grape_x_wards`.`x_ward_id` = `grape_x_streets`.`x_ward_id`
			LEFT JOIN `grape_electoral_wards` ON `grape_electoral_wards`.`electoral_ward_id` = `grape_x_wards`.`electoral_ward_id`
			LEFT JOIN `grape_x_elections_electoral_districts` ON `grape_x_elections_electoral_districts`.`x_election_district_id` = `grape_x_wards`.`x_election_district_id`
			LEFT JOIN `grape_electoral_districts` ON `grape_electoral_districts`.`electoral_district_id` = `grape_x_elections_electoral_districts`.`electoral_district_id`
			LEFT JOIN `grape_x_eed_ou` ON `grape_x_eed_ou`.`x_election_district_id` = `grape_x_elections_electoral_districts`.`x_election_district_id`
			WHERE `grape_x_eed_ou`.`ou_id` = $ou_id
			AND `grape_x_elections_electoral_districts`.`election_id` = $election_id
			ORDER BY `last_changes` DESC
			".(($limit!==false)?"LIMIT ".intval($limit):"");
	//$grape->output->content->html.= "<pre>".print_r($sql,true)."</pre>";
	$grape->db->query($sql);
	if($grape->db->num_rows > 0){
		return $grape->db->get_results();
	}
	else{
		return false;
	}
}
/**
 *
 */
function canvassing_get_logbook_electoral_district($x_election_district_id,$limit = false){
	global $grape;
	$x_election_district_id = intval($x_election_district_id);
	$sql = "SELECT `canvassing_street_data`.*,`grape_streets`.`name`,
					CONCAT(`grape_users`.`name`,' ',SUBSTRING(`grape_users`.`last_name`,1,1),'.') AS user,
					`grape_users`.`user_id`,
					`grape_users`.`email` AS email,
					(CASE 
						WHEN `done` IS NOT NULL 
						THEN 'Straßenzug erledigt'
						ELSE 
							CASE 
								WHEN (`comment` IS NOT NULL AND `comment` <> '')
								THEN `comment`
								ELSE 'Straßenzug reserviert'
							END
						END) AS my_comment
			FROM `canvassing_street_data`
			LEFT JOIN `grape_x_streets` ON `grape_x_streets`.`x_street_id` = `canvassing_street_data`.`x_street_id`
			LEFT JOIN `grape_x_wards` ON `grape_x_wards`.`x_ward_id` = `grape_x_streets`.`x_ward_id`
			LEFT JOIN `grape_streets` ON `grape_streets`.`street_id` = `grape_x_streets`.`street_id`
			LEFT JOIN `grape_users` ON `grape_users`.`user_id` = `canvassing_street_data`.`locked_by`
			WHERE `grape_x_wards`.`x_election_district_id` = $x_election_district_id
			ORDER BY `last_changes` DESC
			".(($limit!==false)?"LIMIT $limit":"");
	$grape->db->query($sql);
	return $grape->db->get_results();
}/**
 *
 */
function canvassing_get_logbook_electoral_ward($x_ward_id,$limit = false){
	global $grape;
	$x_ward_id = intval($x_ward_id);
	$sql = "SELECT `canvassing_street_data`.*,`grape_streets`.`name`,
					CONCAT(`grape_users`.`name`,' ',SUBSTRING(`grape_users`.`last_name`,1,1),'.') AS user,
					`grape_users`.`user_id`,
					`grape_users`.`email` AS email,
					(CASE 
						WHEN `done` IS NOT NULL 
						THEN 'Straßenzug erledigt'
						ELSE 
							CASE 
								WHEN (`comment` IS NOT NULL AND `comment` <> '')
								THEN `comment`
								ELSE 'Straßenzug reserviert'
							END
						END) AS my_comment
			FROM `canvassing_street_data`
			LEFT JOIN `grape_x_streets` ON `grape_x_streets`.`x_street_id` = `canvassing_street_data`.`x_street_id`
			LEFT JOIN `grape_x_wards` ON `grape_x_wards`.`x_ward_id` = `grape_x_streets`.`x_ward_id`
			LEFT JOIN `grape_streets` ON `grape_streets`.`street_id` = `grape_x_streets`.`street_id`
			LEFT JOIN `grape_users` ON `grape_users`.`user_id` = `canvassing_street_data`.`locked_by`
			WHERE `grape_x_wards`.`x_ward_id` = $x_ward_id
			ORDER BY `last_changes` DESC
			".(($limit!==false)?"LIMIT $limit":"");
	$grape->db->query($sql);
	return $grape->db->get_results();
}
/**
 *
 */
function canvassing_html_ward_select_by_ou($ou_id,$election_id){
	global $grape;
	$html = '';
	$districts = canvassing_get_electoral_districts_by_ou($ou_id,$election_id);
	foreach($districts as $item){
		$html.= '<optgroup label="'.$item->name.'">';
		//$html.= $item->name;
		$wards = canvassing_get_electoral_wards_by_district($item->electoral_district_id,$election_id);
		foreach($wards as $ward){
			$html.= '<option value="'.$ward->x_ward_id.'">'.$ward->name.' – '.$ward->code.' ('.$ward->potential.')</option>';
		}
		$html.= '</optgroup>';
	}
	return $html;
}
/**
 *
 */
function canvassing_get_electoral_districts_by_ou($ou_id,$election_id){
	global $grape;
	$ou_id = intval($ou_id);
	$election_id = intval($election_id);
	$sql = "
			SELECT 	`grape_x_elections_electoral_districts`.*,`grape_electoral_districts`.`name`
			FROM `grape_x_elections_electoral_districts`
			LEFT JOIN `grape_electoral_districts` ON `grape_electoral_districts`.`electoral_district_id` = `grape_x_elections_electoral_districts`.`electoral_district_id`
			LEFT JOIN `grape_x_eed_ou` ON `grape_x_eed_ou`.`x_election_district_id` = `grape_x_elections_electoral_districts`.`x_election_district_id`
			WHERE `grape_x_eed_ou`.`ou_id` = $ou_id
			AND `grape_x_elections_electoral_districts`.`election_id` = $election_id
			ORDER BY `grape_electoral_districts`.`name`";
	//$grape->output->content->html.= $grape->output->dump_var($sql);
	$grape->db->query($sql);
	$result = $grape->db->get_results();
	//$grape->output->content->html.= $grape->output->dump_var($grape->db);
	//$grape->output->content->html.= $grape->output->dump_var($result);
	return $result;
}
/**
 *
 */
function canvassing_get_electoral_wards_by_district($district_id,$election_id){
	global $grape;
	$district_id = intval($district_id);
	$election_id = intval($election_id);
	$sql = "
			SELECT
					`grape_x_wards`.`x_ward_id`,
					`grape_electoral_wards`.`name`,
					`grape_electoral_wards`.`code`,
					`canvassing_electoral_ward_data`.`potential`,
					`canvassing_electoral_ward_data`.`visible`/*,
					(
						SELECT COUNT(`street_id`)
						FROM `grape_streets`
						WHERE `electoral_ward_id` = `grape_electoral_wards`.`electoral_ward_id`
					) AS street_counter*/
			FROM `grape_x_wards` 
			LEFT JOIN `grape_electoral_wards` ON `grape_electoral_wards`.`electoral_ward_id` = `grape_x_wards`.`electoral_ward_id`
			LEFT JOIN `grape_x_elections_electoral_districts` ON `grape_x_elections_electoral_districts`.`x_election_district_id` = `grape_x_wards`.`x_election_district_id`
			LEFT JOIN `canvassing_electoral_ward_data` ON `canvassing_electoral_ward_data`.`x_ward_id` = `grape_x_wards`.`x_ward_id`
			WHERE `grape_x_elections_electoral_districts`.`electoral_district_id` = $district_id
			AND `grape_x_elections_electoral_districts`.`election_id` = $election_id
			ORDER BY `grape_electoral_wards`.`code`,`grape_electoral_wards`.`name`";
	//$grape->output->content->html.= "<pre>$sql</pre>";
	$grape->db->query($sql);
	return $grape->db->get_results();
}
/**
 * @param object $geojson
 * @return object $geojson
 */
function canvassing_multipolygon2polygons($geojson){
	$new_geojson = json_decode('
		{
			"type":"FeatureCollection",
			"crs":{
				"type":"name",
				"properties":{
					"name":"urn:ogc:def:crs:OGC:1.3:CRS84"
				}
			},
			"features": []
		}');
	if($geojson->type=="MultiPolygon"){
		$polygon_geojson = json_decode('
			{
				"type": "Feature",
				"properties": {},
				"geometry": {
					"type": "Polygon",
					"coordinates": []
				}
			}');
		for($i=0;$i<(count($geojson->coordinates));$i++){
			$tmp_polygon = $polygon_geojson;
			$tmp_polygon->geometry->coordinates = $geojson->coordinates[$i];
			$new_geojson->features[$i] = json_decode(json_encode($tmp_polygon));
		}
	}
	else{
		$new_geojson->features[0] = $geojson;
	}
	$geojson = $new_geojson;
	return $geojson;
}
/**
 * @param object $geojson
 * @return object $geojson
 */
function canvassing_polygons2multipolygon($geojson){
	if(count($geojson->features) > 1){
		$new_geojson = json_decode('
			{
				"type":"FeatureCollection",
				"crs":{
					"type":"name",
					"properties":{
						"name":"urn:ogc:def:crs:OGC:1.3:CRS84"
					}
				},
				"features": [
					{
						"type": "Feature",
						"properties": {},
						"geometry": {
							"type": "MultiPolygon",
							"coordinates": []
						}
					}
				]
			}');
		for($i=0;$i<(count($geojson->features));$i++){
			$new_geojson->features[0]->geometry->coordinates[$i] = json_decode(json_encode($geojson->features[$i]->geometry->coordinates));
		}
		$geojson = $new_geojson;
	}
	return $geojson;
}
/**
 *
 */
function canvassing_get_election(){
	
}
/**
 *
 */
function canvassing_mail_street(){
	global $grape;
	$show_start = true;
	$html = "";
	//print_r($_REQUEST);
	$campaign_id = intval($_REQUEST["campaign_id"]);
	//$html.= $grape->output->dump_var($_REQUEST);
	if(isset($_REQUEST["send"]) && $_REQUEST["send"]=="send"){
		//echo "<p>send</p>";
		$user_id = intval($_REQUEST["user_id"]);
		$to_user = grape_get_user_by_id($user_id);
		if($to_user){
			$to_email = $to_user->email;
			if($to_email){
				$from_email = $grape->user->email;
				$subject = $_REQUEST["subject"];
				$body = nl2br($_REQUEST["message"]);
				grape_send_mail($from_email,$to_email,$subject,$body);
				$grape->output->result = "success";
				$grape->output->message = "Deine Nachricht wurde verschickt.";
				$html.= "<p><a class=\"btn btn-primary\" href=\"#\" role=\"button\" onclick=\"load_content('".urldecode($_REQUEST["return"])."');\">Zurück</a></p>";
			}
			else{
				$grape->output->result = "error";
				$grape->output->message = "Sorry, ich konnte unter der übergebenen user_id keine*n Benutzer*in finden.";
				$html.= "<p><a class=\"btn btn-primary\" href=\"#\" role=\"button\" onclick=\"load_content('".urldecode($_REQUEST["return"])."');\">Zurück</a></p>";
			}
		}
	}
	else{
		if(isset($_REQUEST["street_data_id"])){
			$street_data_id = intval($_REQUEST["street_data_id"]);
			$street_data = canvassing_get_steet_data($street_data_id);
			//$html.= $grape->output->dump_var($grape->settings);
			if($street_data){
				//$html.= print_r($street_data,true);
				$html.=    '<form method="post" action="index.php">
								<input type="hidden" name="campaign_id" value="'.$campaign_id.'"/>
								<input type="hidden" name="module" value="canvassing"/>
								<input type="hidden" name="user_id" value="'.$street_data->user_id.'"/>
								<input type="hidden" name="street_data_id" value="'.$street_data->street_data_id.'"/>
								<input type="hidden" name="send" value="send"/>
								<input type="hidden" name="return" value=\''.$_REQUEST["return"].'\'/>
								<input type="hidden" name="job" value="mail_street"/>
								<div class="form-group">
									<label for="subject">Betreff</label><br/>
									<input class="form-control" type="text" name="subject" value="Tür zu Tür | '.$street_data->name.'"/>
								</div>
								<div class="form-group">
									<label for="message">Nachricht</label>
									<textarea class="form-control" id="message" name="message" rows="10">Liebe'.(($street_data->gender=="male")?"r ":" ").$street_data->given_name.",\n";
				// is done
				if(strlen($street_data->done) != 0) $html.=  "Du hast den Straßenzug \"".$street_data->name."\" beendet.";
				// not done
				else{
					$html.=  "Du hast den Straßenzug \"".$street_data->name."\" reserviert, ";
					// no contacts
					if($street_data->last_changes == $street_data->locked_at){
						$html.=  "aber bisher anscheindend nicht bearbeitet ";
						// no comment
						if(strlen($street_data->comment) == 0){
							$html.=  "und auch keinen Kommentar hinterlassen.";
						}
						else{
							$html.=  "und den Kommentar \"".$street_data->comment."\" hinterlassen.";
						}
					}
					else{
						$html.=  "bis zum ".$street_data->last_changes." bearbeitet ";
						if(strlen($street_data->comment) == 0){
							$html.=  "und keinen Kommentar hinterlassen.";
						}
						else{
							$html.=  "und den Kommentar \"".$street_data->comment."\" hinterlassen.";
						}
					}
					// no comment
					
				}
				$html.=  "\nDazu habe ich eine Frage:\n\n";
				$html.=  "Herzliche Grüße\n".$grape->user->name;
				$html.=    '	</textarea>
				</div>
							<a class="btn btn-secondary" href="#" role="button" onclick="load_content(\''.urldecode($_REQUEST["return"]).'\');">Abbrechen</a>
							<button type="submit" class="btn btn-primary">Abschicken</button>
							
						</form>';
				//$grape->output->result = "success";
				//$grape->output->message = "";
				$show_start = false;
			}
			else{
				$grape->output->result = "error";
				$grape->output->message = "Sorry, mit der street_data_id konnte ich nichts anfangen.";
				$html.= "<p><a class=\"btn btn-primary\" href=\"#\" role=\"button\" onclick=\"load_content('".urldecode($_REQUEST["return"])."');\">Zurück</a></p>";
			}
		}
		else{
			$grape->output->result = "error";
			$grape->output->message = "Sorry, ich brauche eine street_data_id. Ohne die komme ich nicht weiter.";
			$html.= "<p><a class=\"btn btn-primary\" href=\"#\" role=\"button\" onclick=\"load_content('".urldecode($_REQUEST["return"])."');\">Zurück</a></p>";
		}
	}
	$grape->output->content->html.= $grape->output->wrap_div($html);
	if($show_start == true){
		canvassing_start();
	}
}
/**
 *
 */
function canvassing_get_steet_data($street_data_id){
	global $grape;
	$sql = "SELECT 	`canvassing_street_data`.*,
					`grape_streets`.`name`,
					`canvassing_street_data`.`locked_by` AS user_id,
					`grape_users`.`gender`,
					`grape_users`.`name` AS given_name
			FROM `canvassing_street_data`
			LEFT JOIN `grape_x_streets` ON `grape_x_streets`.`x_street_id` = `canvassing_street_data`.`x_street_id`
			LEFT JOIN `grape_streets` ON `grape_streets`.`street_id` = `grape_x_streets`.`street_id`
			LEFT JOIN `grape_users` ON `grape_users`.`user_id` = `canvassing_street_data`.`locked_by`
			WHERE `canvassing_street_data`.`street_data_id` = $street_data_id";
	//$grape->output->content->html.= $sql;
	$grape->db->query($sql);
	if($grape->db->num_rows > 0){
		$results = $grape->db->get_results();
		return $results[0];
	}
	else{
		return false;
	}
}
?>
