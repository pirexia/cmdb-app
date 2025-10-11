<?php

namespace App\Services;

use PragmaRX\Google2FA\Google2FA;
use App\Models\User;
use Exception;

class MfaService
{
    private Google2FA $google2fa;
    private User $userModel;
    private array $config;

    public function __construct(User $userModel, array $config)
    {
        $this->google2fa = new Google2FA();
        $this->userModel = $userModel;
        $this->config = $config;
    }

    /**
     * Genera un nuevo secreto MFA para un usuario.
     * @return string El secreto generado.
     */
    public function generateSecretKey(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Genera la URL de un código QR para configurar la app de autenticación.
     * @param string $username El nombre de usuario.
     * @param string $secret El secreto MFA.
     * @return string La URL de la imagen del código QR.
     */
    public function getQrCodeUrl(string $username, string $secret): string
    {
        $appName = $this->config['app']['name'] ?? 'CMDB App';
        $qrCodeUrl = $this->google2fa->getQRCodeUrl($appName, $username, $secret);
        return $qrCodeUrl;
    }

    /**
     * Verifica un código TOTP proporcionado por el usuario.
     * @param string $secret El secreto MFA del usuario.
     * @param string $code El código de 6 dígitos.
     * @return bool True si el código es válido, false en caso contrario.
     */
    public function verifyCode(string $secret, string $code): bool
    {
        // Limpiar el código de espacios o caracteres no numéricos
        $cleanedCode = preg_replace('/[^0-9]/', '', $code);
        if (strlen($cleanedCode) !== 6) {
            return false;
        }

        return $this->google2fa->verifyKey($secret, $cleanedCode);
    }

    /**
     * Habilita MFA para un usuario guardando el secreto y activando la bandera.
     * @param int $userId
     * @param string $secret
     * @return bool
     */
    public function enableMfaForUser(int $userId, string $secret): bool
    {
        $data = [
            'mfa_enabled' => true,
            'mfa_secret' => $secret // Idealmente, esto debería estar cifrado
        ];
        return $this->userModel->updateProfileData($userId, $data);
    }

    /**
     * Deshabilita MFA para un usuario.
     * @param int $userId
     * @return bool
     */
    public function disableMfaForUser(int $userId): bool
    {
        $data = [
            'mfa_enabled' => false,
            'mfa_secret' => null
        ];
        return $this->userModel->updateProfileData($userId, $data);
    }

    /**
     * Actualiza el secreto MFA de un usuario.
     * @param int $userId
     * @param string $secret
     * @return bool
     */
    public function updateUserMfaSecret(int $userId, string $secret): bool
    {
        return $this->userModel->updateProfileData($userId, ['mfa_secret' => $secret]);
    }
}