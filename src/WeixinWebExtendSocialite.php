<?php

namespace SocialiteProviders\WeixinWeb;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WeixinWebExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('weixinweb', __NAMESPACE__.'\Provider');
    }
}
