<?php
namespace Election;
require("ElectionStatus.php");
require("VotingStatus.php");
require("Regions.php");
require("Config.php");
require("Database/DataBaseSingleton.php");
use DataBaseSingleton;
use OAuth;
class ElectionController
{
    public function getContent($canVote = -1) {
        switch(Config::ELECTION_PHASE) {
            case ElectionStatus::ELECTIONS_OFF: {
                return $this->errorMessage('W tej chwili na OC.pl nie trwa żadne głosowanie :)');
            } break;
            case ElectionStatus::WAITING_FOR_CANDIDATES: {
                return $this->waitingForCandidatesMessage();
            } break;
            case ElectionStatus::VOTING: {
                switch($canVote) {
                    case VotingStatus::ALL_OK: {
                        return $this->voteForm();
                    } break;
                    case VotingStatus::SHOW_LOGIN_BUTTON: {
                        return $this->youHaveToLogIn().$this->getSingUpButton();
                    } break;
                    case VotingStatus::FOUNDS_COUNT_ERROR: {
                        $_SESSION["canVote"] = VotingStatus::SHOW_LOGIN_BUTTON;
                        return $this->errorMessage('Niestety nie masz możliwości oddania głosu. Masz za mało znalezionych keszy!');
                    } break;
                    case VotingStatus::USER_TOO_YOUNG_ERROR: {
                        $_SESSION["canVote"] = VotingStatus::SHOW_LOGIN_BUTTON;
                        return $this->errorMessage('Niestety Twój staż na OC jest za krótki byś mógł zagłosować w tych wyborach.');
                    } break;
                    case VotingStatus::VOTED_ALREADY_ERROR: {
                        $_SESSION["canVote"] = VotingStatus::SHOW_LOGIN_BUTTON;
                        return $this->errorMessage('Już głosowałeś w tych wyborach!');
                    } break;
                    case VotingStatus::EMPTY_VOTE_ERROR: {
                        $_SESSION["canVote"] = VotingStatus::SHOW_LOGIN_BUTTON;
                        return $this->errorMessage('Nie możesz oddać pustego głosu!<br />Spróbuj ponownie.'.$this->getSingUpButton());
                    } break;
                    case VotingStatus::TO_MANY_VOTE_ERROR: {
                        $_SESSION["canVote"] = VotingStatus::SHOW_LOGIN_BUTTON;
                        return $this->errorMessage('Wybrałeś zbyt wielu kandydatów!');
                    } break;
                    case VotingStatus::SUCCESS: {
                        $_SESSION["canVote"] = VotingStatus::SHOW_LOGIN_BUTTON;
                        session_destroy();
                        return '<p style="color: #006600;">Dziękujemy za oddanie głosu!</p>';
                    } break;
                }
            } break;
            case ElectionStatus::FINISHED: {
                return $this->resultTable();
            } break;
        }
        return null;
    }

    public function addResultsToDb() {
        if(empty($_POST['votes'])) {
            $_SESSION["canVote"] = VotingStatus::EMPTY_VOTE_ERROR;
        }
        else
        {
            $vote_count = count($_POST['votes']);
            if($vote_count > Config::VOTES_COUNT) {
                $_SESSION["canVote"] = VotingStatus::TO_MANY_VOTE_ERROR;
            }
            else {
                for($i=0; $i < $vote_count; $i++) {
                    $db = DataBaseSingleton::Instance();
                    $query = "INSERT INTO `votes`(`candidate_id`, `userid`) VALUES (:1, :2)";
                    $db->multiVariableQuery($query, $_POST['votes'][$i], $_SESSION['voter_id']);
                }
                $_SESSION["canVote"] = VotingStatus::SUCCESS;
            }
        }
    }

    public function getElectionTitle() {
        if(Config::ELECTION_PHASE != ElectionStatus::ELECTIONS_OFF) {
            return '<p class="bottom">'.Config::ELECTION_TITLE.'</p>';
        }
        return '';
    }

    private function errorMessage($message)
    {
        return '<p style="color: #ff0000;">'.$message.'</p>';
    }

    private function youHaveToLogIn() {
        return '<p>Aby zagłosować w wyborach musisz podpisać głos swoim kontem OC. Kliknij przycisk poniżej by rozpocząć tą procedurę:</p>';
    }

    private function getSingUpButton() {
        return
        '<div id="form">
            <form action="index.php" method="POST">
                <div class="field-holder">
                    <button type="submit" class="submit">Zaloguj mnie!</button>
                    <input type="hidden" name="logMeIn" value="1"
                </div>
                <div class="clear"></div>
            </form>
        </div>
        ';
    }

    private function voteForm() {
        $content = '<p>Głosujesz jako: <b>'.$_SESSION['username'].'</b></p><p>Możesz zagłosować maksymalnie na trzy osoby. Głosy możesz rozdysponować pomiędzy dowolne województwa. Głosować możesz tylko raz. Oddanego głosu nie można zmienić.</p><br />';
        $content .= '<div id="form">
            <form action="index.php" method="POST">';
        foreach (Region::$arr as $region) {
            $content .= "<br /><hr /><b>Województwo ".$region."</b>";
            $content .= $this->getCandidatesFromRegion($region);
        }
        $content .= '<br /><br />
                <div class="field-holder">
                    <button type="submit" class="submit">Oddaję głos!</button>
                    <input type="hidden" name="voted" value="1" />
                </div>
                <div class="clear"></div>
            </form>
        </div>
        ';
        return $content;
    }

