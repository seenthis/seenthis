<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/cextras');
include_spip('base/seenthis');
include_spip('inc/meta');

function seenthis_upgrade($nom_meta_base_version,$version_cible){
	
	$maj = array();
	
	// installation
	$maj['create'] = array(
		array('maj_tables', array(
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
		))
	);
	
	// en 0.9.8, remplir arbitrairement les uuid manquants
	$maj['0.9.8'] = array(
		array('seenthis_maj_remplir_uuid'),
	);
	// en 0.9.9, remplir spip_me_tags & spip_me_follow_tags
	$maj['0.9.9'] = array(
		array('seenthis_mots2tags'),
	);
	// en 1.0.1, remplir spip_me_recherche.titre
	$maj['1.0.1'] = array(
		array('seenthis_maj_recherche_titre'),
	);
	// en 1.0.2, supprimer les tables de mots
	$maj['1.0.2'] = array(
		array('sql_drop_table','spip_me_follow_mot'),
		array('sql_drop_table','spip_me_mot'),
	);
	// en 1.1.1, ajouter champs rezosocio
	$maj['1.1.1'] = array(
		array('sql_alter',"TABLE spip_auteurs ADD twitter varchar(100) DEFAULT '' NOT NULL"),
		array('sql_alter',"TABLE spip_auteurs ADD facebook varchar(100) DEFAULT '' NOT NULL"),
		array('sql_alter',"TABLE spip_auteurs ADD gplus varchar(100) DEFAULT '' NOT NULL"),
	);
	// en 1.1.4, poser mail_partage=0 (c'est nouveau)
	// et mail_rep_partage = mail_rep_moi (c'était confondu)
	// cf. https://github.com/seenthis/seenthis/pull/7
	$maj['1.1.4'] = array(
		array('sql_update','spip_auteurs',array('mail_partage'=>0,'mail_rep_partage'=>'mail_rep_moi')),
	);
	// en 1.1.5, ajouter une clé unique sur spip_me_share en supprimant les doublons
	$maj['1.1.5'] = array(
		array('sql_query',"ALTER IGNORE TABLE spip_me_share ADD UNIQUE INDEX spip_me_share_unique (id_me, id_auteur)"),
	);
	// en 1.1.6, ajouter une clé sur spip_me_tags
	$maj['1.1.6'] = array(
		array('sql_alter',"TABLE spip_me_tags ADD INDEX spip_me_tags_index_tags (class, tag(255))"),
	);
	// en 1.1.7, ajouter les colonne liens_partage_fb et liens_partage_tw
	$maj['1.1.7'] = array(
		array('sql_update','spip_auteurs',array('liens_partage_fb'=>1,'liens_partage_tw'=>1)),
	);
	// en 1.1.8, appliquer troll_forcer=0 pour les troll_forcer IS NULL
	$maj['1.1.8'] = array(
		array('sql_update','spip_auteurs',array('troll_forcer'=>0),"troll_forcer IS NULL"),
	);
	// en 1.1.9, retirer les index sur spip_me_tags car ils génèrent des erreurs SQL cf https://github.com/seenthis/seenthis_squelettes/issues/149 & https://github.com/seenthis/hebergement/issues/12
	$maj['1.1.9'] = array(
		array('sql_alter',"TABLE spip_me_tags DROP INDEX tag"),
		array('sql_alter',"TABLE spip_me_tags DROP INDEX spip_me_tags_index_tags"),
	);

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

function seenthis_maj_remplir_uuid() {
	include_spip('inc/seenthis_uuid');
	seenthis_remplir_uuid();
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

function seenthis_vider_tables($nom_meta_base_version) {
	cextras_api_vider_tables(seenthis_declarer_champs_extras());
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
	effacer_meta($nom_meta_base_version);
}