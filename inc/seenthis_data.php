<?php


/**
 * seenthisaccueil -> tableau
 * @param string $u "env"
 * @return array|bool
 */
function inc_seenthisaccueil_to_array_dist($u) {
	if (!$env = @unserialize($u))
		return false;

	# assembler les flux constitués de :
	# - tous messages suivis (follow), partagés IN (5,share) ou répondus IN(5,replies)
	# - tous messages qu'on m'adresse : @texte "@fil"

	# IN (share,5) OR IN(replies,5) OR @texte "@fil"
	# => puisque pas de OR, dans le @texte ajouter @replies et @share

	# pour les share, date = date du partage
	# trier l'ensemble par date
	# paginer…

	# On commence par prendre debut+maxpagination premiers partages
	# pour reinjecter leurs dates de partage à la place des dates de publi
	$max_pagination = 300;
	$debut = intval($env['debut_messages']);

	$moi = intval($GLOBALS['visiteur_session']['id_auteur']);
	$login = $GLOBALS['visiteur_session']['login'];

	$r = array();

	# dans la page people/login on recoit l'id_auteur dans id ; mais pas d'id_auteur (!!!) du coup on detecte comme ça pour le moment
	if ($env['id'] AND !$env['id_auteur'])
		$env['follow'] = $env['id'];

	switch($env['follow']) {
		# $nous
		case '':
		case 'follow':
			# $nous = $moi + les gens que je follow
			$nous = array_merge(array($moi),liste_follow($moi));

			# ensuite on va faire notre selection de tout ce que :
			# - j'ai envoyé
			#   + les gens que je suis (id_auteur $nous)
			# - les mots que je suis (IN tags [liste des tags])
			# - les URLs que je suis (…………)
			# - $nous avons partagé (share $nous)
			# - j'ai répondu (replies $moi)
			# - pointe vers moi ($pointe)
			$pointe = liste_pointe_sql($debut, $max_pagination, $login, $moi, $nous);
			$where = '('.sql_in('id_auteur', $nous). $pointe.')';
			$fav = liste_favoris($nous,$debut, $max_pagination);
			break;

		# tout : pas de filtre
		case 'all':
			$where = "1=1";
			$fav = liste_favoris($moi,$debut, $max_pagination);
			break;

		# $moi ou une autre
		# c'est la page people/$elle
		case 'fil':
		default:
			if ($elle = sql_allfetsel('id_auteur', 'spip_auteurs', '(login='.sql_quote($env['follow'])." OR id_auteur=".intval($env['id']).") AND statut!='5poubelle'")) {
				# $selfollow="(IN(id_auteur,$moi) OR IN(share,$moi)) as ok";
				$elle = $elle[0]['id_auteur'];
				$fav = liste_favoris($elle,$debut, $max_pagination);
				$where = '('.sql_in('id_auteur', $elle) .')';
			}
			else {
				$where = "0=1";
				$fav = array();
			}
			break;

	}


	# requete triee par date, avec des dates remises en fonction des favoris
	$r = $fav;

	$res = sql_allfetsel('id_me,UNIX_TIMESTAMP(date) as date', 'spip_me', $where.' AND id_parent=0 AND statut="publi"', '', 'date DESC', $max_pagination + $debut);

	foreach ($res as &$match) {
		$date = $match['date'];
		$id = $match['id_me'];
		if (!isset($r[$id])) {
			$r[$id] = $date;
		}
	}

	arsort($r);
	$r =  array_keys($r);

	return array_splice($r,0, $max_pagination+$debut);

}


/**
 * seenthisrecherche -> tableau
 * @param string $u "env"
 * @return array|bool
 */
