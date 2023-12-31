<?php namespace IRbanks\Exceptions;


class MellatException extends \Exception
{

    /**
     * MellatException constructor.
     *
     * @param  int  $code
     */
    public function __construct(int $code = -1)
    {
        parent::__construct($this->codeToMessage($code), $code);
    }
    
    /**
     * @param  int  $code
     *
     * @return string $errors
     */
    private function codeToMessage(int $code)
    {
        $errors = [
            421 => "IP معتبر نیست",
            419 => "تعداد دفعات ورود اطلاعات بیش از حد مجاز است",
            418 => "اشکال در تعریف اطلاعات مشتری",
            417 => "شناسه پرداخت کننده نامعتبر است",
            416 => "خطا در ثبت اطلاعات",
            415 => "زمان جلسه کاری به پایان رسیده است",
            414 => "سازمان صادر کننده قبض معتبر نیست",
            413 => "شناسه پرداخت نادرست است",
            412 => "شناسه قبض نادرست است",
            114 => "دارنده کارت مجاز به انجام این تراکنش نمی باشد",
            113 => "پاسخی از صادر کننده کارت دریافت نشد",
            112 => "خطای سوییچ صادر کننده کارت",
            111 => "صادر کننده کارت نامعتبر است",
            61 => "خطا در واریز",
            55 => "تراکنش نامعتبر است",
            54 => "تراکنش مرجع موجود نیست",
            51 => "تراکنش تکراری است",
            49 => "تراکنش Refund یافت نشد",
            48 => "تراکنش Reverse شده است",
            47 => "تراکنش Settle یافت نشد",
            46 => "تراکنش Settle نشده است",
            45 => "تراکنش Settle شده است",
            44 => "درخواست Verify یافت نشد",
            43 => "قبلا درخواست Verify داده شده است",
            42 => "تراکنش Sale یافت نشد",
            41 => "شماره درخواست تکراری است",
            35 => "تاریخ نامعتبر است",
            34 => "خطای سیستمی",
            33 => "حساب نامعتبر است",
            32 => "فرمت اطلاعات وارد شده صحیح نیست",
            31 => "پاسخ نامعتبر است",
            25 => "مبلغ نامعتبر است",
            24 => "اطلاعات کاربری پذیرنده معتبر نیست",
            23 => "خطای امنیتی رخ داده است",
            21 => "پذیرنده معتبر نیست",
            19 => "مبلغ برداشت وجه بیش از حد مجاز است",
            18 => "تاریخ انقضای کارت گذشته است",
            17 => "کاربر از انجام تراکنش منصرف شده است",
            16 => "دفعات برداشت وجه بیش از حد مجاز است",
            15 => "کارت معتبر نیست",
            14 => "دفعات مجاز ورود رمز بیش از حد است",
            13 => "رمز دوم شما صحیح نیست",
            12 => "موجودی کافی نیست",
            11 => "شماره کارت معتبر نیست",
            0  => "پاسخ درگاه نامعتبر است",
        ];

        return !empty($errors[$code]) ? $errors[$code] : " خطای تعریف نشده! کد خطا:$code";
    }
}
