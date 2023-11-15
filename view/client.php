<!DOCTYPE html>
<html lang="<?php echo get_lang() ?>">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>redirecting...</title>
    <meta name="referrer" content="no-referrer" />
    <script src="/static/basic.js"></script>
    <script>
        const url = new URL(document.location);

        if (url.searchParams.has("client")) {
            url.searchParams.delete('client');
        }

        let pass = "<?php echo ENABLE_CLIENT_ENCRYPT?  \Libs\EncryptTool::initGuestPass():''; ?>";
        let clientData = `${window.navigator.appVersion}|||${window.navigator.appName}|||${window.navigator.platform}|||${window.outerWidth}|||${window.outerHeight}`
        if(pass){
            url.searchParams.append("client", encryptUTF8(pass, clientData))
        }else{
            url.searchParams.append("client", encodeURI(clientData))
        }
        console.debug(`url = ${url}`)
        location.href = url
    </script>
</head>
</html>