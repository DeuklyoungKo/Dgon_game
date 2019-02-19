<?php

namespace App\Controller;

use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Facebook\Facebook;



class FacebookForJavascriptController extends AbstractController
{
    /**
     * @Route("/facebook/javascript", name="facebook_javascript")
     */
    public function javascript()
    {
        return $this->render('facebook_for_javascript/index.html.twig', [
            'controller_name' => 'FacebookForJavascriptController',
        ]);
    }


    /**
     * @Route("/facebook/javascript/redirectPage", name="facebook_javascript_redirectPage")
     */
    public function redirectPage()
    {
        return $this->render('facebook_for_javascript/index.html.twig', [
            'controller_name' => 'FacebookForJavascriptController',
        ]);
    }

    /**
     * @Route("/facebook/sdk", name="facebook_php_sdk")
     */
    public function facebookPhpSdk(Session $session)
    {

        // https://github.com/facebook/php-graph-sdk/issues/473
        if(!session_id()) {
            session_start();
        }


        $fb = new Facebook([
            'app_id' => $_ENV['OAUTH_FACEBOOK_ID'], // Replace {app-id} with your app id
            'app_secret' => $_ENV['OAUTH_FACEBOOK_SECRET'],
            'default_graph_version' => 'v3.2',
//            'persistent_data_handler' => new \App\Libraries\Facebook\FacebookPersistentDataHandler($session)
        ]);

        $helper = $fb->getRedirectLoginHelper();

        $permissions = ['email']; // Optional permissions
        $loginUrl = $helper->getLoginUrl('https://game.dgon.eu//facebook/sdk/redirect', $permissions);
//        die($_SESSION['FBRLH_' . 'state']);


//        dd($loginUrl);

        return new Response('<a href="' . htmlspecialchars($loginUrl) . '">Log in with Facebook!</a>');

    }


    /**
     * @Route("/facebook/sdk/redirect", name="facebook_php_sdk_redirect")
     */
    public function facebookPhpSdkRedirect(Request $request)
    {

        // https://github.com/facebook/php-graph-sdk/issues/473
        if(!session_id()) {
            session_start();
        }

        dump($_SESSION['FBRLH_' . 'state']);

        $fb = new Facebook([
            'app_id' => $_ENV['OAUTH_FACEBOOK_ID'], // Replace {app-id} with your app id
            'app_secret' => $_ENV['OAUTH_FACEBOOK_SECRET'],
            'default_graph_version' => 'v3.2',
        ]);

        $helper = $fb->getRedirectLoginHelper();

        try {
            $accessToken = $helper->getAccessToken();
        } catch(FacebookResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        if (! isset($accessToken)) {
            if ($helper->getError()) {
                header('HTTP/1.0 401 Unauthorized');
                echo "Error: " . $helper->getError() . "\n";
                echo "Error Code: " . $helper->getErrorCode() . "\n";
                echo "Error Reason: " . $helper->getErrorReason() . "\n";
                echo "Error Description: " . $helper->getErrorDescription() . "\n";
            } else {
                header('HTTP/1.0 400 Bad Request');
                echo 'Bad request';
            }
            exit;
        }

// Logged in
        echo '<h3>Access Token</h3>';
        var_dump($accessToken->getValue());

// The OAuth 2.0 client handler helps us manage access tokens
        $oAuth2Client = $fb->getOAuth2Client();

// Get the access token metadata from /debug_token
        $tokenMetadata = $oAuth2Client->debugToken($accessToken);
        echo '<h3>Metadata</h3>';
        var_dump($tokenMetadata);

// Validation (these will throw FacebookSDKException's when they fail)
        $tokenMetadata->validateAppId($_ENV['OAUTH_FACEBOOK_ID']); // Replace {app-id} with your app id
// If you know the user ID this access token belongs to, you can validate it here
//$tokenMetadata->validateUserId('123');
        $tokenMetadata->validateExpiration();

        if (! $accessToken->isLongLived()) {
            // Exchanges a short-lived access token for a long-lived one
            try {
                $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
            } catch (FacebookSDKException $e) {
                echo "<p>Error getting long-lived access token: " . $e->getMessage() . "</p>\n\n";
                exit;
            }

            echo '<h3>Long-lived</h3>';
            var_dump($accessToken->getValue());
        }

        $_SESSION['fb_access_token'] = (string) $accessToken;

//Batch Request Example
        try {

            $fields='id,name,picture,email,location';

            echo '<br><br>fileds ='.$fields.'<br>';

            // Returns a `FacebookFacebookResponse` object
            $response = $fb->get(
                '/me?fields='.$fields,
                $accessToken
            );
        } catch(FacebookExceptionsFacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(FacebookExceptionsFacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }
        $graphNode = $response->getGraphNode();

        /**
         * Generate some requests and then send them in a batch request.
         */

        echo '<h3>requestUserName</h3>';
        var_dump($graphNode);

        $picture = $graphNode->getField('picture');
        $picturneHtml = '<br><img src="'.$picture['url'].'"/>';


// to get info again
        $permissions = ['email']; // Optional permissions
        $loginUrl = $helper->getLoginUrl('https://game.dgon.eu/facebook/sdk/redirect', $permissions);

// to move after logou
        $next = 'https://game.dgon.eu/facebook/sdk';
        $logoutUrl = $helper->getLogoutUrl($accessToken, $next);

        $html = '<hr><a href="' . htmlspecialchars($loginUrl) . '">Log in with Facebook!</a>';
        $html .= '<br><a href="' . htmlspecialchars($logoutUrl) . '">Log out with Facebook!</a>';
        $html .= $picturneHtml;

        return new Response($html);

    }


}
