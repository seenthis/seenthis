<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function seenthis_autoriser() {
}

/**
 * Autorisation de modifier un message : être auteur du message
 *
 * @param string $faire L'action
 * @param string $type Le type d'objet
 * @param int $id L'identifiant numérique de l'objet
 * @param array $qui Les informations de session de l'auteur
 * @param array $opt Des options
 * @return boolean true/false
 */
function autoriser_me_modifier_dist($faire, $quoi, $id, $qui, $opts) {
	$id_auteur = sql_getfetsel('id_auteur', 'spip_me', 'id_me='.intval($id));
	return $qui['id_auteur'] == $id_auteur;
}

/**
 * Autorisation de supprimer un message : être auteur du message
 *
 * @param string $faire L'action
 * @param string $type Le type d'objet
 * @param int $id L'identifiant numérique de l'objet
 * @param array $qui Les informations de session de l'auteur
 * @param array $opt Des options
 * @return boolean true/false
 */
function autoriser_me_supprimer_dist($faire, $quoi, $id, $qui, $opts) {
	$me = sql_fetsel('id_auteur, id_parent', 'spip_me', 'id_me='.intval($id));
	if (!$me) {
		return false;
	}
	if ($qui['id_auteur'] == $me['id_auteur']) {
		return true;
	}
	if ($me['id_parent'] > 0) {
		if ($id_auteur = sql_getfetsel('id_auteur', 'spip_me', 'id_me='.intval($me['id_parent'])) and $qui['id_auteur'] == $id_auteur) {
			return true;
		}
	}
	return false;
}
