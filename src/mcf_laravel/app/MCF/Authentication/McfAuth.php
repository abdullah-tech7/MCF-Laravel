<?php

declare (strict_types = 1);

namespace App\MCF\Authentication;

use App\MCF\Audit\McfAuthAudit;
use App\MCF\Authentication\Internal\Enum\VerificationRequirement;
use App\MCF\Authentication\Internal\HashService;
use App\MCF\Authentication\Internal\LoginThrottleService;
use App\MCF\Authentication\Internal\PasswordService;
use App\MCF\Authentication\Internal\UserService;
use App\MCF\Authentication\Session\ConcurrentSessionHandler;
use App\MCF\Authentication\Session\SessionSecurityHandler;
use App\MCF\Result\Authentication\AuthenticationResult;
use App\MCF\Result\Authentication\ChangePasswordResult;
use App\MCF\Result\Authentication\UpdateResult;
use App\MCF\Result\McfResult;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class McfAuth
{
    private function __construct()
    {
    }

    /*
     |--------------------------------------------------------------------------
     | Auth Wrapper
     |--------------------------------------------------------------------------
     */

    public static function check(): bool
    {
        return Auth::check();
    }

    public static function id(): mixed
    {
        return Auth::id();
    }

    public static function user(): ?Authenticatable
    {
        return Auth::user();
    }

    public static function login(
        Authenticatable $user,
        bool $remember = false,
    ): void {
        Auth::login(
            $user,
            $remember,
        );

        app(SessionSecurityHandler::class)->initialize();
        app(ConcurrentSessionHandler::class)->handle();
    }

    public static function logout(): void
    {
        McfAuthAudit::record(
            action: 'logout',
            description: 'The user logged out.',
        );

        Auth::logout();
    }

    /*
     |--------------------------------------------------------------------------
     | Public Authentication
     |--------------------------------------------------------------------------
     */

    public static function loginByUser(
        Authenticatable $user,
    ): McfResult {
        $identifier = LoginThrottleService::identifierFromUser(
            $user,
        );

        if (
            LoginThrottleService::isThrottled(
                $identifier,
            )
        ) {
            return new AuthenticationResult(
                AuthenticationResult::THROTTLED,
            );
        }

        if (! UserService::canAuthenticate($user)) {
            return new AuthenticationResult(
                AuthenticationResult::NOT_ALLOWED,
            );
        }

        $result = self::authenticate($user);

        if (
            $result->is(
                AuthenticationResult::SUCCESS,
            )
        ) {
            LoginThrottleService::clear(
                $identifier,
            );
        }

        return $result;
    }

    public static function loginByCredentials(
    array $credentials,
    bool $remember = false,
): McfResult {
    $identifier = LoginThrottleService::identifierFromCredentials(
        $credentials,
    );

    /*
     * Throttle check.
     */
    if (
        LoginThrottleService::isThrottled(
            $identifier,
        )
    ) {
        return new AuthenticationResult(
            AuthenticationResult::THROTTLED,
        );
    }

    /*
     * Find the user.
     *
     * UserService::findUser() includes soft-deleted users
     * so we can determine the account state.
     */
    $user = UserService::findUser(
        $credentials,
    );

    if ($user === null) {
        LoginThrottleService::hit(
            $identifier,
        );

        return new AuthenticationResult(
            AuthenticationResult::INVALID_CREDENTIALS,
        );
    }

    /*
     * Validate the password BEFORE exposing whether
     * the account is deleted.
     *
     * This prevents account-state disclosure through
     * the login endpoint.
     */
    $password = $credentials['password'] ?? null;

    if (
        ! is_string($password)
        || ! UserService::validatePassword(
            $user,
            $password,
        )
    ) {
        LoginThrottleService::hit(
            $identifier,
        );

        return new AuthenticationResult(
            AuthenticationResult::INVALID_CREDENTIALS,
        );
    }

    /*
     * The credentials are valid.
     *
     * A soft-deleted account must not continue through
     * the normal authentication flow.
     */
    if ($user->trashed()) {

        /*
         * Self-deleted account.
         */
        if ($user->deletion_type === 'self') {

            /*
             * Clear the throttle because the supplied
             * credentials were valid.
             */
            LoginThrottleService::clear(
                $identifier,
            );

            if (
                UserService::isSelfRestorationAvailable(
                    $user,
                )
            ) {
                return new AuthenticationResult(
                    AuthenticationResult::DELETED_BY_SELF_RESTORABLE,
                );
            }

            return new AuthenticationResult(
                AuthenticationResult::DELETED_BY_SELF_EXPIRED,
            );
        }

        /*
         * Actor-deleted account.
         *
         * Actor deletion is not a self-restoration
         * workflow.
         */
        LoginThrottleService::clear(
            $identifier,
        );

        return new AuthenticationResult(
            AuthenticationResult::DELETED_BY_ACTOR,
        );
    }

    /*
     * The account is not deleted.
     *
     * Continue with the normal authentication checks.
     */
    if (! UserService::canAuthenticate($user)) {
        return new AuthenticationResult(
            AuthenticationResult::NOT_ALLOWED,
        );
    }

    /*
     * Authenticate the user.
     */
    $result = self::authenticate(
        $user,
        $remember,
    );

    if (
        $result->is(
            AuthenticationResult::SUCCESS,
        )
    ) {
        LoginThrottleService::clear(
            $identifier,
        );
    }

    return $result;
}
    /*
     |--------------------------------------------------------------------------
     | Password
     |--------------------------------------------------------------------------
     */

    public static function hashPassword(
        string $password,
    ): string {
        return HashService::make(
            $password,
        );
    }

    public static function changePassword(
        string $currentPassword,
        string $newPassword,
    ): McfResult {
        $user = self::user();

        if ($user === null) {
            return new ChangePasswordResult(
                ChangePasswordResult::FAILED,
            );
        }

        if (
            ! UserService::validatePassword(
                $user,
                $currentPassword,
            )
        ) {
            return new ChangePasswordResult(
                ChangePasswordResult::INVALID_CURRENT_PASSWORD,
            );
        }

        if (
            UserService::validatePassword(
                $user,
                $newPassword,
            )
        ) {
            return new ChangePasswordResult(
                ChangePasswordResult::SAME_PASSWORD,
            );
        }

        try {
            if (
                ! PasswordService::updatePassword(
                    $user,
                    $newPassword,
                )
            ) {
                return new ChangePasswordResult(
                    ChangePasswordResult::FAILED,
                );
            }

            return new ChangePasswordResult(
                ChangePasswordResult::UPDATED,
            );
        } catch (\Throwable) {
            return new ChangePasswordResult(
                ChangePasswordResult::FAILED,
            );
        }
    }

    /*
     |--------------------------------------------------------------------------
     | Reset Password
     |--------------------------------------------------------------------------
     */

    public static function resetPassword(
        string $email,
        string $password,
    ): McfResult {
        $user = UserService::findUserByEmail(
            $email,
        );

        if ($user === null) {
            return new UpdateResult(
                UpdateResult::USER_NOT_FOUND,
            );
        }

        try {
            return DB::transaction(
                function () use (
                    $user,
                    $email,
                    $password,
                ): McfResult {
                    if (
                        ! UserService::updatePassword(
                            $user,
                            self::hashPassword($password),
                        )
                    ) {
                        throw new \RuntimeException(
                            'Failed to update password.',
                        );
                    }

                    if (
                        ! McfVerification::consumeForgotPasswordVerification(
                            $email,
                        )
                    ) {
                        throw new \RuntimeException(
                            'Failed to consume password reset verification.',
                        );
                    }

                    return new UpdateResult(
                        UpdateResult::UPDATED,
                    );
                },
            );
        } catch (\Throwable) {
            return new UpdateResult(
                UpdateResult::FAILED,
            );
        }
    }

    /*
     |--------------------------------------------------------------------------
     | Update new email verified
     |--------------------------------------------------------------------------
     */

    public static function updateNewEmailVerified(
        string $email,
    ): McfResult {
        $user = self::user();

        if ($user === null) {
            return new UpdateResult(
                UpdateResult::USER_NOT_FOUND,
            );
        }

        try {
            DB::transaction(
                function () use (
                    $user,
                    $email,
                ): void {
                    if (
                        ! UserService::updateEmail(
                            $user,
                            $email,
                        )
                    ) {
                        throw new \RuntimeException(
                            'Failed to update email.',
                        );
                    }

                    if (
                        ! UserService::markEmailAsVerified(
                            $user,
                        )
                    ) {
                        throw new \RuntimeException(
                            'Failed to mark email as verified.',
                        );
                    }
                },
            );

            return new UpdateResult(
                UpdateResult::UPDATED,
            );
        } catch (\Throwable) {
            return new UpdateResult(
                UpdateResult::FAILED,
            );
        }
    }

    /*
     |--------------------------------------------------------------------------
     | Update new phone verified
     |--------------------------------------------------------------------------
     */

    public static function updateNewPhoneVerified(
        string $phone,
    ): McfResult {
        $user = self::user();

        if ($user === null) {
            return new UpdateResult(
                UpdateResult::USER_NOT_FOUND,
            );
        }

        try {
            DB::transaction(
                function () use (
                    $user,
                    $phone,
                ): void {
                    if (
                        ! UserService::updatePhone(
                            $user,
                            $phone,
                        )
                    ) {
                        throw new \RuntimeException(
                            'Failed to update phone.',
                        );
                    }

                    if (
                        ! UserService::markPhoneAsVerified(
                            $user,
                        )
                    ) {
                        throw new \RuntimeException(
                            'Failed to mark phone as verified.',
                        );
                    }
                },
            );

            return new UpdateResult(
                UpdateResult::UPDATED,
            );
        } catch (\Throwable) {
            return new UpdateResult(
                UpdateResult::FAILED,
            );
        }
    }

    /**
     * Mark the user's email as verified and authenticate the user.
     */
    public static function loginByVerifiedUser(
        string $email,
    ): McfResult {
        $user = UserService::findUserByEmail(
            $email,
        );

        if ($user === null) {
            return new AuthenticationResult(
                AuthenticationResult::USER_NOT_FOUND,
            );
        }

        try {
            if (
                ! UserService::markEmailAsVerified(
                    $user,
                )
            ) {
                return new AuthenticationResult(
                    AuthenticationResult::FAILED,
                );
            }

            return self::loginByUser(
                $user,
            );
        } catch (\Throwable) {
            return new AuthenticationResult(
                AuthenticationResult::FAILED,
            );
        }
    }

    /*
     |--------------------------------------------------------------------------
     | Private Authentication Flow
     |--------------------------------------------------------------------------
     */

    private static function authenticate(
        Authenticatable $user,
        bool $remember = false,
    ): McfResult {
        if (! self::isVerificationSatisfied($user)) {
            return self::verificationRequiredResult();
        }

        try {
            self::login(
                $user,
                $remember,
            );
            $user->last_login_at = now();
            $user->save();

            McfAuthAudit::record(
                action: 'login',
                description: 'The user logged in.',
            );

            return new AuthenticationResult(
                AuthenticationResult::SUCCESS,
            );
        } catch (\Throwable) {
            McfAuthAudit::record(
                action: 'failed_login',
                description: 'The user failed to log in.',
            );

            return new AuthenticationResult(
                AuthenticationResult::FAILED,
            );
        }
    }

    private static function isVerificationSatisfied(
        Authenticatable $user,
    ): bool {
        return match (
            AuthenticationSettings::$verificationRequirement
        ) {
            VerificationRequirement::NONE  =>
            true,

            VerificationRequirement::EMAIL =>
            UserService::isEmailVerified(
                $user,
            ),

            VerificationRequirement::PHONE =>
            UserService::isPhoneVerified(
                $user,
            ),
        };
    }

    private static function verificationRequiredResult(): McfResult
    {
        return match (
            AuthenticationSettings::$verificationRequirement
        ) {
            VerificationRequirement::NONE  =>
            new AuthenticationResult(
                AuthenticationResult::SUCCESS,
            ),

            VerificationRequirement::EMAIL =>
            new AuthenticationResult(
                AuthenticationResult::NEED_EMAIL_VERIFICATION,
            ),

            VerificationRequirement::PHONE =>
            new AuthenticationResult(
                AuthenticationResult::NEED_PHONE_VERIFICATION,
            ),
        };
    }

    /**
     * Determine whether the user is the system administrator.
     */
    public static function isAdministrator(
        ?Authenticatable $user,
    ): bool {
        if ($user === null) {
            return false;
        }

        $administratorRole = UserSettings::$administratorRole;

        if ($administratorRole === null) {
            return false;
        }

        return UserSettings::resolveRole($user) === $administratorRole;
    }
}
