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

use auth_musaml\local\mapping;
use auth_musaml\local\mapping_import;

/**
 * Bulk mapping import test.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \auth_musaml\local\mapping_import
 */
final class mapping_import_test extends \advanced_testcase {
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

    /**
     * Options with everything skipped or not.
     *
     * @param bool $skip
     * @param array $extra
     * @return \stdClass
     */
    private function get_options(bool $skip, array $extra = []): \stdClass {
        $options = [];
        foreach (array_keys(mapping_import::get_skip_menu()) as $name) {
            $options[$name] = (int)$skip;
        }
        return (object)array_merge($options, $extra);
    }

    public function test_separator_is_detected(): void {
        $expected = [['guid', 'email'], ['z-1', 'one@example.com']];

        foreach (["\t", ',', ';', ':'] as $separator) {
            $content = "guid{$separator}email\nz-1{$separator}one@example.com";
            $rows = mapping_import::parse($content, 'UTF-8', mapping_import::DELIMITER_AUTO);
            $this->assertSame($expected, $rows, 'separator ' . json_encode($separator));
        }

        // A chosen separator is used as it is, even when it reads the data as one column.
        $rows = mapping_import::parse("guid\temail\nz-1\tone@example.com", 'UTF-8', 'comma');
        $this->assertSame([["guid\temail"], ["z-1\tone@example.com"]], $rows);
    }

    public function test_parse(): void {
        $rows = mapping_import::parse("guid,email\nz-1,one@example.com\nz-2,two@example.com", 'UTF-8', 'comma');
        $this->assertSame([['guid', 'email'], ['z-1', 'one@example.com'], ['z-2', 'two@example.com']], $rows);

        $rows = mapping_import::parse("guid;email\nz-1;one@example.com", 'UTF-8', 'semicolon');
        $this->assertSame([['guid', 'email'], ['z-1', 'one@example.com']], $rows);

        // Values are trimmed and quoting is honoured.
        $rows = mapping_import::parse("guid,name\n z-1 ,\"Doe, Jane\"", 'UTF-8', 'comma');
        $this->assertSame(['z-1', 'Doe, Jane'], $rows[1]);

        $this->expectException(\core\exception\coding_exception::class);
        mapping_import::parse('   ', 'UTF-8', 'comma');
    }

    public function test_guess_columns(): void {
        $guesses = mapping_import::guess_columns(['GUID', 'E-mail', 'User name', 'ID number', 'Department']);
        $this->assertSame([
            mapping_import::COLUMN_GUID, 'email', 'username', 'idnumber', mapping_import::COLUMN_IGNORE,
        ], $guesses);
        $this->assertSame([mapping_import::COLUMN_GUID], mapping_import::guess_columns(['IdpUserId']));
    }

    public function test_guess_headers(): void {
        // Names of the database fields and of the account id say the line is a header.
        $this->assertTrue(mapping_import::guess_headers(['guid', 'email']));
        $this->assertTrue(mapping_import::guess_headers(['UserID', 'User name', 'Department']));

        // An account id alone is not enough, and data does not look like names.
        $this->assertFalse(mapping_import::guess_headers(['guid', 'Department']));
        $this->assertFalse(mapping_import::guess_headers(['z-1', 'one@example.com']));
    }

    public function test_data_without_a_header_line(): void {
        $idp = $this->get_generator()->create_idp();
        $user = $this->getDataGenerator()->create_user(['email' => 'one@example.com', 'auth' => 'musaml']);
        $rows = [['z-1', 'one@example.com']];
        $columns = [0 => mapping_import::COLUMN_GUID, 1 => 'email'];

        // The first row is data, so it is checked and imported like any other.
        $csvdata = $this->csvdata($rows, $columns, false);
        $this->assertSame($rows, mapping_import::get_data_rows($csvdata));

        $counts = mapping_import::import($idp, $csvdata, $this->get_options(false));
        $this->assertSame(1, $counts[mapping_import::RESULT_CREATED]);
        $this->assertSame('z-1', mapping::fetch_by_userid($user->id)->guid);
    }

