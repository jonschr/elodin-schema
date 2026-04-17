<?php
/*
	Plugin Name: Elodin Schema
	Plugin URI: https://elod.in
	Description: Manage reusable schema snippets and output them across the site.
	Version: 0.2
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
define( 'ELODIN_SCHEMA_URL', plugin_dir_url( __FILE__ ) );
define( 'ELODIN_SCHEMA_VERSION', '0.2' );
define( 'ELODIN_SCHEMA_POST_TYPE', 'elodin_schema' );

define( 'ELODIN_SCHEMA_META_NOTES', '_elodin_schema_notes' );
define( 'ELODIN_SCHEMA_META_SCRIPT', '_elodin_schema_script' );
define( 'ELODIN_SCHEMA_META_TARGET', '_elodin_schema_target' );
define( 'ELODIN_SCHEMA_META_ENABLED', '_elodin_schema_enabled' );
define( 'ELODIN_SCHEMA_META_TYPE', '_elodin_schema_type' );
define( 'ELODIN_SCHEMA_META_VALIDATION_ERROR', '_elodin_schema_validation_error' );
define( 'ELODIN_SCHEMA_META_LOCAL_ENTRIES', '_elodin_schema_local_entries' );

add_action( 'init', 'elodin_schema_register_post_type' );
add_action( 'add_meta_boxes', 'elodin_schema_register_meta_boxes' );
add_action( 'add_meta_boxes', 'elodin_schema_register_post_meta_box' );
add_action( 'admin_enqueue_scripts', 'elodin_schema_enqueue_admin_assets' );
add_action( 'edit_form_after_title', 'elodin_schema_render_editor_section' );
add_action( 'save_post_' . ELODIN_SCHEMA_POST_TYPE, 'elodin_schema_save_post' );
add_action( 'save_post', 'elodin_schema_save_local_entries', 10, 2 );
add_action( 'wp_head', 'elodin_schema_output_schema', 20 );
add_action( 'admin_notices', 'elodin_schema_render_admin_notice' );
add_action( 'wp_ajax_elodin_schema_preview', 'elodin_schema_ajax_preview' );
add_action( 'wp_ajax_elodin_schema_toggle_enabled', 'elodin_schema_ajax_toggle_enabled' );

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

function elodin_schema_get_supported_post_types() {
	$post_types = get_post_types(
		array(
			'public'  => true,
			'show_ui' => true,
		),
		'names'
	);

	unset( $post_types['attachment'] );
	unset( $post_types[ ELODIN_SCHEMA_POST_TYPE ] );

	$post_types = array_values( $post_types );

	return apply_filters( 'elodin_schema_local_post_types', $post_types );
}

function elodin_schema_is_supported_post_type( $post_type ) {
	return in_array( $post_type, elodin_schema_get_supported_post_types(), true );
}

function elodin_schema_register_post_meta_box() {
	foreach ( elodin_schema_get_supported_post_types() as $post_type ) {
		add_meta_box(
			'elodin-schema-post',
			__( 'Schema', 'elodin-schema' ),
			'elodin_schema_render_post_meta_box',
			$post_type,
			'normal',
			'default'
		);
	}
}

function elodin_schema_enqueue_admin_assets() {
	$screen = get_current_screen();

	if ( ! $screen ) {
		return;
	}

	$is_schema_post_type    = ELODIN_SCHEMA_POST_TYPE === $screen->post_type;
	$is_supported_post_type = elodin_schema_is_supported_post_type( $screen->post_type );

	if ( ! $is_schema_post_type && ! $is_supported_post_type ) {
		return;
	}

	$is_single_editor_screen = in_array( $screen->base, array( 'post', 'post-new' ), true );

	if ( ! $is_schema_post_type && ! $is_single_editor_screen ) {
		return;
	}

	$style_dependencies      = array();
	$script_dependencies     = array( 'jquery' );
	$settings                = false;
	$preview_settings        = false;

	wp_enqueue_script( 'jquery' );

	if ( $is_single_editor_screen ) {
		$style_dependencies[]  = 'code-editor';
		$script_dependencies[] = 'code-editor';

		$settings = wp_enqueue_code_editor(
			array(
				'type'       => 'application/json',
				'codemirror' => array(
					'lineWrapping' => false,
				),
			)
		);

		if ( $is_schema_post_type && false !== $settings ) {
			$preview_settings = $settings;
			if ( ! isset( $preview_settings['codemirror'] ) || ! is_array( $preview_settings['codemirror'] ) ) {
				$preview_settings['codemirror'] = array();
			}
			$preview_settings['codemirror']['readOnly'] = 'nocursor';
		}
	}

	wp_enqueue_style(
		'elodin-schema-admin',
		ELODIN_SCHEMA_URL . 'assets/css/admin.css',
		$style_dependencies,
		(string) filemtime( ELODIN_SCHEMA . '/assets/css/admin.css' )
	);

	wp_enqueue_script(
		'elodin-schema-admin',
		ELODIN_SCHEMA_URL . 'assets/js/admin.js',
		$script_dependencies,
		(string) filemtime( ELODIN_SCHEMA . '/assets/js/admin.js' ),
		true
	);

	wp_localize_script(
		'elodin-schema-admin',
		'elodinSchemaConfig',
		array(
			'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
			'previewNonce'    => wp_create_nonce( 'elodin_schema_preview' ),
			'toggleNonce'     => wp_create_nonce( 'elodin_schema_toggle_enabled' ),
			'editorSettings'  => $settings,
			'previewSettings' => $preview_settings,
		)
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
	$enabled     = get_post_meta( $post->ID, ELODIN_SCHEMA_META_ENABLED, true );
	$schema_type = get_post_meta( $post->ID, ELODIN_SCHEMA_META_TYPE, true );

	if ( empty( $target ) ) {
		$target = 'entire_site';
	}

	if ( '' === $enabled ) {
		$enabled = '1';
	}
	?>
	<div id="elodin-schema-settings-panel">
		<div class="elodin-schema-setting">
			<label class="elodin-schema-toggle" for="elodin_schema_enabled">
				<input
					type="checkbox"
					name="elodin_schema_enabled"
					id="elodin_schema_enabled"
					value="1"
					<?php checked( $enabled, '1' ); ?>
				/>
				<span class="elodin-schema-toggle-track" aria-hidden="true">
					<span class="elodin-schema-toggle-thumb"></span>
				</span>
				<span class="elodin-schema-toggle-text">Enabled</span>
			</label>
			<p class="description">Only enabled entries can output on the front end.</p>
		</div>

		<div class="elodin-schema-setting">
			<div class="elodin-schema-settings-grid">
				<div class="elodin-schema-settings-grid-item">
					<label for="elodin_schema_type" class="elodin-schema-setting-label">Schema Type</label>
					<input
						type="text"
						name="elodin_schema_type"
						id="elodin_schema_type"
						class="widefat"
						value="<?php echo esc_attr( $schema_type ); ?>"
						placeholder="VacationRental"
					/>
					<p class="description">Internal schema label.</p>
				</div>
				<div class="elodin-schema-settings-grid-item">
					<label for="elodin_schema_target" class="elodin-schema-setting-label">Target</label>
					<select name="elodin_schema_target" id="elodin_schema_target" class="widefat">
						<option value="entire_site" <?php selected( $target, 'entire_site' ); ?>>Entire site</option>
					</select>
					<p class="description">Current scope.</p>
				</div>
			</div>
		</div>
	</div>
	<?php
}

function elodin_schema_get_empty_local_entry() {
	return array(
		'enabled'           => '1',
		'schema_type'       => '',
		'replace_global_id' => 0,
		'script'            => '',
		'validation_error'  => '',
	);
}

function elodin_schema_get_post_schema_entries( $post_id ) {
	$entries = get_post_meta( $post_id, ELODIN_SCHEMA_META_LOCAL_ENTRIES, true );

	if ( ! is_array( $entries ) ) {
		return array();
	}

	$normalized_entries = array();

	foreach ( $entries as $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}

		$normalized_entries[] = array(
			'enabled'           => ( isset( $entry['enabled'] ) && '0' === (string) $entry['enabled'] ) ? '0' : '1',
			'schema_type'       => isset( $entry['schema_type'] ) ? (string) $entry['schema_type'] : '',
			'replace_global_id' => isset( $entry['replace_global_id'] ) ? absint( $entry['replace_global_id'] ) : 0,
			'script'            => isset( $entry['script'] ) ? (string) $entry['script'] : '',
			'validation_error'  => isset( $entry['validation_error'] ) ? (string) $entry['validation_error'] : '',
		);
	}

	return $normalized_entries;
}

function elodin_schema_get_global_schema_options() {
	$schema_posts = get_posts(
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
	);

	$options = array();

	foreach ( $schema_posts as $schema_post ) {
		$schema_type = get_post_meta( $schema_post->ID, ELODIN_SCHEMA_META_TYPE, true );
		$label       = get_the_title( $schema_post );

		if ( $schema_type ) {
			$label .= sprintf( ' (%s)', $schema_type );
		}

		$options[] = array(
			'id'    => $schema_post->ID,
			'label' => $label,
		);
	}

	return $options;
}

function elodin_schema_render_post_meta_box( $post ) {
	$entries               = elodin_schema_get_post_schema_entries( $post->ID );
	$global_schema_options = elodin_schema_get_global_schema_options();

	if ( empty( $entries ) ) {
		$entries = array( elodin_schema_get_empty_local_entry() );
	}

	wp_nonce_field( 'elodin_schema_local_save', 'elodin_schema_local_nonce' );
	?>
	<div id="elodin-schema-local-editor" class="elodin-schema-post-editor" data-next-index="<?php echo esc_attr( count( $entries ) ); ?>">
		<p class="description">Add one or more schema blocks for this post. Enabled local blocks output on this post only, and they can explicitly replace a selected global schema entry.</p>

		<div class="elodin-schema-local-entry-list">
			<?php foreach ( $entries as $index => $entry ) : ?>
				<?php elodin_schema_render_post_schema_entry( $index, $entry, $global_schema_options ); ?>
			<?php endforeach; ?>
		</div>

		<p class="elodin-schema-post-editor-actions">
			<button type="button" class="button button-secondary" id="elodin-schema-add-local-entry">Add Schema Block</button>
		</p>
	</div>

	<script type="text/html" id="tmpl-elodin-schema-local-entry">
		<?php elodin_schema_render_post_schema_entry( '__INDEX__', elodin_schema_get_empty_local_entry(), $global_schema_options ); ?>
	</script>
	<?php
}

function elodin_schema_render_post_schema_entry( $index, $entry, $global_schema_options ) {
	$entry             = wp_parse_args( $entry, elodin_schema_get_empty_local_entry() );
	$field_prefix      = 'elodin_schema_local_entries[' . $index . ']';
	$field_id_prefix   = 'elodin_schema_local_entries_' . $index;
	$placeholders      = elodin_schema_get_placeholder_definitions();
	$starter_templates = elodin_schema_get_starter_templates();
	?>
	<div class="elodin-schema-local-entry">
		<div class="elodin-schema-local-entry-header">
			<h3 class="elodin-schema-local-entry-title">Schema Block</h3>
			<button type="button" class="button-link-delete elodin-schema-remove-local-entry">Remove</button>
		</div>

		<?php if ( ! empty( $entry['validation_error'] ) ) : ?>
			<div class="notice notice-error inline">
				<p><strong>JSON-LD validation error:</strong> <?php echo esc_html( $entry['validation_error'] ); ?></p>
				<p>This block stays editable, but it will not output until the JSON-LD is valid again.</p>
			</div>
		<?php endif; ?>

		<div class="elodin-schema-local-entry-settings">
			<div class="elodin-schema-local-entry-grid">
				<div class="elodin-schema-local-entry-grid-item">
					<label class="elodin-schema-toggle" for="<?php echo esc_attr( $field_id_prefix . '_enabled' ); ?>">
						<input
							type="checkbox"
							name="<?php echo esc_attr( $field_prefix . '[enabled]' ); ?>"
							id="<?php echo esc_attr( $field_id_prefix . '_enabled' ); ?>"
							value="1"
							<?php checked( $entry['enabled'], '1' ); ?>
						/>
						<span class="elodin-schema-toggle-track" aria-hidden="true">
							<span class="elodin-schema-toggle-thumb"></span>
						</span>
						<span class="elodin-schema-toggle-text">Enabled</span>
					</label>
					<p class="description">Only enabled, valid blocks output on this post.</p>
				</div>

				<div class="elodin-schema-local-entry-grid-item">
					<label for="<?php echo esc_attr( $field_id_prefix . '_schema_type' ); ?>" class="elodin-schema-setting-label">Schema Type</label>
					<input
						type="text"
						name="<?php echo esc_attr( $field_prefix . '[schema_type]' ); ?>"
						id="<?php echo esc_attr( $field_id_prefix . '_schema_type' ); ?>"
						class="widefat"
						value="<?php echo esc_attr( $entry['schema_type'] ); ?>"
						placeholder="VacationRental"
					/>
					<p class="description">Internal label for this local schema block.</p>
				</div>
			</div>

			<div class="elodin-schema-local-entry-field">
				<label for="<?php echo esc_attr( $field_id_prefix . '_replace_global_id' ); ?>" class="elodin-schema-setting-label">Replace Global Schema</label>
				<select
					name="<?php echo esc_attr( $field_prefix . '[replace_global_id]' ); ?>"
					id="<?php echo esc_attr( $field_id_prefix . '_replace_global_id' ); ?>"
					class="widefat"
				>
					<option value="0">Do not replace a global schema</option>
					<?php foreach ( $global_schema_options as $global_schema_option ) : ?>
						<option value="<?php echo esc_attr( $global_schema_option['id'] ); ?>" <?php selected( $entry['replace_global_id'], $global_schema_option['id'] ); ?>>
							<?php echo esc_html( $global_schema_option['label'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description">When this local block is enabled and valid, the selected global schema is skipped on this post.</p>
			</div>
		</div>

		<div class="elodin-schema-local-entry-toolbar">
			<div class="elodin-schema-local-entry-toolbar-group">
				<label for="<?php echo esc_attr( $field_id_prefix . '_template' ); ?>" class="screen-reader-text">Starter Template</label>
				<select id="<?php echo esc_attr( $field_id_prefix . '_template' ); ?>" class="regular-text elodin-schema-local-template">
					<option value="">Select a starter template</option>
					<?php foreach ( $starter_templates as $template ) : ?>
						<option value="<?php echo esc_attr( $template['json'] ); ?>">
							<?php echo esc_html( $template['label'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<button type="button" class="button elodin-schema-local-apply-template">Insert Template</button>
			</div>

			<div class="elodin-schema-local-entry-toolbar-group">
				<label for="<?php echo esc_attr( $field_id_prefix . '_placeholder_picker' ); ?>" class="screen-reader-text">Placeholder Picker</label>
				<select id="<?php echo esc_attr( $field_id_prefix . '_placeholder_picker' ); ?>" class="regular-text elodin-schema-local-placeholder-picker">
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
		</div>

		<div class="elodin-schema-local-entry-editor">
			<textarea
				name="<?php echo esc_attr( $field_prefix . '[script]' ); ?>"
				id="<?php echo esc_attr( $field_id_prefix . '_script' ); ?>"
				class="widefat code elodin-schema-json-editor"
				rows="20"
				spellcheck="false"
				wrap="off"
			><?php echo esc_textarea( $entry['script'] ); ?></textarea>
		</div>
		<p class="description">Paste JSON-LD only. The plugin wraps valid JSON-LD in <code>&lt;script type="application/ld+json"&gt;</code> during output.</p>
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

	$enabled = isset( $_POST['elodin_schema_enabled'] ) ? '1' : '0';

	$schema_type = '';
	if ( isset( $_POST['elodin_schema_type'] ) ) {
		$schema_type = sanitize_text_field( wp_unslash( $_POST['elodin_schema_type'] ) );
	}

	update_post_meta( $post_id, ELODIN_SCHEMA_META_NOTES, $notes );
	update_post_meta( $post_id, ELODIN_SCHEMA_META_TARGET, $target );
	update_post_meta( $post_id, ELODIN_SCHEMA_META_ENABLED, $enabled );
	update_post_meta( $post_id, ELODIN_SCHEMA_META_TYPE, $schema_type );
	delete_post_meta( $post_id, '_elodin_schema_output' );
	delete_post_meta( $post_id, '_elodin_schema_priority' );

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

function elodin_schema_save_local_entries( $post_id, $post ) {
	if ( ! $post instanceof WP_Post || ! elodin_schema_is_supported_post_type( $post->post_type ) ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['elodin_schema_local_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['elodin_schema_local_nonce'] ) ), 'elodin_schema_local_save' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$entries = array();

	if ( isset( $_POST['elodin_schema_local_entries'] ) && is_array( $_POST['elodin_schema_local_entries'] ) ) {
		$entries = wp_unslash( $_POST['elodin_schema_local_entries'] );
	}

	$normalized_entries = array();

	foreach ( $entries as $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}

		$schema_type       = isset( $entry['schema_type'] ) ? sanitize_text_field( $entry['schema_type'] ) : '';
		$replace_global_id = isset( $entry['replace_global_id'] ) ? absint( $entry['replace_global_id'] ) : 0;
		$enabled           = isset( $entry['enabled'] ) ? '1' : '0';
		$script            = isset( $entry['script'] ) ? elodin_schema_normalize_json_ld( $entry['script'] ) : '';

		if ( '' === $schema_type && 0 === $replace_global_id && '' === $script ) {
			continue;
		}

		$normalized_entry = array(
			'enabled'           => $enabled,
			'schema_type'       => $schema_type,
			'replace_global_id' => $replace_global_id,
			'script'            => $script,
			'validation_error'  => '',
		);

		if ( '' !== $script ) {
			$validation = elodin_schema_validate_json_ld( $script );

			$normalized_entry['script'] = $validation['normalized'];

			if ( ! $validation['valid'] ) {
				$normalized_entry['validation_error'] = $validation['error'];
			}
		}

		$normalized_entries[] = $normalized_entry;
	}

	if ( empty( $normalized_entries ) ) {
		delete_post_meta( $post_id, ELODIN_SCHEMA_META_LOCAL_ENTRIES );
		return;
	}

	update_post_meta( $post_id, ELODIN_SCHEMA_META_LOCAL_ENTRIES, $normalized_entries );
}

function elodin_schema_render_admin_notice() {
	$screen = get_current_screen();

	if ( ! $screen ) {
		return;
	}

	$post_id = 0;
	if ( isset( $_GET['post'] ) ) {
		$post_id = absint( $_GET['post'] );
	}

	if ( $post_id < 1 ) {
		return;
	}

	if ( ELODIN_SCHEMA_POST_TYPE !== $screen->post_type && ! elodin_schema_is_supported_post_type( $screen->post_type ) ) {
		return;
	}

	if ( ELODIN_SCHEMA_POST_TYPE !== $screen->post_type ) {
		$entries = elodin_schema_get_post_schema_entries( $post_id );
		$invalid = array_filter(
			$entries,
			static function( $entry ) {
				return ! empty( $entry['validation_error'] );
			}
		);

		if ( empty( $invalid ) ) {
			return;
		}
		?>
		<div class="notice notice-warning">
			<p><strong>Elodin Schema:</strong> one or more post-level schema blocks are invalid and are currently prevented from outputting.</p>
		</div>
		<?php
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
		'enabled'    => __( 'Enabled', 'elodin-schema' ),
		'title'      => __( 'Title', 'elodin-schema' ),
		'schemaType' => __( 'Type', 'elodin-schema' ),
		'notes'      => __( 'Notes', 'elodin-schema' ),
		'target'     => __( 'Target', 'elodin-schema' ),
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

		case 'notes':
			$notes = get_post_meta( $post_id, ELODIN_SCHEMA_META_NOTES, true );
			$notes = trim( preg_replace( '/\s+/', ' ', (string) $notes ) );

			if ( '' === $notes ) {
				echo esc_html( '—' );
				break;
			}

			$excerpt = wp_html_excerpt( $notes, 120, '…' );
			echo esc_html( $excerpt );
			break;

		case 'enabled':
			$enabled = get_post_meta( $post_id, ELODIN_SCHEMA_META_ENABLED, true );
			$is_enabled = ( '1' === $enabled || '' === $enabled );
			?>
			<div class="elodin-schema-toggle-row">
				<button
					type="button"
					class="elodin-schema-list-switch<?php echo $is_enabled ? ' is-enabled' : ''; ?>"
					data-post-id="<?php echo esc_attr( $post_id ); ?>"
					role="switch"
					aria-checked="<?php echo $is_enabled ? 'true' : 'false'; ?>"
					aria-label="<?php echo esc_attr( sprintf( 'Toggle enabled for %s', get_the_title( $post_id ) ) ); ?>"
				>
					<span class="elodin-schema-list-switch-track" aria-hidden="true">
						<span class="elodin-schema-list-switch-thumb"></span>
					</span>
					<span class="elodin-schema-list-toggle-state" aria-hidden="true"><?php echo esc_html( $is_enabled ? 'On' : 'Off' ); ?></span>
				</button>
			</div>
			<?php
			break;

		case 'target':
			echo esc_html( 'Entire site' );
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

	$local_entries        = array();
	$replaced_global_ids  = array();
	$queried_object_id    = get_queried_object_id();
	$queried_object       = $queried_object_id ? get_post( $queried_object_id ) : null;

	if ( $queried_object instanceof WP_Post && ELODIN_SCHEMA_POST_TYPE !== $queried_object->post_type ) {
		$local_context = elodin_schema_get_render_context( null, $queried_object );
		$local_context = apply_filters( 'elodin_schema_local_context', $local_context, $queried_object );
		$local_entries = elodin_schema_get_local_entries_for_output( $queried_object->ID, $local_context );

		foreach ( $local_entries as $local_entry ) {
			if ( ! empty( $local_entry['replace_global_id'] ) ) {
				$replaced_global_ids[] = (int) $local_entry['replace_global_id'];
			}
		}

		$replaced_global_ids = array_values( array_unique( $replaced_global_ids ) );
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

	$schema_posts = apply_filters( 'elodin_schema_posts', $schema_posts );

	foreach ( $schema_posts as $schema_post ) {
		if ( in_array( (int) $schema_post->ID, $replaced_global_ids, true ) ) {
			continue;
		}

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

	foreach ( $local_entries as $index => $local_entry ) {
		$label = ! empty( $local_entry['schema_type'] ) ? $local_entry['schema_type'] : sprintf( 'Post schema %d', $index + 1 );

		printf(
			"\n<!-- Elodin Schema: %s -->\n%s\n",
			esc_html( $label ),
			$local_entry['markup']
		);
	}
}

function elodin_schema_should_output( $post_id, $context = array() ) {
	$enabled            = get_post_meta( $post_id, ELODIN_SCHEMA_META_ENABLED, true );
	$target             = get_post_meta( $post_id, ELODIN_SCHEMA_META_TARGET, true );
	$validation_message = get_post_meta( $post_id, ELODIN_SCHEMA_META_VALIDATION_ERROR, true );

	if ( '' === $enabled ) {
		$enabled = '1';
	}

	if ( empty( $target ) ) {
		$target = 'entire_site';
	}

	$should_output = (
		'1' === $enabled &&
		'entire_site' === $target &&
		empty( $validation_message )
	);

	return (bool) apply_filters( 'elodin_schema_should_output_entry', $should_output, $post_id, $context );
}

function elodin_schema_get_rendered_markup( $schema_post, $context = array() ) {
	$script = get_post_meta( $schema_post->ID, ELODIN_SCHEMA_META_SCRIPT, true );
	return elodin_schema_get_markup_from_script( $script, $context, $schema_post );
}

function elodin_schema_get_local_entries_for_output( $post_id, $context = array() ) {
	$entries = elodin_schema_get_post_schema_entries( $post_id );
	$entries = apply_filters( 'elodin_schema_local_entries', $entries, $post_id, $context );

	$output_entries = array();

	foreach ( $entries as $entry ) {
		$is_enabled = ! isset( $entry['enabled'] ) || '1' === (string) $entry['enabled'];

		if ( ! $is_enabled || empty( $entry['script'] ) || ! empty( $entry['validation_error'] ) ) {
			continue;
		}

		$markup = elodin_schema_get_markup_from_script( $entry['script'], $context, null, $entry );
		if ( empty( $markup ) ) {
			continue;
		}

		$entry['markup'] = $markup;
		$output_entries[] = $entry;
	}

	return $output_entries;
}

function elodin_schema_get_markup_from_script( $script, $context = array(), $schema_post = null, $local_entry = null ) {
	if ( empty( $script ) ) {
		return '';
	}

	$validation = elodin_schema_validate_json_ld( $script );
	if ( ! $validation['valid'] || empty( $validation['data'] ) ) {
		return '';
	}

	$resolved = elodin_schema_replace_placeholders_recursive( $validation['data'], $context );

	if ( $schema_post instanceof WP_Post ) {
		$resolved = apply_filters( 'elodin_schema_resolved_data', $resolved, $schema_post, $context );
	} else {
		$resolved = apply_filters( 'elodin_schema_local_resolved_data', $resolved, $local_entry, $context );
	}

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

	if ( $schema_post instanceof WP_Post ) {
		return apply_filters( 'elodin_schema_markup', $markup, $schema_post, $context, $resolved );
	}

	return apply_filters( 'elodin_schema_local_markup', $markup, $local_entry, $context, $resolved );
}

function elodin_schema_get_render_context( $schema_post, $object = null, $request_url = '' ) {
	if ( null === $object ) {
		$object_id = get_queried_object_id();
		$object    = $object_id ? get_post( $object_id ) : null;
	} else {
		$object_id = $object instanceof WP_Post ? $object->ID : 0;
	}

	if ( '' === $request_url ) {
		$request_url = elodin_schema_get_current_url();
	}

	$context = array(
		'schema_post' => $schema_post,
		'request'     => array(
			'url' => $request_url,
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

function elodin_schema_ajax_toggle_enabled() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error(
			array(
				'message' => 'You are not allowed to update schema entries.',
			),
			403
		);
	}

	check_ajax_referer( 'elodin_schema_toggle_enabled', 'nonce' );

	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	if ( $post_id < 1 ) {
		wp_send_json_error(
			array(
				'message' => 'Missing schema entry.',
			),
			400
		);
	}

	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post || ELODIN_SCHEMA_POST_TYPE !== $post->post_type ) {
		wp_send_json_error(
			array(
				'message' => 'Invalid schema entry.',
			),
			400
		);
	}

	$enabled = isset( $_POST['enabled'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['enabled'] ) ) ? '1' : '0';
	update_post_meta( $post_id, ELODIN_SCHEMA_META_ENABLED, $enabled );

	wp_send_json_success(
		array(
			'enabled' => $enabled,
		)
	);
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
