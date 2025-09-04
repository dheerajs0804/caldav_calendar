if (window.rcmail) {
	rcmail
			.addEventListener(
					'init',
					function(evt) {
						// Register commands
						rcmail.register_command(
								'plugin.textsense.do_textsense', function() {
									do_textsense();
								});
						rcmail.register_command(
								'plugin.textsense.do_named_entity_recognition',
								function() {
									do_named_entity_recognition();
								});
						rcmail.register_command(
								'plugin.textsense.do_sentiment_analysis',
								function() {
									do_sentiment_analysis();
								});
						rcmail.register_command(
								'plugin.textsense.do_sensitivity', function() {
									do_sensitivity();
								});

						// Enable commands iff user selected mail
						// Will support only single mail selection
						// Will not enable buttons on multiple mail selection
						rcmail.message_list
								&& rcmail.message_list
										.addEventListener(
												'select',
												function(list) {
													var selected = list
															.get_selection();
													if (selected.length == 1) {
														rcmail
																.enable_command(
																		'plugin.textsense.do_textsense',
																		true);
														rcmail
																.enable_command(
																		'plugin.textsense.do_named_entity_recognition',
																		true);
														rcmail
																.enable_command(
																		'plugin.textsense.do_sentiment_analysis',
																		true);
														rcmail
																.enable_command(
																		'plugin.textsense.do_sensitivity',
																		true);
													}
												});

						// Disable commands when selected mail folder
						rcmail
								.addEventListener(
										'selectfolder',
										function(evt) {
											rcmail
													.enable_command(
															'plugin.textsense.do_textsense',
															false);
											rcmail
													.enable_command(
															'plugin.textsense.do_named_entity_recognition',
															false);
											rcmail
													.enable_command(
															'plugin.textsense.do_sentiment_analysis',
															false);
											rcmail
													.enable_command(
															'plugin.textsense.do_sensitivity',
															false);
										});

						// Add event listner and register callback functions
						rcmail
								.addEventListener(
										'plugin.textsense.show_named_entity_recognition_result',
										show_named_entity_recognition_result);
						rcmail
								.addEventListener(
										'plugin.textsense.show_sentiment_analysis_result',
										show_sentiment_analysis_result);
						rcmail.addEventListener(
								'plugin.textsense.show_sensitivity_result',
								show_sensitivity_result);
					});
}

function do_textsense() {
	// DO NOTHING
}

// Call registred named_entity_recognition action
function do_named_entity_recognition() {
	// Get uid of selected email
	var uid = rcmail.get_single_uid();
	var data = {
		_uid : uid,
		_mbox : rcmail.env.mailbox
	};
	var lock = rcmail.set_busy(true, 'loading');
	rcmail.http_post('plugin.textsense.named_entity_recognition', data, lock);
}

// Call registred sentiment_analysis action
function do_sentiment_analysis() {
	var uid = rcmail.get_single_uid();
	var data = {
		_uid : uid,
		_mbox : rcmail.env.mailbox
	};
	var lock = rcmail.set_busy(true, 'loading');
	rcmail.http_post('plugin.textsense.sentiment_analysis', data, lock);
}

// Call registred sensitivity action
function do_sensitivity() {
	var uid = rcmail.get_single_uid();
	var data = {
		_uid : uid,
		_mbox : rcmail.env.mailbox
	};
	var lock = rcmail.set_busy(true, 'loading');
	rcmail.http_post('plugin.textsense.sensitivity', data, lock);
}

// Call back action to show entity_recognition_result
function show_named_entity_recognition_result(result) {
	rcmail.show_popup_dialog(result, 'Named Entity Recognition', null, {
		width : 900,
		modal : false
	});
}

// Call back action to show sentiment_analysis_result
function show_sentiment_analysis_result(result) {
	rcmail.show_popup_dialog(result, 'Sentiment Analysis', null, {
		width : 900,
		modal : false
	});
}

// Call back action to show sensitivity_result
function show_sensitivity_result(result) {
	rcmail.show_popup_dialog(result, 'Sensitivity Analysis', null, {
		width : 900,
		modal : false
	});
}
