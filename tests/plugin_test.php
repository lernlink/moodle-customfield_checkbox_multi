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
use core_customfield_test_instance_form;

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

    /** @var \stdClass[] */
    private $courses = [];
    /** @var \core_customfield\category_controller */
    private $cfcat;
    /** @var \core_customfield\field_controller[] */
    private $cfields;

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
                'type' => 'checkbox_multi',
                'configdata' => ['options' => "A\nB\nC"],
            ]
        );
        $this->cfields[2] = $this->get_generator()->create_field(
            [
                'categoryid' => $this->cfcat->get('id'),
                'shortname' => 'myfieldrequired',
                'type' => 'checkbox_multi',
                'configdata' => ['required' => 1, 'options' => "A\nB\nC"],
            ]
        );
        $this->cfields[3] = $this->get_generator()->create_field(
            [
                'categoryid' => $this->cfcat->get('id'),
                'shortname' => 'myfielddefault',
                'type' => 'checkbox_multi',
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
     * Test for initialising field and data controllers.
     */
    public function test_initialise(): void {
        $field = \core_customfield\field_controller::create($this->cfields[1]->get('id'));
        $this->assertTrue($field instanceof field_controller);

        $field = \core_customfield\field_controller::create(0, (object)['type' => 'checkbox_multi'], $this->cfcat);
        $this->assertTrue($field instanceof field_controller);

        $data = \core_customfield\data_controller::create(0, (object)['instanceid' => $this->courses[1]->id], $this->cfields[1]);
        $this->assertTrue($data instanceof data_controller);
    }

    /**
     * Test configuration form submission.
     */
    public function test_config_form(): void {
        $this->setAdminUser();
        $submitdata = (array)$this->cfields[1]->to_record();
        $submitdata['configdata'] = $this->cfields[1]->get('configdata');
        $submitdata['configdata_options_ui'] = ['A', 'B', 'C'];

        $submitdata = \core_customfield\field_config_form::mock_ajax_submit($submitdata);
        $form = new \core_customfield\field_config_form(null, null, 'post', '', null, true, $submitdata, true);
        $form->set_data_for_dynamic_submission();
        $this->assertTrue($form->is_validated());
    }

    /**
     * Test required validation for instance form.
     */
    public function test_instance_form_required_validation(): void {
        global $CFG;
        require_once($CFG->dirroot . '/customfield/tests/fixtures/test_instance_form.php');
        $this->setAdminUser();
        $handler = $this->cfcat->get_handler();

        $submitdata = (array)$this->courses[1];
        core_customfield_test_instance_form::mock_submit($submitdata, []);
        $form = new core_customfield_test_instance_form(
            'POST',
            ['handler' => $handler, 'instance' => $this->courses[1]]
        );
        $this->assertFalse($form->is_validated());

        $submitdata['customfield_myfieldrequired'] = [1 => 1];
        core_customfield_test_instance_form::mock_submit($submitdata, []);
        $form = new core_customfield_test_instance_form(
            'POST',
            ['handler' => $handler, 'instance' => $this->courses[1]]
        );
        $this->assertTrue($form->is_validated());
    }

    /**
     * Test default values are mapped to option indexes.
     */
    public function test_default_value_mapping(): void {
        $data = \core_customfield\data_controller::create(0, (object)['instanceid' => $this->courses[1]->id], $this->cfields[3]);
        $this->assertSame('1,2', $data->get_default_value());
        $this->assertSame('B, C', $data->export_value());

        $instance = (object)['id' => 0];
        $data->instance_form_before_set_data($instance);
        $elementname = $data->get_form_element_name();
        $this->assertSame([1 => 1, 2 => 1], $instance->{$elementname});
    }

    /**
     * Test set_value always normalises values to JSON arrays.
     */
    public function test_set_value_normalises_json_storage(): void {
        $data = \core_customfield\data_controller::create(0, (object)['instanceid' => $this->courses[1]->id], $this->cfields[1]);

        $data->set_value(['A', ' ', 'B']);
        $this->assertSame('["A","B"]', $data->get('value'));
        $this->assertSame('A, B', $data->export_value());

        $data->set_value('A, B, , C');
        $this->assertSame('["A","B","C"]', $data->get('value'));

        $data->set_value('[]');
        $this->assertSame('[]', $data->get('value'));
    }

    /**
     * Test is_empty treats empty JSON list as empty.
     */
    public function test_is_empty_handles_empty_json_list(): void {
        $data = \core_customfield\data_controller::create(0, (object)['instanceid' => $this->courses[2]->id], $this->cfields[1]);
        $method = new \ReflectionMethod($data, 'is_empty');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($data, '[]'));
        $this->assertTrue($method->invoke($data, []));
        $this->assertTrue($method->invoke($data, ''));
        $this->assertFalse($method->invoke($data, '["A"]'));
    }
}
