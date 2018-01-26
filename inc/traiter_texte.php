<?php

function _traiter_hash ($regs) {
	$tag = substr($regs[0],1); // supprimer le '#'

	$url = 'tag/'.mb_strtolower($tag,'UTF-8');

	$url = urlencode_1738_plus($url);

	$le_hash = "<span class='lien_tag'>#<a href=\"$url\">$tag</a></span>";

	$GLOBALS["num_hash"] ++;
	
	$GLOBALS["les_hashs"][$GLOBALS["num_hash"]] = $le_hash;
	
	
	return "XXXHASH".$GLOBALS["num_hash"]."HASHXXX";
	
}

function _traiter_code($regs) {

	// marquage de langage genre ```php, pour coloration syntaxique
	if (strlen($regs[1])
	AND preg_match('/^(.*?)\n(.*)$/ms', $regs[1], $r)) {
		$lang = $r[1];
		$code = $r[2];
	} else {
		$code = $regs[2];
	}

	$le_hash = traiter_echap_code_dist(array(null,null,null,$code));

	// dirty pour recuperer un code avec des lignes alternees (pour coloration)
	$le_hash = str_replace('<br>', '</code></div><div class="spip_code"><code>', $le_hash);

	$GLOBALS["num_hash"] ++;
	$GLOBALS["les_hashs"][$GLOBALS["num_hash"]] = $le_hash;

	return "XXXHASH".$GLOBALS["num_hash"]."HASHXXX";
}

function _traiter_people ($regs) {
	$tag = mb_substr($regs[0], 1, 1000);
	
	$query = sql_query("SELECT id_auteur,login
		FROM spip_auteurs
		WHERE login=".sql_quote(mb_strtolower($tag,'UTF8'))
			." AND statut!='5poubelle'");
	if ($k = sql_fetch($query)) {
		$GLOBALS['destinataires'][] = microcache($k['id_auteur'], 'noisettes/logo_auteur/message_logo_auteur_small');
		$url = 'people/'.urlencode_1738_plus(mb_strtolower($k['login'],'UTF8'));

		$res = "<span class='lien_people'>@<a href='$url'>$tag</a></span>";

		// echapper pour eviter la typo sur @_cym_
		// return $res;
		$GLOBALS["num_lien"] ++;
		$GLOBALS["les_liens"][$GLOBALS["num_lien"]] = $res;
		return "XXXLIEN".$GLOBALS["num_lien"]."LIENXXX";
	}
	else return "@$tag";
}


