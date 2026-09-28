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

namespace auth_musaml\local\form;

use auth_musaml\local\mapping_import;
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\checkbox;
use tool_mulib\muform\element\info;
use tool_mulib\muform\element\inforawhtml;
use tool_mulib\muform\element\reload;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;

/**
 * Mapping import, stage three: options and the dry run result.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_upload_options extends form {
    #[\Override]
    protected function definition(): void {
        $extra = $this->get_extra_data();
        $idp = $extra['idp'];
        $csvdata = $extra['csvdata'];

        // What happens to the accounts comes first, the skip list below depends on it.
        $allowotherauth = (new checkbox('allowotherauth', get_string('user_mapping_allowotherauth', 'auth_musaml')))
            ->add_help_button('user_mapping_allowotherauth', 'auth_musaml');
        $this->add($allowotherauth);
        $setauth = (new checkbox('setauth', get_string('user_mapping_setauth', 'auth_musaml')))
            ->set_default(1)
            ->add_help_button('user_mapping_setauth', 'auth_musaml');
        $this->add($setauth);

        $this->add(new info('skipinfo', '', get_string('import_skip_info', 'auth_musaml')));
        foreach (mapping_import::get_skip_menu() as $name => $label) {
            $skip = (new checkbox($name, $label))
                ->set_default(1);
            $this->add($skip);
        }

        // The preview always shows what the options in the form would do right now.
        $options = (object)[];
        foreach (mapping_import::get_option_names() as $name) {
            $options->$name = (int)$this->get_element($name)->get_value();
        }
        $outcomes = mapping_import::check($idp, $csvdata, $options);

        $this->add(new inforawhtml('preview', '', $this->render_preview($outcomes)));

        // The import button comes first so that Enter never skips the dry run of other options.
        $this->add(new buttons('buttons'));
        if (self::is_importable($outcomes)) {
            $this->add(new submit('submit', get_string('import_confirm', 'auth_musaml')), 'buttons');
        }
        $this->add(new reload('refresh', get_string('import_refresh', 'auth_musaml')), 'buttons');
        $this->add(new reload('back', get_string('muform_back', 'tool_mulib')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    /**
     * May the import run with the outcomes of the dry run?
     *
     * @param array $outcomes
     * @return bool
     */
    private static function is_importable(array $outcomes): bool {
        $created = 0;
        foreach ($outcomes as $outcome) {
            if ($outcome->result === mapping_import::RESULT_ERROR) {
                return false;
            }
            if ($outcome->result === mapping_import::RESULT_CREATED) {
                $created++;
            }
        }
        return (bool)$created;
    }

    /**
     * Counts of the dry run and the first rows of its result.
     *
     * @param array $outcomes
     * @return string html
     */
    private function render_preview(array $outcomes): string {
        global $OUTPUT;

        $counts = [
            mapping_import::RESULT_CREATED => 0,
            mapping_import::RESULT_SKIPPED => 0,
            mapping_import::RESULT_ERROR => 0,
        ];
        foreach ($outcomes as $outcome) {
            $counts[$outcome->result]++;
        }

        $message = get_string('import_counts', 'auth_musaml', (object)$counts);
        $style = $counts[mapping_import::RESULT_ERROR] ? 'error' : 'info';
        $html = $OUTPUT->notification($message, $style, false);

        $labels = [
            mapping_import::RESULT_CREATED => get_string('import_result_created', 'auth_musaml'),
            mapping_import::RESULT_SKIPPED => get_string('import_result_skipped', 'auth_musaml'),
            mapping_import::RESULT_ERROR => get_string('import_result_error', 'auth_musaml'),
        ];
        $table = new \html_table();
        $table->attributes['class'] = 'generaltable mb-0';
        $table->head = [
            get_string('user_mapping_guid', 'auth_musaml'),
            get_string('username'),
            get_string('status'),
            get_string('description'),
        ];

        // Long imports are not read row by row, the counts above say the rest.
        $shown = array_slice($outcomes, 0, mapping_import::PREVIEW_ROWS);
        foreach ($shown as $outcome) {
            $class = $outcome->result === mapping_import::RESULT_ERROR ? 'text-danger' : '';
            $table->data[] = [
                s($outcome->guid),
                s($outcome->username),
                \core\output\html_writer::span($labels[$outcome->result], $class),
                s($outcome->message),
            ];
        }
        $html .= \core\output\html_writer::table($table);
        if (count($outcomes) > count($shown)) {
            $more = count($outcomes) - count($shown);
            $html .= \core\output\html_writer::div(get_string('import_more_rows', 'auth_musaml', $more), 'text-muted');
        }

        return $html;
    }
}
