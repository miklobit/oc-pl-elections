<?php
namespace Election;

class VotingStatus
{
    const SHOW_LOGIN_BUTTON = 0; //init status
    const ALL_OK = 1; //show form for voting
    const FOUNDS_COUNT_ERROR = 2;
    const USER_TOO_YOUNG_ERROR = 3;
    const VOTED_ALREADY_ERROR = 4;
    const EMPTY_VOTE_ERROR = 5;
    const TO_MANY_VOTE_ERROR = 6;
    const SUCCESS = 7;
}
?>