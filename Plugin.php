<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Bangumi 追番列表
 *
 * @package BangumiList
 * @author SSpirits
 * @version 1.0.0
 * @link http://blog.sspirits.top
 */
class BangumiList_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('BangumiList_Plugin', 'replace');
        Helper::addRoute("route_BangumiList", "/BangumiList", "BangumiList_Action", 'action');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        Helper::removeRoute("route_BangumiList");
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        /**表单设置 */
        $userID = new Typecho_Widget_Helper_Form_Element_Text('userID', NULL, NULL, _t('输入你的 Bangumi 用户 ID'), _t('用户 ID 在个人主页中查询，是 "你的用户名 @" 后面的那串数字'));
        $userID->input->setAttribute('class', 'mini');
        $form->addInput($userID);
        $hasCache = new Typecho_Widget_Helper_Form_Element_Radio('hasCache', array('1' => _t('开启'), '0' => _t('关闭')), '0', _t('开启缓存'), _t('开启缓存需保证插件根目录可写'));
        $form->addInput($hasCache);
        $cacheTime = new Typecho_Widget_Helper_Form_Element_Text('cacheTime', NULL, '86400', _t('缓存过期时间'), _t('设置缓存过期时间，单位为秒，默认为一天'));
        $form->addInput($cacheTime);
    }

    public static function replace($content, $widget, $lastResult)
    {
        $content = empty($lastResult) ? $content : $lastResult;
        if (strpos($content, '[bangumi]') !== false) {
            $content = str_replace("[bangumi]", self::output(), $content);
        }
        return $content;
    }

    public static function output()
    {
        $cssPath = Helper::options()->pluginUrl . '/BangumiList/loading.css';
        return '<link rel="stylesheet" type="text/css" href="' . $cssPath . '" />' .
            '<div id="bangumiList" class="bangumiList">
        	    <div>
                    <div class="bangumi_loading">
                        <div class="inner one"></div>
                        <div class="inner two"></div>
                        <div class="inner three"></div>
                        </div>
                    <div class="bangumi_loading_text">追番列表加载中...</div>
                </div>
            </div>' . "
		<script>
			jQuery.ajax({
				type: 'GET',
				url: '" . Helper::options()->siteUrl . "/BangumiList',
				success: function(res) {
					$('#bangumiList').empty().append(res);
			
				},
				error:function(){
					$('#bangumiList').empty().text('加载失败');
				}
			});
		</script>";
    }
}