<?php
include("Night.php");

/**
 * Created by IntelliJ IDEA.
 * User: tomashanley
 * Date: 11/01/2017
 * Time: 21:35
 */
class Festival
{

    var $name, $addr1, $addr2, $addr3, $ordinal, $url, $adjudicator, $admission, $booking, $times;

    var $nights;
    var $nightsArrayPosition;

    public function __construct($name, $addr1, $addr2, $addr3, $ordinal, $url, $adjudicator, $admission, $booking, $times)
    {
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

        $this->nights = array();
        $this->nightsArrayPosition = 0;
    }

    function addNight($date, $group, $play)
    {
        $night = new Night($date, $group, $play);
        $this->nights[$this->nightsArrayPosition] = $night;
        $this->nightsArrayPosition++;
    }

    function getNights(){
        return $this->nights;
    }

    function length(){
        return count($this->nights);
    }

    function getFormattedHeader()
    {
        $returnString = "<header class=\"festival-header\">\n";

        $returnString .= "<h1>";
        if ($this->ordinal != null) {
            $returnString .= $this->ordinal . " ";
        }
        $returnString .= $this->name . " Drama Festival</h1>\n";

        if ($this->addr1 != null) {
            $returnString .= "<h2>" . $this->addr1 . ", ";
        }
        if ($this->addr2 != null) {
            $returnString .= $this->addr2 . ", ";
        }
        if ($this->addr3 != null) {
            $returnString .= "Co. " . $this->addr3 . "</h2>\n";
        }
        if ($this->url != null) {
            $returnString .= "<h3><a href=\"http://$this->url\">$this->url</a></h3>\n";
        }
        if ($this->adjudicator != null) {
            $returnString .= "<h3>Adjudicator: " . $this->adjudicator . "</h3>\n";
        }
        if ($this->admission != null) {
            $returnString .= "<h3>Admission: " . $this->admission . "</h3>\n";
        }
        if ($this->booking != null) {
            $returnString .= "<h3>Booking: " . $this->booking . "</h3>\n";
        }
        if ($this->times != null) {
            $returnString .= "<h3>Curtain: " . $this->times . "</h3>\n";
        }
        $returnString .= "</header>\n";

        return $returnString;
    }

}


