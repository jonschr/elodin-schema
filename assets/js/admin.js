(function ($) {
	function initializeEditors() {
		if (
			typeof wp === "undefined" ||
			!wp.codeEditor ||
			typeof elodinSchemaConfig === "undefined"
		) {
			return;
		}

		window.elodinSchemaEditors = window.elodinSchemaEditors || {};

		if (
			elodinSchemaConfig.editorSettings &&
			document.getElementById("elodin_schema_script")
		) {
			window.elodinSchemaEditors.script = wp.codeEditor.initialize(
				"elodin_schema_script",
				elodinSchemaConfig.editorSettings
			);
		}

		if (
			elodinSchemaConfig.previewSettings &&
			document.getElementById("elodin_schema_preview_output")
		) {
			window.elodinSchemaEditors.preview = wp.codeEditor.initialize(
				"elodin_schema_preview_output",
				elodinSchemaConfig.previewSettings
			);
		}
	}

	function getScriptValue($script) {
		if (
			window.elodinSchemaEditors &&
			window.elodinSchemaEditors.script &&
			window.elodinSchemaEditors.script.codemirror
		) {
			return window.elodinSchemaEditors.script.codemirror.getValue();
		}

		return $script.val();
	}

	function setScriptValue($script, value) {
		$script.val(value).trigger("change");

		if (
			window.elodinSchemaEditors &&
			window.elodinSchemaEditors.script &&
			window.elodinSchemaEditors.script.codemirror
		) {
			window.elodinSchemaEditors.script.codemirror.setValue(value);
		}
	}

	function setPreviewValue($previewOutput, value) {
		$previewOutput.val(value);

		if (
			window.elodinSchemaEditors &&
			window.elodinSchemaEditors.preview &&
			window.elodinSchemaEditors.preview.codemirror
		) {
			window.elodinSchemaEditors.preview.codemirror.setValue(value);
		}
	}

	function insertPlaceholder($script, value) {
		if (!value) {
			return;
		}

		if (
			window.elodinSchemaEditors &&
			window.elodinSchemaEditors.script &&
			window.elodinSchemaEditors.script.codemirror
		) {
			window.elodinSchemaEditors.script.codemirror.replaceSelection(value, "around");
			window.elodinSchemaEditors.script.codemirror.focus();
			return;
		}

		var textarea = $script.get(0);
		if (!textarea) {
			return;
		}

		var start =
			typeof textarea.selectionStart === "number"
				? textarea.selectionStart
				: textarea.value.length;
		var end =
			typeof textarea.selectionEnd === "number"
				? textarea.selectionEnd
				: textarea.value.length;
		var nextValue =
			textarea.value.slice(0, start) + value + textarea.value.slice(end);

		$script.val(nextValue).trigger("change");
		textarea.focus();
		textarea.selectionStart = start + value.length;
		textarea.selectionEnd = start + value.length;
	}

	$(function () {
		var $template = $("#elodin_schema_template");
		var $script = $("#elodin_schema_script");
		var $placeholder = $("#elodin_schema_placeholder_picker");
		var $previewUrl = $("#elodin_schema_preview_url");
		var $previewButton = $("#elodin_schema_run_preview");
		var $previewOutput = $("#elodin_schema_preview_output");
		var $previewStatus = $("#elodin_schema_preview_status");
		var previewPostId = $("#post_ID").val();

		initializeEditors();

		$("#elodin_schema_apply_template").on("click", function (e) {
			var selected = $template.val();
			if (!selected) {
				return;
			}

			e.preventDefault();

			if (
				getScriptValue($script).trim() !== "" &&
				!window.confirm(
					"Replace the current JSON-LD with the selected starter template?"
				)
			) {
				return;
			}

			setScriptValue($script, selected);
		});

		$placeholder.on("change", function () {
			var value = $(this).val();
			if (!value) {
				return;
			}

			insertPlaceholder($script, value);
			$(this).val("");
		});

		function runPreview(e) {
			if (e) {
				e.preventDefault();
			}

			$previewStatus.text("Loading preview...");

			$.post(elodinSchemaConfig.ajaxUrl, {
				action: "elodin_schema_preview",
				nonce: elodinSchemaConfig.previewNonce,
				post_id: previewPostId,
				preview_url: $previewUrl.val(),
				script: getScriptValue($script),
			})
				.done(function (response) {
					if (!response || !response.success) {
						var message =
							response && response.data && response.data.message
								? response.data.message
								: "Preview could not be generated.";
						$previewStatus.text(message);
						return;
					}

					setPreviewValue($previewOutput, response.data.markup);
					$previewStatus.text(response.data.message || "Preview updated.");
				})
				.fail(function () {
					$previewStatus.text("Preview request failed.");
				});
		}

		$previewButton.on("click", runPreview);
		$previewUrl.on("change", runPreview);
		$previewUrl.on("paste", function () {
			window.setTimeout(runPreview, 50);
		});
		$previewUrl.on("keydown", function (e) {
			if (e.key === "Enter") {
				runPreview(e);
			}
		});

		$(document).on("click", ".elodin-schema-list-switch", function (e) {
			var $button = $(this);
			var $state = $button.find(".elodin-schema-list-toggle-state");
			var wasEnabled = $button.hasClass("is-enabled");
			var enabled = wasEnabled ? "0" : "1";
			var postId = $button.data("post-id");

			e.preventDefault();

			if (!postId || typeof elodinSchemaConfig === "undefined") {
				return;
			}

			$button.addClass("is-saving");
			$button.prop("disabled", true);

			$.post(elodinSchemaConfig.ajaxUrl, {
				action: "elodin_schema_toggle_enabled",
				nonce: elodinSchemaConfig.toggleNonce,
				post_id: postId,
				enabled: enabled,
			})
				.done(function (response) {
					if (!response || !response.success) {
						return;
					}

					var isEnabled = response.data && response.data.enabled === "1";
					$button.toggleClass("is-enabled", isEnabled);
					$button.attr("aria-checked", isEnabled ? "true" : "false");
					$state.text(isEnabled ? "On" : "Off");
				})
				.always(function () {
					$button.prop("disabled", false);
					$button.removeClass("is-saving");
				});
		});
	});
})(jQuery);
