<?php

/**
 * seenthisaccueil -> tableau
 * @param string $u "env"
 * @return array|bool
 */
function inc_seenthisaccueil_to_array_dist($u, $page = null) {
	if (!$env = @unserialize($u)) {
		return false;
	}

	# assembler les flux constitués de :
	# - tous messages suivis (follow), partagés IN (5,share) ou répondus IN(5,replies)
	# - tous messages qu'on m'adresse : @texte "@fil"

	# IN (share,5) OR IN(replies,5) OR @texte "@fil"
	# => puisque pas de OR, dans le @texte ajouter @replies et @share

	# pour les share de $moi, date = date du partage

	# ne pas prendre les messages des auteurs bloques (i.e. dans $where)
	# sauf s'ils ont été mis en favori par $nous (i.e. dans $fav)

	# trier l'ensemble par date
	# paginer…

	# On commence par prendre debut+maxpagination premiers partages
	# pour reinjecter leurs dates de partage à la place des dates de publi
	$max_pagination = 300;
	$debut = intval($env['debut_messages']);
	$variante = $env['variante']; // '', 'only', 'all', 'follow'
	$moi = 0;
	$auteurs_bloques = [];


	$r = [];

	switch ($page) {
		case 'auteur':
			$env['follow'] = $env['id'];
			break;
		case 'accueil':
		default:
			$moi = intval($GLOBALS['visiteur_session']['id_auteur']);
			break;
	}

	switch ($env['follow']) {
		# $nous
		case '':
		case 'follow':
			# $nous = $moi + les gens que je follow
			$nous = array_merge([$moi], liste_follow($moi));

			# ensuite on va faire notre selection de tout ce que :
			# - j'ai envoyé
			#   + les gens que je suis (id_auteur $nous)
			# - les mots que je suis (IN tags [liste des tags])
			# - les URLs que je suis (…………)
			# - $nous avons partagé (share $nous);
			#    => si c'est $moi qui partage, conserver la date_m
			# - j'ai répondu (replies $moi)
			# - pointe vers moi ($pointe)
			$pointe = liste_pointe_sql($debut, $max_pagination, $moi);
			$where = '(' . sql_in('id_auteur', $nous) . $pointe . ')';
			$auteurs_bloques = auteurs_bloques($moi);
			$fav = liste_partages($nous, $debut, $max_pagination, 'date_m', $auteurs_bloques);
			break;

		# tout : pas de filtre (sauf les bloques)
		case 'all':
			$where = '1=1';
			$auteurs_bloques = auteurs_bloques($moi);
			$fav = liste_partages($moi, $debut, $max_pagination, 'date_m', $auteurs_bloques);
			break;

		# $moi ou une autre
		# c'est la page people/$elle
		case 'fil':
		default:
			if ($elle = sql_allfetsel('id_auteur', 'spip_auteurs', '(login=' . sql_quote($env['follow']) . ' OR id_auteur=' . intval($env['id']) . ") AND statut!='5poubelle'")) {
				# $selfollow="(IN(id_auteur,$moi) OR IN(share,$moi)) as ok";
				$elle = $elle[0]['id_auteur'];
				$auteurs_bloques = auteurs_bloques($elle);

				if ($variante == 'only') {
					$fav = [];
				} else {
					$fav = liste_partages($elle, $debut, $max_pagination, 'date_s', $auteurs_bloques);
				}

				$where = '(' . sql_in('id_auteur', $elle) . ')';
			}
			else {
				$where = '0=1';
				$fav = [];
			}
			break;
	}


	# requete triee par date, avec des dates remises en fonction des favoris
	$r = $fav;
	$bloquer = count($auteurs_bloques)
		? ' AND ' . sql_in('id_auteur', $auteurs_bloques, 'NOT')
		: '';

	$res = sql_allfetsel('id_me,UNIX_TIMESTAMP(date) as date', 'spip_me', $where . $bloquer . ' AND id_parent=0 AND statut="publi"', '', 'date DESC', $max_pagination + $debut);

	foreach ($res as &$match) {
		$date = $match['date'];
		$id = $match['id_me'];
		if (!isset($r[$id])) {
			$r[$id] = $date;
		}
	}

	arsort($r);
	$r = array_keys($r);

#spip_log($r, 'debug');
	return array_splice($r, 0, $max_pagination + $debut);
}


/**
 * seenthisbackend -> tableau
 * @param string $u "env"
 * @return array|bool
 */
