<?php

namespace App\Http\Middleware;

use App\User;
use Closure;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\JWTAuth;

class VerifyUserToken
{
    protected $auth;

    protected $response;

    public function __construct(JWTAuth $auth, ResponseFactory $response)
    {
        $this->auth = $auth;
        $this->response = $response;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @internal param JWTAuth $auth
     * @internal param ResponseFactory $response
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$this->auth->parser()->setRequest($request)->hasToken()) {
            return $this->respond('token_not_provided', 'no JWT token was provided', 400);
        }

        try {
            $user = $this->auth->parseToken()->authenticate();
        } catch (TokenExpiredException $e) {
            return $this->respond('token_expired', 'provided token has already expired', 401);
        } catch (JWTException $e) {
            return $this->respond('token_invalid', 'provided token is invalid', 401);
        }

        if (!$user) {
            return $this->respond('user_not_found', 'user extracted from token was not found', 404);
        }

        return $next($request);
    }

    protected function respond($code, $detail, $status)
    {
        return $this->response->json([
            'code' => $code,
            'detail' => $detail,
            'redirect' => route('auth.login'),
        ], $status);
    }
}
