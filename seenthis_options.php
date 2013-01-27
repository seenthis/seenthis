<?php

//$xhtml = true;
//$xml_indent = true;

define ("_TROLL_VAL", 3000);

// Définir des id_groupes de mots à exclure (par exemples les URL)
// define('_EXCLUDE_GROUPE', '21');

if (!defined("_EXCLUDE_GROUPE")) define("_EXCLUDE_GROUPE", 0);

function nofollow($texte){
   $texte = str_replace("<a href","<a rel='nofollow' href",$texte);
   return $texte;
} 


define (_REG_CHARS, "a-z0-9\pN\pL\pM\'‘’°\&\+–\_");

define (_REG_HASH, "(\#["._REG_CHARS."\@\.\/-]*["._REG_CHARS."])");
define (_REG_URL, "((http|ftp)s?:\/\/["._REG_CHARS."\"#~!«»“”;:\|\.’\?=&%@!\/\,\$\(\)\[\]<>-]+["._REG_CHARS."#«»“”\/\=\(\)\[\]\$\*-])");
//define(_REG_URL, "(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]\{\};:'\".,<>?«»“”‘’]))");
define (_REG_PEOPLE, "\B@[a-zA-Z0-9\.\_\-]+[a-zA-Z0-9\_\-]");

define (_REG_DEBUT_URL, "((http|ftp)s?:\/\/(www\.)?)");
define (_REG_FIN_URL, "(\.(html?|jpg|gif|png|php|css|js)\/?$)");



define('_TRANSLITTERER_URL', false);


/* on vient d'inserer le mot id_mot dans la base, et on veut déterminer :
   1. son id_parent
   2. eventuellement, des mots dont il deviendrait le parent
   
*/
function hierarchiser_mot($id_mot) {
	spip_log("hierarchiser_mot($id_mot)", "cache");
	include_spip('base/abstract_sql');

	$query = sql_select("titre,id_parent", "spip_mots", "id_mot=$id_mot AND id_groupe=1");
	if (!$row = sql_fetch($query))
		return false;

	$titre = $row['titre'];
	$id_parent = $row['id_parent'];

	spip_log("Changement de parent pour $id_mot '$titre' ?");

	// le premier mot inférieur à nous est notre parent
	// (attention on ajoute 'A' devant pour eviter les ennuis avec les tags
	// qui commencent par des chiffres :  "SELECT 30g = 30p"
	$l = mb_strlen($titre, 'UTF8');

	// une seule lettre ? alors il n'a pas de parent, mais il peut s'inserer
	// dans l'arbre
	if ($l > 1)
	while ($l-- > 0) {
		spip_log("essaie parent '".mb_substr($titre,0,$l, 'UTF8')."'");
		if ($a = sql_fetsel("id_mot as id_parent,titre", "spip_mots", "CONCAT('A',titre)=".sql_quote("A".mb_substr($titre,0,$l, 'UTF8'))." AND id_groupe=1")) {
			if ($a['id_parent'] == $id_parent) {
				spip_log("Ne change pas de parent: '$titre'($id_mot) : '$a[titre]' ($a[id_parent])");
				return false;
			} else {
				spip_log("Nouveau parent pour '$titre'($id_mot) : '$a[titre]' ($a[id_parent])");
				sql_update ("spip_mots", 
					array(
						"id_parent" => $a["id_parent"]
					),
					"id_mot = $id_mot"
				);
				$nouveau_parent = $a["id_parent"];
			}
			break;
		}
	}

	// si on lui a trouvé un nouveau parent
	// notre mot s'insère peut-être comme le parent
	// de certains des mots qui avaient ce parent
	foreach (sql_allfetsel("id_mot,titre", "spip_mots",
	"id_parent=".intval($nouveau_parent)
	." AND LENGTH(titre) > ".strlen($titre)
	." AND titre LIKE ".sql_quote(
		str_replace(array('_', '%'), array('\\_', '\\%'), $titre)
		."%" )
	." AND id_mot!=$id_mot"
	." AND id_groupe=1"
	) as $f) {
		hierarchiser_mot($f['id_mot']);
	}

	cache_mot($id_mot);

}

/* utilitaire pour refaire la hierarchie des mots-cles ;
   ne pas appeler en prod
*/
function tout_hier($raz=false) {
	spip_log("Rechercher les parents... $raz");
	include_spip('base/abstract_sql');
	if ($raz)
		sql_query("UPDATE spip_mots SET id_parent=0 WHERE id_groupe=1");
	foreach(sql_allfetsel('id_mot,titre', 'spip_mots', 'id_parent=0 AND id_groupe=1') as $i) {
		hierarchiser_mot($i['id_mot']);
	}
	spip_log("Fini de rechercher les parents.");
}
#tout_hier(false);
#hierarchiser_mot(161876);


function identifier_url($url, $id_parent) {
	spip_log("identifier_url($url, $id_parent)", "cache");

	$query = sql_query("SELECT id_syndic,id_parent FROM spip_syndic WHERE url_site=".sql_quote($url));
	if ($row = sql_fetch($query)) {
		$id_syndic = $row["id_syndic"];
		if ($row["id_parent"] != $id_parent) {
			sql_updateq ("spip_syndic", 
				array(
				"id_rubrique" => 1,
				"id_secteur" => 1,
				"id_parent" => $id_parent,
				"nom_site" => $url,
				"url_site" => $url,
				"md5" => md5($url),
				"statut" => "publie",
				"date" => "NOW()"
				),
				"id_syndic=$id_syndic"
			);
		}
	} else {
		$id_syndic = sql_insertq ("spip_syndic", 
			array(
				"id_rubrique" => 1,
				"id_secteur" => 1,
				"id_parent" => $id_parent,
				"nom_site" => $url,
				"url_site" => $url,
				"md5" => md5($url),
				"statut" => "publie",
				"date" => "NOW()"
		));
		if ($id_syndic > 0) job_queue_add('recuperer_contenu_site', 'récupérer_contenu_site '.$url, array($id_syndic, "$url"));
		//recuperer_contenu_site ($id_syndic, $url);
	}
	
//	echo "<li>$id_syndic &gt; $id_parent / $url</li>";
	return $id_syndic;

}

function hierarchier_url($id_syndic) {
	spip_log("hierarchier_url($id_syndic)", "cache");

	$query = sql_query("SELECT url_site FROM spip_syndic WHERE id_syndic = $id_syndic");
	if ($row = sql_fetch($query)) {
		$url_site = $row["url_site"];
		
		$url = parse_url($url_site);
		
		$scheme = $url["scheme"];
		$host = $url["host"];
		$path = $url["path"];
		$query = $url["query"];
		
		$chemins = explode("/", $path);
		
		$id_parent = identifier_url("$scheme://$host", 0);
		cache_url($id_parent);
		$chemin_complet = "$scheme://$host";
		
		foreach($chemins as $chemin) {
			if (strlen($chemin) > 0) {
				$chemin_complet .= "/$chemin";
				$id_parent = identifier_url($chemin_complet, $id_parent);
			}
		}
		if ($url_site != $chemin_complet) identifier_url("$url_site", $id_parent);
	}
}



