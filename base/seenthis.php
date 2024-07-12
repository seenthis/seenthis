<?php

/*
 * Plugin Seenthis
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// lire directement une declaration SQL SHOW CREATE TABLE => SPIP
function seenthis_lire_create_table($x) {
	$m = ['field' => [], 'key' => []];

	foreach (explode("\n", $x) as $line) {
		$line = trim(preg_replace('/,$/', '', $line));
		if (preg_match('/^(PRIMARY KEY) \(`(.*?)`\)/', $line, $c)) {
			$m['key'][$c[1]] = $c[2];
		} elseif (preg_match('/^(KEY) `(.*?)`\s+\((.*?)\)/', $line, $c)) {
			$m['key'][$c[1] . ' ' . $c[2]] = $c[3];
		} elseif (preg_match('/^`(.*?)`\s+(.*?)$/', $line, $c)) {
			$m['field'][$c[1]] = str_replace('`', '', $c[2]);
		}
	}

	return $m;
}

function seenthis_declarer_tables_interfaces($interface) {
	// 'spip_' dans l'index de $tables_principales
	$interface['table_des_tables']['me'] = 'me';
	$interface['table_des_tables']['me_texte'] = 'me_texte';
	$interface['table_des_tables']['me_recherche'] = 'me_recherche';
	$interface['table_des_tables']['me_follow'] = 'me_follow';
	$interface['table_des_tables']['me_tags'] = 'me_tags';
	$interface['tables_jointures']['spip_me'][] = 'spip_me_follow';
	$interface['tables_jointures']['spip_me'][] = 'spip_me_block';

	$interface['table_des_traitements']['TEXTE']['spip_me'] = 'traiter_texte(%s)';

	return $interface;
}
function seenthis_declarer_tables_objets_surnoms($interface) {
	// 'spip_' dans l'index de $tables_principales
	$interface['me'] = 'me';
	$interface['me_texte'] = 'me_texte';
	$interface['me_recherche'] = 'me_recherche';
	$interface['me_follow'] = 'me_follow';

	return $interface;
}

function seenthis_declarer_tables_principales($tables_principales) {
	$tables_principales['spip_me'] = seenthis_lire_create_table("
		`id_me` bigint(21) NOT NULL AUTO_INCREMENT,
		`uuid` char(36) NOT NULL,
		`date` datetime NOT NULL,
		`date_modif` datetime NOT NULL,
		`date_parent` datetime NOT NULL,
		`id_auteur` bigint(21) NOT NULL,
		`id_parent` bigint(21) NOT NULL,
		`statut` varchar(5) NOT NULL DEFAULT 'oui',
		`ip` varchar(40) NOT NULL,
		`id_dest` bigint(21) NOT NULL,
		`troll` bigint(21) NOT NULL,
		`viarss` tinyint(1) NOT NULL DEFAULT '0',
		PRIMARY KEY (`id_me`),
		KEY `uuid` (`uuid`),
		KEY `id_auteur` (`id_auteur`),
		KEY `id_parent` (`id_parent`)
	");

	$tables_principales['spip_me_texte'] = seenthis_lire_create_table('
		`id_me` bigint(21) NOT NULL,
		`uuid` char(36) NOT NULL,
		`texte` longtext NOT NULL
		PRIMARY KEY (`id_me`),
		KEY `uuid` (`uuid`)
	');

	$tables_principales['spip_me_recherche'] = seenthis_lire_create_table('
		`id_me` bigint(21) NOT NULL AUTO_INCREMENT,
		`uuid` char(36) NOT NULL,
		`date` datetime NOT NULL,
		`id_auteur` bigint(21) NOT NULL,
		`titre` text NOT NULL,
		`texte` longtext NOT NULL,
		`troll` bigint(21) NOT NULL,
		PRIMARY KEY (`id_me`),
		KEY `uuid` (`uuid`),
		KEY `id_auteur` (`id_auteur`)
	');


	$tables_principales['spip_me_tags'] = seenthis_lire_create_table("
		`id_me` bigint(21) NOT NULL DEFAULT 0,
		`uuid` char(36) NOT NULL DEFAULT '',
		`tag` text NOT NULL DEFAULT '',
		`class` char(6) NOT NULL DEFAULT '',
		`date` datetime NOT NULL,
		`relevance` int(11) NOT NULL,
		`off` char(3) NOT NULL DEFAULT 'non',
		KEY (`id_me`), # pas de primary
		KEY `uuid` (`uuid`),
		KEY `date` (`date`),
		KEY `id_me` (`id_me`)");


	// ajouts dans spip_auteurs
	$auteurs = &$tables_principales['spip_auteurs'];
	$auteurs['field']['couleur'] = "varchar(6) NOT NULL DEFAULT '24b8dd'";
	$auteurs['field']['troll'] = 'bigint(21) DEFAULT NULL';
	$auteurs['field']['troll_forcer'] = "bigint(21) NOT NULL DEFAULT '0'";
	$auteurs['field']['copyright'] = "varchar(10) NOT NULL DEFAULT 'C'";
	$auteurs['field']['mail_nouv_billet'] = "tinyint(1) NOT NULL DEFAULT '1'";
	$auteurs['field']['mail_partage'] = "tinyint(1) NOT NULL DEFAULT '0'";
	$auteurs['field']['mail_rep_moi'] = "tinyint(1) NOT NULL DEFAULT '1'";
	$auteurs['field']['mail_rep_partage'] = "tinyint(1) NOT NULL DEFAULT '1'";
	$auteurs['field']['mail_rep_billet'] = "tinyint(1) NOT NULL DEFAULT '0'";
	$auteurs['field']['mail_rep_conv'] = "tinyint(1) NOT NULL DEFAULT '0'";
	$auteurs['field']['mail_suivre_moi'] = "tinyint(1) NOT NULL DEFAULT '1'";
	$auteurs['field']['mail_mes_billets'] = "tinyint(1) NOT NULL DEFAULT '0'";
	$auteurs['field']['mail_tag_suivi'] = "tinyint(1) NOT NULL DEFAULT '0'";
	$auteurs['field']['twitter'] = "varchar(100) NOT NULL DEFAULT ''";
	$auteurs['field']['facebook'] = "varchar(100) NOT NULL DEFAULT ''";
	$auteurs['field']['gplus'] = "varchar(100) NOT NULL DEFAULT ''";
	$auteurs['field']['liens_partage_fb'] = "tinyint(1) NOT NULL DEFAULT '0'";
	$auteurs['field']['liens_partage_tw'] = "tinyint(1) NOT NULL DEFAULT '0'";

	// ajouts dans spip_syndic
	$syndic = &$tables_principales['spip_syndic'];
	$syndic['field']['recup'] = "int(11) NOT NULL DEFAULT '0'";
	$syndic['field']['id_parent'] = 'bigint(21) DEFAULT NULL';
	$syndic['field']['titre'] = 'text NOT NULL';
	$syndic['field']['texte'] = 'longtext NOT NULL';
	$syndic['field']['lang'] = 'varchar(10) NOT NULL';
	$syndic['field']['md5'] = 'char(32) DEFAULT NULL'; # md5 de l'URL
	$syndic['key']['KEY id_parent'] = 'id_parent';
	$syndic['key']['KEY url'] = 'url_site(255)';
	$syndic['key']['KEY md5'] = 'md5';

	return $tables_principales;
}

function seenthis_declarer_tables_auxiliaires($tables_auxiliaires) {

	$tables_auxiliaires['spip_me_auteur'] = seenthis_lire_create_table(
		'
  `id_me` bigint(21) NOT NULL,
  `id_auteur` bigint(21) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `id_me` (`id_me`),
  KEY `id_auteur` (`id_auteur`),
  KEY `date` (`date`)
'
	);
	$tables_auxiliaires['spip_me_follow'] = seenthis_lire_create_table(
		"
  `id_follow` bigint(21) NOT NULL,
  `id_auteur` bigint(21) NOT NULL,
  `date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  KEY `id_follow` (`id_follow`),
  KEY `id_auteur` (`id_auteur`)
"
	);
	$tables_auxiliaires['spip_me_block'] = seenthis_lire_create_table(
		"
  `id_block` bigint(21) NOT NULL,
  `id_auteur` bigint(21) NOT NULL,
  `date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  KEY `id_block` (`id_block`),
  KEY `id_auteur` (`id_auteur`)
"
	);

	$tables_auxiliaires['spip_me_follow_tag'] = seenthis_lire_create_table(
		"
		`tag` text NOT NULL DEFAULT '',
		`id_follow` bigint(21) NOT NULL,
		`date` datetime NOT NULL,
		KEY `id_follow` (`id_follow`)
"
	);

	$tables_auxiliaires['spip_me_follow_url'] = seenthis_lire_create_table(
		'
  `id_syndic` bigint(21) NOT NULL,
  `id_follow` bigint(21) NOT NULL,
  `date` datetime NOT NULL,
  KEY `id_syndic` (`id_syndic`),
  KEY `id_follow` (`id_follow`)
'
	);

	$tables_auxiliaires['spip_me_share'] = seenthis_lire_create_table(
		'
  `id_me` bigint(20) NOT NULL,
  `id_auteur` bigint(20) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `id_me` (`id_me`),
  KEY `id_auteur` (`id_auteur`)
'
	);

	$tables_auxiliaires['spip_me_syndic'] = seenthis_lire_create_table(
		"
  `id_me` bigint(21) NOT NULL,
  `id_syndic` bigint(21) NOT NULL,
  `date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  KEY `date` (`date`),
  KEY `id_me` (`id_me`),
  KEY `id_syndic` (`id_syndic`)
"
	);

	# pour google
	$tables_auxiliaires['spip_traductions'] = seenthis_lire_create_table(
		'
  `hash` varchar(32) NOT NULL,
  `langue` varchar(5) NOT NULL,
  `texte` text NOT NULL,
  KEY `hash` (`hash`)
'
	);

	return $tables_auxiliaires;
}

// champs extras pour afficher tous les champs supplémentaires d'un auteur
// dans l'espace privé (si on active le plugin champ_extras :
// svn co svn://zone.spip.org/spip-zone/_plugins_/champs_extras_core/trunk champs_extras_core/
function seenthis_declarer_champs_extras($champs = []) {

	$champs['spip_auteurs']['copyright'] = [
		'saisie' => 'input',
		'options' => [
			'nom' => 'copyright',
			'label' => 'Licence',
			'obligatoire' => false,
			'rechercher' => false,
			'sql' => "varchar(10) DEFAULT 'C'",
		]
	];

	foreach (explode(' ', 'mail_nouv_billet mail_partage mail_rep_moi mail_rep_partage mail_rep_billet mail_rep_conv mail_suivre_moi mail_mes_billets liens_partage_fb liens_partage_tw') as $c) {
	$champs['spip_auteurs'][$c] = [
		'saisie' => 'input',
		'options' => [
			'nom' => $c,
			'label' => $c,
			'obligatoire' => false,
			'rechercher' => false,
			'sql' => 'tinyint(1) NOT NULL',
		]
	];
	}

	foreach (explode(' ', 'troll troll_forcer') as $c) {
	$champs['spip_auteurs'][$c] = [
		'saisie' => 'input',
		'options' => [
			'nom' => $c,
			'label' => $c,
			'obligatoire' => false,
			'rechercher' => false,
			'sql' => 'BIGINT(21)',
		]
	];
	}


	foreach (explode(' ', 'twitter facebook gplus') as $c) {
	$champs['spip_auteurs'][$c] = [
		'saisie' => 'input',
		'options' => [
			'nom' => $c,
			'label' => $c,
			'obligatoire' => false,
			'rechercher' => false,
			'sql' => "VARCHAR(100) NOT NULL DEFAULT ''",
		]
	];
	}

	return $champs;
}