function inc_seenthisbackend_to_array_dist($u, $variante = null) {
	if (!$env = @unserialize($u)) {
		return false;
	}

	$max_pagination = 25;
	$debut = 0; //intval($env['debut_messages']);

	$r = [];

	// utilisateur de base
	$moi = $env['id_auteur'];

	// $variante = '', 'only', 'follow', 'all'

	switch ($variante) {
		# /LOGIN/only/feed
		case 'only':
			# $nous = $moi
			$nous = [$moi];

			# ensuite on va faire notre selection de tout ce que :
			# - j'ai envoyé
			$where = 'id_auteur=' . $moi;
			$fav = [];
			$auteurs_bloques = '';
			break;

		# follow: ici c'est UNIQUEMENT ceux que je suis ($nous mais pas $moi)
		# /LOGIN/follow/feed
		case 'follow':
			$nous = array_merge([0], liste_follow($moi));

			# ensuite on va faire notre selection de tout ce que :
			# - $nous avons envoyé
			# - $nous avons partagé (share $nous)
			# - les mots que je suis (IN tags [liste des tags])
			# - les URLs que je suis (…………)
			# - j'ai répondu (replies $moi)
			$where = '(' . sql_in('id_auteur', $nous) . ')';
			$auteurs_bloques = auteurs_bloques($moi);
			$fav = liste_partages($nous, $debut, $max_pagination, 'date_s', $auteurs_bloques);
			break;

		# /LOGIN/all/feed
		case 'all':
			# $nous = $moi + les gens que je follow
			$nous = array_merge([$moi], liste_follow($moi));

			# ensuite on va faire notre selection de tout ce que :
			# - j'ai envoyé
			#   + les gens que je suis (id_auteur $nous)
			# - les mots que je suis (IN tags [liste des tags])
			# - les URLs que je suis (…………)
			# - $nous avons partagé (share $nous)
			# - j'ai répondu (replies $moi)
			# - pointe vers moi ($pointe)
			$pointe = liste_pointe_sql($debut, $max_pagination, $moi);
			$where = '(' . sql_in('id_auteur', $nous) . $pointe . ')';
			$auteurs_bloques = auteurs_bloques($moi);
			$fav = liste_partages($nous, $debut, $max_pagination, 'date_s', $auteurs_bloques);
			break;

		# /LOGIN/feed
		case '':
		default:
			# ensuite on va faire notre selection de tout ce que :
			# - j'ai envoyé + partagé (share $moi)
			$where = 'id_auteur=' . $moi;
			$auteurs_bloques = auteurs_bloques($moi);
			$fav = liste_partages($moi, $debut, $max_pagination, 'date_s', $auteurs_bloques);
			break;
	}

	# requete triee par date, avec des dates remises en fonction des favoris
	$r = $fav;

	$bloquer = count($auteurs_bloques)
		? ' AND ' . sql_in('id_auteur', $auteurs_bloques, 'NOT')
		: '';

	$res = sql_allfetsel('id_me,UNIX_TIMESTAMP(date) as date', 'spip_me', $where . $bloquer . ' AND id_parent=0 AND statut="publi"', '', 'date DESC', $max_pagination + $debut);

	foreach ($res as &$match) {
		$date = $match['date'];
		$id = $match['id_me'];
		if (!isset($r[$id])) {
			$r[$id] = $date;
		}
	}

	arsort($r);
	$r = array_keys($r);

	return array_splice($r, 0, $max_pagination + $debut);
}


/**
 * seenthisrecherche -> tableau
 * @param string $u "env"
 * @return array|bool
 */
