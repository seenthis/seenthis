<?php

function notifier_suivre_moi ($id_auteur, $id_follow) {
	//mail_suivre_moi
	// $id_auteur => celui qui est suivi => celui à prévenir
	// $id_follow => celui qui suit

	// verifier que l'on est bien suivi (cas à éviter : je clique par erreur
	// sur "suivre @machin", puis je reclique pour ne plus le suivre)
	if (!sql_countsel('spip_me_follow', 'id_follow='.sql_quote($id_follow).' AND id_auteur='.sql_quote($id_auteur))) {
		spip_log('pas de notification pour un auteur non suivi', 'suivre');
		return;
	}

	if (tester_mail_auteur($id_auteur, "mail_suivre_moi")) {
		$seenthis = $GLOBALS['meta']['nom_site']; # "Seenthis";

		$from = "$seenthis <no-reply@"._HOST.">";
		//$headers .= 'Content-Type: text/plain; charset="utf-8"'."\n";

		//$headers .= "Content-Transfer-Encoding: 8bit\n";

		$headers = "Message-Id: <$id_auteur.$id_follow.".time()."@"._HOST.">\n";

		$query_dest = sql_select("*", "spip_auteurs", "id_auteur = $id_follow");
		if ($row_dest = sql_fetch($query_dest)) {
			$nom_aut = $row_dest["nom"];
			$login_aut = $row_dest["login"];
		}

		$query_dest = sql_select("*", "spip_auteurs", "id_auteur = $id_auteur");
		if ($row_dest = sql_fetch($query_dest)) {
			$nom_dest = $row_dest["nom"];
			$email_dest = $row_dest["email"];
			$lang = $row_dest["lang"];

			if (strlen(trim($email_dest)) > 3) {

				include_spip("inc/filtres_mini");
				$url_me = "http://"._HOST."/".generer_url_entite($id_follow,"auteur");

				if ($lang == "en") {

					$titre_mail = _L("$nom_aut is following you on $seenthis.");
					$annonce = _L("Hi $nom_dest,\n\n$nom_aut (@$login_aut) is following you on $seenthis.");
				} else {
					$titre_mail = _L("$nom_aut vous suit sur $seenthis.");
					$annonce = _L("Bonjour $nom_dest,\n\n$nom_aut (@$login_aut) vous suit sur $seenthis.");
				}

				$lien = _L("\n\n---------\nPour ne plus recevoir d'alertes de $seenthis,\n vous pouvez régler vos préférences dans votre profil\nhttp://"._HOST."\n\n");

				$envoyer = "\n\n$annonce\n$url_me\n\n$lien";
				//echo "<hr /><pre>$envoyer</pre>";

				//$titre_mail = mb_encode_mimeheader(html_entity_decode($titre_mail, null, 'UTF-8'), 'UTF-8');
				$envoyer_mail = charger_fonction('envoyer_mail','inc');
				$envoyer_mail("$email_dest", "$seenthis - $titre_mail", "$envoyer", $from, $headers);

				spip_log("notification: @$login_aut suit @".$row_dest['login'], 'suivre');

			}
		}
	}
}