// Effacer le microcache d'un message
// - effacer message lui-même
// - effacer le parent (c-a-dire fil de messages)
function cache_me ($id_me, $id_parent = 0) {
	spip_log("cache_me ($id_me, $id_parent)", "cache");
	
	// Si on connait deja l'id_parent, pas besoin de boucle.
	if ($id_parent < 1) {
		$query_me = sql_select("id_me, id_parent", "spip_me", "id_me = $id_me");
		while ($row_me = sql_fetch($query_me)) {
			$id_parent = $row_me["id_parent"];
		}
	}

	supprimer_microcache($id_me, "noisettes/atom_me");
	supprimer_microcache($id_me, "noisettes/afficher_me_xml");
	supprimer_microcache($id_me, "noisettes/atom_me_tw");

	supprimer_microcache($id_me, "noisettes/afficher_message");
	supprimer_microcache($id_me, "noisettes/message_texte");


	// Pas de noisettes pour id_parent 0.
	if ($id_parent > 0) {
		$id_share = $id_parent;
		supprimer_microcache($id_parent, "noisettes/afficher_message");
		supprimer_microcache($id_parent, "noisettes/oc_message");

		cache_mot_fil ($id_parent);
		cache_auteur_fil($id_parent);

	} else {
		$id_share = $id_me;
		cache_mot_fil ($id_me);
		cache_auteur_fil($id_me);
		supprimer_microcache($id_me, "noisettes/head_message");
		supprimer_microcache($id_me, "noisettes/oc_message");
	}
	

	$query_share = sql_select("id_auteur", "spip_me_share", "id_me = $id_share");
	while ($row_share = sql_fetch($query_share)) {
		$id_auteur = $row_share["id_auteur"];
		cache_auteur($id_auteur);
	}
}

function cache_mot_fil($id_me) {
	spip_log("cache_mot_fil($id_me)", "cache");
	// Supprimer le cache des mots liés
	$query = sql_select("id_mot", "spip_me_mot", "id_me=$id_me");
	while ($row = sql_fetch($query)) {
		$id_mot = $row["id_mot"];
		cache_mot($id_mot);
	}
	// Supprimer le cache des sites liés
	$query = sql_select("id_syndic", "spip_me_syndic", "id_me=$id_me");
	while ($row = sql_fetch($query)) {
		$id_syndic = $row["id_syndic"];
		cache_url_fil($id_syndic);
	}
	// Traiter les enfants
	$query_enfants = sql_select("id_me", "spip_me", "id_parent=$id_me");
	while ($row_enfants = sql_fetch($query_enfants)) {
		$id_me = $row_enfants["id_me"];
		cache_mot_fil($id_me);
	}
}

function cache_url_fil($id_syndic) {
	spip_log("cache_url_fil($id_syndic)", "cache");
	// Supprimer le cache des sites liés
	cache_url($id_syndic);

	// Traiter les enfants
	$query_enfants = sql_select("id_syndic", "spip_syndic", "id_parent=$id_syndic");
	while ($row_enfants = sql_fetch($query_enfants)) {
		$id_syndic = $row_enfants["id_syndic"];
		cache_url_fil($id_syndic);
	}
	
}


function cache_auteur_fil($id_me) {
	spip_log("cache_auteur_fil($id_me)", "cache");
	$query = sql_select("id_auteur, id_parent", "spip_me", "id_me=$id_me");
	while ($row = sql_fetch($query)) {
		$id_auteur = $row["id_auteur"];
		cache_auteur($id_auteur);
		
		if ($id_parent > 0) cache_auteur_fil($id_parent);
	}

}

function cache_auteur($id_auteur) {
	spip_log("cache_auteur($id_auteur)", "cache");
	supprimer_microcache($id_auteur, "noisettes/contenu_auteur");
	supprimer_microcache($id_auteur, "noisettes/contenu_page_tags");
	supprimer_microcache($id_auteur, "noisettes/contenu_page_sites");
	supprimer_microcache($id_auteur, "noisettes/atom_messages_auteur");
	supprimer_microcache($id_auteur, "noisettes/atom_backend_auteur");
	supprimer_microcache($id_auteur, "noisettes/atom_backend_auteur_tw");

	# invalider les caches normaux de SPIP
	include_spip('inc/invalideur');
	suivre_invalideur('*', true);
}

function cache_mot ($id_mot) {
	spip_log("cache_mot ($id_mot)", "cache");

	supprimer_microcache($id_mot, "noisettes/contenu_mot");
	supprimer_microcache($id_mot, "noisettes/contenu_mot_fin");
	supprimer_microcache($id_mot, "noisettes/contenu_mot_flou");

	$query = sql_select("id_follow,id_mot", "spip_me_follow_mot", "id_mot=$id_mot");
	while ($row = sql_fetch($query)) {
		$id_auteur = $row["id_follow"];
		supprimer_microcache($id_auteur, "noisettes/contenu_page_tags");
	}
	
	$query = sql_select("id_parent", "spip_mots", "id_mot=$id_mot");
	while ($row = sql_fetch($query)) {
		$id_parent = $row["id_parent"];
		if ($id_parent > 0 AND ($id_parent!=$id_mot)) cache_mot($id_parent);
	}

}


function cache_url ($id_syndic) {
	spip_log("cache_url ($id_syndic)", "cache");
	supprimer_microcache($id_syndic, "noisettes/contenu_site");
	supprimer_microcache($id_syndic, "noisettes/afficher_enfants_site");

	$query = sql_select("id_follow", "spip_me_follow_url", "id_syndic=$id_syndic");
	while ($row = sql_fetch($query)) {
		$id_auteur = $row["id_follow"];
		supprimer_microcache($id_auteur, "noisettes/contenu_page_sites");
	}
}



function nettoyer_graphisme_auteur($id_auteur) {
			supprimer_microcache($id_auteur, "noisettes/head_auteur");
			supprimer_microcache($id_auteur, "noisettes/css_auteur");
			supprimer_microcache($id_auteur, "noisettes/head_auteur_message");
			supprimer_microcache($id_auteur, "noisettes/head_message");
			supprimer_microcache($id_auteur, "noisettes/entete_auteur");
			supprimer_microcache($id_auteur, "noisettes/entete_auteur_message");
}

