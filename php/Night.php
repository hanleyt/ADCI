<?php

/**
 * Created by IntelliJ IDEA.
 * User: tomashanley
 * Date: 11/01/2017
 * Time: 21:36
 */
class Night {

    public $date, $group, $play;

    public function __construct($date, $group, $play) {
        $this->date = $date;
        $this->group = $group;
        $this->play = $play;
    }

}
