/* script identifier: mapping.js */

//var map = {};

function get_url_param(name){
   if(name=(new RegExp('[?&]'+encodeURIComponent(name)+'=([^&]*)')).exec(location.search))
      return decodeURIComponent(name[1]);
}
	var geojson = "";
	var outline_wahlbezirk = "";

var map_initialized = false;
function construct_map(electoral_ward_url,job,mapdiv="map"){
   $("#loader_wrapper").show();
   console.log("map("+electoral_ward_url+" , "+job+" , "+mapdiv+") fired");
   console.log(mapdiv);
   console.log(map_initialized);
   //if(!map_initialized){
      console.log("map job: "+job);
      /*if(job != "map_electoral_ward"){
         $(mapdiv).html("<div id='map' style='width: 100%; position: absolute; left: 0px; top: 54px; bottom:0px;'></div>");
      }
      else{
         $(mapdiv).html("<div id='map' style='width: 100%; position: absolute; left: 0px; top: 0px; bottom:0px;'></div>");
      }*/
   console.log("startup mapping.js construct_map()");

	console.log(electoral_ward_url);
	getAjax(electoral_ward_url, function(data){
      //console.log(data);
		outline_wahlbezirk = JSON.parse(data);
      console.log(outline_wahlbezirk);
      
      //attribution
      var attributions = [];
      for(i=0;i<outline_wahlbezirk.features.length;i++){
         attr = outline_wahlbezirk.features[i].properties.attribution;
         if(attributions.indexOf(attr)==-1){
            attributions.push(attr);
         }
      }
      attribution = attributions.join(", ");
      
      var child_div = $(mapdiv+" div:first-child").attr('id');
      console.log(mapdiv+" div:first-child : "+child_div);
      $("#"+child_div).removeClass();
      $("#"+child_div).removeAttr("tabindex");
      //console.log(map);
		map = L.map(child_div);
      // gray via css
		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			maxZoom: 18,
			attribution: 'Map data &copy; <a href="http://openstreetmap.org">OSM</a> contributors',
			id: 'mapbox.light'
		}).addTo(map);
      //
		// control that shows state info on hover
		var info = L.control();
	
		info.onAdd = function (map) {
         $("#loader_wrapper").hide();
			this._div = L.DomUtil.create('div', 'info');
			this.update();
			return this._div;
		};
	
		info.update = function (props) {
			//console.log(props);
         if(job != "map_electoral_ward"){
            if (typeof props !== 'undefined')
               if (typeof props.visible === 'undefined')
                  props.visible = 1;
            this._div.innerHTML = (props ?
               '<b>' + props.electoral_district_name + ' ' + props.electoral_ward_name + ' ' + props.code + '</b><br />Potential: ' + parseFloat(props.potential).toFixed(0) + '<br/>Status: '+((props.visible==1)?parseFloat(props.done_rate).toFixed(0)+' % erledigt':"gesperrt")+'<br/><a href="#" onclick="load_content(\'module=canvassing&campaign_id='+props.campaign_id+'&job=selectStreet&x_ward_id='+props.x_ward_id+'&job=selectStreet\');">Zum Wahlbezirk wechseln</a>' 
               : 'Bewege die Maus über ein Feld');
         }
         else{
            this._div.innerHTML = '<div onclick="$(\'#grape_overlay #map\').html(\'\');$(\'#grape_overlay\').hide();$(\'#main-content\').show();$(\'#grape_overlay2 #map\').html(\'\');$(\'#grape_overlay2\').hide();map.remove();map = null;" class="btn btn-primary">Karte schlie&szlig;en</div>';
         }
		};
	
		info.addTo(map);
	
		var layerSwitcher = L.control({position: 'bottomleft'});
		
		layerSwitcher.onAdd = function (map) {
			this._div = L.DomUtil.create('div', 'switcher');
			this._div.innerHTML = '<div onclick="switchLayer();" id="switch">Anzeige ändern</div>';
			return this._div;
		};
		//layerSwitcher.addTo(map);
		if(job != "map_electoral_ward") layerSwitcher.addTo(map);
		
		function highlightFeature(e) {
			var layer = e.target;
			console.log(layerStatus);
			if(layerStatus != "potential_bv"){
				layer.setStyle({
					weight: 2,
					color: '#46962b',
					dashArray: '',
					fillOpacity: 0.7
				});
			}
	
			if (!L.Browser.ie && !L.Browser.opera && !L.Browser.edge) {
				layer.bringToFront();
			}
	
			info.update(layer.feature.properties);
		}
		
		function resetHighlight(e) {
			//geojson.resetStyle(e.target);
			var layer = e.target;
			if(layerStatus != "potential_bv"){
				layer.setStyle({
					weight: 0.5,
					fillOpacity: (layer.feature.properties.visible==1)?0.7:0.1,
					color: '#666'
				});
			}
			info.update();
		}
		
		function goToVotingDistrict(e){
			var props = e.target.feature.properties;
			load_content("module=canvassing&campaign_id="+props.campaign_id+"&job=selectStreet&x_ward_id="+props.x_ward_id);
		}
		
		function clickHandler(e){
			if(isMobile) {
				if(layerStatus == "potential_local") geojson.setStyle(style_potential);
				else if(layerStatus == "status"){
					geojson.setStyle(style_done_rate);
				}
				else {
					geojson.setStyle(style);
				}
				highlightFeature(e);
			}
			else goToVotingDistrict(e);
		}
		
		function onEachFeature(feature, layer) {
			layer.on({
				mouseover: highlightFeature,
				mouseout: resetHighlight,
				click: clickHandler
			});
		}
	
		if(job != "map_electoral_ward"){
			geojson = L.geoJson(outline_wahlbezirk, {
				style: style_potential,
				onEachFeature: onEachFeature
			}).addTo(map);
		}
		else{
			geojson = L.geoJson(outline_wahlbezirk, {
				style: style
			}).addTo(map);
		}
		
		// just one wahlbezirk
		if(outline_wahlbezirk.features.length == 1)
			info.update(outline_wahlbezirk.features[0].properties);
	
		
		map.attributionControl.addAttribution(attribution);
	
		map.fitBounds(geojson.getBounds());
		
		var legend = L.control({position: 'bottomright'});
	
		legend.onAdd = function (map) {
	
			var div = L.DomUtil.create('div', 'info legend'),
				grades = [0, 20, 40, 60, 80, 100],
				labels = [],
				from, to;
	
			for (var i = 0; i < grades.length; i++) {
				from = grades[i];
				to = grades[i + 1];
	
				labels.push(
					'<i style="background:' + getColor(from + 1) + '"></i> ' +
					from + (to ? '&ndash;' + to : '+'));
			}
	
			div.innerHTML = labels.join('<br>');
			return div;
		};
	
		if(job != "map_electoral_ward"){
			legend.addTo(map);
		}
		
		L.control.locate({
			strings: {
				title: "Zeig mir, wo ich bin!"
			}
		}).addTo(map);
	
	}
   );
   //}
	
	function getColor(d) {
		return  d > 99.9 ? '#46962b' :
				d > 80   ? '#bd0026' :
				d > 60   ? '#f03b20' :
				d > 40   ? '#fd293c' :
				d > 20   ? '#fecc5c' :
				d > 0    ? '#ffffb2' :
						   '#ffffff';
	}
	
	
	function style(feature) {
		return {
			weight: 2,
			opacity: 1,
			color: '#46962b',
			fillOpacity: 0, fillColor: "rgb(0,0,0)"			
		};
	}
	
	function style_potential(feature) {
		return {
			weight: 0.5,
			opacity: 1,
			color: '#666',
			fillOpacity: (feature.properties.visible==1)?0.7:0.1,
			fillColor: getColor(feature.properties.potential)
		};
	}
	
	function style_done_rate(feature) {
		//console.log(feature.properties.visible);
		return {
			weight: 0.5,
			opacity: 1,
			color: '#666',
			fillOpacity: (feature.properties.visible==1)?0.7:0.1,
			fillColor: getColor(feature.properties.done_rate)
		};
	}
}
// old stuff

