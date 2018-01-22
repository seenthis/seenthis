<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

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