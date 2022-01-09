<?php

/**
 * Notifier appelé quand on partage un message
 * @param $id_auteur_partage string celui qui fait le partage
 * @param $id_me string l'identifiant du message
 */
function notifier_partage($id_auteur_partage, $id_me) {
	// vérifier que le message est toujours partagé
	if (!sql_countsel('spip_me_share', 'id_me=' . sql_quote($id_me) . ' AND id_auteur=' . sql_quote($id_auteur_partage))) {
		return;
	}

	// vérifier que l'auteur.e du message est bien intéressé.e par le partage
	$query_auteur = sql_select("id_auteur", "spip_me", "id_me=$id_me AND statut='publi'");
	$row_auteur = sql_fetch($query_auteur);
	if (!$row_auteur) {
		return;
	}

	$id_auteur = $row_auteur['id_auteur'];

	// vérifie que l'auteur.e est intéressé.e par le partage
	if (!tester_mail_auteur($id_auteur, "mail_partage")) {
		return;
	}

	$seenthis = $GLOBALS['meta']['nom_site']; # "Seenthis";


	$query_dest = sql_select("*", "spip_auteurs", "id_auteur = $id_auteur");
	$row_dest = sql_fetch($query_dest);
	if (!$row_dest) {
		return;
	}
	$email_dest = $row_dest["email"];

	if (strlen(trim($email_dest)) <= 3) {
		return;
	}

	$query_aut_partage = sql_select("*", "spip_auteurs", "id_auteur = $id_auteur_partage AND id_auteur NOT IN (select id_auteur FROM spip_me_block WHERE id_block = $id_auteur)");
	$row_aut_partage = sql_fetch($query_aut_partage);
	if (!$row_aut_partage) {
		return;
	}

	$nom_aut_partage = $row_aut_partage["nom"];
	$login_aut_partage = $row_aut_partage["login"];

	$nom_dest = nom_auteur($id_auteur);
	$lang = $row_dest["lang"];

	$url_aut_partage = url_absolue(generer_url_entite($id_auteur_partage, 'auteur', '', '', true));

	$titre_mail = _T('seenthis:notif_partage_titre', array(
		'nom_aut_partage' => $nom_aut_partage,
		'login_aut_partage' => $login_aut_partage,
		'seenthis' => $seenthis,
		'spip_lang' => $lang
	));
	$annonce = _T('seenthis:notif_partage_texte', array(
		'nom_dest' => $nom_dest,
		'nom_aut_partage' => $nom_aut_partage,
		'login_aut_partage' => $login_aut_partage,
		'seenthis' => $seenthis,
		'spip_lang' => $lang
	));

	$texte_message = message_texte(texte_de_me($id_me));
	$footer = seenthis_message_footer($lang, $seenthis);
	$corps_mail['texte'] = "\n\n$annonce\n$url_aut_partage\n\n$texte_message\n\n$footer";
	$corps_mail['headers'] = "Message-Id: <$id_auteur.$id_auteur_partage." . time() . "@" . _HOST . ">\n";
	seenthis_envoyer_mail($email_dest, $titre_mail, $corps_mail);
	spip_log("notifier partage $id_me part $id_auteur_partage pour $id_auteur", 'notifier');

}

/**
 * Get the footer of a message
 * @param $lang string the user lang
 * @param $seenthis string the site name
 * @return string the footer to be used
 */
function seenthis_message_footer($lang, $seenthis) {
	return _T('seenthis:notif_message_footer', array(
		'seenthis' => $seenthis,
		'url' =>  _HTTPS . "://" . _HOST,
		'spip_lang' => $lang
	));
}

/**
 * Envoie un email (avec le plugin facteur)
 */
function seenthis_envoyer_mail($email_dest, $titre_mail, $corps_mail) {
	$envoyer_mail = charger_fonction('envoyer_mail', 'inc');
	$envoyer_mail($email_dest, $titre_mail, $corps_mail);
}

/**
 * Notifier appelé quand un auteur en suit un autre
 * @param $id_auteur string celui qui est suivi => celui à prévenir
 * @param $id_follow string celui qui suit
 */
