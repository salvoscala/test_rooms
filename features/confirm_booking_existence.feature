Feature: Check Unit Booking
	In order check a Booking
	As an admin user
	I need to be able see the booking confirmed
@api @javascript
Scenario:
	Given I am logged in as a user with the "administrator" role
	And the state of unit "Test" is "1"
	And I have a booking from "20/10/2014" to "12/12/2014"
	Then Unit Calendar should confirm booking from "20/10/2014" to "11/12/2014"