-- phpMyAdmin SQL Dump
-- version 3.3.8
-- http://www.phpmyadmin.net
--
-- Serveur: localhost
-- Généré le : Mercredi 13 Avril 2011 à 17:00
-- Version du serveur: 5.0.51
-- Version de PHP: 5.2.6-1+lenny9

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Base de données: `seenthis`
--

-- --------------------------------------------------------

--
-- Structure de la table `spip_articles`
--

CREATE TABLE IF NOT EXISTS `spip_articles` (
  `id_article` bigint(21) NOT NULL auto_increment,
  `surtitre` text NOT NULL,
  `titre` text NOT NULL,
  `soustitre` text NOT NULL,
  `id_rubrique` bigint(21) NOT NULL default '0',
  `descriptif` text NOT NULL,
  `chapo` mediumtext NOT NULL,
  `texte` longtext NOT NULL,
  `ps` mediumtext NOT NULL,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `statut` varchar(10) NOT NULL default '0',
  `id_secteur` bigint(21) NOT NULL default '0',
  `maj` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `export` varchar(10) default 'oui',
  `date_redac` datetime NOT NULL default '0000-00-00 00:00:00',
  `visites` int(11) NOT NULL default '0',
  `referers` int(11) NOT NULL default '0',
  `popularite` double NOT NULL default '0',
  `accepter_forum` char(3) NOT NULL default '',
  `date_modif` datetime NOT NULL default '0000-00-00 00:00:00',
  `lang` varchar(10) NOT NULL default '',
  `langue_choisie` varchar(3) default 'non',
  `id_trad` bigint(21) NOT NULL default '0',
  `extra` longtext,
  `id_version` int(10) unsigned NOT NULL default '0',
  `nom_site` tinytext NOT NULL,
  `url_site` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id_article`),
  KEY `id_rubrique` (`id_rubrique`),
  KEY `id_secteur` (`id_secteur`),
  KEY `id_trad` (`id_trad`),
  KEY `lang` (`lang`),
  KEY `statut` (`statut`,`date`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=16 ;

-- --------------------------------------------------------

--
-- Structure de la table `spip_auteurs`
--

CREATE TABLE IF NOT EXISTS `spip_auteurs` (
  `id_auteur` bigint(21) NOT NULL auto_increment,
  `couleur` varchar(6) NOT NULL default '24b8dd',
  `nom` text NOT NULL,
  `bio` text NOT NULL,
  `email` tinytext NOT NULL,
  `nom_site` tinytext NOT NULL,
  `url_site` text NOT NULL,
  `login` varchar(255) character set utf8 collate utf8_bin default NULL,
  `pass` tinytext NOT NULL,
  `low_sec` tinytext NOT NULL,
  `statut` varchar(255) NOT NULL default '0',
  `webmestre` varchar(3) NOT NULL default 'non',
  `maj` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `pgp` text NOT NULL,
  `htpass` tinytext NOT NULL,
  `en_ligne` datetime NOT NULL default '0000-00-00 00:00:00',
  `imessage` varchar(3) default NULL,
  `messagerie` varchar(3) default NULL,
  `alea_actuel` tinytext,
  `alea_futur` tinytext,
  `prefs` tinytext,
  `cookie_oubli` tinytext,
  `source` varchar(10) NOT NULL default 'spip',
  `lang` varchar(10) NOT NULL default '',
  `extra` longtext,
  `openid` text NOT NULL,
  `troll` bigint(21) default NULL,
  `troll_forcer` bigint(21) default NULL,
  `copyright` varchar(10) NOT NULL default 'C',
  `mail_nouv_billet` tinyint(1) NOT NULL default '1',
  `mail_rep_moi` tinyint(1) NOT NULL default '1',
  `mail_rep_billet` tinyint(1) NOT NULL default '0',
  `mail_rep_conv` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id_auteur`),
  KEY `login` (`login`),
  KEY `statut` (`statut`),
  KEY `en_ligne` (`en_ligne`),
  FULLTEXT KEY `nom` (`nom`),
  FULLTEXT KEY `nom_2` (`nom`,`bio`),
  FULLTEXT KEY `nom_3` (`nom`,`bio`,`nom_site`,`url_site`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=345 ;

-- --------------------------------------------------------

--
-- Structure de la table `spip_auteurs_articles`
--

CREATE TABLE IF NOT EXISTS `spip_auteurs_articles` (
  `id_auteur` bigint(21) NOT NULL default '0',
  `id_article` bigint(21) NOT NULL default '0',
  PRIMARY KEY  (`id_auteur`,`id_article`),
  KEY `id_article` (`id_article`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `spip_auteurs_messages`
--

CREATE TABLE IF NOT EXISTS `spip_auteurs_messages` (
  `id_auteur` bigint(21) NOT NULL default '0',
  `id_message` bigint(21) NOT NULL default '0',
  `vu` char(3) default NULL,
  PRIMARY KEY  (`id_auteur`,`id_message`),
  KEY `id_message` (`id_message`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `spip_auteurs_rubriques`
--

CREATE TABLE IF NOT EXISTS `spip_auteurs_rubriques` (
  `id_auteur` bigint(21) NOT NULL default '0',
  `id_rubrique` bigint(21) NOT NULL default '0',
  PRIMARY KEY  (`id_auteur`,`id_rubrique`),
  KEY `id_rubrique` (`id_rubrique`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `spip_breves`
--

CREATE TABLE IF NOT EXISTS `spip_breves` (
  `id_breve` bigint(21) NOT NULL auto_increment,
  `date_heure` datetime NOT NULL default '0000-00-00 00:00:00',
  `titre` text NOT NULL,
  `texte` longtext NOT NULL,
  `lien_titre` text NOT NULL,
  `lien_url` text NOT NULL,
  `statut` varchar(6) NOT NULL default '0',
  `id_rubrique` bigint(21) NOT NULL default '0',
  `lang` varchar(10) NOT NULL default '',
  `langue_choisie` varchar(3) default 'non',
  `maj` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `extra` longtext,
  PRIMARY KEY  (`id_breve`),
  KEY `id_rubrique` (`id_rubrique`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `spip_documents`
--

CREATE TABLE IF NOT EXISTS `spip_documents` (
  `id_document` bigint(21) NOT NULL auto_increment,
  `id_vignette` bigint(21) NOT NULL default '0',
  `extension` varchar(10) NOT NULL default '',
  `titre` text NOT NULL,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `descriptif` text NOT NULL,
  `fichier` varchar(255) NOT NULL default '',
  `taille` int(11) default NULL,
  `largeur` int(11) default NULL,
  `hauteur` int(11) default NULL,
  `mode` enum('vignette','image','document') NOT NULL default 'document',
  `distant` varchar(3) default 'non',
  `maj` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `contenu` text NOT NULL,
  `extrait` varchar(3) NOT NULL default 'non',
  PRIMARY KEY  (`id_document`),
  KEY `id_vignette` (`id_vignette`),
  KEY `mode` (`mode`),
  KEY `extension` (`extension`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `spip_documents_liens`
--

CREATE TABLE IF NOT EXISTS `spip_documents_liens` (
  `id_document` bigint(21) NOT NULL default '0',
  `id_objet` bigint(21) NOT NULL default '0',
  `objet` varchar(25) NOT NULL default '',
  `vu` enum('non','oui') NOT NULL default 'non',
  PRIMARY KEY  (`id_document`,`id_objet`,`objet`),
  KEY `id_document` (`id_document`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `spip_forum`
--

CREATE TABLE IF NOT EXISTS `spip_forum` (
  `id_forum` bigint(21) NOT NULL auto_increment,
  `id_parent` bigint(21) NOT NULL default '0',
  `id_thread` bigint(21) NOT NULL default '0',
  `id_rubrique` bigint(21) NOT NULL default '0',
  `id_article` bigint(21) NOT NULL default '0',
  `id_breve` bigint(21) NOT NULL default '0',
  `date_heure` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_thread` datetime NOT NULL default '0000-00-00 00:00:00',
  `titre` text NOT NULL,
  `texte` mediumtext NOT NULL,
  `auteur` text NOT NULL,
  `email_auteur` text NOT NULL,
  `nom_site` text NOT NULL,
  `url_site` text NOT NULL,
  `statut` varchar(8) NOT NULL default '0',
  `ip` varchar(16) NOT NULL default '',
  `maj` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `id_auteur` bigint(20) NOT NULL default '0',
  `id_message` bigint(21) NOT NULL default '0',
  `id_syndic` bigint(21) NOT NULL default '0',
  PRIMARY KEY  (`id_forum`),
  KEY `id_auteur` (`id_auteur`),
  KEY `id_parent` (`id_parent`),
  KEY `id_thread` (`id_thread`),
  KEY `optimal` (`statut`,`id_parent`,`id_article`,`date_heure`,`id_breve`,`id_syndic`,`id_rubrique`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `spip_groupes_mots`
--

CREATE TABLE IF NOT EXISTS `spip_groupes_mots` (
  `id_groupe` bigint(21) NOT NULL auto_increment,
  `titre` text NOT NULL,
  `descriptif` text NOT NULL,
  `texte` longtext NOT NULL,
  `unseul` varchar(3) NOT NULL default '',
  `obligatoire` varchar(3) NOT NULL default '',
  `tables_liees` text NOT NULL,
  `minirezo` varchar(3) NOT NULL default '',
  `comite` varchar(3) NOT NULL default '',
  `forum` varchar(3) NOT NULL default '',
  `maj` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id_groupe`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=41 ;

-- --------------------------------------------------------

--
-- Structure de la table `spip_jobs`
--

CREATE TABLE IF NOT EXISTS `spip_jobs` (
  `id_job` bigint(21) NOT NULL auto_increment,
  `descriptif` text NOT NULL,
  `fonction` varchar(255) NOT NULL,
  `args` longblob NOT NULL,
  `md5args` char(32) NOT NULL default '',
  `inclure` varchar(255) NOT NULL,
  `priorite` smallint(6) NOT NULL default '0',
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `status` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`id_job`),
  KEY `date` (`date`),
  KEY `status` (`status`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=188171 ;

-- --------------------------------------------------------

--
-- Structure de la table `spip_jobs_liens`
--

CREATE TABLE IF NOT EXISTS `spip_jobs_liens` (
  `id_job` bigint(21) NOT NULL default '0',
  `id_objet` bigint(21) NOT NULL default '0',
  `objet` varchar(25) NOT NULL default '',
  PRIMARY KEY  (`id_job`,`id_objet`,`objet`),
  KEY `id_job` (`id_job`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `spip_me`
--

CREATE TABLE IF NOT EXISTS `spip_me` (
  `id_me` bigint(21) NOT NULL auto_increment,
  `date` datetime NOT NULL,
  `id_auteur` bigint(21) NOT NULL,
  `id_parent` bigint(21) NOT NULL,
  `texte` longtext character set utf8 NOT NULL,
  `themes` text character set utf8 NOT NULL,
  `statut` varchar(5) NOT NULL,
  `ip` varchar(40) character set utf8 NOT NULL,
  `id_dest` bigint(20) NOT NULL,
  `id_mot` bigint(20) NOT NULL,
  `troll` bigint(21) NOT NULL,
  PRIMARY KEY  (`id_me`),
  KEY `id_auteur` (`id_auteur`),
  KEY `id_parent` (`id_parent`),
  FULLTEXT KEY `texte` (`texte`,`themes`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=15870 ;

-- --------------------------------------------------------

--
-- Structure de la table `spip_messages`
--

CREATE TABLE IF NOT EXISTS `spip_messages` (
  `id_message` bigint(21) NOT NULL auto_increment,
  `titre` text NOT NULL,
  `texte` longtext NOT NULL,
  `type` varchar(6) NOT NULL default '',
  `date_heure` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_fin` datetime NOT NULL default '0000-00-00 00:00:00',
  `rv` varchar(3) NOT NULL default '',
  `statut` varchar(6) NOT NULL default '0',
  `id_auteur` bigint(21) NOT NULL,
  `maj` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id_message`),
  KEY `id_auteur` (`id_auteur`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `spip_meta`
--

CREATE TABLE IF NOT EXISTS `spip_meta` (
  `nom` varchar(255) NOT NULL,
  `valeur` text,
  `impt` enum('non','oui') NOT NULL default 'oui',
  `maj` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`nom`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `spip_me_auteur`
--

CREATE TABLE IF NOT EXISTS `spip_me_auteur` (
  `id_me` bigint(21) NOT NULL,
  `id_auteur` bigint(21) NOT NULL,
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  KEY `id_me` (`id_me`),
  KEY `id_auteur` (`id_auteur`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `spip_me_follow`
--

CREATE TABLE IF NOT EXISTS `spip_me_follow` (
  `id_follow` bigint(21) NOT NULL,
  `id_auteur` bigint(21) NOT NULL,
  `date` timestamp NOT NULL default '0000-00-00 00:00:00' on update CURRENT_TIMESTAMP,
  KEY `id_follow` (`id_follow`),
  KEY `id_auteur` (`id_auteur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `spip_me_follow_mot`
--

CREATE TABLE IF NOT EXISTS `spip_me_follow_mot` (
  `id_mot` bigint(21) NOT NULL,
  `id_follow` bigint(21) NOT NULL,
  `date` datetime NOT NULL,
  KEY `id_mot` (`id_mot`),
  KEY `id_follow` (`id_follow`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `spip_me_mot`
--

CREATE TABLE IF NOT EXISTS `spip_me_mot` (
  `id_me` bigint(21) NOT NULL,
  `id_mot` bigint(21) NOT NULL,
  `date` timestamp NOT NULL default '0000-00-00 00:00:00' on update CURRENT_TIMESTAMP,
  `relevance` int(11) NOT NULL,
  KEY `id_me` (`id_me`),
  KEY `id_mot` (`id_mot`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `spip_me_share`
--

CREATE TABLE IF NOT EXISTS `spip_me_share` (
  `id_me` bigint(20) NOT NULL,
  `id_auteur` bigint(20) NOT NULL,
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  KEY `id_me` (`id_me`),
  KEY `id_auteur` (`id_auteur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `spip_me_syndic`
--

CREATE TABLE IF NOT EXISTS `spip_me_syndic` (
  `id_me` bigint(21) NOT NULL,
  `id_syndic` bigint(21) NOT NULL,
  `date` timestamp NOT NULL default '0000-00-00 00:00:00' on update CURRENT_TIMESTAMP,
  KEY `date` (`date`),
  KEY `id_me` (`id_me`),
  KEY `id_syndic` (`id_syndic`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `spip_mots`
--

CREATE TABLE IF NOT EXISTS `spip_mots` (
  `id_mot` bigint(21) NOT NULL auto_increment,
  `id_parent` bigint(21) NOT NULL default '0',
  `titre` text NOT NULL,
  `descriptif` text NOT NULL,
  `texte` longtext NOT NULL,
  `id_groupe` bigint(21) NOT NULL default '0',
  `type` text NOT NULL,
  `extra` longtext,
  `maj` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id_mot`),
  KEY `id_parent` (`id_parent`),
  FULLTEXT KEY `tout` (`titre`,`texte`,`descriptif`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=26803 ;

-- --------------------------------------------------------

--
-- Structure de la table `spip_mots_articles`
--

CREATE TABLE IF NOT EXISTS `spip_mots_articles` (
  `id_mot` bigint(21) NOT NULL default '0',
  `id_article` bigint(21) NOT NULL default '0',
  PRIMARY KEY  (`id_article`,`id_mot`),
  KEY `id_mot` (`id_mot`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `spip_mots_breves`
--

CREATE TABLE IF NOT EXISTS `spip_mots_breves` (
  `id_mot` bigint(21) NOT NULL default '0',
  `id_breve` bigint(21) NOT NULL default '0',
  PRIMARY KEY  (`id_breve`,`id_mot`),
  KEY `id_mot` (`id_mot`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `spip_mots_documents`
--

CREATE TABLE IF NOT EXISTS `spip_mots_documents` (
  `id_mot` bigint(21) NOT NULL default '0',
  `id_document` bigint(21) NOT NULL default '0',
  PRIMARY KEY  (`id_document`,`id_mot`),
  KEY `id_mot` (`id_mot`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `spip_mots_forum`
--

CREATE TABLE IF NOT EXISTS `spip_mots_forum` (
  `id_mot` bigint(21) NOT NULL default '0',
  `id_forum` bigint(21) NOT NULL default '0',
  PRIMARY KEY  (`id_forum`,`id_mot`),
  KEY `id_mot` (`id_mot`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `spip_mots_rubriques`
--

CREATE TABLE IF NOT EXISTS `spip_mots_rubriques` (
  `id_mot` bigint(21) NOT NULL default '0',
  `id_rubrique` bigint(21) NOT NULL default '0',
  PRIMARY KEY  (`id_rubrique`,`id_mot`),
  KEY `id_mot` (`id_mot`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `spip_mots_syndic`
--

CREATE TABLE IF NOT EXISTS `spip_mots_syndic` (
  `id_mot` bigint(21) NOT NULL default '0',
  `id_syndic` bigint(21) NOT NULL default '0',
  PRIMARY KEY  (`id_syndic`,`id_mot`),
  KEY `id_mot` (`id_mot`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `spip_petitions`
--

CREATE TABLE IF NOT EXISTS `spip_petitions` (
  `id_article` bigint(21) NOT NULL default '0',
  `email_unique` char(3) NOT NULL default '',
  `site_obli` char(3) NOT NULL default '',
  `site_unique` char(3) NOT NULL default '',
  `message` char(3) NOT NULL default '',
  `texte` longtext NOT NULL,
  `maj` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id_article`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `spip_referers`
--

CREATE TABLE IF NOT EXISTS `spip_referers` (
  `referer_md5` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `referer` varchar(255) default NULL,
  `visites` int(10) unsigned NOT NULL,
  `maj` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `visites_veille` int(10) unsigned NOT NULL default '0',
  `visites_jour` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`referer_md5`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `spip_referers_articles`
--

CREATE TABLE IF NOT EXISTS `spip_referers_articles` (
  `id_article` int(10) unsigned NOT NULL,
  `referer_md5` bigint(20) unsigned NOT NULL,
  `referer` varchar(255) NOT NULL default '',
  `visites` int(10) unsigned NOT NULL,
  `maj` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id_article`,`referer_md5`),
  KEY `referer_md5` (`referer_md5`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `spip_resultats`
--

CREATE TABLE IF NOT EXISTS `spip_resultats` (
  `recherche` char(16) NOT NULL default '',
  `id` int(10) unsigned NOT NULL,
  `points` int(10) unsigned NOT NULL default '0',
  `maj` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `spip_rubriques`
--

CREATE TABLE IF NOT EXISTS `spip_rubriques` (
  `id_rubrique` bigint(21) NOT NULL auto_increment,
  `id_parent` bigint(21) NOT NULL default '0',
  `titre` text NOT NULL,
  `descriptif` text NOT NULL,
  `texte` longtext NOT NULL,
  `id_secteur` bigint(21) NOT NULL default '0',
  `maj` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `export` varchar(10) default 'oui',
  `id_import` bigint(20) default '0',
  `statut` varchar(10) NOT NULL default '0',
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `lang` varchar(10) NOT NULL default '',
  `langue_choisie` varchar(3) default 'non',
  `extra` longtext,
  `statut_tmp` varchar(10) NOT NULL default '0',
  `date_tmp` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id_rubrique`),
  KEY `lang` (`lang`),
  KEY `id_parent` (`id_parent`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Structure de la table `spip_signatures`
--

CREATE TABLE IF NOT EXISTS `spip_signatures` (
  `id_signature` bigint(21) NOT NULL auto_increment,
  `id_article` bigint(21) NOT NULL default '0',
  `date_time` datetime NOT NULL default '0000-00-00 00:00:00',
  `nom_email` text NOT NULL,
  `ad_email` text NOT NULL,
  `nom_site` text NOT NULL,
  `url_site` text NOT NULL,
  `message` mediumtext NOT NULL,
  `statut` varchar(10) NOT NULL default '0',
  `maj` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id_signature`),
  KEY `id_article` (`id_article`),
  KEY `statut` (`statut`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `spip_syndic`
--

CREATE TABLE IF NOT EXISTS `spip_syndic` (
  `id_syndic` bigint(21) NOT NULL auto_increment,
  `id_parent` bigint(21) NOT NULL,
  `id_rubrique` bigint(21) NOT NULL default '0',
  `id_secteur` bigint(21) NOT NULL default '0',
  `nom_site` text NOT NULL,
  `url_site` text NOT NULL,
  `url_syndic` text NOT NULL,
  `descriptif` text NOT NULL,
  `maj` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `syndication` varchar(3) NOT NULL default '',
  `statut` varchar(10) NOT NULL default '0',
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_syndic` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_index` datetime NOT NULL default '0000-00-00 00:00:00',
  `extra` longtext,
  `moderation` varchar(3) default 'non',
  `miroir` varchar(3) default 'non',
  `oubli` varchar(3) default 'non',
  `resume` varchar(3) default 'oui',
  `recup` int(11) NOT NULL default '0',
  `titre` text NOT NULL,
  `texte` longtext NOT NULL,
  `lang` varchar(10) NOT NULL,
  PRIMARY KEY  (`id_syndic`),
  KEY `id_rubrique` (`id_rubrique`),
  KEY `id_secteur` (`id_secteur`),
  KEY `statut` (`statut`,`date_syndic`),
  KEY `id_parent` (`id_parent`),
  FULLTEXT KEY `url_site` (`url_site`,`titre`,`texte`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=26681 ;

-- --------------------------------------------------------

--
-- Structure de la table `spip_syndic_articles`
--

CREATE TABLE IF NOT EXISTS `spip_syndic_articles` (
  `id_syndic_article` bigint(21) NOT NULL auto_increment,
  `id_syndic` bigint(21) NOT NULL default '0',
  `titre` text NOT NULL,
  `url` varchar(255) NOT NULL default '',
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `lesauteurs` text NOT NULL,
  `maj` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `statut` varchar(10) NOT NULL default '0',
  `descriptif` text NOT NULL,
  `lang` varchar(10) NOT NULL default '',
  `url_source` tinytext NOT NULL,
  `source` tinytext NOT NULL,
  `tags` text NOT NULL,
  PRIMARY KEY  (`id_syndic_article`),
  KEY `id_syndic` (`id_syndic`),
  KEY `statut` (`statut`),
  KEY `url` (`url`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `spip_syndic_oc`
--

CREATE TABLE IF NOT EXISTS `spip_syndic_oc` (
  `id_syndic` bigint(21) NOT NULL,
  `id_mot` bigint(21) NOT NULL,
  `relevance` int(11) NOT NULL,
  KEY `id_syndic` (`id_syndic`),
  KEY `id_mot` (`id_mot`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `spip_traductions`
--

CREATE TABLE IF NOT EXISTS `spip_traductions` (
  `hash` varchar(32) NOT NULL,
  `langue` varchar(5) NOT NULL,
  `texte` text character set utf8 NOT NULL,
  KEY `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `spip_types_documents`
--

CREATE TABLE IF NOT EXISTS `spip_types_documents` (
  `extension` varchar(10) NOT NULL default '',
  `titre` text NOT NULL,
  `descriptif` text NOT NULL,
  `mime_type` varchar(100) NOT NULL default '',
  `inclus` enum('non','image','embed') NOT NULL default 'non',
  `upload` enum('oui','non') NOT NULL default 'oui',
  `maj` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`extension`),
  KEY `inclus` (`inclus`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `spip_urls`
--

CREATE TABLE IF NOT EXISTS `spip_urls` (
  `url` varchar(255) NOT NULL,
  `type` varchar(15) NOT NULL default 'article',
  `id_objet` bigint(21) NOT NULL,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`url`),
  KEY `type` (`type`,`id_objet`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `spip_versions`
--

CREATE TABLE IF NOT EXISTS `spip_versions` (
  `id_article` bigint(21) NOT NULL,
  `id_version` bigint(21) NOT NULL default '0',
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `id_auteur` varchar(23) NOT NULL default '',
  `titre_version` text NOT NULL,
  `permanent` char(3) default NULL,
  `champs` text,
  PRIMARY KEY  (`id_article`,`id_version`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `spip_versions_fragments`
--

CREATE TABLE IF NOT EXISTS `spip_versions_fragments` (
  `id_fragment` int(10) unsigned NOT NULL default '0',
  `version_min` int(10) unsigned NOT NULL default '0',
  `version_max` int(10) unsigned NOT NULL default '0',
  `id_article` bigint(21) NOT NULL,
  `compress` tinyint(4) NOT NULL,
  `fragment` longblob,
  PRIMARY KEY  (`id_article`,`id_fragment`,`version_min`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `spip_visites`
--

CREATE TABLE IF NOT EXISTS `spip_visites` (
  `date` date NOT NULL,
  `visites` int(10) unsigned NOT NULL default '0',
  `maj` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `spip_visites_articles`
--

CREATE TABLE IF NOT EXISTS `spip_visites_articles` (
  `date` date NOT NULL,
  `id_article` int(10) unsigned NOT NULL,
  `visites` int(10) unsigned NOT NULL default '0',
  `maj` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`date`,`id_article`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
