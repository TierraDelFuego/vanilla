<?php
/**
 * InThisDiscussion module.
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package InThisDiscussion
 */

/**
 * Renders a list of users who are taking part in a particular discussion.
 */
class InThisDiscussionModule extends Gdn_Module {

    /** @var array */
    protected $_UserData;

    /**
     *
     *
     * @param string $Sender
     */
    public function __construct($Sender = '') {
        $this->_UserData = false;
        parent::__construct($Sender);
    }

    /**
     *
     *
     * @param $DiscussionID
     * @param int $Limit
     * @throws Exception
     */
    public function getData($DiscussionID, $Limit = 50) {
        $SQL = Gdn::sql();
        $this->_UserData = $SQL
            ->select('u.UserID, u.Name, u.Photo')
            ->select('c.DateInserted', 'max', 'DateLastActive')
            ->from('User u')
            ->join('Comment c', 'u.UserID = c.InsertUserID')
            ->where('c.DiscussionID', $DiscussionID)
            ->groupBy('u.UserID, u.Name, u.Photo')
            ->orderBy('c.DateInserted', 'desc')
            ->limit($Limit)
            ->get();
    }

    /**
     * Default render location.
     *
     * @return string
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     * Build HTML.
     *
     * @return string HTML.
     */
    public function toString() {
        if ($this->_UserData->numRows() == 0) {
            return '';
        }

        $String = '';
        ob_start();
        ?>
        <div class="Box BoxInThisDiscussion">
            <?php echo panelHeading(t('In this Discussion')); ?>
            <ul class="PanelInfo PanelInThisDiscussion">
                <?php foreach ($this->_UserData->Result() as $User) :
?>
                    <li>
                        <?php
                        echo anchor(
                            wrap(wrap(Gdn_Format::date($User->DateLastActive, 'html')), 'span', ['class' => 'Aside']).' '.
                            wrap(wrap(val('Name', $User), 'span', ['class' => 'Username']), 'span'),
                            userUrl($User)
                        )
                        ?>
                    </li>
                <?php
endforeach; ?>
            </ul>
        </div>
        <?php
        $String = ob_get_clean();
        return $String;
    }
}
