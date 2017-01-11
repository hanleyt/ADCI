<?php
include("Night.php");
/**
 * Created by IntelliJ IDEA.
 * User: tomashanley
 * Date: 11/01/2017
 * Time: 21:35
 */
class Festival {

    public $name, $addr1, $addr2, $addr3, $ordinal, $url, $adjudicator, $admission, $booking, $times;

    public $nights;
    var $nightsArrayPosition = 0;

    public function __construct($name, $addr1, $addr2, $addr3, $ordinal, $url, $adjudicator, $admission, $booking, $times) {
        $this->name = $name;
        $this->addr1 = $addr1;
        $this->addr2 = $addr2;
        $this->addr3 = $addr3;
        $this->ordinal = $ordinal;
        $this->url = $url;
        $this->adjudicator = $adjudicator;
        $this->admission = $admission;
        $this->booking = $booking;
        $this->times = $times;

        $nights = array();
        $nightsArrayPosition = 0;
    }

    function addNight($date, $group, $play){
        $night = new Night($date, $group, $play);
        $this->nights[$this->nightsArrayPosition] = $night;
        $this->nightsArrayPosition++;
    }

    function getFormattedHeader()
    {
        $returnString = "";

        $returnString .= "<h1>";
        if ($this->ordinal != null) {
            $returnString .= $this->ordinal . " ";
        }
        $returnString .= $this->name . " Drama Festival</h1>";

        if ($this->addr1 != null) {
            $returnString .= "<h2>" . $this->addr1 . ", ";
        }
        if ($this->addr2 != null) {
            $returnString .= $festival->addr2 . ", ";
        }
        if ($this->addr3 != null) {
            $returnString .= "Co. " . $this->addr3 . "</h2>";
        }
        if ($this->url != null) {
            $returnString .= "<h3><a href=\"http://$this->url\">$this->url</a></h3>";
        }
        if ($this->adjudicator != null) {
            $returnString .= "<h3>Adjudicator: " . $this->adjudicator . "</h3>";
        }
        if ($this->admission != null) {
            $returnString .= "<h3>Admission: " . $this->admission . "</h3>";
        }
        if ($this->booking != null) {
            $returnString .= "<h3>Booking: " . $this->booking . "</h3>";
        }
        if ($this->times != null) {
            $returnString .= "<h3>Curtain: " . $this->times . "</h3>";
        }

        return $returnString;
    }

}