    private function resultTable() {
        $content = '<p>Wyniki głosowania:</p><br />';
        $content .= '<div id="form">';
        foreach (Region::$arr as $region) {
            $content .= "<br /><hr /><b>Województwo ".$region."</b>";
            $content .= $this->getCandidatesScore($region);
        }
        $content .= '<br /><br />
        </div>
        ';
        return $content;
    }

    private function getCandidatesScore($region) {
        $query = '
        SELECT COUNT(*) AS `vote_count`, `candidates`.`user_name`, `candidates`.`user_oc_id`
        FROM `candidates`
        JOIN `votes` ON `candidates`.`user_oc_id` = `votes`.`candidate_id`
        WHERE `region` = :1
        GROUP BY `votes`.`candidate_id`
        ORDER BY `vote_count` DESC';
        $db = DataBaseSingleton::Instance();
        $db->multiVariableQuery($query, $region);
        $scoreTable = '<div style="text-align: left;">';
        while (true) {
            $cacheDbRow = $db->dbResultFetch();
            if (is_array($cacheDbRow)) {
                $scoreTable .= '<a href="http://opencaching.pl/viewprofile.php?userid='.$cacheDbRow['user_oc_id'].'" target="_blank">'.$cacheDbRow['user_name'].'</a> ('.$cacheDbRow['vote_count'].')<br />';
            }
            else {
                break;
            }
        }
        $scoreTable .= '</div>';
        return $scoreTable;
    }

    private function getCandidatesFromRegion($region)
    {
        $regionForm = '<div style="text-align: left;">';
        $db = DataBaseSingleton::Instance();
        $query = "SELECT `user_oc_id`, `user_name` FROM `candidates` WHERE `region`=:1 ORDER BY `user_name`";
        $db->multiVariableQuery($query, $region);
        while (true) {
            $cacheDbRow = $db->dbResultFetch();
            if (is_array($cacheDbRow)) {
                $regionForm .= '<input type="checkbox" name="votes[]" value="'.$cacheDbRow['user_oc_id'].'" /><a href="http://opencaching.pl/viewprofile.php?userid='.$cacheDbRow['user_oc_id'].'" target="_blank">'.$cacheDbRow['user_name'].'</a><br />';
            }
            else {
                break;
            }
        }
        $regionForm .= '</div>';
        return $regionForm;
    }

    public function waitingForCandidatesMessage(){
        $text = '
        <p>Aktualnie trwa proces zbierania kandydatów do wyborów. Możesz złożyć swoją kandydaturę na forum.
        O <a href="http://forum.opencaching.pl" target="_blank">tutaj</a>.</p>
        ';
        return $text;
    }

    public function logMeIn() {
        $oauth = new OAuth(Config::CONSUMER_KEY, Config::SECRET_KEY, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
        $oauth->disableSSLChecks();
        $request_token_info = $oauth->getRequestToken("http://opencaching.pl/okapi/services/oauth/request_token", config::DOMAIN."index.php");
        $_SESSION["token_secret"] = $request_token_info['oauth_token_secret'];
        header('Location: http://opencaching.pl/okapi/services/oauth/authorize?interactivity=confirm_user&oauth_token='.$request_token_info['oauth_token']);
    }

    public function authorizeMe($token) {
        $oauth = new OAuth(Config::CONSUMER_KEY, Config::SECRET_KEY, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
        $oauth->setToken($token, $_SESSION["token_secret"]);
        $access_token_info = $oauth->getAccessToken('http://opencaching.pl/okapi/services/oauth/access_token');
        $oauth->setToken($access_token_info["oauth_token"], $access_token_info["oauth_token_secret"]);
        $oauth->fetch('http://opencaching.pl/okapi/services/users/user?fields=username|internal_id|caches_found|date_registered');
        $json = json_decode($oauth->getLastResponse());
        if($this->compareTime($json->{'date_registered'})) {
            $_SESSION['canVote'] = VotingStatus::USER_TOO_YOUNG_ERROR;
        } else if($json->{'caches_found'} <= Config::REQUIRED_FOUNDS) {
            $_SESSION['canVote'] = VotingStatus::FOUNDS_COUNT_ERROR;
        } else if($this->userAlreadyVoted($json->{'internal_id'})) {
            $_SESSION['canVote'] = VotingStatus::VOTED_ALREADY_ERROR;
        } else {
            $_SESSION['canVote'] = VotingStatus::ALL_OK;
            $_SESSION['voter_id'] = $json->{'internal_id'};
            $_SESSION['username'] = $json->{'username'};
        }
        unset($_SESSION["token_secret"]);
        header('Location: '.Config::DOMAIN.'index.php'); //we want remove secret data from URI
        exit();
    }

    private function compareTime($time) {
        $registration_time = new \DateTime($time);
        $required_time = new \DateTime("now");
        $required_time->sub(new \DateInterval(Config::REQUIRED_ACCOUNT_AGE));
        $time_diff = $registration_time->diff($required_time);
        return $time_diff->format("%r");
    }

    private function userAlreadyVoted($userid){
        $db = DataBaseSingleton::Instance();
        $query = "SELECT `userid` FROM `votes` WHERE `userid`=:1";
        $db->multiVariableQuery($query, $userid);
        $cacheDbRow = $db->dbResultFetch();
        return is_array($cacheDbRow);
    }
}
?>