@customfield @customfield_checkbox_multi @javascript
Feature: Managers can manage course custom fields multi-select
  In order to have additional data on the course
  As a manager
  I need to create a multi-select custom field and select its values

  Background:
    Given the following "custom field categories" exist:
      | name              | component   | area   | itemid |
      | Category for test | core_course | course | 0      |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And I log in as "admin"

  Scenario: Create a custom course multi-select field
    Given I navigate to "Courses > Default settings > Course custom fields" in site administration
    When I click on "//a[contains(., 'Add a new custom field') or contains(., 'Add field')]" "xpath_element"
    And I click on "Multi-select" "link"
    And I set the following fields to these values:
      | Name       | Test field |
      | Short name | testfield  |
    And I set the field with xpath "//input[contains(@class, 'option-input') and @data-index = '0']" to "test1"
    And I set the field with xpath "//input[contains(@class, 'option-input') and @data-index = '1']" to "test2"
    And I set the field with xpath "//input[@id = 'default_0']" to "1"
    And I click on "Save changes" "button" in the "Adding a new Multi-select" "dialogue"
    Then I should see "Test field"
    And I log out

  Scenario: Selected values are stored and shown again on the course settings form
    Given the following "custom fields" exist:
      | name       | category          | type           | shortname | configdata                                |
      | Test field | Category for test | checkbox_multi | testfield | {"options":"Option A\nOption B\nOption C"} |
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    When I set the field "customfield_testfield[0]" to "1"
    And I set the field "customfield_testfield[2]" to "1"
    And I press "Save and display"
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    Then the field "customfield_testfield[0]" matches value "1"
    And the field "customfield_testfield[1]" matches value ""
    And the field "customfield_testfield[2]" matches value "1"
    And I log out

  Scenario: A required multi-select field cannot be submitted without a selection
    Given the following "custom fields" exist:
      | name       | category          | type           | shortname | configdata                                   |
      | Test field | Category for test | checkbox_multi | testfield | {"options":"Option A\nOption B","required":1} |
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    When I press "Save and display"
    Then I should see "Please select at least one option."
    And I set the field "customfield_testfield[0]" to "1"
    And I press "Save and display"
    And I should not see "Please select at least one option."
    And I log out
