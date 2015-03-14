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


function balise_TEXTE ($p) {
	if (in_array($p->type_requete, array('spip_me', 'me'))) {
		$_id_me = champ_sql('id_me', $p);
		$p->code = "texte_de_me($_id_me)";
		$p->interdire_scripts = true;
		return $p;
	}
	else {
		$f = charger_fonction('DEFAUT', 'calculer_balise');
		return $f('TEXTE', $p);
	}
}


function seenthis_declarer_tables_interfaces($interface){

	// 'spip_' dans l'index de $tables_principales
	$interface['table_des_tables']['me']='me';
	$interface['table_des_tables']['me_texte']='me_texte';
	$interface['table_des_tables']['me_recherche']='me_recherche';
	$interface['table_des_tables']['me_follow']='me_follow';
	$interface['tables_jointures']['spip_me'][] = 'spip_me_follow';		
	$interface['tables_jointures']['spip_me'][] = 'spip_me_block';		

	$interface['table_des_traitements']['TEXTE']['spip_me']= 'traiter_texte(%s)';

	return $interface;
}
function seenthis_declarer_tables_objets_surnoms($interface){
	// 'spip_' dans l'index de $tables_principales
	$interface['me']='me';
	$interface['me_texte']='me_texte';
	$interface['me_recherche']='me_recherche';
	$interface['me_follow']='me_follow';
		
	return $interface;
}

