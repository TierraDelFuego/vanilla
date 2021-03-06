<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 2.0
 */


/**
 * Render a breadcrumb trail for the user based on the page they are on.
 *
 * @param array $Params
 * @param object $Smarty
 * @return string
 */
function smarty_function_breadcrumbs($Params, &$Smarty) {
    $Breadcrumbs = Gdn::controller()->data('Breadcrumbs');
    if (!is_array($Breadcrumbs)) {
        $Breadcrumbs = [];
    }

    $Options = arrayTranslate($Params, ['homeurl' => 'HomeUrl', 'hidelast' => 'HideLast']);
   
    return Gdn_Theme::breadcrumbs($Breadcrumbs, val('homelink', $Params, true), $Options);
}
