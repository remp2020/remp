<?php

namespace App\Http\Controllers\Auth;

use App\EmailWhitelist;
use App\Models\GoogleUser;
use App\Http\Controllers\Controller;
use App\RedirectUrlValidator;
use App\UrlHelper;
use App\Models\User;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\Response;
use Socialite;
use Session;
use Tymon\JWTAuth\JWT;

class GoogleController extends Controller
{
    const SUCCESS_URL_KEY = 'url.success';

    const ERROR_URL_KEY = 'url.error';

    const SUCCESS_URL_QUERY_PARAM = 'successUrl';

    const ERROR_URL_QUERY_PARAM = 'errorUrl';

    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @param Request $request
     * @return Response
     */
    public function redirect(Request $request, RedirectUrlValidator $redirectUrlValidator)
    {
        Session::put(self::SUCCESS_URL_KEY, $redirectUrlValidator->ensureAllowed($request->input(self::SUCCESS_URL_QUERY_PARAM), self::SUCCESS_URL_QUERY_PARAM));
        Session::put(self::ERROR_URL_KEY, $redirectUrlValidator->ensureAllowed($request->input(self::ERROR_URL_QUERY_PARAM), self::ERROR_URL_QUERY_PARAM));

        // not stateless - the OAuth "state" stored in the session is verified in callback() (login CSRF)
        /** @var GoogleProvider $driver */
        $driver = Socialite::driver(User::PROVIDER_GOOGLE);
        return $driver->redirect();
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @param JWT            $jwt
     * @param EmailWhitelist $whitelist
     * @param UrlHelper      $urlHelper
     *
     * @return Response
     */
    public function callback(JWT $jwt, EmailWhitelist $whitelist, UrlHelper $urlHelper, RedirectUrlValidator $redirectUrlValidator)
    {
        $backUrl = Session::pull(self::SUCCESS_URL_KEY);
        $errorUrl = Session::pull(self::ERROR_URL_KEY);
        if ($backUrl === null || $errorUrl === null) {
            // the session expired (or was lost) since redirect() - there's no URL to return to
            return redirect()->route('sso.error', [
                'error' => 'Login session expired, please try again.',
            ]);
        }

        // validated again when used, not only when stored (defense in depth)
        $backUrl = $redirectUrlValidator->ensureAllowed($backUrl, self::SUCCESS_URL_QUERY_PARAM);
        $errorUrl = $redirectUrlValidator->ensureAllowed($errorUrl, self::ERROR_URL_QUERY_PARAM);

        try {
            /** @var GoogleProvider $driver */
            $driver = Socialite::driver(User::PROVIDER_GOOGLE);
            /** @var \Laravel\Socialite\Two\User $factoryUser */
            $factoryUser = $driver->user();
        } catch (InvalidStateException|ClientException $e) {
            // OAuth state doesn't match the session (login CSRF, login restarted in another tab) or Google rejected
            // the authorization code (e.g. reloaded callback URL) - let the user start the login again
            Log::notice('Google SSO login failed', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return redirect($urlHelper->appendQueryParams($errorUrl, [
                'error' => 'Login to Google failed, please try again.',
            ]));
        }

        if (!$whitelist->validate($factoryUser->getEmail())) {
            $errorUrl = $urlHelper->appendQueryParams($errorUrl, [
                'error' => sprintf('email not whitelisted to log in: %s', $factoryUser->getEmail()),
            ]);
            return redirect($errorUrl);
        }

        /** @var User $user */
        $user = User::withTrashed()->firstOrCreate([
            'email' => $factoryUser->getEmail(),
        ], [
            'email' => $factoryUser->getEmail(),
            'name' => $factoryUser->getName(),
        ]);

        if ($user->trashed()) {
            // previously trashed user was allowed access again, just restore
            $user->restore();
        }

        /** @var GoogleUser $googleUser */
        $googleUser = $user->googleUser()->firstOrNew([
            'google_id' => $factoryUser->getId(),
            'nickname' => $factoryUser->getNickname(),
            'name' => $factoryUser->getName(),
        ]);
        $googleUser->nickname = $factoryUser->getNickname();
        $googleUser->name = $factoryUser->getName();
        $googleUser->save();

        $user->latestProvider = User::PROVIDER_GOOGLE;
        $token = $jwt->fromSubject($user);

        // new session ID for the logged in user (session fixation)
        session()->regenerate();
        session()->put(User::USER_SUBJECT_SESSION_KEY, $user);

        $redirectUrl = $urlHelper->appendQueryParams($backUrl, [
            'token' => $token,
        ]);
        return redirect($redirectUrl);
    }
}
