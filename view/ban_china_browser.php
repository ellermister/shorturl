<!DOCTYPE html>
<html lang="<?php echo get_lang() ?>">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WARNING</title>
    <meta name="referrer" content="no-referrer" />
    <style>
        .main-tips{
            text-align: center;
            padding: 20px;
            flex-direction: column;
            align-items: center;
            display: flex;
        }
        .main-tips h2{
            user-select: none;
        }
        .main-tips pre{
            padding: 12px;
            background: #ccc;
        }
    </style>
</head>

<body>
    <div class="main-tips">
        <h2><?php echo __('Please use a non-China browser');?></h2>
        <pre onclick="allSelectContent()" id="url"></pre>
        <label><?php echo __('Please copy this link to open in other browsers');?></label>
    </div>
    <script>
        let safeURL = new URL(location.href)
        safeURL.search = ""

        let url = document.getElementById('url')
        url.innerText = safeURL.href

        function allSelectContent(){
            let url = document.getElementById('url')
            var selection = document.getSelection()

            var range = document.createRange()
            range.selectNode(url)

            selection.removeAllRanges()
            selection.addRange(range)
        }
    </script>
</body>
</html>