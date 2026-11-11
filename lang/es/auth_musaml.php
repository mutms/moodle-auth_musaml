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
 * Strings for component 'auth_musaml', language 'es'.
 *
 * NOTE: This Spanish translation was produced automatically by an AI language
 * model and has not yet been reviewed by a human translator. Corrections are
 * welcome.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['attribute_advertised'] = 'Atributos ofrecidos por los metadatos del proveedor de identidad: {$a}';
$string['attribute_create'] = 'Añadir atributo';
$string['attribute_delete'] = 'Eliminar atributo';
$string['attribute_delete_info'] = 'Se quita el mapeo; los valores ya copiados en los perfiles de usuario se conservan.';
$string['attribute_idpattr'] = 'Atributo del IdP';
$string['attribute_idpattr_help'] = 'Nombre del atributo tal como lo envía el proveedor de identidad, con distinción entre mayúsculas y minúsculas. El nombre especial "nameid" toma el valor del NameID de SAML. Un inicio de sesión de prueba muestra todo lo que se recibe.';
$string['attribute_sync'] = 'Sincronización';
$string['attribute_sync_help'] = 'Nunca: se usa solo para el mapeo automático. Al crear la cuenta: se copia una sola vez. En cada inicio de sesión: se mantiene sincronizado con el proveedor de identidad, normalmente con el campo bloqueado.';
$string['attribute_sync_none'] = 'Nunca';
$string['attribute_sync_oncreate'] = 'Al crear la cuenta';
$string['attribute_sync_onlogin'] = 'En cada inicio de sesión';
$string['attribute_update'] = 'Editar atributo';
$string['attribute_userfield'] = 'Campo de usuario';
$string['attribute_userfield_help'] = 'Campo de usuario de Moodle que recibe el valor. El nombre de usuario solo se establece cuando se crea la cuenta de usuario.';
$string['attribute_usermapping'] = 'Usado para el mapeo automático';
$string['attribute_usermapping_help'] = 'El valor debe coincidir exactamente con un único usuario.';
$string['attribute_value'] = 'Valor';
$string['attributes_none'] = 'Todavía no se ha mapeado ningún atributo.';
$string['auth_musamldescription'] = 'Los usuarios inician sesión a través de proveedores de identidad SAML 2.0 externos.';
$string['check_certificates'] = 'Certificados SAML';
$string['check_certificates_allok'] = 'Todos los certificados son válidos. Número de certificados: {$a}.';
$string['check_certificates_disabled'] = 'La autenticación SAML está deshabilitada.';
$string['check_certificates_expired'] = 'El certificado de {$a->name} expiró hace {$a->time}.';
$string['check_certificates_none'] = 'No se han encontrado certificados con fecha de expiración.';
$string['check_certificates_ok'] = 'El certificado de {$a->name} expira dentro de {$a->time}.';
$string['check_certificates_soon'] = 'El certificado de {$a->name} expira pronto, dentro de {$a->time}.';
$string['error_certificate'] = 'El texto no contiene certificados válidos.';
$string['error_createemail'] = 'No se puede crear una nueva cuenta de usuario, la dirección de correo "{$a}" no se puede utilizar.';
$string['error_createmissing'] = 'No se puede crear una nueva cuenta de usuario, el proveedor de identidad no ha enviado el campo "{$a}".';
$string['error_createusername'] = 'No se puede crear una nueva cuenta de usuario, el nombre de usuario "{$a}" no se puede utilizar.';
$string['error_customsettings_json'] = 'Los ajustes personalizados no son JSON válido: {$a}';
$string['error_customsettings_object'] = 'Los ajustes personalizados deben ser un objeto JSON con los nombres de los ajustes de php-saml como claves.';
$string['error_entityidexists'] = 'Ya existe un proveedor de identidad con el ID de entidad "{$a}".';
$string['error_entityidtoolong'] = 'El ID de entidad tiene más de 255 caracteres, este proveedor de identidad no se puede utilizar.';
$string['error_guidmapped'] = 'Esta cuenta del proveedor de identidad ya está mapeada a otro usuario.';
$string['error_idpunavailable'] = 'Este proveedor de identidad no está disponible.';
$string['error_logout'] = 'El mensaje de cierre de sesión del proveedor de identidad ha sido rechazado: {$a}';
$string['error_metadatablocked'] = 'La URL de metadatos {$a} está bloqueada por los ajustes de seguridad de cURL del sitio, consulte "Seguridad HTTP" en la administración del sitio.';
$string['error_metadatadownload'] = 'Ha fallado la descarga de los metadatos: {$a}';
$string['error_metadatanourl'] = 'Los metadatos pegados no se pueden actualizar. Pegue los nuevos metadatos en los ajustes del proveedor de identidad.';
$string['error_metadataparse'] = 'No se pueden analizar los metadatos: {$a}';
$string['error_metadataurl'] = 'La URL de metadatos debe empezar por http:// o https://';
$string['error_noguid'] = 'El proveedor de identidad no ha enviado exactamente un valor del atributo de ID de usuario "{$a}".';
$string['error_nohttps'] = 'Este sitio no utiliza https. Los inicios de sesión SAML solo funcionan con un proveedor de identidad en este mismo sitio.';
$string['error_nomapping'] = 'No hay ningún usuario mapeado a la cuenta del proveedor de identidad "{$a}". Póngase en contacto con el administrador del sitio.';
$string['error_norequest'] = 'No se ha encontrado ninguna solicitud de inicio de sesión pendiente para esta respuesta, vuelva a iniciar sesión desde el principio.';
$string['error_otherauth'] = 'Su cuenta utiliza el método de autenticación "{$a}" y no se puede usar con este proveedor de identidad.';
$string['error_replay'] = 'Esta respuesta del proveedor de identidad ya se ha utilizado, inicie sesión de nuevo.';
$string['error_response'] = 'La respuesta del proveedor de identidad ha sido rechazada: {$a}';
$string['error_spcertinuse'] = 'El certificado del proveedor de servicios no se puede eliminar mientras haya proveedores de identidad configurados.';
$string['error_spmetadata'] = 'Los metadatos del proveedor de servicios no son válidos: {$a}';
$string['error_spnotconfigured'] = 'Todavía no se ha creado el certificado del proveedor de servicios.';
$string['error_suspended'] = 'Su cuenta está suspendida.';
$string['error_userfieldmapped'] = 'Este campo de usuario ya está mapeado para este proveedor de identidad.';
$string['error_usermapped'] = 'El usuario ya está mapeado a un proveedor de identidad.';
$string['error_usernameprefix'] = 'El prefijo solo puede contener letras minúsculas, dígitos, puntos, guiones y guiones bajos, con un máximo de 50 caracteres.';
$string['error_usernamesync'] = 'El nombre de usuario solo se puede copiar cuando se crea la cuenta de usuario.';
$string['event_idp_created'] = 'Proveedor de identidad creado';
$string['event_idp_deleted'] = 'Proveedor de identidad eliminado';
$string['event_idp_updated'] = 'Proveedor de identidad actualizado';
$string['event_login_failed'] = 'Inicio de sesión SAML fallido';
$string['event_metadata_refresh_failed'] = 'Actualización de los metadatos del proveedor de identidad fallida';
$string['event_user_mapping_created'] = 'Mapeo de usuario creado';
$string['event_user_mapping_deleted'] = 'Mapeo de usuario eliminado';
$string['idp_advertisedattributes'] = 'Atributos en los metadatos';
$string['idp_attrsimple'] = 'Nombres de atributo simples';
$string['idp_attrsimple_help'] = 'Aceptar también la última parte de los nombres de atributo en forma de URI, por ejemplo emailaddress para http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress.';
$string['idp_autocreate'] = 'Crear cuentas de usuario automáticamente';
$string['idp_autocreate_help'] = 'En el primer inicio de sesión se crean nuevas cuentas de usuario para las cuentas desconocidas del proveedor de identidad. Los mapeos de atributos deben proporcionar al menos el nombre de usuario y la dirección de correo.';
$string['idp_autologin'] = 'Inicio de sesión automático';
$string['idp_autologin_help'] = 'Cuando este es el único proveedor de identidad disponible en la página de inicio de sesión, los visitantes son enviados a él de inmediato en lugar de ver el formulario de inicio de sesión.';
$string['idp_autologin_noslo'] = '{$a} no admite el cierre de sesión único, por lo que los usuarios siguen con la sesión iniciada en el proveedor de identidad después de cerrar sesión aquí. La siguiente persona que use el mismo navegador inicia sesión como ellos. No utilice el inicio de sesión automático en ordenadores compartidos.';
$string['idp_automap'] = 'Mapear automáticamente los usuarios existentes';
$string['idp_automap_help'] = 'Mapear en el primer inicio de sesión a un usuario con autenticación SAML y sin mapeo, cuando todos los atributos marcados para el mapeo automático coinciden con su perfil.';
$string['idp_autorefresh'] = 'Actualizar los metadatos automáticamente';
$string['idp_autorefresh_help'] = 'Los metadatos se descargan de nuevo una vez al día después de un inicio de sesión correcto, y de inmediato cuando una respuesta no se puede verificar. Desactive esta opción para conservar los certificados almacenados.';
$string['idp_certificate_manual'] = 'Añadido a mano';
$string['idp_certificate_metadata'] = 'De los metadatos';
$string['idp_certificate_source'] = 'Origen';
$string['idp_certificates'] = 'Certificados de firma';
$string['idp_certificates_edit'] = 'Ajustes de certificados';
$string['idp_create'] = 'Añadir proveedor de identidad';
$string['idp_created'] = 'Se ha añadido el proveedor de identidad';
$string['idp_customsettings'] = 'Ajustes personalizados de la biblioteca';
$string['idp_customsettings_help'] = 'Objeto JSON que se combina sobre los ajustes de php-saml, por ejemplo {"security": {"wantAssertionsSigned": false}}. Se permiten valores no seguros, pero muestran una advertencia. Úselo solo cuando nada más funcione.';
$string['idp_delete'] = 'Eliminar proveedor de identidad';
$string['idp_delete_info'] = 'Se eliminarán el proveedor de identidad, sus mapeos de atributos y sus mapeos de usuarios (número: {$a}). Las cuentas de usuario se conservan.';
$string['idp_deleted'] = 'Se ha eliminado el proveedor de identidad';
$string['idp_enabled'] = 'Habilitado';
$string['idp_entityid'] = 'ID de entidad';
$string['idp_extracerts'] = 'Certificados de firma adicionales';
$string['idp_extracerts_help'] = 'Certificados aceptados además de los de los metadatos, en formato PEM, uno tras otro. Utilícelo durante una rotación de claves cuando el proveedor de identidad todavía no ha publicado el nuevo certificado.';
$string['idp_logouturl'] = 'URL de cierre de sesión único';
$string['idp_mapattr'] = 'Atributo de ID de usuario';
$string['idp_mapattr_help'] = 'Atributo que contiene el identificador permanente del usuario en el proveedor de identidad. Nunca debe cambiar para un usuario ni reutilizarse. El nombre especial "nameid" toma el valor del NameID de SAML.';
$string['idp_metadata_error'] = 'La última actualización ha fallado: {$a}';
$string['idp_metadata_fetched'] = 'Metadatos obtenidos';
$string['idp_metadata_never'] = 'Nunca';
$string['idp_metadata_refresh'] = 'Actualizar metadatos';
$string['idp_metadata_refresh_info'] = 'Los metadatos se descargan de nuevo desde la URL de metadatos. El ID de entidad, los puntos de conexión y los certificados de firma se sustituyen por los valores actuales.';
$string['idp_metadata_refreshed'] = 'Se han actualizado los metadatos';
$string['idp_metadata_validuntil'] = 'Metadatos válidos hasta';
$string['idp_metadatapasted'] = 'XML pegado, no se actualiza automáticamente';
$string['idp_metadatasource'] = 'URL o XML de metadatos';
$string['idp_metadatasource_help'] = 'URL de los metadatos SAML 2.0 del proveedor de identidad, o el propio XML de metadatos. Una URL se actualiza automáticamente; el XML pegado debe sustituirse a mano cuando cambian los certificados.';
$string['idp_metadataurl'] = 'URL de metadatos';
$string['idp_metadataurl_help'] = 'URL pública de los metadatos SAML 2.0 del proveedor de identidad. El ID de entidad, la URL de inicio de sesión único y los certificados de firma se leen de ella y se actualizan automáticamente.';
$string['idp_name'] = 'Nombre';
$string['idp_name_help'] = 'Se muestra en el botón de la página de inicio de sesión.';
$string['idp_nameidformat'] = 'Formato de NameID';
$string['idp_provider'] = 'Proveedor';
$string['idp_provider_help'] = 'Rellena previamente los atributos y adapta los ajustes de la biblioteca a las particularidades conocidas de cada producto. Utilice SAML 2.0 genérico para cualquier otro caso.';
$string['idp_settings_relaxed'] = 'Menos estrictos que los valores por defecto del plugin, establecidos por el proveedor o por los ajustes personalizados de la biblioteca: {$a}';
$string['idp_ssourl'] = 'URL de inicio de sesión único';
$string['idp_tenant'] = 'Inquilino';
$string['idp_tenant_help'] = 'Se ofrece solo en la página de inicio de sesión de este inquilino. Sin inquilino se ofrece en todas partes.';
$string['idp_update'] = 'Editar proveedor de identidad';
$string['idp_updated'] = 'Se ha actualizado el proveedor de identidad';
$string['idp_usernameprefix'] = 'Prefijo del nombre de usuario';
$string['idp_usernameprefix_help'] = 'Se añade a los nombres de usuario de las cuentas de usuario creadas y se usa al buscar coincidencias de nombres de usuario. Mantiene los nombres de usuario únicos entre proveedores de identidad.';
$string['idps'] = 'Proveedores de identidad';
$string['idps_none'] = 'Todavía no se ha añadido ningún proveedor de identidad.';
$string['idps_none_info'] = 'Pulse Añadir proveedor de identidad y pegue la URL de sus metadatos.';
$string['idps_none_info2'] = 'Envíe al proveedor de identidad los metadatos del proveedor de servicios indicados arriba. Cualquiera de los dos pasos puede hacerse primero.';
$string['import'] = 'Importar mapeos de usuarios';
$string['import_column_ignore'] = 'No utilizada';
$string['import_column_number'] = 'Columna {$a}';
$string['import_columns'] = 'Significado de las columnas';
$string['import_columns_headers'] = 'La primera línea contiene los nombres de las columnas';
$string['import_columns_info'] = 'Indique qué contiene cada columna. Filas leídas: {$a}.';
$string['import_confirm'] = 'Importar mapeos';
$string['import_counts'] = 'Listos para importar: {$a->created}. Omitidos: {$a->skipped}. Problemas: {$a->error}.';
$string['import_done'] = 'Mapeos de usuarios importados: {$a->created}. Omitidos: {$a->skipped}.';
$string['import_error_ambiguous'] = 'Más de un usuario coincide con "{$a}".';
$string['import_error_columntwice'] = 'Cada significado de columna solo puede utilizarse una vez.';
$string['import_error_columnunknown'] = 'Este significado de columna no se conoce.';
$string['import_error_empty'] = 'No se han encontrado filas utilizables.';
$string['import_error_guidused'] = 'La cuenta del proveedor de identidad "{$a}" ya está mapeada a otro usuario.';
$string['import_error_headers'] = 'Indique si la primera línea contiene los nombres de las columnas.';
$string['import_error_invalid'] = 'La fila no contiene un ID de cuenta del proveedor de identidad y un identificador de usuario.';
$string['import_error_mapped'] = 'El usuario "{$a}" ya está mapeado a otra cuenta del proveedor de identidad.';
$string['import_error_mappedsame'] = 'El usuario "{$a}" ya tiene este mapeo.';
$string['import_error_missing'] = 'Ningún usuario coincide con "{$a}".';
$string['import_error_nodatarows'] = 'Aparte de los nombres de las columnas no hay datos.';
$string['import_error_noguid'] = 'Una columna debe contener el ID de cuenta del proveedor de identidad.';
$string['import_error_nouser'] = 'Una columna debe identificar al usuario.';
$string['import_error_otherauth'] = 'La cuenta de usuario utiliza el método de autenticación "{$a}"; permita otra autenticación o cambie el método de la cuenta.';
$string['import_error_rowsize'] = 'Todas las filas deben tener el mismo número de columnas.';
$string['import_error_suspended'] = 'El usuario "{$a}" está suspendido.';
$string['import_failed'] = 'No se ha importado nada. Filas con problemas: {$a->error}.';
$string['import_more_rows'] = 'Filas adicionales no mostradas: {$a}.';
$string['import_options'] = 'Opciones de importación';
$string['import_preview'] = 'Comprobar los datos';
$string['import_refresh'] = 'Actualizar vista previa';
$string['import_result_created'] = 'Se importará';
$string['import_result_error'] = 'Problema';
$string['import_result_skipped'] = 'Omitido';
$string['import_skip_ambiguous'] = 'Omitir las filas que coinciden con más de un usuario';
$string['import_skip_guidused'] = 'Omitir las filas cuya cuenta del proveedor de identidad ya está mapeada';
$string['import_skip_info'] = 'Las filas con algún problema detienen la importación, salvo que se omitan aquí. No se escribe nada hasta que se hayan comprobado los datos.';
$string['import_skip_invalid'] = 'Omitir las filas con valores ausentes';
$string['import_skip_mapped'] = 'Omitir los usuarios que ya están mapeados';
$string['import_skip_missing'] = 'Omitir las filas sin usuario coincidente';
$string['import_skip_unusable'] = 'Omitir los usuarios suspendidos y los usuarios que no pueden usar este proveedor de identidad';
$string['import_source_info'] = 'Suba un archivo CSV o pegue los datos.';
$string['musaml:managemappings'] = 'Gestionar los mapeos de usuarios a cuentas del proveedor de identidad';
$string['pluginname'] = 'Autenticación SAML';
$string['privacy:metadata:auth_musaml_login'] = 'Inicios de sesión en curso, eliminados cuando termina el inicio de sesión y, como muy tarde, al cabo de unos minutos';
$string['privacy:metadata:auth_musaml_login:idpid'] = 'Proveedor de identidad';
$string['privacy:metadata:auth_musaml_login:resultjson'] = 'Atributos recibidos del proveedor de identidad';
$string['privacy:metadata:auth_musaml_login:timecreated'] = 'Hora en que empezó el inicio de sesión';
$string['privacy:metadata:auth_musaml_login:userid'] = 'Usuario al que corresponde el inicio de sesión';
$string['privacy:metadata:auth_musaml_user'] = 'Mapeos de usuarios a cuentas del proveedor de identidad';
$string['privacy:metadata:auth_musaml_user:allowotherauth'] = 'Si el usuario puede iniciar sesión también con otro método de autenticación';
$string['privacy:metadata:auth_musaml_user:automapped'] = 'Si el mapeo se creó automáticamente en el primer inicio de sesión';
$string['privacy:metadata:auth_musaml_user:guid'] = 'Identificador único del usuario en el proveedor de identidad';
$string['privacy:metadata:auth_musaml_user:idpid'] = 'Proveedor de identidad';
$string['privacy:metadata:auth_musaml_user:timecreated'] = 'Fecha de creación del mapeo';
$string['privacy:metadata:auth_musaml_user:userid'] = 'Usuario de Moodle';
$string['privacy:metadata:authsubsystem'] = 'El plugin inicia la sesión de los usuarios a través del subsistema de autenticación.';
$string['provider_adfs'] = 'Active Directory Federation Services';
$string['provider_auto'] = 'Detectar a partir de los metadatos';
$string['provider_entra'] = 'Microsoft Entra ID';
$string['provider_generic'] = 'SAML 2.0 genérico';
$string['provider_google'] = 'Google Workspace';
$string['provider_keycloak'] = 'Keycloak';
$string['provider_okta'] = 'Okta';
$string['provider_zitadel'] = 'Zitadel';
$string['setting_debug'] = 'Registro de depuración';
$string['setting_debug_desc'] = 'Escribir los detalles de las solicitudes y respuestas SAML en el registro de errores de PHP. No lo habilite en sitios de producción.';
$string['setting_fieldlocks_desc'] = 'Los usuarios no pueden editar los campos bloqueados. Los bloqueos se aplican a todos los usuarios con autenticación SAML, estén mapeados o no.';
$string['sp'] = 'Proveedor de servicios';
$string['sp_acsurl'] = 'URL del servicio consumidor de aserciones';
$string['sp_cert'] = 'Certificado';
$string['sp_cert_commonname'] = 'Nombre común';
$string['sp_cert_create'] = 'Generar certificado';
$string['sp_cert_days'] = 'Validez en días';
$string['sp_cert_delete'] = 'Eliminar certificado';
$string['sp_cert_delete_info'] = 'Se eliminan el certificado del proveedor de servicios, su clave privada y el ID de entidad.';
$string['sp_cert_download'] = 'Descargar certificado';
$string['sp_cert_fingerprint'] = 'Huella digital del certificado (SHA-256)';
$string['sp_cert_missing'] = 'Genere primero el certificado del proveedor de servicios, los proveedores de identidad lo necesitan durante la configuración.';
$string['sp_cert_notafter'] = 'El certificado expira';
$string['sp_cert_organizationname'] = 'Organización';
$string['sp_cert_regen'] = 'Regenerar certificado';
$string['sp_cert_regen_warning'] = 'El certificado y la clave privada se sustituyen de inmediato. Los inicios de sesión fallan hasta que todos los proveedores de identidad tengan los nuevos metadatos del proveedor de servicios.';
$string['sp_cert_subject'] = 'Sujeto del certificado';
$string['sp_entityid'] = 'ID de entidad';
$string['sp_entityid_help'] = 'Identifica este sitio ante los proveedores de identidad; por defecto es la URL de metadatos. Solo se puede cambiar junto con el certificado, y ambos deben registrarse entonces de nuevo en todos los proveedores de identidad.';
$string['sp_metadataurl'] = 'Metadatos del proveedor de servicios';
$string['sp_slourl'] = 'URL de cierre de sesión único';
$string['tab_attributes'] = 'Atributos';
$string['tab_details'] = 'Detalles';
$string['tab_mappings'] = 'Usuarios mapeados';
$string['tenant_all'] = 'Todos los inquilinos';
$string['test_attributes_info'] = 'Recibidos durante un inicio de sesión de prueba hace {$a}.';
$string['test_login'] = 'Inicio de sesión de prueba';
$string['test_login_attributes'] = 'Atributos recibidos';
$string['test_login_nouser'] = 'Todavía no hay ningún usuario mapeado a esta cuenta del proveedor de identidad.';
$string['test_login_success'] = 'La respuesta del proveedor de identidad ha sido aceptada. Era una prueba, no se ha iniciado la sesión de nadie.';
$string['test_login_user'] = 'Usuario mapeado';
$string['user_mapping_allowotherauth'] = 'Se permite otra autenticación';
$string['user_mapping_allowotherauth_help'] = 'Permite que un usuario con otro método de autenticación, por ejemplo manual, inicie sesión también a través del proveedor de identidad.';
$string['user_mapping_automapped'] = 'Mapeado automáticamente';
$string['user_mapping_create'] = 'Añadir mapeo de usuario';
$string['user_mapping_delete'] = 'Eliminar mapeo de usuario';
$string['user_mapping_delete_info'] = 'Se quita el mapeo entre el usuario y la cuenta del proveedor de identidad. La cuenta de usuario se conserva.';
$string['user_mapping_guid'] = 'ID de cuenta del proveedor de identidad';
$string['user_mapping_guid_help'] = 'Valor del atributo de ID de usuario exactamente como lo envía el proveedor de identidad.';
$string['user_mapping_setauth'] = 'Cambiar la cuenta de usuario a la autenticación SAML';
$string['user_mapping_setauth_help'] = 'Cambia la cuenta de usuario a la autenticación SAML. Déjelo sin marcar para mantener el método actual y permita en su lugar otra autenticación.';
$string['user_mapping_timecreated'] = 'Fecha del mapeo';
$string['user_mapping_update'] = 'Editar mapeo de usuario';
$string['user_mapping_user'] = 'Usuario';
$string['user_mapping_user_help'] = 'Los usuarios ya mapeados a un proveedor de identidad no aparecen en la lista.';
$string['user_mappings'] = 'Usuarios mapeados';
$string['user_mappings_none'] = 'Todavía no se ha mapeado ningún usuario.';
