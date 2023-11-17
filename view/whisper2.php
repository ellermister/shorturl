<!doctype html>
<html lang="<?php echo get_lang() ?>">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"
          integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/editor.md@1.5.0/css/editormd.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/editor.md@1.5.0/css/editormd.preview.css"/>

    <title>Whisper text!</title>
</head>
<body>
<div class="container-fluid" style="margin-top: 40px;">
    <a  href="<?php echo $url; ?>" class="btn btn-primary btn-lg" role="button">See details</a>
    <div id="editormd-view">
        <textarea style="display:none;" name="editormd-markdown-doc"></textarea>
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

<script>

function showMarkdown(whisper) {
    let editormdView = editormd.markdownToHTML("editormd-view", {
        markdown: whisper,
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

showMarkdown(window.atob("<?php echo base64_encode($whisper); ?>"));

</script>
</body>
</html>