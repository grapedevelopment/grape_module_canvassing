/* script identifier: canvassing.js */

var contacts = [];
var counts = {};
var contact_id = 1;
var last_synchronized = 0;
var submit_period_milliseconds = 60 * 1000;
/**
 *
 */
function reset_contacts(){
	contacts = [];
	counts = {};
	contact_id = 1;
	last_synchronized = 0;
}
/**
 * restore storage an register callbacks
 */
function startup() {
	console.log("canvassing: startup triggered");
	reset_contacts();
	register_smiley_buttons();
	enable_undo();
	$("#local-time").text(get_timestamp()/1000);
	setInterval(submit_silent, submit_period_milliseconds);
}
/**
 * register button callbacks and button counts
 */
function register_smiley_buttons() {
	//var classes = document.getElementsByClassName('reaction-class');
	var classes = $(".reaction-class");
	for (var i = 0; i < classes.length; ++i) {
		var reaction_class = classes[i];
		var reaction_class_name = reaction_class.id;
		var reactions = reaction_class.getElementsByClassName('smiley');
		for (var j = 0; j < reactions.length; ++j) {
			var reaction_box = reactions[j];
			var button = reaction_box.getElementsByTagName('button')[0];
			var reaction = button.classList[0];
			var counter = reaction_box.getElementsByClassName('counter')[0];
			//console.log("register "+reaction_class_name+" "+reaction+" "+counter);
			button.onclick = smiley_clicked.bind(null, reaction_class_name, reaction, counter);

			// create or set count
			if (counts[reaction_class_name+"/"+reaction] == undefined) {
				counts[reaction_class_name+"/"+reaction] = 0;
			} else {
				counter.textContent = counts[reaction_class_name+"/"+reaction];
			}
		}
	}
}
/**
 *
 */
function get_timestamp() {
	if (!Date.now) {
		// IE8 workaround
		return new Date().getTime();
	} else {
		return Date.now();
	}
}
/**
 *
 */
function smiley_clicked(contact_type, reaction, counter) {
	// save contact
	add_contact(contact_type, reaction);
	// increase counter
	counter.textContent = counts[contact_type+"/"+reaction];
	enable_undo();
}
/**
 *
 */
function add_contact(contact_type, reaction) {
	contacts.push({
		id: contact_id,
		timestamp: get_timestamp(),
		contact_type: contact_type,
		reaction: reaction
	});
	++contact_id;
	$("#button-undo").disabled = false;
	++counts[contact_type+"/"+reaction];
	//updateLocalStorage();
	
	if (contacts.length % 10 === 0) {
		// avoid SAML timeout by requesting a dummy page from time to time
		$.post( base_url+"ajax.php", {"job": "ping"}, function(data, status, xhr){});
	}
}
/**
 *
 */
function get_counter(contact_type, reaction) {
	console.log("get_counter "+contact_type+" "+reaction);
	var id = "#"+contact_type+" .counter."+reaction.substr(9, 50);
	console.log("id: "+id);
	return $(id);
}
/**
 *
 */
function undo() {
	// assert: there is a contact because button is not disabled
	var contact = contacts[contacts.length-1];
	
	if (contact.timestamp < last_synchronized) {
		console.log("Error: Undo button is not disabled, but you cannot undo because contact has already been synchronized. ");
		return;
	}
	console.log("undo "+contact.contact_type+" "+contact.reaction);
	--counts[contact.contact_type+"/"+contact.reaction];
	var counter = get_counter(contact.contact_type, contact.reaction);
	console.log("counter: "+counter);
	//counter.text() = counts[contact.contact_type+"/"+contact.reaction];
	counter.html(counts[contact.contact_type+"/"+contact.reaction]);

	// remove contact
	contacts.splice(contacts.length-1, 1);

	enable_undo();
}
/**
 *
 */
function enable_undo() {
	console.log("enable_undo");
	console.log(contacts);
	if (contacts.length <= 0 || contacts[contacts.length-1].timestamp < last_synchronized) {
		document.getElementById('button-undo').disabled = true;
	}
	else{
		document.getElementById('button-undo').disabled = false;
	}
}
/**
 *
 */
