<?php
namespace Election;

class Config
{
    const ELECTION_TITLE = "COG 2016";
    const ELECTION_PHASE = ElectionStatus::ELECTIONS_OFF;
    const REQUIRED_FOUNDS = 100; //Minimal cache count value
    const REQUIRED_ACCOUNT_AGE = "P30D"; //30 days
    const VOTES_COUNT = 3; //How many candidates, user can point

    //DB settings
    const DB_ADDRESS = "";
    const DB_USER =  "";
    const DB_PASS = "";
    const DB_DATABASE = "";

    //OKAPI settings
    const DOMAIN = "";
    const CONSUMER_KEY = "";
    const SECRET_KEY = "";
}
?>