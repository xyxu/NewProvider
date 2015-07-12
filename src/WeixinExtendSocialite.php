<?php

namespace SocialiteProviders\Weixin;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WeixinExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('weixin', __NAMESPACE__.'\Provider');
    }
}