function inc_seenthisrecherche_to_array_dist($u) {
	if (!$env = @unserialize($u))
		return false;
	$follow = strval(@$env['follow']);

	# valeur maximum de la pagination sur cette boucle DATA
	$max_pagination = 100;

	$debut = intval($env['debut_messages']);

	$moi = intval($GLOBALS['visiteur_session']['id_auteur']);

	$where = array();

	

	## env[age] est la date minimum au format "YYYY-MM-dd H:i:s"
	if ($env['age']
	AND strtotime($env['age']) // verifier que la date est valide
	) {
		$wheredate = ' AND (m.date >= '.sql_quote($env['age']).')';
	}

	switch($env['follow']) {
		# $nous
		case '':
		case 'follow':
			# $nous = $moi + les gens que je follow
			$nous = array_merge(array($moi),liste_follow($moi));

			# ensuite on va faire notre selection de tout ce que :
			# - j'ai envoyé
			#   + les gens que je suis (id_auteur $nous)
			# - les mots que je suis (IN tags [liste des tags])
			# - les URLs que je suis (…………)
			# - $nous avons partagé (share $nous)
			# - j'ai répondu (replies $moi)
			# - pointe vers moi ($pointe)
			$pointe = str_replace('id_me', 'm.id_me',
				liste_pointe_sql($debut, $max_pagination, $login, $moi, $nous));
			$fav = liste_favoris($nous,$debut, $max_pagination);
			$wherefollow = ' AND ('.sql_in('m.id_auteur', $nous). $pointe
				. ' OR '.sql_in('m.id_me', array_keys($fav)).')';
			break;

		# tout : pas de filtre
		case 'all':
			$wherefollow = "";
			break;

		# $moi ou une autre
		# c'est la page people/$elle
		case 'fil':
		default:
			if ($elle = sql_allfetsel('id_auteur', 'spip_auteurs', '(login='.sql_quote($env['follow'])." OR id_auteur=".intval($env['id']).") AND statut!='5poubelle'")) {
				# $selfollow="(IN(id_auteur,$moi) OR IN(share,$moi)) as ok";
				$elle = $elle[0]['id_auteur'];
				$fav = liste_favoris($elle,$debut, $max_pagination);
				$wherefollow = 'AND (('.sql_in('m.id_auteur', $elle) .')'
					. ' OR '.sql_in('m.id_me', array_keys($fav)).')';
			}
			else {
				$wherefollow = "AND 0=1";
				$fav = array();
			}
			break;

	}

	# tri par "time segments" :
	#    1 heure, 1 jour, 1 semaine, 1 mois, 3 mois, et le reste
	#    dans chaque segment, tri par pertinence
	#    NOTE: pour savoir dans quel segment on est, recalculer time()-date :(
	$segments = array(1, 24, 7*24, 31*24, 90*24, 365*24);
	$scores = array();
	foreach($segments as $k => $duree) {
		$d = date('Y-m-d H:i:s', time()-3600*$duree);
		$scores[] = "CASE WHEN (m.date > ".sql_quote($d).") THEN $k";
	}
	$tseg = '('
		.join(' ELSE ', $scores) . " ELSE "
		. (1+count($segments))
		.str_repeat(' END', count($segments))
		.') AS tseg';



	# fulltext
	$key = "`texte`";
	$r = trim(preg_replace(',\s+,', ' ', $env['recherche']));

	// si espace, ajouter la meme chaine avec des guillemets pour ameliorer la pertinence
	$pe = (strpos($r, ' ') AND strpos($r,'"')===false)
		? sql_quote(trim("\"$r\"")) : '';

	// On utilise la translitteration pour contourner le pb des bases
	// declarees en iso-latin mais remplies d'utf8
	if (($r2 = translitteration($r)) != $r)
		$r .= ' '.$r2;

	$p = sql_quote(trim("$r"));

	$val = $match = "MATCH($key) AGAINST ($p)";
	// Une chaine exacte rapporte plein de points
	if ($pe)
		$val .= "+ 2 * MATCH($key) AGAINST ($pe)";

	// si symboles booleens les prendre en compte
	if ($boolean = preg_match(', [+-><~]|\* |".*?",', " $r "))
		$val = $match = "MATCH($key) AGAINST ($p IN BOOLEAN MODE)";

	$res = sql_allfetsel("SQL_CALC_FOUND_ROWS r.id_me AS id, m.date, $val AS score, $tseg", "spip_me_recherche AS r INNER JOIN spip_me AS m ON r.id_me=m.id_me", "$match AND m.statut='publi'$wheredate$wherefollow", null, 'tseg ASC, score DESC'
	, "$debut,$max_pagination"
	);
	$t = sql_fetch(mysql_query("SELECT FOUND_ROWS() as total"));
	# remplir avant debut, avec du vide
	for ($i=0; $i< $debut; $i++) {
		array_unshift($res, 0);
	}
	# remplir apres fin, avec du vide
	$grand_total = min(2000, intval($t['total']));
	for ($i=count($res); $i < $grand_total; $i++) {
		array_push($res, 0);
	}

	return $res;
}

/* recherche dans les sites syndiqués, methode fulltext */
function inc_syndicrecherche_to_array_dist($u) {
	if (!$env = @unserialize($u))
		return false;
	$key = "`url_site`,`titre`,`texte`";
	$r = trim(preg_replace(',\s+,', ' ', $env['recherche']));
	$p = sql_quote(trim("$r"));

	$val = $match = "MATCH($key) AGAINST ($p)";

	$res = sql_allfetsel("id_syndic AS id, url_site AS url, $val AS score", "spip_syndic", "$match AND statut='publie'", null, 'score DESC'
	, "0,30"
	);

	return $res;
}

