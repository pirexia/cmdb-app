<?php
// app/Services/EncryptionService.php

namespace App\Services;

use Exception;

/**
 * Servicio para cifrado y descifrado simétrico.
 * Utiliza OpenSSL y una clave de la configuración de la aplicación.
 */
class EncryptionService
{
    private string $key;
    private string $cipher;

    /**
     * Constructor.
     * @param array $config Configuración de la aplicación.
     * @throws Exception Si la clave de cifrado no está configurada.
     */
    public function __construct(array $config)
    {
        // Decodificar la clave de base64 a binario.
        $this->key = base64_decode($config['app']['encryption_key'] ?? '');
        $this->cipher = $config['app']['encryption_cipher'] ?? 'aes-256-gcm';

        if (empty($this->key)) {
            throw new Exception('La clave de cifrado no está configurada. Por favor, define APP_ENCRYPTION_KEY en tu fichero .env.');
        }

        if (!in_array($this->cipher, openssl_get_cipher_methods())) {
            throw new Exception("El algoritmo de cifrado '{$this->cipher}' no está soportado.");
        }
    }

    /**
     * Cifra un valor de texto plano.
     * @param string $value El texto a cifrar.
     * @return string El valor cifrado en base64.
     * @throws Exception Si el cifrado falla.
     */
    public function encrypt(string $value): string
    {
        $ivlen = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $tag = ''; // El tag se genera por referencia con GCM.

        $ciphertext = openssl_encrypt($value, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv, $tag);

        if ($ciphertext === false) {
            throw new Exception('El cifrado ha fallado.');
        }

        // Concatenamos IV, tag y texto cifrado, y lo codificamos en base64.
        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Descifra un valor cifrado.
     * @param string $encryptedValue El valor cifrado en base64.
     * @return string El texto plano original.
     * @throws Exception Si el descifrado falla.
     */
    public function decrypt(string $encryptedValue): string
    {
        $data = base64_decode($encryptedValue);
        $ivlen = openssl_cipher_iv_length($this->cipher);
        $taglen = 16; // GCM usa un tag de 16 bytes.

        $iv = substr($data, 0, $ivlen);
        $tag = substr($data, $ivlen, $taglen);
        $ciphertext = substr($data, $ivlen + $taglen);

        $decrypted = openssl_decrypt($ciphertext, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv, $tag);

        if ($decrypted === false) {
            throw new Exception('El descifrado ha fallado. La clave podría ser incorrecta o los datos están corruptos.');
        }

        return $decrypted;
    }
}