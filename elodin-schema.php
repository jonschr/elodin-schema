<?php
/*
	Plugin Name: Elodin Schema
	Plugin URI: https://elod.in
	Description: Manage reusable schema snippets and output them across the site.
	Version: 0.1
	Author: Jon Schroeder
	Author URI: https://elod.in

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.
*/

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Sorry, you are not allowed to access this page directly.' );
}

define( 'ELODIN_SCHEMA', dirname( __FILE__ ) );
define( 'ELODIN_SCHEMA_VERSION', '0.1' );
define( 'ELODIN_SCHEMA_POST_TYPE', 'elodin_schema' );

define( 'ELODIN_SCHEMA_META_NOTES', '_elodin_schema_notes' );
define( 'ELODIN_SCHEMA_META_SCRIPT', '_elodin_schema_script' );
define( 'ELODIN_SCHEMA_META_TARGET', '_elodin_schema_target' );
define( 'ELODIN_SCHEMA_META_OUTPUT', '_elodin_schema_output' );
define( 'ELODIN_SCHEMA_META_ENABLED', '_elodin_schema_enabled' );
define( 'ELODIN_SCHEMA_META_PRIORITY', '_elodin_schema_priority' );
define( 'ELODIN_SCHEMA_META_TYPE', '_elodin_schema_type' );
define( 'ELODIN_SCHEMA_META_VALIDATION_ERROR', '_elodin_schema_validation_error' );

add_action( 'init', 'elodin_schema_register_post_type' );
add_action( 'add_meta_boxes', 'elodin_schema_register_meta_boxes' );
add_action( 'admin_enqueue_scripts', 'elodin_schema_enqueue_admin_assets' );
add_action( 'edit_form_after_title', 'elodin_schema_render_editor_section' );
add_action( 'save_post_' . ELODIN_SCHEMA_POST_TYPE, 'elodin_schema_save_post' );
add_action( 'wp_head', 'elodin_schema_output_schema', 20 );
add_action( 'admin_notices', 'elodin_schema_render_admin_notice' );
add_action( 'wp_ajax_elodin_schema_preview', 'elodin_schema_ajax_preview' );

add_filter( 'enter_title_here', 'elodin_schema_title_placeholder', 10, 2 );
add_filter( 'parent_file', 'elodin_schema_highlight_settings_menu' );
add_filter( 'submenu_file', 'elodin_schema_highlight_settings_submenu' );
add_filter( 'manage_edit-' . ELODIN_SCHEMA_POST_TYPE . '_columns', 'elodin_schema_edit_columns' );
add_action(
	'manage_' . ELODIN_SCHEMA_POST_TYPE . '_posts_custom_column',
	'elodin_schema_render_custom_column',
	10,
	2
);

function elodin_schema_register_post_type() {
	$labels = array(
		'name'                  => __( 'Schema', 'elodin-schema' ),
		'singular_name'         => __( 'Schema', 'elodin-schema' ),
		'menu_name'             => __( 'Schema', 'elodin-schema' ),
		'name_admin_bar'        => __( 'Schema', 'elodin-schema' ),
		'add_new'               => __( 'Add Schema', 'elodin-schema' ),
		'add_new_item'          => __( 'Add New Schema', 'elodin-schema' ),
		'edit_item'             => __( 'Edit Schema', 'elodin-schema' ),
		'new_item'              => __( 'New Schema', 'elodin-schema' ),
		'view_item'             => __( 'View Schema', 'elodin-schema' ),
		'search_items'          => __( 'Search Schema', 'elodin-schema' ),
		'not_found'             => __( 'No schema found.', 'elodin-schema' ),
		'not_found_in_trash'    => __( 'No schema found in Trash.', 'elodin-schema' ),
		'all_items'             => __( 'Schema', 'elodin-schema' ),
	);

	$capabilities = array(
		'edit_post'              => 'manage_options',
		'read_post'              => 'manage_options',
		'delete_post'            => 'manage_options',
		'edit_posts'             => 'manage_options',
		'edit_others_posts'      => 'manage_options',
		'publish_posts'          => 'manage_options',
		'read_private_posts'     => 'manage_options',
		'delete_posts'           => 'manage_options',
		'delete_private_posts'   => 'manage_options',
		'delete_published_posts' => 'manage_options',
		'delete_others_posts'    => 'manage_options',
		'edit_private_posts'     => 'manage_options',
		'edit_published_posts'   => 'manage_options',
		'create_posts'           => 'manage_options',
	);

	register_post_type(
		ELODIN_SCHEMA_POST_TYPE,
		array(
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => 'options-general.php',
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'show_in_rest'        => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'menu_icon'           => 'dashicons-editor-code',
			'supports'            => array( 'title' ),
			'capabilities'        => $capabilities,
			'map_meta_cap'        => false,
		)
	);
}

function elodin_schema_register_meta_boxes() {
	add_meta_box(
		'elodin-schema-template',
		__( 'Starter Template', 'elodin-schema' ),
		'elodin_schema_render_template_meta_box',
		ELODIN_SCHEMA_POST_TYPE,
		'side',
		'default'
	);

	add_meta_box(
		'elodin-schema-settings',
		__( 'Targeting & Output', 'elodin-schema' ),
		'elodin_schema_render_settings_meta_box',
		ELODIN_SCHEMA_POST_TYPE,
		'normal',
		'default'
	);

	add_meta_box(
		'elodin-schema-notes',
		__( 'Notes', 'elodin-schema' ),
		'elodin_schema_render_notes_meta_box',
		ELODIN_SCHEMA_POST_TYPE,
		'normal',
		'default'
	);
}

