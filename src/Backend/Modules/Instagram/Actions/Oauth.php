<?php

namespace Backend\Modules\Instagram\Actions;

use Backend\Core\Engine\Base\Action as BackendBaseAction;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Modules\Instagram\Engine\Helper;
use Backend\Modules\Instagram\Engine\Model as BackendInstagramModel;

/**
 * This is the settings-action, it will display a form to set general instagram settings
 *
 * @author Jesse Dobbelaere <jesse@dobbelae.re>
 */
class Oauth extends BackendBaseAction
{
    /**
     * Execute the action
     */
    public function execute()
    {
        parent::execute();

        // Get the redirect code if there is one (if you are redirected here from the Instagram authentication)
        $oAuthCode = $this->getParameter('code', 'string', '');

        // Get settings
        $settingsService = $this->get('fork.settings');
        $client_id = $settingsService->get($this->URL->getModule(), 'client_id');
        $client_secret = $settingsService->get($this->URL->getModule(), 'client_secret');

        // If no settings configured, redirect
        if (empty($client_id) || empty($client_secret)) {
            $this->redirect(BackendModel::createURLForAction('Settings') . '&error=non-existing');
        }

        // First visit? (otherwise instagram would have added a parameter)
        if ($oAuthCode == '') {
            // Get Instagram api token
            $instagram_login_url = Helper::getLoginUrl($client_id, SITE_URL . BackendModel::createURLForAction('Oauth'));
            $this->redirect($instagram_login_url);
        }

        // oAuth code is set (meaning we got redirected from instagram)
        else {
            // Exchanging the instagram login code for an access token
            $authenticationData = Helper::getOAuthToken(
                $client_id,
                $client_secret,
                $oAuthCode,
                SITE_URL . BackendModel::createURLForAction('Oauth'),
                false
            );

            // Define variables
            $userId = $authenticationData->user->id;
            $userName = $authenticationData->user->username;
            $accessToken = $authenticationData->access_token;

            if (isset($accessToken)) {
                $instagramUser = array(
                    'user_id' => $userId,
                    'username' => $userName,
                    'locked' => 'Y',
                );

                $instagramUser['id'] = BackendInstagramModel::insert($instagramUser);

                // Save access_token to settings
                $this->get('fork.settings')->set(
                    $this->URL->getModule(),
                    'access_token',
                    $accessToken
                );

                // Save access_token to settings
                $this->get('fork.settings')->set(
                    $this->URL->getModule(),
                    'default_instagram_user_id',
                    $instagramUser['id']
                );

                // Trigger event
                BackendModel::triggerEvent($this->getModule(), 'after_oauth');

                // Successfully authenticated
                $this->redirect(BackendModel::createURLForAction('Settings') . '&report=authentication_success');
            } else {
                $this->redirect(BackendModel::createURLForAction('Settings') . '&error=authentication_failed');
            }
        }
    }
}
