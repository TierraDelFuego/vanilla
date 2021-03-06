<?php
/**
 * Configuration module.
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.2
 */

/**
 * This class gives a simple way to load/save configuration settings.
 *
 * To use this module you must:
 *  1. Call Schema() to set the config fields you are using.
 *  2. Call Initialize() within the controller to load/save the data.
 *  3. Do one of the following:
 *   a) Call the controller's Render() method and call Render() somewhere inside of the view.
 *   b) Call this object's RenderAll() method within the view if you don't want to customize the view any further.
 */
class ConfigurationModule extends Gdn_Module {

    /** @var bool Whether or not the view is rendering the entire page. */
    public $RenderAll = false;

    /** @var array A definition of the data that this will manage. */
    protected $_Schema;

    /** @var ConfigurationModule */
    public $ConfigurationModule = null;

    /**
     *
     *
     * @param Gdn_Controller $Controller The controller using this model.
     */
    public function __construct($Sender = null) {
        parent::__construct($Sender);

        if (property_exists($Sender, 'Form')) {
            $this->Form($Sender->Form);
        }

        $this->ConfigurationModule = $this;
    }

    /**
     *
     *
     * @return Gdn_Controller
     */
    public function controller() {
        return $this->_Sender;
    }

    /**
     *
     *
     * @param Gdn_Form $NewValue
     * @return Gdn_Form
     */
    public function form($NewValue = null) {
        static $Form = null;

        if ($NewValue !== null) {
            $Form = $NewValue;
        } elseif ($Form === null)
            $Form = new Gdn_Form('', 'bootstrap');

        return $Form;
    }

    /**
     *
     *
     * @return bool
     */
    public function hasFiles() {
        static $HasFiles = null;

        if ($HasFiles === null) {
            $HasFiles = false;
            foreach ($this->schema() as $K => $Row) {
                if (strtolower(val('Control', $Row)) == 'imageupload') {
                    $HasFiles = true;
                    break;
                }
            }
        }
        return $HasFiles;
    }

    /**
     *
     *
     * @param null $Schema
     * @throws Exception
     */
    public function initialize($Schema = null) {
        if ($Schema !== null) {
            $this->schema($Schema);
        }

        /** @var Gdn_Form $Form */
        $Form = $this->form();

        if ($Form->authenticatedPostBack()) {
            // Grab the data from the form.
            $Data = [];
            $Post = $Form->formValues();

            foreach ($this->_Schema as $Row) {
                $Name = $Row['Name'];
                $Config = $Row['Config'];

                // For API calls make this a sparse save.
                if ($this->controller()->deliveryType() === DELIVERY_TYPE_DATA && !array_key_exists($Name, $Post)) {
                    continue;
                }

                if (strtolower(val('Control', $Row)) == 'imageupload') {
                    $options = arrayTranslate($Row, ['Prefix', 'Size']);
                    if (val('OutputType', $Row, false)) {
                        $options['OutputType'] = val('OutputType', $Row);
                    }
                    if (val('Crop', $Row, false)) {
                        $options['Crop'] = val('Crop', $Row);
                    }

                    // Old image to clean!
                    $options['CurrentImage'] = c($Name, false);

                    // Save the new image and clean up the old one.
                    $Form->saveImage($Name, $options);
                }

                $Value = $Form->getFormValue($Name);

                // Trim all incoming values by default.
                if (val('Trim', $Row, true)) {
                    $Value = trim($Value);
                }

                if ($Value == val('Default', $Value, '')) {
                    $Value = '';
                }

                $Data[$Config] = $Value;
                $this->controller()->setData($Name, $Value);
            }

            // Halt the save if we've had errors assigned.
            if ($Form->errorCount() == 0) {
                // Save it to the config.
                saveToConfig($Data, ['RemoveEmpty' => true]);
                $this->_Sender->informMessage(t('Saved'));
            }
        } else {
            // Load the form data from the config.
            $Data = [];
            foreach ($this->_Schema as $Row) {
                $Data[$Row['Name']] = c($Row['Config'], val('Default', $Row, ''));
            }
            $Form->setData($Data);
            $this->controller()->Data = array_merge($this->controller()->Data, $Data);
        }
    }

    /**
     *
     *
     * @param $SchemaRow
     * @return bool|mixed|string
     */
    public function labelCode($SchemaRow) {
        if (isset($SchemaRow['LabelCode'])) {
            return $SchemaRow['LabelCode'];
        }

        if (strpos($SchemaRow['Name'], '.') !== false) {
            $LabelCode = trim(strrchr($SchemaRow['Name'], '.'), '.');
        } else {
            $LabelCode = $SchemaRow['Name'];
        }

        // Split camel case labels into seperate words.
        $LabelCode = preg_replace('`(?<![A-Z0-9])([A-Z0-9])`', ' $1', $LabelCode);
        $LabelCode = preg_replace('`([A-Z0-9])(?=[a-z])`', ' $1', $LabelCode);
        $LabelCode = trim($LabelCode);

        $LabelCode = StringEndsWith($LabelCode, " ID", true, true);

        return $LabelCode;
    }

    /**
     *
     *
     * @throws Exception
     */
    public function renderAll() {
        $this->RenderAll = true;
        $Controller = $this->Controller();
        $Controller->ConfigurationModule = $this;

        $Controller->render($this->fetchViewLocation());
        $this->RenderAll = false;
    }

    /**
     * Set the data definition to load/save from the config.
     *
     * @param array $Def A list of fields from the config that this form will use.
     */
    public function schema($Def = null) {
        if ($Def !== null) {
            $Schema = [];

            foreach ($Def as $Key => $Value) {
                $Row = ['Name' => '', 'Type' => 'string', 'Control' => 'TextBox', 'Options' => []];

                if (is_numeric($Key)) {
                    $Row['Name'] = $Value;
                } elseif (is_string($Value)) {
                    $Row['Name'] = $Key;
                    $Row['Type'] = $Value;
                } elseif (is_array($Value)) {
                    $Row['Name'] = $Key;
                    $Row = array_merge($Row, $Value);
                } else {
                    $Row['Name'] = $Key;
                }
                touchValue('Config', $Row, $Row['Name']);
                $Schema[] = $Row;
            }
            $this->_Schema = $Schema;
        }
        return $this->_Schema;
    }
}
