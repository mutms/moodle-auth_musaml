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

namespace auth_musaml\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for SAML authentication.
 *
 * The only personal data is the mapping of a user to an identity provider account id.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    #[\Override]
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('auth_musaml_user', [
            'idpid' => 'privacy:metadata:auth_musaml_user:idpid',
            'guid' => 'privacy:metadata:auth_musaml_user:guid',
            'userid' => 'privacy:metadata:auth_musaml_user:userid',
            'allowotherauth' => 'privacy:metadata:auth_musaml_user:allowotherauth',
            'automapped' => 'privacy:metadata:auth_musaml_user:automapped',
            'timecreated' => 'privacy:metadata:auth_musaml_user:timecreated',
        ], 'privacy:metadata:auth_musaml_user');
        $collection->add_database_table('auth_musaml_login', [
            'idpid' => 'privacy:metadata:auth_musaml_login:idpid',
            'userid' => 'privacy:metadata:auth_musaml_login:userid',
            'resultjson' => 'privacy:metadata:auth_musaml_login:resultjson',
            'timecreated' => 'privacy:metadata:auth_musaml_login:timecreated',
        ], 'privacy:metadata:auth_musaml_login');
        $collection->link_subsystem('core_auth', 'privacy:metadata:authsubsystem');
        return $collection;
    }

    #[\Override]
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT ctx.id
                  FROM {auth_musaml_user} m
                  JOIN {context} ctx ON ctx.instanceid = m.userid AND ctx.contextlevel = :contextlevel
                 WHERE m.userid = :userid";
        $params = ['userid' => $userid, 'contextlevel' => CONTEXT_USER];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    #[\Override]
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_user) {
            return;
        }
        $sql = "SELECT userid
                  FROM {auth_musaml_user}
                 WHERE userid = :userid";
        $userlist->add_from_sql('userid', $sql, ['userid' => $context->instanceid]);
    }

    #[\Override]
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $context = \context_user::instance($userid);

        $sql = "SELECT m.id, m.guid, m.allowotherauth, m.automapped, m.timecreated, i.name AS idpname, i.entityid
                  FROM {auth_musaml_user} m
                  JOIN {auth_musaml_idp} i ON i.id = m.idpid
                 WHERE m.userid = :userid";
        $mappings = $DB->get_records_sql($sql, ['userid' => $userid]);
        foreach ($mappings as $mapping) {
            $data = (object)[
                'idpname' => $mapping->idpname,
                'entityid' => $mapping->entityid,
                'guid' => $mapping->guid,
                'allowotherauth' => transform::yesno($mapping->allowotherauth),
                'automapped' => transform::yesno($mapping->automapped),
                'timecreated' => transform::datetime($mapping->timecreated),
            ];
            writer::with_context($context)->export_data([
                get_string('privacy:metadata:auth_musaml_user', 'auth_musaml'),
                $mapping->idpname,
            ], $data);
        }
    }

    #[\Override]
    public static function delete_data_for_all_users_in_context(\context $context) {
        if ($context->contextlevel != CONTEXT_USER) {
            return;
        }
        self::delete_user_data($context->instanceid);
    }

    #[\Override]
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();
        if ($context instanceof \context_user) {
            self::delete_user_data($context->instanceid);
        }
    }

    #[\Override]
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_USER && $context->instanceid == $userid) {
                self::delete_user_data($userid);
            }
        }
    }

    /**
     * Delete the IDP mapping and any login in progress of one user.
     *
     * @param int $userid
     */
    private static function delete_user_data(int $userid): void {
        global $DB;
        $DB->delete_records('auth_musaml_user', ['userid' => $userid]);
        // A login in progress holds the attributes for a few minutes, drop it too.
        $DB->delete_records('auth_musaml_login', ['userid' => $userid]);
    }
}
