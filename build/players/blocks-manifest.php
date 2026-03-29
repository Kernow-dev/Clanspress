<?php
// This file is generated. Do not modify it manually.
return array(
	'player-avatar' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/player-avatar',
		'version' => '0.1.0',
		'title' => 'Player Avatar',
		'category' => 'clanspress-players',
		'icon' => 'smiley',
		'description' => 'Example block scaffolded with Create Block tool.',
		'example' => array(
			
		),
		'supports' => array(
			'html' => false,
			'interactivity' => true
		),
		'textdomain' => 'player-avatar',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php',
		'viewScriptModule' => 'file:./view.js'
	),
	'player-cover' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/player-cover',
		'version' => '1.0.0',
		'title' => 'Player Cover',
		'category' => 'clanspress-players',
		'icon' => 'smiley',
		'description' => 'Example block scaffolded with Create Block tool.',
		'example' => array(
			
		),
		'textdomain' => 'player-cover',
		'attributes' => array(
			'id' => array(
				'type' => 'number'
			),
			'minHeight' => array(
				'type' => 'number'
			),
			'minHeightUnit' => array(
				'type' => 'string'
			),
			'contentPosition' => array(
				'type' => 'string'
			),
			'templateLock' => array(
				'type' => array(
					'string',
					'boolean'
				),
				'enum' => array(
					'all',
					'insert',
					'contentOnly',
					false
				)
			),
			'sizeSlug' => array(
				'type' => 'string'
			)
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'supports' => array(
			'anchor' => true,
			'align' => true,
			'html' => false,
			'shadow' => true,
			'spacing' => array(
				'padding' => true,
				'margin' => array(
					'top',
					'bottom'
				),
				'blockGap' => true,
				'__experimentalDefaultControls' => array(
					'padding' => true,
					'blockGap' => true
				)
			),
			'__experimentalBorder' => array(
				'color' => true,
				'radius' => true,
				'style' => true,
				'width' => true,
				'__experimentalDefaultControls' => array(
					'color' => true,
					'radius' => true,
					'style' => true,
					'width' => true
				)
			),
			'color' => array(
				'heading' => true,
				'text' => true,
				'background' => false,
				'__experimentalSkipSerialization' => array(
					'gradients'
				),
				'enableContrastChecker' => false
			),
			'dimensions' => array(
				'aspectRatio' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'layout' => array(
				'allowJustification' => false
			),
			'interactivity' => true,
			'filter' => array(
				'duotone' => true
			),
			'allowedBlocks' => true
		),
		'selectors' => array(
			'filter' => array(
				'duotone' => '.wp-block-clanspress-player-cover > .player-cover__image-background, .wp-block-clanspress-player-cover > .player-cover__video-background'
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php',
		'viewScriptModule' => 'file:./view.js'
	),
	'player-settings' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/player-settings',
		'version' => '0.1.0',
		'title' => 'Player Settings',
		'category' => 'clanspress',
		'icon' => 'smiley',
		'description' => 'Block for outputting player settings for their profile.',
		'example' => array(
			
		),
		'supports' => array(
			'html' => false,
			'interactivity' => true
		),
		'textdomain' => 'clanspress',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php',
		'viewScriptModule' => 'file:./view.js'
	)
);
