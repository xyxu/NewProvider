<?php

namespace SocialiteProviders\WeixinWeb;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * @var string
     */
    protected $openId;

    /**
     * @var array
     */
    protected $scopes = ['snsapi_login'];

    /**
     * {@inheritdoc}.
     *
     * @see \Laravel\Socialite\Two\AbstractProvider::getAuthUrl()
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://open.weixin.qq.com/connect/qrconnect', $state);
    }

    protected function buildAuthUrlFromBase($url, $state)
    {
        $session = $this->request->getSession();

        return $url. '?'. http_build_query($this->getCodeFields($state), '', '&', $this->encodingType). '#wechat_redirect';
    }

    protected function getCodeFields($state = null)
    {
        return [
            'appid' => $this->clientId, 'redirect_uri' => $this->redirectUrl,
            'response_type' => 'code',
            'scope' => $this->formatScopes($this->scopes, $this->scopeSeparator), 'state' => $state,
        ];
    }

    /**
     * {@inheritdoc}.
     *
     * @see \Laravel\Socialite\Two\AbstractProvider::getTokenUrl()
     */
    protected function getTokenUrl()
    {
        return 'https://api.weixin.qq.com/sns/oauth2/access_token';
    }

    /**
     * {@inheritdoc}.
     *
     * @see \Laravel\Socialite\Two\AbstractProvider::getUserByToken()
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.weixin.qq.com/sns/userinfo', [
            'query' => [
                'access_token' => $token,
                'openid' => $this->openId,
                'lang' => 'zh_CN',    //简体中文
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}.
     *
     * @see \Laravel\Socialite\Two\AbstractProvider::mapUserToObject()
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['openid'],
            'nickname' => $user['nickname'],
            'avatar' => $user['headimgurl'],
            'name' => null, 
            'email' => null,
        ]);
    }

    /**
     * {@inheritdoc}.
     *
     * @see \Laravel\Socialite\Two\AbstractProvider::getTokenFields()
     */
    protected function getTokenFields($code)
    {
        return [
            'appid' => $this->clientId,
            'secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];
    }

    /**
     * {@inheritdoc}.
     *
     * @see \Laravel\Socialite\Two\AbstractProvider::getAccessToken()
     */
    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()->get($this->getTokenUrl(), [
            'query' => $this->getTokenFields($code),
        ]);

        return $this->parseAccessToken($response->getBody()->getContents());
    }

    protected function parseAccessToken($body)
    {
        $jsonArray = json_decode($body, true);
        $this->openId = $jsonArray['openid'];//记录openid

        return $jsonArray['access_token'];
    }

    /**
     * @param mixed $response
     *
     * @return string
     */
    protected function removeCallback($response)
    {
        if (strpos($response, 'callback') !== false) {
            $lpos = strpos($response, '(');
            $rpos = strrpos($response, ')');
            $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
        }

        return $response;
    }

}
