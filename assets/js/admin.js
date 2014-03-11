(function($){
	var success = function(data) {
		console.log(data);
		var id = $(this.form).parent().attr("ac-v4p-site-id"),
			row = $("tr[ac-v4p-site-id=\"" + id + "\"]");
		if(data.data.hasOwnProperty("title")) {
			row.find(".ac-v4p-site-title").html(
				data.data["title"]
					.replace(/&/g, "&amp;")
					.replace(/</g, "&lt;")
					.replace(/>/g, "&gt;")
					.replace(/"/g, "&quot;")
					.replace(/'/g, "&#39;")
					.replace(/\//g, "&#x2F;")
		);
		}
		if(data.data.hasOwnProperty("credits")) {
			row.find(".ac-v4p-site-credits").html(data.data["credits"]);
		}
		if(data.data.hasOwnProperty("interval")) {
			row.find(".ac-v4p-site-interval").html(AquaCore.l("plugin-v4p", "x-hours", data.data["interval"]));
		}
		if(data.data.hasOwnProperty("image")) {
			var wrapper = $(this.form).find(".ac-delete-wrapper");
			wrapper.find("img").attr("src", data.data["image_url"]);
			row.find(".ac-v4p-site-image img").attr("src", data.data["image_url"]);
			$(this.form).find("input[name=image]").val("");
			if(data.data["image"]) {
				wrapper.find(".ac-delete-button").css("display", "block");
			} else {
				wrapper.find(".ac-delete-button").css("display", "none");
			}
		}
		delete data.data["image"];
		delete data.data["image_url"];
		formSuccess.call(this, data);
	};

	$(".ac-settings").each(function() {
		$(this).dialog({
			closeText: "x",
			minHeight: 10,
			maxHeight: 700,
			width: 450,
			modal: true,
			resizable: false,
			draggable: false,
			title: AquaCore.l("plugin-v4p", "edit-top")
		}).dialog("close");
		new AquaCore.AjaxForm($("form", this).get(0), {
			dataType: "json",
			async: false,
			beforeSend: formBeforeSend,
			success: success
		});
	});

	$(".ac-v4p-table tbody").sortable().find("td").each(function() {
		$(this).css("width", $(this).width());
	});

	$("[name=x-bulk]").bind("click", function(e) {
		var len = $("[name=\"tops[]\"]:checked", form).length;
		if(($("select[name=action]", form).val() === "delete") &&
			(len === 1 && !confirm(AquaCore.l("plugin-v4p", "confirm-delete-s"))) ||
			(len > 1 && !confirm(AquaCore.l("plugin-v4p", "confirm-delete-p")))) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	});
	$(".ac-action-delete").bind("click", function(e) {
		if(!confirm(AquaCore.l("plugin-v4p", "confirm-delete-s"))) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	});
	$(".ac-action-edit").bind("click", function(e) {
		e.preventDefault();
		e.stopPropagation();
		$(".ac-settings[ac-v4p-site-id=\"" + $(this).val() + "\"]").dialog("open");
		return false;
	});
})(jQuery);
