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
 * Strings for component 'auth_musaml', language 'pt'.
 *
 * NOTE: This Portuguese translation was produced automatically by an AI
 * language model and has not yet been reviewed by a human translator. Corrections
 * are welcome.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['attribute_advertised'] = 'Atributos disponibilizados nos metadados do fornecedor de identidade: {$a}';
$string['attribute_create'] = 'Adicionar atributo';
$string['attribute_delete'] = 'Apagar atributo';
$string['attribute_delete_info'] = 'O mapeamento é removido, os valores já copiados para os perfis dos utilizadores são mantidos.';
$string['attribute_idpattr'] = 'Atributo do IdP';
$string['attribute_idpattr_help'] = 'Nome do atributo tal como é enviado pelo fornecedor de identidade, com distinção entre maiúsculas e minúsculas. O nome especial "nameid" obtém o valor do NameID do SAML. Um teste de autenticação lista tudo o que é recebido.';
$string['attribute_sync'] = 'Sincronização';
$string['attribute_sync_help'] = 'Nunca: utilizado apenas para o mapeamento automático. Na criação da conta: copiado uma única vez. Em cada autenticação: mantido em sintonia com o fornecedor de identidade, normalmente com o campo bloqueado.';
$string['attribute_sync_none'] = 'Nunca';
$string['attribute_sync_oncreate'] = 'Na criação da conta';
$string['attribute_sync_onlogin'] = 'Em cada autenticação';
$string['attribute_update'] = 'Editar atributo';
$string['attribute_userfield'] = 'Campo do utilizador';
$string['attribute_userfield_help'] = 'Campo do utilizador do Moodle que recebe o valor. O nome de utilizador só é definido quando a conta de utilizador é criada.';
$string['attribute_usermapping'] = 'Utilizado para o mapeamento automático';
$string['attribute_usermapping_help'] = 'O valor tem de corresponder a exatamente um utilizador.';
$string['attribute_value'] = 'Valor';
$string['attributes_none'] = 'Ainda não há atributos mapeados.';
$string['auth_musamldescription'] = 'Os utilizadores autenticam-se através de fornecedores de identidade SAML 2.0 externos.';
$string['check_certificates'] = 'Certificados SAML';
$string['check_certificates_allok'] = 'Todos os certificados são válidos, número de certificados: {$a}.';
$string['check_certificates_disabled'] = 'A autenticação SAML está desativada.';
$string['check_certificates_expired'] = 'O certificado de {$a->name} expirou há {$a->time}.';
$string['check_certificates_none'] = 'Não foram encontrados certificados com data de validade.';
$string['check_certificates_ok'] = 'O certificado de {$a->name} expira dentro de {$a->time}.';
$string['check_certificates_soon'] = 'O certificado de {$a->name} expira dentro de {$a->time}.';
$string['error_certificate'] = 'O texto não contém certificados válidos.';
$string['error_createemail'] = 'Não é possível criar uma nova conta de utilizador, o endereço de e-mail "{$a}" não pode ser utilizado.';
$string['error_createmissing'] = 'Não é possível criar uma nova conta de utilizador, o fornecedor de identidade não enviou o campo "{$a}".';
$string['error_createusername'] = 'Não é possível criar uma nova conta de utilizador, o nome de utilizador "{$a}" não pode ser utilizado.';
$string['error_customsettings_json'] = 'As configurações personalizadas não são JSON válido: {$a}';
$string['error_customsettings_object'] = 'As configurações personalizadas têm de ser um objeto JSON cujas chaves são nomes de configurações da php-saml.';
$string['error_entityidexists'] = 'Já existe um fornecedor de identidade com o ID da entidade "{$a}".';
$string['error_entityidtoolong'] = 'O ID da entidade tem mais de 255 caracteres, este fornecedor de identidade não pode ser utilizado.';
$string['error_guidmapped'] = 'Esta conta do fornecedor de identidade já está mapeada para outro utilizador.';
$string['error_idpunavailable'] = 'Este fornecedor de identidade não está disponível.';
$string['error_logout'] = 'A mensagem de saída do fornecedor de identidade foi rejeitada: {$a}';
$string['error_metadatablocked'] = 'O URL dos metadados {$a} está bloqueado pelas configurações de segurança cURL do site, consulte "Segurança HTTP" na administração do site.';
$string['error_metadatadownload'] = 'O descarregamento dos metadados falhou: {$a}';
$string['error_metadatanourl'] = 'Os metadados colados não podem ser atualizados. Cole novos metadados nas configurações do fornecedor de identidade.';
$string['error_metadataparse'] = 'Não é possível analisar os metadados: {$a}';
$string['error_metadataurl'] = 'O URL dos metadados tem de começar por http:// ou https://';
$string['error_noguid'] = 'O fornecedor de identidade não enviou exatamente um valor do atributo de ID do utilizador "{$a}".';
$string['error_nohttps'] = 'Este site não utiliza https. As autenticações SAML só funcionam com um fornecedor de identidade neste mesmo site.';
$string['error_nomapping'] = 'Nenhum utilizador está mapeado para a conta do fornecedor de identidade "{$a}". Contacte o administrador do site.';
$string['error_norequest'] = 'Não foi encontrado nenhum pedido de autenticação pendente para esta resposta, por favor inicie novamente a autenticação.';
$string['error_otherauth'] = 'A sua conta utiliza o método de autenticação "{$a}" e não pode ser utilizada com este fornecedor de identidade.';
$string['error_replay'] = 'Esta resposta do fornecedor de identidade já foi utilizada, por favor autentique-se novamente.';
$string['error_response'] = 'A resposta do fornecedor de identidade foi rejeitada: {$a}';
$string['error_spcertinuse'] = 'O certificado do fornecedor de serviços não pode ser apagado enquanto existirem fornecedores de identidade configurados.';
$string['error_spmetadata'] = 'Os metadados do fornecedor de serviços não são válidos: {$a}';
$string['error_spnotconfigured'] = 'O certificado do fornecedor de serviços ainda não foi criado.';
$string['error_suspended'] = 'A sua conta está suspensa.';
$string['error_userfieldmapped'] = 'Este campo do utilizador já está mapeado para este fornecedor de identidade.';
$string['error_usermapped'] = 'O utilizador já está mapeado para um fornecedor de identidade.';
$string['error_usernameprefix'] = 'O prefixo só pode conter letras minúsculas, algarismos, pontos, hífenes e sublinhados, no máximo 50 caracteres.';
$string['error_usernamesync'] = 'O nome de utilizador só pode ser copiado quando a conta de utilizador é criada.';
$string['event_idp_created'] = 'Fornecedor de identidade criado';
$string['event_idp_deleted'] = 'Fornecedor de identidade apagado';
$string['event_idp_updated'] = 'Fornecedor de identidade atualizado';
$string['event_login_failed'] = 'A autenticação SAML falhou';
$string['event_metadata_refresh_failed'] = 'A atualização dos metadados do fornecedor de identidade falhou';
$string['event_user_mapping_created'] = 'Mapeamento de utilizador criado';
$string['event_user_mapping_deleted'] = 'Mapeamento de utilizador apagado';
$string['idp_advertisedattributes'] = 'Atributos nos metadados';
$string['idp_attrsimple'] = 'Nomes de atributos simples';
$string['idp_attrsimple_help'] = 'Aceitar também a última parte dos nomes de atributos em formato URI, por exemplo emailaddress para http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress.';
$string['idp_autocreate'] = 'Criar contas de utilizador automaticamente';
$string['idp_autocreate_help'] = 'São criadas novas contas de utilizador para contas desconhecidas do fornecedor de identidade na primeira autenticação. Os mapeamentos de atributos têm de fornecer, pelo menos, o nome de utilizador e o e-mail.';
$string['idp_autologin'] = 'Autenticação automática';
$string['idp_autologin_help'] = 'Quando este é o único fornecedor de identidade disponível na página de autenticação, os visitantes são imediatamente encaminhados para ele, em vez de verem o formulário de autenticação.';
$string['idp_autologin_noslo'] = 'O produto {$a} não suporta a saída única (single logout), pelo que os utilizadores permanecem autenticados no fornecedor de identidade depois de saírem deste site. A pessoa seguinte que utilizar o mesmo navegador é autenticada como esse utilizador. Não utilize a autenticação automática em computadores partilhados.';
$string['idp_automap'] = 'Mapear utilizadores existentes automaticamente';
$string['idp_automap_help'] = 'Mapear, na primeira autenticação, um utilizador com autenticação SAML e sem mapeamento, quando todos os atributos marcados para mapeamento automático correspondem ao seu perfil.';
$string['idp_autorefresh'] = 'Atualizar metadados automaticamente';
$string['idp_autorefresh_help'] = 'Os metadados são descarregados de novo uma vez por dia após uma autenticação bem-sucedida, e de imediato quando uma resposta não pode ser verificada. Desative para manter os certificados guardados.';
$string['idp_certificate_manual'] = 'Adicionado manualmente';
$string['idp_certificate_metadata'] = 'Dos metadados';
$string['idp_certificate_source'] = 'Origem';
$string['idp_certificates'] = 'Certificados de assinatura';
$string['idp_certificates_edit'] = 'Configurações dos certificados';
$string['idp_create'] = 'Adicionar fornecedor de identidade';
$string['idp_created'] = 'O fornecedor de identidade foi adicionado';
$string['idp_customsettings'] = 'Configurações personalizadas da biblioteca';
$string['idp_customsettings_help'] = 'Objeto JSON que se sobrepõe às configurações da php-saml, por exemplo {"security": {"wantAssertionsSigned": false}}. São permitidas substituições não seguras, mas é apresentado um aviso. Utilize apenas quando nada mais funcionar.';
$string['idp_delete'] = 'Apagar fornecedor de identidade';
$string['idp_delete_info'] = 'O fornecedor de identidade, os seus mapeamentos de atributos e os seus mapeamentos de utilizadores (número: {$a}) serão apagados. As contas de utilizador são mantidas.';
$string['idp_deleted'] = 'O fornecedor de identidade foi apagado';
$string['idp_enabled'] = 'Ativado';
$string['idp_entityid'] = 'ID da entidade';
$string['idp_extracerts'] = 'Certificados de assinatura adicionais';
$string['idp_extracerts_help'] = 'Certificados aceites além dos provenientes dos metadados, em formato PEM, uns a seguir aos outros. Utilize esta opção durante uma rotação de chaves, quando o fornecedor de identidade ainda não publicou o novo certificado.';
$string['idp_logouturl'] = 'URL de saída única (single logout)';
$string['idp_mapattr'] = 'Atributo de ID do utilizador';
$string['idp_mapattr_help'] = 'Atributo que contém o identificador permanente do utilizador no fornecedor de identidade. Nunca pode mudar para um utilizador nem ser reutilizado. O nome especial "nameid" obtém o valor do NameID do SAML.';
$string['idp_metadata_error'] = 'A última atualização falhou: {$a}';
$string['idp_metadata_fetched'] = 'Metadados obtidos em';
$string['idp_metadata_never'] = 'Nunca';
$string['idp_metadata_refresh'] = 'Atualizar metadados';
$string['idp_metadata_refresh_info'] = 'Os metadados são descarregados de novo a partir do URL dos metadados. O ID da entidade, os endpoints e os certificados de assinatura são substituídos pelos valores atuais.';
$string['idp_metadata_refreshed'] = 'Os metadados foram atualizados';
$string['idp_metadata_validuntil'] = 'Metadados válidos até';
$string['idp_metadatapasted'] = 'XML colado, não é atualizado automaticamente';
$string['idp_metadatasource'] = 'URL ou XML dos metadados';
$string['idp_metadatasource_help'] = 'URL dos metadados SAML 2.0 do fornecedor de identidade, ou o próprio XML dos metadados. Um URL é atualizado automaticamente, o XML colado tem de ser substituído manualmente quando os certificados mudam.';
$string['idp_metadataurl'] = 'URL dos metadados';
$string['idp_metadataurl_help'] = 'URL público dos metadados SAML 2.0 do fornecedor de identidade. O ID da entidade, o URL de autenticação única e os certificados de assinatura são lidos a partir dele e atualizados automaticamente.';
$string['idp_name'] = 'Nome';
$string['idp_name_help'] = 'Apresentado no botão da página de autenticação.';
$string['idp_nameidformat'] = 'Formato do NameID';
$string['idp_provider'] = 'Fornecedor';
$string['idp_provider_help'] = 'Pré-preenche os atributos e ajusta as configurações da biblioteca às particularidades conhecidas dos produtos. Utilize "SAML 2.0 genérico" para tudo o resto.';
$string['idp_settings_relaxed'] = 'Menos restritivo do que as predefinições do módulo, definido pelo fornecedor ou pelas configurações personalizadas da biblioteca: {$a}';
$string['idp_ssourl'] = 'URL de autenticação única (single sign-on)';
$string['idp_tenant'] = 'Inquilino';
$string['idp_tenant_help'] = 'Disponibilizado apenas na página de autenticação deste inquilino. Sem inquilino, é disponibilizado em todo o lado.';
$string['idp_update'] = 'Editar fornecedor de identidade';
$string['idp_updated'] = 'O fornecedor de identidade foi atualizado';
$string['idp_usernameprefix'] = 'Prefixo do nome de utilizador';
$string['idp_usernameprefix_help'] = 'Adicionado aos nomes de utilizador das contas de utilizador criadas e utilizado na correspondência de nomes de utilizador. Mantém os nomes de utilizador únicos entre fornecedores de identidade.';
$string['idps'] = 'Fornecedores de identidade';
$string['idps_none'] = 'Ainda não foi adicionado nenhum fornecedor de identidade.';
$string['idps_none_info'] = 'Prima "Adicionar fornecedor de identidade" e cole o URL dos respetivos metadados.';
$string['idps_none_info2'] = 'Envie os metadados do fornecedor de serviços acima indicados ao fornecedor de identidade. Qualquer um dos passos pode ser o primeiro.';
$string['import'] = 'Importar mapeamentos de utilizadores';
$string['import_column_ignore'] = 'Não utilizada';
$string['import_column_number'] = 'Coluna {$a}';
$string['import_columns'] = 'Significado das colunas';
$string['import_columns_headers'] = 'A primeira linha contém os nomes das colunas';
$string['import_columns_info'] = 'Indique o que cada coluna contém. Linhas lidas: {$a}.';
$string['import_confirm'] = 'Importar mapeamentos';
$string['import_counts'] = 'Prontos para importação: {$a->created}. Ignorados: {$a->skipped}. Problemas: {$a->error}.';
$string['import_done'] = 'Mapeamentos de utilizadores importados: {$a->created}, ignorados: {$a->skipped}.';
$string['import_error_ambiguous'] = 'Mais do que um utilizador corresponde a "{$a}".';
$string['import_error_columntwice'] = 'Cada significado de coluna só pode ser utilizado uma vez.';
$string['import_error_columnunknown'] = 'Este significado de coluna não é conhecido.';
$string['import_error_empty'] = 'Não foram encontradas linhas utilizáveis.';
$string['import_error_guidused'] = 'A conta do fornecedor de identidade "{$a}" já está mapeada para outro utilizador.';
$string['import_error_headers'] = 'Indique se a primeira linha contém os nomes das colunas.';
$string['import_error_invalid'] = 'A linha não contém um ID de conta do fornecedor de identidade e um identificador de utilizador.';
$string['import_error_mapped'] = 'O utilizador "{$a}" já está mapeado para outra conta do fornecedor de identidade.';
$string['import_error_mappedsame'] = 'O utilizador "{$a}" já tem este mapeamento.';
$string['import_error_missing'] = 'Nenhum utilizador corresponde a "{$a}".';
$string['import_error_nodatarows'] = 'Além dos nomes das colunas, não existem dados.';
$string['import_error_noguid'] = 'Uma das colunas tem de conter o ID da conta do fornecedor de identidade.';
$string['import_error_nouser'] = 'Uma coluna tem de identificar o utilizador.';
$string['import_error_otherauth'] = 'A conta de utilizador utiliza o método de autenticação "{$a}", permita outra autenticação ou mude a conta.';
$string['import_error_rowsize'] = 'Todas as linhas têm de conter o mesmo número de colunas.';
$string['import_error_suspended'] = 'O utilizador "{$a}" está suspenso.';
$string['import_failed'] = 'Nada foi importado. Linhas com problemas: {$a->error}.';
$string['import_more_rows'] = 'Linhas adicionais não apresentadas: {$a}.';
$string['import_options'] = 'Opções de importação';
$string['import_preview'] = 'Verificar os dados';
$string['import_refresh'] = 'Atualizar pré-visualização';
$string['import_result_created'] = 'Será importado';
$string['import_result_error'] = 'Problema';
$string['import_result_skipped'] = 'Ignorado';
$string['import_skip_ambiguous'] = 'Ignorar linhas que correspondem a mais do que um utilizador';
$string['import_skip_guidused'] = 'Ignorar linhas cuja conta do fornecedor de identidade já está mapeada';
$string['import_skip_info'] = 'As linhas com problemas impedem a importação, a menos que sejam ignoradas aqui. Nada é gravado até os dados terem sido verificados.';
$string['import_skip_invalid'] = 'Ignorar linhas com valores em falta';
$string['import_skip_mapped'] = 'Ignorar utilizadores que já estão mapeados';
$string['import_skip_missing'] = 'Ignorar linhas sem utilizador correspondente';
$string['import_skip_unusable'] = 'Ignorar utilizadores suspensos e utilizadores que não podem utilizar este fornecedor de identidade';
$string['import_source_info'] = 'Carregue um ficheiro CSV ou cole os dados.';
$string['musaml:managemappings'] = 'Gerir mapeamentos de utilizadores para contas de fornecedores de identidade';
$string['pluginname'] = 'Autenticação SAML';
$string['privacy:metadata:auth_musaml_login'] = 'Autenticações em curso, apagadas quando a autenticação termina e, o mais tardar, ao fim de alguns minutos';
$string['privacy:metadata:auth_musaml_login:idpid'] = 'Fornecedor de identidade';
$string['privacy:metadata:auth_musaml_login:resultjson'] = 'Atributos recebidos do fornecedor de identidade';
$string['privacy:metadata:auth_musaml_login:timecreated'] = 'Data de início da autenticação';
$string['privacy:metadata:auth_musaml_login:userid'] = 'Utilizador identificado pela autenticação';
$string['privacy:metadata:auth_musaml_user'] = 'Mapeamentos de utilizadores para contas de fornecedores de identidade';
$string['privacy:metadata:auth_musaml_user:allowotherauth'] = 'Indica se o utilizador também se pode autenticar com outro método de autenticação';
$string['privacy:metadata:auth_musaml_user:automapped'] = 'Indica se o mapeamento foi criado automaticamente na primeira autenticação';
$string['privacy:metadata:auth_musaml_user:guid'] = 'Identificador único do utilizador no fornecedor de identidade';
$string['privacy:metadata:auth_musaml_user:idpid'] = 'Fornecedor de identidade';
$string['privacy:metadata:auth_musaml_user:timecreated'] = 'Data de criação do mapeamento';
$string['privacy:metadata:auth_musaml_user:userid'] = 'Utilizador do Moodle';
$string['privacy:metadata:authsubsystem'] = 'O módulo autentica os utilizadores através do subsistema de autenticação.';
$string['provider_adfs'] = 'Active Directory Federation Services';
$string['provider_auto'] = 'Detetar a partir dos metadados';
$string['provider_entra'] = 'Microsoft Entra ID';
$string['provider_generic'] = 'SAML 2.0 genérico';
$string['provider_google'] = 'Google Workspace';
$string['provider_keycloak'] = 'Keycloak';
$string['provider_okta'] = 'Okta';
$string['provider_zitadel'] = 'Zitadel';
$string['setting_debug'] = 'Registo de depuração';
$string['setting_debug_desc'] = 'Escrever os detalhes dos pedidos e das respostas SAML no registo de erros do PHP. Não ative em sites de produção.';
$string['setting_fieldlocks_desc'] = 'Os campos bloqueados não podem ser editados pelos utilizadores. Os bloqueios aplicam-se a todos os utilizadores com autenticação SAML, mapeados ou não.';
$string['sp'] = 'Fornecedor de serviços';
$string['sp_acsurl'] = 'URL do serviço de consumo de asserções';
$string['sp_cert'] = 'Certificado';
$string['sp_cert_commonname'] = 'Nome comum';
$string['sp_cert_create'] = 'Gerar certificado';
$string['sp_cert_days'] = 'Validade em dias';
$string['sp_cert_delete'] = 'Apagar certificado';
$string['sp_cert_delete_info'] = 'O certificado do fornecedor de serviços, a respetiva chave privada e o ID da entidade são apagados.';
$string['sp_cert_download'] = 'Descarregar certificado';
$string['sp_cert_fingerprint'] = 'Impressão digital do certificado (SHA-256)';
$string['sp_cert_missing'] = 'Gere primeiro o certificado do fornecedor de serviços, os fornecedores de identidade precisam dele durante a configuração.';
$string['sp_cert_notafter'] = 'O certificado expira em';
$string['sp_cert_organizationname'] = 'Organização';
$string['sp_cert_regen'] = 'Gerar novo certificado';
$string['sp_cert_regen_warning'] = 'O certificado e a chave privada são substituídos de imediato. As autenticações falham até que todos os fornecedores de identidade tenham os novos metadados do fornecedor de serviços.';
$string['sp_cert_subject'] = 'Titular do certificado';
$string['sp_entityid'] = 'ID da entidade';
$string['sp_entityid_help'] = 'Identifica este site perante os fornecedores de identidade, por omissão é o URL dos metadados. Só pode ser alterado juntamente com o certificado, e ambos têm depois de ser registados de novo em todos os fornecedores de identidade.';
$string['sp_metadataurl'] = 'Metadados do fornecedor de serviços';
$string['sp_slourl'] = 'URL de saída única (single logout)';
$string['tab_attributes'] = 'Atributos';
$string['tab_details'] = 'Detalhes';
$string['tab_mappings'] = 'Utilizadores mapeados';
$string['tenant_all'] = 'Todos os inquilinos';
$string['test_attributes_info'] = 'Recebidos durante um teste de autenticação há {$a}.';
$string['test_login'] = 'Teste de autenticação';
$string['test_login_attributes'] = 'Atributos recebidos';
$string['test_login_nouser'] = 'Ainda não há nenhum utilizador mapeado para esta conta do fornecedor de identidade.';
$string['test_login_success'] = 'A resposta do fornecedor de identidade foi aceite. Tratou-se de um teste, ninguém foi autenticado.';
$string['test_login_user'] = 'Utilizador mapeado';
$string['user_mapping_allowotherauth'] = 'Outra autenticação permitida';
$string['user_mapping_allowotherauth_help'] = 'Permite que um utilizador com outro método de autenticação, por exemplo manual, se autentique também através do fornecedor de identidade.';
$string['user_mapping_automapped'] = 'Mapeado automaticamente';
$string['user_mapping_create'] = 'Adicionar mapeamento de utilizador';
$string['user_mapping_delete'] = 'Apagar mapeamento de utilizador';
$string['user_mapping_delete_info'] = 'O mapeamento entre o utilizador e a conta do fornecedor de identidade é removido. A conta de utilizador é mantida.';
$string['user_mapping_guid'] = 'ID da conta do fornecedor de identidade';
$string['user_mapping_guid_help'] = 'Valor do atributo de ID do utilizador, exatamente como é enviado pelo fornecedor de identidade.';
$string['user_mapping_setauth'] = 'Mudar a conta de utilizador para a autenticação SAML';
$string['user_mapping_setauth_help'] = 'Muda a conta de utilizador para a autenticação SAML. Deixe desmarcado para manter o método atual e, em alternativa, permita outra autenticação.';
$string['user_mapping_timecreated'] = 'Mapeado em';
$string['user_mapping_update'] = 'Editar mapeamento de utilizador';
$string['user_mapping_user'] = 'Utilizador';
$string['user_mapping_user_help'] = 'Os utilizadores já mapeados para um fornecedor de identidade não são listados.';
$string['user_mappings'] = 'Utilizadores mapeados';
$string['user_mappings_none'] = 'Ainda não há utilizadores mapeados.';
