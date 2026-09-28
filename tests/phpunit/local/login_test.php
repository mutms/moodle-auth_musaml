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

// phpcs:disable moodle.Commenting.DocblockDescription.Missing

namespace auth_musaml\phpunit\local;

use auth_musaml\local\attribute;
use auth_musaml\local\idp;
use auth_musaml\local\login;
use auth_musaml\local\mapping;
use auth_musaml\local\saml;

/**
 * Login helper test.
 *
 * @group      MuTMS
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \auth_musaml\local\login
 */
final class login_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Plugin generator.
     *
     * @return \auth_musaml_generator
     */
    private function get_generator(): \auth_musaml_generator {
        return $this->getDataGenerator()->get_plugin_generator('auth_musaml');
    }

    public function test_short_name(): void {
        $long = 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress';
        $this->assertSame('emailaddress', login::get_short_name($long));
        $this->assertSame('0.9.2342.19200300.100.1.3', login::get_short_name('urn:oid:0.9.2342.19200300.100.1.3'));
        $this->assertSame('Email', login::get_short_name('Email'));
        $this->assertSame('x/', login::get_short_name('x/'));
    }

    public function test_attribute_bag(): void {
        $idp = $this->get_generator()->create_idp(['attrsimple' => 0, 'mapattr' => 'UserID']);
        $attributes = [
            'UserID' => ['123'],
            'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress' => ['a@example.com'],
            'groups' => ['g1', 'g2'],
            'empty' => [],
        ];

        $bag = login::build_attribute_bag($attributes, 'nameid-value', $idp);
        $this->assertSame(['123'], $bag['UserID']);
        $this->assertSame(['g1', 'g2'], $bag['groups']);
        $this->assertSame([], $bag['empty']);
        $this->assertSame(['nameid-value'], $bag['nameid']);
        $this->assertArrayNotHasKey('emailaddress', $bag);
        $this->assertSame('123', login::get_guid($bag, $idp));

        $idp->attrsimple = 1;
        $bag = login::build_attribute_bag($attributes, null, $idp);
        $this->assertSame(['a@example.com'], $bag['emailaddress']);
        $this->assertArrayHasKey('http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress', $bag);
        $this->assertArrayNotHasKey('nameid', $bag);

        // Short names never overwrite real attributes.
        $attributes['emailaddress'] = ['real@example.com'];
        $bag = login::build_attribute_bag($attributes, '', $idp);
        $this->assertSame(['real@example.com'], $bag['emailaddress']);
        $this->assertArrayNotHasKey('nameid', $bag);
    }

    public function test_guid(): void {
        $idp = $this->get_generator()->create_idp(['mapattr' => 'nameid']);
        $this->assertSame('abc', login::get_guid(['nameid' => [' abc ']], $idp));
        $this->assertNull(login::get_guid([], $idp));
        $this->assertNull(login::get_guid(['nameid' => ['']], $idp));
        $this->assertNull(login::get_guid(['nameid' => ['a', 'b']], $idp), 'multiple values are ambiguous');
    }

    public function test_idp_availability(): void {
        $gen = $this->get_generator();
        $this->assertTrue(login::is_idp_available($gen->create_idp(['enabled' => 1])));
        $this->assertFalse(login::is_idp_available($gen->create_idp(['enabled' => 0])));
        $this->assertFalse(login::is_idp_available($gen->create_idp(['tenantid' => 5])), 'no tenancy means no tenant IDPs');
        $this->assertNull(login::get_current_tenantid());
    }

    public function test_archived_tenant_idp_is_not_available(): void {
        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            $this->markTestSkipped('tool_mutenancy is not installed');
        }
        $gen = $this->get_generator();
        /** @var \tool_mutenancy_generator $tenancygen */
        $tenancygen = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        $tenant = $tenancygen->create_tenant();
        $idp = $gen->create_idp(['tenantid' => $tenant->id]);
        $siteidp = $gen->create_idp();

        \tool_mutenancy\local\tenancy::force_current_tenantid($tenant->id);
        $this->assertTrue(login::is_idp_available($idp));
        $this->assertEqualsCanonicalizing([$siteidp->id, $idp->id], array_keys(idp::get_enabled($tenant->id)));

        \tool_mutenancy\local\tenant::archive($tenant->id);
        $idp = idp::fetch($idp->id);

        $this->assertTrue(idp::is_tenant_archived($tenant->id));
        $this->assertFalse(login::is_idp_available($idp), 'archived tenant identity providers are withdrawn');
        $this->assertEquals([$siteidp->id], array_keys(idp::get_enabled($tenant->id)));
        $this->assertTrue(login::is_idp_available($siteidp), 'site wide identity providers stay');
    }

    public function test_tenant_idp_response_is_accepted_without_a_tenant(): void {
        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            $this->markTestSkipped('tool_mutenancy is not installed');
        }
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        /** @var \tool_mutenancy_generator $tenancygen */
        $tenancygen = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        $tenant = $tenancygen->create_tenant();
        $idp = $gen->create_idp(['name' => 'Tenant IDP', 'tenantid' => $tenant->id]);

        // The login starts on the tenant login page.
        \tool_mutenancy\local\tenancy::switch($tenant->id);
        $this->assertTrue(login::is_idp_available($idp));

        // The response arrives at the ACS endpoint, which has no session and no tenant
        // cookie, so the tenant is unknown there.
        \tool_mutenancy\local\tenancy::force_current_tenantid(null);
        $this->assertNull(login::get_current_tenantid());
        $this->assertFalse(login::is_idp_available($idp), 'not offered on the main login page');
        $this->assertTrue(login::is_idp_usable($idp), 'the response of a started login is still accepted');

        // A disabled identity provider is refused wherever the response lands.
        idp::update((object)['id' => $idp->id, 'enabled' => 0]);
        $this->assertFalse(login::is_idp_usable(idp::fetch($idp->id)));

        // So is one of a tenant that is being wound down.
        idp::update((object)['id' => $idp->id, 'enabled' => 1]);
        \tool_mutenancy\local\tenant::archive($tenant->id);
        $this->assertFalse(login::is_idp_usable(idp::fetch($idp->id)));
    }

    public function test_tenant_login_completes_for_a_tenant_user(): void {
        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            $this->markTestSkipped('tool_mutenancy is not installed');
        }
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        /** @var \tool_mutenancy_generator $tenancygen */
        $tenancygen = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        $tenant = $tenancygen->create_tenant();
        $idp = $gen->create_idp(['tenantid' => $tenant->id, 'autocreate' => 1]);
        $fields = ['UserName' => 'username', 'Email' => 'email', 'FirstName' => 'firstname', 'SurName' => 'lastname'];
        foreach ($fields as $idpattr => $userfield) {
            $gen->create_attribute(['idpid' => $idp->id, 'idpattr' => $idpattr, 'userfield' => $userfield,
                'sync' => attribute::SYNC_ONCREATE]);
        }

        $bag = [
            'UserID' => ['t-1'],
            'UserName' => ['tenantnewbie'],
            'Email' => ['tenantnewbie@example.com'],
            'FirstName' => ['Tenant'],
            'SurName' => ['Newbie'],
        ];

        // No tenant is known where the response is processed, the account is still created.
        \tool_mutenancy\local\tenancy::force_current_tenantid(null);
        $user = login::authenticate($idp, $bag);

        $this->assertSame('tenantnewbie', $user->username);
        $this->assertEquals($tenant->id, $user->tenantid, 'new users belong to the tenant of the identity provider');
        $this->assertSame($idp->id, mapping::fetch_by_userid($user->id)->idpid);
    }

    public function test_tenant_autocreated_user_cannot_be_reused_by_another_tenant(): void {
        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            $this->markTestSkipped('tool_mutenancy is not installed');
        }
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        /** @var \tool_mutenancy_generator $tenancygen */
        $tenancygen = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        $one = $tenancygen->create_tenant();
        $two = $tenancygen->create_tenant();
        $idpone = $gen->create_idp(['name' => 'One', 'tenantid' => $one->id, 'autocreate' => 1]);
        $idptwo = $gen->create_idp(['name' => 'Two', 'tenantid' => $two->id, 'autocreate' => 1, 'automap' => 1]);
        $fields = ['UserName' => 'username', 'Email' => 'email', 'FirstName' => 'firstname', 'SurName' => 'lastname'];
        foreach ([$idpone, $idptwo] as $idp) {
            foreach ($fields as $idpattr => $userfield) {
                $gen->create_attribute(['idpid' => $idp->id, 'idpattr' => $idpattr, 'userfield' => $userfield,
                    'sync' => attribute::SYNC_ONCREATE, 'usermapping' => ($userfield === 'username' ? 1 : 0)]);
            }
        }

        $bag = [
            'UserID' => ['t-1'],
            'UserName' => ['sharedname'],
            'Email' => ['sharedname@example.com'],
            'FirstName' => ['Shared'],
            'SurName' => ['Name'],
        ];

        $user = login::authenticate($idpone, $bag);
        $this->assertEquals($one->id, $user->tenantid);

        // The second tenant sends the same person, the account of the first tenant is
        // taken, the identity provider of a tenant must not reach into another one.
        $this->assert_fails(fn() => login::authenticate($idptwo, ['UserID' => ['t-2']] + $bag), 'error_createusername');
    }

    public function test_tenant_idp_is_offered_on_tenant_login_pages_only(): void {
        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            $this->markTestSkipped('tool_mutenancy is not installed');
        }
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        /** @var \tool_mutenancy_generator $tenancygen */
        $tenancygen = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        $tenant = $tenancygen->create_tenant();
        $tenantidp = $gen->create_idp(['name' => 'Tenant IDP', 'tenantid' => $tenant->id]);
        $siteidp = $gen->create_idp(['name' => 'Site IDP']);
        $authplugin = get_auth_plugin('musaml');

        // The main login page knows no tenant, only site wide identity providers are offered.
        $this->assertNull(login::get_current_tenantid());
        $this->assertSame(['Site IDP'], array_column($authplugin->loginpage_idp_list('/'), 'name'));
        $this->assertFalse(login::is_idp_available($tenantidp));

        // The tenant login page offers both.
        \tool_mutenancy\local\tenancy::switch($tenant->id);
        $names = array_column($authplugin->loginpage_idp_list('/'), 'name');
        $this->assertEqualsCanonicalizing(['Site IDP', 'Tenant IDP'], $names);
        $this->assertTrue(login::is_idp_available($tenantidp));
        $this->assertTrue(login::is_idp_available($siteidp));
    }

    public function test_logout_url(): void {
        global $SESSION;
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        $idp = $gen->create_idp();

        // Nothing to do without a SAML session.
        $this->assertNull(login::get_session());
        $this->assertNull(login::get_logout_url());

        $user = $this->getDataGenerator()->create_user(['auth' => 'musaml']);
        $gen->create_user_mapping(['idpid' => $idp->id, 'userid' => $user->id, 'guid' => 'z-1']);
        @$this->login_user($idp, ['UserID' => ['z-1']]);

        $session = login::get_session();
        $this->assertEquals($idp->id, $session['idpid']);
        $this->assertSame('n', $session['nameid']);
        $this->assertSame('idx', $session['sessionindex']);

        $url = login::get_logout_url();
        $this->assertStringStartsWith($idp->logouturl . '?SAMLRequest=', $url);
        $this->assertStringContainsString('&Signature=', $url, 'logout requests are signed');

        // Products that refuse logout requests are skipped.
        idp::update((object)['id' => $idp->id, 'provider' => 'zitadel']);
        $this->assertNull(login::get_logout_url());
        idp::update((object)['id' => $idp->id, 'provider' => 'generic']);
        $this->assertNotNull(login::get_logout_url());

        // An identity provider without a logout service is skipped.
        idp::update((object)['id' => $idp->id, 'metadataurl' => $idp->metadataurl]);
        $DB = $GLOBALS['DB'];
        $DB->set_field('auth_musaml_idp', 'logouturl', null, ['id' => $idp->id]);
        $this->assertNull(login::get_logout_url());

        // A gone identity provider does not break logging out either.
        $SESSION->auth_musaml_session['idpid'] = -1;
        $this->assertNull(login::get_logout_url());
    }

    public function test_logout_redirect_without_single_logout(): void {
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        $idp = $gen->create_idp(['provider' => 'zitadel', 'autologin' => 1]);
        $user = $this->getDataGenerator()->create_user(['auth' => 'musaml']);
        $gen->create_user_mapping(['idpid' => $idp->id, 'userid' => $user->id, 'guid' => 'z-1']);

        $this->assertNull(login::get_logout_redirect(), 'nothing to do without a SAML session');

        @$this->login_user($idp, ['UserID' => ['z-1']]);

        // Zitadel refuses logout requests, so the user would be signed straight back in.
        $this->assertNull(login::get_logout_url());
        $this->assertSame(login::get_retry_url(), login::get_logout_redirect());

        // Without automatic login the normal logout target is fine.
        idp::update((object)['id' => $idp->id, 'autologin' => 0]);
        $this->assertNull(login::get_logout_redirect());
    }

    public function test_process_logout_rejects_garbage(): void {
        saml::regenerate_sp_certificate();
        $idp = $this->get_generator()->create_idp();

        $_GET['SAMLRequest'] = base64_encode('nonsense');
        try {
            login::process_logout($idp);
            $this->fail('exception expected');
        } catch (\core\exception\moodle_exception $e) {
            $this->assertSame('error_logout', $e->errorcode);
        } finally {
            unset($_GET['SAMLRequest']);
        }
    }

    public function test_autologin_idp(): void {
        global $SESSION;
        $gen = $this->get_generator();
        $auth = get_auth_plugin('musaml');
        set_config('auth', 'musaml');

        // Nothing is configured yet.
        $this->assertNull(login::get_autologin_idp());

        saml::regenerate_sp_certificate();
        $idp = $gen->create_idp(['autologin' => 1]);
        $found = login::get_autologin_idp();
        $this->assertNotNull($found);
        $this->assertEquals($idp->id, $found->id);

        // The flag has to be set on the identity provider.
        idp::update((object)['id' => $idp->id, 'autologin' => 0]);
        $this->assertNull(login::get_autologin_idp());
        idp::update((object)['id' => $idp->id, 'autologin' => 1]);
        $this->assertNotNull(login::get_autologin_idp());

        // A second identity provider means the visitor has to choose.
        $second = $gen->create_idp(['autologin' => 1]);
        $this->assertNull(login::get_autologin_idp());
        idp::update((object)['id' => $second->id, 'enabled' => 0]);
        $this->assertNotNull(login::get_autologin_idp());

        // A failed login turns the redirect off for the rest of the session.
        $SESSION->auth_musaml_noautologin = true;
        $this->assertNull(login::get_autologin_idp());
        unset($SESSION->auth_musaml_noautologin);

        // Somebody submitting the login form is not redirected away from it.
        $_POST['username'] = 'someone';
        $this->assertNull(login::get_autologin_idp());
        unset($_POST['username']);
        $this->assertNotNull(login::get_autologin_idp());

        // Logged in users and a disabled plugin are left alone.
        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertNull(login::get_autologin_idp());
        $this->setUser(null);
        set_config('auth', '');
        \core\di::set(\core\authentication::class, new \core\authentication());
        $this->assertNull(login::get_autologin_idp());
        $this->assertNotNull($auth);
    }

    public function test_autologin_is_rate_limited(): void {
        saml::regenerate_sp_certificate();
        set_config('auth', 'musaml');
        $gen = $this->get_generator();
        $gen->create_idp(['autologin' => 1]);

        // A lost session cannot stop a redirect loop, the counter lives in a cookie.
        $this->assertFalse(login::is_autologin_rate_limited());
        for ($i = 0; $i < login::AUTOLOGIN_LIMIT; $i++) {
            $this->assertNotNull(login::get_autologin_idp(), 'redirect ' . $i);
            login::count_autologin_redirect();
        }

        $this->assertTrue(login::is_autologin_rate_limited());
        $this->assertNull(login::get_autologin_idp(), 'the loop is broken');

        // The window is short, an old counter does not block a later visit.
        $_COOKIE[login::AUTOLOGIN_COOKIE] = login::AUTOLOGIN_LIMIT . ':' . (time() - login::AUTOLOGIN_WINDOW - 1);
        $this->assertFalse(login::is_autologin_rate_limited());

        // Rubbish in the cookie is ignored.
        $_COOKIE[login::AUTOLOGIN_COOKIE] = 'nonsense';
        $this->assertFalse(login::is_autologin_rate_limited());
        unset($_COOKIE[login::AUTOLOGIN_COOKIE]);
    }

    public function test_successful_login_clears_the_rate_limit(): void {
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        $idp = $gen->create_idp(['mapattr' => 'UserID', 'autologin' => 1]);
        $user = $this->getDataGenerator()->create_user(['auth' => 'musaml']);
        $gen->create_user_mapping(['idpid' => $idp->id, 'userid' => $user->id, 'guid' => 'z-1']);

        $_COOKIE[login::AUTOLOGIN_COOKIE] = login::AUTOLOGIN_LIMIT . ':' . time();
        $this->assertTrue(login::is_autologin_rate_limited());

        @$this->login_user($idp, ['UserID' => ['z-1']]);

        $this->assertFalse(login::is_autologin_rate_limited(), 'a login that works resets the counter');
    }

    public function test_autologin_never_uses_another_tenant_idp(): void {
        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            $this->markTestSkipped('tool_mutenancy is not installed');
        }
        saml::regenerate_sp_certificate();
        set_config('auth', 'musaml');
        $gen = $this->get_generator();
        /** @var \tool_mutenancy_generator $tenancygen */
        $tenancygen = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        $tenant = $tenancygen->create_tenant();
        $tenantidp = $gen->create_idp(['tenantid' => $tenant->id, 'autologin' => 1]);

        // The main login page has no identity provider of its own, nobody is redirected.
        $this->assertNull(login::get_autologin_idp());

        // The tenant login page redirects to the identity provider of that tenant.
        \tool_mutenancy\local\tenancy::switch($tenant->id);
        $found = login::get_autologin_idp();
        $this->assertNotNull($found);
        $this->assertEquals($tenantidp->id, $found->id);

        // A site wide identity provider next to it means a choice again.
        $gen->create_idp(['autologin' => 1]);
        $this->assertNull(login::get_autologin_idp());
    }

    public function test_login_page_icons_come_from_the_provider(): void {
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        $gen->create_idp(['provider' => 'zitadel']);
        $auth = get_auth_plugin('musaml');

        $list = $auth->loginpage_idp_list('/');
        $this->assertCount(1, $list);
        $expected = \auth_musaml\local\provider\zitadel::get_icon();
        $this->assertStringContainsString($expected->pix, (string)$list[0]['iconurl']);
    }

    public function test_nologin_account_cannot_use_the_identity_provider(): void {
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        $idp = $gen->create_idp(['mapattr' => 'UserID']);
        $blocked = $this->getDataGenerator()->create_user(['auth' => 'nologin']);
        $gen->create_user_mapping(['idpid' => $idp->id, 'userid' => $blocked->id, 'guid' => 'z-1', 'allowotherauth' => 1]);

        // The nologin plugin is always enabled, it must not become a way in.
        $this->assertTrue(is_enabled_auth('nologin'));
        $sink = $this->redirectEvents();
        $this->assert_fails(fn() => login::authenticate($idp, ['UserID' => ['z-1']]), 'error_otherauth');
        $sink->close();
    }

    public function test_login_url(): void {
        global $CFG;
        $idp = $this->get_generator()->create_idp();
        $this->assertSame($CFG->wwwroot . '/auth/musaml/login.php?id=' . $idp->id, login::get_login_url($idp)->out(false));
        $url = login::get_login_url($idp, '/course/view.php?id=2');
        $this->assertSame('/course/view.php?id=2', $url->get_param('wantsurl'));
    }

    public function test_start_and_pop_request(): void {
        global $DB;
        saml::regenerate_sp_certificate();
        $idp = $this->get_generator()->create_idp();

        $this->assertNull(login::fetch_request());

        $url = login::start($idp, '/my/');
        $this->assertStringStartsWith($idp->ssourl . '?SAMLRequest=', $url);
        $this->assertStringContainsString('&Signature=', $url, 'authn requests are signed');
        $this->assertStringContainsString('SigAlg=' . urlencode('http://www.w3.org/2001/04/xmldsig-more#rsa-sha256'), $url);

        // The response arrives without a session, so the pending login is a row and a cookie.
        $request = login::fetch_request();
        $this->assertEquals($idp->id, $request->idpid);
        $this->assertStringStartsWith('ONELOGIN_', $request->requestid);
        $this->assertSame('/my/', $request->wantsurl);
        $this->assertSame(0, (int)$request->test);
        $this->assertSame(0, (int)$request->ready);
        $this->assertSame($request->token, $_COOKIE[login::REQUEST_COOKIE]);

        login::start($idp, '', true);
        $this->assertSame(1, (int)login::fetch_request()->test);

        // Nothing is handed over before a response was accepted.
        $this->assertNull(login::pop_result());
        $this->assertNull(login::fetch_request(), 'the cookie is cleared with the row');

        login::start($idp, '/my/');
        $request = login::fetch_request();
        login::store_result($request, ['bag' => ['UserID' => ['z-1']]], 7);
        $finished = login::pop_result();
        $this->assertSame(7, (int)$finished['request']->userid);
        $this->assertSame(['UserID' => ['z-1']], $finished['result']['bag']);
        $this->assertNull(login::pop_result(), 'the result is used once');

        login::start($idp);
        $request = login::fetch_request();
        $DB->set_field('auth_musaml_login', 'timeexpires', time() - 1, ['id' => $request->id]);
        $this->assertNull(login::fetch_request(), 'expired request is dropped');
    }

    public function test_retry_url_disables_autologin(): void {
        global $SESSION, $CFG;

        $this->assertSame($CFG->wwwroot . '/login/index.php?musaml=off', login::get_retry_url());
        $this->assertTrue(login::is_autologin_allowed());

        // The parameter of the retry URL turns it off for the rest of the session.
        $_GET['musaml'] = 'off';
        $this->assertFalse(login::is_autologin_allowed());
        unset($_GET['musaml']);
        $this->assertFalse(login::is_autologin_allowed(), 'stays off without the parameter');

        $_GET['musaml'] = 'on';
        $this->assertFalse(login::is_autologin_allowed(), 'only a new session turns it back on');
        unset($_GET['musaml']);

        unset($SESSION->auth_musaml_noautologin);
        $this->assertTrue(login::is_autologin_allowed());
    }

    public function test_successful_login_allows_autologin_again(): void {
        global $SESSION;
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        $idp = $gen->create_idp(['mapattr' => 'UserID']);
        $user = $this->getDataGenerator()->create_user(['auth' => 'musaml']);
        $gen->create_user_mapping(['idpid' => $idp->id, 'userid' => $user->id, 'guid' => 'z-1']);
        $SESSION->auth_musaml_noautologin = true;

        @$this->login_user($idp, ['UserID' => ['z-1']]);

        $this->assertTrue(login::is_autologin_allowed());
    }

    public function test_resolve_user_and_complete(): void {
        global $SESSION, $CFG;
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        $idp = $gen->create_idp(['mapattr' => 'UserID']);
        $user = $this->getDataGenerator()->create_user(['auth' => 'musaml', 'firstname' => 'Mapped']);
        $gen->create_user_mapping(['idpid' => $idp->id, 'userid' => $user->id, 'guid' => 'z-1']);

        $this->assertNull(login::resolve_user($idp, 'unknown', []));
        $this->assertEquals($user->id, login::resolve_user($idp, 'z-1', [])->id);

        // Silenced like core does for complete_user_login(), there is no PHP session in tests.
        $url = @$this->login_user($idp, ['UserID' => ['z-1'], 'nameid' => ['n']], '/course/index.php');

        $this->assertSame($user->id, $GLOBALS['USER']->id, 'user is logged in');
        $this->assertSame($CFG->wwwroot . '/course/index.php', $url->out(false));
        $absolute = @$this->login_user($idp, ['UserID' => ['z-1']], $CFG->wwwroot . '/my/');
        $this->assertSame($CFG->wwwroot . '/my/', $absolute->out(false), 'absolute local url is not doubled');
        $this->assertSame($idp->id, $SESSION->auth_musaml_session['idpid']);
        $this->assertSame('n', $SESSION->auth_musaml_session['nameid']);
        $this->assertSame('idx', $SESSION->auth_musaml_session['sessionindex']);
        $this->assertFalse(isset($SESSION->wantsurl), 'wantsurl consumed by core_login_get_return_url');
    }

    public function test_complete_syncs_attributes(): void {
        global $DB;
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        $idp = $gen->create_idp(['mapattr' => 'UserID']);
        attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'Email', 'userfield' => 'email',
            'sync' => attribute::SYNC_ONLOGIN]);
        attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'FirstName', 'userfield' => 'firstname',
            'sync' => attribute::SYNC_ONCREATE]);
        $user = $this->getDataGenerator()->create_user(['auth' => 'musaml', 'email' => 'old@example.com',
            'firstname' => 'Old']);
        $gen->create_user_mapping(['idpid' => $idp->id, 'userid' => $user->id, 'guid' => 'z-1']);

        @$this->login_user($idp, ['UserID' => ['z-1'], 'Email' => ['new@example.com'], 'FirstName' => ['New']]);

        $stored = $DB->get_record('user', ['id' => $user->id]);
        $this->assertSame('new@example.com', $stored->email, 'login sync applied');
        $this->assertSame('Old', $stored->firstname, 'creation only field untouched');
        $this->assertSame($user->id, $GLOBALS['USER']->id);
        $this->assertSame('new@example.com', $GLOBALS['USER']->email, 'session has fresh data');
    }

    public function test_dual_login_keeps_local_account_data(): void {
        global $DB;
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        $idp = $gen->create_idp(['mapattr' => 'UserID']);
        attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'Email', 'userfield' => 'email',
            'sync' => attribute::SYNC_ONLOGIN]);

        $manual = $this->getDataGenerator()->create_user(['auth' => 'manual', 'email' => 'local@example.com']);
        $gen->create_user_mapping(['idpid' => $idp->id, 'userid' => $manual->id, 'guid' => 'z-1', 'allowotherauth' => 1]);
        $bag = ['UserID' => ['z-1'], 'Email' => ['idp@example.com']];

        @$this->login_user($idp, $bag);

        $this->assertSame($manual->id, $GLOBALS['USER']->id, 'dual login is allowed');
        $stored = $DB->get_record('user', ['id' => $manual->id]);
        $this->assertSame('manual', $stored->auth, 'the account keeps its own method');
        $this->assertSame('local@example.com', $stored->email, 'local data is not overwritten');

        // The same account after switching to SAML authentication is synchronised.
        \auth_musaml\local\mapping::set_user_auth($manual->id);
        $user = $DB->get_record('user', ['id' => $manual->id]);
        @$this->login_user($idp, $bag);
        $this->assertSame('idp@example.com', $DB->get_field('user', 'email', ['id' => $user->id]));
    }

    public function test_dual_login_requires_the_flag_and_enabled_plugin(): void {
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        $idp = $gen->create_idp(['mapattr' => 'UserID']);
        $manual = $this->getDataGenerator()->create_user(['auth' => 'manual']);
        $mapping = $gen->create_user_mapping(['idpid' => $idp->id, 'userid' => $manual->id, 'guid' => 'z-1']);
        $bag = ['UserID' => ['z-1']];

        $sink = $this->redirectEvents();
        $this->assert_fails(fn() => login::authenticate($idp, $bag), 'error_otherauth');
        $sink->close();

        \auth_musaml\local\mapping::update((object)['id' => $mapping->id, 'allowotherauth' => 1]);
        $url = @$this->login_user($idp, $bag);
        $this->assertNotEmpty($url);

        // An account using a disabled authentication method cannot log in either,
        // manual is always enabled in Moodle so another plugin is used here.
        $this->setUser(null);
        $this->assertFalse(is_enabled_auth('shibboleth'));
        $disabled = $this->getDataGenerator()->create_user(['auth' => 'shibboleth']);
        $gen->create_user_mapping(['idpid' => $idp->id, 'userid' => $disabled->id, 'guid' => 'z-2', 'allowotherauth' => 1]);

        $sink = $this->redirectEvents();
        $this->assert_fails(fn() => login::authenticate($idp, ['UserID' => ['z-2']]), 'error_otherauth');
        $sink->close();
    }

    public function test_complete_replaces_another_session(): void {
        global $SESSION;
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        $idp = $gen->create_idp(['mapattr' => 'UserID']);
        $user = $this->getDataGenerator()->create_user(['auth' => 'musaml']);
        $gen->create_user_mapping(['idpid' => $idp->id, 'userid' => $user->id, 'guid' => 'z-1']);

        $other = $this->getDataGenerator()->create_user();
        $this->setUser($other);
        $SESSION->leftover = 'from the other user';

        @$this->login_user($idp, ['UserID' => ['z-1']]);

        $this->assertSame($user->id, $GLOBALS['USER']->id);
        $this->assertFalse(isset($SESSION->leftover), 'session of the previous user is dropped');
        $this->assertSame($idp->id, $SESSION->auth_musaml_session['idpid']);
    }

    public function test_complete_replaces_guest_session(): void {
        global $SESSION;
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        $idp = $gen->create_idp(['mapattr' => 'UserID']);
        $user = $this->getDataGenerator()->create_user(['auth' => 'musaml']);
        $gen->create_user_mapping(['idpid' => $idp->id, 'userid' => $user->id, 'guid' => 'z-1']);

        // Guests have a real user id, so isloggedin() is true for them too.
        $this->setGuestUser();
        $this->assertTrue(isloggedin());
        $SESSION->leftover = 'from the guest';

        @$this->login_user($idp, ['UserID' => ['z-1']]);

        $this->assertSame($user->id, $GLOBALS['USER']->id);
        $this->assertFalse(isset($SESSION->leftover), 'guest session is dropped too');
    }

    public function test_automap_matches_single_user(): void {
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        $idp = $gen->create_idp(['mapattr' => 'UserID', 'automap' => 1, 'usernameprefix' => 'zit']);
        attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'UserName', 'userfield' => 'username',
            'sync' => attribute::SYNC_ONCREATE, 'usermapping' => 1]);
        attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'Email', 'userfield' => 'email',
            'sync' => attribute::SYNC_ONLOGIN, 'usermapping' => 1]);

        $match = $this->getDataGenerator()->create_user([
            'auth' => 'musaml', 'username' => 'zitjane', 'email' => 'Jane@Example.com']);
        // Same username but another auth, another email, and one already mapped.
        $this->getDataGenerator()->create_user(['auth' => 'manual', 'username' => 'jane']);
        $this->getDataGenerator()->create_user(['auth' => 'musaml', 'username' => 'zitjohn', 'email' => 'john@example.com']);

        $bag = ['UserName' => ['Jane'], 'Email' => ['jane@example.com']];
        $found = login::find_automap_user($idp, $bag);
        $this->assertNotNull($found);
        $this->assertSame($match->id, $found->id);

        // The mapping is created on resolve and used from then on.
        $user = login::resolve_user($idp, 'guid-1', $bag);
        $this->assertSame($match->id, $user->id);
        $mapping = \auth_musaml\local\mapping::fetch_by_guid($idp->id, 'guid-1');
        $this->assertSame('1', $mapping->automapped);
        $this->assertNull(login::find_automap_user($idp, $bag), 'mapped users are not candidates any more');
        $this->assertSame($match->id, login::resolve_user($idp, 'guid-1', [])->id);
    }

    public function test_automap_requires_all_attributes_and_uniqueness(): void {
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        $idp = $gen->create_idp(['mapattr' => 'UserID', 'automap' => 1]);

        $this->getDataGenerator()->create_user(['auth' => 'musaml', 'username' => 'jane', 'email' => 'jane@example.com']);
        $this->assertNull(login::find_automap_user($idp, ['UserName' => ['jane']]), 'no usermapping attributes');

        attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'UserName', 'userfield' => 'username',
            'sync' => attribute::SYNC_NONE, 'usermapping' => 1]);
        attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'Email', 'userfield' => 'email',
            'sync' => attribute::SYNC_NONE, 'usermapping' => 1]);

        $this->assertNull(login::find_automap_user($idp, ['UserName' => ['jane']]), 'missing email attribute');
        $this->assertNull(login::find_automap_user($idp, ['UserName' => ['jane'], 'Email' => ['']]), 'empty value');
        $mismatch = ['UserName' => ['jane'], 'Email' => ['other@example.com']];
        $this->assertNull(login::find_automap_user($idp, $mismatch), 'all attributes must match the same user');
        $this->assertNotNull(login::find_automap_user($idp, ['UserName' => ['jane'], 'Email' => ['jane@example.com']]));

        // Two candidates are ambiguous, nobody is mapped.
        $this->getDataGenerator()->create_user(['auth' => 'musaml', 'username' => 'jane2', 'email' => 'jane@example.com']);
        attribute::delete_for_idp($idp->id);
        attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'Email', 'userfield' => 'email',
            'sync' => attribute::SYNC_NONE, 'usermapping' => 1]);
        $this->assertNull(login::find_automap_user($idp, ['Email' => ['jane@example.com']]));
    }

    public function test_automap_matches_profile_field(): void {
        global $DB;
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        $idp = $gen->create_idp(['mapattr' => 'UserID', 'automap' => 1]);

        $DB->insert_record('user_info_field', (object)['shortname' => 'staffid', 'name' => 'Staff ID',
            'categoryid' => 1, 'datatype' => 'text', 'sortorder' => 1, 'required' => 0, 'locked' => 0,
            'visible' => 2, 'forceunique' => 0, 'signup' => 0, 'defaultdata' => '', 'param1' => 30, 'param2' => 255]);
        attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'StaffId', 'userfield' => 'profile_staffid',
            'sync' => attribute::SYNC_NONE, 'usermapping' => 1]);

        $user = $this->getDataGenerator()->create_user(['auth' => 'musaml', 'profile_field_staffid' => 'S-42']);
        $this->getDataGenerator()->create_user(['auth' => 'musaml']);

        $found = login::find_automap_user($idp, ['StaffId' => ['S-42']]);
        $this->assertNotNull($found);
        $this->assertSame($user->id, $found->id);
        $this->assertNull(login::find_automap_user($idp, ['StaffId' => ['S-99']]));
    }

    public function test_autocreate(): void {
        global $DB, $CFG;
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        $idp = $gen->create_idp(['mapattr' => 'UserID', 'autocreate' => 1, 'usernameprefix' => 'zit']);
        foreach ([['UserName', 'username'], ['Email', 'email'], ['FirstName', 'firstname'], ['SurName', 'lastname']] as $row) {
            attribute::create((object)['idpid' => $idp->id, 'idpattr' => $row[0], 'userfield' => $row[1],
                'sync' => attribute::SYNC_ONCREATE]);
        }
        attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'Dept', 'userfield' => 'department',
            'sync' => attribute::SYNC_NONE]);

        $bag = ['UserName' => ['NewBie'], 'Email' => ['New@Example.com'], 'FirstName' => ['New'],
            'SurName' => ['Bie'], 'Dept' => ['Sales']];
        $sink = $this->redirectEvents();
        $user = login::resolve_user($idp, 'guid-new', $bag);

        $this->assertSame('zitnewbie', $user->username, 'prefixed and lowercased');
        $this->assertSame('new@example.com', $user->email, 'lowercased');
        $this->assertSame('New', $user->firstname);
        $this->assertSame('Bie', $user->lastname);
        $this->assertSame('', $user->department, 'sync none is not copied');
        $this->assertSame('musaml', $user->auth);
        $this->assertSame('1', $user->confirmed);
        $this->assertEquals($CFG->mnet_localhost_id, $user->mnethostid);
        $this->assertFalse($DB->record_exists('user_password_history', ['userid' => $user->id]));

        $mapping = \auth_musaml\local\mapping::fetch_by_guid($idp->id, 'guid-new');
        $this->assertEquals($user->id, $mapping->userid);
        $this->assertSame('0', $mapping->automapped);

        $events = array_filter($sink->get_events(), fn($e) => $e instanceof \core\event\user_created);
        $this->assertCount(1, $events);
        $sink->close();
    }

    public function test_autocreate_refuses_bad_data(): void {
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        $idp = $gen->create_idp(['mapattr' => 'UserID', 'autocreate' => 1]);
        foreach ([['UserName', 'username'], ['Email', 'email'], ['FirstName', 'firstname'], ['SurName', 'lastname']] as $row) {
            attribute::create((object)['idpid' => $idp->id, 'idpattr' => $row[0], 'userfield' => $row[1],
                'sync' => attribute::SYNC_ONCREATE]);
        }
        $good = ['UserName' => ['newbie'], 'Email' => ['new@example.com'], 'FirstName' => ['New'], 'SurName' => ['Bie']];

        $bag = $good;
        unset($bag['SurName']);
        $this->assert_fails(fn() => login::resolve_user($idp, 'g1', $bag), 'error_createmissing');

        $bag = array_merge($good, ['Email' => ['not an email']]);
        $this->assert_fails(fn() => login::resolve_user($idp, 'g1', $bag), 'error_createemail');

        $this->getDataGenerator()->create_user(['username' => 'newbie']);
        $this->assert_fails(fn() => login::resolve_user($idp, 'g1', $good), 'error_createusername');

        $this->getDataGenerator()->create_user(['username' => 'taken', 'email' => 'new@example.com']);
        $bag = array_merge($good, ['UserName' => ['brandnew']]);
        set_config('allowaccountssameemail', 0);
        $this->assert_fails(fn() => login::resolve_user($idp, 'g1', $bag), 'error_createemail');

        // The site setting does not matter, a new account always needs an unused email.
        set_config('allowaccountssameemail', 1);
        $this->assert_fails(fn() => login::resolve_user($idp, 'g1', $bag), 'error_createemail');

        $bag = array_merge($good, ['UserName' => ['brandnew'], 'Email' => ['brandnew@example.com']]);
        $this->assertNotNull(login::resolve_user($idp, 'g1', $bag));
    }

    public function test_resolve_user_without_automap_or_autocreate(): void {
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        $idp = $gen->create_idp(['mapattr' => 'UserID', 'automap' => 0, 'autocreate' => 0]);
        attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'Email', 'userfield' => 'email',
            'sync' => attribute::SYNC_NONE, 'usermapping' => 1]);
        $this->getDataGenerator()->create_user(['auth' => 'musaml', 'email' => 'jane@example.com']);

        $this->assertNull(login::resolve_user($idp, 'g1', ['Email' => ['jane@example.com']]));
    }

    public function test_complete_failures(): void {
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        $idp = $gen->create_idp(['mapattr' => 'UserID']);

        $sink = $this->redirectEvents();
        $this->assert_fails(fn() => login::authenticate($idp, []), 'error_noguid');
        $this->assert_fails(fn() => login::authenticate($idp, ['UserID' => ['a', 'b']]), 'error_noguid');
        $this->assert_fails(fn() => login::authenticate($idp, ['UserID' => ['z-1']]), 'error_nomapping');

        $suspended = $this->getDataGenerator()->create_user(['auth' => 'musaml', 'suspended' => 1]);
        $gen->create_user_mapping(['idpid' => $idp->id, 'userid' => $suspended->id, 'guid' => 'z-1']);
        $this->assert_fails(fn() => login::authenticate($idp, ['UserID' => ['z-1']]), 'error_suspended');

        $manual = $this->getDataGenerator()->create_user(['auth' => 'manual']);
        $gen->create_user_mapping(['idpid' => $idp->id, 'userid' => $manual->id, 'guid' => 'z-2']);
        $this->assert_fails(fn() => login::authenticate($idp, ['UserID' => ['z-2']]), 'error_otherauth');

        // Every failure is reported with its reason, known accounts also reach the core report.
        $events = array_filter($sink->get_events(), fn($e) => $e instanceof \auth_musaml\event\login_failed);
        $this->assertCount(5, $events);
        $reasons = array_map(fn($e) => $e->other['reason'], $events);
        $this->assertSame(
            ['error_noguid', 'error_noguid', 'error_nomapping', 'error_suspended', 'error_otherauth'],
            array_values($reasons)
        );
        $core = array_filter($sink->get_events(), fn($e) => $e instanceof \core\event\user_login_failed);
        $this->assertCount(2, $core, 'only failures with a known account');
        $sink->close();

        // Other auth is fine when the mapping allows it and the plugin is enabled.
        \auth_musaml\local\mapping::delete(\auth_musaml\local\mapping::fetch_by_userid($manual->id)->id);
        $gen->create_user_mapping(['idpid' => $idp->id, 'userid' => $manual->id, 'guid' => 'z-2', 'allowotherauth' => 1]);
        $url = @$this->login_user($idp, ['UserID' => ['z-2']]);
        $this->assertSame($manual->id, $GLOBALS['USER']->id);
        $this->assertNotEmpty($url);
    }

    /**
     * Assert that the callback ends with the given auth_musaml error.
     *
     * @param callable $callback
     * @param string $errorcode
     */
    private function assert_fails(callable $callback, string $errorcode): void {
        try {
            $callback();
            $this->fail('exception expected: ' . $errorcode);
        } catch (\core\exception\moodle_exception $e) {
            $this->assertSame($errorcode, $e->errorcode);
        }
    }

    /**
     * Authenticate and log in, what the ACS endpoint and complete.php do together.
     *
     * @param \stdClass $idp
     * @param array $bag attribute bag
     * @param string $wantsurl
     * @return \core\url
     */
    private function login_user(\stdClass $idp, array $bag, string $wantsurl = ''): \core\url {
        $user = login::authenticate($idp, $bag);
        $samlsession = ['nameid' => 'n', 'nameidformat' => 'unspecified', 'sessionindex' => 'idx'];
        return login::finish($idp, $user, $bag, $samlsession, $wantsurl);
    }

    /**
     * php-saml Auth instance that reports a validated session without any network.
     *
     * @param string $userid
     * @return \OneLogin\Saml2\Auth
     */
    private function get_fake_auth(string $userid): \OneLogin\Saml2\Auth {
        $auth = new class ($userid) extends \OneLogin\Saml2\Auth {
            /** @var string */
            private string $userid;

            /**
             * Constructor without settings validation.
             *
             * @param string $userid
             */
            public function __construct(string $userid) {
                $this->userid = $userid;
            }

            #[\Override]
            public function getNameId() {
                return 'n';
            }

            #[\Override]
            public function getNameIdFormat() {
                return 'urn:x';
            }

            #[\Override]
            public function getSessionIndex() {
                return 'idx';
            }

            #[\Override]
            public function getAttributes() {
                return ['UserID' => [$this->userid]];
            }
        };
        return $auth;
    }

    public function test_process_response_rejects_garbage(): void {
        saml::regenerate_sp_certificate();
        $idp = $this->get_generator()->create_idp();
        $_POST['SAMLResponse'] = base64_encode('<samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"/>');
        try {
            login::process_response($idp, 'ONELOGIN_x');
            $this->fail('exception expected');
        } catch (\core\exception\moodle_exception $e) {
            $this->assertSame('error_response', $e->errorcode);
        } finally {
            unset($_POST['SAMLResponse']);
        }
    }

    public function test_loginpage_idp_list(): void {
        global $CFG;
        $auth = get_auth_plugin('musaml');
        $gen = $this->get_generator();
        $this->assertSame([], $auth->loginpage_idp_list('/'));

        saml::regenerate_sp_certificate();
        $one = $gen->create_idp(['name' => 'One']);
        $gen->create_idp(['name' => 'Off', 'enabled' => 0]);

        $list = $auth->loginpage_idp_list('/my/');
        $this->assertCount(1, $list);
        $this->assertSame('One', $list[0]['name']);
        $expected = $CFG->wwwroot . '/auth/musaml/login.php?id=' . $one->id . '&wantsurl=%2Fmy%2F';
        $this->assertSame($expected, $list[0]['url']->out(false));
        $this->assertNotEmpty($list[0]['iconurl']);
    }
}
