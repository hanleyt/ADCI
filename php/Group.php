<?php
include("Level.php");

/**
 * Created by IntelliJ IDEA.
 * User: tomashanley
 * Date: 17/01/2017
 * Time: 20:31
 */
class Group
{
    public $name, $level;

    public function __construct($name, $level)
    {
        $this->name = $name;
        $this->level = $level;
    }

    function isOpen()
    {
        return strtolower($this->level) == strtolower(Level::OPEN);
    }

}
