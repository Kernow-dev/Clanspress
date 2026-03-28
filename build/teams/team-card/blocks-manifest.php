<?php
// This file is generated. Do not modify it manually.
return array(
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
	)
);