function nettoyer_nom_auteur($id_auteur) {
	$query = sql_select("id_me", "spip_me", "id_auteur=$id_auteur");
	while ($row = sql_fetch($query)) {
		$id_me = $row["id_me"];
		cache_me($id_me);
	}
}

function nettoyer_logo_auteur($id_auteur) {
	cache_auteur($id_auteur);

	supprimer_microcache($id_auteur, "noisettes/message_logo_auteur");
	supprimer_microcache($id_auteur, "noisettes/message_logo_auteur_small");
	supprimer_microcache($id_auteur, "noisettes/message_logo_auteur_small_nofollow");
	
	$query = sql_select("id_me", "spip_me", "id_auteur=$id_auteur");
	while ($row = sql_fetch($query)) {
		$id_me = $row["id_me"];
		cache_me($id_me);
	}
}



function supprimer_me($id_me) {
	// Les messages supprimés changent seulement de statut.
	// Garder le lien vers son auteur, et changer son statut en "supp"
	
	
	cache_me($id_me);
	
	$query = sql_select("id_auteur, id_parent", "spip_me", "id_me = $id_me");
	while ($row = sql_fetch($query)) {
		$id_parent = $row["id_parent"];
		$id_auteur = $row["id_auteur"];
		cache_auteur($id_auteur);
		
		if ($id_parent > 0) $id_ref = $id_parent;
		else $id_ref = $id_me;
	}
	
	//	sql_query("DELETE FROM spip_me_auteur WHERE id_me=$id_me");
	sql_query("DELETE FROM spip_me_mot WHERE id_me=$id_me");
	sql_query("DELETE FROM spip_me_syndic WHERE id_me=$id_me");
	sql_query("DELETE FROM spip_me_tags WHERE id_me=$id_me");
	sql_query("UPDATE spip_me SET statut='supp' WHERE id_me=$id_me");

	if ($id_parent == 0) sql_delete("spip_me_recherche", "id_me=$id_ref");

	
	$query = sql_select("id_me, id_auteur", "spip_me", "id_parent = $id_me");
	while ($row = sql_fetch($query)) {
		$id_enfant = $row["id_me"];
		supprimer_me($id_enfant);
		$id_auteur = $row["id_auteur"];
		cache_auteur($id_auteur);
	}
	
	
}

function allonger_url($url) {

	if (!preg_match("/(seen\.li|youtu\.be|t\.co|reut\.rs|nyti\.ms|fb\.me|bit\.ly|goo\.gl|2tu\.us|icio\.us|tinyurl\.com|tr\.im|ur1\.ca|a\.pwal\.fr|j\.mp|is\.gd|a\.gd|ow\.ly|spedr\.com|shar\.es|twurl\.nl|shr\.im|u\.nu|ff\.im|bt\.io|minu\.me|zi\.pe)/", $url)) return $url;
	
	$l = false;
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$a = curl_exec($ch);

	if(preg_match('#Location: (.*)#', $a, $r)) {
		$l = trim($r[1]);
		if (!preg_match(",^(http|ftp)s?\:,", $l)) $l = curl_getinfo($ch , CURLINFO_EFFECTIVE_URL);
	}	
	return $l;
}


function recuperer_contenu_site ($id_syndic, $url) {
	include_spip("inc/distant");

	// D'abord tester le nom du (futur) fichier local
	// ce qui permet de ne travailler que sur le HTML
	$local = fichier_copie_locale($url);

	$recup = 0;
	
	if (!preg_match(",html$,", $local)) {
		$recup = 2;
	} else {
	
		$contenu = _DIR_RACINE.copie_locale($url);
		if ($contenu) {
			$html = join(file($contenu), "");
			include_spip("inc/texte");


			// Virer les scripts en amont			
			$html = preg_replace(",<script.*</script>,Uims", "", $html);
			$html = interdire_scripts($html, true);
			
			include_spip("php/Readability");

			// give it to Readability
			$readability = new Readability($html, $url);
			
			// print debug output?
			// useful to compare against Arc90's original JS version -
			// simply click the bookmarklet with FireBug's
			// console window open
			$readability->debug = false;
			
			// convert links to footnotes?
			$readability->convertLinksToFootnotes = false;
			
			// process it
			$result = $readability->init();
			
			// does it look like we found what we wanted?
			if ($result) {
				$titre = $readability->getTitle()->textContent;
				$content = $readability->getContent()->innerHTML;

				$content = preg_replace(",^.*<body.*>,Uims", "", $content);
				$content = str_replace("</body></html></div>", "", $content);
				$content = preg_replace(", readability=\"[0-9]*\.[0-9]*\",", "", $content);
								
				include_spip("php/detecter_langue_fonctions");
				
				//$titre = interdire_scripts($titre, true);
				//$content = interdire_scripts($content, true);
				
				$lang = detecter_langue($content);
				if ($lang) $dir = lang_dir($lang);

				$recup = 1;

				
//				return "<h3>Langue: $lang - $dir</h3>$content";
			} else {
				$recup = 2;
			}			
			
			
			
		} else {
			$recup = 2;
		}
		
	}

	if ($recup == 1) {

		if ($l = allonger_url($url));
		if (!$l) $l = "";

		sql_updateq("spip_syndic", 
			array(
				"titre" => $titre,
				"texte" => $content,
				"recup" => $recup,
				"lang" => $lang,
				"url_syndic" => $l
			), 
			"id_syndic = $id_syndic"
		);

		$query = sql_select("id_me", "spip_me_syndic", "id_syndic=$id_syndic");
		while ($row = sql_fetch($query)) {
		
			$id_me = $row ["id_me"];
			cache_me($id_me);
			supprimer_microcache($id_me, "noisettes/message_texte");
		}
		job_queue_add('OC_site', 'thématiser site '.$url, array($id_syndic));
		
		return "<h3>Langue: $lang - $dir</h3>$content";	
	} else {
		sql_updateq("spip_syndic", 
			array(
				"recup" => 2
			), 
			"id_syndic = $id_syndic"
		);
		return false;
	} 
	
}


/* cf sucrer_utm dans seenthis.js */
function sucrer_utm ($url) {
	if (is_array($url)) $url = $url[0];
	
	$url = preg_replace(",https?://twitter.com/#!/,", "http://twitter.com/", $url);
	
	$url = preg_replace(",([\?\&]|\&amp;)utm\_.*,", "", $url);
	
	return $url;
	
}


