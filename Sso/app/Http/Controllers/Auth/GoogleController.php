<?php

namespace App\Http\Controllers\Auth;

use App\EmailWhitelist;
use App\GoogleUser;
use App\Http\Controllers\Controller;
use App\UrlHelper;
use App\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use JWTAuth;
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
    public function redirect(Request $request)
    {
        Session::put(self::SUCCESS_URL_KEY, $request->input(self::SUCCESS_URL_QUERY_PARAM, '/'));
        Session::put(self::ERROR_URL_KEY, $request->input(self::ERROR_URL_QUERY_PARAM, '/'));

        return Socialite::driver(User::PROVIDER_GOOGLE)->stateless()->redirect();
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
    public function callback(JWT $jwt, EmailWhitelist $whitelist, UrlHelper $urlHelper)
    {
        $backUrl = Session::get(self::SUCCESS_URL_KEY);
        Session::forget(self::SUCCESS_URL_KEY);
        $errorUrl = Session::get(self::ERROR_URL_KEY);
        Session::forget(self::ERROR_URL_KEY);

        /** @var \Laravel\Socialite\Two\User $factoryUser */
        $factoryUser = Socialite::driver(User::PROVIDER_GOOGLE)->stateless()->user();
        if (!$whitelist->validate($factoryUser->getEmail())) {
            $errorUrl = $urlHelper->appendQueryParams($errorUrl, [
                'error' => sprintf('email not whitelisted to log in: %s', $factoryUser->getEmail()),
            ]);
            return redirect($errorUrl);
        }

        /** @var User $user */
        $user = User::firstOrCreate([
            'email' => $factoryUser->getEmail(),
        ], [
            'email' => $factoryUser->getEmail(),
            'name' => $factoryUser->getName(),
        ]);

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

        session()->put(User::USER_SUBJECT_SESSION_KEY, $user);

        $redirectUrl = $urlHelper->appendQueryParams($backUrl, [
            'token' => $token,
        ]);
        return redirect($redirectUrl);
    }
}