function inc_seenthisrecherche_to_array_dist($u) {
	if (!$env = @unserialize($u)) {
		return false;
	}
	$follow = strval(@$env['follow']);

	# valeur maximum de la pagination sur cette boucle DATA
	$max_pagination = 100;

	$debut = intval($env['debut_messages']);

	include_spip('inc/session');
	$moi = intval(session_get('id_auteur'));
	$auteurs_bloques = [];

	$where = [];

	switch ($env['follow']) {
		# $nous
		case '':
		case 'follow':
			# $nous = $moi + les gens que je follow
			$nous = array_merge([$moi], liste_follow($moi));

			# ensuite on va faire notre selection de tout ce que :
			# - j'ai envoyé
			#   + les gens que je suis (id_auteur $nous)
			# - les mots que je suis (IN tags [liste des tags])
			# - les URLs que je suis (…………)
			# - $nous avons partagé (share $nous)
			# - j'ai répondu (replies $moi)
			# - pointe vers moi ($pointe)
			$pointe = str_replace(
				'id_me',
				'm.id_me',
				liste_pointe_sql($debut, $max_pagination, $moi)
			);
			$auteurs_bloques = auteurs_bloques($moi);
			$fav = liste_partages($nous, $debut, $max_pagination, 'date_s', $auteurs_bloques);
			$wherefollow = ' AND (' . sql_in('m.id_auteur', $nous) . $pointe
				. ' OR ' . sql_in('m.id_me', array_keys($fav)) . ')';
			break;

		# tout : pas de filtre
		case 'all':
			$auteurs_bloques = auteurs_bloques($moi);
			break;

		# $moi ou une autre
		# c'est la page people/$elle
		case 'fil':
		default:
			if ($elle = sql_allfetsel('id_auteur', 'spip_auteurs', '(login=' . sql_quote($env['follow']) . ' OR id_auteur=' . intval($env['id']) . ") AND statut!='5poubelle'")) {
				# $selfollow="(IN(id_auteur,$moi) OR IN(share,$moi)) as ok";
				$elle = $elle[0]['id_auteur'];
				$auteurs_bloques = auteurs_bloques($elle);
				$fav = liste_partages($elle, $debut, $max_pagination, 'date_s', $auteurs_bloques);
				$where[] = '((' . sql_in('m.id_auteur', $elle) . ')'
					. ' OR ' . sql_in('m.id_me', array_keys($fav)) . ')';
			}
			else {
				$fav = [];
			}
			break;
	}

	if (count($auteurs_bloques)) {
		$where[] = sql_in('m.id_auteur', $auteurs_bloques, 'NOT');
	}

	# tri par "time segments" :
	#    1 heure, 1 jour, 1 semaine, 1 mois, 3 mois, et le reste
	#    dans chaque segment, tri par pertinence
	#    NOTE: pour savoir dans quel segment on est, recalculer time()-date :(
	$segments = [1, 24, 7 * 24, 31 * 24, 90 * 24, 365 * 24];
	$scores = [];
	foreach ($segments as $k => $duree) {
		$d = date('Y-m-d H:i:s', time() - 3600 * $duree);
		$scores[] = 'CASE WHEN (m.date > ' . sql_quote($d) . ") THEN $k";
	}
	$tseg = '('
		. join(' ELSE ', $scores) . ' ELSE '
		. (1 + count($segments))
		. str_repeat(' END', count($segments))
		. ') AS tseg';

	# fulltext
	$key_titre = '`titre`';
	$key = '`texte`';
	$r = trim(preg_replace(',\s+,', ' ', $env['recherche']));

	### Cas particuliers
	# recherche d'un people ? => messages ecrits ou adresses a @people
	if (preg_match_all('/' . _REG_PEOPLE . '/i', $r, $people)) {
		foreach ($people[0] as $k => $p) {
			$login = mb_substr($p, 1);
			if ($t = sql_fetsel('id_auteur', 'spip_auteurs', 'login=' . sql_quote($login))) {
				$where[] = "((MATCH(r.$key) AGAINST ('$p')) OR m.id_auteur=" . $t['id_auteur'] . ')';
				$r = trim(str_replace($p, '', $r));
				# s'il ne reste plus rien, on renvoie vers people/$login
				if (!strlen($r)) {
					include_spip('inc/headers');
					include_spip('inc/urls');
					redirige_par_entete(generer_url_entite($t['id_auteur'], 'auteur'));
				}
			}
		}
	}	# recherche d'une URL ?
	if (preg_match_all('/' . _REG_URL . '/ui', $r, $urls)) {
		foreach ($urls[0] as $k => $p) {
			$ids = sql_allfetsel('id_me', 'spip_me_tags', 'tag LIKE ' . sql_quote("$p%"));
			foreach ($ids as $k => $p) {
				$ids[$k] = $p['id_me'];
			}
			$where[] = sql_in('m.id_me', $ids);
			$r = trim(str_replace($p, '', $r));
			if (!strlen($r)) {
				$r = 'http*'; # hack
			}
		}
	}
	###

	// si espace, ajouter la meme chaine avec des guillemets pour ameliorer la pertinence
	$pe = (strpos($r, ' ') and strpos($r, '"') === false)
		? sql_quote(trim("\"$r\"")) : '';

	// On utilise la translitteration pour contourner le pb des bases
	// declarees en iso-latin mais remplies d'utf8
	if (($r2 = translitteration($r)) != $r) {
		$r .= ' ' . $r2;
	}

	$p = sql_quote(trim("$r"));

	$val = $match = "5 * (MATCH($key_titre) AGAINST($p)) + MATCH(r.$key) AGAINST ($p)";
	// Une chaine exacte rapporte plein de points
	if ($pe) {
		$val .= "+ 2 * MATCH(r.$key) AGAINST ($pe)";
	}

	// si symboles booleens les prendre en compte
	if ($boolean = preg_match(', [+-><~]|\* |".*?",', " $r ")) {
		$val = $match = "MATCH(r.$key) AGAINST ($p IN BOOLEAN MODE)";
	}

	$where[] = "($match) > 0";
	$where[] = "m.statut='publi'";

	$res = sql_allfetsel(
		"SQL_CALC_FOUND_ROWS r.id_me AS id, m.date, $val AS score, $tseg",
		'spip_me_recherche AS r INNER JOIN spip_me AS m ON r.id_me=m.id_me',
		$where,
		null,
		'tseg ASC, score DESC',
		"$debut,$max_pagination"
	);

	$t = sql_fetch(sql_query('SELECT FOUND_ROWS() as total'));
	# remplir avant debut, avec du vide
	for ($i = 0; $i < $debut; $i++) {
		array_unshift($res, 0);
	}
	# remplir apres fin, avec du vide
	$grand_total = min(2000, intval($t['total']));
	for ($i = count($res); $i < $grand_total; $i++) {
		array_push($res, 0);
	}

	return $res;
}


