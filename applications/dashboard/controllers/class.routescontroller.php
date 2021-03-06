<?php
/**
 * Controlling default routes in Garden's MVC dispatcher system.
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /routes endpoint.
 */
class RoutesController extends DashboardController {

    /** @var array Models to automatically instantiate. */
    public $Uses = ['Form'];

    /**
     * Set menu path. Automatically run on every use.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize() {
        parent::initialize();
        Gdn_Theme::section('Dashboard');
        if ($this->Menu) {
            $this->Menu->highlightRoute('/dashboard/settings');
        }
    }

    /**
     * Create a route.
     *
     * @since 2.0.0
     * @access public
     */
    public function add() {
        $this->permission('Garden.Settings.Manage');
        // Use the edit form with no roleid specified.
        $this->View = 'Edit';
        $this->edit();
    }

    /**
     * Edit a route.
     *
     * @since 2.0.0
     * @access public
     * @param string $RouteIndex Name of route.
     */
    public function edit($RouteIndex = false) {
        $this->permission('Garden.Settings.Manage');
        $this->setHighlightRoute('dashboard/routes');
        $this->Route = Gdn::router()->getRoute($RouteIndex);

        $Validation = new Gdn_Validation();
        $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
        $ConfigurationModel->setField(['Route', 'Target', 'Type']);

        // Set the model on the form.
        $this->Form->setModel($ConfigurationModel);

        // If seeing the form for the first time...
        if (!$this->Form->authenticatedPostBack()) {
            // Apply the route info to the form.
            if ($this->Route !== false) {
                $this->Form->setData([
                    'Route' => $this->Route['Route'],
                    'Target' => $this->Route['Destination'],
                    'Type' => $this->Route['Type']
                ]);
            }
        } else {
            // Define some validation rules for the fields being saved
            $ConfigurationModel->Validation->applyRule('Route', 'Required');
            $ConfigurationModel->Validation->applyRule('Target', 'Required');
            $ConfigurationModel->Validation->applyRule('Type', 'Required');

            // Validate & Save
            $FormPostValues = $this->Form->formValues();

            // Dunno.
            if ($this->Route['Reserved']) {
                $FormPostValues['Route'] = $this->Route['Route'];
            }

            if ($ConfigurationModel->validate($FormPostValues)) {
                $NewRouteName = val('Route', $FormPostValues);

                if ($this->Route !== false && $NewRouteName != $this->Route['Route']) {
                    Gdn::router()->deleteRoute($this->Route['Route']);
                }

                Gdn::router()->setRoute(
                    $NewRouteName,
                    val('Target', $FormPostValues),
                    val('Type', $FormPostValues)
                );

                $this->informMessage(t("The route was saved successfully."));
                $this->setRedirectTo('dashboard/routes');
            } else {
                $this->Form->setValidationResults($ConfigurationModel->validationResults());
            }
        }

        $this->render();
    }

    /**
     * Remove a route.
     *
     * @since 2.0.0
     * @access public
     * @param mixed $RouteIndex Name of route.
     * @param string $TransientKey Security token.
     */
    public function delete($RouteIndex = false, $TransientKey = false) {
        $this->permission('Garden.Settings.Manage');
        $this->deliveryType(DELIVERY_TYPE_BOOL);
        $Session = Gdn::session();

        // If seeing the form for the first time...
        if ($TransientKey !== false && $Session->validateTransientKey($TransientKey)) {
            Gdn::router()->deleteRoute($RouteIndex);
        }

        if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            redirectTo('dashboard/routes');
        }

        $this->render();
    }

    /**
     * Show list of current routes.
     *
     * @since 2.0.0
     * @access public
     */
    public function index() {
        $this->permission('Garden.Settings.Manage');
        $this->setHighlightRoute('dashboard/routes');
        $this->title(t('Routes'));

        $this->MyRoutes = Gdn::router()->Routes;
        $this->render();
    }
}
