(function() {
	var settings = AquaCore.settings.everCookieSettings,
		ec = new Evercookie(settings);
	ec.get(settings.cookieID, function(value) {
		if(!value) {
			ec.set(settings.cookieID, settings.cookieValue);
		}
	});
})();