/**
 * seenthisfollowtags -> tableau
 * @param string $u "env"
 * @return array|bool
 */
function inc_seenthisfollowtags_to_array_dist($u, $page = null) {
	if (!$env = @unserialize($u)) {
		return false;
	}

	# page tags/ : les tags que je follow
	$max_pagination = 300;
	$debut = intval($env['debut_messages']);

	$r = [];

	$moi = intval($GLOBALS['visiteur_session']['id_auteur']);


	if (!$moi) {
		return [];
	}

	$auteurs_bloques = auteurs_bloques($moi);
	$fav = liste_partages($moi, $debut, $max_pagination, 'date_s', $auteurs_bloques);

	$bloquer = count($auteurs_bloques)
		? ' AND ' . sql_in('id_auteur', $auteurs_bloques, 'NOT')
		: '';

	if ($page == 'sites') {
		$class = 'url';
	} else {
		$class = '# oc';
	}
	$k = liste_pointe_tags($debut, $max_pagination, $moi, $class);
	$p = seenthis_chercher_parents($k);
	$where = sql_in('id_me', $p);

	$r = sql_allfetsel('id_me', 'spip_me', $where . $bloquer, '', 'date DESC', $max_pagination + $debut);

	return array_map('array_pop', array_splice($r, 0, $max_pagination + $debut));
}


/* recherche dans les sites syndiqués, methode fulltext */
function inc_syndicrecherche_to_array_dist($u) {
	if (!$env = @unserialize($u)) {
		return false;
	}
	$key = '`url_site`,`titre`,`texte`';
	$r = trim(preg_replace(',\s+,', ' ', $env['recherche']));
	$p = sql_quote(trim("$r"));

	$val = $match = "MATCH($key) AGAINST ($p)";

	$res = sql_allfetsel(
		"id_syndic AS id, url_site AS url, $val AS score",
		'spip_syndic',
		"$match AND statut='publie'",
		null,
		'score DESC',
		'0,30'
	);

	return $res;
}

function liste_partages($nous, $debut = 0, $max_pagination = 500, $datep = 'date_s', $auteurs_bloques = []) {
	$r = [];

	if (!is_array($nous)) {
		$nous = [$nous];
	}

	$bloquer = count($auteurs_bloques)
		? ' AND ' . sql_in('m.id_auteur', $auteurs_bloques, 'NOT')
		: '';

	# en deux temps, car je cherche en priorité la date de mes fav,
	# puis celle de mes amis
	$eux = $nous;
	$moi = array_shift($eux);

	# choix du tri sur date_m (message) ou date_s (partage)
	# attention il y a une difficulte avec date_m, car il faut savoir si l'un d'eux a partage avant moi, ou l'inverse : on memorise donc cette date dans $date_s
	$date_s = [];

	if ($f = sql_allfetsel('s.id_me, UNIX_TIMESTAMP(s.date) as date_s, UNIX_TIMESTAMP(m.date) as date_m', 'spip_me_share AS s INNER JOIN spip_me AS m ON s.id_me=m.id_me', 's.id_auteur=' . $moi . ' AND m.statut="publi" AND m.id_parent=0', 's.id_me', ["$datep DESC"], '0,' . ($debut + $max_pagination))) {
		foreach ($f as $m) {
			$me = intval($m['id_me']);
			$r[$me] = (int) $m[$datep];
			$date_s[$me] = (int) $m['date_s'];
		}
	}

	# logique d'horodatage des partages de mes amis :
	# - si le message est ecrit par quelqu'un que je suis, je n'en ai
	#   pas besoin, car j'ai deja vu ce message dans mon flux
	# - en revanche, si c'est un message provenant d'une personne que je ne
	#   suis pas, je n'ai pas vu ce message, un partage le "remonte"
	#   dans mon flux, à la date du partage (s.date)
	if ($eux) {
		$nouspasauteurs = sql_in('m.id_auteur', $nous, 'NOT');
		$euxshare = sql_in('s.id_auteur', $eux);
		if ($f = sql_allfetsel('s.id_me, UNIX_TIMESTAMP(m.date) as mdate, MIN(UNIX_TIMESTAMP(s.date)) AS sdate, UNIX_TIMESTAMP(MIN(s.date)) as date', 'spip_me_share AS s INNER JOIN spip_me AS m ON s.id_me=m.id_me', $nouspasauteurs . ' AND ' . $euxshare . ' AND m.statut="publi" AND m.id_parent=0' . $bloquer, 's.id_me', ['date DESC'], '0,' . ($debut + $max_pagination))) {
			foreach ($f as $m) {
				$me = intval($m['id_me']);
				if (!isset($date_s[$me]) or $m['date'] < $date_s[$me]) {
					$r[$me] = (int) $m['date'];
				}
			}
		}
	}

	return $r;
}

