<?php

namespace LVR\CreditCard\Tests\Unit;

use LVR\CreditCard\Tests\TestCase;
use LVR\CreditCard\CardExpirationDate;
use LVR\CreditCard\CardExpirationYear;
use LVR\CreditCard\CardExpirationMonth;
use Illuminate\Support\Facades\Validator;
use LVR\CreditCard\ExpirationDateValidator;

class CardExpirationTest extends TestCase
{
    /** @test  */
    public function it_checks_expiration_year()
    {
        // Invalid year
        $this->assertFalse($this->yearValidator(' ')->passes());
        $this->assertFalse($this->yearValidator(1)->passes());
        $this->assertFalse($this->yearValidator(1900)->passes());
        $this->assertFalse($this->yearValidator(2010)->passes());

        // Integer values
        $this->assertTrue($this->yearValidator(intval(date('Y')))->passes());

        // Not numbers
        $this->assertFalse($this->yearValidator('j2020')->passes());
        $this->assertFalse($this->yearValidator('asdasd')->passes());

        // Past year
        $this->assertFalse($this->yearValidator(date('Y', strtotime('-1 year')))->passes());

        // Next year
        $this->assertTrue($this->yearValidator(date('Y', strtotime('+1 year')))->passes());
    }

    /** @test */
    public function it_checks_expiration_month()
    {
        // Invalid month
        $this->assertFalse($this->monthValidator('')->passes());
        $this->assertFalse($this->monthValidator(13)->passes());
        $this->assertFalse($this->monthValidator(0)->passes());
        $this->assertFalse($this->monthValidator(20)->passes());

        // Integer values
        $this->assertTrue($this->monthValidator(intval(date('m')))->passes());

        // Not numbers
        $this->assertFalse($this->monthValidator('d5')->passes());
        $this->assertFalse($this->monthValidator('a')->passes());
        $this->assertFalse($this->monthValidator('12s')->passes());

        // Future month
        $this->assertTrue($this->monthValidator(date('m', strtotime('+1 month')))->passes());

        // Current year, past month
        $this->assertFalse($this->monthValidator(date('m', strtotime('-1 month')))->passes());

        // Current year, current month
        $this->assertTrue($this->monthValidator(date('m'))->passes());
    }

    /** @test */
    public function it_checks_expiration_date()
    {
        $this->assertFalse($this->dateValidator('-11', 'Y-m')->passes());
        $this->assertFalse($this->dateValidator(date('Y'), 'Y-m')->passes());
        $this->assertFalse($this->dateValidator(date('Y').'-', 'Y-m')->passes());
        $this->assertFalse($this->dateValidator('-', 'Y-m')->passes());
        $this->assertFalse($this->dateValidator(date('Y-m'), 'Ym')->passes());
        $this->assertFalse($this->dateValidator(date('Y-m-d H:i:s'), 'Y-m')->passes());
        $this->assertFalse($this->dateValidator(date('Ymd'), 'Ym')->passes());

        $this->assertTrue($this->dateValidator(date('Y-m'), 'Y-m')->passes());
        $this->assertTrue($this->dateValidator(date('Y/m'), 'Y/m')->passes());
        $this->assertTrue($this->dateValidator(date('mY'), 'mY')->passes());
        $this->assertTrue($this->dateValidator(date('m/Y'), 'm/Y')->passes());
        $this->assertTrue($this->dateValidator(date('Ym'), 'Ym')->passes());
        $this->assertTrue($this->dateValidator(date('YM'), 'YM')->passes());
        $this->assertTrue($this->dateValidator(date('Yn'), 'Yn')->passes());
        $this->assertTrue($this->dateValidator(date('yn'), 'yn')->passes());
        $this->assertTrue($this->dateValidator(date('ym'), 'ym')->passes());
        $this->assertTrue($this->dateValidator(date('y/m'), 'y/m')->passes());

        // Invalid month
        $this->assertFalse($this->dateValidator(date('Y-0'), 'Y-m')->passes());
        $this->assertFalse($this->dateValidator(date('Y0'), 'Ym')->passes());

        // Invalid year
        $this->assertFalse($this->dateValidator(date('1-n'), 'Y-m')->passes());
        $this->assertFalse($this->dateValidator(date('10-m'), 'Ym')->passes());
        $this->assertFalse($this->dateValidator(date('100-m'), 'Y-m')->passes());
        $this->assertFalse($this->dateValidator(date('1900m'), 'Ym')->passes());

        // Integer values
        $this->assertTrue($this->dateValidator(intval(date('Y').date('m')), 'Ym')->passes());

        // Not numbers
        $this->assertFalse($this->dateValidator('j2020-d5', 'Y-m')->passes());
        $this->assertFalse($this->dateValidator('Ym', 'Ym')->passes());
        $this->assertFalse($this->dateValidator('2020/asd', 'Y/m')->passes());

        // Past year, future month
        $timestamp = strtotime('+1 month');
        $d = (date('Y', $timestamp) - 1).'-'.date('m', $timestamp);
        $this->assertFalse($this->dateValidator($d, 'Y-m')->passes());

        // Current year, past month
        $timestamp = strtotime('-1 month');
        $d = date('Y', $timestamp).'-'.date('m', $timestamp);
        $this->assertFalse($this->dateValidator($d, 'Y-m')->passes());

        // Next year
        $timestamp = strtotime('+1 year');
        $d = date('Y', $timestamp).'-'.date('m', $timestamp);
        $this->assertTrue($this->dateValidator($d, 'Y-m')->passes());
    }

    /** @test **/
    public function it_can_be_called_directly()
    {
        $this->assertFalse(ExpirationDateValidator::validate('', date('m')));
        $this->assertFalse(ExpirationDateValidator::validate(date('y'), ''));
        $this->assertFalse(ExpirationDateValidator::validate(' ', ' '));
        $this->assertTrue(ExpirationDateValidator::validate(date('Y'), date('m')));
    }

    /**
     * @param string $year
     *
     * @return mixed
     */
    protected function yearValidator(string $year)
    {
        return Validator::make(
            [
                'expiration_year' => $year,
            ],
            ['expiration_year' => ['required', new CardExpirationYear(date('m'))]]
        );
    }

    /**
     * @param string $month
     *
     * @return mixed
     */
    protected function monthValidator(string $month)
    {
        return Validator::make(
            [
                'expiration_month' => $month,
            ],
            ['expiration_month' => ['required', new CardExpirationMonth(date('Y'))]]
        );
    }

    /**
     * @param string $date
     *
     * @param string $format
     *
     * @return mixed
     */
    protected function dateValidator(string $date, string $format)
    {
        return Validator::make(
            [
                'expiration_date' => $date,
            ],
            ['expiration_date' => ['required', new CardExpirationDate($format)]]
        );
    }
}
