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
		'usesContext' => array(
			'clanspress/playerId'
		),
		'attributes' => array(
			'isLink' => array(
				'type' => 'boolean',
				'default' => false
			),
			'linkTarget' => array(
				'type' => 'string',
				'default' => '_self'
			),
			'rel' => array(
				'type' => 'string',
				'default' => ''
			)
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
	'player-country' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/player-country',
		'version' => '0.1.0',
		'title' => 'Player country',
		'category' => 'clanspress-players',
		'icon' => 'admin-site-alt3',
		'description' => 'Displays the player country on player profile and roster templates.',
		'textdomain' => 'clanspress',
		'usesContext' => array(
			'postId',
			'postType',
			'clanspress/playerId'
		),
		'attributes' => array(
			'showCode' => array(
				'type' => 'boolean',
				'default' => false
			),
			'countryDisplay' => array(
				'type' => 'string',
				'default' => 'both'
			),
			'flagFirst' => array(
				'type' => 'boolean',
				'default' => true
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
			'postType',
			'clanspress/playerId'
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
	'player-display-name' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/player-display-name',
		'version' => '0.1.0',
		'title' => 'Player display name',
		'category' => 'clanspress-players',
		'icon' => 'heading',
		'description' => 'Displays the player display name on player profile templates.',
		'textdomain' => 'clanspress',
		'usesContext' => array(
			'postId',
			'postType',
			'clanspress/playerId'
		),
		'attributes' => array(
			'textAlign' => array(
				'type' => 'string'
			),
			'isLink' => array(
				'type' => 'boolean',
				'default' => false
			),
			'linkTarget' => array(
				'type' => 'string',
				'default' => '_self'
			),
			'rel' => array(
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
			'color' => array(
				'link' => true,
				'__experimentalDefaultControls' => array(
					'link' => true
				)
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontStyle' => true,
				'__experimentalFontWeight' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalTextTransform' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./editor.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'player-query' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/player-query',
		'version' => '0.1.0',
		'title' => 'Player query',
		'category' => 'clanspress-players',
		'icon' => 'groups',
		'description' => 'Loops members of a team. Add a Player template inside, then place player blocks (avatar, display name, …) within the template.',
		'textdomain' => 'clanspress',
		'attributes' => array(
			'teamId' => array(
				'type' => 'number',
				'default' => 0
			),
			'inheritTeamContext' => array(
				'type' => 'boolean',
				'default' => true
			),
			'excludeBannedMembers' => array(
				'type' => 'boolean',
				'default' => true
			),
			'queryOrderby' => array(
				'type' => 'string',
				'default' => 'default'
			),
			'queryOrder' => array(
				'type' => 'string',
				'default' => 'ASC'
			),
			'queryMetaKey' => array(
				'type' => 'string',
				'default' => ''
			),
			'queryPerPage' => array(
				'type' => 'number',
				'default' => 0
			),
			'queryOffset' => array(
				'type' => 'number',
				'default' => 0
			),
			'queryMetaQueryJson' => array(
				'type' => 'string',
				'default' => ''
			),
			'queryExcludeUsers' => array(
				'type' => 'string',
				'default' => ''
			),
			'queryExcludeCurrentUser' => array(
				'type' => 'boolean',
				'default' => false
			),
			'queryExcludeRoles' => array(
				'type' => 'string',
				'default' => ''
			),
			'queryExcludeMetaQueryJson' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'providesContext' => array(
			'clanspress/teamId' => 'teamId',
			'clanspress/inheritTeamContext' => 'inheritTeamContext',
			'clanspress/excludeBannedMembers' => 'excludeBannedMembers',
			'clanspress/queryOrderby' => 'queryOrderby',
			'clanspress/queryOrder' => 'queryOrder',
			'clanspress/queryMetaKey' => 'queryMetaKey',
			'clanspress/queryPerPage' => 'queryPerPage',
			'clanspress/queryOffset' => 'queryOffset',
			'clanspress/queryMetaQueryJson' => 'queryMetaQueryJson',
			'clanspress/queryExcludeUsers' => 'queryExcludeUsers',
			'clanspress/queryExcludeCurrentUser' => 'queryExcludeCurrentUser',
			'clanspress/queryExcludeRoles' => 'queryExcludeRoles',
			'clanspress/queryExcludeMetaQueryJson' => 'queryExcludeMetaQueryJson'
		),
		'supports' => array(
			'html' => false,
			'align' => array(
				'wide',
				'full'
			)
		),
		'editorScript' => 'file:./index.js'
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
	),
	'player-settings-link' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/player-settings-link',
		'version' => '0.1.0',
		'title' => 'Player settings link',
		'category' => 'clanspress-players',
		'icon' => 'admin-settings',
		'description' => 'Link to player account settings. Only shown when the viewer is the profile owner.',
		'textdomain' => 'clanspress',
		'usesContext' => array(
			'postId',
			'postType',
			'clanspress/playerId'
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
		'editorStyle' => 'file:./editor.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'player-template' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/player-template',
		'version' => '0.1.0',
		'title' => 'Player template',
		'category' => 'clanspress-players',
		'icon' => 'list-view',
		'description' => 'Layout repeated for each team member. Use only inside a Player query block.',
		'textdomain' => 'clanspress',
		'ancestor' => array(
			'clanspress/player-query'
		),
		'usesContext' => array(
			'clanspress/teamId',
			'clanspress/inheritTeamContext',
			'clanspress/excludeBannedMembers',
			'clanspress/queryOrderby',
			'clanspress/queryOrder',
			'clanspress/queryMetaKey',
			'clanspress/queryPerPage',
			'clanspress/queryOffset',
			'clanspress/queryMetaQueryJson',
			'clanspress/queryExcludeUsers',
			'clanspress/queryExcludeCurrentUser',
			'clanspress/queryExcludeRoles',
			'clanspress/queryExcludeMetaQueryJson',
			'postId',
			'postType'
		),
		'supports' => array(
			'anchor' => true,
			'reusable' => false,
			'html' => false,
			'align' => array(
				'wide',
				'full'
			),
			'layout' => true,
			'color' => array(
				'gradients' => true,
				'link' => true,
				'__experimentalDefaultControls' => array(
					'background' => true,
					'text' => true
				)
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
			'spacing' => array(
				'margin' => true,
				'padding' => true,
				'blockGap' => array(
					'__experimentalDefault' => '1.25em'
				),
				'__experimentalDefaultControls' => array(
					'blockGap' => true,
					'padding' => false,
					'margin' => false
				)
			),
			'__experimentalBorder' => array(
				'radius' => true,
				'color' => true,
				'width' => true,
				'style' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'profile-nav' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/player-profile-nav',
		'title' => 'Player Profile Navigation',
		'category' => 'clanspress-players',
		'icon' => 'groups',
		'description' => 'Displays the player profile subpage navigation.',
		'supports' => array(
			'html' => false,
			'spacing' => array(
				'blockGap' => true
			)
		),
		'textdomain' => 'clanspress',
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'user-nav' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/user-nav',
		'version' => '1.0.0',
		'title' => 'User Navigation',
		'category' => 'clanspress',
		'icon' => 'admin-users',
		'description' => 'Displays login/register links for guests, or user avatar with dropdown menu for logged-in users.',
		'supports' => array(
			'html' => false,
			'align' => false,
			'className' => true,
			'interactivity' => true
		),
		'attributes' => array(
			'avatarSize' => array(
				'type' => 'number',
				'default' => 32
			),
			'showUsername' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'textdomain' => 'clanspress',
		'editorScript' => 'file:./index.js',
		'viewScriptModule' => 'file:./view.js',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	)
);
