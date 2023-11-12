<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/13
 * Time: 1:50
 */

namespace Libs;


class AAEncode
{
    function charCodeAt($str, $index)
    {
        $char = mb_substr($str, $index, 1, 'UTF-8');
        if (mb_check_encoding($char, 'UTF-8')) {
            $ret = mb_convert_encoding($char, 'UTF-32BE', 'UTF-8');
            return hexdec(bin2hex($ret));
        } else {
            return null;
        }
    }

    function uchr($codes)
    {
        if (is_scalar($codes)) $codes = func_get_args();
        $str = '';
        foreach ($codes as $code) {
            $buf = html_entity_decode('&#' . $code . ';', ENT_NOQUOTES, 'UTF-8');
            $buf == '&#' . $code . ';' && ($buf = mb_convert_encoding('&#' . intval($code) . ';', 'UTF-8', 'HTML-ENTITIES'));
            $str .= $buf;
        }
        return $str;
    }

    function aaEncode($javascript)
    {
        $b = [
            "(c^_^o)",
            "(ﾟΘﾟ)",
            "((o^_^o) - (ﾟΘﾟ))",
            "(o^_^o)",
            "(ﾟｰﾟ)",
            "((ﾟｰﾟ) + (ﾟΘﾟ))",
            "((o^_^o) +(o^_^o))",
            "((ﾟｰﾟ) + (o^_^o))",
            "((ﾟｰﾟ) + (ﾟｰﾟ))",
            "((ﾟｰﾟ) + (ﾟｰﾟ) + (ﾟΘﾟ))",
            "(ﾟДﾟ) .ﾟωﾟﾉ",
            "(ﾟДﾟ) .ﾟΘﾟﾉ",
            "(ﾟДﾟ) ['c']",
            "(ﾟДﾟ) .ﾟｰﾟﾉ",
            "(ﾟДﾟ) .ﾟДﾟﾉ",
            "(ﾟДﾟ) [ﾟΘﾟ]"
        ];
        $r = "ﾟωﾟﾉ= /｀ｍ´）ﾉ ~┻━┻   //*´∇｀*/ ['_']; o=(ﾟｰﾟ)  =_=3; c=(ﾟΘﾟ) =(ﾟｰﾟ)-(ﾟｰﾟ); ";
        if (preg_match('/ひだまりスケッチ×(365|３５６)\s*来週も見てくださいね[!！]/', $javascript)) {
            $r .= "X=_=3; ";
            $r .= "\r\n\r\n    X / _ / X < \"来週も見てくださいね!\";\r\n\r\n";
        }
        $r .= "(ﾟДﾟ) =(ﾟΘﾟ)= (o^_^o)/ (o^_^o);" .
            "(ﾟДﾟ)={ﾟΘﾟ: '_' ,ﾟωﾟﾉ : ((ﾟωﾟﾉ==3) +'_') [ﾟΘﾟ] " .
            ",ﾟｰﾟﾉ :(ﾟωﾟﾉ+ '_')[o^_^o -(ﾟΘﾟ)] " .
            ",ﾟДﾟﾉ:((ﾟｰﾟ==3) +'_')[ﾟｰﾟ] }; (ﾟДﾟ) [ﾟΘﾟ] =((ﾟωﾟﾉ==3) +'_') [c^_^o];" .
            "(ﾟДﾟ) ['c'] = ((ﾟДﾟ)+'_') [ (ﾟｰﾟ)+(ﾟｰﾟ)-(ﾟΘﾟ) ];" .
            "(ﾟДﾟ) ['o'] = ((ﾟДﾟ)+'_') [ﾟΘﾟ];" .
            "(ﾟoﾟ)=(ﾟДﾟ) ['c']+(ﾟДﾟ) ['o']+(ﾟωﾟﾉ +'_')[ﾟΘﾟ]+ ((ﾟωﾟﾉ==3) +'_') [ﾟｰﾟ] + " .
            "((ﾟДﾟ) +'_') [(ﾟｰﾟ)+(ﾟｰﾟ)]+ ((ﾟｰﾟ==3) +'_') [ﾟΘﾟ]+" .
            "((ﾟｰﾟ==3) +'_') [(ﾟｰﾟ) - (ﾟΘﾟ)]+(ﾟДﾟ) ['c']+" .
            "((ﾟДﾟ)+'_') [(ﾟｰﾟ)+(ﾟｰﾟ)]+ (ﾟДﾟ) ['o']+" .
            "((ﾟｰﾟ==3) +'_') [ﾟΘﾟ];(ﾟДﾟ) ['_'] =(o^_^o) [ﾟoﾟ] [ﾟoﾟ];" .
            "(ﾟεﾟ)=((ﾟｰﾟ==3) +'_') [ﾟΘﾟ]+ (ﾟДﾟ) .ﾟДﾟﾉ+" .
            "((ﾟДﾟ)+'_') [(ﾟｰﾟ) + (ﾟｰﾟ)]+((ﾟｰﾟ==3) +'_') [o^_^o -ﾟΘﾟ]+" .
            "((ﾟｰﾟ==3) +'_') [ﾟΘﾟ]+ (ﾟωﾟﾉ +'_') [ﾟΘﾟ]; " .
            "(ﾟｰﾟ)+=(ﾟΘﾟ); (ﾟДﾟ)[ﾟεﾟ]='\\\\'; " .
            "(ﾟДﾟ).ﾟΘﾟﾉ=(ﾟДﾟ+ ﾟｰﾟ)[o^_^o -(ﾟΘﾟ)];" .
            "(oﾟｰﾟo)=(ﾟωﾟﾉ +'_')[c^_^o];" .
            "(ﾟДﾟ) [ﾟoﾟ]='\\\"';" .
            "(ﾟДﾟ) ['_'] ( (ﾟДﾟ) ['_'] (ﾟεﾟ+";
        $r .= "(ﾟДﾟ)[ﾟoﾟ]+ ";

        for ($i = 0; $i < mb_strlen($javascript); $i++) {
            $n = $this->charCodeAt($javascript, $i);
            $t = "(ﾟДﾟ)[ﾟεﾟ]+";
            if ($n <= 127) {
                $t .= preg_replace_callback('/[0-7]/', function ($c) use ($b) {
                    return $b[$c[0]] . "+ ";
                }, ((string)decoct($n)));
            } else {
                if (preg_match('/[0-9a-f]{4}$/', '000' . ((string)dechex($n)), $result)) {
                    $m = $result[0];
                } else {
                    $m = '';
                }
                $t .= "(oﾟｰﾟo)+ " . preg_replace_callback('/[0-9a-f]/i', function ($c) use ($b) {
                        return $b[hexdec($c[0])] . "+ ";
                    }, $m);
            }
            $r .= $t;
        }

        $r .= "(ﾟДﾟ)[ﾟoﾟ]) (ﾟΘﾟ)) ('_');";
        return $r;

    }
}