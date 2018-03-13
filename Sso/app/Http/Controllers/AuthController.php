<?php

namespace App\Http\Controllers;

use App\ApiToken;
use App\UrlHelper;
use Illuminate\Http\Request;
use JWTAuth;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AuthController extends Controller
{
    public function index()
    {
        return redirect(route('api-tokens.index'));
    }

    public function login(Request $request, UrlHelper $urlHelper)
    {
        $successUrl = $request->input('successUrl');
        if (!$successUrl) {
            throw new BadRequestHttpException('missing successUrl query param');
        }
        $errorUrl = $request->input('errorUrl');
        if (!$errorUrl) {
            throw new BadRequestHttpException('missing errorUrl query param');
        }

        // TODO: get providers from container; display login page if multiple, autoredirect if single

        $redirectUrl = $urlHelper->appendQueryParams(route('auth.google'), [
            'successUrl' => $successUrl,
            'errorUrl' => $errorUrl,
        ]);
        return redirect($redirectUrl);
    }

    public function refresh(\Tymon\JWTAuth\JWTAuth $auth, \Illuminate\Http\Request $request)
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

    public function apiToken(\Illuminate\Http\Request $request)
    {
        $bearerToken = $request->bearerToken();
        $token = ApiToken::whereToken($bearerToken)->first();
        if (!$token) {
            return response()->json(null, 404);
        }
        return response()->json(null, 200);
    }

    public function error(Request $request)
    {
        return 'error during login: ' . $request->get('error');
    }
}
