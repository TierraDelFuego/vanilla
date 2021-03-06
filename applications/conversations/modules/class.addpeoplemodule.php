<?php
/**
 * Add People module.
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Conversations
 * @since 2.0
 */

/**
 * Renders a form that allows people to be added to conversations.
 */
class AddPeopleModule extends Gdn_Module {

    /** @var array */
    public $Conversation;

    /** @var Gdn_Form */
    public $Form;

    /** @var bool Whether user is allowed to use this form. */
    public $AddUserAllowed = true;

    /**
     *
     * @param Gdn_Controller $Sender
     * @throws Exception
     */
    public function __construct($Sender = null) {
        if (property_exists($Sender, 'Conversation')) {
            $this->Conversation = $Sender->Conversation;
        }

        // Allowed to use this module?
        $this->AddUserAllowed = $Sender->ConversationModel->addUserAllowed($this->Conversation->ConversationID);

        $this->Form = Gdn::factory('Form', 'AddPeople');
        // If the form was posted back, check for people to add to the conversation
        if ($this->Form->authenticatedPostBack()) {
            // Defer exceptions until they try to use the form so we don't fill our logs
            if (!$this->AddUserAllowed || !checkPermission('Conversations.Conversations.Add')) {
                throw permissionException();
            }

            $NewRecipientUserIDs = [];
            $NewRecipients = explode(',', $this->Form->getFormValue('AddPeople', ''));
            $UserModel = Gdn::factory("UserModel");
            foreach ($NewRecipients as $Name) {
                if (trim($Name) != '') {
                    $User = $UserModel->getByUsername(trim($Name));
                    if (is_object($User)) {
                        $NewRecipientUserIDs[] = $User->UserID;
                    }
                }
            }

            if ($Sender->ConversationModel->addUserToConversation($this->Conversation->ConversationID, $NewRecipientUserIDs)) {
                $Sender->informMessage(t('Your changes were saved.'));
            } else {
                $maxRecipients = ConversationModel::getMaxRecipients();
                $Sender->informMessage(sprintf(
                    plural(
                        $maxRecipients,
                        "You are limited to %s recipient.",
                        "You are limited to %s recipients."
                    ),
                    $maxRecipients
                ));
            }
            $Sender->setRedirectTo('/messages/'.$this->Conversation->ConversationID, false);
        }
        $this->_ApplicationFolder = $Sender->Application;
        $this->_ThemeFolder = $Sender->Theme;
    }

    /**
     *
     *
     * @return string
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     * Render the module.
     *
     * @return string Rendered HTML.
     */
    public function toString() {
        // Simplify our permission logic
        $ConversationExists = (is_object($this->Conversation) && $this->Conversation->ConversationID > 0);
        $CanAddUsers = ($this->AddUserAllowed && checkPermission('Conversations.Conversations.Add'));

        if ($ConversationExists && $CanAddUsers) {
            return parent::toString();
        }

        return '';
    }
}
