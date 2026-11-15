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

namespace auth_musaml\local\wizard\user_upload;

use auth_musaml\local\idp;
use auth_musaml\local\mapping_import;
use core\url;
use stdClass;
use tool_mulib\muform\form;

/**
 * Upload or copy/paste multiple user mappings for one IDP via CSV format.
 *
 * Three stages: source, column meaning, options with a dry run. The parsed rows and the answers
 * live in the wizard state, the source stage is locked once the data were read.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class wizard extends \tool_mulib\muform\wizard_page {
    /** @var stdClass identity provider */
    private stdClass $idp;
    /** @var stdClass stored state validated again on every request */
    private stdClass $csvdata;

    /**
     * Load the identity provider and check access.
     *
     * @param int $idpid
     */
    public function __construct(int $idpid) {
        global $DB;

        require_capability('auth/musaml:managemappings', \core\context\system::instance());
        $this->idp = $DB->get_record('auth_musaml_idp', ['id' => $idpid], '*', MUST_EXIST);
    }

    #[\Override]
    protected function get_name(): string {
        // The state belongs to this identity provider, it cannot be replayed against another one.
        return 'auth_musaml_user_upload:' . $this->idp->id;
    }

    #[\Override]
    protected function get_stages(): array {
        return [
            'source' => get_string('import', 'auth_musaml'),
            'columns' => get_string('import_columns', 'auth_musaml'),
            'options' => get_string('import_options', 'auth_musaml'),
        ];
    }

    #[\Override]
    protected function get_locked_stages(): array {
        return ['source'];
    }

    #[\Override]
    protected function get_page_url(): url {
        return new url('/auth/musaml/management/user_upload.php', ['id' => $this->idp->id]);
    }

    #[\Override]
    protected function get_return_url(): url {
        return new url('/auth/musaml/management/users.php', ['id' => $this->idp->id]);
    }

    #[\Override]
    protected function setup_page(): void {
        global $PAGE;

        idp::setup_mappings_page($this->idp, $this->get_page_url());
        $PAGE->navbar->add(get_string('import', 'auth_musaml'));
    }

    #[\Override]
    protected function get_heading(): ?string {
        return $this->get_stages()[$this->stage];
    }

    #[\Override]
    protected function check_state(): void {
        // Each stage fills one part of the stored state.
        $this->csvdata = mapping_import::validate($this->state);
    }

    #[\Override]
    protected function is_stage_valid(string $stage): bool {
        return match ($stage) {
            'source' => mapping_import::is_source_valid($this->csvdata),
            'columns' => mapping_import::is_columns_valid($this->csvdata),
            'options' => mapping_import::is_options_valid($this->csvdata),
        };
    }

    #[\Override]
    protected function create_form(string $stage, url $formurl): form {
        if ($stage === 'source') {
            $extra = $this->is_stage_locked($stage) ? ['rows' => $this->csvdata->rows] : [];
            return new source($formurl, [], $extra);
        }
        if ($stage === 'columns') {
            $current = [];
            if ($this->csvdata->columns) {
                $current['headers'] = (int)$this->csvdata->columns['headers'];
                foreach ($this->csvdata->columns['map'] as $index => $value) {
                    $current['column_' . $index] = $value;
                }
            }
            return new columns($formurl, $current, ['rows' => $this->csvdata->rows]);
        }
        // Options of an earlier confirmation are only defaults of the form.
        return new options($formurl, $this->csvdata->options, ['idp' => $this->idp, 'csvdata' => $this->csvdata]);
    }

    #[\Override]
    protected function submit_stage(string $stage, stdClass $data, form $form): ?url {
        if ($stage === 'source') {
            $csvdata = mapping_import::save_source($form->get_source_content(), $data->encoding, $data->delimiter_name);
        } else if ($stage === 'columns') {
            $csvdata = mapping_import::save_columns($this->csvdata, $data);
        } else {
            $csvdata = mapping_import::save_options($this->csvdata, $data);
        }
        $this->state = (array)$csvdata;
        return null;
    }

    #[\Override]
    protected function finish(): ?url {
        // Everything was answered, write the mappings.
        $counts = mapping_import::import($this->idp, $this->csvdata, (object)$this->csvdata->options);
        $this->wizard->delete();
        if ($counts[mapping_import::RESULT_ERROR]) {
            \core\notification::error(get_string('import_failed', 'auth_musaml', (object)$counts));
        } else {
            \core\notification::success(get_string('import_done', 'auth_musaml', (object)$counts));
        }
        return $this->get_return_url();
    }
}
