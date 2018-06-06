<?php
/**
 * Buddies Controller
 *
 * PHP Version 5.5+
 *
 * @category Controller
 * @package  Application
 * @author   XG Proyect Team
 * @license  http://www.xgproyect.org XG Proyect
 * @link     http://www.xgproyect.org
 * @version  3.1.0
 */
namespace application\controllers\game;

use application\core\Controller;
use application\core\entities\BuddyEntity;
use application\libraries\buddies\Buddy;
use application\libraries\enumerators\BuddiesStatusEnumerator as BuddiesStatus;
use application\libraries\FunctionsLib;

/**
 * Buddies Class
 *
 * @category Classes
 * @package  Application
 * @author   XG Proyect Team
 * @license  http://www.xgproyect.org XG Proyect
 * @link     http://www.xgproyect.org
 * @version  3.1.0
 */
class Buddies extends Controller
{

    const MODULE_ID = 20;

    /**
     *
     * @var type \Users_library
     */
    private $_user;

    /**
     *
     * @var array
     */
    private $_buddy = null;
    
    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        // check if session is active
        parent::$users->checkSession();

        // load Model
        parent::loadModel('game/buddies');

        // Check module access
        FunctionsLib::moduleMessage(FunctionsLib::isModuleAccesible(self::MODULE_ID));

        // set data
        $this->_user = $this->getUserData();

        // init a new buddy object
        $this->setUpBudies();
        
        // time to do something
        $this->runAction();
        
