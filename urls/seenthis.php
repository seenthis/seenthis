<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

/*
	Ce jeu d'URLs est une variation de urls/arbo
	qui prend en compte les urls de message
*/

if (!defined('_URLS_ARBO_MIN')) define('_URLS_ARBO_MIN', 0);

define('URLS_SEENTHIS_EXEMPLE', 'message/12 people/login tag/mot-clé');

if (!defined('_MARQUEUR_URL'))
	define('_MARQUEUR_URL', false);

## utiliser des mots anglais
$GLOBALS['url_arbo_types']=array(
	'mot'=>'tag',
	'auteur'=>'people',
);

## delicate composition pour prendre le login a la place du nom dans l'URL
include_spip('public/interfaces');
$GLOBALS['table_titre']['auteurs'] = 'login AS titre, lang';


// http://doc.spip.org/@urls_libres_dist
function urls_seenthis_dist($i, &$entite, $args='', $ancre='') {

#	echo "<hr />";var_dump($i,$entite,$args,$ancre); echo "<br />\n";

	if (is_numeric($i)) {
		# #URL_ME
		if ($entite == 'me') {
			# s'il y a un parent, c'est #URL_ME{parent}#message$i
			$k = sql_allfetsel('id_me,id_parent', 'spip_me', 'id_me='.$i);
			if (!$k[0]) $g = '';
			if ($k[0]['id_parent'])
				$g = urls_seenthis_dist($k[0]['id_parent'], $entite, $args, 'message'.$i);
			# sinon c'est messages/$i
			else
				$g = 'messages/'.$i;

			// Ajouter les args
			if ($args)
				$g .= ((strpos($g, '?')===false) ? '?' : '&') . $args;
		
			// Ajouter l'ancre
			if ($ancre)
				$g .= "#$ancre";

		}
	} else if (TRUE) {
		if (preg_match(',/people/?$,', $i)) {
			$g = array(array(), 'people');
		}
		else
		if (preg_match(',/messages/(\d+)$,', $i, $r)) {
			$g = array(
				array('id_me' => $r[1]),
				'message',
				null,
				null
			);
		}
	}

	// Sinon on se base sur l'url arbo
	if (!isset($g)) {
		$f = charger_fonction('arbo', 'urls');
		$g = $f($i, $entite, $args, $ancre);
	}

	return $g;
}

?>
