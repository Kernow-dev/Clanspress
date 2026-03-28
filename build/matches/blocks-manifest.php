<?php
// This file is generated. Do not modify it manually.
return array(
	'match-card' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/match-card',
		'version' => '0.1.0',
		'title' => 'Match card',
		'category' => 'clanspress-matches',
		'icon' => 'calendar-alt',
		'description' => 'Display one match (title, teams, time, score).',
		'textdomain' => 'clanspress',
		'supports' => array(
			'html' => false,
			'align' => true
		),
		'attributes' => array(
			'matchId' => array(
				'type' => 'number',
				'default' => 0
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'match-list' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/match-list',
		'version' => '0.1.0',
		'title' => 'Match list',
		'category' => 'clanspress-matches',
		'icon' => 'list-view',
		'description' => 'List published matches with optional filters.',
		'textdomain' => 'clanspress',
		'supports' => array(
			'html' => false,
			'align' => true
		),
		'attributes' => array(
			'teamId' => array(
				'type' => 'number',
				'default' => 0
			),
			'limit' => array(
				'type' => 'number',
				'default' => 0
			),
			'statusFilter' => array(
				'type' => 'string',
				'default' => ''
			),
			'order' => array(
				'type' => 'string',
				'default' => 'asc'
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	)
);
