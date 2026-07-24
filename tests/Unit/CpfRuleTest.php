<?php

namespace Tests\Unit;

use App\Rules\Cpf;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CpfRuleTest extends TestCase
{
    public function test_it_accepts_a_valid_cpf(): void
    {
        $validator = Validator::make(
            ['cpf' => '52998224725'],
            ['cpf' => [new Cpf]],
        );

        $this->assertTrue($validator->passes());
    }

    public function test_it_rejects_invalid_and_repeated_cpfs(): void
    {
        foreach (['52998224724', '11111111111'] as $cpf) {
            $validator = Validator::make(
                ['cpf' => $cpf],
                ['cpf' => [new Cpf]],
            );

            $this->assertTrue($validator->fails());
        }
    }
}
