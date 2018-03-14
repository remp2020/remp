<?php

namespace App\Http\Controllers;

use App\ApiToken;
use App\UrlHelper;
use App\User;
use Illuminate\Http\Request;
use JWTAuth;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AuthController extends Controller
{
    public function login(Request $request, UrlHelper $urlHelper, \Tymon\JWTAuth\JWTAuth $auth)
    {
        $successUrl = $request->input('successUrl');
        if (!$successUrl) {
            throw new BadRequestHttpException('missing successUrl query param');
        }
        $errorUrl = $request->input('errorUrl');
        if (!$errorUrl) {
            throw new BadRequestHttpException('missing errorUrl query param');
        }

        if ($token = session()->get(User::USER_TOKEN_SESSION_KEY)) {
            try {
                $refreshedToken = $auth->setToken($token)->refresh();
                $redirectUrl = $urlHelper->appendQueryParams($successUrl, [
                    'token' => $refreshedToken,
                ]);
                return redirect($redirectUrl);
            } catch (JWTException $e) {
                // cannot refresh the token (it might have been already blacklisted), let user log in again
            }
        }

        // TODO: get providers from container; display login page if multiple, autoredirect if single

        $redirectUrl = $urlHelper->appendQueryParams(route('auth.google'), [
            'successUrl' => $successUrl,
            'errorUrl' => $errorUrl,
        ]);
        return redirect($redirectUrl);
    }

    public function refresh(\Tymon\JWTAuth\JWTAuth $auth, Request $request)
    {
        try {
            $refreshedToken = $auth->setRequest($request)->parseToken()->refresh();
            session()->put(User::USER_TOKEN_SESSION_KEY, $refreshedToken);
            return response()->json([
                'token' => $refreshedToken,
            ]);
        } catch (TokenExpiredException $e) {
            return response()->json([
                'code' => 'token_expired',
                'detail' => 'token is expired: refresh timeout hit',
                'redirect' => route('auth.login'),
            ])->setStatusCode(401);
        } catch (JWTException $e) {
            return response()->json([
                'code' => 'token_invalid',
                'detail' => 'provided token is invalid',
                'redirect' => route('auth.login'),
            ])->setStatusCode(400);
        }
    }

    public function introspect()
    {
        $payload = JWTAuth::getPayload();

        return response()->json([
            'id' => $payload['id'],
            'name' => $payload->get('name'),
            'email' => $payload->get('email'),
            'scopes' => $payload->get('scopes'),
        ]);
    }

    public function apiToken(Request $request)
    {
        $bearerToken = $request->bearerToken();
        $token = ApiToken::whereToken($bearerToken)->first();
        if (!$token) {
            return response()->json(null, 404);
        }
        return response()->json(null, 200);
    }

    public function invalidate(\Tymon\JWTAuth\JWTAuth $auth, Request $request)
    {
        try {
            $auth->setRequest($request)->parseToken()->invalidate()->refresh();
        } catch (\Exception $e) {
            // no token found or token blacklisted, we're fine
        }
        session()->remove(User::USER_TOKEN_SESSION_KEY);
        return response()->json(null, 200);
    }
}
