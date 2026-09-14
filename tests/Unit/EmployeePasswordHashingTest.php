<?php

namespace Tests\Unit;

use App\Models\Employee;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeePasswordHashingTest extends TestCase
{
    public function test_password_assignments_are_bcrypt_hashed(): void
    {
        $employee = new Employee;
        $employee->password = 'A-secure-password';

        $this->assertNotSame('A-secure-password', $employee->password);
        $this->assertSame('bcrypt', password_get_info($employee->password)['algoName']);
        $this->assertTrue(Hash::check('A-secure-password', $employee->password));
    }

    public function test_existing_bcrypt_passwords_are_not_hashed_twice(): void
    {
        $hash = Hash::make('A-secure-password');
        $employee = new Employee;
        $employee->password = $hash;

        $this->assertSame($hash, $employee->password);
    }
}