function liste_favoris($qui,$debut=0,$max_pagination=500) {
	$r = array();

	if (!is_array($qui))
		$qui = array($qui);

	# en deux temps, car je cherche en priorité la date de mes fav,
	# puis celle de mes amis
	$moi = array_shift($qui);

	if ($f = sql_allfetsel('s.id_me, UNIX_TIMESTAMP(s.date) as date', 'spip_me_share AS s LEFT JOIN spip_me AS m ON s.id_me=m.id_me', 's.id_auteur='.$moi.' AND m.statut="publi" AND m.id_parent=0', 's.id_me', array('date DESC'), '0,'.($debut+$max_pagination))) {
		foreach ($f as $m) {
			$me = intval($m['id_me']);
			$r[$me] = (int) $m['date'];
		}
	}

	if ($qui) {
		$auteurs = sql_in('s.id_auteur', $qui);
		if ($f = sql_allfetsel('s.id_me, UNIX_TIMESTAMP(s.date) as date', 'spip_me_share AS s LEFT JOIN spip_me AS m ON s.id_me=m.id_me', $auteurs.' AND m.statut="publi" AND m.id_parent=0', 's.id_me', array('date DESC'), '0,'.($debut+$max_pagination))) {
			foreach ($f as $m) {
				$me = intval($m['id_me']);
				if (!isset($r[$me]))
					$r[$me] = (int) $m['date'];
			}
		}
	}

	return $r;
}

function liste_pointe_sql($debut, $max_pagination, $login, $moi, $nous) {

	# on cherche des messages relativement recents et interessants
	$pointe = array();

	# les mentions @login vers moi :
	$mentions = sql_allfetsel('id_me', 'spip_me_auteur', 'id_auteur='.$moi, '', 'date DESC', '0,'.($debut + $max_pagination));
	$pointe = array_merge($pointe, array_map('array_pop', $mentions));

	# les messages qui parlent d'un sujet qui m'interesse
	if ($tags = liste_tags($moi)) {
		$mentions = sql_allfetsel('id_me', 'spip_me_mot', sql_in('id_mot', $tags), '', 'date DESC', '0,'.($debut + $max_pagination));
		$pointe = array_merge($pointe, array_map('array_pop', $mentions));
	}

	# les messages qui parlent d'un URL qui m'interesse
	if ($urls = liste_urls($moi)) {
		$mentions = sql_allfetsel('id_me', 'spip_me_syndic', sql_in('id_syndic', $urls), '', 'date DESC', '0,'.($debut + $max_pagination));
		$pointe = array_merge($pointe, array_map('array_pop', $mentions));
	}

	# les messages mis en favoris par $nous
	$mentions = sql_allfetsel('id_me', 'spip_me_share', sql_in('id_auteur', $nous), '', 'date DESC', '0,'.($debut + $max_pagination));
	$pointe = array_merge($pointe, array_map('array_pop', $mentions));

	# les messages auxquels j'ai repondu
	$mentions = sql_allfetsel('DISTINCT(id_parent) as id', 'spip_me', "id_auteur=$moi AND id_parent>0 AND statut='publi'", '', 'date DESC', '0,'.($debut + $max_pagination));
	$pointe = array_merge($pointe, array_map('array_pop', $mentions));

	$pointe = array_unique($pointe);

	if ($pointe)
		return " OR ".sql_in('id_me', $pointe);
}


function liste_tags($moi) {
	$tags = array();
	foreach(sql_allfetsel('id_mot', 'spip_me_follow_mot', 'id_follow='.$moi) as $m) {
		$titre_mot = sql_fetsel('titre,texte', 'spip_mots', "id_mot=".$m['id_mot']);
		# cette merde a cause de l'encodage pourri de la base ; a revoir
		# cas test : http://localhost/seenthis/tags/pr%C3%A9carit%C3%A9?var_mode=recalcul
		$titre_mot = importer_charset($titre_mot['titre'], 'iso-8859-1');
		$check[] = 'titre like '.sql_quote(trim($titre_mot).'%');
	}

	# ici au lieu d'envoyer array($t) il faudrait vérifier si on a mis un $
	# et dans le cas contraire, lister les tags qui commencent par titre_mot
	# OU ALORS, indexer sur @mots ___TAG*
	if ($check) {
		foreach (sql_allfetsel('id_mot,titre', 'spip_mots', JOIN(' OR ', $check)) as $m) {
			$tags[] = $m['id_mot']; #CRC32(/*mb_strtolower*/trim(importer_charset($m['titre'], 'iso-8859-1')));
		}
	}

	return $tags;
}

function liste_urls($moi) {
	$urls = sql_allfetsel('id_syndic', 'spip_me_follow_url', 'id_follow='.$moi);
	return array_map('array_pop', $urls);
}