function liste_pointe_sql($debut, $max_pagination, $moi) {

	# on cherche des messages relativement recents et interessants
	$pointe = [];

	# les mentions @login vers $moi :
	$mentions = sql_allfetsel('id_me', 'spip_me_auteur', 'id_auteur=' . $moi, '', 'date DESC', '0,' . ($debut + $max_pagination));
	$pointe = array_column($mentions, 'id_me');

	# les messages qui parlent d'un sujet ou url qui m'interesse $moi
	if ($pointetags = liste_pointe_tags($debut, $max_pagination, $moi)) {
		$pointe = array_merge($pointe, $pointetags);
	}

	# les messages auxquels j'ai repondu $moi
	$mentions = sql_allfetsel('DISTINCT(id_parent) as id, date', 'spip_me', "id_auteur=$moi AND id_parent>0 AND statut='publi'", '', 'date DESC', '0,' . ($debut + $max_pagination));
	$pointe = array_column($mentions, 'id');

	# faut-il ajouter les messages ayant des URLs avec un tag opencalais que je suis ?

	$pointe = seenthis_chercher_parents($pointe);

	if ($pointe) {
		return ' OR ' . sql_in('id_me', $pointe);
	}
}

/*
 * class : '# oc' pour manuel|opencalais; 'url'; null=tout
 */
function liste_pointe_tags($debut, $max_pagination, $moi, $class = null) {
	if ($class === null) {
		$where = '';
	} elseif ($class == 'url') {
		$where = " AND tag LIKE 'http%'";
	} elseif ($class == '# oc') {
		$where = " AND NOT (tag LIKE 'http%')";
	}
	if ($tags = sql_allfetsel('DISTINCT(tag)', 'spip_me_follow_tag', 'id_follow=' . $moi . $where)) {
		$tags = array_map('array_pop', $tags);

		// tags stricts ?
		# $condition = sql_in('tag', $tags);
		// tags ou extensions du tag
		$condition = [];
		foreach ($tags as $tag) {
			$tag = str_replace(['%', '_'], ['\\%', '\\_'], sql_quote($tag));
			$tag = substr($tag, 0, -1) . "%'";
			$condition[] = "tag like $tag";
		}
		$condition = '(' . join(' OR ', $condition) . ')';

		$mentions = sql_allfetsel('id_me', 'spip_me_tags', $condition, null, 'date DESC', '0,' . ($debut + $max_pagination));

		return array_map('array_pop', $mentions);
	}
	return [];
}

/* chercher les parents d'une suite de messages
 * si les messages sont leurs propres parents, ok
 */
function seenthis_chercher_parents($m = [], $publie = true) {
	$r = [];
	$publie = $publie
		? " AND statut='publi'"
		: '';
	$s = sql_query('SELECT DISTINCT(IF(id_parent>0,id_parent,id_me)) AS i FROM spip_me WHERE ' . sql_in('id_me', $m) . $publie);
	while ($t = sql_fetch($s)) {
		$r[] = $t['i'];
	}
	return $r;
}

/* quels sont les auteurs que je bloque */
function auteurs_bloques($moi) {
	return array_map('array_pop', sql_allfetsel('id_auteur', 'spip_me_block', 'id_block=' . sql_quote($moi)));
}