function elodin_schema_enqueue_admin_assets() {
	$screen = get_current_screen();

	if ( ! $screen || ELODIN_SCHEMA_POST_TYPE !== $screen->post_type ) {
		return;
	}

	wp_enqueue_script( 'jquery' );
	wp_add_inline_style(
		'common',
		'#elodin-schema-editor-section{margin:20px 0 24px;}
		#elodin-schema-editor-section .elodin-schema-workspace{display:flex;gap:20px;align-items:flex-start;}
		#elodin-schema-editor-section .elodin-schema-panel{flex:1 1 0;min-width:0;display:flex;flex-direction:column;background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:16px;box-shadow:0 1px 1px rgba(0,0,0,.04);}
		#elodin-schema-editor-section .elodin-schema-panel-title{margin:0 0 12px;font-size:14px;line-height:1.4;padding-bottom:10px;border-bottom:1px solid #f0f0f1;}
		#elodin-schema-editor-section .elodin-schema-panel-body{display:flex;flex-direction:column;flex:1;min-height:0;}
		#elodin-schema-editor-section .elodin-schema-editor-toolbar{text-align:right;margin-bottom:8px;}
		#elodin-schema-editor-section .elodin-schema-editor-wrap{display:flex;flex-direction:column;flex:1;min-height:0;}
		#elodin-schema-editor-section .elodin-schema-editor-note{margin-top:8px;}
		#elodin-schema-editor-section .CodeMirror,
		#elodin-schema-editor-section .CodeMirror-scroll{min-height:540px;}
		#elodin-schema-editor-section .elodin-schema-editor-wrap .CodeMirror,
		#elodin-schema-editor-section .elodin-schema-preview-output-wrap .CodeMirror{flex:1;height:auto;}
		#elodin-schema-editor-section textarea.code{min-height:540px;}
		#elodin_schema_script,
		#elodin_schema_preview_output{flex:1;min-height:540px;}
		#elodin-schema-editor-section .elodin-schema-preview-controls{display:flex;gap:8px;align-items:center;margin-bottom:12px;}
		#elodin_schema_preview_url{flex:1 1 auto;}
		#elodin_schema_run_preview{flex:0 0 auto;}
		#elodin-schema-editor-section .elodin-schema-preview-meta{margin-bottom:12px;}
		#elodin-schema-editor-section .elodin-schema-preview-output-wrap{display:flex;flex-direction:column;flex:1;min-height:0;}
		#elodin-schema-editor-section .elodin-schema-preview-output{margin-bottom:8px;}
		#elodin-schema-settings-panel .elodin-schema-setting{padding:12px 0;border-top:1px solid #f0f0f1;}
		#elodin-schema-settings-panel .elodin-schema-setting:first-child{padding-top:0;border-top:0;}
		#elodin-schema-settings-panel .elodin-schema-setting-label{display:block;margin-bottom:6px;font-weight:600;}
		#elodin-schema-settings-panel .elodin-schema-setting .description{margin-top:6px;}
		#elodin-schema-settings-panel .elodin-schema-inline-toggle{display:flex;align-items:center;gap:8px;}
		@media (max-width: 1200px){
			#elodin-schema-editor-section .elodin-schema-workspace{display:block;}
			#elodin-schema-editor-section .elodin-schema-panel + .elodin-schema-panel{margin-top:20px;}
			#elodin-schema-editor-section .elodin-schema-preview-controls{display:block;}
			#elodin_schema_run_preview{margin-top:8px;}
		}'
	);

	$settings = wp_enqueue_code_editor(
		array(
			'type'       => 'application/json',
			'codemirror' => array(
				'lineWrapping' => false,
			),
		)
	);

	if ( false !== $settings ) {
		$preview_settings = $settings;
		if ( ! isset( $preview_settings['codemirror'] ) || ! is_array( $preview_settings['codemirror'] ) ) {
			$preview_settings['codemirror'] = array();
		}
		$preview_settings['codemirror']['readOnly'] = 'nocursor';

		wp_add_inline_script(
			'code-editor',
			sprintf(
				'jQuery(function() { if ( typeof wp !== "undefined" && wp.codeEditor ) { window.elodinSchemaEditors = window.elodinSchemaEditors || {}; window.elodinSchemaEditors.script = wp.codeEditor.initialize( "elodin_schema_script", %1$s ); window.elodinSchemaEditors.preview = wp.codeEditor.initialize( "elodin_schema_preview_output", %2$s ); } });',
				wp_json_encode( $settings ),
				wp_json_encode( $preview_settings )
			)
		);
	}

		wp_add_inline_script(
			'jquery',
			'jQuery(function($){
				var $template = $("#elodin_schema_template");
				var $script = $("#elodin_schema_script");
				var $placeholder = $("#elodin_schema_placeholder_picker");
				var $previewUrl = $("#elodin_schema_preview_url");
				var $previewButton = $("#elodin_schema_run_preview");
				var $previewOutput = $("#elodin_schema_preview_output");
				var $previewStatus = $("#elodin_schema_preview_status");
				var previewNonce = $("#elodin_schema_preview_nonce").val();
				var previewPostId = $("#post_ID").val();

				function insertPlaceholder(value) {
					if (!value) {
						return;
					}

					if (window.elodinSchemaEditors && window.elodinSchemaEditors.script && window.elodinSchemaEditors.script.codemirror) {
						window.elodinSchemaEditors.script.codemirror.replaceSelection(value, "around");
						window.elodinSchemaEditors.script.codemirror.focus();
						return;
					}

					var textarea = $script.get(0);
					if (!textarea) {
						return;
					}

					var start = typeof textarea.selectionStart === "number" ? textarea.selectionStart : textarea.value.length;
					var end = typeof textarea.selectionEnd === "number" ? textarea.selectionEnd : textarea.value.length;
					var nextValue = textarea.value.slice(0, start) + value + textarea.value.slice(end);

					$script.val(nextValue).trigger("change");
					textarea.focus();
					textarea.selectionStart = start + value.length;
					textarea.selectionEnd = start + value.length;
				}

			$("#elodin_schema_apply_template").on("click", function(e){
				var selected = $template.val();
				if (!selected) {
					return;
				}
				e.preventDefault();
				if ($script.val().trim() !== "" && !window.confirm("Replace the current JSON-LD with the selected starter template?")) {
					return;
				}
				$script.val(selected).trigger("change");
				if (window.elodinSchemaEditors && window.elodinSchemaEditors.script && window.elodinSchemaEditors.script.codemirror) {
					window.elodinSchemaEditors.script.codemirror.setValue(selected);
				}
			});

				$placeholder.on("change", function(){
					var value = $(this).val();
					if (!value) {
						return;
					}
					insertPlaceholder(value);
					$(this).val("");
				});

			function runPreview(e) {
				if (e) {
					e.preventDefault();
				}
				$previewStatus.text("Loading preview...");
				var scriptValue = $script.val();
				if (window.elodinSchemaEditors && window.elodinSchemaEditors.script && window.elodinSchemaEditors.script.codemirror) {
					scriptValue = window.elodinSchemaEditors.script.codemirror.getValue();
				}

				$.post(ajaxurl, {
					action: "elodin_schema_preview",
					nonce: previewNonce,
					post_id: previewPostId,
					preview_url: $previewUrl.val(),
					script: scriptValue
				}).done(function(response){
					if (!response || !response.success) {
						var message = response && response.data && response.data.message ? response.data.message : "Preview could not be generated.";
						$previewStatus.text(message);
						return;
					}

					$previewOutput.val(response.data.markup);
					if (window.elodinSchemaEditors && window.elodinSchemaEditors.preview && window.elodinSchemaEditors.preview.codemirror) {
						window.elodinSchemaEditors.preview.codemirror.setValue(response.data.markup);
					}
					$previewStatus.text(response.data.message || "Preview updated.");
				}).fail(function(){
					$previewStatus.text("Preview request failed.");
				});
			}

			$previewButton.on("click", runPreview);
			$previewUrl.on("change", runPreview);
			$previewUrl.on("paste", function(){
				window.setTimeout(runPreview, 50);
			});
			$previewUrl.on("keydown", function(e){
				if (e.key === "Enter") {
					runPreview(e);
				}
			});
		});',
		'after'
	);
}