function texte_de_me($id_me) {
	// Aller chercher le texte d'un id_me
	// dans la table spip_me_texte
	// 
	// Stocker le résultat, parce qu'utilisé plusieurs fois dans le même script

	if ($GLOBALS["texte_de_id_me"]["$id_me"]) return $GLOBALS["texte_de_id_me"]["$id_me"];

	$query = sql_select("texte", "spip_me_texte", "id_me=$id_me");
	if ($row = sql_fetch($query)) {
		$GLOBALS["texte_de_id_me"]["$id_me"] = $row["texte"];
		return $GLOBALS["texte_de_id_me"]["$id_me"];
	}
}

function OC_message($id_me) {
	include_spip("php/opencalais");
	
	$query = sql_select("id_me", "spip_me", "(id_me=$id_me OR id_parent=$id_me) AND statut='publi'", "id_me");
	while ($row = sql_fetch($query)) {
		$texte .= " ".texte_de_me($row["id_me"]);
		
	}

	$texte = preg_replace("/"._REG_URL."/i", " ", $texte);
	
	traiterOpenCalais($texte, $id_me, "id_me", "spip_me_mot");
	cache_me($id_me);
	//inserer_themes($id_me);
}

function OC_site($id_syndic) {
	include_spip("php/opencalais");
	
	$query = sql_select("texte", "spip_syndic", "id_syndic=$id_syndic");
	while ($row = sql_fetch($query)) {
		$texte .= " ".$row["texte"];
	}
	if (strlen($texte) > 10) {
		traiterOpenCalais($texte, $id_syndic, "id_syndic", "spip_syndic_oc");
	}
	supprimer_microcache($id_syndic, "noisettes/oc_site");
	
	$query = sql_select("id_mot", "spip_syndic_oc", "id_syndic=$id_syndic");
	while ($row = sql_fetch($query)) {
		$id_mot = $row["id_mot"];
		cache_mot($id_mot);
	}

	$query_syndic = sql_select("id_me", "spip_me_syndic", "id_syndic=$id_syndic");
	while ($row_syndic = sql_fetch($query_syndic)) {
		$id_me = $row_syndic["id_me"];
		//inserer_themes($id_me);
		supprimer_microcache($id_me, "noisettes/oc_message");
	}

}

/*
function inserer_themes($id_me) {
	return; # cette fonction ne fait rien -- a part prendre du temps !

	$mots = false;
	$update = "";

	$query = sql_select("id_mot", "spip_me_mot", "id_me=$id_me");
	while ($row = sql_fetch($query)) {
		$mots[] = $row["id_mot"];
	}
	
	
	$query_syndic = sql_select("id_syndic", "spip_me_syndic", "id_me=$id_me");
	while ($row_syndic = sql_fetch($query_syndic)) {
		$id_syndic = $row_syndic["id_syndic"];
		
		$query = sql_select ("id_mot", "spip_syndic_oc", "id_syndic=$id_syndic");
		while ($row = sql_fetch($query)) {
			$mots[] = $row["id_mot"];
		}
	}
	
	
	if (!$mots) {
		$update = "";
	} else {
		
		$mots = join(",", $mots);
		
		$query = sql_select("titre", "spip_mots", "id_mot IN ($mots)");
		while ($row = sql_fetch($query)) {
			$update .= " #".$row["titre"];
		}
	}

	/ *
	sql_updateq ("spip_me", 
		array(
			"themes" => $update
		),
		"id_me = '$id_me'"
	);
	* /
}
*/


function racine_bandeau($id_auteur) {
	$dossier_bandeau = sous_repertoire(_NOM_PERMANENTS_ACCESSIBLES, "bandeau");
	$racine = sous_repertoire($dossier_bandeau, dechex($id_auteur%10))."bandeau$id_auteur";;
	return $racine;
}

function fichier_bandeau($id_auteur, $avec_date = true) {
	// IMG/bandeaux/bandeau#ID_AUTEUR.png
	
	$racine = racine_bandeau($id_auteur);
		
	$fichier = false;
	if (file_exists("$racine.jpg")) $fichier = "$racine.jpg";
	else if (file_exists("$racine.png")) $fichier = "$racine.png";
	else if (file_exists("$racine.gif")) $fichier = "$racine.gif";
	if ($fichier && $avec_date) $fichier = $fichier."?".filemtime($fichier);
	return $fichier;	
}

function racine_fond($id_auteur) {
	$dossier_fond = sous_repertoire(_NOM_PERMANENTS_ACCESSIBLES, "fond");
	$racine = sous_repertoire($dossier_fond, dechex($id_auteur%10))."fond$id_auteur";;
	return $racine;
}

function fichier_fond($id_auteur, $avec_date = true) {
	
	$racine = racine_fond($id_auteur);
	$fichier = false;
	if (file_exists("$racine.jpg")) $fichier = "$racine.jpg";
	else if (file_exists("$racine.png")) $fichier = "$racine.png";
	else if (file_exists("$racine.gif")) $fichier = "$racine.gif";
	if ($fichier && $avec_date) $fichier = $fichier."?".filemtime($fichier);
	return $fichier;	
}

function fichier_logo_auteur($id_auteur, $avec_date = true) {
	
	$racine = _NOM_PERMANENTS_ACCESSIBLES."auton$id_auteur";
	$fichier = false;
	if (file_exists("$racine.jpg")) $fichier = "$racine.jpg";
	else if (file_exists("$racine.png")) $fichier = "$racine.png";
	else if (file_exists("$racine.gif")) $fichier = "$racine.gif";
	if ($fichier && $avec_date) $fichier = $fichier."?".filemtime($fichier);
	return $fichier;	
}





function calculer_troll($id_auteur, $reseau = false) {
	$troll_forcer = 0;
	$query = sql_select("troll_forcer", "spip_auteurs", "id_auteur=$id_auteur");
	if ($row = sql_fetch($query)) {
		$troll_forcer = $row["troll_forcer"];
	}
	if ($troll_forcer >0) {
		$total = $troll_forcer;
	} else {
		$query = sql_select("auteur.id_auteur, auteur.troll, auteur.troll_forcer", "spip_auteurs as auteur LEFT JOIN spip_me_follow as follow ON follow.id_follow=auteur.id_auteur", "follow.id_auteur=$id_auteur");
		while ($row = sql_fetch($query)) {
			$id_follow = $row["id_auteur"];
			$troll = $row["troll_forcer"];
			if ($troll == 0) {
				$troll = $row["troll"];
				if ($reseau) $troll = calculer_troll($id_follow, false);
			}
			
			$troll = max(70, ($troll - _TROLL_VAL)/20 );
			
			$total = $total + ($troll);
			
		}
		
		//$total = max(0, $total);
		//$total = min($total, 6000);

		$query = sql_select("*", "spip_me_block", "id_auteur=$id_auteur");
		while ($row = sql_fetch($query)) {
			$id_blockeur = $row["id_auteur"];
			$troll = afficher_troll($id_blockeur);
			
			$troll = max(70, ($troll - _TROLL_VAL)/20 );
			
			$total = $total - $troll;
			
		}
		

		
		$total = round($total);
		
		$total = max(0, $total);
		$total = min($total, 6000);
	}
	sql_update ("spip_auteurs", 
		array(
			"troll" => $total
		),
		"id_auteur = '$id_auteur'"
	);

	// if ($reseau) echo "<li><b>$id_auteur - $total</b></li>";
	// else echo "<li>$id_auteur - $total</li>";
	
	return $total;
}


