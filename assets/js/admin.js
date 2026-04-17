(function ($) {
	function getEditorInstance($textarea) {
		return $textarea.data("elodinSchemaEditor") || null;
	}

	function initializeCodeEditor($textarea, settings) {
		var element = $textarea.get(0);
		var editor;

		if (
			!element ||
			typeof wp === "undefined" ||
			!wp.codeEditor ||
			!settings ||
			getEditorInstance($textarea)
		) {
			return;
		}

		editor = wp.codeEditor.initialize(element.id, settings);
		$textarea.data("elodinSchemaEditor", editor);

		if (editor && editor.codemirror) {
			editor.codemirror.on("change", function () {
				editor.codemirror.save();
			});

			window.requestAnimationFrame(function () {
				editor.codemirror.refresh();
			});

			window.setTimeout(function () {
				editor.codemirror.refresh();
			}, 0);
		}
	}

	function initializeEditors($scope) {
		if (typeof elodinSchemaConfig === "undefined") {
			return;
		}

		if (elodinSchemaConfig.editorSettings) {
			$scope.find(".elodin-schema-json-editor").each(function () {
				initializeCodeEditor($(this), elodinSchemaConfig.editorSettings);
			});

			if ($scope.is("#elodin_schema_script")) {
				initializeCodeEditor($scope, elodinSchemaConfig.editorSettings);
			} else {
				$scope.find("#elodin_schema_script").each(function () {
					initializeCodeEditor($(this), elodinSchemaConfig.editorSettings);
				});
			}
		}

		if (elodinSchemaConfig.previewSettings) {
			if ($scope.is("#elodin_schema_preview_output")) {
				initializeCodeEditor($scope, elodinSchemaConfig.previewSettings);
			} else {
				$scope.find("#elodin_schema_preview_output").each(function () {
					initializeCodeEditor($(this), elodinSchemaConfig.previewSettings);
				});
			}
		}
	}

	function syncEditorsToTextareas($scope) {
		$scope.find("textarea").each(function () {
			var editor = getEditorInstance($(this));

			if (editor && editor.codemirror) {
				editor.codemirror.save();
			}
		});
	}

	function refreshEditors($scope) {
		$scope.find("textarea").each(function () {
			var editor = getEditorInstance($(this));

			if (editor && editor.codemirror) {
				editor.codemirror.refresh();
			}
		});
	}

	function initializeAndRefresh($scope) {
		initializeEditors($scope);
		refreshEditors($scope);
	}

	function getTextareaValue($textarea) {
		var editor = getEditorInstance($textarea);

		if (editor && editor.codemirror) {
			return editor.codemirror.getValue();
		}

		return $textarea.val();
	}

	function setTextareaValue($textarea, value) {
		var editor = getEditorInstance($textarea);

		$textarea.val(value).trigger("change");

		if (editor && editor.codemirror) {
			editor.codemirror.setValue(value);
		}
	}

	function insertPlaceholder($textarea, value) {
		var editor;
		var textarea;
		var start;
		var end;
		var nextValue;

		if (!value) {
			return;
		}

		editor = getEditorInstance($textarea);
		if (editor && editor.codemirror) {
			editor.codemirror.replaceSelection(value, "around");
			editor.codemirror.focus();
			return;
		}

		textarea = $textarea.get(0);
		if (!textarea) {
			return;
		}

		start =
			typeof textarea.selectionStart === "number"
				? textarea.selectionStart
				: textarea.value.length;
		end =
			typeof textarea.selectionEnd === "number"
				? textarea.selectionEnd
				: textarea.value.length;
		nextValue =
			textarea.value.slice(0, start) + value + textarea.value.slice(end);

		$textarea.val(nextValue).trigger("change");
		textarea.focus();
		textarea.selectionStart = start + value.length;
		textarea.selectionEnd = start + value.length;
	}

	function appendLocalEntry() {
		var $editor = $("#elodin-schema-local-editor");
		var $list = $editor.find(".elodin-schema-local-entry-list");
		var template = $("#tmpl-elodin-schema-local-entry").html();
		var nextIndex;
		var html;
		var $entry;

		if (!$editor.length || !template) {
			return;
		}

		nextIndex = parseInt($editor.attr("data-next-index"), 10) || 0;
		html = template.replace(/__INDEX__/g, String(nextIndex));
		$entry = $(html);

		$list.append($entry);
		$editor.attr("data-next-index", String(nextIndex + 1));
		initializeAndRefresh($entry);
	}

	function watchForLocalEditorMount() {
		var root = document.getElementById("poststuff") || document.body;
		var observer;

		if (!root || typeof MutationObserver === "undefined") {
			return;
		}

		observer = new MutationObserver(function (mutations) {
			mutations.forEach(function (mutation) {
				$(mutation.addedNodes).each(function () {
					var $node = $(this);

					if (this.nodeType !== 1) {
						return;
					}

					if (
						$node.is(".elodin-schema-local-entry, #elodin-schema-local-editor") ||
						$node.find(".elodin-schema-local-entry, #elodin-schema-local-editor")
							.length
					) {
						initializeAndRefresh($node);
					}
				});
			});
		});

		observer.observe(root, {
			childList: true,
			subtree: true,
		});
	}

	$(function () {
		var $document = $(document);
		var $script = $("#elodin_schema_script");
		var $template = $("#elodin_schema_template");
		var $placeholder = $("#elodin_schema_placeholder_picker");
		var $previewUrl = $("#elodin_schema_preview_url");
		var $previewButton = $("#elodin_schema_run_preview");
		var $previewOutput = $("#elodin_schema_preview_output");
		var $previewStatus = $("#elodin_schema_preview_status");
		var previewPostId = $("#post_ID").val();

		initializeAndRefresh($(document.body));
		watchForLocalEditorMount();

		$(window).on("load", function () {
			initializeAndRefresh($(document.body));
		});

		$document.on("postbox-toggled", function (event, postbox) {
			initializeAndRefresh($(postbox));
		});

		$("#elodin_schema_apply_template").on("click", function (e) {
			var selected = $template.val();

			if (!selected) {
				return;
			}

			e.preventDefault();

			if (
				getTextareaValue($script).trim() !== "" &&
				!window.confirm(
					"Replace the current JSON-LD with the selected starter template?"
				)
			) {
				return;
			}

			setTextareaValue($script, selected);
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

			if (
				typeof elodinSchemaConfig === "undefined" ||
				!$previewButton.length ||
				!$previewStatus.length
			) {
				return;
			}

			$previewStatus.text("Loading preview...");

			$.post(elodinSchemaConfig.ajaxUrl, {
				action: "elodin_schema_preview",
				nonce: elodinSchemaConfig.previewNonce,
				post_id: previewPostId,
				preview_url: $previewUrl.val(),
				script: getTextareaValue($script),
			})
				.done(function (response) {
					var message;

					if (!response || !response.success) {
						message =
							response && response.data && response.data.message
								? response.data.message
								: "Preview could not be generated.";
						$previewStatus.text(message);
						return;
					}

					setTextareaValue($previewOutput, response.data.markup);
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

		$("#post").on("submit", function () {
			syncEditorsToTextareas($(this));
		});

		$document.on("click", "#elodin-schema-add-local-entry", function (e) {
			e.preventDefault();
			appendLocalEntry();
		});

		$document.on("click", ".elodin-schema-remove-local-entry", function (e) {
			var $entry = $(this).closest(".elodin-schema-local-entry");
			var $textarea = $entry.find(".elodin-schema-json-editor").first();
			var hasContent =
				getTextareaValue($textarea).trim() !== "" ||
				$entry.find('input[type="text"]').filter(function () {
					return $(this).val().trim() !== "";
				}).length > 0 ||
				$entry.find("select").filter(function () {
					return $(this).val() && $(this).val() !== "0";
				}).length > 0;

			e.preventDefault();

			if (
				hasContent &&
				!window.confirm("Remove this schema block from the post editor?")
			) {
				return;
			}

			$entry.remove();
		});

		$document.on("click", ".elodin-schema-local-apply-template", function (e) {
			var $entry = $(this).closest(".elodin-schema-local-entry");
			var $localTemplate = $entry.find(".elodin-schema-local-template");
			var $textarea = $entry.find(".elodin-schema-json-editor").first();
			var selected = $localTemplate.val();

			if (!selected) {
				return;
			}

			e.preventDefault();

			if (
				getTextareaValue($textarea).trim() !== "" &&
				!window.confirm(
					"Replace the current JSON-LD with the selected starter template?"
				)
			) {
				return;
			}

			setTextareaValue($textarea, selected);
		});

		$document.on(
			"change",
			".elodin-schema-local-placeholder-picker",
			function () {
				var $picker = $(this);
				var value = $picker.val();
				var $entry = $picker.closest(".elodin-schema-local-entry");
				var $textarea = $entry.find(".elodin-schema-json-editor").first();

				if (!value) {
					return;
				}

				insertPlaceholder($textarea, value);
				$picker.val("");
			}
		);

		$document.on("click", ".elodin-schema-list-switch", function (e) {
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
					var isEnabled;

					if (!response || !response.success) {
						return;
					}

					isEnabled = response.data && response.data.enabled === "1";
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
