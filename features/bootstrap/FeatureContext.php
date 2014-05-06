<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Mink\WebAssert;


//
// Require 3rd-party libraries here:
//
//   require_once 'PHPUnit/Autoload.php';
//   require_once 'PHPUnit/Framework/Assert/Functions.php';
//

/**
 * Features context.
 */
class FeatureContext extends Drupal\DrupalExtension\Context\DrupalContext {
  /**
    *
    * @var     string
    * Unit and Booking Parametres
  */
  public $unit_id;
  public $booking_id;
  public $unit_price = 200;
  public $unit_type_name = "Test";
  public $unit_name = "test";
  public $unit_max_sleeps = 2;
  public $order_id;
  public $customer = "test_customer";
  /**
  * Initializes context.
  * Every scenario gets its own context object.
  *
  * @param array $parameters context parameters (set them up through behat.yml)
  */
  public function __construct(array $parameters) {
    // Initialize your context here
  }   
  /**
  * Click some text in the current page
  *
  * @When /^I click on the text "([^"]*)"$/
  */
  public function iClickOnTheText($text) {
    $session = $this->getSession();
    $element = $session->getPage()->find(
        'xpath',
        $session->getSelectorsHandler()->selectorToXpath('xpath', '*//*[text()="'. $text .'"]')
    );
    if (null === $element) {
        throw new \InvalidArgumentException(sprintf('Cannot find text: "%s"', $text));
    }

    $element->click();

  }
  /**
   * Click on the element with the provided xpath query
   *
   * @When /^I click on the element with xpath "([^"]*)"$/
   */
  public function iClickOnTheElementWithXPath($xpath) {
      
    $session = $this->getSession();
    $element = $session->getPage()->find(
        'xpath',
        $session->getSelectorsHandler()->selectorToXpath('xpath', $xpath)
    );
    if (null === $element) {
        throw new \InvalidArgumentException(sprintf('Could not evaluate XPath: "%s"', $xpath));
    }
    $element->click();
  }    
  /**
  * Add BookableUnitType
  *
  * @Given /^I add BookableUnitType "(?P<name>(?:[^"]|\\")*)" with price "(?P<price>(?:[^"]|\\")*)"$/
  */
  public function addBookableUnitType($name, $price) {
      
    $this->assertAtPath("/admin/rooms/units/unit-types/add");
    $this->fillField("label", $name);
    $this->fillField("data[base_price]", $price);
    $this->fillField("edit-data-max-sleeps", $this->unit_max_sleeps);
    $this->pressButton("edit-submit");
  }
  /**
  * Looking for BookableUnitType, if it is not present we will create it.
  *
  * @Given /^I have BookableUnitType "(?P<name>(?:[^"]|\\")*)"$/
  */
  public function checkBookableUnitType($name) {
    try {
      $this->assertAtPath("/admin/rooms/units/unit-types");
      $this->assertSession()->elementContains('xpath','//TABLE[@CLASS="sticky-enabled tableheader-processed sticky-table"]',$name);
    } catch (Exception $rte) {
        $this->addBookableUnit($name,$this->unit_price);
      }
  }
  /**
  * Check Bookable Unit, if it is not present we will create it.
  *
  * @Given /^I have BookableUnit "(?P<name>(?:[^"]|\\")*)" of type "(?P<type>(?:[^"]|\\")*)" with default state "(?P<state>(?:[^"]|\\")*)"$/
  */
  public function checkBookableUnit($name, $type, $state) {
          
    $this->assertAtPath("/admin/rooms/units");
    try {        
       $this->assertSession()->elementContains('xpath','//TABLE[@CLASS="views-table cols-7"]',$type);
    } catch (Exception $rte) {
        $this->iClickOnTheText("Add a Bookable Unit");
        $this->iClickOnTheText("Add ".$type." unit");
        $this->fillField("edit-name", $name);
        $this->fillField("edit-default-state",$state);
        $this->pressButton("edit-submit");
      }
    $this->checkUnitState($name, $state);
  }    
  /**
  * Attempts to check the state of a Unit
  *
  * @Given /^the state of unit "(?P<unit>[^"]*)" is "(?P<state>[^"]*)"$/
  */
  public function checkUnitState($unit, $state) {
    $this->assertAtPath("/admin/rooms/units");
    $this->assertClickInTableRow("Edit", $unit);
    $page = $this->getSession()->getCurrentUrl();
    //Taking Unit ID
    $tmp = explode("/", $page);
    $this->unit_id = $tmp[8];
    $this->assertFieldContains("edit-default-state", $state);
    $this->pressButton("edit-submit");
  }  
  /**
  * Update availability or price ("type") of the unit Test.
  * To run this function i need to ChecUnitState before.
  *
  * @When /^I update "(?P<type>[^"]*)" to state "(?P<state>[^"]*)" from "(?P<start>[^"]*)" to "(?P<end>[^"]*)"$/
  */
  public function updateBookingEvent($type, $state, $start, $end) {
    
    $this->assertAtPath("/admin/rooms/units");
    if ($type == "availability") {
        $this->assertClickInTableRow("Manage Availability", $this->unit_id);
        sleep(3);
        $this->iClickOnTheText("Update Unit Availability");
    }
    if ($type == "price") {
        $this->assertClickInTableRow("Manage Pricing", $this->unit_id);
        sleep(3);
        $this->iClickOnTheText("Update Pricing");
    }
    $this->fillField("rooms_start_date[date]", $start);
    $this->fillField("rooms_end_date[date]", $end);
    if ($type == "availability")
      $this->fillField("edit-unit-state", $state);
    if ($type == "price")
      $this->fillField("edit-amount", $state);
    
    $this->pressButton("edit-submit");
  }
  /**
  * Return number of days in a month
  */
  public function endOfMonth($month, $year) {
    if ($month == "01" || $month == "1" || $month == 1)
        return 31;
    if ($month == "02" || $month == "2"|| $month == 2) {
        if ($year%4 == 0 && $year%100 != 0)
           return 29;
        else if ($year%400 == 0)
               return 29;
        else return 28;
    }
    if ($month == "03" || $month == "3" || $month == 3)
        return 31;
    if ($month == "04" || $month == "4" || $month == 4)
        return 30;
    if ($month == "05" || $month == "5" || $month == 5)
        return 31;
    if ($month == "06" || $month == "6" || $month == 6)
        return 30;
    if ($month == "07" || $month == "7" || $month == 7)
        return 31;
    if ($month == "08" || $month == "8" || $month == 8)
        return 31;
    if ($month == "09" || $month == "9" || $month == 9)
        return 30;
    if ($month == "10" || $month == "10" || $month == 10)
        return 31;
    if ($month == "11" || $month == "11" || $month == 11)
        return 30;
    if ($month == "12" || $month == "12" || $month == 12)
        return 31;
  }
  /**
  * Check Json Event of unit. Type can be availability or price.
  * To run this function i need to CheckUnitState before.
  *
  * @Then /^the "(?P<type>[^"]*)" state from "(?P<start>[^"]*)" to "(?P<end>[^"]*)" should be "(?P<state>[^"]*)"$/
  */
  public function checkJsonEvent($type, $start, $end, $state) {
      
    //Taking start day, start month, start year
    $tmp = explode("/", $start);
    $day_start = $tmp[0];
    $month_start = $tmp[1];
    $year_start = $tmp[2];
    //Taking end day, end month, end year
    $tmp = explode("/", $end);
    $day_end = $tmp[0];
    $month_end = $tmp[1];
    $year_end = $tmp[2];
    
    if ($state == "0")
        $state = "N\/A";
    if ($state == "1")
        $state = "AV";
    if ($state == "2")
        $state = "ON-REQ";
    if ($state == "3")
        $state = $this->customer;
    if ($type == "availability")
        $url = "/?q=rooms/units/unit/".$this->unit_id."/availability/json/";
    if ($type == "price")
        $url = "/?q=admin/rooms/units/unit/".$this->unit_id."/pricing/json/";
    $this->assertAtPath($url.$year_start."/".$month_start."/".$day_start."/".$year_end."/".$month_end."/".$day_end);
    sleep(5);
    $year_count = (int)$year_end - (int)$year_start;
    //Number of Months
    if ($year_count == 0)
      $months = (int)$month_end - (int)$month_start;
    else {
      if ((int)$month_start > (int)$month_end)
          $months = 12*$year_count - ((int)$month_start - (int)$month_end);
      else
          $months = 12*$year_count + ((int)$month_end - (int)$month_start);
    }
    $month = (int)$month_start;
    $year = (int)$year_start;
    for ($i = 0; $i <= $months; $i++) {
      if ($i != 0 && ((int)$month == 13)) {
        $year++;
        $month = 1;
      }
      $end_month = $this->endOfMonth($month, $year);
      //First month (Event begin and end in the same month)
      if ($i == 0 && $type == "availability" && $months == 0)
        {$this->assertPageContainsText('"start":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$day_start.'T13:00:00Z","end":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$day_end.'T13:00:00Z","title":"'.$state.'"');
        printf('"start":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$day_start.'T13:00:00Z","end":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$day_end.'T13:00:00Z","title":"'.$state.'"');
        }
      if ($i == 0 && $type == "price" && $months == 0)
        {$this->assertPageContainsText('"start":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$day_start.'","end":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$day_end.'","color":"green","title":"'.$state.'"');
        printf('"start":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$day_start.'","end":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$day_end.'","color":"green","title":"'.$state.'"');
        }
      //First month (Event begin and end in the different months)
      if ($i == 0 && $type == "availability" && $months != 0)
        {$this->assertPageContainsText('"start":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$day_start.'T13:00:00Z","end":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$end_month.'T13:00:00Z","title":"'.$state.'"');
        printf('"start":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$day_start.'T13:00:00Z","end":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$end_month.'T13:00:00Z","title":"'.$state.'"');
        }
      if ($i == 0 && $type == "price" && $months != 0)
        {$this->assertPageContainsText('"start":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$day_start.'","end":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$end_month.'","color":"green","title":"'.$state.'"');
        printf('"start":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$day_start.'","end":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$end_month.'","color":"green","title":"'.$state.'"');
        }
      //Months between Start month and end month
      if ($i != 0 && $type == "availability" && $i != $months) 
        {$this->assertPageContainsText('"start":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-01T13:00:00Z","end":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$end_month.'T13:00:00Z","title":"'.$state.'"');
        printf('"start":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-01T13:00:00Z","end":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$end_month.'T13:00:00Z","title":"'.$state.'"');
        }
      if ($i != 0 && $type == "price" && $i != $months) 
        {$this->assertPageContainsText('"start":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-01","end":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$end_month.'","color":"green","title":"'.$state.'"');
        printf('"start":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-01","end":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$end_month.'","color":"green","title":"'.$state.'"');
        }
      //Last Month
      if ($i == $months && $type == "availability" && $months != 0)
        {$this->assertPageContainsText('"start":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-01T13:00:00Z","end":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$day_end.'T13:00:00Z","title":"'.$state.'"');
        printf('"start":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-01T13:00:00Z","end":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$day_end.'T13:00:00Z","title":"'.$state.'"');
        break;
        }      
      if ($i == $months && $type == "price" && $months != 0)
        {$this->assertPageContainsText('"start":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-01","end":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$day_end.'","color":"green","title":"'.$state.'"');
        printf('"start":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-01","end":"'.$year.'-'.str_pad($month,2,"0", STR_PAD_LEFT).'-'.$day_end.'","color":"green","title":"'.$state.'"');
        break;
        }
      $month++;
    }
  }
   /**
  * Add a customer. 
  *
  * @Given /^I have a customer "(?P<name>[^"]*)"$/
  */
  public function addCostumer($name) {
    $this->assertAtPath("/admin/commerce/customer-profiles");
    try {
      $this->assertSession()->elementContains('xpath','//TABLE[@CLASS="views-table cols-6"]',$name);
    } catch (Exception $e) {
        $this->iClickOnTheText("Add a customer profile");
        $this->fillField("edit-commerce-customer-address-und-0-name-line", $name);
        $this->fillField("edit-commerce-customer-address-und-0-thoroughfare", "Via Sicilia 72");
        $this->fillField("edit-commerce-customer-address-und-0-postal-code", "97015");
        $this->fillField("edit-commerce-customer-address-und-0-locality", "Ispica");
        $this->fillField("edit-commerce-customer-address-und-0-administrative-area", "RG");
        $this->pressButton("edit-submit");
      }
    
  }
 /**
  * Add a booking for unit Test 
  *
  * @Given /^I have a booking from "(?P<start>[^"]*)" to "(?P<end>[^"]*)"$/
  */
  public function addBooking($start,$end) {
    $price = $this->unit_price * $this->daysBetweenDates($start, $end);
    $this->assertAtPath("/admin/rooms/units");
    $this->unit_id = $this->findElementInATable($this->unit_type_name, 0);
    $this->assertAtPath("/admin/rooms/bookings");
    sleep(3);
    $this->iClickOnTheText("Add a Booking");
    $this->fillField("edit-client", $this->customer);
    sleep(3);
    $this->fillField("rooms_start_date[date]", $start);
    $this->fillField("rooms_end_date[date]", $end);
    $this->pressButton("edit-get-availability");
    sleep(3);
    $this->fillField("edit-unit-type", $this->unit_name);
    sleep(3);
    $this->assertSelectRadioById("Test - Cost: $ ".$price,"edit-unit-id-".$this->unit_id);
    sleep(3);
    $this->checkOption("edit-booking-status");
    $this->pressButton("edit-submit");
    $this->booking_id = $this->findElementInATable($this->customer, 0);
  }
  /**
  * Calculate the number of days between two dates
  */
  public function daysBetweenDates($start, $end) {
    $date_start = explode("/", $start);
    $date_end = explode("/", $end);
    $d1 = mktime(0,0,0,$date_start[1],$date_start[0],$date_start[2]);
    $d2 = mktime(0,0,0,$date_end[1],$date_end[0], $date_end[2]);
    $seconds = $d1 - $d2;
    $days = abs(intval($seconds / 86400));
    return $days;
  }

  /**
  * Add a booking for unit Test
  *
  * @When /^I add a booking from "(?P<start>[^"]*)" to "(?P<end>[^"]*)"$/
  */
  public function addNewBooking($start,$end) {
    $this->assertAtPath("/booking");
    sleep(3);
    $this->fillField("rooms_start_date[date]", $start);
    $this->fillField("rooms_end_date[date]", $end);
    $this->pressButton("edit-submit");
    sleep(5);
    $this->assertPageContainsText("Arrival Date ".$start);
    $this->assertPageContainsText("Departure Date ".$end);
    $this->assertPageContainsText("Nights ".($this->daysBetweenDates($start,$end)));
    $this->fillField("edit-".$this->unit_name."-".$this->daysBetweenDates($start,$end) * $this->unit_price."-quantity", "1");
    sleep(3);
    $this->pressButton("edit-place-booking");
    sleep(3);
    $this->pressButton("edit-checkout");

    $page = $this->getSession()->getCurrentUrl();
    //Taking Order ID
    $tmp = explode("/", $page);
    $this->order_id = $tmp[5];
    $this->fillField("edit-customer-profile-billing-commerce-customer-address-und-0-name-line", $this->customer);
    $this->fillField("edit-customer-profile-billing-commerce-customer-address-und-0-thoroughfare", "Via Roma 123");
    $this->fillField("edit-customer-profile-billing-commerce-customer-address-und-0-postal-code", "123456");
    $this->fillField("edit-customer-profile-billing-commerce-customer-address-und-0-locality", "Ragusa");
    $this->fillField("edit-customer-profile-billing-commerce-customer-address-und-0-administrative-area", "RG");
    $this->pressButton("edit-continue");
    $this->pressButton("edit-continue");
    //taking booking id
    $this->assertAtPath("/admin/rooms/bookings");
    $this->booking_id = $this->findElementInATable($this->order_id, 0);
    printf("Booking ID is : " .$this->booking_id);
  }
  /**
  * Check Order
  *
  * @Given /^I should see the order from "(?P<start>[^"]*)" to "(?P<end>[^"]*)"$/
  */
  public function checkOrder($start, $end) {
    $this->assertAtPath("/admin/commerce/orders");
    $date_start = explode("/", $start);
    $date_end = explode("/", $end); 
    $nights = $this->daysBetweenDates($start,$end);
    $this->fillField("edit-order-identifier", $this->order_id);
    $this->pressButton("edit-submit");
    $this->assertPageContainsText("Booking for ".$this->unit_type_name. " (".$nights." Nights; Arrival: ".$date_start[0]."-".$date_start[1]."-".$date_start[2]." Departure: ".$date_end[0]."-".$date_end[1]."-".$date_end[2].")");
  }
  /**
  * Check DB (I need to check the unit state to have unit_id)
  *
  * @Then /^Unit Calendar should confirm booking from "(?P<start>[^"]*)" to "(?P<end>[^"]*)"$/
  */
  public function checkDatabase($start, $end) {
    //taking unit id.
    $this->assertAtPath("/admin/rooms/units");
    $this->unit_id = $this->findElementInATable($this->unit_type_name, 0);
    $booking_id = rooms_availability_assign_id($this->booking_id, 1);
    $nights = $this->daysBetweenDates($start, $end);
    $states = array();
    for ($i = 0; $i < $nights; $i++)
      $states[$i] = $booking_id;
    $rc = new UnitCalendar($this->unit_id);
    $start_date = datetime::createfromformat('d/m/Y',$start);
    $end_date = datetime::createfromformat('d/m/Y',$end);
    $valid = $rc->stateAvailability($start_date, $end_date, $states);
    if ( ! $valid ) 
      throw new Exception('Invalid state');
  }
  /**
  * Return an element (given we know the position) from a table in a row where is 
  * present an ID
  */
  public function findElementInATable($id, $position) {
    $page = $this->getSession()->getPage();
    $rows = $page->findAll('css', 'tr');
    $element = "null";
    foreach ($rows as $row) {
      $cells = $row->findAll('css', 'td');
      //Loop through each child (cell) of the row 
      $tmp[] = null;
      $i = 0;
      foreach ($cells as $cell) {
        $tmp[$i] = $cell->getText();
        $i++;
      }
      foreach ($tmp as $value)
        if ($value == $id) {
         $element = $tmp[$position];
        }
      if ($element != "null")
        break;
    }
    return $element;
  }
}


