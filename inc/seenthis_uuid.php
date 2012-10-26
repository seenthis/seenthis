<?php

/*
 *  Fonctions pour gerer des UUID dans seenthis
 */

// chercher un message portant cet UUID, si besoin le creer
// pas souci de simplicitŽ de l'API, on accepte une chaine
// n'ayant pas la forme canonique d'un UUID : on cree alors un UUID
// a partir de cette chaine
function get_create_me_uuid($what=null, $id_auteur=null) {
	include_spip('inc/uuid');

	$uuid = UUID::getuuid($what);

	spip_log($uuid);

	$s = spip_query("SELECT id_me FROM spip_me WHERE uuid=".sql_quote($uuid));
	if ($s AND $t = sql_fetch($s)) {
		$id_me = $t['id_me'];
		spip_log("uuid: $uuid, found id_me=$id_me", 'debug');
	} else {
		if (is_null($id_auteur))
			$id_auteur = $GLOBALS['visiteur_session']['id_auteur'];

		$id_me = sql_insertq("spip_me",
			array(
				"date" => "NOW()",
				"date_modif" => "NOW()",
				"date_parent" => "NOW()",
				"id_auteur" => $id_auteur,
				'uuid' => $uuid
			)
		);
		if ($id_me) {
			sql_insertq("spip_me_texte",
				array("id_me" => $id_me)
			);
			spip_log("uuid: $uuid, create id_me=$id_me", 'debug');
		}
	}

	return $id_me;
}


function seenthis_remplir_uuid() {
	spip_log("remplir arbitrairement les uuid manquants", 'maj');
	include_spip('inc/uuid');
	$s = sql_query('SELECT id_me FROM spip_me WHERE uuid=""');
	while ($t = sql_fetch($s)) {
		$uuid = UUID::getuuid();
		sql_updateq('spip_me', array('uuid'=>$uuid), 'id_me='.$t['id_me']);
	}
	spip_log("uuid manquants : fin", 'maj');
}