function afficher_troll($id_auteur) {
	static $val_troll = array();

	if (isset($val_troll["$id_auteur"])) return $val_troll["$id_auteur"];
	else {
		$query = sql_select("troll, troll_forcer", "spip_auteurs", "id_auteur=$id_auteur");
		if ($row = sql_fetch($query)) {
			$troll = $row["troll_forcer"];
			if ($troll == 0) $troll = $row["troll"];
			
			$val_troll["$id_auteur"] = $troll;
			
			return $troll;
		}
	}
	return false;
}

function rel_troll($id_auteur) {
	$troll = afficher_troll($id_auteur);
	if ($troll < _TROLL_VAL) return "nofollow";
}

function liens_troll($texte, $id_auteur) {
	if (rel_troll($id_auteur) == "nofollow") {
		$texte = nofollow($texte);
	}
	return $texte;
}

function traiter_texte($texte) {
	include_spip("inc/traiter_texte");
	return _traiter_texte($texte);
}


function ajouter_embed($texte) {
	include_spip("inc/traiter_texte");
	return _ajouter_embed($texte);
}




function message_texte($texte) {
	$texte = liens_absolus($texte);
	
	
	$texte = str_replace("<br />", "\n", $texte);
	$texte = str_replace("►", "", $texte);
	
	
	$texte = preg_replace(",<\/?p[^>]*>,", "\n\n", $texte);
	$texte = preg_replace(",<blockquote[^>]*>( |\n)*,", "\n\n❝", $texte);
	$texte = preg_replace(",( |\n)*<\/blockquote[^>]*>,", "❞\n\n", $texte);
	
	
	$texte = preg_replace(",\n\n+,", "\n\n", $texte);
	
	//$texte = strip_tags($texte);
		
	return trim($texte);
}


function extraire_titre($texte) {
	$texte = preg_replace(",([\t\r\n\ ]+),", " ", $texte);
	$texte = preg_replace(",\ +,", " ", $texte);
	$texte = preg_replace("/(#|@)/", "", $texte);

	if (preg_match("/"._REG_URL."/i", $texte, $regs, PREG_OFFSET_CAPTURE)) {
		$premier_lien = $regs[0][1];
		if ($premier_lien > 10) $texte = substr($texte, 0, $premier_lien);
		else {
				$texte_alt = preg_replace("/"._REG_URL."/i", " ", $texte);
				$texte_alt = preg_replace(",\ +,", " ", $texte_alt);
				 $texte = $texte_alt;
		}
	} 


	if (mb_strlen($texte, "utf-8") > 100) {
		$texte = mb_substr($texte, 0, 100, "utf-8");
		$pos = mb_strrpos($texte, " ", "utf-8");
		
		if ($pos > 5) {
			$texte = mb_substr($texte, 0, $pos, "utf-8")."…";
		}
	}
	
	
	
	include_spip("inc/filtres");
	include_spip("inc/texte");
	return textebrut(typo(couper($texte,140)));
}


function tester_mail_auteur($id_auteur, $val) {
	$ret = false;
	
	if (!(isset($GLOBALS["envoi_mail"]["$id_auteur"]))) {
		$query = sql_select("mail_nouv_billet, mail_rep_moi, mail_rep_billet, mail_rep_conv, mail_suivre_moi", "spip_auteurs", "id_auteur=$id_auteur");
		if ($row = sql_fetch($query)) {
			$GLOBALS["envoi_mail"]["$id_auteur"] = $row;
		}
	}
	
	$reponse = $GLOBALS["envoi_mail"]["$id_auteur"]["$val"];
	
	if ($reponse == 1)
		$ret = true;
	else
		$ret = false;

	return $ret;
}


$GLOBALS["nom_auteur"] = array();
function nom_auteur($id_auteur) {
	if ($GLOBALS["nom_auteur"]["$id_auteur"]) return $GLOBALS["nom_auteur"]["$id_auteur"];
	
	$query_auteur = sql_select("nom,login", "spip_auteurs", "id_auteur=$id_auteur");
	if ($row_auteur = sql_fetch($query_auteur)) {
		$nom_auteur = trim($row_auteur["nom"]);
		$login_auteur = trim($row_auteur["login"]);
	}

	$GLOBALS["nom_auteur"]["$id_auteur"] = "$nom_auteur (@$login_auteur)";
	return $GLOBALS["nom_auteur"]["$id_auteur"];
}


function construire_texte($id_parent, $id_ref) {
//		$texte = message_texte(traiter_texte($texte));
		
	$query = sql_select("id_me, id_auteur", "spip_me", "id_me=$id_parent && statut='publi'");
	if ($row = sql_fetch($query)) {
		$nom_auteur = nom_auteur($row["id_auteur"]);
		$id_me = $row["id_me"];
		$texte = texte_de_me($id_me);
		if ($row["id_me"] == $id_ref) $ret = message_texte(($texte))."\n";
		else $ret = "\n> ---------\n> $nom_auteur ".trim(extraire_titre($texte));
		
	}
	
	$query = sql_select("id_me, id_auteur", "spip_me", "id_parent=$id_parent && statut='publi'");
	while ($row = sql_fetch($query)) {
		$nom_auteur = nom_auteur($row["id_auteur"]);
		$id_me = $row["id_me"];
		$texte = texte_de_me($id_me);
		if ($row["id_me"] == $id_ref) $ret = message_texte(($texte))."\n\n".$ret;
		else $ret .= "\n> ---------\n> $nom_auteur ".trim(extraire_titre($texte));
		
	}
	
	return $ret;
	
}

