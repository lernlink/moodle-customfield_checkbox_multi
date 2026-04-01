<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace customfield_checkbox_multi;

use core_customfield_generator;

/**
 * Functional tests for customfield_checkbox_multi.
 *
 * @package    customfield_checkbox_multi
 * @covers     \customfield_checkbox_multi\data_controller
 * @covers     \customfield_checkbox_multi\field_controller
 * @copyright  2026 Boxuan Liu <boxuan.liu@tu-dresden.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class plugin_test extends \advanced_testcase {

    /** @var string Plugin field type used by customfield generator. */
    private const TEST_FIELD_TYPE = 'checkbox_multi';

    /** @var \stdClass[] */
    private $courses = [];
    /** @var \core_customfield\category_controller */
    private $cfcat;
    /** @var \core_customfield\field_controller[] */
    private $cfields = [];

    /**
     * Tests set up.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $this->cfcat = $this->get_generator()->create_category();

        $this->cfields[1] = $this->get_generator()->create_field(
            [
                'categoryid' => $this->cfcat->get('id'),
                'shortname' => 'myfield1',
                'type' => self::TEST_FIELD_TYPE,
                'configdata' => ['options' => "A\nB\nC"],
            ]
        );
        $this->cfields[2] = $this->get_generator()->create_field(
            [
                'categoryid' => $this->cfcat->get('id'),
                'shortname' => 'myfieldrequired',
                'type' => self::TEST_FIELD_TYPE,
                'configdata' => ['required' => 1, 'options' => "A\nB\nC"],
            ]
        );
        $this->cfields[3] = $this->get_generator()->create_field(
            [
                'categoryid' => $this->cfcat->get('id'),
                'shortname' => 'myfielddefault',
                'type' => self::TEST_FIELD_TYPE,
                'configdata' => ['defaultvalue' => "B\nC", 'options' => "A\nB\nC"],
            ]
        );

        $this->courses[1] = $this->getDataGenerator()->create_course();
        $this->courses[2] = $this->getDataGenerator()->create_course();
    }

    /**
     * Get generator.
     *
     * @return core_customfield_generator
     */
    protected function get_generator(): core_customfield_generator {
        return $this->getDataGenerator()->get_plugin_generator('core_customfield');
    }

    /**
     * Build minimal valid submit data for a new custom field.
     *
     * @param array $overrides
     * @return array
     */
    private function get_new_field_submit_data(array $overrides = []): array {
        $data = [
            'id' => 0,
            'categoryid' => $this->cfcat->get('id'),
            'name' => 'Audit field',
            'shortname' => 'auditfield',
            'type' => self::TEST_FIELD_TYPE,
            'configdata' => [
                'options' => "Option A\nOption B",
                'defaultvalue' => '',
            ],
            'configdata_options_ui' => ['Option A', 'Option B'],
        ];

        return array_replace_recursive($data, $overrides);
    }

    /**
     * Create a data controller for a generated field and course.
     *
     * @param int $fieldindex
     * @param int $courseindex
     * @return data_controller
     */
    private function get_data_controller(int $fieldindex, int $courseindex = 1): data_controller {
        return \core_customfield\data_controller::create(
            0,
            (object)['instanceid' => $this->courses[$courseindex]->id],
            $this->cfields[$fieldindex]
        );
    }

    /**
     * Test for initialising field and data controllers.
     */
    public function test_initialise(): void {
        $field = \core_customfield\field_controller::create($this->cfields[1]->get('id'));
        $this->assertTrue($field instanceof field_controller);

        $field = \core_customfield\field_controller::create(0, (object)['type' => self::TEST_FIELD_TYPE], $this->cfcat);
        $this->assertTrue($field instanceof field_controller);

        $data = $this->get_data_controller(1);
        $this->assertTrue($data instanceof data_controller);
    }

    /**
     * Test the new-field configuration form can initialise without coding exceptions.
     */
    public function test_new_field_config_form_initialises(): void {
        global $PAGE;

        $this->setAdminUser();
        $PAGE = new \moodle_page();
        $PAGE->set_context(\context_system::instance());
        $PAGE->set_url(new \moodle_url('/customfield/field/checkbox_multi/tests/plugin_test.php'));

        $submitdata = \core_customfield\field_config_form::mock_ajax_submit($this->get_new_field_submit_data());
        $form = new \core_customfield\field_config_form(null, null, 'post', '', null, true, $submitdata, true);
        $form->set_data_for_dynamic_submission();

        $this->assertTrue($form->is_validated());
        $this->assertStringContainsString(
            'customfield_checkbox_multi/options_editor',
            $PAGE->requires->get_end_code()
        );
    }

    /**
     * Test duplicate options use the localised validation string.
     */
    public function test_config_form_validation_rejects_duplicate_options(): void {
        $field = \core_customfield\field_controller::create(0, (object)['type' => self::TEST_FIELD_TYPE], $this->cfcat);
        $errors = $field->config_form_validation(
            [
                'configdata' => [
                    'options' => "Option A\nOption A",
                    'defaultvalue' => '',
                ],
                'configdata_options_ui' => ['Option A', 'Option A'],
            ],
            []
        );

        $this->assertArrayHasKey('options_label', $errors);
        $this->assertSame(
            get_string('errorduplicateoptions', 'customfield_checkbox_multi'),
            $errors['options_label']
        );
    }

    /**
     * Test required validation for instance form reports the expected field error.
     */
    public function test_instance_form_required_validation_requires_at_least_one_selection(): void {
        $data = $this->get_data_controller(2);
        $elementname = $data->get_form_element_name();

        $errors = $data->instance_form_validation([], []);
        $this->assertArrayHasKey($elementname . '[0]', $errors);
        $this->assertSame(
            get_string('errorrequiredatleastone', 'customfield_checkbox_multi'),
            $errors[$elementname . '[0]']
        );

        $errors = $data->instance_form_validation([$elementname => [1 => 1]], []);
        $this->assertArrayNotHasKey($elementname . '[0]', $errors);
    }

    /**
     * Test default values are mapped to option indexes.
     */
    public function test_default_value_mapping(): void {
        $data = $this->get_data_controller(3);
        $this->assertSame('1,2', $data->get_default_value());
        $this->assertSame('B, C', $data->export_value());

        $instance = (object)['id' => 0];
        $data->instance_form_before_set_data($instance);
        $elementname = $data->get_form_element_name();

        $this->assertSame([1 => 1, 2 => 1], $instance->{$elementname});
    }

    /**
     * Test instance-form saves always persist JSON arrays, including empty selections.
     */
    public function test_instance_form_save_persists_json_for_selected_and_empty_values(): void {
        $data = $this->get_data_controller(1);
        $elementname = $data->get_form_element_name();

        $data->instance_form_save((object)[$elementname => [0 => 1, 2 => 1]]);
        $this->assertSame('["A","C"]', $data->get_value());
        $this->assertSame('A, C', $data->export_value());

        $instance = (object)['id' => $this->courses[1]->id];
        $data->instance_form_before_set_data($instance);
        $this->assertSame([0 => 1, 2 => 1], $instance->{$elementname});

        $data->instance_form_save((object)[]);
        $this->assertSame('[]', $data->get_value());
        $this->assertNull($data->export_value());
    }

    /**
     * Test set_value keeps JSON storage compatible with form round-tripping.
     */
    public function test_set_value_round_trips_json_storage(): void {
        $data = $this->get_data_controller(1);
        $elementname = $data->get_form_element_name();

        // Bootstrap a persisted record so set_value() exercises the stored-data path.
        $data->instance_form_save((object)[$elementname => [0 => 1]]);

        $data->set_value(['A', ' ', 'B']);
        $this->assertSame('["A","B"]', $data->get('value'));
        $this->assertSame('A, B', $data->export_value());

        $instance = (object)['id' => $this->courses[1]->id];
        $data->instance_form_before_set_data($instance);
        $this->assertSame([0 => 1, 1 => 1], $instance->{$elementname});

        $data->set_value('A, B, , C');
        $this->assertSame('["A","B","C"]', $data->get('value'));

        $instance = (object)['id' => $this->courses[1]->id];
        $data->instance_form_before_set_data($instance);
        $this->assertSame([0 => 1, 1 => 1, 2 => 1], $instance->{$elementname});

        $data->set_value('[]');
        $this->assertSame('[]', $data->get('value'));
        $this->assertNull($data->export_value());

        $instance = (object)['id' => $this->courses[1]->id];
        $data->instance_form_before_set_data($instance);
        $this->assertSame([], $instance->{$elementname});
    }

    /**
     * Test is_empty treats empty JSON list as empty.
     */
    public function test_is_empty_handles_empty_json_list(): void {
        $data = $this->get_data_controller(1, 2);
        $method = new \ReflectionMethod($data, 'is_empty');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($data, '[]'));
        $this->assertTrue($method->invoke($data, []));
        $this->assertTrue($method->invoke($data, ''));
        $this->assertFalse($method->invoke($data, '["A"]'));
    }
}