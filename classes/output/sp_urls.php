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

namespace auth_musaml\output;

use auth_musaml\local\saml;
use core\output\html_writer;
use core\url;
use renderer_base;
use tool_mulib\output\entity_details;

/**
 * Service provider URLs an identity provider needs.
 *
 * Products that read our metadata need the metadata URL only, products configured by
 * hand, Google among them, ask for the entity ID and the endpoints one by one. Both are
 * shown wherever an identity provider is set up.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sp_urls {
    /**
     * Add every service provider value to a details block.
     *
     * @param entity_details $details
     * @param renderer_base $output
     */
    public static function add_to_details(entity_details $details, renderer_base $output): void {
        $details->add(get_string('sp_entityid', 'auth_musaml'), s(saml::get_sp_entityid()));
        $details->add(get_string('sp_metadataurl', 'auth_musaml'), self::metadata($output));
        $acs = self::copyable(saml::get_endpoint_url('acs'), 'auth_musaml_acsurl', $output);
        $details->add(get_string('sp_acsurl', 'auth_musaml'), $acs);
        $slo = self::copyable(saml::get_endpoint_url('sls'), 'auth_musaml_slourl', $output);
        $details->add(get_string('sp_slourl', 'auth_musaml'), $slo);
    }

    /**
     * Metadata URL as a link, followed by copy and download icons.
     *
     * @param renderer_base $output
     * @return string
     */
    public static function metadata(renderer_base $output): string {
        $url = saml::get_metadata_url();
        $id = 'auth_musaml_metadataurl';

        $link = html_writer::link($url, $url->out(false), [
            'id' => $id,
            'target' => '_blank',
            'rel' => 'noopener',
        ]);
        $downloadurl = new url('/auth/musaml/metadata.php', ['download' => 1]);
        $downloadicon = $output->pix_icon('t/download', get_string('download', 'core'));
        $download = html_writer::link($downloadurl, $downloadicon, ['title' => get_string('download', 'core')]);

        return $link . ' ' . self::copy_icon($id, $output) . ' ' . $download;
    }

    /**
     * URL as plain text with a copy icon, these are typed into another system.
     *
     * @param url $url
     * @param string $id
     * @param renderer_base $output
     * @return string
     */
    public static function copyable(url $url, string $id, renderer_base $output): string {
        $text = html_writer::span($url->out(false), '', ['id' => $id]);
        return $text . ' ' . self::copy_icon($id, $output);
    }

    /**
     * Copy to clipboard icon for the element with the given id.
     *
     * @param string $id
     * @param renderer_base $output
     * @return string
     */
    private static function copy_icon(string $id, renderer_base $output): string {
        global $PAGE;

        $PAGE->requires->js_amd_inline("require(['core/copy_to_clipboard']);");

        $icon = $output->pix_icon('t/copy', get_string('copytoclipboard', 'core'));
        return html_writer::link('#', $icon, [
            'data-action' => 'copytoclipboard',
            'data-clipboard-target' => '#' . $id,
            'data-clipboard-success-message' => get_string('textcopiedtoclipboard', 'core'),
            'title' => get_string('copytoclipboard', 'core'),
        ]);
    }
}