function notifier_suivre_moi ($id_auteur, $id_follow) {
	//mail_suivre_moi
	// $id_auteur => celui qui est suivi => celui à prévenir
	// $id_follow => celui qui suit
	
	if (tester_mail_auteur($id_auteur, "mail_suivre_moi")) {

		$from = "Seenthis <no-reply@"._HOST.">";
		//$headers .= 'Content-Type: text/plain; charset="utf-8"'."\n"; 
		//$headers .= "Content-Transfer-Encoding: 8bit\n"; 
		$headers = "Message-Id:<$id_auteur.$id_follow@"._HOST.">\n"; 

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
					$titre_mail = "$nom_aut is following you on Seenthis.";
					$annonce = "Hi $nom_dest,\n\n$nom_aut (@$login_aut) is following you on Seenthis.";
				} else {
					$titre_mail = "$nom_aut vous suit sur Seenthis.";
					$annonce = "Bonjour $nom_dest,\n\n$nom_aut (@$login_aut) vous suit sur Seenthis.";
				}
				
				$lien = "\n\n---------\nPour ne plus recevoir d'alertes de Seenthis,\n vous pouvez régler vos préférences dans votre profil\nhttp://"._HOST."\n\n";
				
				$envoyer = "\n\n$annonce\n$url_me\n\n$lien";
				//echo "<hr /><pre>$envoyer</pre>";


				//$titre_mail = mb_encode_mimeheader(html_entity_decode($titre_mail, null, 'UTF-8'), 'UTF-8');
				$envoyer_mail = charger_fonction('envoyer_mail','inc');
				$envoyer_mail("$email_dest", "Seenthis - $titre_mail", "$envoyer", $from, $headers);
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

		if ($id_parent == 0) {		
			$url_me = "http://"._HOST."/messages/$id_me";
		} else {
			$url_me = "http://"._HOST."/messages/$id_parent#message$id_me";
		}
		
		
		$nom_auteur = nom_auteur($id_auteur_me);
		
		
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
		
		if ($id_parent > 0)
			$texte = construire_texte($id_parent, $id_me);
		else
			$texte = construire_texte($id_me, $id_me);
		

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
			foreach ($people[0] as $k=>$p) {
				$people[$k] = mb_substr($p,1); // liste des logins cites
			}
			$s = sql_query($q = 'SELECT m.id_auteur FROM spip_auteurs AS m LEFT JOIN spip_me_block AS b ON b.id_block=m.id_auteur AND b.id_auteur='.sql_quote($id_auteur_me).' WHERE '.sql_in('m.login', array_unique($people)).' AND b.id_block IS NULL');
			while($t = sql_fetch($s)) {
				$id_dest[] = $t['id_auteur'];
			}
		}


		if (isset($id_dest)) { 
			//$from = mb_encode_mimeheader($nom_auteur)." - Seenthis <no-reply@"._HOST.">\n";
			$from = $nom_auteur." - Seenthis <no-reply@"._HOST.">\n";
			//$headers .= 'Content-Type: text/plain; charset="utf-8"'."\n"; 
			//$headers .= "Content-Transfer-Encoding: 8bit\n"; 
			$headers = "Message-Id:<$id_me@"._HOST.">\n"; 
			if ($id_parent > 0) $headers .= "In-Reply-To:<$id_parent@"._HOST.">\n"; 

			//$texte = html_entity_decode($texte, null, 'UTF-8');	
			//$titre_mail = mb_encode_mimeheader(html_entity_decode($titre_mail, null, 'UTF-8'), 'UTF-8');

		
			$id_dest = join(",", $id_dest);

			$query_dest = sql_select("*", "spip_auteurs", "id_auteur IN ($id_dest)
			AND id_auteur != $id_auteur_me
			");
			while ($row_dest = sql_fetch($query_dest)) {
				$nom_dest = $row_dest["nom"];

				$email_dest = $row_dest["email"];
				$lang = $row_dest["lang"];
				spip_log("notifier $id_me($id_parent) a $email_dest", 'notifier');

				if (strlen(trim($email_dest)) > 3) {
					
					if ($lang == "en") {				
						if ($id_parent == 0) {
							$annonce = "$nom_auteur has posted a new message";
						} else {
							if ($nom_auteur == $nom_auteur_init ) $annonce = "$nom_auteur has answered his/her own message";
							else $annonce = "$nom_auteur has answered to $nom_auteur_init";
						}
					} else {
						if ($id_parent == 0) {
							$annonce = "$nom_auteur a posté un nouveau billet";
						} else {
							if ($nom_auteur == $nom_auteur_init ) $annonce = "$nom_auteur a répondu à un de ses billets";
							else $annonce = "$nom_auteur a répondu à un billet de $nom_auteur_init";
						}
					}
					
//					$titre_mail = mb_encode_mimeheader($annonce);
					
					$lien = "\n\n---------\nPour ne plus recevoir d'alertes de Seenthis,\n vous pouvez régler vos préférences dans votre profil\nhttp://"._HOST."\n\n";
					
					$envoyer = "$annonce\n$url_me\n\n$texte\n\n\n\n$lien";
					//echo "<hr /><pre>$envoyer</pre>";
					$envoyer_mail = charger_fonction('envoyer_mail','inc');
					$envoyer_mail("$email_dest", "$titre_mail", "$envoyer", $from, $headers);

				}
			}
			
		}
	
	}
}	

function indexer_me($id_ref) {
	$query = sql_select("*", "spip_me", "(id_me=$id_ref OR id_parent=$id_ref) AND statut='publi'");
	
	$id_billets = false;
	
	while($row = sql_fetch($query)) {
		$id_me = $row["id_me"];
		$id_parent = $row["id_parent"];
		//$ret .= " [$id_me]";
		
		$id_billets[] = $id_me;
		
		$texte = texte_de_me($id_me);
		$texte = "\n\n ".preg_replace(",[\_\*\-❝❞#],u", " ", $texte)." ";
		
		$ret .= $texte;
		if($id_parent == 0) {
			$id_auteur_ref = $row["id_auteur"];
			$id_me_ref = $id_me;
			$date_ref = $row["date"];
			$ret .= $texte;
		}
		
	}
	
	
	if ($id_billets) {
		$id_billets = join($id_billets, ",");
		//$ret .= "<h4>$id_billets</h4>";
		
		$query = sql_select("spip_mots.titre", "spip_mots, spip_me_mot", "spip_me_mot.id_me IN ($id_billets) AND spip_mots.id_mot=spip_me_mot.id_mot AND spip_me_mot.off='non'");
		while ($row = sql_fetch($query)){
			$titre = $row["titre"];
			
			$ret .= "\n\n #$titre $titre";
		}
		
	}

	sql_delete("spip_me_recherche", "id_me=$id_ref");
	sql_insertq("spip_me_recherche",
		array(
			"id_me" => $id_me_ref,
			"date" => $date_ref,
			"id_auteur" => $id_auteur_ref,
			"texte" => $ret
		)
	);
		
	
	
	return $id_ref;
}


function supprimer_background_favicon($texte) {
	include_spip("inc/traiter_texte");
	return _supprimer_background_favicon($texte);
}	

