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
 * Strings for component 'auth_musaml', language 'it'.
 *
 * NOTE: This Italian translation was produced automatically by an AI language
 * model and has not yet been reviewed by a human translator. Corrections are
 * welcome.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['attribute_advertised'] = 'Attributi offerti dai metadati del provider di identità: {$a}';
$string['attribute_create'] = 'Aggiungi attributo';
$string['attribute_delete'] = 'Elimina attributo';
$string['attribute_delete_info'] = 'La mappatura viene rimossa, i valori già copiati nei profili utente vengono mantenuti.';
$string['attribute_idpattr'] = 'Attributo IdP';
$string['attribute_idpattr_help'] = 'Nome dell\'attributo così come viene inviato dal provider di identità, con distinzione tra maiuscole e minuscole. Il nome speciale "nameid" prende il valore dal NameID SAML. Un login di prova elenca tutto ciò che viene ricevuto.';
$string['attribute_sync'] = 'Sincronizzazione';
$string['attribute_sync_help'] = 'Mai: usato solo per la mappatura automatica. Alla creazione dell\'account: copiato una sola volta. A ogni login: mantenuto allineato al provider di identità, di solito con il campo bloccato.';
$string['attribute_sync_none'] = 'Mai';
$string['attribute_sync_oncreate'] = 'Alla creazione dell\'account';
$string['attribute_sync_onlogin'] = 'A ogni login';
$string['attribute_update'] = 'Modifica attributo';
$string['attribute_userfield'] = 'Campo utente';
$string['attribute_userfield_help'] = 'Campo utente di Moodle che riceve il valore. Lo username viene impostato solo alla creazione dell\'account utente.';
$string['attribute_usermapping'] = 'Usato per la mappatura automatica';
$string['attribute_usermapping_help'] = 'Il valore deve corrispondere esattamente a un solo utente.';
$string['attribute_value'] = 'Valore';
$string['attributes_none'] = 'Nessun attributo è ancora mappato.';
$string['auth_musamldescription'] = 'Gli utenti effettuano il login tramite provider di identità SAML 2.0 esterni.';
$string['check_certificates'] = 'Certificati SAML';
$string['check_certificates_allok'] = 'Tutti i certificati sono validi. Numero di certificati: {$a}.';
$string['check_certificates_disabled'] = 'L\'autenticazione SAML è disabilitata.';
$string['check_certificates_expired'] = 'Il certificato di {$a->name} è scaduto da {$a->time}.';
$string['check_certificates_none'] = 'Non sono stati trovati certificati con una data di scadenza.';
$string['check_certificates_ok'] = 'Il certificato di {$a->name} scade tra {$a->time}.';
$string['check_certificates_soon'] = 'Il certificato di {$a->name} scade tra {$a->time}.';
$string['error_certificate'] = 'Il testo non contiene certificati validi.';
$string['error_createemail'] = 'Non è possibile creare un nuovo account utente, l\'indirizzo email "{$a}" non è utilizzabile.';
$string['error_createmissing'] = 'Non è possibile creare un nuovo account utente, il provider di identità non ha inviato il campo "{$a}".';
$string['error_createusername'] = 'Non è possibile creare un nuovo account utente, lo username "{$a}" non è utilizzabile.';
$string['error_customsettings_json'] = 'Le impostazioni personalizzate non sono un JSON valido: {$a}';
$string['error_customsettings_object'] = 'Le impostazioni personalizzate devono essere un oggetto JSON con i nomi delle impostazioni di php-saml come chiavi.';
$string['error_entityidexists'] = 'Esiste già un provider di identità con entity ID "{$a}".';
$string['error_entityidtoolong'] = 'L\'entity ID supera i 255 caratteri, questo provider di identità non può essere usato.';
$string['error_guidmapped'] = 'Questo account del provider di identità è già mappato a un altro utente.';
$string['error_idpunavailable'] = 'Questo provider di identità non è disponibile.';
$string['error_logout'] = 'Il messaggio di logout del provider di identità è stato rifiutato: {$a}';
$string['error_metadatablocked'] = 'L\'URL dei metadati {$a} è bloccato dalle impostazioni di sicurezza cURL del sito, vedere "Sicurezza HTTP" nell\'amministrazione del sito.';
$string['error_metadatadownload'] = 'Scaricamento dei metadati non riuscito: {$a}';
$string['error_metadatanourl'] = 'I metadati incollati non possono essere aggiornati. Incollare i nuovi metadati nelle impostazioni del provider di identità.';
$string['error_metadataparse'] = 'Non è possibile analizzare i metadati: {$a}';
$string['error_metadataurl'] = 'L\'URL dei metadati deve iniziare con http:// oppure https://';
$string['error_noguid'] = 'Il provider di identità non ha inviato esattamente un valore dell\'attributo ID utente "{$a}".';
$string['error_nohttps'] = 'Questo sito non usa https. I login SAML funzionano solo con un provider di identità su questo stesso sito.';
$string['error_nomapping'] = 'Nessun utente è mappato all\'account del provider di identità "{$a}". Contattare l\'amministratore del sito.';
$string['error_norequest'] = 'Non è stata trovata alcuna richiesta di login in sospeso per questa risposta, è necessario ricominciare il login.';
$string['error_otherauth'] = 'L\'account usa il metodo di autenticazione "{$a}" e non può essere usato con questo provider di identità.';
$string['error_replay'] = 'Questa risposta del provider di identità è già stata usata, è necessario effettuare di nuovo il login.';
$string['error_response'] = 'La risposta del provider di identità è stata rifiutata: {$a}';
$string['error_spcertinuse'] = 'Il certificato del provider di servizi non può essere eliminato finché sono configurati provider di identità.';
$string['error_spmetadata'] = 'I metadati del provider di servizi non sono validi: {$a}';
$string['error_spnotconfigured'] = 'Il certificato del provider di servizi non è ancora stato creato.';
$string['error_suspended'] = 'L\'account è sospeso.';
$string['error_userfieldmapped'] = 'Questo campo utente è già mappato per questo provider di identità.';
$string['error_usermapped'] = 'L\'utente è già mappato a un provider di identità.';
$string['error_usernameprefix'] = 'Il prefisso può contenere solo lettere minuscole, cifre, punti, trattini e trattini bassi, al massimo 50 caratteri.';
$string['error_usernamesync'] = 'Lo username può essere copiato solo alla creazione dell\'account utente.';
$string['event_idp_created'] = 'Provider di identità creato';
$string['event_idp_deleted'] = 'Provider di identità eliminato';
$string['event_idp_updated'] = 'Provider di identità aggiornato';
$string['event_login_failed'] = 'Login SAML non riuscito';
$string['event_metadata_refresh_failed'] = 'Aggiornamento dei metadati del provider di identità non riuscito';
$string['event_user_mapping_created'] = 'Mappatura utente creata';
$string['event_user_mapping_deleted'] = 'Mappatura utente eliminata';
$string['idp_advertisedattributes'] = 'Attributi nei metadati';
$string['idp_attrsimple'] = 'Nomi di attributo semplici';
$string['idp_attrsimple_help'] = 'Accetta anche l\'ultima parte dei nomi di attributo in formato URI, ad esempio emailaddress per http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress.';
$string['idp_autocreate'] = 'Crea automaticamente gli account utente';
$string['idp_autocreate_help'] = 'Al primo login vengono creati nuovi account utente per gli account del provider di identità sconosciuti. Le mappature degli attributi devono fornire almeno username ed email.';
$string['idp_autologin'] = 'Login automatico';
$string['idp_autologin_help'] = 'Quando questo è l\'unico provider di identità disponibile nella pagina di login, i visitatori vengono inviati subito al provider invece di vedere il form di login.';
$string['idp_autologin_noslo'] = '{$a} non supporta il single logout, quindi dopo il logout da questo sito gli utenti restano autenticati presso il provider di identità. La persona successiva che usa lo stesso browser viene autenticata al loro posto. Non usare il login automatico su computer condivisi.';
$string['idp_automap'] = 'Mappa automaticamente gli utenti esistenti';
$string['idp_automap_help'] = 'Al primo login mappa un utente con autenticazione SAML e senza mappatura, quando ogni attributo contrassegnato per la mappatura automatica corrisponde al suo profilo.';
$string['idp_autorefresh'] = 'Aggiorna automaticamente i metadati';
$string['idp_autorefresh_help'] = 'I metadati vengono scaricati di nuovo una volta al giorno dopo un login riuscito, e immediatamente quando una risposta non può essere verificata. Disattivare per mantenere i certificati memorizzati.';
$string['idp_certificate_manual'] = 'Aggiunto a mano';
$string['idp_certificate_metadata'] = 'Dai metadati';
$string['idp_certificate_source'] = 'Origine';
$string['idp_certificates'] = 'Certificati di firma';
$string['idp_certificates_edit'] = 'Impostazioni dei certificati';
$string['idp_create'] = 'Aggiungi provider di identità';
$string['idp_created'] = 'Il provider di identità è stato aggiunto';
$string['idp_customsettings'] = 'Impostazioni personalizzate della libreria';
$string['idp_customsettings_help'] = 'Oggetto JSON unito alle impostazioni di php-saml, sulle quali ha la precedenza, ad esempio {"security": {"wantAssertionsSigned": false}}. Le sovrascritture non sicure sono consentite ma generano un avviso. Usare solo quando nient\'altro funziona.';
$string['idp_delete'] = 'Elimina provider di identità';
$string['idp_delete_info'] = 'Verranno eliminati il provider di identità, le sue mappature degli attributi e le sue mappature utente (numero: {$a}). Gli account utente vengono mantenuti.';
$string['idp_deleted'] = 'Il provider di identità è stato eliminato';
$string['idp_enabled'] = 'Abilitato';
$string['idp_entityid'] = 'Entity ID';
$string['idp_extracerts'] = 'Certificati di firma aggiuntivi';
$string['idp_extracerts_help'] = 'Certificati accettati oltre a quelli dei metadati, in formato PEM uno dopo l\'altro. Da usare durante la rotazione delle chiavi, quando il provider di identità non ha ancora pubblicato il nuovo certificato.';
$string['idp_logouturl'] = 'URL di single logout';
$string['idp_mapattr'] = 'Attributo ID utente';
$string['idp_mapattr_help'] = 'Attributo che contiene l\'identificativo permanente dell\'utente nel provider di identità. Per un utente non deve mai cambiare e non deve mai essere riutilizzato. Il nome speciale "nameid" prende il valore dal NameID SAML.';
$string['idp_metadata_error'] = 'Ultimo aggiornamento non riuscito: {$a}';
$string['idp_metadata_fetched'] = 'Metadati scaricati';
$string['idp_metadata_never'] = 'Mai';
$string['idp_metadata_refresh'] = 'Aggiorna metadati';
$string['idp_metadata_refresh_info'] = 'I metadati vengono scaricati di nuovo dall\'URL dei metadati. Entity ID, endpoint e certificati di firma vengono sostituiti dai valori correnti.';
$string['idp_metadata_refreshed'] = 'I metadati sono stati aggiornati';
$string['idp_metadata_validuntil'] = 'Metadati validi fino al';
$string['idp_metadatapasted'] = 'XML incollato, non aggiornato automaticamente';
$string['idp_metadatasource'] = 'URL o XML dei metadati';
$string['idp_metadatasource_help'] = 'URL dei metadati SAML 2.0 del provider di identità, oppure l\'XML stesso dei metadati. Un URL viene aggiornato automaticamente, l\'XML incollato deve essere sostituito a mano quando cambiano i certificati.';
$string['idp_metadataurl'] = 'URL dei metadati';
$string['idp_metadataurl_help'] = 'URL pubblico dei metadati SAML 2.0 del provider di identità. Entity ID, URL di single sign-on e certificati di firma vengono letti da qui e aggiornati automaticamente.';
$string['idp_name'] = 'Nome';
$string['idp_name_help'] = 'Visualizzato sul pulsante della pagina di login.';
$string['idp_nameidformat'] = 'Formato NameID';
$string['idp_provider'] = 'Provider';
$string['idp_provider_help'] = 'Precompila gli attributi e adatta le impostazioni della libreria alle particolarità note dei prodotti. Per tutti gli altri casi usare SAML 2.0 generico.';
$string['idp_settings_relaxed'] = 'Meno restrittive dei default del plugin, impostate dal provider o dalle impostazioni personalizzate della libreria: {$a}';
$string['idp_ssourl'] = 'URL di single sign-on';
$string['idp_tenant'] = 'Tenant';
$string['idp_tenant_help'] = 'Offerto solo nella pagina di login di questo tenant. Senza tenant viene offerto ovunque.';
$string['idp_update'] = 'Modifica provider di identità';
$string['idp_updated'] = 'Il provider di identità è stato aggiornato';
$string['idp_usernameprefix'] = 'Prefisso dello username';
$string['idp_usernameprefix_help'] = 'Viene aggiunto agli username degli account utente creati e usato nel confronto degli username. Mantiene gli username univoci tra i provider di identità.';
$string['idps'] = 'Provider di identità';
$string['idps_none'] = 'Non è ancora stato aggiunto alcun provider di identità.';
$string['idps_none_info'] = 'Premere Aggiungi provider di identità e incollare l\'URL dei suoi metadati.';
$string['idps_none_info2'] = 'Inviare al provider di identità i metadati del provider di servizi riportati sopra. I due passaggi possono essere eseguiti in qualsiasi ordine.';
$string['import'] = 'Importa mappature utente';
$string['import_column_ignore'] = 'Non utilizzata';
$string['import_column_number'] = 'Colonna {$a}';
$string['import_columns'] = 'Significato delle colonne';
$string['import_columns_headers'] = 'La prima riga contiene i nomi delle colonne';
$string['import_columns_info'] = 'Indicare il contenuto di ciascuna colonna. Righe lette: {$a}.';
$string['import_confirm'] = 'Importa mappature';
$string['import_counts'] = 'Pronte per l\'importazione: {$a->created}. Saltate: {$a->skipped}. Problemi: {$a->error}.';
$string['import_done'] = 'Mappature utente importate: {$a->created}, saltate: {$a->skipped}.';
$string['import_error_ambiguous'] = 'Più di un utente corrisponde a "{$a}".';
$string['import_error_columntwice'] = 'Ogni significato di colonna può essere utilizzato una sola volta.';
$string['import_error_columnunknown'] = 'Questo significato di colonna è sconosciuto.';
$string['import_error_empty'] = 'Non sono state trovate righe utilizzabili.';
$string['import_error_guidused'] = 'L\'account del provider di identità "{$a}" è già mappato a un altro utente.';
$string['import_error_headers'] = 'Indicare se la prima riga contiene i nomi delle colonne.';
$string['import_error_invalid'] = 'La riga non contiene un ID dell\'account del provider di identità e un identificativo dell\'utente.';
$string['import_error_mapped'] = 'L\'utente "{$a}" è già mappato a un altro account del provider di identità.';
$string['import_error_mappedsame'] = 'L\'utente "{$a}" ha già questa mappatura.';
$string['import_error_missing'] = 'Nessun utente corrisponde a "{$a}".';
$string['import_error_nodatarows'] = 'Oltre ai nomi delle colonne non sono presenti dati.';
$string['import_error_noguid'] = 'Una colonna deve contenere l\'ID dell\'account del provider di identità.';
$string['import_error_nouser'] = 'Una colonna deve identificare l\'utente.';
$string['import_error_otherauth'] = 'L\'account utente usa il metodo di autenticazione "{$a}", consentire un\'altra autenticazione oppure cambiare il metodo dell\'account.';
$string['import_error_rowsize'] = 'Tutte le righe devono contenere lo stesso numero di colonne.';
$string['import_error_suspended'] = 'L\'utente "{$a}" è sospeso.';
$string['import_failed'] = 'Non è stato importato nulla, righe con problemi: {$a->error}.';
$string['import_more_rows'] = 'Altre righe non visualizzate: {$a}.';
$string['import_options'] = 'Opzioni di importazione';
$string['import_preview'] = 'Verifica i dati';
$string['import_refresh'] = 'Aggiorna anteprima';
$string['import_result_created'] = 'Sarà importata';
$string['import_result_error'] = 'Problema';
$string['import_result_skipped'] = 'Saltata';
$string['import_skip_ambiguous'] = 'Salta le righe che corrispondono a più di un utente';
$string['import_skip_guidused'] = 'Salta le righe il cui account del provider di identità è già mappato';
$string['import_skip_info'] = 'Le righe con un problema bloccano l\'importazione, a meno che non vengano saltate qui. Nulla viene scritto finché i dati non sono stati verificati.';
$string['import_skip_invalid'] = 'Salta le righe con valori mancanti';
$string['import_skip_mapped'] = 'Salta gli utenti già mappati';
$string['import_skip_missing'] = 'Salta le righe senza un utente corrispondente';
$string['import_skip_unusable'] = 'Salta gli utenti sospesi e gli utenti che non possono usare questo provider di identità';
$string['import_source_info'] = 'Caricare un file CSV oppure incollare i dati.';
$string['musaml:managemappings'] = 'Gestire le mappature degli utenti agli account dei provider di identità';
$string['pluginname'] = 'Autenticazione SAML';
$string['privacy:metadata:auth_musaml_login'] = 'Login in corso, eliminati al termine del login e al più tardi dopo alcuni minuti';
$string['privacy:metadata:auth_musaml_login:idpid'] = 'Provider di identità';
$string['privacy:metadata:auth_musaml_login:resultjson'] = 'Attributi ricevuti dal provider di identità';
$string['privacy:metadata:auth_musaml_login:timecreated'] = 'Data di inizio del login';
$string['privacy:metadata:auth_musaml_login:userid'] = 'Utente a cui è stato ricondotto il login';
$string['privacy:metadata:auth_musaml_user'] = 'Mappature degli utenti agli account dei provider di identità';
$string['privacy:metadata:auth_musaml_user:allowotherauth'] = 'Indica se l\'utente può accedere anche con un altro metodo di autenticazione';
$string['privacy:metadata:auth_musaml_user:automapped'] = 'Indica se la mappatura è stata creata automaticamente al primo accesso';
$string['privacy:metadata:auth_musaml_user:guid'] = 'Identificativo univoco dell\'utente nel provider di identità';
$string['privacy:metadata:auth_musaml_user:idpid'] = 'Provider di identità';
$string['privacy:metadata:auth_musaml_user:timecreated'] = 'Data di creazione della mappatura';
$string['privacy:metadata:auth_musaml_user:userid'] = 'Utente Moodle';
$string['privacy:metadata:authsubsystem'] = 'Il plugin autentica gli utenti tramite il sottosistema di autenticazione.';
$string['provider_adfs'] = 'Active Directory Federation Services';
$string['provider_auto'] = 'Rileva dai metadati';
$string['provider_entra'] = 'Microsoft Entra ID';
$string['provider_generic'] = 'SAML 2.0 generico';
$string['provider_google'] = 'Google Workspace';
$string['provider_keycloak'] = 'Keycloak';
$string['provider_okta'] = 'Okta';
$string['provider_zitadel'] = 'Zitadel';
$string['setting_debug'] = 'Log di debug';
$string['setting_debug_desc'] = 'Scrive i dettagli delle richieste e delle risposte SAML nel log degli errori di PHP. Non abilitare nei siti di produzione.';
$string['setting_fieldlocks_desc'] = 'I campi bloccati non possono essere modificati dagli utenti. I blocchi si applicano a tutti gli utenti con autenticazione SAML, mappati o meno.';
$string['sp'] = 'Provider di servizi';
$string['sp_acsurl'] = 'URL dell\'Assertion Consumer Service';
$string['sp_cert'] = 'Certificato';
$string['sp_cert_commonname'] = 'Nome comune';
$string['sp_cert_create'] = 'Genera certificato';
$string['sp_cert_days'] = 'Validità in giorni';
$string['sp_cert_delete'] = 'Elimina certificato';
$string['sp_cert_delete_info'] = 'Vengono eliminati il certificato del provider di servizi, la sua chiave privata e l\'entity ID.';
$string['sp_cert_download'] = 'Scarica certificato';
$string['sp_cert_fingerprint'] = 'Impronta digitale del certificato (SHA-256)';
$string['sp_cert_missing'] = 'Generare prima il certificato del provider di servizi, i provider di identità ne hanno bisogno durante la configurazione.';
$string['sp_cert_notafter'] = 'Scadenza del certificato';
$string['sp_cert_organizationname'] = 'Organizzazione';
$string['sp_cert_regen'] = 'Rigenera certificato';
$string['sp_cert_regen_warning'] = 'Il certificato e la chiave privata vengono sostituiti immediatamente. I login non riescono finché ogni provider di identità non dispone dei nuovi metadati del provider di servizi.';
$string['sp_cert_subject'] = 'Soggetto del certificato';
$string['sp_entityid'] = 'Entity ID';
$string['sp_entityid_help'] = 'Identifica questo sito presso i provider di identità, per default è l\'URL dei metadati. Può essere cambiato solo insieme al certificato, entrambi devono poi essere registrati di nuovo in ogni provider di identità.';
$string['sp_metadataurl'] = 'Metadati del provider di servizi';
$string['sp_slourl'] = 'URL di single logout';
$string['tab_attributes'] = 'Attributi';
$string['tab_details'] = 'Dettagli';
$string['tab_mappings'] = 'Utenti mappati';
$string['tenant_all'] = 'Tutti i tenant';
$string['test_attributes_info'] = 'Ricevuti durante un login di prova {$a} fa.';
$string['test_login'] = 'Login di prova';
$string['test_login_attributes'] = 'Attributi ricevuti';
$string['test_login_nouser'] = 'Nessun utente è ancora mappato a questo account del provider di identità.';
$string['test_login_success'] = 'La risposta del provider di identità è stata accettata. Si trattava di una prova, nessuno è stato autenticato.';
$string['test_login_user'] = 'Utente mappato';
$string['user_mapping_allowotherauth'] = 'Altra autenticazione consentita';
$string['user_mapping_allowotherauth_help'] = 'Consente anche a un utente con un altro metodo di autenticazione, ad esempio manuale, di effettuare il login tramite il provider di identità.';
$string['user_mapping_automapped'] = 'Mappato automaticamente';
$string['user_mapping_create'] = 'Aggiungi mappatura utente';
$string['user_mapping_delete'] = 'Elimina mappatura utente';
$string['user_mapping_delete_info'] = 'La mappatura tra l\'utente e l\'account del provider di identità viene rimossa. L\'account utente viene mantenuto.';
$string['user_mapping_guid'] = 'ID dell\'account del provider di identità';
$string['user_mapping_guid_help'] = 'Valore dell\'attributo ID utente esattamente come viene inviato dal provider di identità.';
$string['user_mapping_setauth'] = 'Passa l\'account utente all\'autenticazione SAML';
$string['user_mapping_setauth_help'] = 'Passa l\'account utente all\'autenticazione SAML. Lasciare deselezionato per mantenere il metodo corrente e consentire invece un\'altra autenticazione.';
$string['user_mapping_timecreated'] = 'Data di mappatura';
$string['user_mapping_update'] = 'Modifica mappatura utente';
$string['user_mapping_user'] = 'Utente';
$string['user_mapping_user_help'] = 'Gli utenti già mappati a un provider di identità non sono elencati.';
$string['user_mappings'] = 'Utenti mappati';
$string['user_mappings_none'] = 'Nessun utente è ancora mappato.';
