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
 * Strings for component 'auth_musaml', language 'de'.
 *
 * NOTE: This German translation was produced automatically by an AI language
 * model and has not yet been reviewed by a human translator. Corrections are
 * welcome.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['attribute_advertised'] = 'In den Metadaten des Identitätsanbieters angebotene Attribute: {$a}';
$string['attribute_create'] = 'Attribut hinzufügen';
$string['attribute_delete'] = 'Attribut löschen';
$string['attribute_delete_info'] = 'Die Zuordnung wird entfernt, die bereits in Nutzerprofile kopierten Werte bleiben erhalten.';
$string['attribute_idpattr'] = 'IdP-Attribut';
$string['attribute_idpattr_help'] = 'Name des Attributs, wie es der Identitätsanbieter sendet, mit Beachtung der Groß- und Kleinschreibung. Der spezielle Name „nameid“ übernimmt den Wert aus der SAML-NameID. Eine Testanmeldung listet alles auf, was ankommt.';
$string['attribute_sync'] = 'Synchronisierung';
$string['attribute_sync_help'] = 'Nie: wird nur für die automatische Zuordnung verwendet. Beim Anlegen des Kontos: wird einmal kopiert. Bei jeder Anmeldung: wird mit dem Identitätsanbieter abgeglichen, meist bei gesperrtem Feld.';
$string['attribute_sync_none'] = 'Nie';
$string['attribute_sync_oncreate'] = 'Beim Anlegen des Kontos';
$string['attribute_sync_onlogin'] = 'Bei jeder Anmeldung';
$string['attribute_update'] = 'Attribut bearbeiten';
$string['attribute_userfield'] = 'Nutzerfeld';
$string['attribute_userfield_help'] = 'Moodle-Nutzerfeld, das den Wert erhält. Der Anmeldename wird nur beim Anlegen des Nutzerkontos gesetzt.';
$string['attribute_usermapping'] = 'Für die automatische Zuordnung verwendet';
$string['attribute_usermapping_help'] = 'Der Wert muss genau zu einer/m Nutzer/in passen.';
$string['attribute_value'] = 'Wert';
$string['attributes_none'] = 'Es sind noch keine Attribute zugeordnet.';
$string['auth_musamldescription'] = 'Nutzer/innen melden sich über externe SAML-2.0-Identitätsanbieter an.';
$string['check_certificates'] = 'SAML-Zertifikate';
$string['check_certificates_allok'] = 'Alle Zertifikate sind gültig (Anzahl: {$a}).';
$string['check_certificates_disabled'] = 'Die SAML-Authentifizierung ist deaktiviert.';
$string['check_certificates_expired'] = 'Das Zertifikat von {$a->name} ist vor {$a->time} abgelaufen.';
$string['check_certificates_none'] = 'Es wurden keine Zertifikate mit Ablaufdatum gefunden.';
$string['check_certificates_ok'] = 'Das Zertifikat von {$a->name} läuft in {$a->time} ab.';
$string['check_certificates_soon'] = 'Das Zertifikat von {$a->name} läuft bald ab, in {$a->time}.';
$string['error_certificate'] = 'Der Text enthält keine gültigen Zertifikate.';
$string['error_createemail'] = 'Ein neues Nutzerkonto kann nicht angelegt werden, die E-Mail-Adresse „{$a}“ ist nicht verwendbar.';
$string['error_createmissing'] = 'Ein neues Nutzerkonto kann nicht angelegt werden, der Identitätsanbieter hat das Feld „{$a}“ nicht gesendet.';
$string['error_createusername'] = 'Ein neues Nutzerkonto kann nicht angelegt werden, der Anmeldename „{$a}“ ist nicht verwendbar.';
$string['error_customsettings_json'] = 'Die nutzerdefinierten Einstellungen sind kein gültiges JSON: {$a}';
$string['error_customsettings_object'] = 'Die nutzerdefinierten Einstellungen müssen ein JSON-Objekt mit Namen von php-saml-Einstellungen als Schlüsseln sein.';
$string['error_entityidexists'] = 'Ein Identitätsanbieter mit der Entity-ID „{$a}“ existiert bereits.';
$string['error_entityidtoolong'] = 'Die Entity-ID ist länger als 255 Zeichen, dieser Identitätsanbieter kann nicht verwendet werden.';
$string['error_guidmapped'] = 'Dieses Konto beim Identitätsanbieter ist bereits einer/m anderen Nutzer/in zugeordnet.';
$string['error_idpunavailable'] = 'Dieser Identitätsanbieter ist nicht verfügbar.';
$string['error_logout'] = 'Die Abmeldenachricht des Identitätsanbieters wurde abgelehnt: {$a}';
$string['error_metadatablocked'] = 'Die Metadaten-URL {$a} wird durch die cURL-Sicherheitseinstellungen der Website blockiert, siehe „HTTP-Sicherheit“ in der Website-Administration.';
$string['error_metadatadownload'] = 'Das Herunterladen der Metadaten ist fehlgeschlagen: {$a}';
$string['error_metadatanourl'] = 'Eingefügte Metadaten können nicht aktualisiert werden. Fügen Sie neue Metadaten in den Einstellungen des Identitätsanbieters ein.';
$string['error_metadataparse'] = 'Die Metadaten können nicht verarbeitet werden: {$a}';
$string['error_metadataurl'] = 'Die Metadaten-URL muss mit http:// oder https:// beginnen';
$string['error_noguid'] = 'Der Identitätsanbieter hat nicht genau einen Wert des Nutzer-ID-Attributs „{$a}“ gesendet.';
$string['error_nohttps'] = 'Diese Website verwendet kein https. SAML-Anmeldungen funktionieren nur mit einem Identitätsanbieter auf derselben Website.';
$string['error_nomapping'] = 'Dem Konto „{$a}“ beim Identitätsanbieter ist keine/r Nutzer/in zugeordnet. Wenden Sie sich an die Administrator/innen der Website.';
$string['error_norequest'] = 'Für diese Antwort wurde keine offene Anmeldeanfrage gefunden. Starten Sie die Anmeldung bitte erneut.';
$string['error_otherauth'] = 'Ihr Konto verwendet die Authentifizierungsmethode „{$a}“ und kann nicht mit diesem Identitätsanbieter verwendet werden.';
$string['error_replay'] = 'Diese Antwort des Identitätsanbieters wurde bereits verwendet. Melden Sie sich bitte erneut an.';
$string['error_response'] = 'Die Antwort des Identitätsanbieters wurde abgelehnt: {$a}';
$string['error_spcertinuse'] = 'Das Zertifikat des Dienstanbieters kann nicht gelöscht werden, solange Identitätsanbieter konfiguriert sind.';
$string['error_spmetadata'] = 'Die Metadaten des Dienstanbieters sind ungültig: {$a}';
$string['error_spnotconfigured'] = 'Das Zertifikat des Dienstanbieters wurde noch nicht erstellt.';
$string['error_suspended'] = 'Ihr Konto ist gesperrt.';
$string['error_userfieldmapped'] = 'Dieses Nutzerfeld ist für diesen Identitätsanbieter bereits zugeordnet.';
$string['error_usermapped'] = 'Die/Der Nutzer/in ist bereits einem Identitätsanbieter zugeordnet.';
$string['error_usernameprefix'] = 'Das Präfix darf nur Kleinbuchstaben, Ziffern, Punkte, Bindestriche und Unterstriche enthalten, höchstens 50 Zeichen.';
$string['error_usernamesync'] = 'Der Anmeldename kann nur beim Anlegen des Nutzerkontos kopiert werden.';
$string['event_idp_created'] = 'Identitätsanbieter angelegt';
$string['event_idp_deleted'] = 'Identitätsanbieter gelöscht';
$string['event_idp_updated'] = 'Identitätsanbieter aktualisiert';
$string['event_login_failed'] = 'SAML-Anmeldung fehlgeschlagen';
$string['event_metadata_refresh_failed'] = 'Aktualisierung der Metadaten des Identitätsanbieters fehlgeschlagen';
$string['event_user_mapping_created'] = 'Nutzerzuordnung angelegt';
$string['event_user_mapping_deleted'] = 'Nutzerzuordnung gelöscht';
$string['idp_advertisedattributes'] = 'Attribute in den Metadaten';
$string['idp_attrsimple'] = 'Einfache Attributnamen';
$string['idp_attrsimple_help'] = 'Auch den letzten Teil von URI-Attributnamen akzeptieren, zum Beispiel emailaddress für http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress.';
$string['idp_autocreate'] = 'Nutzerkonten automatisch anlegen';
$string['idp_autocreate_help'] = 'Für unbekannte Konten beim Identitätsanbieter werden bei der ersten Anmeldung neue Nutzerkonten angelegt. Die Attributzuordnungen müssen mindestens Anmeldename und E-Mail-Adresse liefern.';
$string['idp_autologin'] = 'Automatische Anmeldung';
$string['idp_autologin_help'] = 'Wenn dies der einzige auf der Anmeldeseite verfügbare Identitätsanbieter ist, werden Besucher/innen sofort zu ihm weitergeleitet, statt das Anmeldeformular zu sehen.';
$string['idp_autologin_noslo'] = '{$a} unterstützt kein Single Logout, daher bleiben Nutzer/innen nach der Abmeldung hier beim Identitätsanbieter angemeldet. Die nächste Person, die denselben Browser verwendet, wird als diese/r Nutzer/in angemeldet. Verwenden Sie die automatische Anmeldung nicht auf gemeinsam genutzten Computern.';
$string['idp_automap'] = 'Vorhandene Nutzer/innen automatisch zuordnen';
$string['idp_automap_help'] = 'Ordnet bei der ersten Anmeldung eine/n Nutzer/in mit SAML-Authentifizierung und ohne Zuordnung zu, wenn jedes für die automatische Zuordnung markierte Attribut mit dem Profil übereinstimmt.';
$string['idp_autorefresh'] = 'Metadaten automatisch aktualisieren';
$string['idp_autorefresh_help'] = 'Die Metadaten werden einmal täglich nach einer erfolgreichen Anmeldung erneut heruntergeladen, und sofort, wenn eine Antwort nicht verifiziert werden kann. Deaktivieren Sie dies, um die gespeicherten Zertifikate beizubehalten.';
$string['idp_certificate_manual'] = 'Manuell hinzugefügt';
$string['idp_certificate_metadata'] = 'Aus den Metadaten';
$string['idp_certificate_source'] = 'Quelle';
$string['idp_certificates'] = 'Signaturzertifikate';
$string['idp_certificates_edit'] = 'Zertifikatseinstellungen';
$string['idp_create'] = 'Identitätsanbieter hinzufügen';
$string['idp_created'] = 'Der Identitätsanbieter wurde hinzugefügt';
$string['idp_customsettings'] = 'Nutzerdefinierte Bibliothekseinstellungen';
$string['idp_customsettings_help'] = 'JSON-Objekt, das über die php-saml-Einstellungen gelegt wird, zum Beispiel {"security": {"wantAssertionsSigned": false}}. Unsichere Überschreibungen sind erlaubt, zeigen aber eine Warnung an. Verwenden Sie dies nur, wenn nichts anderes funktioniert.';
$string['idp_delete'] = 'Identitätsanbieter löschen';
$string['idp_delete_info'] = 'Der Identitätsanbieter, seine Attributzuordnungen und seine Nutzerzuordnungen (Anzahl: {$a}) werden gelöscht. Die Nutzerkonten bleiben erhalten.';
$string['idp_deleted'] = 'Der Identitätsanbieter wurde gelöscht';
$string['idp_enabled'] = 'Aktiviert';
$string['idp_entityid'] = 'Entity-ID';
$string['idp_extracerts'] = 'Zusätzliche Signaturzertifikate';
$string['idp_extracerts_help'] = 'Zertifikate, die neben denen aus den Metadaten akzeptiert werden, im PEM-Format nacheinander. Verwenden Sie dies während eines Schlüsselwechsels, wenn der Identitätsanbieter das neue Zertifikat noch nicht veröffentlicht hat.';
$string['idp_logouturl'] = 'Single-Logout-URL';
$string['idp_mapattr'] = 'Nutzer-ID-Attribut';
$string['idp_mapattr_help'] = 'Attribut, das die dauerhafte Nutzerkennung beim Identitätsanbieter enthält. Es darf sich für eine/n Nutzer/in nie ändern und nie wiederverwendet werden. Der spezielle Name „nameid“ übernimmt den Wert aus der SAML-NameID.';
$string['idp_metadata_error'] = 'Letzte Aktualisierung fehlgeschlagen: {$a}';
$string['idp_metadata_fetched'] = 'Metadaten abgerufen';
$string['idp_metadata_never'] = 'Nie';
$string['idp_metadata_refresh'] = 'Metadaten aktualisieren';
$string['idp_metadata_refresh_info'] = 'Die Metadaten werden erneut von der Metadaten-URL heruntergeladen. Entity-ID, Endpunkte und Signaturzertifikate werden durch die aktuellen Werte ersetzt.';
$string['idp_metadata_refreshed'] = 'Die Metadaten wurden aktualisiert';
$string['idp_metadata_validuntil'] = 'Metadaten gültig bis';
$string['idp_metadatapasted'] = 'Eingefügtes XML, wird nicht automatisch aktualisiert';
$string['idp_metadatasource'] = 'Metadaten-URL oder XML';
$string['idp_metadatasource_help'] = 'URL der SAML-2.0-Metadaten des Identitätsanbieters oder das Metadaten-XML selbst. Eine URL wird automatisch aktualisiert, eingefügtes XML muss manuell ersetzt werden, wenn sich die Zertifikate ändern.';
$string['idp_metadataurl'] = 'Metadaten-URL';
$string['idp_metadataurl_help'] = 'Öffentliche URL der SAML-2.0-Metadaten des Identitätsanbieters. Entity-ID, Single-Sign-on-URL und Signaturzertifikate werden daraus gelesen und automatisch aktualisiert.';
$string['idp_name'] = 'Name';
$string['idp_name_help'] = 'Wird auf der Schaltfläche der Anmeldeseite angezeigt.';
$string['idp_nameidformat'] = 'NameID-Format';
$string['idp_provider'] = 'Anbieter';
$string['idp_provider_help'] = 'Füllt Attribute vor und passt die Bibliothekseinstellungen an bekannte Eigenheiten des Produkts an. Verwenden Sie für alles andere „Generisches SAML 2.0“.';
$string['idp_settings_relaxed'] = 'Weniger streng als die Standardwerte des Plugins, festgelegt durch den Anbieter oder die nutzerdefinierten Bibliothekseinstellungen: {$a}';
$string['idp_ssourl'] = 'Single-Sign-on-URL';
$string['idp_tenant'] = 'Mandant';
$string['idp_tenant_help'] = 'Wird nur auf der Anmeldeseite dieses Mandanten angeboten. Ohne Mandant wird er überall angeboten.';
$string['idp_update'] = 'Identitätsanbieter bearbeiten';
$string['idp_updated'] = 'Der Identitätsanbieter wurde aktualisiert';
$string['idp_usernameprefix'] = 'Präfix für Anmeldenamen';
$string['idp_usernameprefix_help'] = 'Wird den Anmeldenamen angelegter Nutzerkonten vorangestellt und beim Abgleich von Anmeldenamen verwendet. Hält die Anmeldenamen über alle Identitätsanbieter hinweg eindeutig.';
$string['idps'] = 'Identitätsanbieter';
$string['idps_none'] = 'Es wurden noch keine Identitätsanbieter hinzugefügt.';
$string['idps_none_info'] = 'Klicken Sie auf „Identitätsanbieter hinzufügen“ und fügen Sie dessen Metadaten-URL ein.';
$string['idps_none_info2'] = 'Senden Sie die obigen Metadaten des Dienstanbieters an den Identitätsanbieter. Die Reihenfolge der beiden Schritte ist beliebig.';
$string['import'] = 'Nutzerzuordnungen importieren';
$string['import_column_ignore'] = 'Nicht verwendet';
$string['import_column_number'] = 'Spalte {$a}';
$string['import_columns'] = 'Bedeutung der Spalten';
$string['import_columns_headers'] = 'Erste Zeile enthält Spaltennamen';
$string['import_columns_info'] = 'Geben Sie an, was jede Spalte enthält. Anzahl der gelesenen Zeilen: {$a}.';
$string['import_confirm'] = 'Zuordnungen importieren';
$string['import_counts'] = 'Bereit zum Import: {$a->created}. Übersprungen: {$a->skipped}. Probleme: {$a->error}.';
$string['import_done'] = 'Importierte Nutzerzuordnungen: {$a->created}, übersprungen: {$a->skipped}.';
$string['import_error_ambiguous'] = 'Mehr als ein/e Nutzer/in passt zu „{$a}“.';
$string['import_error_columntwice'] = 'Jede Spaltenbedeutung kann nur einmal verwendet werden.';
$string['import_error_columnunknown'] = 'Diese Spaltenbedeutung ist nicht bekannt.';
$string['import_error_empty'] = 'Es wurden keine verwendbaren Zeilen gefunden.';
$string['import_error_guidused'] = 'Das Konto „{$a}“ beim Identitätsanbieter ist bereits einer/m anderen Nutzer/in zugeordnet.';
$string['import_error_headers'] = 'Geben Sie an, ob die erste Zeile Spaltennamen enthält.';
$string['import_error_invalid'] = 'Die Zeile enthält keine Konto-ID beim Identitätsanbieter und keine Nutzerkennung.';
$string['import_error_mapped'] = 'Nutzer/in „{$a}“ ist bereits einem anderen Konto beim Identitätsanbieter zugeordnet.';
$string['import_error_mappedsame'] = 'Nutzer/in „{$a}“ hat diese Zuordnung bereits.';
$string['import_error_missing'] = 'Keine/r Nutzer/in passt zu „{$a}“.';
$string['import_error_nodatarows'] = 'Außer den Spaltennamen sind keine Daten vorhanden.';
$string['import_error_noguid'] = 'Eine Spalte muss die Konto-ID beim Identitätsanbieter enthalten.';
$string['import_error_nouser'] = 'Eine Spalte muss die/den Nutzer/in identifizieren.';
$string['import_error_otherauth'] = 'Das Nutzerkonto verwendet die Authentifizierungsmethode „{$a}“. Erlauben Sie eine andere Authentifizierung oder stellen Sie das Konto um.';
$string['import_error_rowsize'] = 'Jede Zeile muss dieselbe Anzahl von Spalten enthalten.';
$string['import_error_suspended'] = 'Nutzer/in „{$a}“ ist gesperrt.';
$string['import_failed'] = 'Es wurde nichts importiert. Anzahl der Zeilen mit Problemen: {$a->error}.';
$string['import_more_rows'] = 'Anzahl weiterer, nicht angezeigter Zeilen: {$a}.';
$string['import_options'] = 'Importoptionen';
$string['import_preview'] = 'Daten prüfen';
$string['import_refresh'] = 'Vorschau aktualisieren';
$string['import_result_created'] = 'Wird importiert';
$string['import_result_error'] = 'Problem';
$string['import_result_skipped'] = 'Übersprungen';
$string['import_skip_ambiguous'] = 'Zeilen überspringen, die zu mehr als einer/m Nutzer/in passen';
$string['import_skip_guidused'] = 'Zeilen überspringen, deren Konto beim Identitätsanbieter bereits zugeordnet ist';
$string['import_skip_info'] = 'Zeilen mit einem Problem stoppen den Import, sofern sie hier nicht übersprungen werden. Es wird nichts geschrieben, bevor die Daten geprüft wurden.';
$string['import_skip_invalid'] = 'Zeilen mit fehlenden Werten überspringen';
$string['import_skip_mapped'] = 'Bereits zugeordnete Nutzer/innen überspringen';
$string['import_skip_missing'] = 'Zeilen ohne passende/n Nutzer/in überspringen';
$string['import_skip_unusable'] = 'Gesperrte Nutzer/innen und Nutzer/innen überspringen, die diesen Identitätsanbieter nicht verwenden können';
$string['import_source_info'] = 'Laden Sie eine CSV-Datei hoch oder fügen Sie die Daten ein.';
$string['musaml:managemappings'] = 'Zuordnungen von Nutzer/innen zu Konten beim Identitätsanbieter verwalten';
$string['pluginname'] = 'SAML-Authentifizierung';
$string['privacy:metadata:auth_musaml_login'] = 'Laufende Anmeldungen, werden nach Abschluss der Anmeldung und spätestens nach einigen Minuten gelöscht';
$string['privacy:metadata:auth_musaml_login:idpid'] = 'Identitätsanbieter';
$string['privacy:metadata:auth_musaml_login:resultjson'] = 'Vom Identitätsanbieter empfangene Attribute';
$string['privacy:metadata:auth_musaml_login:timecreated'] = 'Zeitpunkt des Anmeldebeginns';
$string['privacy:metadata:auth_musaml_login:userid'] = 'Nutzer/in, zu der/dem die Anmeldung aufgelöst wurde';
$string['privacy:metadata:auth_musaml_user'] = 'Zuordnungen von Nutzer/innen zu Konten beim Identitätsanbieter';
$string['privacy:metadata:auth_musaml_user:allowotherauth'] = 'Ob sich die/der Nutzer/in auch mit einer anderen Authentifizierungsmethode anmelden darf';
$string['privacy:metadata:auth_musaml_user:automapped'] = 'Ob die Zuordnung bei der ersten Anmeldung automatisch angelegt wurde';
$string['privacy:metadata:auth_musaml_user:guid'] = 'Eindeutige Nutzerkennung beim Identitätsanbieter';
$string['privacy:metadata:auth_musaml_user:idpid'] = 'Identitätsanbieter';
$string['privacy:metadata:auth_musaml_user:timecreated'] = 'Zeitpunkt, zu dem die Zuordnung angelegt wurde';
$string['privacy:metadata:auth_musaml_user:userid'] = 'Moodle-Nutzer/in';
$string['privacy:metadata:authsubsystem'] = 'Das Plugin meldet Nutzer/innen über das Authentifizierungssubsystem an.';
$string['provider_adfs'] = 'Active Directory Federation Services';
$string['provider_auto'] = 'Aus den Metadaten erkennen';
$string['provider_entra'] = 'Microsoft Entra ID';
$string['provider_generic'] = 'Generisches SAML 2.0';
$string['provider_google'] = 'Google Workspace';
$string['provider_keycloak'] = 'Keycloak';
$string['provider_okta'] = 'Okta';
$string['provider_zitadel'] = 'Zitadel';
$string['setting_debug'] = 'Debug-Protokollierung';
$string['setting_debug_desc'] = 'Details zu SAML-Anfragen und -Antworten in das PHP-Fehlerprotokoll schreiben. Nicht auf Produktivsystemen aktivieren.';
$string['setting_fieldlocks_desc'] = 'Gesperrte Felder können von Nutzer/innen nicht bearbeitet werden. Die Sperren gelten für alle Nutzer/innen mit SAML-Authentifizierung, ob zugeordnet oder nicht.';
$string['sp'] = 'Dienstanbieter';
$string['sp_acsurl'] = 'Assertion Consumer Service URL';
$string['sp_cert'] = 'Zertifikat';
$string['sp_cert_commonname'] = 'Common Name (CN)';
$string['sp_cert_create'] = 'Zertifikat generieren';
$string['sp_cert_days'] = 'Gültigkeit in Tagen';
$string['sp_cert_delete'] = 'Zertifikat löschen';
$string['sp_cert_delete_info'] = 'Das Zertifikat des Dienstanbieters, sein privater Schlüssel und die Entity-ID werden gelöscht.';
$string['sp_cert_download'] = 'Zertifikat herunterladen';
$string['sp_cert_fingerprint'] = 'Fingerabdruck des Zertifikats (SHA-256)';
$string['sp_cert_missing'] = 'Generieren Sie zuerst das Zertifikat des Dienstanbieters, Identitätsanbieter benötigen es bei der Einrichtung.';
$string['sp_cert_notafter'] = 'Zertifikat läuft ab';
$string['sp_cert_organizationname'] = 'Organisation (O)';
$string['sp_cert_regen'] = 'Zertifikat neu generieren';
$string['sp_cert_regen_warning'] = 'Das Zertifikat und der private Schlüssel werden sofort ersetzt. Anmeldungen schlagen fehl, bis jeder Identitätsanbieter die neuen Metadaten des Dienstanbieters hat.';
$string['sp_cert_subject'] = 'Antragsteller des Zertifikats';
$string['sp_entityid'] = 'Entity-ID';
$string['sp_entityid_help'] = 'Identifiziert diese Website gegenüber den Identitätsanbietern, standardmäßig die Metadaten-URL. Sie kann nur zusammen mit dem Zertifikat geändert werden, beide müssen dann bei jedem Identitätsanbieter neu registriert werden.';
$string['sp_metadataurl'] = 'Metadaten des Dienstanbieters';
$string['sp_slourl'] = 'Single-Logout-URL';
$string['tab_attributes'] = 'Attribute';
$string['tab_details'] = 'Details';
$string['tab_mappings'] = 'Zugeordnete Nutzer/innen';
$string['tenant_all'] = 'Alle Mandanten';
$string['test_attributes_info'] = 'Vor {$a} bei einer Testanmeldung empfangen.';
$string['test_login'] = 'Testanmeldung';
$string['test_login_attributes'] = 'Empfangene Attribute';
$string['test_login_nouser'] = 'Diesem Konto beim Identitätsanbieter ist noch keine/r Nutzer/in zugeordnet.';
$string['test_login_success'] = 'Die Antwort des Identitätsanbieters wurde akzeptiert. Dies war ein Test, niemand wurde angemeldet.';
$string['test_login_user'] = 'Zugeordnete/r Nutzer/in';
$string['user_mapping_allowotherauth'] = 'Andere Authentifizierung erlaubt';
$string['user_mapping_allowotherauth_help'] = 'Erlaubt einer/m Nutzer/in mit einer anderen Authentifizierungsmethode, zum Beispiel manuell, sich auch über den Identitätsanbieter anzumelden.';
$string['user_mapping_automapped'] = 'Automatisch zugeordnet';
$string['user_mapping_create'] = 'Nutzerzuordnung hinzufügen';
$string['user_mapping_delete'] = 'Nutzerzuordnung löschen';
$string['user_mapping_delete_info'] = 'Die Zuordnung zwischen der/dem Nutzer/in und dem Konto beim Identitätsanbieter wird entfernt. Das Nutzerkonto bleibt erhalten.';
$string['user_mapping_guid'] = 'Konto-ID beim Identitätsanbieter';
$string['user_mapping_guid_help'] = 'Wert des Nutzer-ID-Attributs genau so, wie ihn der Identitätsanbieter sendet.';
$string['user_mapping_setauth'] = 'Nutzerkonto auf SAML-Authentifizierung umstellen';
$string['user_mapping_setauth_help'] = 'Stellt das Nutzerkonto auf SAML-Authentifizierung um. Lassen Sie dies deaktiviert, um die aktuelle Methode beizubehalten, und erlauben Sie stattdessen eine andere Authentifizierung.';
$string['user_mapping_timecreated'] = 'Zugeordnet';
$string['user_mapping_update'] = 'Nutzerzuordnung bearbeiten';
$string['user_mapping_user'] = 'Nutzer/in';
$string['user_mapping_user_help'] = 'Nutzer/innen, die bereits einem Identitätsanbieter zugeordnet sind, werden nicht aufgeführt.';
$string['user_mappings'] = 'Zugeordnete Nutzer/innen';
$string['user_mappings_none'] = 'Es sind noch keine Nutzer/innen zugeordnet.';
