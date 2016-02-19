@home
Feature: Homepage
  In order to verify success at installing the web site
  As a website user
  I want to go to the home page without errors

  Scenario: View the homepage
    When I am on the homepage
    Then the response status code should be 200
    And I should see "User login"
    And I should not see "Log out"

  @api
  Scenario: View the homepage while logged in
    Given I am logged in as a user with the "administrator" role
    When I am on the homepage
    Then the response status code should be 200
    Then I should see "My account"
    And I should see "Log out"
