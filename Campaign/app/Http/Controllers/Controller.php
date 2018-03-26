<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    const FORM_ACTION_SAVE_CLOSE = 'save_close';
    const FORM_ACTION_SAVE = 'save';


    /**
     * Decide where to redirect based on form action.
     *
     * Array $formActionRoutes should contain at least route for FORM_ACTION_SAVE action. Format:
     *
     * [
     *    self::FORM_ACTION_SAVE_CLOSE => 'resource.index',
     *    self::FORM_ACTION_SAVE => 'resource.edit',
     * ]
     *
     * @param string $action
     * @param array $formActionRoutes
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    protected function getRouteBasedOnAction(string $action, array $formActionRoutes, $resource)
    {
        // FORM_ACTION_SAVE must be set, it's default action
        // if it's not, redirect back to previous view
        if (!isset($formActionRoutes[self::FORM_ACTION_SAVE])) {
            return redirect()->back();
        }

        // if action wasn't provided with actions routes, use default action (FORM_ACTION_SAVE)
        if (!isset($formActionRoutes[$action])) {
            $action = self::FORM_ACTION_SAVE;
        }

        return redirect()->route($formActionRoutes[$action], $resource);
    }
}