function submit_silent(){
	if ($('#server-time').length > 0) {
		var server_time = parseFloat($('#server-time').text());
		var local_time = parseFloat($('#local-time').text());
		var time_difference_in_milliseconds = Math.round((local_time-server_time)*1000);
	
		last_synchronized = get_timestamp();
		console.log("Uploading contacts ("+new Date(last_synchronized).toUTCString()+")");
		
		// disable undo until next contact
		document.getElementById('button-undo').disabled = true;
		
		$.post( base_url+"ajax.php", {
				"contacts": JSON.stringify(contacts),
				"job": "saveContacts",
				"x_street_id": $("#data_x_street_id").text(),
				"partner_id": $("#data_partner_id").text(),
				"time_difference_in_milliseconds": time_difference_in_milliseconds,
				"street_is_complete": false
			}, function(data, status, xhr){});
	}
}
/**
 *
 */
function submit(street_is_complete) {
	if (contacts.length <= 0 && !street_is_complete) {
		//alert("Bitte erfasse zunächst deine Kontaktversuche.");
		//window.location.href = "index.php";
		//document.getElementById("street-comment-form").submit();
		load_content($("#street-comment-form").serialize());
		return;
	}

	var confirm_text = street_is_complete ?
		"Hast du alle Häuser in der angegebenen Straße abgearbeitet und möchtest die Daten nun abschließend speichern?"
		: "Willst du die Bearbeitung der Straße wirklich unterbrechen? Die bis hierhin erhobenen Daten werden gespeichert. Jemand anders kann die Bearbeitung der Straße anschließend fortführen.";

	if (!confirm(confirm_text)) {
		return;
	}
	var campaign_id = $("#campaign_id").val();
	var server_time = parseFloat($("#server-time").text());
	var local_time = parseFloat($("#local-time").text());
	console.log(server_time);
	console.log(local_time);
	var time_difference_in_milliseconds = Math.round((local_time-server_time)*1000);

	load_content({
			"module": "canvassing",
			"contacts": JSON.stringify(contacts),
			"job": "saveContacts",
			"x_street_id": $("#data_x_street_id").text(),
			"partner_id": $("#data_partner_id").text(),
			"campaign_id": campaign_id,
			"time_difference_in_milliseconds": time_difference_in_milliseconds,
			"street_is_complete": street_is_complete,
			"comment":street_is_complete
		});
}

function clear_data() {
	if (confirm("Willst du die in dieser Straße von dir erhobenen Daten wirklich UNWIEDERBRINGLICH LÖSCHEN?")) {
		clear_data_core();
	}
}
/**
 *
 */
function clear_data_core() {
	localStorage.clear();
	contacts = [];
	for (var key in counts) {
		counts[key] = 0;
	}
	// this must be called in order to reset the counter labels:
	register_smiley_buttons();
}
/**
 *
 */
function help() {
	alert("Klicke nach jedem Klingeln entweder auf den Button unter NICHT ANGETROFFEN, oder bewerte die Stimmung unter PERSÖNLICH ANGETROFFEN bzw. ÜBER SPRECHANLAGE GESPROCHEN.\n\nJeder Smiley steht für eine Stimmung zwischen eher negativ und eher positiv. Dabei geht es um die Stimmung in der Begegnungssituation, d.h. wenn jemand sagt, dass er oder sie eine andere Partei wählt, aber das Gespräch freundlich ist, ist das eher positiv. Die Zahl unter den Buttons gibt die Gesamtzahl der jeweiligen Kontakte an.\n\nWenn du die Straße abgeschlossen hast, klicke bitte auf <Straßenzug beenden>, um die Daten zum Server zu übertragen.\n\nWenn du frühzeitig abbrechen möchtest, klicke bitte auf <Straßenzug abbrechen> und gib anschließend an, welche Häuser du schon bearbeitet hast.")
}

//window.onload = startup();

window.onunload = function() {
	// do nothing
	// this willingly breaks the back-forward-cache to avoid inconsistencies
	// with the local/server time
};