function notifier_suivre_moi($id_auteur, $id_follow) {
	// verifier que l'on est bien suivi (cas à éviter : je clique par erreur
	// sur "suivre @machin", puis je reclique pour ne plus le suivre)
	if (!sql_countsel('spip_me_follow', 'id_follow=' . sql_quote($id_follow) . ' AND id_auteur=' . sql_quote($id_auteur))) {
		spip_log('pas de notification pour un auteur non suivi', 'suivre');
		return;
	}

	// est ce que la personne veut bien recevoir le mail
	if (!tester_mail_auteur($id_auteur, "mail_suivre_moi")) {
		return;
	}

	$query_aut = sql_select("*", "spip_auteurs", "id_auteur = $id_follow");
	if ($row_aut = sql_fetch($query_aut)) {
		$nom_aut = $row_aut["nom"];
		$login_aut = $row_aut["login"];

		$query_dest = sql_select("*", "spip_auteurs", "id_auteur = $id_auteur");
		if ($row_dest = sql_fetch($query_dest)) {
			$nom_dest = $row_dest["nom"];
			$email_dest = $row_dest["email"];
			$lang = $row_dest["lang"];

			if (strlen(trim($email_dest)) > 3) {

				include_spip("inc/filtres_mini");
				$url_me = url_absolue(generer_url_entite($id_follow, 'auteur', '', '', true));

				$seenthis = $GLOBALS['meta']['nom_site']; # "Seenthis";
				$titre_mail = _T('seenthis:notif_suivremoi_titre', array(
					'nom_aut' => $nom_aut,
					'login_aut' => $login_aut,
					'seenthis' => $seenthis,
					'spip_lang' => $lang
				));
				$annonce = _T('seenthis:notif_suivremoi_texte', array(
					'nom_dest' => $nom_dest,
					'nom_aut' => $nom_aut,
					'login_aut' => $login_aut,
					'seenthis' => $seenthis,
					'spip_lang' => $lang
				));

				$footer = seenthis_message_footer($lang, $seenthis);
				$corps_mail['texte'] = "\n\n$annonce\n$url_me\n\n$footer";
				$corps_mail['headers'] = "Message-Id: <$id_auteur.$id_follow." . time() . "@" . _HOST . ">\n";

				seenthis_envoyer_mail($email_dest, "$seenthis - $titre_mail", $corps_mail);
				spip_log("notification: @$login_aut suit @" . $row_dest['login'], 'suivre');
			}
		}
	}
}

/**
 * Notifier appelé quand un message a été posté
 * @param $id_me string l'identifiant du message
 * @param $id_parent string l'identifiant du parent du message
 */
