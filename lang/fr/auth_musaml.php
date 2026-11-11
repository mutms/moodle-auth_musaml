<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

/**
 * Strings for component 'auth_musaml', language 'fr'.
 *
 * NOTE: This French translation was produced automatically by an AI language
 * model and has not yet been reviewed by a human translator. Corrections are
 * welcome.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['attribute_advertised'] = 'Attributs proposés par les métadonnées du fournisseur d’identité : {$a}';
$string['attribute_create'] = 'Ajouter un attribut';
$string['attribute_delete'] = 'Supprimer l’attribut';
$string['attribute_delete_info'] = 'L’appariement est supprimé, les valeurs déjà copiées dans les profils des utilisateurs sont conservées.';
$string['attribute_idpattr'] = 'Attribut de l’IdP';
$string['attribute_idpattr_help'] = 'Nom de l’attribut tel que le fournisseur d’identité l’envoie, sensible à la casse. Le nom spécial « nameid » reprend la valeur du NameID SAML. Une connexion de test affiche tout ce qui est reçu.';
$string['attribute_sync'] = 'Synchronisation';
$string['attribute_sync_help'] = 'Jamais : utilisé uniquement pour l’appariement automatique. À la création du compte : copié une seule fois. À chaque connexion : maintenu identique à la valeur du fournisseur d’identité, généralement avec le champ verrouillé.';
$string['attribute_sync_none'] = 'Jamais';
$string['attribute_sync_oncreate'] = 'À la création du compte';
$string['attribute_sync_onlogin'] = 'À chaque connexion';
$string['attribute_update'] = 'Modifier l’attribut';
$string['attribute_userfield'] = 'Champ utilisateur';
$string['attribute_userfield_help'] = 'Champ utilisateur de Moodle qui reçoit la valeur. Le nom d’utilisateur n’est défini qu’à la création du compte utilisateur.';
$string['attribute_usermapping'] = 'Utilisé pour l’appariement automatique';
$string['attribute_usermapping_help'] = 'La valeur doit correspondre à exactement un utilisateur.';
$string['attribute_value'] = 'Valeur';
$string['attributes_none'] = 'Aucun attribut n’est encore apparié.';
$string['auth_musamldescription'] = 'Les utilisateurs se connectent par l’intermédiaire de fournisseurs d’identité SAML 2.0 externes.';
$string['check_certificates'] = 'Certificats SAML';
$string['check_certificates_allok'] = 'Tous les certificats sont valides (nombre : {$a}).';
$string['check_certificates_disabled'] = 'L’authentification SAML est désactivée.';
$string['check_certificates_expired'] = 'Le certificat pour {$a->name} est échu depuis {$a->time}.';
$string['check_certificates_none'] = 'Aucun certificat avec une date d’échéance n’a été trouvé.';
$string['check_certificates_ok'] = 'Le certificat pour {$a->name} arrive à échéance dans {$a->time}.';
$string['check_certificates_soon'] = 'Le certificat pour {$a->name} arrive bientôt à échéance, dans {$a->time}.';
$string['error_certificate'] = 'Le texte ne contient pas de certificats valides.';
$string['error_createemail'] = 'Impossible de créer un nouveau compte utilisateur, l’adresse de courriel « {$a} » n’est pas utilisable.';
$string['error_createmissing'] = 'Impossible de créer un nouveau compte utilisateur, le fournisseur d’identité n’a pas envoyé le champ « {$a} ».';
$string['error_createusername'] = 'Impossible de créer un nouveau compte utilisateur, le nom d’utilisateur « {$a} » n’est pas utilisable.';
$string['error_customsettings_json'] = 'Les réglages personnalisés ne sont pas du JSON valide : {$a}';
$string['error_customsettings_object'] = 'Les réglages personnalisés doivent être un objet JSON dont les clefs sont des noms de réglages de php-saml.';
$string['error_entityidexists'] = 'Un fournisseur d’identité avec l’identifiant d’entité « {$a} » existe déjà.';
$string['error_entityidtoolong'] = 'L’identifiant d’entité dépasse 255 caractères, ce fournisseur d’identité ne peut pas être utilisé.';
$string['error_guidmapped'] = 'Ce compte du fournisseur d’identité est déjà apparié à un autre utilisateur.';
$string['error_idpunavailable'] = 'Ce fournisseur d’identité n’est pas disponible.';
$string['error_logout'] = 'Le message de déconnexion du fournisseur d’identité a été rejeté : {$a}';
$string['error_metadatablocked'] = 'L’URL des métadonnées {$a} est bloquée par les réglages de sécurité cURL du site, voir « Sécurité HTTP » dans l’administration du site.';
$string['error_metadatadownload'] = 'Échec du téléchargement des métadonnées : {$a}';
$string['error_metadatanourl'] = 'Des métadonnées collées ne peuvent pas être actualisées. Collez de nouvelles métadonnées dans les paramètres du fournisseur d’identité.';
$string['error_metadataparse'] = 'Les métadonnées ne peuvent pas être analysées : {$a}';
$string['error_metadataurl'] = 'L’URL des métadonnées doit commencer par http:// ou https://';
$string['error_noguid'] = 'Le fournisseur d’identité n’a pas envoyé exactement une valeur de l’attribut d’identifiant utilisateur « {$a} ».';
$string['error_nohttps'] = 'Ce site n’utilise pas https. Les connexions SAML ne fonctionnent qu’avec un fournisseur d’identité situé sur ce même site.';
$string['error_nomapping'] = 'Aucun utilisateur n’est apparié au compte « {$a} » du fournisseur d’identité. Contactez l’administrateur du site.';
$string['error_norequest'] = 'Aucune demande de connexion en attente n’a été trouvée pour cette réponse, veuillez recommencer la connexion.';
$string['error_otherauth'] = 'Votre compte utilise la méthode d’authentification « {$a} » et ne peut pas être utilisé avec ce fournisseur d’identité.';
$string['error_replay'] = 'Cette réponse du fournisseur d’identité a déjà été utilisée, veuillez vous connecter à nouveau.';
$string['error_response'] = 'La réponse du fournisseur d’identité a été rejetée : {$a}';
$string['error_spcertinuse'] = 'Le certificat du fournisseur de services ne peut pas être supprimé tant que des fournisseurs d’identité sont configurés.';
$string['error_spmetadata'] = 'Les métadonnées du fournisseur de services ne sont pas valides : {$a}';
$string['error_spnotconfigured'] = 'Le certificat du fournisseur de services n’a pas encore été créé.';
$string['error_suspended'] = 'Votre compte est suspendu.';
$string['error_userfieldmapped'] = 'Ce champ utilisateur est déjà apparié pour ce fournisseur d’identité.';
$string['error_usermapped'] = 'L’utilisateur est déjà apparié à un fournisseur d’identité.';
$string['error_usernameprefix'] = 'Le préfixe ne peut contenir que des lettres minuscules, des chiffres, des points, des tirets et des tirets bas, 50 caractères au maximum.';
$string['error_usernamesync'] = 'Le nom d’utilisateur ne peut être copié qu’à la création du compte utilisateur.';
$string['event_idp_created'] = 'Fournisseur d’identité créé';
$string['event_idp_deleted'] = 'Fournisseur d’identité supprimé';
$string['event_idp_updated'] = 'Fournisseur d’identité mis à jour';
$string['event_login_failed'] = 'Échec de la connexion SAML';
$string['event_metadata_refresh_failed'] = 'Échec de l’actualisation des métadonnées du fournisseur d’identité';
$string['event_user_mapping_created'] = 'Appariement d’utilisateur créé';
$string['event_user_mapping_deleted'] = 'Appariement d’utilisateur supprimé';
$string['idp_advertisedattributes'] = 'Attributs dans les métadonnées';
$string['idp_attrsimple'] = 'Noms d’attributs simples';
$string['idp_attrsimple_help'] = 'Accepter aussi la dernière partie des noms d’attributs de type URI, par exemple emailaddress pour http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress.';
$string['idp_autocreate'] = 'Créer automatiquement les comptes utilisateur';
$string['idp_autocreate_help'] = 'De nouveaux comptes utilisateur sont créés à la première connexion pour les comptes inconnus du fournisseur d’identité. Les appariements d’attributs doivent fournir au moins le nom d’utilisateur et l’adresse de courriel.';
$string['idp_autologin'] = 'Connexion automatique';
$string['idp_autologin_help'] = 'Lorsqu’il s’agit du seul fournisseur d’identité disponible sur la page de connexion, les visiteurs y sont envoyés immédiatement au lieu de voir le formulaire de connexion.';
$string['idp_autologin_noslo'] = '{$a} ne prend pas en charge la déconnexion unique, les utilisateurs restent donc connectés auprès du fournisseur d’identité après s’être déconnectés ici. La personne suivante qui utilise le même navigateur est connectée sous leur identité. N’utilisez pas la connexion automatique sur des ordinateurs partagés.';
$string['idp_automap'] = 'Apparier automatiquement les utilisateurs existants';
$string['idp_automap_help'] = 'Apparier à la première connexion un utilisateur avec l’authentification SAML et sans appariement, lorsque tous les attributs marqués pour l’appariement automatique correspondent à son profil.';
$string['idp_autorefresh'] = 'Actualiser automatiquement les métadonnées';
$string['idp_autorefresh_help'] = 'Les métadonnées sont téléchargées à nouveau une fois par jour après une connexion réussie, et immédiatement lorsqu’une réponse ne peut pas être vérifiée. Désactivez cette option pour conserver les certificats enregistrés.';
$string['idp_certificate_manual'] = 'Ajouté manuellement';
$string['idp_certificate_metadata'] = 'Issu des métadonnées';
$string['idp_certificate_source'] = 'Source';
$string['idp_certificates'] = 'Certificats de signature';
$string['idp_certificates_edit'] = 'Paramètres des certificats';
$string['idp_create'] = 'Ajouter un fournisseur d’identité';
$string['idp_created'] = 'Le fournisseur d’identité a été ajouté';
$string['idp_customsettings'] = 'Réglages personnalisés de la bibliothèque';
$string['idp_customsettings_help'] = 'Objet JSON fusionné par-dessus les réglages de php-saml, par exemple {"security": {"wantAssertionsSigned": false}}. Les remplacements non sécurisés sont autorisés, mais affichent un avertissement. À n’utiliser que lorsque rien d’autre ne fonctionne.';
$string['idp_delete'] = 'Supprimer le fournisseur d’identité';
$string['idp_delete_info'] = 'Le fournisseur d’identité, ses appariements d’attributs et ses appariements d’utilisateurs (nombre : {$a}) seront supprimés. Les comptes utilisateur sont conservés.';
$string['idp_deleted'] = 'Le fournisseur d’identité a été supprimé';
$string['idp_enabled'] = 'Activé';
$string['idp_entityid'] = 'Identifiant d’entité';
$string['idp_extracerts'] = 'Certificats de signature supplémentaires';
$string['idp_extracerts_help'] = 'Certificats acceptés en plus de ceux des métadonnées, au format PEM, l’un à la suite de l’autre. Utilisez ce champ lors d’un renouvellement de clef, lorsque le fournisseur d’identité n’a pas encore publié le nouveau certificat.';
$string['idp_logouturl'] = 'URL de déconnexion unique';
$string['idp_mapattr'] = 'Attribut d’identifiant utilisateur';
$string['idp_mapattr_help'] = 'Attribut contenant l’identifiant permanent de l’utilisateur chez le fournisseur d’identité. Il ne doit jamais changer pour un utilisateur ni être réutilisé. Le nom spécial « nameid » reprend la valeur du NameID SAML.';
$string['idp_metadata_error'] = 'Échec de la dernière actualisation : {$a}';
$string['idp_metadata_fetched'] = 'Métadonnées récupérées';
$string['idp_metadata_never'] = 'Jamais';
$string['idp_metadata_refresh'] = 'Actualiser les métadonnées';
$string['idp_metadata_refresh_info'] = 'Les métadonnées sont téléchargées à nouveau depuis l’URL des métadonnées. L’identifiant d’entité, les points de terminaison et les certificats de signature sont remplacés par les valeurs actuelles.';
$string['idp_metadata_refreshed'] = 'Les métadonnées ont été actualisées';
$string['idp_metadata_validuntil'] = 'Métadonnées valides jusqu’au';
$string['idp_metadatapasted'] = 'XML collé, non actualisé automatiquement';
$string['idp_metadatasource'] = 'URL ou XML des métadonnées';
$string['idp_metadatasource_help'] = 'URL des métadonnées SAML 2.0 du fournisseur d’identité, ou le XML des métadonnées lui-même. Une URL est actualisée automatiquement, un XML collé doit être remplacé manuellement lorsque les certificats changent.';
$string['idp_metadataurl'] = 'URL des métadonnées';
$string['idp_metadataurl_help'] = 'URL publique des métadonnées SAML 2.0 du fournisseur d’identité. L’identifiant d’entité, l’URL d’authentification unique et les certificats de signature y sont lus et actualisés automatiquement.';
$string['idp_name'] = 'Nom';
$string['idp_name_help'] = 'Affiché sur le bouton de la page de connexion.';
$string['idp_nameidformat'] = 'Format du NameID';
$string['idp_provider'] = 'Fournisseur';
$string['idp_provider_help'] = 'Préremplit les attributs et adapte les réglages de la bibliothèque aux particularités connues du produit. Utilisez SAML 2.0 générique pour tout autre produit.';
$string['idp_settings_relaxed'] = 'Moins strict que les valeurs par défaut du plugin, défini par le fournisseur ou par les réglages personnalisés de la bibliothèque : {$a}';
$string['idp_ssourl'] = 'URL d’authentification unique';
$string['idp_tenant'] = 'Locataire';
$string['idp_tenant_help'] = 'Proposé uniquement sur la page de connexion de ce locataire. Sans locataire, il est proposé partout.';
$string['idp_update'] = 'Modifier le fournisseur d’identité';
$string['idp_updated'] = 'Le fournisseur d’identité a été mis à jour';
$string['idp_usernameprefix'] = 'Préfixe du nom d’utilisateur';
$string['idp_usernameprefix_help'] = 'Ajouté aux noms d’utilisateur des comptes utilisateur créés et utilisé lors de la recherche de correspondance des noms d’utilisateur. Garantit l’unicité des noms d’utilisateur entre les fournisseurs d’identité.';
$string['idps'] = 'Fournisseurs d’identité';
$string['idps_none'] = 'Aucun fournisseur d’identité n’a encore été ajouté.';
$string['idps_none_info'] = 'Cliquez sur Ajouter un fournisseur d’identité et collez l’URL de ses métadonnées.';
$string['idps_none_info2'] = 'Envoyez au fournisseur d’identité les métadonnées du fournisseur de services ci-dessus. Les deux étapes peuvent être réalisées dans n’importe quel ordre.';
$string['import'] = 'Importer des appariements d’utilisateurs';
$string['import_column_ignore'] = 'Non utilisée';
$string['import_column_number'] = 'Colonne {$a}';
$string['import_columns'] = 'Signification des colonnes';
$string['import_columns_headers'] = 'La première ligne contient les noms des colonnes';
$string['import_columns_info'] = 'Indiquez ce que contient chaque colonne. Nombre de lignes lues : {$a}.';
$string['import_confirm'] = 'Importer les appariements';
$string['import_counts'] = 'Prêts à être importés : {$a->created}. Ignorés : {$a->skipped}. Problèmes : {$a->error}.';
$string['import_done'] = 'Appariements d’utilisateurs importés : {$a->created}, ignorés : {$a->skipped}.';
$string['import_error_ambiguous'] = 'Plusieurs utilisateurs correspondent à « {$a} ».';
$string['import_error_columntwice'] = 'Chaque signification de colonne ne peut être utilisée qu’une seule fois.';
$string['import_error_columnunknown'] = 'Cette signification de colonne est inconnue.';
$string['import_error_empty'] = 'Aucune ligne utilisable n’a été trouvée.';
$string['import_error_guidused'] = 'Le compte « {$a} » du fournisseur d’identité est déjà apparié à un autre utilisateur.';
$string['import_error_headers'] = 'Indiquez si la première ligne contient les noms des colonnes.';
$string['import_error_invalid'] = 'La ligne ne contient pas un identifiant de compte du fournisseur d’identité et un identifiant d’utilisateur.';
$string['import_error_mapped'] = 'L’utilisateur « {$a} » est déjà apparié à un autre compte du fournisseur d’identité.';
$string['import_error_mappedsame'] = 'L’utilisateur « {$a} » possède déjà cet appariement.';
$string['import_error_missing'] = 'Aucun utilisateur ne correspond à « {$a} ».';
$string['import_error_nodatarows'] = 'Hormis les noms des colonnes, il n’y a aucune donnée.';
$string['import_error_noguid'] = 'Une colonne doit contenir l’identifiant de compte du fournisseur d’identité.';
$string['import_error_nouser'] = 'Une colonne doit identifier l’utilisateur.';
$string['import_error_otherauth'] = 'Le compte utilisateur utilise la méthode d’authentification « {$a} », autorisez une autre authentification ou changez la méthode du compte.';
$string['import_error_rowsize'] = 'Toutes les lignes doivent contenir le même nombre de colonnes.';
$string['import_error_suspended'] = 'L’utilisateur « {$a} » est suspendu.';
$string['import_failed'] = 'Rien n’a été importé. Nombre de lignes présentant des problèmes : {$a->error}.';
$string['import_more_rows'] = 'Nombre de lignes supplémentaires non affichées : {$a}.';
$string['import_options'] = 'Options d’importation';
$string['import_preview'] = 'Vérifier les données';
$string['import_refresh'] = 'Actualiser l’aperçu';
$string['import_result_created'] = 'Sera importé';
$string['import_result_error'] = 'Problème';
$string['import_result_skipped'] = 'Ignoré';
$string['import_skip_ambiguous'] = 'Ignorer les lignes correspondant à plusieurs utilisateurs';
$string['import_skip_guidused'] = 'Ignorer les lignes dont le compte du fournisseur d’identité est déjà apparié';
$string['import_skip_info'] = 'Les lignes présentant un problème bloquent l’importation, sauf si elles sont ignorées ici. Rien n’est enregistré tant que les données n’ont pas été vérifiées.';
$string['import_skip_invalid'] = 'Ignorer les lignes avec des valeurs manquantes';
$string['import_skip_mapped'] = 'Ignorer les utilisateurs déjà appariés';
$string['import_skip_missing'] = 'Ignorer les lignes sans utilisateur correspondant';
$string['import_skip_unusable'] = 'Ignorer les utilisateurs suspendus et les utilisateurs qui ne peuvent pas utiliser ce fournisseur d’identité';
$string['import_source_info'] = 'Déposez un fichier CSV ou collez les données.';
$string['musaml:managemappings'] = 'Gérer les appariements des utilisateurs aux comptes du fournisseur d’identité';
$string['pluginname'] = 'Authentification SAML';
$string['privacy:metadata:auth_musaml_login'] = 'Connexions en cours, supprimées à la fin de la connexion et au plus tard après quelques minutes';
$string['privacy:metadata:auth_musaml_login:idpid'] = 'Fournisseur d’identité';
$string['privacy:metadata:auth_musaml_login:resultjson'] = 'Attributs reçus du fournisseur d’identité';
$string['privacy:metadata:auth_musaml_login:timecreated'] = 'Date de début de la connexion';
$string['privacy:metadata:auth_musaml_login:userid'] = 'Utilisateur identifié par la connexion';
$string['privacy:metadata:auth_musaml_user'] = 'Appariements des utilisateurs aux comptes du fournisseur d’identité';
$string['privacy:metadata:auth_musaml_user:allowotherauth'] = 'Indique si l’utilisateur peut aussi se connecter avec une autre méthode d’authentification';
$string['privacy:metadata:auth_musaml_user:automapped'] = 'Indique si l’appariement a été créé automatiquement à la première connexion';
$string['privacy:metadata:auth_musaml_user:guid'] = 'Identifiant unique de l’utilisateur chez le fournisseur d’identité';
$string['privacy:metadata:auth_musaml_user:idpid'] = 'Fournisseur d’identité';
$string['privacy:metadata:auth_musaml_user:timecreated'] = 'Date de création de l’appariement';
$string['privacy:metadata:auth_musaml_user:userid'] = 'Utilisateur Moodle';
$string['privacy:metadata:authsubsystem'] = 'Le plugin connecte les utilisateurs par l’intermédiaire du sous-système d’authentification.';
$string['provider_adfs'] = 'Active Directory Federation Services';
$string['provider_auto'] = 'Détecter à partir des métadonnées';
$string['provider_entra'] = 'Microsoft Entra ID';
$string['provider_generic'] = 'SAML 2.0 générique';
$string['provider_google'] = 'Google Workspace';
$string['provider_keycloak'] = 'Keycloak';
$string['provider_okta'] = 'Okta';
$string['provider_zitadel'] = 'Zitadel';
$string['setting_debug'] = 'Journalisation de débogage';
$string['setting_debug_desc'] = 'Écrire les détails des requêtes et des réponses SAML dans le journal d’erreurs PHP. Ne pas activer sur les sites de production.';
$string['setting_fieldlocks_desc'] = 'Les champs verrouillés ne peuvent pas être modifiés par les utilisateurs. Les verrouillages s’appliquent à tous les utilisateurs avec l’authentification SAML, appariés ou non.';
$string['sp'] = 'Fournisseur de services';
$string['sp_acsurl'] = 'URL du service consommateur d’assertions';
$string['sp_cert'] = 'Certificat';
$string['sp_cert_commonname'] = 'Nom commun';
$string['sp_cert_create'] = 'Générer le certificat';
$string['sp_cert_days'] = 'Validité en jours';
$string['sp_cert_delete'] = 'Supprimer le certificat';
$string['sp_cert_delete_info'] = 'Le certificat du fournisseur de services, sa clef privée et l’identifiant d’entité sont supprimés.';
$string['sp_cert_download'] = 'Télécharger le certificat';
$string['sp_cert_fingerprint'] = 'Empreinte du certificat (SHA-256)';
$string['sp_cert_missing'] = 'Générez d’abord le certificat du fournisseur de services, les fournisseurs d’identité en ont besoin lors de la configuration.';
$string['sp_cert_notafter'] = 'Échéance du certificat';
$string['sp_cert_organizationname'] = 'Organisation';
$string['sp_cert_regen'] = 'Régénérer le certificat';
$string['sp_cert_regen_warning'] = 'Le certificat et la clef privée sont remplacés immédiatement. Les connexions échouent jusqu’à ce que chaque fournisseur d’identité dispose des nouvelles métadonnées du fournisseur de services.';
$string['sp_cert_subject'] = 'Sujet du certificat';
$string['sp_entityid'] = 'Identifiant d’entité';
$string['sp_entityid_help'] = 'Identifie ce site auprès des fournisseurs d’identité, par défaut l’URL des métadonnées. Il ne peut être modifié qu’en même temps que le certificat, les deux doivent alors être enregistrés à nouveau auprès de chaque fournisseur d’identité.';
$string['sp_metadataurl'] = 'Métadonnées du fournisseur de services';
$string['sp_slourl'] = 'URL de déconnexion unique';
$string['tab_attributes'] = 'Attributs';
$string['tab_details'] = 'Détails';
$string['tab_mappings'] = 'Utilisateurs appariés';
$string['tenant_all'] = 'Tous les locataires';
$string['test_attributes_info'] = 'Reçus lors d’une connexion de test il y a {$a}.';
$string['test_login'] = 'Connexion de test';
$string['test_login_attributes'] = 'Attributs reçus';
$string['test_login_nouser'] = 'Aucun utilisateur n’est encore apparié à ce compte du fournisseur d’identité.';
$string['test_login_success'] = 'La réponse du fournisseur d’identité a été acceptée. Il s’agissait d’un test, personne n’a été connecté.';
$string['test_login_user'] = 'Utilisateur apparié';
$string['user_mapping_allowotherauth'] = 'Autre authentification autorisée';
$string['user_mapping_allowotherauth_help'] = 'Permet à un utilisateur ayant une autre méthode d’authentification, par exemple manuelle, de se connecter aussi par l’intermédiaire du fournisseur d’identité.';
$string['user_mapping_automapped'] = 'Apparié automatiquement';
$string['user_mapping_create'] = 'Ajouter un appariement d’utilisateur';
$string['user_mapping_delete'] = 'Supprimer l’appariement d’utilisateur';
$string['user_mapping_delete_info'] = 'L’appariement entre l’utilisateur et le compte du fournisseur d’identité est supprimé. Le compte utilisateur est conservé.';
$string['user_mapping_guid'] = 'Identifiant de compte du fournisseur d’identité';
$string['user_mapping_guid_help'] = 'Valeur de l’attribut d’identifiant utilisateur, exactement telle que le fournisseur d’identité l’envoie.';
$string['user_mapping_setauth'] = 'Passer le compte utilisateur à l’authentification SAML';
$string['user_mapping_setauth_help'] = 'Passe le compte utilisateur à l’authentification SAML. Laissez décoché pour conserver la méthode actuelle et autorisez plutôt une autre authentification.';
$string['user_mapping_timecreated'] = 'Date d’appariement';
$string['user_mapping_update'] = 'Modifier l’appariement d’utilisateur';
$string['user_mapping_user'] = 'Utilisateur';
$string['user_mapping_user_help'] = 'Les utilisateurs déjà appariés à un fournisseur d’identité ne sont pas listés.';
$string['user_mappings'] = 'Utilisateurs appariés';
$string['user_mappings_none'] = 'Aucun utilisateur n’est encore apparié.';
