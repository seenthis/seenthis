<?php

/*
 *  Fonctions pour gerer des UUID dans seenthis
 */

function seenthis_remplir_uuid() {
	spip_log("remplir arbitrairement les uuid manquants", 'maj');
	echo "TEST";
	include_spip('inc/uuid');
	$s = sql_query('SELECT id_me FROM spip_me WHERE uuid IS NULL');
	while ($t = sql_fetch($s)) {
		$uuid = UUID::getuuid();
		sql_updateq('spip_me', array('uuid'=>$uuid), 'id_me='.$t['id_me']);
	}
	spip_log("uuid manquants : fin", 'maj');
}

