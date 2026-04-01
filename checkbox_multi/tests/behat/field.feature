@customfield @customfield_checkbox_multi @javascript
Feature: Managers can manage course custom fields multi-select
  In order to have additional data on the course
  As a manager
  I need to create a multi-select custom field

  Background:
    Given the following "custom field categories" exist:
      | name              | component   | area   | itemid |
      | Category for test | core_course | course | 0      |
    And I log in as "admin"
    And I navigate to "Courses > Default settings > Course custom fields" in site administration

  Scenario: Create a custom course multi-select field
    When I click on "Add a new custom field" "link"
    And I click on "Multi-select" "link"
    And I set the following fields to these values:
      | Name         | Test multi field |
      | Short name   | testmultifield   |
      | Menu options | test1            |
      | Menu options | test2            |
    And click at least one "checkbox" besides option to set it as default true.
    And I click on "Save changes" "button" in the "Adding a new Multi-select" "dialogue"
    Then I should see "Test multi field"
    And I log out


  Scenario: Create a custom course multi-select field
    When I click on "Add a new custom field" "link"
    And I click on "Multi-select" "link"
    And I set the following fields to these values:
      | Name         | Test multi field |
      | Short name   | testmultifield   |
      | Menu options | test1            |
      | Menu options | test2            |
    And click at least one "checkbox" besides option to set it as default true.
    And I click on "Save changes" "button" in the "Adding a new Multi-select" "dialogue"
    And I click on "Edit" "link" in the "Test field" "table_row"
    And I set the following fields to these values:
      | Name | Edited field |
    And I click on "Save changes" "button" in the "Updating Test field" "dialogue"
    Then I should see "Edited field"
    And I should not see "Test multi field"

  Scenario: Delete a custom course multi-select field
    When I click on "Add a new custom field" "link"
    And I click on "Multi-select" "link"
    And I set the following fields to these values:
      | Name         | Test multi field |
      | Short name   | testmultifield   |
      | Menu options | test1            |
      | Menu options | test2            |
    And click at least one "checkbox" besides option to set it as default true.
    And I click on "Save changes" "button" in the "Adding a new Multi-select" "dialogue"
    And I click on "Delete" "link" in the "Test field" "table_row"
    And I click on "Yes" "button" in the "Confirm" "dialogue"
    Then I should not see "Test multi field"
    And I log out