// insertion ou modification en base d'un message
function instance_me ($id_auteur = 0, $texte_message="",  $id_me=0, $id_parent=0, $id_dest=0, $ze_mot=0, $time="NOW()", $uuid=null){
	include_spip('base/abstract_sql');

	if ($id_auteur < 1) return false;
	if ($id_me > 0) cache_me($id_me);
	
	$troll = afficher_troll($id_auteur);
	

	// Virer les UTM en dur dans la sauvegarde
	$texte_message = preg_replace_callback("/"._REG_URL."/i", "sucrer_utm", $texte_message);
	
	# Voir config Varnish/apache 
	# http://seenthis.net/messages/37781
	$adresse_ip = $_SERVER["REMOTE_ADDR"];

	// Valider ou creer un UUID aleatoire
	include_spip('inc/uuid');
	if (is_null($uuid)) {
		$uuid = UUID::getuuid();
	} else {
		$uuid = UUID::getuuid($uuid);
		if ($id_me = sql_getfetsel('id_me', 'spip_me', 'uuid='.sql_quote($uuid))) {
			spip_log("uuid: $uuid, found id_me=$id_me", 'debug');
		}
	}

	// creation ?
	if ($id_me == 0) {
	
		// message en reponse
		if ($id_parent > 0) {
			$query_parent = sql_select("date", "spip_me", "id_me=$id_parent");
			if ($row_parent= sql_fetch($query_parent)) {
				$date_parent = $row_parent["date"];
			}
		} else {
			$date_parent = $time;
		}

		// Insertion en base
		$id_me = sql_insertq("spip_me",
			array(
				"date" => "$time",
				'uuid' => $uuid,
				"date_parent" => "$date_parent",
				"date_modif" => "NOW()",
				"id_auteur" => $id_auteur,
				"id_parent" => $id_parent,
				"id_dest" => $id_dest,
				"id_mot" => $ze_mot,
				"ip" => $adresse_ip,
				"statut" => "publi",
				"troll" => $troll
			)
		);

		sql_insertq("spip_me_texte",
			array(
				"id_me" => $id_me,
				"texte" => $texte_message
			)
		);
		
	} else {
		// Mise à jour
		
		$maj = 1;

		
		$query = sql_select("*", "spip_me", "id_me=$id_me");
		if ($row = sql_fetch($query)) {
			$id_parent = $row["id_parent"];
			$id_dest = $row["id_dest"];
			$ze_mot = $row["id_mot"];
			$ip = $row["ip"];
			$id_auteur_old = $row["id_auteur"];
			$date_parent_old = $row["date_parent"];
		}
		
		if ($id_auteur_old != $id_auteur) die ("Forbidden");

		cache_me($id_me, $id_parent);

		$query = sql_query("DELETE FROM spip_me_mot WHERE id_me=$id_me AND id_mot!=$ze_mot");
		$query = sql_query("DELETE FROM spip_me_syndic WHERE id_me=$id_me");
		$query = sql_query("DELETE FROM spip_me_tags WHERE id_me=$id_me");
		$query = sql_query("DELETE FROM spip_me_auteur WHERE id_me=$id_me AND id_auteur!=$id_dest");

		if ($id_parent > 0) {
			$query_parent = sql_select("date", "spip_me", "id_me=$id_parent");
			if ($row_parent= sql_fetch($query_parent)) {
				$date_parent = $row_parent["date"];
			}
		} else {
			$date_parent = "$date_parent_old";
		}
		

		sql_updateq("spip_me", 
			array(
				"id_auteur" => $id_auteur,
				"id_parent" => $id_parent,
				"date_parent" => "$date_parent",
				"date_modif" => "NOW()",
				"id_dest" => $id_dest,
				"id_mot" => $ze_mot,
				"ip" => $adresse_ip,
				"statut" => "publi"
			),
			"id_me=$id_me"
		);
		sql_updateq("spip_me_texte", 
			array(
				"texte" => $texte_message
			),
			"id_me=$id_me"
		);
		supprimer_microcache($id_me, "noisettes/message_texte");

	}

	cache_auteur($id_auteur);
	cache_me($id_me, $id_parent);

	if ($id_parent > 0) {

		job_queue_add('OC_message', 'thématiser message '.$id_parent, array($id_parent));
		// Indexer le contenu, dans une demi-heure

		job_queue_add(
			'indexer_me', 
			'indexer message '.$id_parent, 
			array($id_parent),
			"",
			true,
			time() + (60 * 30) 
		);
		
		
	} else {
		job_queue_add('OC_message', 'thématiser message '.$id_me, array($id_me));
		// Indexer le contenu, dans cinq minutes
		job_queue_add(
			'indexer_me', 
			'indexer message '.$id_me, 
			array($id_me),
			"",
			true,
			time() + (60 * 5) 
		);
	}
	
	// $deja_vu pour eviter les doublons
	
	$deja_vu = Array();
	
	// Ajouter auteur automatique (id_dest)
	if ($id_dest > 0 && $id_dest != $id_auteur) {
			if (!$deja_vu["people"][$nom]) {
			
				$query = sql_query("SELECT id_auteur FROM spip_auteurs WHERE id_auteur = '$id_dest'");
				if ($row = sql_fetch($query)) {
					$dest = $row["id_auteur"];
					
					sql_insertq("spip_me_auteur", array(
						"id_me" => $id_me,
						"id_auteur" => $dest
					));
					
				}
	
				$deja_vu["people"][$nom] = true;
	
			}		
	}
	
	
	
	// Extraire les people et fabriquer les liens
	preg_match_all("/"._REG_PEOPLE."/", $texte_message, $regs);
	if ($regs) {	
		include_spip("base/abstract_sql");
	
		foreach ($regs[0] as $k=>$people) {
			$nom = substr($people, 1, 1000);

			if (!$deja_vu["people"][$nom]) {
			
				$query = sql_query("SELECT id_auteur FROM spip_auteurs WHERE login = '$nom'");
				if ($row = sql_fetch($query)) {
					$dest = $row["id_auteur"];
					
					sql_insertq("spip_me_auteur", array(
						"id_me" => $id_me,
						"id_auteur" => $dest
					));
					
				}
	
				$deja_vu["people"][$nom] = true;
	
			}		
		}
	}


	// Ajouter mot automatique (ze_mot)
	if ($ze_mot > 0) {
			
				$query = sql_query("SELECT id_mot, titre FROM spip_mots WHERE id_mot=$ze_mot");
				if ($row = sql_fetch($query)) {
					$id_mot = $row["id_mot"];			
					$titre = $row["titre"];			
					
					sql_insertq("spip_me_mot", array(
						"id_me" => $id_me,
						"id_mot" => $id_mot
					));
					
				}
	
				$deja_vu["mot"][$titre] = true;
				cache_mot($id_mot);

	}
	


	if ($id_parent > 0) $pave = $id_parent;
	else $pave = $id_me;

	// inserer_themes($id_me);

	// indexer tout de suite
	indexer_me($id_parent ? $id_parent : $id_me);


	// inserer les tags
	inserer_tags_liens($id_me);


	// notifications 
	// uniquement si nouveau message, et si ça n'est pas une «archive» (delicious notamment)
	if ($maj == 0 && $time == "NOW()") {
		job_queue_add(
			'notifier_me', 
			'notifier nouveau message '.$id_me, 
			array($id_me, $id_parent),
			"",
			true,
			time() + (60 * 5) 
		);
	}
	
	return array("id_me" => $id_me, "id_parent" => $id_parent, "maj" => $maj);
}

