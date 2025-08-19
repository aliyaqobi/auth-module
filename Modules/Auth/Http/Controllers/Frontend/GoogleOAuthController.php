<?php

namespace Modules\Auth\Http\Controllers\Frontend;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Http\Requests\Frontend\GoogleCallbackRequest;
use Modules\Auth\Service\AuthService;
use Modules\Auth\Service\GoogleOAuthService;
use Modules\Auth\Transformers\Frontend\UserResource;

class GoogleOAuthController extends ApiController
{
    public function __construct(
        private GoogleOAuthService $googleOAuthService,
        private AuthService $authService
    ) {}

    /**
     * Get Google OAuth redirect URL
     */
    public function redirect(Request $request): JsonResponse
    {
        try {
            $redirectUrl = $this->googleOAuthService->getRedirectUrl();

            Log::info('Google OAuth redirect URL generated', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return $this->success([
                'redirect_url' => $redirectUrl
            ], 'Redirect URL generated successfully.');

        } catch (Exception $e) {
            Log::error('Google OAuth redirect error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return $this->error('Unable to generate redirect URL', [], 500);
        }
    }

    /**
     * Handle Google OAuth callback (for browser redirect)
     */
    public function callback(Request $request)
    {
        try {
            // Check for authorization errors
            if ($request->has('error')) {
                $error = $request->get('error');
                Log::warning('Google OAuth authorization error', [
                    'error' => $error,
                    'ip' => $request->ip()
                ]);

                return redirect($this->getFrontendUrl('/auth/login?error=' . urlencode($error)));
            }

            // Check for authorization code
            if (!$request->has('code')) {
                Log::warning('Google OAuth callback missing authorization code', [
                    'ip' => $request->ip()
                ]);

                return redirect($this->getFrontendUrl('/auth/login?error=missing_code'));
            }

            // Process OAuth callback
            $result = $this->processOAuthCallback($request);

            // Build success redirect URL
            $frontendUrl = $this->getFrontendUrl(
                '/auth/callback?token=' . urlencode($result['token']) . '&success=1'
            );

            return redirect($frontendUrl);

        } catch (Exception $e) {
            Log::error('Google OAuth callback error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return redirect($this->getFrontendUrl('/auth/login?error=auth_failed'));
        }
    }

    /**
     * Handle Google OAuth callback (API endpoint)
     */
    public function handleCallback(GoogleCallbackRequest $request): JsonResponse
    {
        try {
            $result = $this->processOAuthCallback($request);

            return $this->success([
                'user' => new UserResource($result['user']),
                'access_token' => $result['token'],
                'token_type' => 'Bearer',
            ], __('Welcome to :app_name', ['app_name' => config('app.name')]));

        } catch (Exception $e) {
            Log::error('Google OAuth API callback error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return $this->error('Authentication failed. Please try again.', [], 400);
        }
    }

    /**
     * Unlink Google account
     */
    public function unlink(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->google_id) {
                return $this->error('No Google account linked to this user.', [], 400);
            }

            $success = $this->googleOAuthService->revokeAccess($user);

            if ($success) {
                Log::info('Google account unlinked successfully', [
                    'user_id' => $user->id
                ]);

                return $this->success(null, 'Google account unlinked successfully.');
            }

            return $this->error('Failed to unlink Google account.', [], 400);

        } catch (Exception $e) {
            Log::error('Google OAuth unlink error', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return $this->error('Failed to unlink Google account.', [], 500);
        }
    }

    /**
     * Process OAuth callback (shared logic)
     */
    private function processOAuthCallback(Request $request): array
    {
        $result = $this->googleOAuthService->handleCallback($request->get('code'));

        // Create access token
        $token = $this->authService->createAccessToken(
            $result['user'],
            $request->get('device_name', 'google-oauth')
        );

        return [
            'user' => $result['user'],
            'token' => $token
        ];
    }

    /**
     * Get frontend URL
     */
    private function getFrontendUrl(string $path): string
    {
        $baseUrl = env('FRONTEND_APP_URL', 'http://localhost:3000');
        return rtrim($baseUrl, '/') . $path;
    }
}