function elodin_schema_render_editor_section( $post ) {
	if ( ELODIN_SCHEMA_POST_TYPE !== $post->post_type ) {
		return;
	}

	echo '<div id="elodin-schema-editor-section">';
	echo '<div class="elodin-schema-workspace">';
	echo '<div class="elodin-schema-panel">';
	echo '<h2 class="elodin-schema-panel-title">Schema JSON-LD</h2>';
	echo '<div class="elodin-schema-panel-body">';
	elodin_schema_render_script_meta_box( $post );
	echo '</div>';
	echo '</div>';
	echo '<div class="elodin-schema-panel">';
	echo '<h2 class="elodin-schema-panel-title">Preview &amp; Placeholders</h2>';
	echo '<div class="elodin-schema-panel-body">';
	elodin_schema_render_preview_meta_box( $post );
	echo '</div>';
	echo '</div>';
	echo '</div>';
	echo '</div>';
}

function elodin_schema_render_script_meta_box( $post ) {
	wp_nonce_field( 'elodin_schema_save', 'elodin_schema_nonce' );

	$script              = get_post_meta( $post->ID, ELODIN_SCHEMA_META_SCRIPT, true );
	$validation_message  = get_post_meta( $post->ID, ELODIN_SCHEMA_META_VALIDATION_ERROR, true );
	$placeholders        = elodin_schema_get_placeholder_definitions();
	?>
	<?php if ( ! empty( $validation_message ) ) : ?>
		<div class="notice notice-error inline">
			<p><strong>JSON-LD validation error:</strong> <?php echo esc_html( $validation_message ); ?></p>
			<p>The saved entry will stay editable, but it will not output on the front end until the JSON-LD is valid again.</p>
		</div>
	<?php endif; ?>

		<div class="elodin-schema-editor-toolbar">
			<select id="elodin_schema_placeholder_picker" class="regular-text">
				<option value="">Choose a placeholder to insert</option>
				<?php foreach ( $placeholders as $group_label => $group_placeholders ) : ?>
					<optgroup label="<?php echo esc_attr( $group_label ); ?>">
					<?php foreach ( $group_placeholders as $placeholder => $description ) : ?>
						<option value="<?php echo esc_attr( $placeholder ); ?>">
							<?php echo esc_html( $placeholder . ' — ' . $description ); ?>
						</option>
					<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="elodin-schema-editor-wrap">
		<textarea
			name="elodin_schema_script"
			id="elodin_schema_script"
			class="widefat code"
			rows="30"
			spellcheck="false"
			wrap="off"
		><?php echo esc_textarea( $script ); ?></textarea>
	</div>
	<p class="elodin-schema-editor-note">Paste JSON-LD only. The plugin wraps valid JSON-LD in <code>&lt;script type="application/ld+json"&gt;</code> during output.</p>
	<?php
}