function inserer_tags_liens($id_me) {
	spip_log("inserer_tags_liens $id_me");

	$texte_message = texte_de_me($id_me);

	$t = sql_fetsel('uuid,date', 'spip_me', 'id_me='.$id_me);
	$uuid = $t['uuid'];
	$date = $t['date'];


	// Extraire les tags et fabriquer des mots-clés


	// Virer les liens hypertexte (qui peuvent contenir une chaîne #ancre)
	$message_off = preg_replace("/"._REG_URL."/ui", "", $texte_message);


	if (preg_match_all("/"._REG_HASH."/ui", $message_off, $regs)) {
		$tags = array();
		foreach ($regs[0] as $k=>$hash) {

			$tags[] = $hash;

			// DEBUT MOTS-CLES OLD STYLE

			$titre = substr($hash, 1);

			# peut-être ce tag existe-t-il déjà
			if ($s = sql_query('SELECT id_mot FROM spip_mots WHERE titre='.sql_quote($titre).' AND id_groupe=1')
			AND $t = sql_fetch($s)) {
				$id_mot = $t['id_mot'];
			}
			# sinon on le cree, on fabrique son URL, et on lance sa hierarchie
			else {
				$id_mot = sql_insertq ("spip_mots", 
					array(
						"titre" => $titre,
						"id_groupe" => 1
				));
				$url = generer_url_entite($id_mot, 'mot');

				// Hierarchiser ce mot en tache de fond
				job_queue_add(
					'hierarchiser_mot',
					'hierarchiser le mot '.$id_mot, 
					array($id_mot),
					"",
					true,
					time()
				);
			}

			sql_insertq("spip_me_mot", array(
				"id_me" => $id_me,
				"id_mot" => $id_mot,
				"date" => "NOW()"
			));

			cache_mot($id_mot);

			// FIN MOTS-CLES OLD STYLE

		}

		// tags new style (spip_me_tags)
		sql_delete('spip_me_tags', 'uuid='.sql_quote($uuid).' AND class="#"');
		foreach(array_unique($tags) as $tag) {
			sql_insertq('spip_me_tags', array(
				'id_me' => $id_me,
				'uuid' => $uuid,
				'tag' => $tag,
				'class' => '#',
				'date' => $date
			));
		}
	}
	
	// Extraire les liens et fabriquer des spip_syndic
	preg_match_all("/"._REG_URL."/ui", $texte_message, $regs);

	if ($regs) {
		foreach ($regs[0] as $k=>$url) {
		
			// Supprimer parenthese fermante finale si pas de parenthese ouvrante dans l'URL
			if (preg_match(",\)$,", $url) && !preg_match(",\(,", $url)) {
				$url = preg_replace(",\)$,", "", $url);
			}
		
			$url = preg_replace(",/$,", "", $url);
						
		
			if (!$deja_vu["url"][$url]) {


				$query = sql_query($a = "SELECT id_syndic FROM spip_syndic WHERE url_site=".sql_quote($url));
				if ($row = sql_fetch($query)) {
					$id_syndic = $row["id_syndic"];
					
					$query_total = sql_select("spip_me.id_me AS id_me_supp, spip_me.id_parent AS id_parent_supp, spip_me.id_auteur AS id_auteur_supp",
						"spip_me, spip_me_syndic",
						"spip_me_syndic.id_syndic=$id_syndic  AND spip_me_syndic.id_me=spip_me.id_me AND spip_me.statut='publi'");

					$total_syndic = sql_count($query_total);
					if ($total_syndic < 3) {
						while ($row_total = sql_fetch($query_total)) {
							$id_me_supp = $row_total["id_me_supp"];
							$id_parent_supp = $row_total["id_parent_supp"];
							$id_auteur_supp = $row_total["id_auteur_supp"];
							cache_me($id_me_supp, $id_parent_supp);
							cache_auteur($id_auteur_supp);
						}
					}
				} else {
					$id_syndic = sql_insertq ("spip_syndic", 
						array(
							"id_rubrique" => 1,
							"id_secteur" => 1,
							"nom_site" => $url,
							"url_site" => $url,
							"md5" => md5($url),
							"statut" => "publie",
							"date" => "NOW()"
					));
					job_queue_add('recuperer_contenu_site', 'récupérer_contenu_site '.$url, array($id_syndic, "$url"));
				}
				// echo "<li>$id_syndic - $url</li>";
				sql_insertq("spip_me_syndic", array(
					"id_me" => $id_me,
					"id_syndic" => $id_syndic
				));
				sql_insertq("spip_me_tags", array(
					"id_me" => $id_me,
					'uuid' => $uuid,
					"tag" => $url,
					"class" => 'url',
					"date" => 'NOW()'
				));
				// Hierarchiser l'URL
				hierarchier_url($id_syndic);
				$deja_vu["url"][$url] = true;
			}
		}
	}
	
}

// Transformer les caracteres utf8 d'une URL (farsi par ex) selon la RFC 1738
// Transformer aussi les caracteres de controle 00-1F, et l'espace 20
// la fonction urlencode_1738() du core ne suffit pas,
// car elle n'encode ni espaces, ni guillemets, etc.
function urlencode_1738_plus($url) {
	$uri = '';
	$l = strlen($url);
	for ($i=0; $i < $l; $i++) {
		$u = ord($a = $url[$i]);
		if ($u <= 0x20 OR $u >= 0x7F OR in_array($a, array("'",'"','+')))
			$a = rawurlencode($a);
		// le % depend : s'il est suivi d'un code hex, ou pas
		if ($a == '%'
		AND !preg_match('/^[0-9a-f][0-9a-f]$/i', $url[$i+1].$url[$i+2]))
			$a = rawurlencode($a);
		$uri .= $a;
	}
	return quote_amp($uri);
}

function erreur_405($texte, $err405 = 405) {
	 header("HTTP/1.0 ".$err405." $texte");
 	die("<html><body><h1>error $err405</h1><h2>$texte</h2></body></html>");
}


?>