<?php

namespace App\Http\Controllers;

use App\ApiToken;
use App\UrlHelper;
use App\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use JWTAuth;
use Illuminate\Support\Facades\Redis;
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

        if ($user = session()->get(User::USER_SUBJECT_SESSION_KEY)) {
            try {
                $token = $auth->fromSubject($user);
                $redirectUrl = $urlHelper->appendQueryParams($successUrl, [
                    'token' => $token,
                ]);
                return redirect($redirectUrl);
            } catch (JWTException $e) {
                // cannot refresh the token (it might have been already blacklisted), let user log in again
                session()->forget(User::USER_SUBJECT_SESSION_KEY);
            }
        }

        // TODO: get providers from container; display login page if multiple, autoredirect if single

        $redirectUrl = $urlHelper->appendQueryParams(route('auth.google'), [
            'successUrl' => $successUrl,
            'errorUrl' => $errorUrl,
        ]);
        return redirect($redirectUrl);
    }

    public function logout(\Tymon\JWTAuth\JWTAuth $auth)
    {
        $user = session()->remove(User::USER_SUBJECT_SESSION_KEY);
        if ($user) {
            $user->last_logout_at = Carbon::now();
            $user->save();
        }
        return redirect()->back();
    }

    public function logoutWeb()
    {
        Auth::logout();
        return redirect()->back();
    }

    public function refresh(\Tymon\JWTAuth\JWTAuth $auth, Request $request)
    {
        try {
            $refreshedToken = $auth->setRequest($request)->parseToken()->refresh();
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
        $auth = $auth->setRequest($request)->parseToken();
        Redis::hset(User::USER_LAST_LOGOUT_KEY, $auth->payload()->get('id'), time());

        try {
            $auth->invalidate();
        } catch (\Exception $e) {
            // no token found or token blacklisted, we're fine
        }

        return response()->json([
            'redirect' => route('auth.logout'),
        ], 200);
    }

    public function error(Request $request)
    {
        return 'error during login: ' . $request->get('error');
    }
}