function elodin_schema_render_template_meta_box() {
	$starter_templates = elodin_schema_get_starter_templates();
	?>
	<p>Select a starter template and insert it into the JSON-LD editor below.</p>
	<p>
		<label for="elodin_schema_template"><strong>Starter Template</strong></label>
		<select id="elodin_schema_template" class="regular-text">
			<option value="">Select a starter template</option>
			<?php foreach ( $starter_templates as $template ) : ?>
				<option value="<?php echo esc_attr( $template['json'] ); ?>">
					<?php echo esc_html( $template['label'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>
	<p>
		<button type="button" class="button" id="elodin_schema_apply_template">Insert Template</button>
	</p>
	<?php
}

function elodin_schema_render_preview_meta_box( $post ) {
	$preview_result = elodin_schema_get_preview_result( $post );
	?>
	<div class="elodin-schema-preview-controls">
		<label for="elodin_schema_preview_url" class="screen-reader-text">Preview URL</label>
		<input
			type="url"
			id="elodin_schema_preview_url"
			class="widefat"
			placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>"
			value="<?php echo esc_attr( home_url( '/' ) ); ?>"
		/>
		<button type="button" class="button" id="elodin_schema_run_preview">Run Preview</button>
		<input type="hidden" id="elodin_schema_preview_nonce" value="<?php echo esc_attr( wp_create_nonce( 'elodin_schema_preview' ) ); ?>" />
	</div>
	<div class="elodin-schema-preview-output-wrap">
		<textarea class="widefat code elodin-schema-preview-output" rows="30" readonly id="elodin_schema_preview_output"><?php echo esc_textarea( $preview_result['markup'] ); ?></textarea>
		<p class="description" id="elodin_schema_preview_status"><?php echo esc_html( $preview_result['message'] ); ?></p>
	</div>
	<?php
}

function elodin_schema_render_notes_meta_box( $post ) {
	wp_nonce_field( 'elodin_schema_save', 'elodin_schema_nonce' );

	$notes = get_post_meta( $post->ID, ELODIN_SCHEMA_META_NOTES, true );
	?>
	<p>Internal notes for this schema entry. These notes never output on the site.</p>
	<textarea
		name="elodin_schema_notes"
		id="elodin_schema_notes"
		class="widefat"
		rows="8"
	><?php echo esc_textarea( $notes ); ?></textarea>
	<?php
}

function elodin_schema_render_settings_meta_box( $post ) {
	wp_nonce_field( 'elodin_schema_save', 'elodin_schema_nonce' );

	$target      = get_post_meta( $post->ID, ELODIN_SCHEMA_META_TARGET, true );
	$output      = get_post_meta( $post->ID, ELODIN_SCHEMA_META_OUTPUT, true );
	$enabled     = get_post_meta( $post->ID, ELODIN_SCHEMA_META_ENABLED, true );
	$priority    = get_post_meta( $post->ID, ELODIN_SCHEMA_META_PRIORITY, true );
	$schema_type = get_post_meta( $post->ID, ELODIN_SCHEMA_META_TYPE, true );

	if ( empty( $target ) ) {
		$target = 'entire_site';
	}

	if ( empty( $output ) ) {
		$output = 'wp_head';
	}

	if ( '' === $enabled ) {
		$enabled = '1';
	}

	if ( '' === $priority ) {
		$priority = 10;
	}
	?>
	<div id="elodin-schema-settings-panel">
		<div class="elodin-schema-setting">
			<label for="elodin_schema_type" class="elodin-schema-setting-label">Schema Type</label>
			<input
				type="text"
				name="elodin_schema_type"
				id="elodin_schema_type"
				class="widefat"
				value="<?php echo esc_attr( $schema_type ); ?>"
				placeholder="VacationRental"
			/>
			<p class="description">A short internal label for the schema class this entry represents.</p>
		</div>

		<div class="elodin-schema-setting">
			<label class="elodin-schema-inline-toggle">
				<input type="checkbox" name="elodin_schema_enabled" value="1" <?php checked( $enabled, '1' ); ?> />
				<span>Enabled</span>
			</label>
			<p class="description">Only enabled entries can output on the front end.</p>
		</div>

		<div class="elodin-schema-setting">
			<label for="elodin_schema_priority" class="elodin-schema-setting-label">Priority</label>
			<input
				type="number"
				name="elodin_schema_priority"
				id="elodin_schema_priority"
				class="small-text"
				min="0"
				step="1"
				value="<?php echo esc_attr( $priority ); ?>"
			/>
			<p class="description">Lower numbers output first.</p>
		</div>

		<div class="elodin-schema-setting">
			<label for="elodin_schema_target" class="elodin-schema-setting-label">Target</label>
			<select name="elodin_schema_target" id="elodin_schema_target" class="widefat">
				<option value="entire_site" <?php selected( $target, 'entire_site' ); ?>>Entire site</option>
			</select>
			<p class="description">This schema currently applies across the whole site.</p>
		</div>

		<div class="elodin-schema-setting">
			<label for="elodin_schema_output" class="elodin-schema-setting-label">Output Location</label>
			<select name="elodin_schema_output" id="elodin_schema_output" class="widefat">
				<option value="wp_head" <?php selected( $output, 'wp_head' ); ?>>Document head via wp_head</option>
			</select>
			<p class="description">Published and enabled entries output in the site <code>&lt;head&gt;</code> on front-end requests.</p>
		</div>
	</div>
	<?php
}

function elodin_schema_save_post( $post_id ) {
	if ( ! isset( $_POST['elodin_schema_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['elodin_schema_nonce'] ) ), 'elodin_schema_save' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$notes = '';
	if ( isset( $_POST['elodin_schema_notes'] ) ) {
		$notes = sanitize_textarea_field( wp_unslash( $_POST['elodin_schema_notes'] ) );
	}

	$script = '';
	if ( isset( $_POST['elodin_schema_script'] ) ) {
		$script = elodin_schema_normalize_json_ld( wp_unslash( $_POST['elodin_schema_script'] ) );
	}

	$target = 'entire_site';
	if ( isset( $_POST['elodin_schema_target'] ) && 'entire_site' === sanitize_key( wp_unslash( $_POST['elodin_schema_target'] ) ) ) {
		$target = 'entire_site';
	}

	$output = 'wp_head';
	if ( isset( $_POST['elodin_schema_output'] ) && 'wp_head' === sanitize_key( wp_unslash( $_POST['elodin_schema_output'] ) ) ) {
		$output = 'wp_head';
	}

	$enabled = isset( $_POST['elodin_schema_enabled'] ) ? '1' : '0';

	$priority = 10;
	if ( isset( $_POST['elodin_schema_priority'] ) ) {
		$priority = absint( wp_unslash( $_POST['elodin_schema_priority'] ) );
	}

	$schema_type = '';
	if ( isset( $_POST['elodin_schema_type'] ) ) {
		$schema_type = sanitize_text_field( wp_unslash( $_POST['elodin_schema_type'] ) );
	}

	update_post_meta( $post_id, ELODIN_SCHEMA_META_NOTES, $notes );
	update_post_meta( $post_id, ELODIN_SCHEMA_META_TARGET, $target );
	update_post_meta( $post_id, ELODIN_SCHEMA_META_OUTPUT, $output );
	update_post_meta( $post_id, ELODIN_SCHEMA_META_ENABLED, $enabled );
	update_post_meta( $post_id, ELODIN_SCHEMA_META_PRIORITY, $priority );
	update_post_meta( $post_id, ELODIN_SCHEMA_META_TYPE, $schema_type );

	if ( '' === $script ) {
		delete_post_meta( $post_id, ELODIN_SCHEMA_META_SCRIPT );
		delete_post_meta( $post_id, ELODIN_SCHEMA_META_VALIDATION_ERROR );
		return;
	}

	$validation = elodin_schema_validate_json_ld( $script );

	update_post_meta( $post_id, ELODIN_SCHEMA_META_SCRIPT, $validation['normalized'] );

	if ( $validation['valid'] ) {
		delete_post_meta( $post_id, ELODIN_SCHEMA_META_VALIDATION_ERROR );
	} else {
		update_post_meta( $post_id, ELODIN_SCHEMA_META_VALIDATION_ERROR, $validation['error'] );
	}
}

function elodin_schema_render_admin_notice() {
	$screen = get_current_screen();

	if ( ! $screen || ELODIN_SCHEMA_POST_TYPE !== $screen->post_type ) {
		return;
	}

	$post_id = 0;
	if ( isset( $_GET['post'] ) ) {
		$post_id = absint( $_GET['post'] );
	}

	if ( $post_id < 1 ) {
		return;
	}

	$validation_message = get_post_meta( $post_id, ELODIN_SCHEMA_META_VALIDATION_ERROR, true );
	if ( empty( $validation_message ) ) {
		return;
	}
	?>
	<div class="notice notice-warning">
		<p><strong>Elodin Schema:</strong> this entry contains invalid JSON-LD and is currently prevented from outputting. <?php echo esc_html( $validation_message ); ?></p>
	</div>
	<?php
}

function elodin_schema_title_placeholder( $title, $post ) {
	if ( ELODIN_SCHEMA_POST_TYPE === $post->post_type ) {
		return 'Schema title';
	}

	return $title;
}

function elodin_schema_highlight_settings_menu( $parent_file ) {
	$screen = get_current_screen();

	if ( $screen && ELODIN_SCHEMA_POST_TYPE === $screen->post_type ) {
		return 'options-general.php';
	}

	return $parent_file;
}

function elodin_schema_highlight_settings_submenu( $submenu_file ) {
	$screen = get_current_screen();

	if ( $screen && ELODIN_SCHEMA_POST_TYPE === $screen->post_type ) {
		return 'edit.php?post_type=' . ELODIN_SCHEMA_POST_TYPE;
	}

	return $submenu_file;
}

function elodin_schema_edit_columns( $columns ) {
	return array(
		'cb'         => $columns['cb'],
		'title'      => __( 'Title', 'elodin-schema' ),
		'schemaType' => __( 'Type', 'elodin-schema' ),
		'enabled'    => __( 'Enabled', 'elodin-schema' ),
		'priority'   => __( 'Priority', 'elodin-schema' ),
		'target'     => __( 'Target', 'elodin-schema' ),
		'output'     => __( 'Output', 'elodin-schema' ),
		'validation' => __( 'Validation', 'elodin-schema' ),
		'date'       => $columns['date'],
	);
}

function elodin_schema_render_custom_column( $column, $post_id ) {
	switch ( $column ) {
		case 'schemaType':
			$schema_type = get_post_meta( $post_id, ELODIN_SCHEMA_META_TYPE, true );
			echo esc_html( $schema_type ? $schema_type : '—' );
			break;

		case 'enabled':
			$enabled = get_post_meta( $post_id, ELODIN_SCHEMA_META_ENABLED, true );
			echo esc_html( ( '1' === $enabled || '' === $enabled ) ? 'Yes' : 'No' );
			break;

		case 'priority':
			$priority = get_post_meta( $post_id, ELODIN_SCHEMA_META_PRIORITY, true );
			echo esc_html( '' !== $priority ? $priority : '10' );
			break;

		case 'target':
			echo esc_html( 'Entire site' );
			break;

		case 'output':
			echo esc_html( 'Head' );
			break;

		case 'validation':
			$validation_message = get_post_meta( $post_id, ELODIN_SCHEMA_META_VALIDATION_ERROR, true );
			echo esc_html( empty( $validation_message ) ? 'Valid' : 'Invalid' );
			break;
	}
}

function elodin_schema_output_schema() {
	if ( is_admin() ) {
		return;
	}

	$schema_posts = get_posts(
		apply_filters(
			'elodin_schema_query_args',
			array(
				'post_type'              => ELODIN_SCHEMA_POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			)
		)
	);

	if ( empty( $schema_posts ) ) {
		return;
	}

	usort( $schema_posts, 'elodin_schema_sort_posts' );
	$schema_posts = apply_filters( 'elodin_schema_posts', $schema_posts );

	foreach ( $schema_posts as $schema_post ) {
		$context = elodin_schema_get_render_context( $schema_post );
		$context = apply_filters( 'elodin_schema_context', $context, $schema_post );

		if ( ! elodin_schema_should_output( $schema_post->ID, $context ) ) {
			continue;
		}

		$markup = elodin_schema_get_rendered_markup( $schema_post, $context );
		if ( empty( $markup ) ) {
			continue;
		}

		printf(
			"\n<!-- Elodin Schema: %s -->\n%s\n",
			esc_html( get_the_title( $schema_post ) ),
			$markup
		);
	}
}

function elodin_schema_sort_posts( $left, $right ) {
	$left_priority  = (int) get_post_meta( $left->ID, ELODIN_SCHEMA_META_PRIORITY, true );
	$right_priority = (int) get_post_meta( $right->ID, ELODIN_SCHEMA_META_PRIORITY, true );

	if ( $left_priority === $right_priority ) {
		return strcmp( $left->post_title, $right->post_title );
	}

	return $left_priority < $right_priority ? -1 : 1;
}

function elodin_schema_should_output( $post_id, $context = array() ) {
	$enabled            = get_post_meta( $post_id, ELODIN_SCHEMA_META_ENABLED, true );
	$target             = get_post_meta( $post_id, ELODIN_SCHEMA_META_TARGET, true );
	$output             = get_post_meta( $post_id, ELODIN_SCHEMA_META_OUTPUT, true );
	$validation_message = get_post_meta( $post_id, ELODIN_SCHEMA_META_VALIDATION_ERROR, true );

	if ( '' === $enabled ) {
		$enabled = '1';
	}

	if ( empty( $target ) ) {
		$target = 'entire_site';
	}

	if ( empty( $output ) ) {
		$output = 'wp_head';
	}

	$should_output = (
		'1' === $enabled &&
		'entire_site' === $target &&
		'wp_head' === $output &&
		empty( $validation_message )
	);

	return (bool) apply_filters( 'elodin_schema_should_output_entry', $should_output, $post_id, $context );
}

function elodin_schema_get_rendered_markup( $schema_post, $context = array() ) {
	$script = get_post_meta( $schema_post->ID, ELODIN_SCHEMA_META_SCRIPT, true );
	if ( empty( $script ) ) {
		return '';
	}

	$validation = elodin_schema_validate_json_ld( $script );
	if ( ! $validation['valid'] || empty( $validation['data'] ) ) {
		return '';
	}

	$resolved = elodin_schema_replace_placeholders_recursive( $validation['data'], $context );
	$resolved = apply_filters( 'elodin_schema_resolved_data', $resolved, $schema_post, $context );

	$json = wp_json_encode(
		$resolved,
		JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	);

	if ( false === $json ) {
		return '';
	}

	$json = str_replace( '</script>', '<\/script>', $json );

	$markup = sprintf(
		"<script type=\"application/ld+json\">\n%s\n</script>",
		$json
	);

	return apply_filters( 'elodin_schema_markup', $markup, $schema_post, $context, $resolved );
}

function elodin_schema_get_render_context( $schema_post ) {
	$object_id = get_queried_object_id();
	$object    = $object_id ? get_post( $object_id ) : null;

	$context = array(
		'schema_post' => $schema_post,
		'request'     => array(
			'url' => elodin_schema_get_current_url(),
		),
		'site'        => array(
			'name'        => get_bloginfo( 'name' ),
			'description' => get_bloginfo( 'description' ),
			'url'         => home_url( '/' ),
			'language'    => get_bloginfo( 'language' ),
		),
		'object'      => null,
		'object_id'   => $object_id,
	);

	if ( $object instanceof WP_Post ) {
		$context['object'] = array(
			'id'             => $object->ID,
			'title'          => get_the_title( $object ),
			'excerpt'        => has_excerpt( $object ) ? get_the_excerpt( $object ) : '',
			'content'        => wp_strip_all_tags( $object->post_content ),
			'slug'           => $object->post_name,
			'url'            => get_permalink( $object ),
			'type'           => $object->post_type,
			'date_published' => get_post_time( DATE_W3C, false, $object ),
			'date_modified'  => get_post_modified_time( DATE_W3C, false, $object ),
		);
	}

	return $context;
}

function elodin_schema_get_current_url() {
	$scheme = is_ssl() ? 'https://' : 'http://';
	$host   = isset( $_SERVER['HTTP_HOST'] ) ? wp_unslash( $_SERVER['HTTP_HOST'] ) : '';
	$uri    = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';

	return esc_url_raw( $scheme . $host . $uri );
}

function elodin_schema_replace_placeholders_recursive( $value, $context ) {
	if ( is_array( $value ) ) {
		$resolved = array();
		foreach ( $value as $key => $child ) {
			$resolved[ $key ] = elodin_schema_replace_placeholders_recursive( $child, $context );
		}
		return $resolved;
	}

	if ( ! is_string( $value ) ) {
		return $value;
	}

	preg_match_all( '/{{\s*([^}]+)\s*}}/', $value, $matches );
	if ( empty( $matches[1] ) ) {
		return $value;
	}

	if ( preg_match( '/^\s*{{\s*([^}]+)\s*}}\s*$/', $value, $single_match ) ) {
		$resolved = elodin_schema_resolve_placeholder( $single_match[1], $context );
		if ( null !== $resolved ) {
			return $resolved;
		}
	}

	$replaced = $value;
	foreach ( $matches[1] as $index => $token ) {
		$resolved = elodin_schema_resolve_placeholder( $token, $context );
		if ( null === $resolved ) {
			continue;
		}

		if ( is_scalar( $resolved ) ) {
			$replacement = (string) $resolved;
		} else {
			$replacement = wp_json_encode( $resolved, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		}

		$replaced = str_replace( $matches[0][ $index ], $replacement, $replaced );
	}

	return $replaced;
}

function elodin_schema_resolve_placeholder( $token, $context ) {
	$token = trim( $token );
	if ( '' === $token ) {
		return null;
	}

	$parts = explode( '.', $token );
	$root  = array_shift( $parts );

	switch ( $root ) {
		case 'site':
		case 'request':
		case 'object':
			$value = isset( $context[ $root ] ) ? $context[ $root ] : null;
			break;

		case 'meta':
			if ( empty( $context['object_id'] ) || empty( $parts ) ) {
				return null;
			}
			$meta_key = implode( '.', $parts );
			$value    = get_post_meta( $context['object_id'], $meta_key, true );
			$parts    = array();
			break;

		case 'field':
			if ( empty( $context['object_id'] ) || empty( $parts ) || ! function_exists( 'get_field' ) ) {
				return null;
			}
			$field_key = implode( '.', $parts );
			$value     = get_field( $field_key, $context['object_id'] );
			$parts     = array();
			break;

		case 'option':
			if ( empty( $parts ) ) {
				return null;
			}
			$option_name = implode( '.', $parts );
			$value       = get_option( $option_name );
			$parts       = array();
			break;

		default:
			return null;
	}

	foreach ( $parts as $part ) {
		if ( is_array( $value ) && array_key_exists( $part, $value ) ) {
			$value = $value[ $part ];
		} else {
			return null;
		}
	}

	return apply_filters( 'elodin_schema_resolved_value', $value, $token, $context );
}

function elodin_schema_get_preview_markup( $post ) {
	$preview_result = elodin_schema_get_preview_result( $post );
	return $preview_result['markup'];
}

function elodin_schema_get_preview_result( $post, $preview_url = '', $script_override = null ) {
	if ( is_string( $script_override ) ) {
		$script = elodin_schema_normalize_json_ld( $script_override );
	} else {
		$script = get_post_meta( $post->ID, ELODIN_SCHEMA_META_SCRIPT, true );
	}

	if ( empty( $script ) ) {
		return array(
			'markup'  => '',
			'message' => 'Add JSON-LD above to preview it.',
		);
	}

	$validation = elodin_schema_validate_json_ld( $script );
	if ( ! $validation['valid'] || empty( $validation['data'] ) ) {
		return array(
			'markup'  => 'Preview unavailable until the JSON-LD is valid.',
			'message' => 'Fix the JSON-LD validation error first.',
		);
	}

	$context = elodin_schema_get_preview_context_from_url( $post, $preview_url );

	$resolved = elodin_schema_replace_placeholders_recursive( $validation['data'], $context );
	$json     = wp_json_encode(
		$resolved,
		JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	);

	if ( false === $json ) {
		return array(
			'markup'  => 'Preview unavailable.',
			'message' => 'Preview encoding failed.',
		);
	}

	return array(
		'markup'  => $json,
		'message' => $context['preview_message'],
	);
}

function elodin_schema_get_preview_context_from_url( $schema_post, $preview_url = '' ) {
	$site_url        = home_url( '/' );
	$requested_url   = trim( (string) $preview_url );
	$resolved_url    = $requested_url ? esc_url_raw( $requested_url ) : $site_url;
	$preview_message = 'Previewing against the site home URL.';

	if ( empty( $resolved_url ) ) {
		$resolved_url    = $site_url;
		$preview_message = 'Preview URL was empty or invalid, so the site home URL is being used.';
	}

	$context = array(
		'schema_post'     => $schema_post,
		'request'         => array(
			'url' => $resolved_url,
		),
		'site'            => array(
			'name'        => get_bloginfo( 'name' ),
			'description' => get_bloginfo( 'description' ),
			'url'         => home_url( '/' ),
			'language'    => get_bloginfo( 'language' ),
		),
		'object'          => null,
		'object_id'       => 0,
		'preview_message' => $preview_message,
	);

	$site_host    = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
	$preview_host = wp_parse_url( $resolved_url, PHP_URL_HOST );

	if ( empty( $preview_host ) || $preview_host !== $site_host ) {
		$context['preview_message'] = 'Previewing request placeholders only. This URL does not appear to belong to the current site.';
		return $context;
	}

	$object_id = url_to_postid( $resolved_url );

	if ( ! $object_id && untrailingslashit( $resolved_url ) === untrailingslashit( home_url( '/' ) ) ) {
		$front_page_id = (int) get_option( 'page_on_front' );
		if ( $front_page_id > 0 ) {
			$object_id = $front_page_id;
		}
	}

	if ( $object_id < 1 ) {
		$context['preview_message'] = 'Previewing site and request placeholders. This URL did not resolve to a singular WordPress object.';
		return $context;
	}

	$object = get_post( $object_id );
	if ( ! $object instanceof WP_Post ) {
		$context['preview_message'] = 'Previewing site and request placeholders. The URL resolved, but no preview object could be loaded.';
		return $context;
	}

	$context['object_id'] = $object->ID;
	$context['object']    = array(
		'id'             => $object->ID,
		'title'          => get_the_title( $object ),
		'excerpt'        => has_excerpt( $object ) ? get_the_excerpt( $object ) : '',
		'content'        => wp_strip_all_tags( $object->post_content ),
		'slug'           => $object->post_name,
		'url'            => get_permalink( $object ),
		'type'           => $object->post_type,
		'date_published' => get_post_time( DATE_W3C, false, $object ),
		'date_modified'  => get_post_modified_time( DATE_W3C, false, $object ),
	);
	$context['preview_message'] = sprintf(
		'Previewing against %s "%s".',
		$object->post_type,
		get_the_title( $object )
	);

	return $context;
}

function elodin_schema_ajax_preview() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error(
			array(
				'message' => 'You are not allowed to preview schema.',
			),
			403
		);
	}

	check_ajax_referer( 'elodin_schema_preview', 'nonce' );

	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$post    = $post_id > 0 ? get_post( $post_id ) : null;
	if ( $post && ( ! $post instanceof WP_Post || ELODIN_SCHEMA_POST_TYPE !== $post->post_type ) ) {
		wp_send_json_error(
			array(
				'message' => 'Invalid schema entry.',
			),
			400
		);
	}

	if ( ! $post ) {
		$post = (object) array(
			'ID'        => 0,
			'post_type' => ELODIN_SCHEMA_POST_TYPE,
		);
	}

	$preview_url    = isset( $_POST['preview_url'] ) ? wp_unslash( $_POST['preview_url'] ) : '';
	$script         = isset( $_POST['script'] ) ? wp_unslash( $_POST['script'] ) : '';
	$preview_result = elodin_schema_get_preview_result( $post, $preview_url, $script );

	wp_send_json_success( $preview_result );
}

function elodin_schema_validate_json_ld( $script ) {
	$script     = trim( $script );
	$normalized = $script;

	if ( '' === $script ) {
		return array(
			'valid'      => false,
			'error'      => 'JSON-LD is empty.',
			'data'       => null,
			'normalized' => '',
		);
	}

	$data = json_decode( $script, true );
	if ( JSON_ERROR_NONE !== json_last_error() ) {
		return array(
			'valid'      => false,
			'error'      => json_last_error_msg(),
			'data'       => null,
			'normalized' => $normalized,
		);
	}

	$normalized = wp_json_encode(
		$data,
		JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	);

	return array(
		'valid'      => false !== $normalized,
		'error'      => false === $normalized ? 'Could not normalize JSON-LD.' : '',
		'data'       => $data,
		'normalized' => false === $normalized ? $script : $normalized,
	);
}

function elodin_schema_normalize_json_ld( $script ) {
	$script = trim( $script );

	if ( '' === $script ) {
		return '';
	}

	if ( preg_match( '#<script\b[^>]*type=(["\'])application/ld\+json\1[^>]*>(.*?)</script>#is', $script, $matches ) ) {
		return trim( $matches[2] );
	}

	return $script;
}

function elodin_schema_get_starter_templates() {
	$templates = array(
		array(
			'label' => 'Organization',
			'data'  => array(
				'@context' => 'https://schema.org',
				'@type'    => 'Organization',
				'name'     => '{{site.name}}',
				'url'      => '{{site.url}}',
				'description' => '{{site.description}}',
			),
		),
		array(
			'label' => 'WebSite',
			'data'  => array(
				'@context' => 'https://schema.org',
				'@type'    => 'WebSite',
				'name'     => '{{site.name}}',
				'url'      => '{{site.url}}',
			),
		),
		array(
			'label' => 'VacationRental',
			'data'  => array(
				'@context' => 'https://schema.org',
				'@type'    => 'VacationRental',
				'name'     => '{{site.name}}',
				'url'      => '{{site.url}}',
				'description' => '{{site.description}}',
				'mainEntityOfPage' => '{{request.url}}',
			),
		),
		array(
			'label' => 'FAQPage',
			'data'  => array(
				'@context' => 'https://schema.org',
				'@type'    => 'FAQPage',
				'mainEntity' => array(
					array(
						'@type' => 'Question',
						'name'  => 'Question goes here',
						'acceptedAnswer' => array(
							'@type' => 'Answer',
							'text'  => 'Answer goes here',
						),
					),
				),
			),
		),
	);

	foreach ( $templates as &$template ) {
		$template['json'] = wp_json_encode(
			$template['data'],
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);
	}

	return $templates;
}

function elodin_schema_get_placeholder_definitions() {
	return array(
		'Site'    => array(
			'{{site.name}}'        => 'Site name',
			'{{site.url}}'         => 'Home URL',
			'{{site.description}}' => 'Site description',
			'{{site.language}}'    => 'Site language',
		),
		'Request' => array(
			'{{request.url}}' => 'Current request URL',
		),
		'Object'  => array(
			'{{object.title}}'          => 'Current object title',
			'{{object.url}}'            => 'Current object permalink',
			'{{object.slug}}'           => 'Current object slug',
			'{{object.type}}'           => 'Current object post type',
			'{{object.excerpt}}'        => 'Current object excerpt',
			'{{object.date_published}}' => 'Current object publish date',
			'{{object.date_modified}}'  => 'Current object modified date',
		),
		'Dynamic' => array(
			'{{meta.custom_field_name}}'  => 'Current object post meta value',
			'{{field.custom_field_name}}' => 'Current object ACF field value',
			'{{option.blogname}}'         => 'WordPress option value',
		),
	);
}

require 'vendor/plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/jonschr/elodin-schema',
	__FILE__,
	'elodin-schema'
);

$myUpdateChecker->setBranch( 'master' );