        // build the page
        $this->buildPage();
    }

    /**
     * Creates a new buddy object that will handle all the buddies
     * creation methods and actions
     * 
     * @return void
     */
    private function setUpBudies()
    {
        $this->_buddy = new Buddy(
            $this->Buddies_Model->getBuddiesByUserId($this->_user['user_id'])
        );
    }
    
    /**
     * Run an action
     * 
     * @return void
     */
    private function runAction()
    {
        $mode = filter_input(INPUT_GET, 'mode', FILTER_VALIDATE_INT);
        $sm = filter_input(INPUT_GET, 'sm', FILTER_VALIDATE_INT);
        $bid = filter_input(INPUT_GET, 'bid', FILTER_VALIDATE_INT);
        $user = filter_input(INPUT_GET, 'u', FILTER_VALIDATE_INT);
        
        $allowed_modes = [
            1 => 'runAction',
            2 => 'buddyRequest'
        ];
        
        $allowed_actions = [
            1 => 'rejectRequest',
            2 => 'acceptRequest',
            3 => 'sendRequest'
        ];
        
        if (in_array($mode, $allowed_modes)) {
        
            if (in_array($sm, $allowed_actions)) {

                $this->$allowed_modes[$mode]($allowed_actions[$sm]);
            }
        }
    }
    
    /**
     * Build the page
     * 
     * @return void
     */
    private function buildPage()
    {
        /**
         * Parse the items
         */
        $page = [];
        $page['list_of_requests_received'] = $this->buildListOfRequestsReceived();
        $page['list_of_requests_sent'] = $this->buildListOfRequestsSent();
        $page['list_of_buddies'] = $this->buildListOfBuddies();

        // display the page
        parent::$page->display(
            $this->getTemplate()->set(
                'buddies/buddies_view', array_merge($page, $this->getLang())
            )
        );

        
        $mode = isset($_GET['mode']) ? intval($_GET['mode']) : NULL;
        $bid = isset($_GET['bid']) ? intval($_GET['bid']) : NULL;
        $sm = isset($_GET['sm']) ? intval($_GET['sm']) : NULL;
        $user = isset($_GET['u']) ? intval($_GET['u']) : NULL;
        $this->_lang['js_path'] = JS_PATH;
        $parse = $this->_lang;
        $requestsSended = '';
        $requestsReceived = '';
        $budys = '';

        switch ($mode) {
            case 1:

                switch ($sm) {
                    // REJECT / CANCEL
                    case 1:

                        $senderID = $this->_db->queryFetch("SELECT *
																	FROM " . BUDDY . "
																	WHERE `buddy_id`='" . intval($bid) . "'");

                        if ($senderID['buddy_status'] == 0) {
                            if ($senderID['buddy_sender'] != $this->_current_user['user_id']) {
                                FunctionsLib::sendMessage($senderID['buddy_sender'], $this->_current_user['user_id'], '', 5, $this->_current_user['user_name'], $this->_lang['bu_rejected_title'], str_replace('%u', $this->_current_user['user_name'], $this->_lang['bu_rejected_text']));
                            } elseif ($senderID['buddy_sender'] == $this->_current_user['user_id']) {
                                FunctionsLib::sendMessage($senderID['buddy_receiver'], $this->_current_user['user_id'], '', 5, $this->_current_user['user_name'], $this->_lang['bu_rejected_title'], str_replace('%u', $this->_current_user['user_name'], $this->_lang['bu_rejected_title']));
                            }
                        } else {
                            if ($senderID['buddy_sender'] != $this->_current_user['user_id']) {
                                FunctionsLib::sendMessage($senderID['buddy_sender'], $this->_current_user['user_id'], '', 5, $this->_current_user['user_name'], $this->_lang['bu_deleted_title'], str_replace('%u', $this->_current_user['user_name'], $this->_lang['bu_deleted_text']));
                            } elseif ($senderID['buddy_sender'] == $this->_current_user['user_id']) {
                                FunctionsLib::sendMessage($senderID['buddy_receiver'], $this->_current_user['user_id'], '', 5, $this->_current_user['user_name'], $this->_lang['bu_deleted_title'], str_replace('%u', $this->_current_user['user_name'], $this->_lang['bu_deleted_text']));
                            }
                        }

                        $this->_db->query("DELETE FROM " . BUDDY . "
												WHERE `buddy_id`='" . intval($bid) . "' AND
														(`buddy_receiver`='" . $this->_current_user['user_id'] . "' OR `buddy_sender`='" . $this->_current_user['user_id'] . "') ");

                        FunctionsLib::redirect('game.php?page=buddy');

                        break;

                    // ACCEPT
                    case 2:

                        $senderID = $this->_db->queryFetch("SELECT *
																FROM " . BUDDY . "
																WHERE `buddy_id`='" . intval($bid) . "'");

                        FunctionsLib::sendMessage($senderID['buddy_sender'], $this->_current_user['user_id'], '', 5, $this->_current_user['user_name'], $this->_lang['bu_accepted_title'], str_replace('%u', $this->_current_user['user_name'], $this->_lang['bu_accepted_text']));

                        $this->_db->query("UPDATE " . BUDDY . "
												SET `buddy_status` = '1'
												WHERE `buddy_id` ='" . intval($bid) . "' AND
														`buddy_receiver`='" . $this->_current_user['user_id'] . "'");

                        FunctionsLib::redirect('game.php?page=buddy');

                        break;

                    // SEND REQUEST
                    case 3:

                        $query = $this->_db->queryFetch("SELECT `buddy_id`
                            FROM " . BUDDY . "
                            WHERE (`buddy_receiver`='" . intval($this->_current_user['user_id']) . "' AND
                                            `buddy_sender`='" . intval($_POST['user']) . "') OR
                                            (`buddy_receiver`='" . intval($_POST['user']) . "' AND
                                                    `buddy_sender`='" . intval($this->_current_user['user_id']) . "')");

                        if (!$query) {

                            $text = $this->_db->escapeValue(strip_tags($_POST['text']));

                            FunctionsLib::sendMessage(intval($_POST['user']), $this->_current_user['user_id'], '', 5, $this->_current_user['user_name'], $this->_lang['bu_to_accept_title'], str_replace('%u', $this->_current_user['user_name'], $this->_lang['bu_to_accept_text']));

                            $this->_db->query("INSERT INTO " . BUDDY . " SET
                                `buddy_sender`='" . intval($this->_current_user['user_id']) . "',
                                `buddy_receiver`='" . intval($_POST['user']) . "',
                                `buddy_status`='0',
                                `buddy_request_text`='" . $text . "'");

                            FunctionsLib::redirect('game.php?page=buddy');
                        } else {
                            FunctionsLib::message($this->_lang['bu_request_exists'], 'game.php?page=buddy', 2, false, false, false);
                        }

                        break;
                    // ANY OTHER OPTION EXIT
                    default:

                        FunctionsLib::redirect('game.php?page=buddy');

                        break;
                }

                break;

            // FRIENDSHIP REQUEST
            case 2:

                // IF USER = REQUESTED USER, SHOW ERROR.
                if ($user == $this->_current_user['user_id']) {
                    FunctionsLib::message($this->_lang['bu_cannot_request_yourself'], 'game.php?page=buddy', 2, false, false, false);
                } else {
                    // SEARCH THE PLAYER
                    $player = $this->_db->queryFetch("SELECT `user_name`
                        FROM " . USERS . "
                        WHERE `user_id`='" . intval($user) . "'");

                    // IF PLAYER EXISTS, PROCEED
                    if ($player) {
                        $parse['user'] = $user;
                        $parse['player'] = $player['user_name'];

                        parent::$page->display(parent::$page->parseTemplate(parent::$page->getTemplate('buddy/buddy_request'), $parse));
                    } else { // EXIT
                        FunctionsLib::redirect('game.php?page=buddy');
                    }
                }

                break;

            // NOTHING SELECTED
            default:

                $getBuddys = $this->_db->query("SELECT *
                    FROM " . BUDDY . "
                    WHERE `buddy_sender`='" . intval($this->_current_user['user_id']) . "' OR
                                    `buddy_receiver`='" . intval($this->_current_user['user_id']) . "'");

                $subTemplate = parent::$page->getTemplate('buddy/buddy_row');

                while ($buddy = $this->_db->fetchAssoc($getBuddys)) {
                    if ($buddy['buddy_status'] == 0) {
                        if ($buddy['buddy_sender'] == $this->_current_user['user_id']) {
                            $buddy_receiver = $this->_db->queryFetch("SELECT u.`user_id`, u.`user_name`, u.`user_galaxy`, u.`user_system`, u.`user_planet`, u.`user_ally_id`, a.`alliance_name`
																			FROM " . USERS . " AS u
																			LEFT JOIN `" . ALLIANCE . "` AS a ON a.`alliance_id` = u.`user_ally_id`
																			WHERE u.`user_id`='" . intval($buddy['buddy_receiver']) . "'");

                            $parse['id'] = $buddy_receiver['user_id'];
                            $parse['username'] = $buddy_receiver['user_name'];
                            $parse['ally_id'] = $buddy_receiver['user_ally_id'];
                            $parse['alliance_name'] = $buddy_receiver['alliance_name'];
                            $parse['galaxy'] = $buddy_receiver['user_galaxy'];
                            $parse['system'] = $buddy_receiver['user_system'];
                            $parse['planet'] = $buddy_receiver['user_planet'];
                            $parse['text'] = $buddy['buddy_request_text'];
                            $parse['action'] = '<a href="game.php?page=buddy&mode=1&sm=1&bid=' . $buddy['buddy_id'] . '">' . $this->_lang['bu_cancel_request'] . '</a>';

                            $requestsSended .= parent::$page->parseTemplate($subTemplate, $parse);
                        } else {
                            $buddy_sender = $this->_db->queryFetch("SELECT u.`user_id`, u.`user_name`, u.`user_galaxy`, u.`user_system`, u.`user_planet`, u.`user_ally_id`, a.`alliance_name`
																			FROM " . USERS . " as u
																			LEFT JOIN `" . ALLIANCE . "` AS a ON a.`alliance_id` = u.`user_ally_id`
																			WHERE `user_id`='" . intval($buddy['buddy_sender']) . "'");

                            $parse['id'] = $buddy_sender['user_id'];
                            $parse['username'] = $buddy_sender['user_name'];
                            $parse['ally_id'] = $buddy_sender['user_ally_id'];
                            $parse['alliance_name'] = $buddy_sender['alliance_name'];
                            $parse['galaxy'] = $buddy_sender['user_galaxy'];
                            $parse['system'] = $buddy_sender['user_system'];
                            $parse['planet'] = $buddy_sender['user_planet'];
                            $parse['text'] = $buddy['buddy_request_text'];
                            $parse['action'] = '<a href="game.php?page=buddy&mode=1&sm=2&bid=' . $buddy['buddy_id'] . '">' . $this->_lang['bu_accept'] . '</a><br /><a href="game.php?page=buddy&mode=1&sm=1&bid=' . $buddy['buddy_id'] . '">' . $this->_lang['bu_decline'] . '</a>';

                            $requestsReceived .= parent::$page->parseTemplate($subTemplate, $parse);
                        }
                    } else {
                        $who = $buddy['buddy_sender'] == $this->_current_user['user_id'] ? intval($buddy['buddy_receiver']) : intval($buddy['buddy_sender']);
                        $buddy_receiver = $this->_db->queryFetch("SELECT u.`user_id`, u.`user_name`, u.`user_galaxy`, u.`user_system`, u.`user_planet`, u.`user_onlinetime`, u.`user_ally_id`, a.`alliance_name`
																			FROM " . USERS . " as u
																			LEFT JOIN `" . ALLIANCE . "` AS a ON a.`alliance_id` = u.`user_ally_id`
																			WHERE `user_id`='" . $who . "'");

                        $parse['id'] = $buddy_receiver['user_id'];
                        $parse['username'] = $buddy_receiver['user_name'];
                        $parse['ally_id'] = $buddy_receiver['user_ally_id'];
                        $parse['alliance_name'] = $buddy_receiver['alliance_name'];
                        $parse['galaxy'] = $buddy_receiver['user_galaxy'];
                        $parse['system'] = $buddy_receiver['user_system'];
                        $parse['planet'] = $buddy_receiver['user_planet'];
                        $parse['text'] = '<font color="' . ( ( $buddy_receiver['user_onlinetime'] + 60 * 10 >= time() ) ? 'lime">' . $this->_lang['bu_connected'] . '' : ( ( $buddy_receiver['user_onlinetime'] + 60 * 15 >= time() ) ? 'yellow">' . $this->_lang['bu_fifteen_minutes'] : 'red">' . $this->_lang['bu_disconnected'] ) ) . '</font>';
                        $parse['action'] = '<a href="game.php?page=buddy&mode=1&sm=1&bid=' . $buddy['buddy_id'] . '">' . $this->_lang['bu_delete'] . '</a>';

                        $budys .= parent::$page->parseTemplate($subTemplate, $parse);
                    }
                }

                $parse['request_received'] = $requestsSended;
                $parse['request_sended'] = $requestsReceived;
                $parse['buddys'] = $budys;

                parent::$page->display(parent::$page->parseTemplate(parent::$page->getTemplate('buddy/buddy_body'), $parse));

                break;
        }
    }
    
    /**
     * Build the list of requests received
     * 
     * @return string
     */
    private function buildListOfRequestsReceived()
    {
        $received_requests = $this->_buddy->getReceivedRequests();
        $rows = [];
        
        if ($this->hasAny($received_requests)) {

            foreach ($received_requests as $received) {
                
                $rows[] = $this->extractPlayerData($received);
            }   
        }
        
        return $rows;
    }
    
    /**
     * Build the list of requests sent
     * 
     * @return string
     */
    private function buildListOfRequestsSent()
    {
        $requests_sent = $this->_buddy->getSentRequests();
        $rows = [];
        
        if ($this->hasAny($requests_sent)) {

            foreach ($requests_sent as $sent) {

                $rows[] = $this->extractPlayerData($sent);
            }   
        }
        
        return $rows;
    }

    /**
     * Build the list of buddies
     * 
     * @return array
     */
    private function buildListOfBuddies()
    {
        $buddies = $this->_buddy->getBuddies();
        $rows = [];
        
        if ($this->hasAny($buddies)) {

            foreach ($buddies as $buddy) {

                $rows[] = $this->extractPlayerData($buddy);
            }   
        }

        return $rows;
    }
    
    /**
     * Extract player data based on provided object
     * 
     * @param BuddyEntity $buddy Buddy Entity Object
     * 
     * @return arrau
     */
    private function extractPlayerData(BuddyEntity $buddy)
    {
        return [
            'id' => '',
            'username' => '',
            'ally_id' => '',
            'alliance_name' => '',
            'galaxy' => '',
            'system' => '',
            'planet' => '',
            'text' => $this->setText($buddy),
            'action' => ''
        ];
    }
    
    /**
     * Set the text
     * 
     * @param BuddyEntity $buddy Buddy
     * 
     * @return string
     */
    private function setText(BuddyEntity $buddy)
    {
        if ($buddy->getBuddyStatus() == BuddiesStatus::isBuddy) {
            
            return $this->setOnlineStatus();
        } else {
            
            return $buddy->getRequestText();
        }
    }
    
    private function setOnlineStatus()
    {
        return '';
    }
    
    /**
     * Check if there's anything
     * 
     * @param array $array Array
     * 
     * @return boolean
     */
    private function hasAny($array)
    {
        return (count($array) > 0);
    }
}

/* end of buddy.php */
