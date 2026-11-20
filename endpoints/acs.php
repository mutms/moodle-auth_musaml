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
 * SP Assertion Consumer Service endpoint.
 *
 * The IDP posts the SAML response here after the user authenticated. That post comes
 * from the site of the identity provider, so the browser sends no Moodle session
 * cookie. This script must never start a session, a new one would replace the session
 * cookie of whoever uses the browser. It finds the pending login by the request the
 * response answers, stores the outcome with it and redirects, the page after the
 * redirect has the session back and only that session can finish the login.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_musaml\local\idp;
use auth_musaml\local\login;
use auth_musaml\local\mapping;
use auth_musaml\local\saml;
use core\exception\moodle_exception;

// phpcs:disable moodle.Files.RequireLogin.Missing
define('NO_MOODLE_COOKIES', true);
define('NO_DEBUG_DISPLAY', true);

require(__DIR__ . '/../../../config.php');

$samlresponse = $_POST['SAMLResponse'] ?? null;
$request = is_string($samlresponse) ? login::fetch_request_for_response($samlresponse) : null;

try {
    if (!is_string($samlresponse) || $samlresponse === '') {
        throw new moodle_exception('error_response', 'auth_musaml', '', 'missing SAMLResponse');
    }
    if (!$request) {
        throw new moodle_exception('error_norequest', 'auth_musaml');
    }
    if ($request->ready) {
        // The response of this login was accepted already.
        throw new moodle_exception('error_replay', 'auth_musaml');
    }

    $idp = idp::fetch($request->idpid);
    if (!$idp || !login::is_idp_usable($idp)) {
        throw new moodle_exception('error_idpunavailable', 'auth_musaml');
    }

    $auth = login::process_response($idp, $request->requestid);
    $bag = login::build_attribute_bag($auth->getAttributes(), $auth->getNameId(), $idp);

    $result = [
        'bag' => $bag,
        'nameid' => (string)$auth->getNameId(),
        'nameidformat' => (string)$auth->getNameIdFormat(),
        'sessionindex' => (string)$auth->getSessionIndex(),
    ];

    if ($request->test) {
        // A test never creates or maps anybody, it only reports what would happen.
        $guid = login::get_guid($bag, $idp);
        $mapping = $guid === null ? null : mapping::fetch_by_guid($idp->id, $guid);
        $stored = login::store_result($request, $result, $mapping->userid ?? null);
    } else {
        $user = login::authenticate($idp, $bag);
        $stored = login::store_result($request, $result, $user->id);
    }
    if (!$stored) {
        saml::log('login ' . $request->id . ' has an outcome already, response ignored');
    }
} catch (moodle_exception $e) {
    if ($request && !login::store_result($request, ['error' => $e->getMessage()], null)) {
        saml::log('login ' . $request->id . ' has an outcome already, ignored: ' . $e->getMessage());
    }
}

redirect(login::get_result_url($request));
