<?php

/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/14
 * Time: 01:25
 */

return [
    'en' => [],
    'zh' => [
        'GENERATE'             => '生成',
        'GITHUB'               => 'Github 地址',
        'ABOUT'                => '关于',
        'GENERATE SHORT URL'   => '生成短网址',
        'Quickly generate URL' => '快速生成URL',
        'Enter URL link'       => '输出URL链接',
        'Generate'             => '生成',
        'Firewall'             => '拦截器',
        'Endpoint'             => '跳转方式',

        'Normal'             => '原始',
        'No referer'         => '无Referer',
        'Encrypt redirect'   => '加密跳转',
        'Fake page'          => '伪装页面',
        'Redirect once'      => '阅后即焚',
        'Password access'    => '密码访问',
        'Whisper text'       => '附加图文',
        'PC access only'     => '仅限PC访问',
        'Mobile access only' => '仅限手机访问',
        'Ban China Browser'  => '屏蔽中国大陆浏览器',

        'Jump directly to the website'                                        => '直接跳转到目标网站',
        'No Referer parameter'                                                => '无 Referer 参数，目标网站无法获取来源站地址',
        'Encrypted access, anti-crawler'                                      => '加密跳转参数信息，反大部分爬虫抓取探测',
        'Use random news, forums, product website information to fool robots' => '使用随机信息、论坛、商品来骗过机器人爬虫',
        'Jump only once'                                                      => '一次性跳转(阅后即焚)',
        'Password required'                                                   => '将为你生成密码，访问时需要密码验证',
        'Append rich text information'                                        => '附加富文本信息，您可以在此留言并分享给您的其他社交媒体用户',
        'Only PC users can access this page'                                  => '仅限PC用户访问该地址',
        'Only Mobile users can access this page'                              => '仅限手机用户访问该地址',
        'Mainland China access only'                                          => '仅限中国大陆访问',
        'Non-mainland China access only'                                      => '仅限非中国大陆访问',
        'Please use a non-China browser'                                      => '请使用安全浏览器访问: 如 Chrome, Edge, Firefox',
        'Please copy this link to open in other browsers'                     => '请复制这个链接到其他浏览器打开',

        'Access only to users in mainland China'          => '仅限中国大陆用户访问',
        'Only access users who are not in mainland China' => '仅限非中国大陆用户访问',

        'This site generates a total of :url_record_history links，Currently active :url_active_history' => '当前站点历史生成链接:url_record_history个，当前有效:url_active_history个',

        'Password verification failed'                     => '密码验证失败',
        'Wrong encode_type parameter'                      => '错误的 encode_type 参数',
        'The url cannot be empty'                          => 'URL不能为空',
        'Too long url'                                     => 'URL太长',
        'Too much content'                                 => '内容过多',
        'Link created successfully'                        => '链接创建成功',
        'The link can only be accessed via mobile devices' => '该链接只能通过手机移动设备访问',
        'The link can only be accessed via PC devices'     => '该链接只能通过电脑设备访问',
        'The link has expired'                             => '链接已经过期',
    ],
    'ja' => [
        'GENERATE'             => '生成',
        'GITHUB'               => 'Github',
        'ABOUT'                => 'ついて',
        'GENERATE SHORT URL'   => '短いURLを生成する',
        'Quickly generate URL' => 'URLをすばやく生成する',
        'Enter URL link'       => 'URLリンクを入力します',
        'Generate'             => '生成',
        'Firewall'             => 'ファイアウォール',
        'Endpoint'             => '終点',

        'Normal'                                          => 'デフォルト',
        'No referer'                                      => '「Referer」パラメータなし',
        'Encrypt redirect'                                => '暗号化されたアクセス',
        'Fake Page'                                       => '偽のウェブページ',
        'Redirect once'                                   => '1回限りの訪問',
        'Password access'                                 => 'パスワードの検証',
        'Whisper text'                                    => '追加テキスト',
        'PC access only'                                  => 'PCアクセスのみ',
        'Mobile access only'                              => 'モバイルアクセスのみ',
        'Mainland China access only'                      => '中国本土のユーザーのみがアクセス可能',
        'Non-mainland China access only'                  => '中国本土以外のユーザーに限定',
        'Ban China Browser'                               => '中国のブラウザを禁止する',
        'Please use a non-China browser'                  => '中国以外のブラウザを使用してください',
        'Please copy this link to open in other browsers' => '他のブラウザで開くには、このリンクをコピーしてください',

        'Jump directly to the website'                                        => 'ターゲットのWebサイトに直接ジャンプします',
        'No Referer parameter'                                                => '「Referer」パラメータがないと、ターゲットWebサイトは送信元ステーションのアドレスを取得できません',
        'Encrypted access, anti-crawler'                                      => '暗号化されたジャンプパラメータ情報、ほとんどのクローラーの検出防止',
        'Use random news, forums, product website information to fool robots' => 'ロボットを欺くためにランダムなニュース、フォーラム、製品のウェブサイト情報を生成する',
        'Jump only once'                                                      => 'リンクには一度しかアクセスできず、非常に安全です',
        'Password required'                                                   => 'リンクのパスワードを生成し、アクセス時に確認します',
        'Append rich text information'                                        => 'テキストメッセージを残すことができます',
        'Only PC users can access this page'                                  => 'このアドレスにアクセスできるのはPCユーザーのみです',
        'Only Mobile users can access this page'                              => 'このアドレスにアクセスできるのは携帯電話ユーザーのみです',

        'Access only to users in mainland China'          => '中国本土のユーザーのみがアクセス可能', //このウェブサイトは中国本土でのみアクセスできます
        'Only access users who are not in mainland China' => '中国本土以外のユーザーに限定',

        'This site generates a total of :url_record_history links，Currently active :url_active_history' => '現在のサイト履歴生成リンク:url_record_historyつのリンク、現在有効:url_active_historyつのリンク',

        'Password verification failed'                     => 'パスワードの確認に失敗しました',
        'Wrong encode_type parameter'                      => '間違ったencode_typeパラメータ',
        'The url cannot be empty'                          => 'URLを空にすることはできません',
        'Too long url'                                     => 'URLが長すぎます',
        'Too much content'                                 => 'コンテンツが多すぎます',
        'Link created successfully'                        => 'リンクが正常に作成されました',
        'The link can only be accessed via mobile devices' => 'リンクにはモバイルデバイス経由でのみアクセスできます',
        'The link can only be accessed via PC devices'     => 'リンクにはモバイルデバイスからのみアクセスできます',
        'The link has expired'                             => 'リンクの有効期限が切れています',
    ]
];
