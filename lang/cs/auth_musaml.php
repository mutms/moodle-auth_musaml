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
 * Strings for component 'auth_musaml', language 'cs'.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['attribute_advertised'] = 'Atributy nabízené v metadatech poskytovatele identity: {$a}';
$string['attribute_create'] = 'Přidat atribut';
$string['attribute_delete'] = 'Odstranit atribut';
$string['attribute_delete_info'] = 'Mapování se odstraní, hodnoty už zkopírované do uživatelských profilů zůstanou zachovány.';
$string['attribute_idpattr'] = 'Atribut IDP';
$string['attribute_idpattr_help'] = 'Název atributu tak, jak ho posílá poskytovatel identity, rozlišují se malá a velká písmena. Speciální název „nameid“ převezme hodnotu ze SAML NameID. Testovací přihlášení vypíše všechno, co dorazí.';
$string['attribute_sync'] = 'Synchronizace';
$string['attribute_sync_help'] = 'Nikdy: používá se jen pro automatické mapování. Při vytvoření účtu: zkopíruje se jednou. Při každém přihlášení: udržuje se v souladu s poskytovatelem identity, obvykle s uzamčeným polem.';
$string['attribute_sync_none'] = 'Nikdy';
$string['attribute_sync_oncreate'] = 'Při vytvoření účtu';
$string['attribute_sync_onlogin'] = 'Při každém přihlášení';
$string['attribute_update'] = 'Upravit atribut';
$string['attribute_userfield'] = 'Pole uživatele';
$string['attribute_userfield_help'] = 'Pole uživatele v Moodlu, které hodnotu přijímá. Uživatelské jméno se nastavuje jen při vytvoření uživatelského účtu.';
$string['attribute_usermapping'] = 'Použito pro automatické mapování';
$string['attribute_usermapping_help'] = 'Hodnota musí odpovídat právě jednomu uživateli.';
$string['attribute_value'] = 'Hodnota';
$string['attributes_none'] = 'Zatím nejsou namapovány žádné atributy.';
$string['auth_musamldescription'] = 'Uživatelé se přihlašují přes externí poskytovatele identity SAML 2.0.';
$string['check_certificates'] = 'Certifikáty SAML';
$string['check_certificates_allok'] = 'Všechny certifikáty jsou platné. Počet certifikátů: {$a}.';
$string['check_certificates_disabled'] = 'Ověřování SAML je vypnuté.';
$string['check_certificates_expired'] = 'Certifikátu pro {$a->name} vypršela platnost. Od vypršení uplynulo: {$a->time}.';
$string['check_certificates_none'] = 'Nebyly nalezeny žádné certifikáty s datem vypršení platnosti.';
$string['check_certificates_ok'] = 'Do vypršení platnosti certifikátu pro {$a->name} zbývá: {$a->time}.';
$string['check_certificates_soon'] = 'Do vypršení platnosti certifikátu pro {$a->name} zbývá: {$a->time}.';
$string['error_certificate'] = 'Text neobsahuje platné certifikáty.';
$string['error_createemail'] = 'Nový uživatelský účet nelze vytvořit, e-mailovou adresu „{$a}“ nelze použít.';
$string['error_createmissing'] = 'Nový uživatelský účet nelze vytvořit, poskytovatel identity neposlal pole „{$a}“.';
$string['error_createusername'] = 'Nový uživatelský účet nelze vytvořit, uživatelské jméno „{$a}“ nelze použít.';
$string['error_customsettings_json'] = 'Vlastní nastavení nejsou platný JSON: {$a}';
$string['error_customsettings_object'] = 'Vlastní nastavení musí být objekt JSON, jehož klíči jsou názvy nastavení php-saml.';
$string['error_entityidexists'] = 'Poskytovatel identity s ID entity „{$a}“ už existuje.';
$string['error_entityidtoolong'] = 'ID entity je delší než 255 znaků, tohoto poskytovatele identity nelze použít.';
$string['error_guidmapped'] = 'Tento účet poskytovatele identity je už namapován na jiného uživatele.';
$string['error_idpunavailable'] = 'Tento poskytovatel identity není dostupný.';
$string['error_logout'] = 'Zpráva poskytovatele identity o odhlášení byla odmítnuta: {$a}';
$string['error_metadatablocked'] = 'URL metadat {$a} je blokováno nastavením zabezpečení cURL těchto stránek, viz „Zabezpečení HTTP“ ve správě stránek.';
$string['error_metadatadownload'] = 'Stažení metadat se nezdařilo: {$a}';
$string['error_metadatanourl'] = 'Vložená metadata nelze obnovit. Vložte nová metadata v nastavení poskytovatele identity.';
$string['error_metadataparse'] = 'Metadata nelze zpracovat: {$a}';
$string['error_metadataurl'] = 'URL metadat musí začínat http:// nebo https://';
$string['error_noguid'] = 'Poskytovatel identity neposlal právě jednu hodnotu atributu ID uživatele „{$a}“.';
$string['error_nohttps'] = 'Tyto stránky nepoužívají https. Přihlášení přes SAML funguje jen s poskytovatelem identity na těchto stejných stránkách.';
$string['error_nomapping'] = 'Na účet poskytovatele identity „{$a}“ není namapován žádný uživatel. Obraťte se na správce stránek.';
$string['error_norequest'] = 'K této odpovědi nebyl nalezen žádný čekající požadavek na přihlášení, začněte přihlášení znovu.';
$string['error_otherauth'] = 'Váš účet používá metodu ověřování „{$a}“ a nelze ho použít s tímto poskytovatelem identity.';
$string['error_replay'] = 'Tato odpověď poskytovatele identity už byla použita, přihlaste se znovu.';
$string['error_response'] = 'Odpověď poskytovatele identity byla odmítnuta: {$a}';
$string['error_spcertinuse'] = 'Certifikát poskytovatele služeb nelze odstranit, dokud jsou nastaveni poskytovatelé identity.';
$string['error_spmetadata'] = 'Metadata poskytovatele služeb nejsou platná: {$a}';
$string['error_spnotconfigured'] = 'Certifikát poskytovatele služeb ještě nebyl vytvořen.';
$string['error_suspended'] = 'Váš účet je pozastavený.';
$string['error_userfieldmapped'] = 'Toto pole uživatele je pro tohoto poskytovatele identity už namapováno.';
$string['error_usermapped'] = 'Uživatel je už namapován na poskytovatele identity.';
$string['error_usernameprefix'] = 'Předpona smí obsahovat jen malá písmena, číslice, tečky, pomlčky a podtržítka, nejvýše 50 znaků.';
$string['error_usernamesync'] = 'Uživatelské jméno lze zkopírovat jen při vytvoření uživatelského účtu.';
$string['event_idp_created'] = 'Poskytovatel identity vytvořen';
$string['event_idp_deleted'] = 'Poskytovatel identity odstraněn';
$string['event_idp_updated'] = 'Poskytovatel identity upraven';
$string['event_login_failed'] = 'Přihlášení přes SAML se nezdařilo';
$string['event_metadata_refresh_failed'] = 'Obnovení metadat poskytovatele identity se nezdařilo';
$string['event_user_mapping_created'] = 'Mapování uživatele vytvořeno';
$string['event_user_mapping_deleted'] = 'Mapování uživatele odstraněno';
$string['idp_advertisedattributes'] = 'Atributy v metadatech';
$string['idp_attrsimple'] = 'Jednoduché názvy atributů';
$string['idp_attrsimple_help'] = 'Přijímat také poslední část názvů atributů ve tvaru URI, například emailaddress pro http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress.';
$string['idp_autocreate'] = 'Vytvářet uživatelské účty automaticky';
$string['idp_autocreate_help'] = 'Pro neznámé účty poskytovatele identity se při prvním přihlášení vytvoří nové uživatelské účty. Mapování atributů musí dodat alespoň uživatelské jméno a e-mail.';
$string['idp_autologin'] = 'Automatické přihlášení';
$string['idp_autologin_help'] = 'Když je to jediný poskytovatel identity dostupný na přihlašovací stránce, návštěvníci jsou k němu rovnou přesměrováni a přihlašovací formulář se jim nezobrazí.';
$string['idp_autologin_noslo'] = '{$a} nepodporuje jednotné odhlášení, takže uživatelé po odhlášení tady zůstávají přihlášeni u poskytovatele identity. Další osoba používající stejný prohlížeč bude přihlášena jako oni. Nepoužívejte automatické přihlášení na sdílených počítačích.';
$string['idp_automap'] = 'Mapovat existující uživatele automaticky';
$string['idp_automap_help'] = 'Při prvním přihlášení namapovat uživatele s ověřováním SAML, který ještě nemá mapování, pokud všechny atributy označené pro automatické mapování odpovídají jeho profilu.';
$string['idp_autorefresh'] = 'Obnovovat metadata automaticky';
$string['idp_autorefresh_help'] = 'Metadata se znovu stahují jednou denně po úspěšném přihlášení a okamžitě, když nelze ověřit odpověď. Vypnutím zachováte uložené certifikáty.';
$string['idp_certificate_manual'] = 'Přidáno ručně';
$string['idp_certificate_metadata'] = 'Z metadat';
$string['idp_certificate_source'] = 'Zdroj';
$string['idp_certificates'] = 'Podpisové certifikáty';
$string['idp_certificates_edit'] = 'Nastavení certifikátů';
$string['idp_create'] = 'Přidat poskytovatele identity';
$string['idp_created'] = 'Poskytovatel identity byl přidán';
$string['idp_customsettings'] = 'Vlastní nastavení knihovny';
$string['idp_customsettings_help'] = 'Objekt JSON sloučený přes nastavení php-saml, například {"security": {"wantAssertionsSigned": false}}. Nebezpečná přepsání jsou povolena, ale zobrazí se u nich varování. Používejte jen tehdy, když nic jiného nefunguje.';
$string['idp_delete'] = 'Odstranit poskytovatele identity';
$string['idp_delete_info'] = 'Odstraní se poskytovatel identity, jeho mapování atributů a mapování uživatelů (počet: {$a}). Uživatelské účty zůstanou zachovány.';
$string['idp_deleted'] = 'Poskytovatel identity byl odstraněn';
$string['idp_enabled'] = 'Povoleno';
$string['idp_entityid'] = 'ID entity';
$string['idp_extracerts'] = 'Další podpisové certifikáty';
$string['idp_extracerts_help'] = 'Certifikáty přijímané vedle certifikátů z metadat, ve formátu PEM jeden za druhým. Použijte při výměně klíčů, když poskytovatel identity ještě nezveřejnil nový certifikát.';
$string['idp_logouturl'] = 'URL jednotného odhlášení';
$string['idp_mapattr'] = 'Atribut ID uživatele';
$string['idp_mapattr_help'] = 'Atribut s trvalým identifikátorem uživatele u poskytovatele identity. U uživatele se nesmí nikdy změnit a nesmí se znovu použít. Speciální název „nameid“ převezme hodnotu ze SAML NameID.';
$string['idp_metadata_error'] = 'Poslední obnovení se nezdařilo: {$a}';
$string['idp_metadata_fetched'] = 'Metadata stažena';
$string['idp_metadata_never'] = 'Nikdy';
$string['idp_metadata_refresh'] = 'Obnovit metadata';
$string['idp_metadata_refresh_info'] = 'Metadata se znovu stáhnou z URL metadat. ID entity, koncové body a podpisové certifikáty se nahradí aktuálními hodnotami.';
$string['idp_metadata_refreshed'] = 'Metadata byla obnovena';
$string['idp_metadata_validuntil'] = 'Metadata platná do';
$string['idp_metadatapasted'] = 'Vložené XML, neobnovuje se automaticky';
$string['idp_metadatasource'] = 'URL metadat nebo XML';
$string['idp_metadatasource_help'] = 'URL metadat SAML 2.0 poskytovatele identity, nebo přímo XML metadat. URL se obnovuje automaticky, vložené XML je při změně certifikátů nutné nahradit ručně.';
$string['idp_metadataurl'] = 'URL metadat';
$string['idp_metadataurl_help'] = 'Veřejné URL metadat SAML 2.0 poskytovatele identity. Načte se z něj ID entity, URL jednotného přihlášení a podpisové certifikáty a automaticky se obnovují.';
$string['idp_name'] = 'Název';
$string['idp_name_help'] = 'Zobrazuje se na tlačítku na přihlašovací stránce.';
$string['idp_nameidformat'] = 'Formát NameID';
$string['idp_provider'] = 'Poskytovatel';
$string['idp_provider_help'] = 'Předvyplní atributy a upraví nastavení knihovny podle známých zvláštností produktů. Pro všechno ostatní použijte Obecný SAML 2.0.';
$string['idp_settings_relaxed'] = 'Méně přísné než výchozí nastavení pluginu, nastavil poskytovatel nebo vlastní nastavení knihovny: {$a}';
$string['idp_ssourl'] = 'URL jednotného přihlášení';
$string['idp_tenant'] = 'Klient';
$string['idp_tenant_help'] = 'Nabízí se jen na přihlašovací stránce tohoto klienta. Bez klienta se nabízí všude.';
$string['idp_update'] = 'Upravit poskytovatele identity';
$string['idp_updated'] = 'Poskytovatel identity byl upraven';
$string['idp_usernameprefix'] = 'Předpona uživatelského jména';
$string['idp_usernameprefix_help'] = 'Přidává se k uživatelským jménům vytvořených uživatelských účtů a používá se při porovnávání uživatelských jmen. Zajišťuje jedinečnost uživatelských jmen napříč poskytovateli identity.';
$string['idps'] = 'Poskytovatelé identity';
$string['idps_none'] = 'Zatím nebyli přidáni žádní poskytovatelé identity.';
$string['idps_none_info'] = 'Klikněte na Přidat poskytovatele identity a vložte URL jeho metadat.';
$string['idps_none_info2'] = 'Pošlete výše uvedená metadata poskytovatele služeb poskytovateli identity. Začít můžete kterýmkoli z těchto kroků.';
$string['import'] = 'Importovat mapování uživatelů';
$string['import_column_ignore'] = 'Nepoužito';
$string['import_column_number'] = 'Sloupec {$a}';
$string['import_columns'] = 'Význam sloupců';
$string['import_columns_headers'] = 'První řádek obsahuje názvy sloupců';
$string['import_columns_info'] = 'Uveďte, co jednotlivé sloupce obsahují. Počet načtených řádků: {$a}.';
$string['import_confirm'] = 'Importovat mapování';
$string['import_counts'] = 'Připraveno k importu: {$a->created}. Přeskočeno: {$a->skipped}. Problémy: {$a->error}.';
$string['import_done'] = 'Importovaná mapování uživatelů: {$a->created}. Přeskočeno: {$a->skipped}.';
$string['import_error_ambiguous'] = 'Hodnotě „{$a}“ odpovídá více než jeden uživatel.';
$string['import_error_columntwice'] = 'Každý význam sloupce lze použít jen jednou.';
$string['import_error_columnunknown'] = 'Tento význam sloupce není znám.';
$string['import_error_empty'] = 'Nebyly nalezeny žádné použitelné řádky.';
$string['import_error_guidused'] = 'Účet poskytovatele identity „{$a}“ je už namapován na jiného uživatele.';
$string['import_error_headers'] = 'Uveďte, zda první řádek obsahuje názvy sloupců.';
$string['import_error_invalid'] = 'Řádek neobsahuje ID účtu poskytovatele identity a identifikátor uživatele.';
$string['import_error_mapped'] = 'Uživatel „{$a}“ je už namapován na jiný účet poskytovatele identity.';
$string['import_error_mappedsame'] = 'Uživatel „{$a}“ už toto mapování má.';
$string['import_error_missing'] = 'Hodnotě „{$a}“ neodpovídá žádný uživatel.';
$string['import_error_nodatarows'] = 'Kromě názvů sloupců nejsou k dispozici žádná data.';
$string['import_error_noguid'] = 'Jeden sloupec musí obsahovat ID účtu poskytovatele identity.';
$string['import_error_nouser'] = 'Jeden sloupec musí identifikovat uživatele.';
$string['import_error_otherauth'] = 'Uživatelský účet používá metodu ověřování „{$a}“, povolte jiné ověřování nebo účet přepněte.';
$string['import_error_rowsize'] = 'Každý řádek musí obsahovat stejný počet sloupců.';
$string['import_error_suspended'] = 'Uživatel „{$a}“ je pozastavený.';
$string['import_failed'] = 'Nic nebylo importováno. Počet řádků s problémy: {$a->error}.';
$string['import_more_rows'] = 'Další nezobrazené řádky: {$a}.';
$string['import_options'] = 'Možnosti importu';
$string['import_preview'] = 'Zkontrolovat data';
$string['import_refresh'] = 'Obnovit náhled';
$string['import_result_created'] = 'Bude importováno';
$string['import_result_error'] = 'Problém';
$string['import_result_skipped'] = 'Přeskočeno';
$string['import_skip_ambiguous'] = 'Přeskočit řádky, kterým odpovídá více než jeden uživatel';
$string['import_skip_guidused'] = 'Přeskočit řádky, jejichž účet poskytovatele identity je už namapován';
$string['import_skip_info'] = 'Řádky s problémem zastaví import, pokud je tady nepřeskočíte. Dokud nejsou data zkontrolována, nic se nezapíše.';
$string['import_skip_invalid'] = 'Přeskočit řádky s chybějícími hodnotami';
$string['import_skip_mapped'] = 'Přeskočit uživatele, kteří jsou už namapováni';
$string['import_skip_missing'] = 'Přeskočit řádky bez odpovídajícího uživatele';
$string['import_skip_unusable'] = 'Přeskočit pozastavené uživatele a uživatele, kteří nemohou tohoto poskytovatele identity používat';
$string['import_source_info'] = 'Nahrajte soubor CSV nebo vložte data.';
$string['musaml:managemappings'] = 'Správa mapování uživatelů na účty poskytovatelů identity';
$string['pluginname'] = 'Ověřování SAML';
$string['privacy:metadata:auth_musaml_login'] = 'Probíhající přihlášení, odstraní se po dokončení přihlášení, nejpozději po několika minutách';
$string['privacy:metadata:auth_musaml_login:idpid'] = 'Poskytovatel identity';
$string['privacy:metadata:auth_musaml_login:resultjson'] = 'Atributy přijaté od poskytovatele identity';
$string['privacy:metadata:auth_musaml_login:timecreated'] = 'Čas zahájení přihlášení';
$string['privacy:metadata:auth_musaml_login:userid'] = 'Uživatel, kterému přihlášení odpovídá';
$string['privacy:metadata:auth_musaml_user'] = 'Mapování uživatelů na účty poskytovatelů identity';
$string['privacy:metadata:auth_musaml_user:allowotherauth'] = 'Zda se uživatel smí přihlásit také jinou metodou ověřování';
$string['privacy:metadata:auth_musaml_user:automapped'] = 'Zda bylo mapování vytvořeno automaticky při prvním přihlášení';
$string['privacy:metadata:auth_musaml_user:guid'] = 'Jedinečný identifikátor uživatele u poskytovatele identity';
$string['privacy:metadata:auth_musaml_user:idpid'] = 'Poskytovatel identity';
$string['privacy:metadata:auth_musaml_user:timecreated'] = 'Čas vytvoření mapování';
$string['privacy:metadata:auth_musaml_user:userid'] = 'Uživatel Moodle';
$string['privacy:metadata:authsubsystem'] = 'Plugin přihlašuje uživatele prostřednictvím subsystému ověřování.';
$string['provider_adfs'] = 'Active Directory Federation Services';
$string['provider_auto'] = 'Zjistit z metadat';
$string['provider_entra'] = 'Microsoft Entra ID';
$string['provider_generic'] = 'Obecný SAML 2.0';
$string['provider_google'] = 'Google Workspace';
$string['provider_keycloak'] = 'Keycloak';
$string['provider_okta'] = 'Okta';
$string['provider_zitadel'] = 'Zitadel';
$string['setting_debug'] = 'Ladicí záznamy';
$string['setting_debug_desc'] = 'Zapisovat podrobnosti požadavků a odpovědí SAML do chybového protokolu PHP. Nezapínejte na produkčních stránkách.';
$string['setting_fieldlocks_desc'] = 'Uzamčená pole nemohou uživatelé upravovat. Uzamčení platí pro každého uživatele s ověřováním SAML, namapovaného i nenamapovaného.';
$string['sp'] = 'Poskytovatel služeb';
$string['sp_acsurl'] = 'URL služby Assertion Consumer Service';
$string['sp_cert'] = 'Certifikát';
$string['sp_cert_commonname'] = 'Obecné jméno';
$string['sp_cert_create'] = 'Vygenerovat certifikát';
$string['sp_cert_days'] = 'Platnost ve dnech';
$string['sp_cert_delete'] = 'Odstranit certifikát';
$string['sp_cert_delete_info'] = 'Odstraní se certifikát poskytovatele služeb, jeho soukromý klíč a ID entity.';
$string['sp_cert_download'] = 'Stáhnout certifikát';
$string['sp_cert_fingerprint'] = 'Otisk certifikátu (SHA-256)';
$string['sp_cert_missing'] = 'Nejprve vygenerujte certifikát poskytovatele služeb, poskytovatelé identity ho potřebují při nastavení.';
$string['sp_cert_notafter'] = 'Platnost certifikátu vyprší';
$string['sp_cert_organizationname'] = 'Organizace';
$string['sp_cert_regen'] = 'Znovu vygenerovat certifikát';
$string['sp_cert_regen_warning'] = 'Certifikát a soukromý klíč se okamžitě nahradí. Přihlášení nebudou fungovat, dokud všichni poskytovatelé identity nebudou mít nová metadata poskytovatele služeb.';
$string['sp_cert_subject'] = 'Předmět certifikátu';
$string['sp_entityid'] = 'ID entity';
$string['sp_entityid_help'] = 'Identifikuje tyto stránky vůči poskytovatelům identity, ve výchozím nastavení je to URL metadat. Lze ho změnit jen společně s certifikátem, oba je pak nutné znovu zaregistrovat u všech poskytovatelů identity.';
$string['sp_metadataurl'] = 'Metadata poskytovatele služeb';
$string['sp_slourl'] = 'URL jednotného odhlášení';
$string['tab_attributes'] = 'Atributy';
$string['tab_details'] = 'Podrobnosti';
$string['tab_mappings'] = 'Namapovaní uživatelé';
$string['tenant_all'] = 'Všichni klienti';
$string['test_attributes_info'] = 'Přijato při testovacím přihlášení. Od té doby uplynulo: {$a}.';
$string['test_login'] = 'Testovací přihlášení';
$string['test_login_attributes'] = 'Přijaté atributy';
$string['test_login_nouser'] = 'Na tento účet poskytovatele identity zatím není namapován žádný uživatel.';
$string['test_login_success'] = 'Odpověď poskytovatele identity byla přijata. Byl to jen test, nikdo nebyl přihlášen.';
$string['test_login_user'] = 'Namapovaný uživatel';
$string['user_mapping_allowotherauth'] = 'Jiné ověřování povoleno';
$string['user_mapping_allowotherauth_help'] = 'Umožňuje uživateli s jinou metodou ověřování, například ruční, přihlásit se také přes poskytovatele identity.';
$string['user_mapping_automapped'] = 'Namapováno automaticky';
$string['user_mapping_create'] = 'Přidat mapování uživatele';
$string['user_mapping_delete'] = 'Odstranit mapování uživatele';
$string['user_mapping_delete_info'] = 'Mapování mezi uživatelem a účtem poskytovatele identity se odstraní. Uživatelský účet zůstane zachován.';
$string['user_mapping_guid'] = 'ID účtu poskytovatele identity';
$string['user_mapping_guid_help'] = 'Hodnota atributu ID uživatele přesně tak, jak ji posílá poskytovatel identity.';
$string['user_mapping_setauth'] = 'Přepnout uživatelský účet na ověřování SAML';
$string['user_mapping_setauth_help'] = 'Přepne uživatelský účet na ověřování SAML. Ponechte nezaškrtnuté, chcete-li zachovat současnou metodu a místo toho povolit jiné ověřování.';
$string['user_mapping_timecreated'] = 'Namapováno';
$string['user_mapping_update'] = 'Upravit mapování uživatele';
$string['user_mapping_user'] = 'Uživatel';
$string['user_mapping_user_help'] = 'Uživatelé už namapovaní na poskytovatele identity nejsou v seznamu uvedeni.';
$string['user_mappings'] = 'Namapovaní uživatelé';
$string['user_mappings_none'] = 'Zatím nejsou namapováni žádní uživatelé.';