function notifier_me($id_me, $id_parent) {
	$query = sql_select("id_auteur", "spip_me", "id_me=$id_me AND statut='publi'");
	$row = sql_fetch($query);
	if (!$row) {
		// message n'est plus là => on sort
		return;
	}

	$id_auteur_me = $row["id_auteur"];
	$texte = texte_de_me($id_me);
	$titre_mail = ($id_parent > 0) ? 'Re: ' . trim(extraire_titre(texte_de_me($id_parent), 100, true)) : trim(extraire_titre($texte, 100, true));
	$nom_auteur = nom_auteur($id_auteur_me);
	$notifier_construire_texte = charger_fonction('notifier_construire_texte', '');
	$texte_mail = $notifier_construire_texte($id_parent, $id_me);
	$texte_mail .= "\n\n" . url_absolue(generer_url_entite($id_me, 'me', '', '', true));

	// va contenir tous les destinataires du mail
	$id_dest = array();

	// on commence par le parent du message
	if ($id_parent > 0) {
		$query_auteur = sql_select("id_auteur", "spip_me", "id_me=$id_parent AND statut='publi'");
		if ($row_auteur = sql_fetch($query_auteur)) {
			$id_auteur = $row_auteur["id_auteur"];
			$nom_auteur_init = nom_auteur($id_auteur);

			// alerte reponse à un billet que j'ai écrit
			if (tester_mail_auteur($id_auteur, "mail_rep_moi")) {
				$id_dest[] = $id_auteur;
			}

			// alerte reponse à un billet favori
			$query_fav = sql_select("id_auteur", "spip_me_share", "id_me=$id_parent");
			while ($row_fav = sql_fetch($query_fav)) {
				$id_auteur = $row_fav["id_auteur"];
				if (tester_mail_auteur($id_auteur, "mail_rep_partage")) {
					$id_dest[] = $id_auteur;
				}
			}

		}
	}

	// auteurs qui suivent l'auteur du message
	$query_follow = sql_select("id_follow", "spip_me_follow", "id_auteur=$id_auteur_me");
	while ($row_follow = sql_fetch($query_follow)) {
		$id_follow = $row_follow["id_follow"];

		if ($id_parent == 0) {
			// alerte nouveau mail interessant
			if (tester_mail_auteur($id_follow, "mail_nouv_billet")) {
				$id_dest[] = $id_follow;
			}
		} else {
			if (tester_mail_auteur($id_follow, "mail_rep_billet")) {
				$id_dest[] = $id_follow;
			}
		}
	}

	// auteurs qui ont participé à la discussion
	if ($id_parent > 0) {
		$query = sql_select("id_auteur", "spip_me", "id_parent=$id_parent AND id_me!=$id_me AND statut='publi'");
		while ($row = sql_fetch($query)) {
			$id_auteur = $row["id_auteur"];

			// alerte nouveau mail interessant
			if (tester_mail_auteur($id_auteur, "mail_rep_conv")) {
				$id_dest[] = $id_auteur;
			}
		}
	} else {
		$id_auteur = 0;
	}

	// destinataires cités dans le message ; sauf s'ils bloquent l'auteur
	include_spip('inc/traiter_texte');
	$t = preg_replace_callback("/" . _REG_URL . "/ui", "_traiter_lien", $texte);
	if (preg_match_all("/" . _REG_PEOPLE . "/i", $t, $people)) {
		$logins = array();
		foreach ($people[0] as $k => $p) {
			$logins[$k] = mb_substr($p, 1); // liste des logins cites
		}
		$s = sql_query($q = 'SELECT m.id_auteur FROM spip_auteurs AS m LEFT JOIN spip_me_block AS b ON b.id_block=m.id_auteur AND b.id_auteur=' . sql_quote($id_auteur_me) . ' WHERE ' . sql_in('m.login', array_unique($logins)) . ' AND b.id_block IS NULL');
		while ($t = sql_fetch($s)) {
			$id_dest[] = $t['id_auteur'];
		}
		unset($logins);
	}

	// auteurs qui suivent les tags présents dans le post
	$t = preg_replace("/"._REG_URL."/ui", "", $texte);
	if (preg_match_all("/"._REG_HASH."/ui", $t, $tags)) {
		foreach(array_unique(array_values($tags[0])) as $tag) {
			$query_follow = sql_select('id_follow', 'spip_me_follow_tag', "tag REGEXP ". sql_quote('^' . $tag));
			while ($row_follow = sql_fetch($query_follow)) {
				if (tester_mail_auteur($row_follow['id_follow'], 'mail_tag_suivi')) {
					$id_dest[] = $row_follow['id_follow'];
				}
			}
		}
	}

	$id_dest = pipeline('seenthis_notifierme_destinataires',
		array(
			'args'=>array('id_me'=>$id_me,'id_parent'=>$id_parent),
			'data'=>$id_dest
		)
	);

	// ne pas envoyer vers soi-même
	$remove = [$id_auteur_me];

	// ni vers les comptes bloques
	$s = sql_query($q = 'SELECT id_auteur FROM spip_me_block WHERE id_block IN (' . sql_quote($id_auteur_me).','.sql_quote($id_auteur).')');
	while ($t = sql_fetch($s)) {
		$remove[] = $t['id_auteur'];
	}

	// ni vers les comptes qui bloquent
	$u = sql_select('id_block', 'spip_me_block', "id_auteur=$id_auteur_me");
	while ($v = sql_fetch($u)) {
		$remove[] = $v['id_auteur'];
	}

	foreach ($remove as $id_r) {
		foreach ($id_dest as $k => $id) {
			if ($id == $id_r)
				unset($id_dest[$k]);
		}
	}


	// Ajouter l'auteur.e du message si elle a coche la case correspondante
	if (tester_mail_auteur($id_auteur_me, "mail_mes_billets")) {
		$id_dest[] = $id_auteur_me;
	}

	// Envoyer si besoin
	if (isset($id_dest) and count($id_dest) > 0) {
		$seenthis = $GLOBALS['meta']['nom_site'];
		mb_internal_encoding("UTF-8");
		$corps_mail['nom_envoyeur'] = mb_encode_mimeheader(str_replace('@', '', $nom_auteur) .' - '. lire_meta('nom_site'), "UTF-8", "Q");

		$corps_mail['headers'] = "Message-Id: <$id_me" . "@" . _HOST . ">\n";

		if ($id_parent > 0) {
			$corps_mail['headers'] = "Message-Id: <$id_me." . md5($nom_auteur) . "@" . _HOST . ">\n"
				. "In-Reply-To: <$id_parent" . "@" . _HOST . ">\n";
		}

		$id_dest = join(",", $id_dest);
		spip_log("$id_me($id_parent) : destinataires=$id_dest", 'notifier');

		$query_dest = sql_select("*", "spip_auteurs", "id_auteur IN ($id_dest)");
		while ($row_dest = sql_fetch($query_dest)) {
			$email_dest = $row_dest["email"];
			if (strlen(trim($email_dest)) > 3) {
				$lang = $row_dest["lang"];
				spip_log("notifier $id_me($id_parent) a $email_dest", 'notifier');

				if ($id_parent == 0) {
					$annonce = _T('seenthis:notif_me_nouveau', array('nom_auteur' => $nom_auteur, 'spip_lang' => $lang));
				} else {
					if ($nom_auteur == $nom_auteur_init) {
						$annonce = _T('seenthis:notif_me_reponse', array('nom_auteur' => $nom_auteur, 'spip_lang' => $lang));
					} else {
						$annonce = _T('seenthis:notif_me_reponse_a', array('nom_auteur' => $nom_auteur, 'nom_auteur_init' => $nom_auteur_init, 'spip_lang' => $lang));
					}
				}

				$footer = seenthis_message_footer($lang, $seenthis);
				$corps_mail['texte'] = "$annonce\n\n$texte_mail\n\n\n\n$footer";
				seenthis_envoyer_mail($email_dest, $titre_mail, $corps_mail);
			}
		}

	}
}