function seenthis_declarer_tables_principales($tables_principales){
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
		PRIMARY KEY (`id_me`),
		KEY `uuid` (`uuid`),
		KEY `id_auteur` (`id_auteur`),
		KEY `id_parent` (`id_parent`)
	"
	);

	$tables_principales['spip_me_texte'] = seenthis_lire_create_table("
		`id_me` bigint(21) NOT NULL,
		`uuid` char(36) NOT NULL,
		`texte` longtext NOT NULL
		PRIMARY KEY (`id_me`),
		KEY `uuid` (`uuid`)
	"
	);

	$tables_principales['spip_me_recherche'] = seenthis_lire_create_table("
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
	"
	);


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
		KEY `id_me` (`id_me`)"
	## SPIP 2.1 n'accepte pas les KEY avec (60)
	## on l'ajoute a la main plus bas
	#		KEY `tag` (`tag`(60)),
	);


	// ajouts dans spip_auteurs
	$auteurs = &$tables_principales['spip_auteurs'];
	$auteurs['field']['couleur'] = "varchar(6) NOT NULL DEFAULT '24b8dd'";
	$auteurs['field']['troll'] = "bigint(21) DEFAULT NULL";
	$auteurs['field']['troll_forcer'] = "bigint(21) DEFAULT NULL";
	$auteurs['field']['copyright'] = "varchar(10) NOT NULL DEFAULT 'C'";
	$auteurs['field']['mail_nouv_billet'] = "tinyint(1) NOT NULL DEFAULT '1'";
	$auteurs['field']['mail_partage'] = "tinyint(1) NOT NULL DEFAULT '0'";
	$auteurs['field']['mail_rep_moi'] = "tinyint(1) NOT NULL DEFAULT '1'";
	$auteurs['field']['mail_rep_partage'] = "tinyint(1) NOT NULL DEFAULT '1'";
	$auteurs['field']['mail_rep_billet'] = "tinyint(1) NOT NULL DEFAULT '0'";
	$auteurs['field']['mail_rep_conv'] = "tinyint(1) NOT NULL DEFAULT '0'";
	$auteurs['field']['mail_suivre_moi'] = "tinyint(1) NOT NULL DEFAULT '1'";
	$auteurs['field']['mail_mes_billets'] = "tinyint(1) NOT NULL DEFAULT '1'";
	$auteurs['field']['twitter'] = "varchar(100) NOT NULL DEFAULT ''";
	$auteurs['field']['facebook'] = "varchar(100) NOT NULL DEFAULT ''";
	$auteurs['field']['gplus'] = "varchar(100) NOT NULL DEFAULT ''";
	$auteurs['field']['liens_partage_fb'] = "tinyint(1) NOT NULL DEFAULT '0'";
	$auteurs['field']['liens_partage_tw'] = "tinyint(1) NOT NULL DEFAULT '0'";

	// ajouts dans spip_syndic
	$syndic = &$tables_principales['spip_syndic'];
	$syndic['field']['recup'] = "int(11) NOT NULL DEFAULT '0'";
	$syndic['field']['id_parent'] = "bigint(21) DEFAULT NULL";
	$syndic['field']['titre'] = "text NOT NULL";
	$syndic['field']['texte'] = "longtext NOT NULL";
	$syndic['field']['lang'] = "varchar(10) NOT NULL";
	$syndic['field']['md5'] = "char(32) DEFAULT NULL"; # md5 de l'URL
	$syndic['key']['KEY id_parent'] = "id_parent";
	$syndic['key']['KEY url'] = "url_site(255)";
	$syndic['key']['KEY md5'] = "md5";

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
	"
  `id_syndic` bigint(21) NOT NULL,
  `id_follow` bigint(21) NOT NULL,
  `date` datetime NOT NULL,
  KEY `id_syndic` (`id_syndic`),
  KEY `id_follow` (`id_follow`)
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
		if (version_compare($current_version,"1.1.7",'<')){
			include_spip('base/serial');
			include_spip('base/auxiliaires');
			include_spip('base/create');
			creer_base();

			maj_tables(array(
				'spip_auteurs',
				'spip_me',
				'spip_me_texte',
				'spip_me_recherche',
				'spip_me_auteur',
				'spip_me_follow',
				'spip_me_block',
				'spip_me_follow_url',
				'spip_me_share',
				'spip_me_syndic',
				'spip_syndic',
				'spip_traductions',
				'spip_me_tags'
			));


			// en 0.9.8, remplir arbitrairement les uuid manquants
			if (version_compare($current_version,"0.9.8",'<')){
				include_spip('inc/seenthis_uuid');
				seenthis_remplir_uuid();
			}

			// en 0.9.9, remplir spip_me_tags & spip_me_follow_tags
			if (version_compare($current_version,"0.9.9",'<')){
				seenthis_mots2tags();
			}

			// en 1.0.1, remplir spip_me_recherche.titre
			if (version_compare($current_version,"1.0.1",'<')){
				seenthis_maj_recherche_titre();
			}

			// en 1.0.2, supprimer les tables de mots
			if (version_compare($current_version,"1.0.2",'<')){
				sql_drop_table("spip_me_follow_mot");
				sql_drop_table("spip_me_mot");
			}
			// en 1.1.1, ajouter champs rezosocio
			if (version_compare($current_version,"1.1.1",'<')){
				sql_alter("TABLE spip_auteurs ADD twitter varchar(100) DEFAULT '' NOT NULL");
				sql_alter("TABLE spip_auteurs ADD facebook varchar(100) DEFAULT '' NOT NULL");
				sql_alter("TABLE spip_auteurs ADD gplus varchar(100) DEFAULT '' NOT NULL");
			}
			// en 1.1.2, ajouter l'option "mail a mes propres billets"
			// gere par maj_tables()
			#if (version_compare($current_version,"1.1.2",'<')){}

			// en 1.1.3, ajouter la key(id_me) sur spip_me_tags
			#if (version_compare($current_version,"1.1.3",'<')){}

			// en 1.1.4, poser mail_partage=0 (c'est nouveau)
			// et mail_rep_partage = mail_rep_moi (c'était confondu)
			// cf. https://github.com/seenthis/seenthis/pull/7
			if (version_compare($current_version,"1.1.4",'<')){
				sql_query("UPDATE spip_auteurs SET mail_partage=0, mail_rep_partage = mail_rep_moi");
			}

			// en 1.1.5, ajouter une clé unique sur spip_me_share en supprimant les doublons
			if (version_compare($current_version,"1.1.5",'<')){
				sql_query("ALTER IGNORE TABLE spip_me_share ADD UNIQUE INDEX spip_me_share_unique (id_me, id_auteur)");
			}

			// en 1.1.6, ajouter une clé sur spip_me_tags
			if (version_compare($current_version,"1.1.6",'<')){
				sql_query("ALTER TABLE spip_me_tags ADD INDEX spip_me_tags_index_tags (class, tag(255))");
			}

			// en 1.1.7, ajouter les colonne liens_partage_fb et liens_partage_tw
			if (version_compare($current_version,"1.1.7",'<')){
				sql_query("UPDATE spip_auteurs SET liens_partage_fb = 1, liens_partage_tw = 1");
			}

			ecrire_meta($nom_meta_base_version,$current_version=$version_cible,'non');

		}

	}
}

