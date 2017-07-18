<?php

namespace App\Http\Controllers\Auth;

use App\EmailWhitelist;
use App\GoogleUser;
use App\Http\Controllers\Controller;
use App\UrlHelper;
use App\User;
use Illuminate\Http\Request;
use League\Uri\Components\Query;
use League\Uri\Schemes\Http;
use Symfony\Component\HttpFoundation\Response;
use JWTAuth;
use Socialite;
use Session;

class GoogleController extends Controller
{
    const PROVIDER = 'google';

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
        Session::put(self::SUCCESS_URL_KEY, $request->input(self::SUCCESS_URL_QUERY_PARAM, route('dashboard')));
        Session::put(self::ERROR_URL_KEY, $request->input(self::ERROR_URL_QUERY_PARAM, route('dashboard')));

        return Socialite::driver(self::PROVIDER)->stateless()->redirect();
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @param \Request $request
     * @param EmailWhitelist $whitelist
     * @param UrlHelper $urlHelper
     * @return Response
     */
    public function callback(\Request $request, EmailWhitelist $whitelist, UrlHelper $urlHelper)
    {
        $backUrl = Session::get(self::SUCCESS_URL_KEY);
        Session::forget(self::SUCCESS_URL_KEY);
        $errorUrl = Session::get(self::ERROR_URL_KEY);
        Session::forget(self::ERROR_URL_KEY);

        /** @var \Laravel\Socialite\Two\User $factoryUser */
        $factoryUser = Socialite::driver(self::PROVIDER)->stateless()->user();
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

        $token = JWTAuth::fromUser($user, [
            'provider' => self::PROVIDER,
            'id' => $user->id,
            'name' => $factoryUser->getName(),
            'email' => $factoryUser->getEmail(),
            'scopes' => [],
        ]);

        $redirectUrl = $urlHelper->appendQueryParams($backUrl, [
            'token' => $token,
        ]);
        return redirect($redirectUrl);
    }

}
