<?php


function seenthis_afficher_contenu_objet($flux){
	// recuperer les saisies de l'objet en cours
	$objet = $flux['args']['type'];
	
	if ($objet == "auteur") {
		$id_auteur = $flux["args"]["contexte"]["id_auteur"];	
		$ret = recuperer_fond("prive/interface_bloquer_auteur", array("id_auteur"=>$id_auteur));
		
	
	
		$flux['data'] .= $ret;
	}
	
	return $flux;
}
