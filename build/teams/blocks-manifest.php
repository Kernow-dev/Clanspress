<?php
// This file is generated. Do not modify it manually.
return array(
	'team-avatar' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/team-avatar',
		'version' => '0.1.0',
		'title' => 'Team avatar',
		'category' => 'clanspress-teams',
		'icon' => 'admin-users',
		'description' => 'Displays the team avatar image on single team templates.',
		'textdomain' => 'clanspress',
		'postTypes' => array(
			'cp_team'
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'attributes' => array(
			'width' => array(
				'type' => 'number',
				'default' => 120
			)
		),
		'supports' => array(
			'html' => false,
			'spacing' => array(
				'margin' => true,
				'padding' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'team-card' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/team-card',
		'version' => '0.1.0',
		'title' => 'Team Card',
		'category' => 'clanspress-teams',
		'icon' => 'groups',
		'description' => 'Display a simple team profile card.',
		'textdomain' => 'clanspress',
		'supports' => array(
			'html' => false
		),
		'attributes' => array(
			'teamName' => array(
				'type' => 'string',
				'default' => 'Unnamed Team'
			),
			'gameTitle' => array(
				'type' => 'string',
				'default' => ''
			),
			'description' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css'
	),
	'team-code' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/team-code',
		'version' => '0.1.0',
		'title' => 'Team code',
		'category' => 'clanspress-teams',
		'icon' => 'tag',
		'description' => 'Displays the short team code.',
		'textdomain' => 'clanspress',
		'postTypes' => array(
			'cp_team'
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'supports' => array(
			'html' => false,
			'spacing' => array(
				'margin' => true,
				'padding' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'team-country' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/team-country',
		'version' => '0.1.0',
		'title' => 'Team country',
		'category' => 'clanspress-teams',
		'icon' => 'admin-site-alt3',
		'description' => 'Displays the team country on single team templates.',
		'textdomain' => 'clanspress',
		'postTypes' => array(
			'cp_team'
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'attributes' => array(
			'showCode' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'supports' => array(
			'html' => false,
			'spacing' => array(
				'margin' => true,
				'padding' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'team-cover' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/team-cover',
		'version' => '0.1.0',
		'title' => 'Team cover',
		'category' => 'clanspress-teams',
		'icon' => 'format-image',
		'description' => 'Displays the team cover image on single team templates.',
		'textdomain' => 'clanspress',
		'postTypes' => array(
			'cp_team'
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'attributes' => array(
			'minHeight' => array(
				'type' => 'string',
				'default' => '220px'
			),
			'contentPosition' => array(
				'type' => 'string',
				'default' => 'bottom center'
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
			)
		),
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'align' => array(
				'wide',
				'full'
			),
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
			'allowedBlocks' => true
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'team-create-form' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/team-create-form',
		'version' => '0.1.0',
		'title' => 'Team Create Form',
		'category' => 'clanspress',
		'icon' => 'welcome-write-blog',
		'description' => 'Render a block-based team creation form.',
		'textdomain' => 'clanspress',
		'supports' => array(
			'html' => false,
			'interactivity' => true
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php',
		'viewScriptModule' => 'file:./view.js'
	),
	'team-description' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/team-description',
		'version' => '0.1.0',
		'title' => 'Team description',
		'category' => 'clanspress-teams',
		'icon' => 'text-page',
		'description' => 'Displays the team description (post content).',
		'textdomain' => 'clanspress',
		'postTypes' => array(
			'cp_team'
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'supports' => array(
			'html' => false,
			'align' => array(
				'wide',
				'full'
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'team-draws' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/team-draws',
		'version' => '0.1.0',
		'title' => 'Team draws',
		'category' => 'clanspress-teams',
		'icon' => 'minus',
		'description' => 'Displays the team’s draw count with optional prefix and postfix text.',
		'textdomain' => 'clanspress',
		'postTypes' => array(
			'cp_team'
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'attributes' => array(
			'prefix' => array(
				'type' => 'string',
				'default' => ''
			),
			'postfix' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'supports' => array(
			'html' => false,
			'spacing' => array(
				'margin' => true,
				'padding' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'team-losses' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/team-losses',
		'version' => '0.1.0',
		'title' => 'Team losses',
		'category' => 'clanspress-teams',
		'icon' => 'dismiss',
		'description' => 'Displays the team’s loss count with optional prefix and postfix text.',
		'textdomain' => 'clanspress',
		'postTypes' => array(
			'cp_team'
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'attributes' => array(
			'prefix' => array(
				'type' => 'string',
				'default' => ''
			),
			'postfix' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'supports' => array(
			'html' => false,
			'spacing' => array(
				'margin' => true,
				'padding' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'team-manage-link' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/team-manage-link',
		'version' => '0.1.0',
		'title' => 'Manage team link',
		'category' => 'clanspress-teams',
		'icon' => 'admin-tools',
		'description' => 'Link to the team manage screen. Only shown to users who can edit the team.',
		'textdomain' => 'clanspress',
		'postTypes' => array(
			'cp_team'
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'attributes' => array(
			'label' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'supports' => array(
			'html' => false,
			'align' => true,
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'typography' => array(
				'fontSize' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'team-members-count' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/team-members-count',
		'version' => '0.1.0',
		'title' => 'Team members count',
		'category' => 'clanspress-teams',
		'icon' => 'groups',
		'description' => 'Displays the number of roster members (excluding banned).',
		'textdomain' => 'clanspress',
		'postTypes' => array(
			'cp_team'
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'attributes' => array(
			'label' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'supports' => array(
			'html' => false,
			'spacing' => array(
				'margin' => true,
				'padding' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'team-motto' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/team-motto',
		'version' => '0.1.0',
		'title' => 'Team motto',
		'category' => 'clanspress-teams',
		'icon' => 'format-quote',
		'description' => 'Displays the team motto.',
		'textdomain' => 'clanspress',
		'postTypes' => array(
			'cp_team'
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'supports' => array(
			'html' => false,
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'typography' => array(
				'fontSize' => true,
				'fontStyle' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'team-name' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/team-name',
		'version' => '0.1.0',
		'title' => 'Team name',
		'category' => 'clanspress-teams',
		'icon' => 'heading',
		'description' => 'Displays the team name (post title) on single team templates.',
		'textdomain' => 'clanspress',
		'postTypes' => array(
			'cp_team'
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'attributes' => array(
			'level' => array(
				'type' => 'number',
				'default' => 1
			),
			'textAlign' => array(
				'type' => 'string'
			)
		),
		'supports' => array(
			'html' => false,
			'align' => true,
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'team-wins' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/team-wins',
		'version' => '0.1.0',
		'title' => 'Team wins',
		'category' => 'clanspress-teams',
		'icon' => 'yes-alt',
		'description' => 'Displays the team’s win count with optional prefix and postfix text.',
		'textdomain' => 'clanspress',
		'postTypes' => array(
			'cp_team'
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'attributes' => array(
			'prefix' => array(
				'type' => 'string',
				'default' => ''
			),
			'postfix' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'supports' => array(
			'html' => false,
			'spacing' => array(
				'margin' => true,
				'padding' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	)
);
