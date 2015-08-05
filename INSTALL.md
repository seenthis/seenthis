# Installer seenthis

## Structure du code et des données

Seenthis fonctionne comme un site SPIP classique, avec une base de données de type MySQL, des scripts en PHP et des squelettes SPIP.

Les utilisateurs sont enregistrés dans la table normale des auteurs de SPIP ; quelques champs supplémentaires permettent de conserver leurs informations de configuration.

Une table annexe spip_me_follow permet d'enregistrer la structure du réseau social (qui suit qui).

Les messages sont stockés dans une table spécifique, appelée spip_me; pour des raisons de performance, le contenu (texte) des messages n'est pas stocké dans cette table, mais dans une table annexe, spip_me_texte.

Les liens et les tags (qu'ils aient été marqués dans le contenu texte du message, + éventuellement ceux ajoutés automatiquement par OpenCalais) sont stockés dans une table spip_me_tags. C'est sur cette table que se feront la quasi-totalité des opérations permettant de savoir quels messages il faut afficher sur telle ou telle page, en fonction des préférences du visiteur (notamment, des auteurs et des tags qu'il suit, mais aussi des messages qu'il a mis en partage).

Deux tables permettent de savoir qui suit quels tags et quels URLs: spip_follow_tag, spip_follow_url.

Une table enregistre les partages : spip_me_share.


# Télécharger les composants :

# On commence par installer SPIP en version 2.1
```
cd www/
svn co svn://trac.rezo.net/spip/branches/spip-2.1/ seenthis/
```

*On ajoute ensuite les plugins suivants :*

```
cd seenthis/; 
svn co svn://zone.spip.org/spip-zone/_plugins_/plugins_seenthis/autoembed autoembed/

mkdir plugins; cd plugins/
svn co svn://zone.spip.org/spip-zone/_plugins_/css_imbriques/ css_imbriques/
svn co svn://zone.spip.org/spip-zone/_plugins_/plugins_seenthis/date_relative_dynamique/ date_relative_dynamique/
svn co svn://zone.spip.org/spip-zone/_plugins_/plugins_seenthis/detecter_langue/ detecter_langue/
svn co svn://zone.spip.org/spip-zone/_plugins_/fulltext/ fulltext/
svn co svn://zone.spip.org/spip-zone/_plugins_/gravatar/ gravatar/
svn co svn://zone.spip.org/spip-zone/_plugins_/inclure-ajaxload/ inclure-ajaxload/
svn co svn://zone.spip.org/spip-zone/_plugins_/iterateurs/ iterateurs/
svn co svn://zone.spip.org/spip-zone/_plugins_/job_queue/ job_queue/
svn co svn://zone.spip.org/spip-zone/_plugins_/plugins_seenthis/lien_court/branches/spip2/ lien_court/
svn co svn://zone.spip.org/spip-zone/_plugins_/mediabox/ mediabox/
svn co svn://zone.spip.org/spip-zone/_plugins_/memoization/ memoization/
svn co svn://zone.spip.org/spip-zone/_plugins_/plugins_seenthis/microcache/ microcache/
svn co svn://zone.spip.org/spip-zone/_plugins_/opensearch/ opensearch/
svn co svn://zone.spip.org/spip-zone/_plugins_/plugins_seenthis/recuperer_favicon/ recuperer_favicon/
svn co svn://zone.spip.org/spip-zone/_plugins_/textwheel/ textwheel/
svn co svn://zone.spip.org/spip-zone/_plugins_/typo_guillemets/ typo_guillemets/
svn co svn://zone.spip.org/spip-zone/_plugins_/champs_extras/core/branches/v1/ champs_extras2/
svn co svn://zone.spip.org/spip-zone/_plugins_/saisies/ saisies/
svn co svn://zone.spip.org/spip-zone/_plugins_/cfg/branches/v1 cfg/
svn co svn://zone.spip.org/spip-zone/_plugins_/soundmanager/ soundmanager/
svn co svn://zone.spip.org/spip-zone/_plugins_/photoswipe/trunk/ photoswipe/

svn co https://github.com/seenthis/seenthis/trunk/ seenthis/
svn co https://github.com/seenthis/seenthis_squelettes/trunk/ seenthis_squelettes/
```


Si tout va bien, voici la liste des plugins présents :
* cfg
* champs_extras2
* css_imbriques
* date_relative_dynamique
* detecter_langue
* fulltext
* gravatar
* inclure-ajaxload
* iterateurs
* job_queue
* lien_court
* mediabox
* memoization
* microcache
* oembed
* opensearch
* photoswipe
* recuperer_favicon
* saisies
* seenthis
* seenthis_squelettes
* soundmanager
* textwheel
* typo_guillemets

# Activation du site

On active alors la connexion SQL du site SPIP :
[http://localhost/seenthis/ecrire/](http://localhost/seenthis/ecrire/) (entrer le mot de passe SQL, créer la base, créer le premier compte utilisateur).

À noter : le compte id_auteur=3 est pris comme base pour la couleur des liens de la page d'accueil (cf. plugins/seenthis_squelettes/bonjour.html) ; tant qu'il n'existe pas, le site est un peu de guingois. => créer 2 autres auteurs, et nommer "seenthis" l'auteur numéro 3.

Activer tous les plugins pré-cités, sauf :
* OpenSearch
* option = activer seenthis_opencalais
* option = activer seenthis_importer_flux

Créer un fichier `config/mes_options.php` :

```php
<?php

// localhost/seenthis
define('_ANALYTICS', '');
define('_HOST', 'localhost/seenthis');
define('_STATIC_HOST', _HOST);
define('_SHORT_HOST', 'localhost/seenli');
define('_API_HTTPS', false); # ne pas forcer https sur l'API

$GLOBALS['table_titre']['auteurs'] = 'login';

define('_URL_SEENTHIS', 'http://localhost/seenthis/');  # adresse de l'API
define('_SVG2PNG_SERVER','http://rezo.net/svg2png.php'); # convertisseur de SVG

// systeme pour les traductions
#define('_OPENCALAIS_APIKEY', "xxxxxxxxxxxx");
#define('_GOOGLETRANSLATE_APIKEY', "xxxxxxxxxxxxx");
# translate-shell
define('_TRANSLATESHELL_CMD', 'trans'); // /usr/local/bin/trans

// autoriser charger une copie locale des gros fichiers
// exemple http://localhost/seenthis/messages/96
define('_COPIE_LOCALE_MAX_SIZE',33554432);  # 32 Mo

// autoriser les logins de 3 lettres :
define('_LOGIN_TROP_COURT', 2);

// A titre d'exemple voici la configuration de seenthis.net
#define('_ANALYTICS', 'UA-2985608-16');
#define('_HOST', 'seenthis.net');
#define('_STATIC_HOST', 'static%s.'._HOST);

?>
```

Créer un fichier `.htaccess` à la racine du site, en prenant comme exemple `plugins/seenthis/htaccess.txt`


Renommer le htacccess.txt en .htaccess (cela va sans dire).

Aller sur http://localhost/seenthis/?var_mode=recalcul ; vous avez un seenthis un peu bancal mais presque OK !



# Configuration
## SPIP
[http://localhost/seenthis/ecrire/?exec=config_fonctions](http://localhost/seenthis/ecrire/?exec=config_fonctions)
=> choisir GD2
type d'urls: _seenthis_

[http://localhost/seenthis/ecrire/?exec=config_contenu#configurer-redacteurs](http://localhost/seenthis/ecrire/?exec=config_contenu#configurer-redacteurs)
=> choisir "Accepter les inscriptions"

## gravatar:
[http://localhost/seenthis/ecrire/?exec=configurer_gravatar](http://localhost/seenthis/ecrire/?exec=configurer_gravatar)
=> saisir 48 pour la dimension

## moteur de recherche fulltext:
```
ALTER table spip_me_recherche ENGINE=MyISAM;
ALTER TABLE spip_me_recherche ADD FULLTEXT titre (`titre`);
ALTER TABLE spip_me_recherche ADD FULLTEXT texte (`texte`);

ALTER table spip_syndic ENGINE=MyISAM;
ALTER TABLE spip_syndic ADD FULLTEXT tout (`url_site`,`titre`,`texte`);
```


# Liens "à lire" et pied de page :
`inc/alire.html` affiche les articles de la rubrique 2;
`inc/footer.html` les articles de la rubrique 3


# Ajout des plugins optionnels 

## seenthis_importer_flux

Ce plugin ajoute un champ 'rss' dans spip_auteurs, et une tâche cron qui charge ce RSS et le transforme en seens (et en partages si l'URL est déjà mentionnée dans un autre seen).
```
svn co https://github.com/seenthis/seenthis_importer_flux/trunk/ seenthis_importer_flux/
```


## seenthis_opencalais

Ce plugin s'interface avec l'API OpenCalais (il faut demander une clé d'API), pour thématiser de façon automatique les seens.
```
svn co https://github.com/seenthis/seenthis_opencalais/trunk/ seenthis_opencalais/
````

## champs_extras2

permet aux admins d'éditer les préférences des auteurs depuis l'espace privé.

## Palette
```
svn co svn://zone.spip.org/spip-zone/_plugins_/palette/branches/1_3/ palette/
```
le configurer pour activation sur les pages publiques permet d'offrir la roue chromatique dans les préférences (http://contrib.spip.net/Palette) ; attention, palette nécessite l'activation de cfg.

## seenthis_arbo_anglais
```
svn co https://github.com/seenthis/seenthis_arbo_anglais/trunk/ seenthis_arbo_anglais/
```
un plugin bidouille pour les URLs des articles (et pas des messages) de seenthis.net
à priori ce n'est pas indispensable

## Sphinx

Pour avoir le moteur de recherche basé sur sphinx, installer d'abord Sphinx (http://sphinxsearch.com/ - version 2.2.3 minimum!), puis ajouter deux plugins :
```
svn co https://github.com/seenthis/seenthis_sphinx/trunk/ seenthis_sphinx/
svn co svn://zone.spip.org/spip-zone/_plugins_/indexer/trunk indexer/
```
(voir la doc détaillée sur le plugin indexer)

Par défaut le nom de la base sphinx est `seenthis`, on peut le personnaliser avec
```
define('_SPHINXQL_INDEX', 'seenthis');
define('SPHINX_DEFAULT_INDEX', 'seenthis'); // ne me demandez pas pourquoi on a les deux notations (todo)
define('SPHINX_SERVER_PORT', 9306);
```

Configurer cet index, par exemple dans le fichier `/var/local/sphinx/conf/sites/seenthis.cf`
ajouter :

```
index seenthis
{
        type = rt
        path = /var/local/sphinx/data/seenthis
        rt_field              = title
        rt_attr_string        = title
        rt_field              = summary 
        rt_attr_string        = summary
        rt_field              = content
        rt_attr_string        = content
        rt_attr_timestamp     = date
        rt_attr_string        = uri
        rt_attr_json          = properties
        rt_attr_string        = signature
        dict = keywords
        morphology = stem_en, libstemmer_fr
        <?php echo _CHARSET_INDEXATION_FR ; ?>
}
```

et réindexer le contenu existant :
`php cli_indexer.php tout`


# Personnalisation

Voilà ; vous pouvez commencer à personnaliser les squelettes en les recopiant dans squelettes/

À noter : “microcache”, le cache de seenthis, est très agressif, et tout ce qui est de l'ordre du CSS ou javascript est parfois difficile à recalculer : en cas de doute, ne pas hésiter à purger le répertoire `local/microcache/`