    public function test_wizard_state_and_stages(): void {
        $this->setAdminUser();
        $rows = [['guid', 'email'], ['z-1', 'one@example.com']];
        $columns = ['headers' => true, 'map' => [0 => mapping_import::COLUMN_GUID, 1 => 'email']];

        // Nothing stored yet, the wizard asks for the data.
        foreach ([[], ['rows' => 'x'], ['rows' => [['a'], ['b', 'c']]]] as $stored) {
            $csvdata = mapping_import::validate($stored);
            $this->assertSame([], $csvdata->rows);
            $this->assertSame([], $csvdata->columns);
            $this->assertSame([], $csvdata->options);
            $this->assertTrue(mapping_import::is_source_stage($csvdata));
        }

        // The source stage parses the pasted or uploaded text.
        $csvdata = mapping_import::save_source("guid,email\nz-1,one@example.com", 'UTF-8', mapping_import::DELIMITER_AUTO);
        $this->assertSame($rows, $csvdata->rows);
        $this->assertFalse(mapping_import::is_source_stage($csvdata));
        $this->assertTrue(mapping_import::is_columns_stage($csvdata));
        // The state survives the JSON round trip of the wizard storage.
        $this->assertEquals($csvdata, mapping_import::validate(json_decode(json_encode($csvdata), true)));

        // The columns stage adds what each column holds.
        $csvdata = mapping_import::save_columns($csvdata, (object)['headers' => 1,
            'column_0' => mapping_import::COLUMN_GUID, 'column_1' => 'email']);
        $this->assertSame($columns, $csvdata->columns);
        $this->assertFalse(mapping_import::is_columns_stage($csvdata));
        $this->assertTrue(mapping_import::is_options_stage($csvdata));

        // Confirmed options have to be confirmed again after the columns changed.
        $csvdata = mapping_import::save_options($csvdata, (object)['setauth' => 1]);
        $this->assertFalse(mapping_import::is_options_stage($csvdata));
        $csvdata = mapping_import::save_columns($csvdata, (object)['headers' => 1,
            'column_0' => mapping_import::COLUMN_GUID, 'column_1' => 'email']);
        $this->assertTrue(mapping_import::is_options_stage($csvdata));
        $this->assertSame(1, $csvdata->options['setauth']);
    }

    public function test_options_stage_needs_every_option(): void {
        $this->setAdminUser();
        $csvdata = mapping_import::validate([
            'rows' => [['guid', 'email'], ['z-1', 'one@example.com']],
            'columns' => ['headers' => true, 'map' => [mapping_import::COLUMN_GUID, 'email']],
            'options' => ['setauth' => 1],
        ]);

        // Everything else was answered already, only the options page is left, and the
        // options of the document are the defaults of its form.
        $this->assertFalse(mapping_import::is_source_stage($csvdata));
        $this->assertFalse(mapping_import::is_columns_stage($csvdata));
        $this->assertTrue(mapping_import::is_options_stage($csvdata));
        $this->assertSame(['setauth' => 1], $csvdata->options);

        // Confirming the options ends the questions, the import may run.
        $formdata = (object)['setauth' => 1, 'allowotherauth' => 0];
        $csvdata = mapping_import::save_options($csvdata, $formdata);

        $this->assertFalse($csvdata->options['preview']);
        $this->assertFalse(mapping_import::is_options_stage($csvdata));
        $this->assertFalse(mapping_import::is_options_stage(mapping_import::validate(json_decode(json_encode($csvdata), true))));

        // A document that claims the options were confirmed must answer all of them.
        foreach (mapping_import::get_option_names() as $name) {
            $partial = clone($csvdata);
            $partial->options = $csvdata->options;
            unset($partial->options[$name]);
            $this->assertTrue(mapping_import::is_options_stage($partial), $name);
        }
    }

    public function test_unusable_sections_are_emptied(): void {
        $rows = [['guid', 'email'], ['z-1', 'one@example.com']];

        // Rows of different width are not a table, a single row may still be data.
        $this->assertSame('import_error_rowsize', mapping_import::check_rows([['a', 'b'], ['c']]));
        $this->assertSame('import_error_empty', mapping_import::check_rows([]));
        $this->assertNull(mapping_import::check_rows([['z-1', 'one@example.com']]));
        $this->assertNull(mapping_import::check_rows($rows));
        $this->assertSame([], mapping_import::validate(['rows' => [['a', 'b'], ['c']]])->rows);

        // The form and the document are judged by the same column rules.
        $map = [mapping_import::COLUMN_GUID, 'email'];
        $this->assertSame([], mapping_import::check_columns($rows, ['headers' => true, 'map' => $map]));
        $guidonly = [mapping_import::COLUMN_GUID, mapping_import::COLUMN_IGNORE];
        $expected = ['column_0' => 'import_error_nouser'];
        $this->assertSame($expected, mapping_import::check_columns($rows, ['headers' => true, 'map' => $guidonly]));
        // An unknown meaning is reported, and it leaves nobody to map the row to.
        $nosuch = [mapping_import::COLUMN_GUID, 'nosuchfield'];
        $expected = ['column_1' => 'import_error_columnunknown', 'column_0' => 'import_error_nouser'];
        $this->assertSame($expected, mapping_import::check_columns($rows, ['headers' => true, 'map' => $nosuch]));
        // The headers flag has to be answered, and it must leave data behind.
        $this->assertSame(['headers' => 'import_error_headers'], mapping_import::check_columns($rows, ['map' => $map]));
        $onerow = [['z-1', 'one@example.com']];
        $expected = ['headers' => 'import_error_nodatarows'];
        $this->assertSame($expected, mapping_import::check_columns($onerow, ['headers' => true, 'map' => $map]));

        // Columns must name one identity provider account id and one user column.
        $section = ['headers' => true, 'map' => [mapping_import::COLUMN_GUID, mapping_import::COLUMN_GUID]];
        $this->assertSame([], mapping_import::validate(['rows' => $rows, 'columns' => $section])->columns);
        $section = ['headers' => true, 'map' => ['email', mapping_import::COLUMN_IGNORE]];
        $this->assertSame([], mapping_import::validate(['rows' => $rows, 'columns' => $section])->columns);
        $section = ['headers' => true, 'map' => [mapping_import::COLUMN_GUID, 'nosuchfield']];
        $this->assertSame([], mapping_import::validate(['rows' => $rows, 'columns' => $section])->columns);

        // A column choice that says who is who is kept, together with the headers flag.
        $section = ['headers' => false, 'map' => $map];
        $this->assertSame($section, mapping_import::validate(['rows' => $rows, 'columns' => $section])->columns);
    }