function seenthis_vider_tables($nom_meta_base_version) {
	effacer_meta($nom_meta_base_version);
	sql_drop_table("spip_me");
	sql_drop_table("spip_me_texte");
	sql_drop_table("spip_me_recherche");
	sql_drop_table("spip_me_auteur");
	sql_drop_table("spip_me_follow");
	sql_drop_table("spip_me_block");
	sql_drop_table("spip_me_follow_url");
	sql_drop_table("spip_me_share");
	sql_drop_table("spip_me_syndic");
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


// champs extras pour afficher tous les champs supplémentaires d'un auteur
// dans l'espace privé (si on active le plugin champ_extras :
// svn co svn://zone.spip.org/spip-zone/_plugins_/champs_extras/core/branches/v1/ champs_extras2/
function seenthis_declarer_champs_extras($champs = array()){

	$champs[] = new ChampExtra(array(
		'table' => 'auteur', // sur quelle table ?
		'champ' => 'copyright', // nom sql
		'label' => 'Licence', // chaine de langue 'prefix:cle'
		'precisions' => '', // precisions sur le champ
		'obligatoire' => false, // 'oui' ou '' (ou false)
		'rechercher' => false, // false, ou true ou directement la valeur de ponderation (de 1 à 8 generalement)
		'type' => 'ligne', // type de saisie
		'sql' => "varchar(10) DEFAULT 'C'", // declaration sql
	));

	foreach (explode(' ', 'mail_nouv_billet mail_partage mail_rep_moi mail_rep_partage mail_rep_billet mail_rep_conv mail_suivre_moi mail_mes_billets liens_partage_fb liens_partage_tw') as $c) {
	$champs[] = new ChampExtra(array(
		'table' => 'auteur', // sur quelle table ?
		'champ' => $c, // nom sql
		'label' => $c, // chaine de langue 'prefix:cle'
		'precisions' => '', // precisions sur le champ
		'obligatoire' => false, // 'oui' ou '' (ou false)
		'rechercher' => false, // false, ou true ou directement la valeur de ponderation (de 1 à 8 generalement)
		'type' => 'checkbox', // type de saisie (checkbox existe pas mais on aura essayé : ça affiche une ligne)
		'sql' => "tinyint(1) NOT NULL", // declaration sql
	));
	}

	foreach (explode(' ', 'troll troll_forcer') as $c) {
	$champs[] = new ChampExtra(array(
		'table' => 'auteur', // sur quelle table ?
		'champ' => $c, // nom sql
		'label' => $c, // chaine de langue 'prefix:cle'
		'precisions' => '', // precisions sur le champ
		'obligatoire' => false, // 'oui' ou '' (ou false)
		'rechercher' => false, // false, ou true ou directement la valeur de ponderation (de 1 à 8 generalement)
		'type' => 'checkbox', // type de saisie (checkbox existe pas mais on aura essayé : ça affiche une ligne)
		'sql' => "BIGINT(21)", // declaration sql
	));
	}


	foreach (explode(' ', 'twitter facebook gplus') as $c) {
	$champs[] = new ChampExtra(array(
		'table' => 'auteur', // sur quelle table ?
		'champ' => $c, // nom sql
		'label' => $c, // chaine de langue 'prefix:cle'
		'precisions' => '', // precisions sur le champ
		'obligatoire' => false, // 'oui' ou '' (ou false)
		'rechercher' => false, // false, ou true ou directement la valeur de ponderation (de 1 à 8 generalement)
		'type' => 'ligne', // type de saisie (checkbox existe pas mais on aura essayé : ça affiche une ligne)
		'sql' => "VARCHAR(100) NOT NULL DEFAULT ''", // declaration sql
	));
	}

	return $champs;
}


function seenthis_mots2tags() {

	# convertir les spip_me_mot+spip_mots+spip_groupes_mots
	# => en spip_me_tags
	$s = sql_query($a = "INSERT INTO spip_me_tags (id_me, uuid, tag, class, date, relevance, off)
	SELECT
		a.id_me,
		a.uuid,
		CONCAT(
			CASE WHEN g.titre='Hashtags' THEN '#' ELSE CONCAT(g.titre,':') END,
			m.titre
		) as tag,
		CASE WHEN g.titre='Hashtags' THEN '#' ELSE 'oc' END as class,
		a.date,
		am.relevance,
		am.off
	FROM
		spip_me AS a
		INNER JOIN spip_me_mot AS am ON a.id_me=am.id_me
		INNER JOIN spip_mots AS m on m.id_mot = am.id_mot
		INNER JOIN spip_groupes_mots AS g on m.id_groupe=g.id_groupe
	");


	# convertir les spip_me_syndic+spip_syndic
	# => en spip_me_tags
	$s = sql_query($a = "INSERT INTO spip_me_tags (id_me, uuid, tag, class, date)
	SELECT
		a.id_me,
		a.uuid,
		m.url_site as tag,
		'url' as class,
		a.date
	FROM
		spip_me AS a
		INNER JOIN spip_me_syndic AS am ON a.id_me=am.id_me
		INNER JOIN spip_syndic AS m on m.id_syndic = am.id_syndic
	");

	sql_query("ALTER TABLE spip_me_tags ADD INDEX `tag` (`tag`(60))");

	# convertir spip_me_follow_mot(id_mot)
	# => en spip_me_follow_tag("#spip")
	$s = sql_query($a = "INSERT INTO spip_me_follow_tag (tag, id_follow, date)
	SELECT
		CONCAT(
			CASE WHEN g.titre='Hashtags' THEN '#' ELSE CONCAT(g.titre,':') END,
			m.titre
		) as tag,
		f.id_follow,
		f.date
	FROM
		spip_mots AS m
		INNER JOIN spip_me_follow_mot AS f ON m.id_mot=f.id_mot
		LEFT JOIN spip_groupes_mots AS g ON m.id_groupe=g.id_groupe
	");

	# convertir spip_me_follow_url(id_url)
	# => en spip_me_follow_tag("url")
	$s = sql_query($a = "INSERT INTO spip_me_follow_tag (tag, id_follow, date)
	SELECT
		m.url_site as tag,
		f.id_follow,
		f.date
	FROM
		spip_syndic AS m
		INNER JOIN spip_me_follow_url AS f ON m.id_syndic=f.id_syndic
	");


	sql_query("ALTER TABLE spip_me_follow_tag ADD INDEX `tag` (`tag`(60))");


	// ajouter les md5 sur la table spip_syndic
	sql_query("UPDATE spip_syndic SET `md5`=MD5(url_site) WHERE `md5` IS NULL");

}

function seenthis_maj_recherche_titre() {
	$s = spip_query('SELECT id_me, texte FROM spip_me_recherche WHERE NOT (titre>"")');
	while ($t = sql_fetch($s)) {
		$titre = seenthis_titre_me($t['texte']);
		sql_updateq('spip_me_recherche', array('titre' => $titre), 'id_me='.$t['id_me']);
	}
}

?>