function _creer_lien_riche($lien) {

	$lien_or = $lien;

	// lien local ?
	if (FALSE
	AND preg_match(',^https?://('
		.preg_quote(_HOST).'/messages/(\d+)|'
		.preg_quote(_SHORT_HOST).'/([a-f0-9]+)'
		.'),',
	$lien, $r)) {
		if ($r[3]) # short
			$id_me = base_convert($r[3],36,10);
		else
			$id_me = $r[2];

		if ($id_me
		AND $t = texte_de_me($id_me)) {
			$t = array_filter(explode("\n", $t));
			$titre = supprimer_tags(typo_seenthis(couper($t[0], 60)));
			if (!strlen($titre)) $titre = $lien;
			return "<style>    a.internal-link { text-decoration: none;  } a.internal-link span.titre { text-decoration: underline;  }  a.internal-link span.url {     display:inline-block;     width:1px;     height:1px;     overflow:hidden;     color:transparent;    }    a.internal-link::before {     content: '❝';  }    a.internal-link::after {     content: '❞';  text-decoration: none;}    }    </style>    <a href='$lien' class='internal-link'><span class='url'>$lien </span><span class='titre'>$titre</span></a>";
		}
	}


	// Supprimer slash final
	$lien = preg_replace(",/$,", "", $lien);

	// Gérer les images seafile (seafile officiels seulement, pour le moment)
	if (FALSE
	AND preg_match(',^http://cloud.seafile.com/f/,', $lien)
	AND $g = file_get_contents(copie_locale($lien))
	AND $b = extraire_balises($g, 'img')) {
		foreach($b as $img) {
			if (extraire_attribut($img, 'id') == 'image-view'
			AND $l = extraire_attribut($img, 'src'))
				$lien = url_absolue($l, $lien);
		}
	}

	// Si ça ressemble à une image, inclure la vignette sur place
	if (preg_match(",\.(png|gif|jpe?g|svg),i", $lien)) {
		include_spip('seenthis_fonctions'); # pour afficher_miniature

		// Gérer les images en lien dropbox (remplacer www par dl)
		$lien = preg_replace("/^(https\:\/\/)(www)(\.dropbox\.com\/.*\/.*\/.*)$/", '\1dl\3', $lien);

		// Gérer les images de commons.wikimedia
		if (preg_match("/^https?\:\/\/commons\.wikimedia\.org\/wiki\/File\:(.*)/i", $lien, $regs)) {
			$md5 = md5($regs[1]);
			$lien = 'https://upload.wikimedia.org/wikipedia/commons/' . $md5[0] . '/' . $md5[0] . $md5[1] . '/' . urlencode($regs[1]);
		}

		// liens vers des ressources github (ajouter ?raw=true)
		if (preg_match(",^https://(github\.com/[^/]+/[^/]+)/blob/(.*)$,",
		$lien)) {
			$lien = parametre_url($lien, 'raw', 'true');
		}

		// hacker temporairement adresse_site de maniere a obtenir
		// les images des serveurs qui controlent le referer…
		// cf. inc/distant ligne 632
		$a = $GLOBALS['meta']["adresse_site"];
		$GLOBALS['meta']["adresse_site"] = preg_replace(',(://.+?)/.*$,', '$1', $lien);
		$image = afficher_miniature($lien, 600, 400);
		$GLOBALS['meta']["adresse_site"] = $a;

		if ($image) {
			# gif animé
			if (preg_match(",\.(gif)$,i", $lien)) {
				$image = inserer_attribut($image, "src", $lien);
			}
			// Attention: 
			// plus tard dans le traitement, un preg supprimer les retour à la ligne entre les images, de façon
			// à afficher plusieurs images successives sur la même ligne.
			// L'insertion du lien caché ne doit pas casser ça.
			$lien_off= "<span class=\"lien_court\"><span class=\"lien_off\"> $lien </span></span>";
			$image = str_replace("</a>", $lien_off."</a>", $image);

			return "<pics>$image</pics>";
		}

		//list($width, $height) = @getimagesize($lien);
		//if (($width * $height) >= 300) return;
	}
	
	$query = sql_query("SELECT id_syndic, lang, titre, url_syndic, md5 FROM spip_syndic WHERE url_site=".sql_quote($lien));
	if ($row = sql_fetch($query)) {
		$id_syndic = $row["id_syndic"];
		$lang = $row["lang"];
		$titre = $row["titre"];
		$long = $row["url_syndic"];
		$md5 = $row['md5'];
		
		if (strlen($long) > 0) {
			$lien_or = $long;
			// $lien = $long;
		}
		
		if (strlen($lang) > 1){
			//$nom_lang = traduire_nom_langue($lang);
			$lang = " hreflang=\"$lang\"";
		}
		if (strlen($titre) > 3) {
			$titre = textebrut($titre);
			$titre = str_replace('"', "'", $titre);
			$titre = " title=\"$titre\"";
		}
			
	}

	// Ne faire apparaître le lien_court
	// que si plusieurs billets referencent le lien
	if ($id_syndic) {
		$query_total = sql_query("SELECT count(*) as c
			FROM spip_me
			RIGHT JOIN spip_me_syndic ON spip_me_syndic.id_me=spip_me.id_me
			WHERE spip_me_syndic.id_syndic=$id_syndic
				AND spip_me.statut='publi'");
		include_spip('inc/urls');
		$url = generer_url_entite($id_syndic,'site');

		$r = sql_fetch($query_total);
		$triangle = ($r['c'] > 1) ? '►' : '▻';
		$total = "<span class='lien_lien_total'><a href='$url'>$triangle</a></span>";
	} else
		$total = "";

	include_spip("inc/lien_court");
	$intitule = sucrer_utm(lien_court($lien, 45));

	if (function_exists('recuperer_favicon')) {
		$favicon_file_path = recuperer_favicon($lien_or);
		// Si modif, penser à modifier aussi la fonction supprimer_background_favicon
		if ($favicon_file_path) {
			$favicon_type = pathinfo($favicon_file_path, PATHINFO_EXTENSION);
			$favicon_data = base64_encode(file_get_contents($favicon_file_path));
			$style = " style='background-image:url(data:image/$favicon_type;base64,$favicon_data);'";
		}
	}

	$lien_or = urlencode_1738_plus($lien_or);

	$le_lien = "<span class='lien_lien'$style>$total<a href=\"$lien_or\" class='spip_out'$titre$lang>$intitule</a></span>";

	$le_lien = str_replace("&", "&amp;", $le_lien);
	$le_lien = str_replace("&amp;amp;", "&amp;", $le_lien);


	return $le_lien;
}


function _supprimer_background_favicon($texte) {
	//background-image:url(data:image/png;base64,iVBO...gg==);');
	
	return preg_replace(", style=\'background-image:url\(data:image/[^)]+\);\',","",$texte);
}

function _traiter_lien ($regs) {
	$lien = $regs[0];
	
	// Supprimer parenthese fermante finale si pas de parenthese ouvrante dans l'URL
	$retour_parenthese = "";
	if (preg_match(",\)$,", $lien) && !preg_match(",\(,", $lien)) {
		$lien = preg_replace(",\)$,", "", $lien);
		$retour_parenthese = ")";
	}

	
	# urls inacceptables : numeriques, localhost
	if (preg_match(',^(ftp|https?)://((\d+\.)*\d+|localhost)(/.*)?$,i', $lien))
		return $lien;

	$le_lien = _creer_lien_riche($lien);
	
	$GLOBALS["num_lien"] ++;
	
	$GLOBALS["les_liens"][$GLOBALS["num_lien"]] = $le_lien;
	
	return "XXXLIEN".$GLOBALS["num_lien"]."LIENXXX$retour_parenthese";
}


function _traiter_lien_retablir($regs) {
	$num = $regs[1];

	return $GLOBALS["les_liens"][$num];
}
function _traiter_blocs_retablir($regs) {
	$num = $regs[1];
	
	return trim($GLOBALS["les_blocs"][$num]);
}

function _traiter_hash_retablir($regs) {
	$num = $regs[1];
		
	return trim($GLOBALS["les_hashs"][$num]);
}



function _traiter_block ($regs) {
	$texte = $regs[2];
	
	$texte = preg_replace(",[\x{275d}\x{275e}],u", "", $texte);

	// Cas pathologique: des blocs dans des blocs
	//$texte = preg_replace_callback(",XXXBLOC([0-9]+)BLOCXXX,Uums", "_traiter_blocs_retablir", $texte);
	//$texte = preg_replace(",<\/?blockquote[^>]*>,", "", $texte);
	
	$final = "";

	/*
	if (preg_match(",[»\x{201d}\"]([»\x{201d}\"])$,Uums", $texte, $fin) ) {
		$final = $fin[1];
		
		$texte = preg_replace(",[»\x{201d}\"]$,Uums", "", $texte);
	}
	*/

	$texte = str_replace("~", "TILDE_SEENTHIS", $texte);
	
	$lang = detecter_langue($texte);
	if ($lang) {
		$dir = lang_dir($lang);
		lang_select($lang);
		$texte = typo($texte);
		lang_select();
	} else {
		$texte = typo($texte);
	}
	$texte = typo_seenthis($texte);
	
	$texte = str_replace("TILDE_SEENTHIS", "~", $texte);
	
	
	if ($lang) $inserer = " lang=\"$lang\" dir=\"$dir\"";
	
	$le_bloc = "\n\n<blockquote$inserer><p> $texte </p></blockquote>\n\n";
	$GLOBALS["num_bloc"] ++;
	
	$GLOBALS["les_blocs"][$GLOBALS["num_bloc"]] = $le_bloc;
	
	return "\n\nXXXBLOC".$GLOBALS["num_bloc"]."BLOCXXX\n\n".$final;
}

function _traiter_traiter($reg) {
	echo "<hr>";
	print_r($reg);
	return "AAA";
}

function typo_seenthis($texte) {

	$texte = str_replace("&#8217;", "’", $texte);

	// Remplacer les caractères spéciaux par des lettres,
	// sinon ces caractères spéciaux provoquent eux-mêmes des limites de mots.
	
	$texte = str_replace("*", "seenthisgrassier", $texte);

	// italique-gras _* .... *_
	$texte = preg_replace(",_seenthisgrassier([^<>]*)seenthisgrassier_,Uu", "<strong><i>$1</i></strong>", $texte);

	// gras-italique *_ .... _*
	$texte = preg_replace(",_seenthisgrassier([^<>]*)seenthisgrassier_,Uu", "<strong><i>$1</i></strong>", $texte);

	// gras
	$texte = preg_replace(",\bseenthisgrassier([^<>]*)seenthisgrassier\b,Uu", "<strong>$1</strong>", $texte);

	// un test pas concluant visant à conserver les étoiles en copier/coller
	//$texte = preg_replace(",\bseenthisgrassier([^\ ]+[^<>]*[^\ ]+)seenthisgrassier\b,Uu", "<strong><span class='masquer_texte'>*</span>$1<span class='masquer_texte'>*</span></strong>", $texte);

	// rétablir les etoiles pas utilisees
	$texte = str_replace("seenthisgrassier", "*", $texte);

	//$texte = preg_replace(",\b\_([^\ ]+[^<>]*[^\ ]+)\_\b,Uu", "<em><span class='masquer_texte'>_</span>$1<span class='masquer_texte'>_</span></em>", $texte);

	// italiques
	$texte = preg_replace(",\b\_([^<>]*)\_\b,Uu", "<em>$1</em>", $texte);



	
	return $texte;
}

function _traiter_texte($texte) {

	include_spip("inc/texte");
//	include_spip("php/detecter_langue_fonctions");
	
	// Remplacer \r\n par \n
	// mais sert aussi de ramasse miette: remplace tout de même les \r seuls.
	$texte = preg_replace(",\r\n?,", "\n", $texte);

	// Echapper le `code` et le code multiligne, syntaxe github-markdown
	$texte = preg_replace_callback("/"._REG_CODE."/uims", "_traiter_code", $texte);

	// Echapper les URL
	// (parce que les URL peuvent contenir des «#» qui deviendraient des tags
	$texte = preg_replace_callback("/"._REG_URL."/ui", "_traiter_lien", $texte);

	// echapper les balises HTML
	$texte = str_replace("<", "&lt;", $texte);
	$texte = str_replace(">", "&gt;", $texte);

	// Remplacer les people
	$GLOBALS['destinataires'] = array();
	$texte = preg_replace_callback("/"._REG_PEOPLE."/i", "_traiter_people", $texte);
	$destinataires = $GLOBALS['destinataires'];

	// Remplacer les tags
	$texte = preg_replace_callback("/"._REG_HASH."/ui", "_traiter_hash", $texte);

	$texte = trim($texte);
	
	$texte = preg_replace(",^-,m", "–", $texte);


	// Extraire les citations (paragraphe commençant et se terminant par un «»)

	$texte = preg_replace_callback(",()[[:space:]]?(\x{275d}[^\x{275e}]*\x{275e})()[[:space:]]?(),Uums", "_traiter_block", $texte);

	/*
	// Retester sur paragraphe multiple
	$texte = preg_replace_callback(",(\n+|^)[[:space:]]?([«\x{201c}\"][^«\x{201c}\"]*[»\x{201d}\"])()[[:space:]]?(\n+|$),Uums", "_traiter_block", $texte);
	// Tester d'abord sur paragraphes simples
	$texte = preg_replace_callback(",(\n+|^)[[:space:]]?([«\x{201c}\"][^\n]*[»\x{201d}\"])()[[:space:]]?(\n+|$),Uums", "_traiter_block", $texte);
	// Retester sur paragraphe multiple
	$texte = preg_replace_callback(",(\n+|^)[[:space:]]?([«\x{201c}\"].*[»\x{201d}\"])()[[:space:]]?(\n+|$),Uums", "_traiter_block", $texte);
	*/
	
	// Supprimer dans une variable temporaire les mentions XXX, de facon a recuperer seulement le texte
	$texte_racine = preg_replace(",XXX[A-Z]+([0-9]+)[A-Z]+XXX,Uums", "", $texte);
	$lang = detecter_langue($texte_racine);

	$texte = str_replace("~", "TILDE_SEENTHIS", $texte);

	if ($lang) {
		$dir = lang_dir($lang);
		lang_select($lang);
		$texte = typo($texte);
		lang_select();
	} else {
		$texte = typo($texte);
	}
	$texte = typo_seenthis($texte);

	$texte = str_replace("TILDE_SEENTHIS", "~", $texte);

	// Detacher les blocs du reste du texte, afin de bien fermer et ouvrir les paragraphes.
	$texte = str_replace("<blockquote>", "\n\n<blockquote>", $texte);
	$texte = str_replace("</blockquote>", "</blockquote>\n\n", $texte);

	$texte = "<p>$texte</p>";

	$preg = ",(<a rel='shadowbox\[Portfolio\].*<\/a>)\n+(<a rel='shadowbox\[Portfolio\].*<\/a>),";

	while (preg_match("$preg", $texte)) {
		$texte = preg_replace("$preg", "$1$2", $texte);
	}

	$texte = preg_replace_callback(",XXXBLOC([0-9]+)BLOCXXX,Uums", "_traiter_blocs_retablir", $texte);

	$texte = preg_replace(",([[:space:]]?)\n\n+,", "</p><p>", $texte);
	$texte = preg_replace(",<p>([[:space:]]?)<\/p>,", "", $texte);
	$texte = preg_replace(",<p><blockquote([^>]*)>,", "<blockquote$1>", $texte);
	$texte = preg_replace(",</blockquote>[\n\r\t\ ],", "</blockquote>", $texte);
	$texte = str_replace("</blockquote></p>", "</blockquote>", $texte);

	$texte = preg_replace(",([[:space:]]?)(\n|\r),", "<br>", $texte);

	// Remettre les infos des liens
	$texte = preg_replace_callback(",XXXLIEN([0-9]+)LIENXXX,", "_traiter_lien_retablir", $texte);
	$texte = preg_replace_callback(",XXXHASH([0-9]+)HASHXXX,", "_traiter_hash_retablir", $texte);

	$texte = str_replace("<p><br>", "<p>", $texte);
	$texte = str_replace("<p><div", "<div", $texte);
	$texte = str_replace("</div></p>", "</div>", $texte);

	$texte = str_replace("</pics><br><pics>", "</pics><pics>", $texte);
	$texte = str_replace("</pics></p><p><pics>", "</pics><pics>", $texte);
	$texte = str_replace("</pics><pics>", "", $texte);
	$texte = str_replace("<pics>", "<div class='seenthis_pics'>", $texte);
	$texte = str_replace("</pics>", "</div>", $texte);

	$texte = preg_replace_callback(",<div class\='seenthis_pics'>(.*)<\/div>,U", "_traiter_seenthis_pics", $texte);


	if ($lang) $inserer = " lang=\"$lang\" dir=\"$dir\"";

	if ($destinataires = join('',array_unique($destinataires)))
		$destinataires = '<div class="destinataires">'.$destinataires.'</div>';


	return "$destinataires<div$inserer>$texte</div>";
}

function _traiter_seenthis_pics($regs) {
	$texte = $regs[1];

	$total = substr_count($texte, "<a ");
	return "<div class='seenthis_pics seenthis_pics_$total'>$texte</div>";
}

function _texte_inserer_embed($regs) {

	$url = $regs[2];
	$lien = $regs[0];

	# plugin pas encore conforme a la normale
	# attention: autoembed n'est pas un plugin, c'est un serveur indépendant conçu comme tel
	# que je souhaite pouvoir si nécessaire installer sur un autre serveur (c'est que ça bouffe, ces conneries)
	include_spip('autoembed/autoembed');
	if (function_exists('embed_url')
	AND $embed = embed_url($url)) {
		// ajouter au A la class oembed-link + oembed-source-mp3audio
		// pour pouvoir le masquer en css ; mais ca ne suffit pas
		// car le favicon est present dans le flux en amont…
		$class = ' oembed-link';

		if ($c = extraire_attribut($embed, 'class')
		AND preg_match(',\boembed-source-\w+\b,', $c, $r))
			$class .= ' '.$r[0];

		$lienclass = inserer_attribut($lien,
			'class',
			trim(extraire_attribut($lien,'class').$class)
		);
		
		// transformer les embed http en // pour les activer en https
		$embed = preg_replace(',(["\'])http://,', '$1https://', $embed);
		
		return $lienclass.$embed;
	}

	return $lien;
}

function _ajouter_embed($texte) {
	// Tous les liens sont dans un <span .lien_lien><a></span>
	// donc il faut insérer l'autoembed *après* le </span> final
	$texte = preg_replace_callback(",(<a .*href=['\"])([^>]*)(['\"] class=['\"]spip_out['\"].*</a></span>),U", _texte_inserer_embed, $texte);

	return $texte;
}

?>