    /**
     * The wizard document the import functions work on.
     *
     * @param array $rows
     * @param array $columns
     * @param bool $headers is the first row the column names?
     * @return \stdClass
     */
    private function csvdata(array $rows, array $columns, bool $headers = true): \stdClass {
        return (object)['rows' => $rows, 'columns' => ['headers' => $headers, 'map' => $columns], 'options' => []];
    }

    public function test_check_reports_every_problem(): void {
        $gen = $this->get_generator();
        $idp = $gen->create_idp();
        $other = $gen->create_idp();
        $columns = [0 => mapping_import::COLUMN_GUID, 1 => 'email'];

        $ok = $this->getDataGenerator()->create_user(['auth' => 'musaml', 'email' => 'ok@example.com']);
        $suspended = $this->getDataGenerator()->create_user(['auth' => 'musaml', 'suspended' => 1,
            'email' => 'susp@example.com']);
        $manual = $this->getDataGenerator()->create_user(['auth' => 'manual', 'email' => 'manual@example.com']);
        $mapped = $this->getDataGenerator()->create_user(['auth' => 'musaml', 'email' => 'mapped@example.com']);
        $gen->create_user_mapping(['idpid' => $other->id, 'userid' => $mapped->id, 'guid' => 'x-1']);
        $taken = $this->getDataGenerator()->create_user(['auth' => 'musaml', 'email' => 'taken@example.com']);
        $gen->create_user_mapping(['idpid' => $idp->id, 'userid' => $taken->id, 'guid' => 'used']);
        $this->getDataGenerator()->create_user(['auth' => 'musaml', 'email' => 'twin@example.com']);
        $this->getDataGenerator()->create_user(['auth' => 'musaml', 'email' => 'twin@example.com']);

        $rows = [
            ['guid', 'email'],
            ['z-1', 'ok@example.com'],
            ['z-2', 'nobody@example.com'],
            ['z-3', 'twin@example.com'],
            ['z-4', 'susp@example.com'],
            ['z-5', 'manual@example.com'],
            ['z-6', 'mapped@example.com'],
            ['used', 'ok@example.com'],
            ['', 'ok@example.com'],
        ];

        $outcomes = mapping_import::check($idp, $this->csvdata($rows, $columns), $this->get_options(false));
        $results = array_column($outcomes, 'result');
        $this->assertSame([
            mapping_import::RESULT_CREATED,
            mapping_import::RESULT_ERROR,
            mapping_import::RESULT_ERROR,
            mapping_import::RESULT_ERROR,
            mapping_import::RESULT_ERROR,
            mapping_import::RESULT_ERROR,
            mapping_import::RESULT_ERROR,
            mapping_import::RESULT_ERROR,
        ], $results);
        $this->assertStringContainsString('No user matches', $outcomes[1]->message);
        $this->assertStringContainsString('More than one user', $outcomes[2]->message);
        $this->assertStringContainsString('suspended', $outcomes[3]->message);
        $this->assertStringContainsString('authentication method', $outcomes[4]->message);
        $this->assertStringContainsString('already mapped to another identity provider account', $outcomes[5]->message);
        $this->assertStringContainsString('already mapped to another user', $outcomes[6]->message);
        $this->assertStringContainsString('does not hold', $outcomes[7]->message);
        $this->assertEquals($ok->id, $outcomes[0]->userid);
        $this->assertEquals($suspended->id, $outcomes[3]->userid);

        // With the skip options every problem becomes a skipped row instead.
        $outcomes = mapping_import::check($idp, $this->csvdata($rows, $columns), $this->get_options(true));
        $results = array_unique(array_column($outcomes, 'result'));
        $this->assertEqualsCanonicalizing([mapping_import::RESULT_CREATED, mapping_import::RESULT_SKIPPED], $results);

        // Allowing other authentication makes the manual account importable.
        $options = $this->get_options(false, ['allowotherauth' => 1]);
        $outcomes = mapping_import::check($idp, $this->csvdata([$rows[0], $rows[5]], $columns), $options);
        $this->assertSame(mapping_import::RESULT_CREATED, $outcomes[0]->result);
        $this->assertEquals($manual->id, $outcomes[0]->userid);
    }

