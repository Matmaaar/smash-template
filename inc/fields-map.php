<?php
/**
 * Correspondance entre les données du thème et les champs du site.
 *
 * C'EST LE FICHIER À ADAPTER LE JOUR DE LA MISE EN LIGNE : remplacer chaque `key`
 * par le nom réel du champ ACF (export ACF du site), et vérifier son `type`.
 * Les templates ne lisent jamais un champ directement : ils passent par inc/data.php.
 *
 * Types reconnus : text, html, number, image (ID, URL ou tableau ACF acceptés).
 */

defined( 'ABSPATH' ) || exit;

return array(
	// Fiches bières (CPT `bieres`).
	'beer'   => array(
		'label'   => array( 'key' => 'banderole',   'type' => 'image',  'label' => 'Étiquette (bannière 2:1)' ),
		'photo'   => array( 'key' => 'photo',       'type' => 'image',  'label' => 'Photo' ),
		'style'   => array( 'key' => 'style',       'type' => 'text',   'label' => 'Style' ),
		'abv'     => array( 'key' => 'degre',       'type' => 'number', 'label' => 'Degré (%)' ),
		'formats' => array( 'key' => 'formats',     'type' => 'text',   'label' => 'Formats' ),
		'intro'   => array( 'key' => 'accroche',    'type' => 'text',   'label' => 'Accroche' ),
		'desc'    => array( 'key' => 'description', 'type' => 'html',   'label' => 'Description' ),
		'malts'   => array( 'key' => 'malt',        'type' => 'text',   'label' => 'Malts' ),
		'hops'    => array( 'key' => 'houblons',    'type' => 'text',   'label' => 'Houblons' ),
		'yeast'   => array( 'key' => 'levure',      'type' => 'text',   'label' => 'Levure' ),
		'other'   => array( 'key' => 'other',       'type' => 'text',   'label' => 'Autres ingrédients' ),
		'look'    => array( 'key' => 'aspect',      'type' => 'text',   'label' => 'Aspect' ),
		'when'    => array( 'key' => 'degustation', 'type' => 'html',   'label' => 'Conditions de dégustation' ),
		'food'    => array( 'key' => 'accords',     'type' => 'html',   'label' => 'Accords mets' ),
		// Démo seulement : en production, les prix des fûts viennent des produits Gravity Forms (gf_location).
		'keg_price'  => array( 'key' => 'prix_fut',   'type' => 'number', 'label' => 'Location : prix du fût (€, démo)' ),
		'keg_volume' => array( 'key' => 'volume_fut', 'type' => 'text',   'label' => 'Location : volume du fût (démo)' ),
	),

	// Équipe (CPT `membre`).
	'member' => array(
		'role'  => array( 'key' => 'poste', 'type' => 'text',  'label' => 'Poste' ),
		'bio'   => array( 'key' => 'bio',   'type' => 'html',  'label' => 'Présentation' ),
		'photo' => array( 'key' => 'photo', 'type' => 'image', 'label' => 'Photo' ),
	),

	// Gammes : clé du thème => slug du terme dans la taxonomie `type-de-bieres`.
	'ranges' => array(
		'perm' => 'bieres-permanentes',
		'eph'  => 'bieres-ephemeres',
		'old'  => 'bieres-archivees',
	),

	// Gravity Forms (relevé public du 29/10/2026 ; à revérifier dans l'admin).
	'gf'     => array(
		'location'   => 7,
		'contact'    => 3,
		'newsletter' => 2,
	),

	// Formulaire de location (n° 7) : ID des champs. À REVÉRIFIER LE JOUR J (export du formulaire).
	'gf_location' => array(
		'who'        => 52, // boutons radio : 1er choix = particulier, 2e choix = pro
		'name'       => 25,
		'email'      => 26,
		'phone'      => 27,
		'event'      => 54,
		'pickup'     => 28,
		'return'     => 70,
		'notes'      => 41,
		'taps'       => array( 't1' => 62, 't2' => 63 ),
		// Fûts : slug de la bière => [ champ produit particulier, champ produit pro ].
		'kegs'       => array(
			'aquatrip'  => array( 5, 55 ),
			'affluence' => array( 7, 56 ),
			'pasadena'  => array( 71, 60 ),
			'weizh'     => array( 10, 57 ),
			'sp-95'     => array( 9, 58 ),
			'rye-bomb'  => array( 8, 59 ),
		),
		// Écocups : contenance => [ particulier, pro ].
		'cups'       => array(
			'20' => array( 73, 76 ),
			'50' => array( 74, 77 ),
		),
	),
);
