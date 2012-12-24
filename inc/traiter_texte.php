<?php

function _traiter_hash ($regs) {
	$tag = substr($regs[0],1);

	$url = _url_tag($tag);

	$le_hash = "<span class='lien_tag'>#<a href='$url'>$tag</a></span>";

	$GLOBALS["num_hash"] ++;
	
	$GLOBALS["les_hashs"][$GLOBALS["num_hash"]] = $le_hash;
	
	
	return "XXXHASH".$GLOBALS["num_hash"]."HASHXXX";
	
}

function _traiter_people ($regs) {
	$tag = mb_substr($regs[0], 1, 1000);
	
	$query = sql_query("SELECT id_auteur FROM spip_auteurs WHERE login=".sql_quote($tag)." && statut!='5poubelle'");
	if ($row = sql_fetch($query)) {
		$id_auteur = $row["id_auteur"];
		
		include_spip("urls/arbo");
		$url = declarer_url_arbo("auteur", $id_auteur);
	}
	if ($url) return "<span class='lien_people'>@<a href='$url'>$tag</a></span>";
	else return "@$tag";
//	else return "<span class='lien_people'>@<span class='inexistant'>$tag</span></span>";
	
	
}



function _creer_lien_riche($lien) {

	$lien_or = $lien;

	// Supprimer slash final
	$lien = preg_replace(",/$,", "", $lien);

	// Si ça ressemble à une image, inclure la vignette sur place
	if (preg_match(",\.(png|gif|jpe?g|svg),i", $lien)) {

		// Gérer les images en lien dropbox (remplacer www par dl)
		$lien = preg_replace("/^(https\:\/\/)(www)(\.dropbox\.com\/.*\/.*\/.*)$/", '\1dl\3', $lien);

		$image = afficher_miniature($lien);

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

			return $image;
		}

		//list($width, $height) = @getimagesize($lien);
		//if (($width * $height) >= 300) return;
	}
	
	$query = sql_query("SELECT id_syndic, lang, titre, url_syndic FROM spip_syndic WHERE url_site=\"$lien\"");
	if ($row = sql_fetch($query)) {
		$id_syndic = $row["id_syndic"];
		$lang = $row["lang"];
		$titre = $row["titre"];
		$long = $row["url_syndic"];
		
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
	
	if ($id_syndic) {
		// Ne faire apparaître le lien_court
		// que si plusieurs billets referencent le lien
		$query_total = sql_query("SELECT spip_me.* FROM spip_me, spip_me_syndic 
			WHERE spip_me_syndic.id_syndic=$id_syndic  AND spip_me_syndic.id_me=spip_me.id_me AND spip_me.statut='publi'
			LIMIT 1,1");
		while ($row = sql_fetch($query_total)) {
			$total = "<span class='lien_lien_total'><a href='sites/$id_syndic'>►</a></span>";
		}
	} else {
		$total = "";
	}
	
	
	include_spip("inc/lien_court");
	$intitule = sucrer_utm(lien_court($lien, 45));

	if (function_exists('recuperer_favicon')) {
		$favicon = recuperer_favicon($lien_or);
		// Si modif, penser à modifier aussi la fonction supprimer_background_favicon
		if ($favicon) $style = " style='background-image:url($favicon);'";
	}
	
	$le_lien = "<span class='lien_lien'$style>$total<a href=\"$lien_or\" class='spip_out'$titre$lang>$intitule</a></span>";

	$le_lien = str_replace("&", "&amp;", $le_lien);
	$le_lien = str_replace("&amp;amp;", "&amp;", $le_lien);

	return $le_lien;
}


function _supprimer_background_favicon($texte) {
	//background-image:url(local/cache-favicon/cran-ch-05d83b3285753a7704da06a45e842561.png);
	
	return preg_replace(",style=\'background-image:url\(local/cache-favicon/[^\)]+\);\',","",$texte);
}

function _traiter_lien ($regs) {
	$lien = $regs[0];
	
	// Supprimer parenthese fermante finale si pas de parenthese ouvrante dans l'URL
	$retour_parenthese = "";
	if (preg_match(",\)$,", $lien) && !preg_match(",\(,", $lien)) {
		$lien = preg_replace(",\)$,", "", $lien);
		$retour_parenthese = ")";
	}

	
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

	// Echapper les URL
	// (parce que les URL peuvent contenir des «#» qui deviendraient des tags
	$texte = preg_replace_callback("/"._REG_URL."/ui", "_traiter_lien", $texte);

	// echapper les balises HTML
	$texte = str_replace("<", "&lt;", $texte);
	$texte = str_replace(">", "&gt;", $texte);

	// Remplacer les people
	$texte = preg_replace_callback("/"._REG_PEOPLE."/i", "_traiter_people", $texte);
	
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
	
	// Remettre les infos des liens
	$texte = preg_replace_callback(",XXXBLOC([0-9]+)BLOCXXX,Uums", "_traiter_blocs_retablir", $texte);
	$texte = preg_replace_callback(",XXXLIEN([0-9]+)LIENXXX,", "_traiter_lien_retablir", $texte);
	$texte = preg_replace_callback(",XXXHASH([0-9]+)HASHXXX,", "_traiter_hash_retablir", $texte);

	// Detacher les blocs du reste du texte, afin de bien fermer et ouvrir les paragraphes.
	$texte = str_replace("<blockquote>", "\n\n<blockquote>", $texte);
	$texte = str_replace("</blockquote>", "</blockquote>\n\n", $texte);

	$texte = "<p>$texte</p>";

	$preg = ",(<a rel='shadowbox\[Portfolio\].*<\/a>)\n+(<a rel='shadowbox\[Portfolio\].*<\/a>),";

	while (preg_match("$preg", $texte)) {
		$texte = preg_replace("$preg", "$1$2", $texte);
	}

	
	$texte = preg_replace(",([[:space:]]?)\n\n+,", "</p><p>", $texte);
	$texte = preg_replace(",<p>([[:space:]]?)<\/p>,", "", $texte);
	$texte = preg_replace(",<p><blockquote([^>]*)>,", "<blockquote$1>", $texte);
	$texte = preg_replace(",</blockquote>[\n\r\t\ ],", "</blockquote>", $texte);
	$texte = str_replace("</blockquote></p>", "</blockquote>", $texte);
	
	

	$texte = preg_replace(",([[:space:]]?)(\n|\r),", "<br />", $texte);
	$texte = str_replace("<p><br />", "<p>", $texte);

	if ($lang) $inserer = " lang=\"$lang\" dir=\"$dir\"";

	return "<div$inserer>$texte</div>";
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
		// ajouter la class oembed-link au lien
		// pour pouvoir le masquer en css
		$lienclass = inserer_attribut($lien,
			'class',
			trim(extraire_attribut($lien,'class').' oembed-link')
		);
		return $lienclass.$embed;
	}

	return $lien;
}

function _ajouter_embed($texte) {
	$texte = preg_replace_callback(",(<a .*href=['\"])([^>]*)(['\"] class=['\"]spip_out['\"].*</a>),U", _texte_inserer_embed, $texte);

	return $texte;
}

?>