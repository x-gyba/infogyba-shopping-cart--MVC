<?php

namespace App\Core;

/**
 * Validações de dados de entrada.
 *
 * CORREÇÃO: antes só checávamos `strlen($cpf) === 11`, ou seja, qualquer
 * sequência de 11 dígitos passava (inclusive "00000000000"). Agora
 * validamos de fato os dígitos verificadores do CPF.
 */
class Validation
{
    /** Valida CPF (apenas dígitos, sem máscara) pelo algoritmo oficial de dígitos verificadores. */
    public static function cpfValido(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);

        if (strlen($cpf) !== 11) {
            return false;
        }

        // Rejeita sequências com todos os dígitos iguais (ex.: 111.111.111-11),
        // que passariam matematicamente no cálculo mas nunca são CPFs reais.
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($posicaoDigito = 9; $posicaoDigito <= 10; $posicaoDigito++) {
            $soma = 0;
            for ($i = 0; $i < $posicaoDigito; $i++) {
                $soma += (int) $cpf[$i] * (($posicaoDigito + 1) - $i);
            }
            $resto = ($soma * 10) % 11;
            $digitoEsperado = ($resto === 10) ? 0 : $resto;

            if ((int) $cpf[$posicaoDigito] !== $digitoEsperado) {
                return false;
            }
        }

        return true;
    }

    /** Validação simples de CEP brasileiro (8 dígitos). */
    public static function cepValido(string $cep): bool
    {
        return (bool) preg_match('/^\d{8}$/', preg_replace('/\D/', '', $cep));
    }
}