    public function test_check_requires_all_identifiers_to_agree(): void {
        $gen = $this->get_generator();
        $idp = $gen->create_idp();
        $columns = [0 => mapping_import::COLUMN_GUID, 1 => 'username', 2 => 'email'];
        $this->getDataGenerator()->create_user(['auth' => 'musaml', 'username' => 'jane', 'email' => 'jane@example.com']);
        $this->getDataGenerator()->create_user(['auth' => 'musaml', 'username' => 'john', 'email' => 'john@example.com']);

        $rows = [['guid', 'username', 'email'], ['z-1', 'jane', 'jane@example.com']];
        $outcome = mapping_import::check($idp, $this->csvdata($rows, $columns), $this->get_options(false))[0];
        $this->assertSame(mapping_import::RESULT_CREATED, $outcome->result);

        $rows = [['guid', 'username', 'email'], ['z-1', 'jane', 'john@example.com']];
        $outcome = mapping_import::check($idp, $this->csvdata($rows, $columns), $this->get_options(false))[0];
        $this->assertSame(mapping_import::RESULT_ERROR, $outcome->result);
        $this->assertStringContainsString('More than one user', $outcome->message);
    }

    public function test_import_writes_nothing_when_a_row_fails(): void {
        global $DB;
        $gen = $this->get_generator();
        $idp = $gen->create_idp();
        $columns = [0 => mapping_import::COLUMN_GUID, 1 => 'email'];
        $this->getDataGenerator()->create_user(['auth' => 'musaml', 'email' => 'one@example.com']);

        $rows = [['guid', 'email'], ['z-1', 'one@example.com'], ['z-2', 'nobody@example.com']];

        $counts = mapping_import::import($idp, $this->csvdata($rows, $columns), $this->get_options(false));
        $this->assertSame(1, $counts[mapping_import::RESULT_ERROR]);
        $this->assertSame(0, $DB->count_records('auth_musaml_user'), 'all or nothing');

        $counts = mapping_import::import($idp, $this->csvdata($rows, $columns), $this->get_options(true));
        $this->assertSame(1, $counts[mapping_import::RESULT_CREATED]);
        $this->assertSame(1, $counts[mapping_import::RESULT_SKIPPED]);
        $this->assertSame(1, $DB->count_records('auth_musaml_user'));
    }

    public function test_import_applies_options(): void {
        global $DB;
        $gen = $this->get_generator();
        $idp = $gen->create_idp();
        $columns = [0 => mapping_import::COLUMN_GUID, 1 => 'username'];
        $manual = $this->getDataGenerator()->create_user(['auth' => 'manual', 'username' => 'switchme']);
        $keep = $this->getDataGenerator()->create_user(['auth' => 'manual', 'username' => 'keepme']);

        $rows = [['guid', 'username'], ['z-1', 'switchme']];
        $options = $this->get_options(true, ['setauth' => 1]);
        $counts = mapping_import::import($idp, $this->csvdata($rows, $columns), $options);
        $this->assertSame(1, $counts[mapping_import::RESULT_CREATED]);
        $this->assertSame('musaml', $DB->get_field('user', 'auth', ['id' => $manual->id]));
        $this->assertSame('0', mapping::fetch_by_userid($manual->id)->allowotherauth);

        $rows = [['guid', 'username'], ['z-2', 'keepme']];
        $options = $this->get_options(true, ['allowotherauth' => 1]);
        mapping_import::import($idp, $this->csvdata($rows, $columns), $options);
        $this->assertSame('manual', $DB->get_field('user', 'auth', ['id' => $keep->id]));
        $this->assertSame('1', mapping::fetch_by_userid($keep->id)->allowotherauth);
    }
}