function getAjax(url, success) {
	console.log(url);
    var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    xhr.open('GET', url);
    xhr.onreadystatechange = function() {
        if (xhr.readyState>3 && xhr.status==200) success(xhr.responseText);
    };
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.send();
    return xhr;
}


function getColor(d) {
	return  d > 99.9 ? '#46962b' :
			d > 80   ? '#bd0026' :
			d > 60   ? '#f03b20' :
			d > 40   ? '#fd293c' :
			d > 20   ? '#fecc5c' :
			d > 0    ? '#ffffb2' :
					   '#ffffff';
}


function style(feature) {
	return {
		weight: 2,
		opacity: 1,
		color: '#46962b',
		fillOpacity: 0, fillColor: "rgb(0,0,0)"			
	};
}

function style_potential(feature) {
	return {
		weight: 0.5,
		opacity: 1,
		color: '#666',
		fillOpacity: (feature.properties.visible==1)?0.7:0.1,
		fillColor: getColor(feature.properties.potential)
	};
}

function style_done_rate(feature) {
	//console.log(feature.properties.visible);
	return {
		weight: 0.5,
		opacity: 1,
		color: '#666',
		fillOpacity: (feature.properties.visible==1)?0.7:0.1,
		fillColor: getColor(feature.properties.done_rate)
	};
}
var layerStatus = "potential_local";
$("#switch").html("Potential");
function switchLayer(){
	console.log("switch");
	if(layerStatus == "potential_local"){
		layerStatus = "status";
      $("#switch").html("Status");
		geojson.setStyle(style_done_rate);
		//wmsLayer.setOpacity(0);
	}
	else if(layerStatus == "status"){
		layerStatus = "potential_bv";
      $("#switch").html("Umriss");
		geojson.setStyle(style);
		//wmsLayer.setOpacity(0.7);
	}
	else{
		layerStatus = "potential_local";
      $("#switch").html("Potential");
		geojson.setStyle(style_potential);
		//wmsLayer.setOpacity(0);
	}
}