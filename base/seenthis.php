<?php
/*
 * Plugin Seenthis
 *
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

// lire directement une declaration SQL SHOW CREATE TABLE => SPIP
function seenthis_lire_create_table($x) {
	$m = array('field' => array(), 'key' => array());

	foreach(explode("\n", $x) as $line) {
		$line = trim(preg_replace('/,$/', '', $line));
		if (preg_match("/^(PRIMARY KEY) \(`(.*?)`\)/", $line, $c)) {
			$m['key'][$c[1]] = $c[2];
		}
		elseif (preg_match("/^(KEY) `(.*?)`\s+\((.*?)\)/", $line, $c)) {
			$m['key'][$c[1]." ".$c[2]] = $c[3];
		}
		elseif (preg_match("/^`(.*?)`\s+(.*?)$/", $line, $c)) {
			$m['field'][$c[1]] = str_replace('`', '', $c[2]);
		}
	}

	return $m;
}




function seenthis_declarer_tables_interfaces($interface){

	// 'spip_' dans l'index de $tables_principales
	$interface['table_des_tables']['me']='me';
	$interface['table_des_tables']['me_follow']='me_follow';
	$interface['tables_jointures']['spip_me'][] = 'spip_me_follow';		
	
	return $interface;
}
function seenthis_declarer_tables_objets_surnoms($interface){
	// 'spip_' dans l'index de $tables_principales
	$interface['me']='me';
	$interface['me_follow']='me_follow';
		
	return $interface;
}

function seenthis_declarer_tables_principales($tables_principales){
	$tables_principales['spip_me'] = seenthis_lire_create_table("
  `id_me` bigint(21) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `id_auteur` bigint(21) NOT NULL,
  `id_parent` bigint(21) NOT NULL,
  `texte` longtext NOT NULL,
  `themes` text NOT NULL,
  `statut` varchar(5) NOT NULL DEFAULT 'oui',
  `ip` varchar(40) NOT NULL,
  `id_dest` bigint(21) NOT NULL,
  `id_mot` bigint(21) NOT NULL,
  `troll` bigint(21) NOT NULL,
  PRIMARY KEY (`id_me`),
  KEY `id_auteur` (`id_auteur`),
  KEY `id_parent` (`id_parent`)
"
	);

	// ajouts dans spip_auteurs
	$auteurs = &$tables_principales['spip_auteurs'];
	$auteurs['field']['couleur'] = "varchar(6) NOT NULL DEFAULT '24b8dd'";
	$auteurs['field']['troll'] = "bigint(21) DEFAULT NULL";
	$auteurs['field']['troll_forcer'] = "bigint(21) DEFAULT NULL";
	$auteurs['field']['copyright'] = "varchar(10) NOT NULL DEFAULT 'C'";
	$auteurs['field']['mail_nouv_billet'] = "tinyint(1) NOT NULL DEFAULT '1'";
	$auteurs['field']['mail_rep_moi'] = "tinyint(1) NOT NULL DEFAULT '1'";
	$auteurs['field']['mail_rep_billet'] = "tinyint(1) NOT NULL DEFAULT '0'";
	$auteurs['field']['mail_rep_conv'] = "tinyint(1) NOT NULL DEFAULT '0'";

	// ajouts dans spip_mots
	$mots = &$tables_principales['spip_mots'];
	$mots['field']['id_parent'] = "bigint(21) NOT NULL default '0'";
	$mots['key']['KEY id_parent'] = "id_parent";

	return $tables_principales;
}

function seenthis_declarer_tables_auxiliaires($tables_auxiliaires){

	$tables_auxiliaires['spip_me_auteur'] = seenthis_lire_create_table(
	"
  `id_me` bigint(21) NOT NULL,
  `id_auteur` bigint(21) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `id_me` (`id_me`),
  KEY `id_auteur` (`id_auteur`),
  KEY `date` (`date`)
"
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
	$tables_auxiliaires['spip_me_follow_mot'] = seenthis_lire_create_table(
	"
  `id_mot` bigint(21) NOT NULL,
  `id_follow` bigint(21) NOT NULL,
  `date` datetime NOT NULL,
  KEY `id_mot` (`id_mot`),
  KEY `id_follow` (`id_follow`)
"
	);
	$tables_auxiliaires['spip_me_mot'] = seenthis_lire_create_table(
	"
  `id_me` bigint(21) NOT NULL,
  `id_mot` bigint(21) NOT NULL,
  `date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `relevance` int(11) NOT NULL,
  KEY `id_me` (`id_me`),
  KEY `id_mot` (`id_mot`)
"
	);

	$tables_auxiliaires['spip_me_share'] = seenthis_lire_create_table(
	"
  `id_me` bigint(20) NOT NULL,
  `id_auteur` bigint(20) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `id_me` (`id_me`),
  KEY `id_auteur` (`id_auteur`)
"
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
	
	$tables_auxiliaires['spip_syndic_oc'] = seenthis_lire_create_table(
	"
  `id_syndic` bigint(21) NOT NULL,
  `id_mot` bigint(21) NOT NULL,
  `relevance` int(11) NOT NULL,
  KEY `id_syndic` (`id_syndic`),
  KEY `id_mot` (`id_mot`)
"
	);

	# pour google
	$tables_auxiliaires['spip_traductions'] = seenthis_lire_create_table(
	"
  `hash` varchar(32) NOT NULL,
  `langue` varchar(5) NOT NULL,
  `texte` text NOT NULL,
  KEY `hash` (`hash`)
"
  );

	return $tables_auxiliaires;

}

function seenthis_upgrade($nom_meta_base_version,$version_cible){
	$current_version = 0.0;
	if ((!isset($GLOBALS['meta'][$nom_meta_base_version]) )
	|| (($current_version = $GLOBALS['meta'][$nom_meta_base_version])!=$version_cible)){
		include_spip('base/abstract_sql');
		if (version_compare($current_version,"0.2.0",'<')){
			include_spip('base/serial');
			include_spip('base/auxiliaires');
			include_spip('base/create');
			creer_base();
			ecrire_meta($nom_meta_base_version,$current_version=$version_cible,'non');
		}
	}
}

function seenthis_vider_tables($nom_meta_base_version) {
	effacer_meta($nom_meta_base_version);
	sql_drop_table("spip_me");
	sql_drop_table("spip_me_auteur");
	sql_drop_table("spip_me_follow");
	sql_drop_table("spip_me_follow_mot");
	sql_drop_table("spip_me_mot");
	sql_drop_table("spip_me_share");
	sql_drop_table("spip_me_syndic");
	sql_drop_table("spip_syndic_oc");
	sql_drop_table("spip_traductions");
}


function seenthis_install($action,$prefix,$version_cible){
	$version_base = $GLOBALS[$prefix."_base_version"];
	switch ($action){
		case 'test':
			$ok = (isset($GLOBALS['meta'][$prefix."_base_version"])
				AND version_compare($GLOBALS['meta'][$prefix."_base_version"],$version_cible,">="));
			return $ok;
			break;
		case 'install':
			seenthis_upgrade($prefix."_base_version",$version_cible);
			break;
		case 'uninstall':
			seenthis_vider_tables($prefix."_base_version");
			break;
	}
}

?>