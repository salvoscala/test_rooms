Feature: Check Unit Booking
	In order check a Booking
	As an admin user
	I need to be able see the booking confirmed for two close date
@api @javascript
Scenario:
	Given I am logged in as a user with the "administrator" role
	# Devo creare e controllare per non perdere il booking id.
	And I have a booking from "30/04/2014" to "05/05/2014"
	Then Unit Calendar should confirm booking from "30/04/2014" to "04/05/2014"
	When I have a booking from "05/05/2014" to "10/05/2014"
	And Unit Calendar should confirm booking from "05/05/2014" to "09/05/2014"