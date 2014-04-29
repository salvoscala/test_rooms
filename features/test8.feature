Feature: Add New Booking
	In order to add a new Booking
	As an admin user
	I need to be able add a new booking
@api @javascript
Scenario:
	Given I am logged in as a user with the "administrator" role
	When I add a booking from "25/04/2014" to "20/05/2014"
	Then I should see "Checkout complete"
	And I should see the order from "25/04/2014" to "20/05/2014"