function notifier_construire_texte_dist($id_parent, $id_me) {
	if (!$id_parent) $id_parent = $id_me;
	$conversation = sql_allfetsel("id_me, id_auteur", "spip_me", "(id_me=$id_parent OR id_parent=$id_parent) AND statut='publi' AND id_me <= $id_me ORDER BY date");

	$max = 5;
	if (count($conversation) <= $max + 3)
		$max = 10000;

	$blabla = "\n(... " . (count($conversation) - $max - 1) . " messages...)\n\n";
	$ret = '';
	foreach ($conversation as $i => $row) {
		if ($i == 0 OR $i >= count($conversation) - $max) {
			$nom_auteur = nom_auteur($row["id_auteur"]);
			$id_c = $row["id_me"];
			$texte = texte_de_me($id_c);
			$ret .= ($id_c == $id_me)
				? "\n$nom_auteur " . message_texte(($texte)) . "\n\n"
				: seenthis_email_quote( $nom_auteur . ' ' . trim(extraire_titre($texte)) )
					. "\n> ---------\n";
		} else {
			$ret .= $blabla;
			$blabla = '';
		}
	}

	return $ret;

}

function seenthis_email_quote($t) {
	return trim(join("\n> ", explode("\n", trim(seenthis_mb_wordwrap(
	"> ".$t, 65, "\n")))));
}


// http://stackoverflow.com/questions/3825226/multi-byte-safe-wordwrap-function-for-utf-8
function seenthis_mb_wordwrap ($str, $width = 75, $break = "\n", $cut = false) {
	$lines = explode($break, $str);
	foreach ($lines as &$line) {
		$line = rtrim($line);
		if (mb_strlen($line) <= $width)
			continue;
		$words = explode(' ', $line);
		$line = '';
		$actual = '';
		foreach ($words as $word) {
			if (mb_strlen($actual.$word) <= $width)
				$actual .= $word.' ';
			else {
				if ($actual != '')
					$line .= rtrim($actual).$break;
				$actual = $word;
				if ($cut) {
					while (mb_strlen($actual) > $width) {
						$line .= mb_substr($actual, 0, $width).$break;
						$actual = mb_substr($actual, $width);
					}
				}
				$actual .= ' ';
			}
		}
		$line .= trim($actual);
	}
	return implode($break, $lines);
}