function notifier_me($id_me, $id_parent) {

	$query = sql_select("id_auteur", "spip_me", "id_me=$id_me AND statut='publi'");
	if ($row = sql_fetch($query)) {
		$id_auteur_me = $row["id_auteur"];

		$texte = texte_de_me($id_me);
		$titre_mail = trim(extraire_titre($texte));

		$nom_auteur = nom_auteur($id_auteur_me);

		$id_dest = array();

		if ($id_parent > 0) {
			$query_auteur = sql_select("id_auteur", "spip_me", "id_me=$id_parent");
			if ($row_auteur = sql_fetch($query_auteur)) {
				$id_auteur = $row_auteur["id_auteur"];
				$nom_auteur_init = nom_auteur($id_auteur);

				// alerte reponse a mon billet
				if (tester_mail_auteur($id_auteur, "mail_rep_moi")) {
					$id_dest[] = $id_auteur;
				}

				// alerte reponse a un billet favori
				$query_fav = sql_select("id_auteur", "spip_me_share", "id_me=$id_parent");
				while ($row_fav = sql_fetch($query_fav)) {
					$id_auteur = $row_fav["id_auteur"];

					if (tester_mail_auteur($id_auteur, "mail_rep_moi")) {
						$id_dest[] = $id_auteur;
					}
				}

			}
		}

		$texte_mail = notifier_construire_texte($id_parent, $id_me);
		$texte_mail .= ($id_parent > 0)
			? "\n\nhttp://"._HOST."/messages/$id_parent#message$id_me"
			: "\n\nhttp://"._HOST."/messages/$id_me";

		// auteurs qui suivent l'auteur
		$query_follow = sql_select("id_follow", "spip_me_follow", "id_auteur=$id_auteur_me");
		while ($row_follow = sql_fetch($query_follow)) {
			$id_follow = $row_follow["id_follow"];

			if($id_parent == 0) {
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
			$query = sql_select("id_auteur", "spip_me", "id_parent=$id_parent AND id_me!=$id_me");
			while ($row = sql_fetch($query)) {
				$id_auteur = $row["id_auteur"];

				// alerte nouveau mail interessant
				if (tester_mail_auteur($id_auteur, "mail_rep_conv")) {
					$id_dest[] = $id_auteur;
				}
			}
		}

		// destinataires cités dans le message ; sauf s'ils bloquent l'auteur
		include_spip('inc/traiter_texte');
		$t = preg_replace_callback("/"._REG_URL."/ui", "_traiter_lien", $texte);
		if (preg_match_all("/"._REG_PEOPLE."/i", $t, $people)) {
			$logins = array();
			foreach ($people[0] as $k=>$p) {
				$logins[$k] = mb_substr($p,1); // liste des logins cites
			}
			$s = sql_query($q = 'SELECT m.id_auteur FROM spip_auteurs AS m LEFT JOIN spip_me_block AS b ON b.id_block=m.id_auteur AND b.id_auteur='.sql_quote($id_auteur_me).' WHERE '.sql_in('m.login', array_unique($logins)).' AND b.id_block IS NULL');
			while($t = sql_fetch($s)) {
				$id_dest[] = $t['id_auteur'];
			}
			unset($logins);
		}

		// toutes les raisons precedentes ne doivent jamais envoyer a l'auteur
		// lui-meme : on filtre
		foreach($id_dest as $k=>$id) {
			if ($id == $id_auteur_me)
				unset($id_dest[$k]);
		}

		// Ajouter l'auteur.e du message si elle a coche la case correspondante
		if (tester_mail_auteur($id_auteur_me, "mail_mes_billets")) {
			$id_dest[] = $id_auteur_me;
		}

		// Envoyer
		if (isset($id_dest)) {

			$from = $nom_auteur." - ".$GLOBALS['meta']['nom_site']." <no-reply@"._HOST.">";
			$headers = "Message-Id:<$id_me@"._HOST.">\n";

			if ($id_parent > 0) $headers .= "In-Reply-To:<$id_parent@"._HOST.">\n";

			$id_dest = join(",", $id_dest);

			spip_log("$id_me($id_parent) : destinataires=$id_dest", 'notifier');

			spip_log("$id_me($id_parent) : destinataires=$id_dest", 'notifier');

			$query_dest = sql_select("*", "spip_auteurs", "id_auteur IN ($id_dest)");
			while ($row_dest = sql_fetch($query_dest)) {
				$nom_dest = $row_dest["nom"];

				$email_dest = $row_dest["email"];
				$lang = $row_dest["lang"];
				spip_log("notifier $id_me($id_parent) a $email_dest", 'notifier');

				if (strlen(trim($email_dest)) > 3) {

					if ($lang == "en") {
						if ($id_parent == 0) {
							$annonce = _L("$nom_auteur has posted a new message");
						} else {
							if ($nom_auteur == $nom_auteur_init ) $annonce = _L("$nom_auteur has answered his/her own message");
							else $annonce = _L("$nom_auteur has answered to $nom_auteur_init");
						}
					} else {
						if ($id_parent == 0) {
							$annonce = _L("$nom_auteur a posté un nouveau billet");
						} else {
							if ($nom_auteur == $nom_auteur_init ) $annonce = _L("$nom_auteur a répondu à un de ses billets");
							else $annonce = _L("$nom_auteur a répondu à un billet de $nom_auteur_init");
						}
					}

					$lien = _L("\n---------\nPour ne plus recevoir d'alertes de Seenthis,\nvous pouvez régler vos préférences dans votre profil\n\n");

					$envoyer = "$annonce\n\n$texte_mail\n\n\n\n$lien";
					$envoyer_mail = charger_fonction('envoyer_mail','inc');
					$envoyer_mail("$email_dest", "$titre_mail", "$envoyer", $from, $headers);

				}
			}

		}

	}
}

function notifier_construire_texte($id_parent, $id_me) {
	if (!$id_parent) $id_parent = $id_me;
	$conversation = sql_allfetsel("id_me, id_auteur", "spip_me", "(id_me=$id_parent OR id_parent=$id_parent) AND statut='publi' AND id_me <= $id_me ORDER BY date");

	$max = 5;
	if (count($conversation) <= $max+3)
		$max = 10000;

	$blabla = "\n(... ".(count($conversation) - $max - 1)." messages...)\n\n";

	foreach ($conversation as $i=>$row) {
		if ($i == 0 OR $i >= count($conversation) - $max) {
			$nom_auteur = nom_auteur($row["id_auteur"]);
			$id_c = $row["id_me"];
			$texte = texte_de_me($id_c);
			$ret .= ($id_c == $id_me)
					? "\n$nom_auteur ".message_texte(($texte))."\n\n"
					: "> $nom_auteur ".trim(extraire_titre($texte))."\n> ---------\n";
		} else {
			$ret .= $blabla;
			$blabla = '';
		}
	}

	return $ret;
	
}


