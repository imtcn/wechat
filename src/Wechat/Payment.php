<?php
/**
 * Payment.php
 *
 * Part of Overtrue\Wechat.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author    Frye <frye0423@gmail.com>
 * @copyright 2015 Frye <frye0423@gmail.com>
 * @link      https://github.com/0i
 * @link      http://blog.lost-magic.com
 * @link      https://github.com/thenbsp/Wechat
 */

namespace Overtrue\Wechat;

use Overtrue\Wechat\Utils\JSON;
use Overtrue\Wechat\Utils\SignGenerator;
use Overtrue\Wechat\Payment\UnifiedOrder;

/**
 * 微信支付
 */
class Payment
{
    /**
     * @var UnifiedOrder 统一下单
     */
    protected $unifiedOrder;
    
    public function __construct(UnifiedOrder $unifiedOrder)
    {
        $this->unifiedOrder = $unifiedOrder;
    }

    /**
     * @param bool|true $asJson
     *
     * @return array|string 获取配置文件（用于 WeixinJSBridge invoke 方式）
     */
    public function getConfig($asJson = true)
    {
        $config = $this->_generateConfig();
        return $asJson ? JSON::encode($config) : $config;
    }

    /**
     * @param bool|true $asJson
     *
     * @return array|string 获取配置文件（用于 Jssdk chooseWXPay 方式）
     */
    public function getConfigJssdk($asJson = true)
    {
        $config = $this->_generateConfig();
        $params = array(
            'timestamp' => $config['timeStamp'],
            'nonceStr'  => $config['nonceStr'],
            'package'   => $config['package'],
            'signType'  => $config['signType'],
            'paySign'   => $config['paySign']
        );
        return $asJson ? JSON::encode($params) : $params;
    }

    /**
     * @return array    生成配置
     * @throws Payment\Exception
     */
    private function _generateConfig()
    {
        $response = $this->unifiedOrder->getResponse();
        $business = $this->unifiedOrder->getBusiness();
        $config = array(
            'appId'     => $business->appid,
            'timeStamp' => (string) time(),
            'nonceStr'  => $response['nonce_str'],
            'package'   => 'prepay_id='.$response['prepay_id'],
            'signType'  => 'MD5'
        );
        
        $signGenerator = new SignGenerator($config);
        $signGenerator->onSortAfter(function(SignGenerator $that) use ($business) {
            $that->key = $business->mch_key;
        });
        $config['paySign'] = $signGenerator->getResult();
        return $config;
    }
}
