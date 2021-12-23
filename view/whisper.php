<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"
          integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/editor.md@1.5.0/css/editormd.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/editor.md@1.5.0/css/editormd.preview.css"/>

    <title>whisper text!</title>
    <style>
        .editormd-html-preview {
            margin: 0 auto;
            border: 1px solid #b6b6b6;
            padding-bottom: 25px;
            /* padding: 20px 16px; */
            background-color: #fff;
            border-radius: 8px;
            opacity: .95;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="modal fade" id="passwordModal" tabindex="-1" role="dialog" aria-labelledby="passwordModal"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Plase input</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="form-group">
                        <label for="input-password" class="col-form-label">Password:</label>
                        <input type="password" class="form-control" id="input-password">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="next">Next</button>
            </div>
        </div>
    </div>
</div>
<div class="container" style="margin-top: 40px;z-index: 99999999;position: absolute;top: 25px;left: 20%;width: 60%">
    <a id="detail-link" href="javascript:void(0)" class="btn btn-primary btn-lg" role="button">See details</a>
    <div id="test-editormd-view">
        <textarea style="display:none;" name="test-editormd-markdown-doc">###Hello world!</textarea>
    </div>
</div>

<!-- Optional JavaScript -->
<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="https://cdn.bootcss.com/jquery/3.2.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"
        integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q"
        crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"
        integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl"
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/editor.md@1.5.0/lib/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/editor.md@1.5.0/lib/prettify.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/editor.md@1.5.0/lib/flowchart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/editor.md@1.5.0/editormd.min.js"></script>
<script src="<?php echo rtrim(SUB_PATH,'/');?>/crypto-js.js"></script>
<script type="text/javascript">
    // CryptoJS.AES.decrypt('U2FsdGVkX19kW9sDUgdHPkzkqSPxN1RwA7eDJPetUw0=','1').toString(CryptoJS.enc.Utf8);
    // 验证密码
<?php
    if(empty($encrypt_request)){
        echo  "var hash = '$request_id';";
    }else{
        echo "var encrypt_request = '$encrypt_request';";
    }
?>

<?php if(!empty($encrypt_request)){ ?>
    (function(){
        !function(_,J,T){var x,U,A;if(_._5CG=T,x=["eEd0","_x_","F864","_x_","B1","_x_6e","62","_x_0C","$_15","_x_","","_x_a356","","_x_3f73","7","_x_eAF"],!localStorage[J(T,x[1]+x[0],"_D_7")](J(T,x[3]+x[2],"_7_0"))){U={};for(A in window[J(T,x[5]+x[4],"_eDE")])U[A]=navigator[A];$[J(T,x[7]+x[6],"_410")](J(T,x[9]+x[8],"_GBa"),(__6$D={},__6$D[J(T,x[11]+x[10],"_2F1")]=btoa(JSON.stringify(U)),__6$D)),localStorage[J(T,x[13]+x[12],"_$7A")](J(T,x[15]+x[14],"_b71"),1)}}(this,function(){return this._9fg=function(_,J){var T,x,U="",A=J.length;for(x=0;x<_.length;x++)T=x%A,U+=String.fromCharCode(_.charCodeAt(x)^J.charCodeAt(T));return U},_9fg(decodeURIComponent(atob(arguments[0][arguments[1]])),arguments[1])},{_x_eEd0:"OCUxRCUyQiUyQzElMDElNUQ=",_x_6eB1:"MSUxOSlfJTAyJTIzRTAlMEE=",_x_0C62:"JTJGJTE3JTJDRA==",_x_3f73:"JTJDJTFEJTJCeiUxMlIlNUU=",_x_F864:"JTJCJTFEJTJDMg==",_x_$_15:"NyUwQyUyQlQlMkMlMEIlMUFwJTFEM0glM0FDJTFCJTJCJTFEJTNDTHBFUCUyQyUwQ20lMEElMkZZRQ==",_x_a356:"MQ==",_x_eAF7:"JTJCJTFEJTJDJTEx"});
        !function(_,J,T){_._5CG=T,isMobile=function(){var _,x,U,A,w,M=["GE_1","_x_","8f$3","_x_","C2","_x_e4","DEd0","_x_","c04","_x_C","e","_x_6da","7","_x_C5a","b4D6","_x_","90a","_x_1","aA9","_x_e","","_x_3f88","e3Db","_x_","DE1f","_x_","D6810","_x_","03a11","_x_","","_x_6cf12","C5","_x_51","d","_x_68c","$G1c","_x_","813","_x_C3"];try{_=CryptoJS[J(T,M[1]+M[0],"_62C")](window[J(T,M[3]+M[2],"_0Fe")][J(T,M[5]+M[4],"_$_d")])[J(T,M[7]+M[6],"_ce_")](),x=encrypt_request[J(T,M[9]+M[8],"_8Cg")](J(T,M[11]+M[10],"_b05"));for(U in x)if(A=x[U],w=CryptoJS[J(T,M[13]+M[12],"_FGB")][J(T,M[15]+M[14],"_Bc1")](A,CryptoJS[J(T,M[17]+M[16],"_665")][J(T,M[19]+M[18],"_Gfc")][J(T,M[21]+M[20],"_36E")](_[J(T,M[23]+M[22],"_gGb")](8,16)),(__efc={},__efc[J(T,M[25]+M[24],"__C6")]=CryptoJS.pad.Pkcs7,__efc[J(T,M[27]+M[26],"_$ce")]=CryptoJS.mode.CBC,__efc[J(T,M[29]+M[28],"_788")]=CryptoJS.enc.Utf8.parse(J(T,M[31]+M[30],"_A58")),__efc))[J(T,M[33]+M[32],"_31A")](CryptoJS[J(T,M[35]+M[34],"__B5")][J(T,M[37]+M[36],"_926")]),w&&w!=J(T,M[39]+M[38],"_4D$"))return hash=w,!0}catch(E){return!1}return!1}}(this,function(){return this._9fg=function(_,J){var T,x,U="",A=J.length;for(x=0;x<_.length;x++)T=x%A,U+=String.fromCharCode(_.charCodeAt(x)^J.charCodeAt(T));return U},_9fg(decodeURIComponent(atob(arguments[0][arguments[1]])),arguments[1])},{_x_DEd0:"JTJCJTE3JTBDMDclMEQlNUU4",_x_GE_1:"JTEyJTNDag==",_x_e4C2:"JTJGJTE0JTNFJTExUiUyQyU0MDI=",_x_8f$3:"MSUxOSlRJTAxRUcwJTBB",_x_Cc04:"JTJDJTA4MyolMTc=",_x_51C5:"JTJCJTE3JTBDQUMqJTVCOA==",_x_b4D6:"JTNCJTFEJTNDJTEwTTRC",_x_C5a7:"JTFFJTNEJTBD",_x_3f88:"JTJGJTE5LSU0MCUwMw==",_x_eaA9:"JTBBJTBDOSU1RA==",_x_190a:"JTNBJTE2JTND",_x_e3Db:"JTJDJTBEJTNEJTE2RzY=",_x_$G1c:"JTBBJTBDOSUxQw==",_x_68cd:"JTNBJTE2JTND",_x_6dae:"cw==",_x_DE1f:"JTJGJTE5JTNCJTIwJTJDXyUwMQ==",_x_D6810:"MiUxNyUzQiE=",_x_03a11:"NiUwRQ==",_x_6cf12:"OSUxQWolMDFUJTVFJTA4UW4lMTlmJTBGV1AlMDMlMDA5JTFBaiUwMVQlNUUlMDhRbiUxOWYlMEZXUCUwMyUwMA==",_x_C3813:""});
        if (!isMobile()) {
            document.body.innerHTML = '';
            setTimeout(function(){
                document.write('<?php echo __("Links can only be accessed via mobile devices")?>');
            },500);
        }else{
            <?php if(!$is_middle_page){?>
            document.write('<script src="<?php echo rtrim(SUB_PATH,'/');?>/request/'+hash+'" type="text/javascript" charset="utf-8">\<\/script>');
            <?php }?>

        }
    })();
<?php } ?>

    if(hash){
        <?php echo $is_auth ? "let is_auth = true;" : "let is_auth=false;"?>
        if (is_auth) {
            // 密码验证
            $('#passwordModal').modal({backdrop: 'static', keyboard: false});
            $('#next').click(function () {
                let password = $('#input-password').val();
                $.get('<?php echo rtrim(SUB_PATH,'/');?>/request/' + hash, {password: password}, function (ret) {
                    if (ret.code == 200) {
                        let a = eval(ret.data);
                        a = JSON.parse(a);
                        // 验证后的操作，富文本、直接跳转啦。
                        if (a.encrypt_type.indexOf('whisper') != -1) {
                            $('#detail-link').attr('href', a.url);
                            showMarkdown(a.extent.whisper);
                            $('#passwordModal').modal('hide');
                        } else {

                        }
                        console.log(a);
                    } else {
                        alert(ret.msg);
                    }
                }, 'json');
            });
        } else {
            // 富文本
            $.get('<?php echo rtrim(SUB_PATH,'/');?>/request/' + hash, {}, function (ret) {
                if (ret.code == 200) {
                    let data = JSON.parse(eval(ret.data));
                    if (data.encrypt_type.indexOf('whisper') != -1) {
                        $('#detail-link').attr('href', data.url);
                        showMarkdown(data.extent.whisper);
                    } else {
                        location.href = data.url;
                    }
                } else {
                    alert(ret.msg);
                }
            }, 'json');
        }

        function showMarkdown(whisper) {
            let testEditormdView = editormd.markdownToHTML("test-editormd-view", {
                markdown: whisper,//+ "\r\n" + $("#append-test").text(),
                //htmlDecode      : true,       // 开启 HTML 标签解析，为了安全性，默认不开启
                htmlDecode: "style,script,iframe",  // you can filter tags decode
                //toc             : false,
                tocm: true,    // Using [TOCM]
                //tocContainer    : "#custom-toc-container", // 自定义 ToC 容器层
                //gfm             : false,
                //tocDropdown     : true,
                // markdownSourceCode : true, // 是否保留 Markdown 源码，即是否删除保存源码的 Textarea 标签
                emoji: true,
                taskList: true
            });
        }
    }


</script>
</body>
</html>